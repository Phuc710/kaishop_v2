<?php

/**
 * SePay Webhook Controller
 * Receives POST webhooks from SePay when bank transactions occur.
 * Matches deposit codes, credits user balances, and prevents duplicates.
 */
class SepayWebhookController extends Controller
{
    private $depositModel;

    public function __construct()
    {
        $this->depositModel = new PendingDeposit();
    }

    /**
     * POST /api/sepay/webhook — Receive SePay webhook.
     */
    public function handle()
    {
        global $chungapi;
        $pdo = Database::getInstance()->getConnection();

        // 1. Validate API Key
        $expectedKey = trim((string) ($chungapi['sepay_api_key'] ?? ''));
        $authHeader = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));

        if ($expectedKey !== '') {
            if ($authHeader === '') {
                http_response_code(401);
                return $this->json(['success' => false, 'message' => 'Missing API key']);
            }

            // SePay sends "Apikey YOUR_KEY"
            $providedKey = '';
            if (stripos($authHeader, 'Apikey ') === 0) {
                $providedKey = trim(substr($authHeader, 7));
            }

            if ($providedKey === '' || !hash_equals($expectedKey, $providedKey)) {
                http_response_code(401);
                return $this->json(['success' => false, 'message' => 'Invalid API key']);
            }
        }

        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if ($contentType !== '' && strpos($contentType, 'application/json') === false) {
            http_response_code(415);
            return $this->json(['success' => false, 'message' => 'Unsupported content type']);
        }

        // 2. Parse JSON body
        $rawBody = file_get_contents('php://input');
        if (!is_string($rawBody) || $rawBody === '') {
            http_response_code(400);
            return $this->json(['success' => false, 'message' => 'Empty body']);
        }
        if (strlen($rawBody) > 1024 * 64) {
            http_response_code(413);
            return $this->json(['success' => false, 'message' => 'Payload too large']);
        }
        $data = json_decode($rawBody, true);

        if (!is_array($data)) {
            http_response_code(400);
            return $this->json(['success' => false, 'message' => 'Invalid JSON']);
        }

        // 3. Extract fields
        $sepayId = (int) ($data['id'] ?? 0);
        $transferType = trim((string) ($data['transferType'] ?? ''));
        $transferAmount = (int) ($data['transferAmount'] ?? 0);
        $content = trim((string) ($data['content'] ?? ''));
        $gateway = trim((string) ($data['gateway'] ?? ''));
        $accountNumber = trim((string) ($data['accountNumber'] ?? ''));
        $referenceCode = trim((string) ($data['referenceCode'] ?? ''));
        $transactionDate = trim((string) ($data['transactionDate'] ?? ''));

        // Only process incoming money
        if ($transferType !== 'in') {
            return $this->json(['success' => true, 'message' => 'Ignored: not incoming transfer']);
        }

        if ($sepayId <= 0 || $transferAmount <= 0) {
            http_response_code(400);
            return $this->json(['success' => false, 'message' => 'Missing required fields']);
        }

        // 4. Anti-duplicate: check if this SePay transaction was already processed
        $existing = $this->depositModel->findBySepayId($sepayId);
        if ($existing) {
            // Already processed — return success to avoid SePay retrying
            return $this->json(['success' => true, 'message' => 'Already processed']);
        }

        // 5. Extract deposit code from content (pattern: "kai" + alphanumeric)
        $depositCode = null;
        if (preg_match('/\b(kai[A-Z0-9]{10,20})\b/i', $content, $matches)) {
            $depositCode = (string) $matches[1];
        }

        if (!$depositCode) {
            // No deposit code found — log but still return success
            Logger::info('Billing', 'webhook_no_code', 'SePay webhook: no deposit code found', [
                'sepay_id' => $sepayId,
                'content' => $content,
                'amount' => $transferAmount,
            ]);
            return $this->json(['success' => true, 'message' => 'No deposit code matched']);
        }

        // 6. Find pending deposit
        $deposit = $this->depositModel->findByCode($depositCode);

        if (!$deposit) {
            Logger::info('Billing', 'webhook_code_not_found', 'SePay webhook: deposit code not found in DB', [
                'sepay_id' => $sepayId,
                'deposit_code' => $depositCode,
                'amount' => $transferAmount,
            ]);
            return $this->json(['success' => true, 'message' => 'Deposit code not found']);
        }

        if ($deposit['status'] !== 'pending') {
            return $this->json(['success' => true, 'message' => 'Deposit already processed']);
        }

        // 7. Verify amount matches
        $expectedAmount = (int) $deposit['amount'];
        if ($transferAmount < $expectedAmount) {
            Logger::warning('Billing', 'webhook_amount_mismatch', 'SePay webhook: amount mismatch', [
                'sepay_id' => $sepayId,
                'expected' => $expectedAmount,
                'received' => $transferAmount,
                'deposit_code' => $depositCode,
            ]);
            // Still process but log the discrepancy — use the actual transferred amount
        }

        // 8. Calculate bonus
        $bonusPercent = (int) $deposit['bonus_percent'];
        $bonusAmount = (int) ($transferAmount * $bonusPercent / 100);
        $totalCredit = $transferAmount + $bonusAmount;

        // 9. Credit user balance + mark complete + history insert (transactional with row lock)
        $username = (string) $deposit['username'];
        $userId = (int) $deposit['user_id'];
        $depositId = (int) $deposit['id'];

        try {
            $pdo->beginTransaction();

            $lockStmt = $pdo->prepare("SELECT `id`, `status`, `sepay_transaction_id` FROM `pending_deposits` WHERE `id` = ? LIMIT 1 FOR UPDATE");
            $lockStmt->execute([$depositId]);
            $locked = $lockStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$locked || (string) ($locked['status'] ?? '') !== 'pending') {
                $pdo->rollBack();
                return $this->json(['success' => true, 'message' => 'Deposit already processed']);
            }

            $markStmt = $pdo->prepare("
                UPDATE `pending_deposits`
                SET `status` = 'completed', `sepay_transaction_id` = ?, `completed_at` = NOW()
                WHERE `id` = ? AND `status` = 'pending'
            ");
            $markStmt->execute([$sepayId, $depositId]);
            if ($markStmt->rowCount() < 1) {
                $pdo->rollBack();
                return $this->json(['success' => true, 'message' => 'Deposit already processed']);
            }

            $creditStmt = $pdo->prepare("UPDATE `users` SET `money` = `money` + ?, `tong_nap` = `tong_nap` + ? WHERE `id` = ? LIMIT 1");
            $creditStmt->execute([$totalCredit, $transferAmount, $userId]);
            if ($creditStmt->rowCount() < 1) {
                throw new RuntimeException('User not found for deposit credit');
            }

            $historyStmt = $pdo->prepare("
                INSERT INTO `history_nap_bank`
                (`trans_id`, `username`, `type`, `ctk`, `stk`, `thucnhan`, `status`, `time`)
                VALUES (?, ?, ?, ?, ?, ?, 'hoantat', ?)
            ");
            $historyStmt->execute([
                $referenceCode,
                $username,
                $gateway,
                $content,
                $accountNumber,
                $totalCredit,
                (string) time(),
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::danger('Billing', 'webhook_process_failed', 'SePay webhook xử lý thất bại', [
                'sepay_id' => $sepayId,
                'deposit_code' => $depositCode,
                'error' => $e->getMessage(),
            ]);
            http_response_code(500);
            return $this->json(['success' => false, 'message' => 'Webhook processing failed']);
        }

        // 12. Log
        Logger::info('Billing', 'deposit_completed', "Nạp tiền thành công cho {$username}", [
            'sepay_id' => $sepayId,
            'deposit_code' => $depositCode,
            'transfer_amount' => $transferAmount,
            'bonus_percent' => $bonusPercent,
            'bonus_amount' => $bonusAmount,
            'total_credit' => $totalCredit,
            'gateway' => $gateway,
            'username' => $username,
        ]);

        // 13. Return success
        http_response_code(200);
        return $this->json(['success' => true, 'message' => 'Deposit credited']);
    }
}

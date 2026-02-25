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
        global $chungapi, $connection;

        // 1. Validate API Key
        $expectedKey = trim((string) ($chungapi['sepay_api_key'] ?? ''));
        $authHeader = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));

        if ($expectedKey !== '' && $authHeader !== '') {
            // SePay sends "Apikey YOUR_KEY"
            $providedKey = '';
            if (stripos($authHeader, 'Apikey ') === 0) {
                $providedKey = trim(substr($authHeader, 7));
            }

            if ($providedKey !== $expectedKey) {
                http_response_code(401);
                return $this->json(['success' => false, 'message' => 'Invalid API key']);
            }
        }

        // 2. Parse JSON body
        $rawBody = file_get_contents('php://input');
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
            $depositCode = strtolower($matches[1]) !== strtolower($matches[1])
                ? $matches[1]
                : $matches[1];
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

        // 9. Credit user balance
        $username = $deposit['username'];
        $userId = (int) $deposit['user_id'];

        $safeUsername = $connection->real_escape_string($username);
        $connection->query("UPDATE `users` SET `money` = `money` + {$totalCredit}, `tong_nap` = `tong_nap` + {$transferAmount} WHERE `id` = {$userId}");

        // 10. Mark deposit as completed
        $this->depositModel->markComplete((int) $deposit['id'], $sepayId);

        // 11. Record in history_nap_bank
        $now = time();
        $safeContent = $connection->real_escape_string($content);
        $safeRef = $connection->real_escape_string($referenceCode);
        $safeGateway = $connection->real_escape_string($gateway);

        $connection->query("INSERT INTO `history_nap_bank` SET
            `trans_id` = '{$safeRef}',
            `username` = '{$safeUsername}',
            `type` = '{$safeGateway}',
            `ctk` = '{$safeContent}',
            `stk` = '{$connection->real_escape_string($accountNumber)}',
            `thucnhan` = '{$totalCredit}',
            `status` = 'hoantat',
            `time` = '{$now}'
        ");

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

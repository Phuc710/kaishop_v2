<?php

/**
 * SePay Webhook Controller
 * Receives POST webhooks from SePay when bank transactions occur.
 * Matches deposit codes, credits user balances, and prevents duplicates.
 */
class SepayWebhookController extends Controller
{
    private $depositModel;
    private ?BalanceChangeService $balanceChangeService = null;
    private array $schemaCache = [];

    public function __construct()
    {
        $this->depositModel = new PendingDeposit();
        $this->balanceChangeService = class_exists('BalanceChangeService') ? new BalanceChangeService() : null;
        $this->ensureHistorySourceChannelSchema();
    }

    /**
     * POST /api/sepay/webhook — Receive SePay webhook.
     */
    public function handle()
    {
        global $chungapi, $connection;

        // 1. Validate API Key (fail-closed: reject if key not configured)
        $expectedKey = trim((string) ($chungapi['sepay_api_key'] ?? ''));
        $authHeader = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));

        if ($expectedKey === '') {
            // API key not configured — reject all requests to prevent abuse
            Logger::danger('Billing', 'webhook_no_api_key', 'SePay webhook called but sepay_api_key is not configured', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);
            http_response_code(403);
            return $this->json(['success' => false, 'message' => 'Webhook not configured']);
        }

        // SePay sends "Apikey YOUR_KEY"
        $providedKey = '';
        if (stripos($authHeader, 'Apikey ') === 0) {
            $providedKey = trim(substr($authHeader, 7));
        }

        if (!hash_equals($expectedKey, $providedKey)) {
            Logger::danger('Billing', 'webhook_invalid_key', 'SePay webhook: invalid API key', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);
            http_response_code(401);
            return $this->json(['success' => false, 'message' => 'Invalid API key']);
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

        // 5. Extract deposit code from content (pattern: "kai" + generated suffix)
        $depositCode = null;
        // Generated format is "kai" + 8 random chars, keep wider upper bound for future-proofing.
        if (preg_match('/\b(kai[A-Z0-9]{8,20})\b/i', $content, $matches)) {
            $depositCode = trim((string) ($matches[1] ?? ''));
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

        // 6. Find pending deposit (and expire old ones first)
        $this->depositModel->markExpired();
        $deposit = $this->depositModel->findByCode($depositCode);

        if (!$deposit) {
            Logger::info('Billing', 'webhook_code_not_found', 'SePay webhook: deposit code not found in DB', [
                'sepay_id' => $sepayId,
                'deposit_code' => $depositCode,
                'amount' => $transferAmount,
            ]);
            return $this->json(['success' => true, 'message' => 'Deposit code not found']);
        }
        $sourceChannel = SourceChannelHelper::normalize($deposit['source_channel'] ?? SourceChannelHelper::WEB);

        if ($deposit['status'] === 'expired') {
            Logger::warning('Billing', 'webhook_expired_deposit', 'SePay webhook: Payment received for EXPIRED deposit', [
                'sepay_id' => $sepayId,
                'deposit_code' => $depositCode,
                'amount' => $transferAmount,
                'user_id' => $deposit['user_id'],
            ]);
            return $this->json(['success' => true, 'message' => 'Deposit expired, please contact support']);
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

        // 9. Credit user balance (PDO prepared statements)
        $username = $deposit['username'];
        $userId = (int) $deposit['user_id'];

        $db = Database::getInstance()->getConnection();
        $beforeBalance = 0;
        $beforeStmt = $db->prepare("SELECT `money` FROM `users` WHERE `id` = ? LIMIT 1");
        $beforeStmt->execute([$userId]);
        $beforeValue = $beforeStmt->fetchColumn();
        if ($beforeValue !== false && $beforeValue !== null) {
            $beforeBalance = (int) $beforeValue;
        }

        $stmt = $db->prepare("UPDATE `users` SET `money` = `money` + ?, `tong_nap` = `tong_nap` + ? WHERE `id` = ?");
        $stmt->execute([$totalCredit, $transferAmount, $userId]);
        $afterBalance = $beforeBalance + $totalCredit;

        // Enqueue Telegram notification if user is linked
        try {
            if (class_exists('UserTelegramLink') && class_exists('TelegramOutbox')) {
                $linkModel = new UserTelegramLink();
                $link = $linkModel->findByUserId($userId);
                if ($link) {
                    $outbox = new TelegramOutbox();
                    $notifMsg = "🎉🎊 <b>NẠP TIỀN THÀNH CÔNG</b> 🎊🎉\n\n";
                    $notifMsg .= "💰 Số tiền: <b>" . number_format($transferAmount) . "đ</b>\n";
                    if ($bonusAmount > 0) {
                        $notifMsg .= "🎁 Khuyến mãi: <b>" . number_format($bonusAmount) . "đ</b>\n";
                    }
                    $notifMsg .= "✅ Thực nhận: <b>" . number_format($totalCredit) . "đ</b>\n";
                    $notifMsg .= "🎉 Số dư hiện tại: <b>" . number_format($afterBalance) . "đ</b>\n\n";

                    $outbox->enqueue((int) $link['telegram_id'], $notifMsg);
                }
            }
        } catch (Throwable $teleErr) {
            // Non-blocking
        }

        // 10. Mark deposit as completed
        $this->depositModel->markComplete((int) $deposit['id'], $sepayId);

        // 11. Record in history_nap_bank (PDO prepared statements)
        $now = class_exists('TimeService') ? TimeService::instance()->nowTs() : time();

        $historyColumns = ['trans_id', 'username', 'type', 'ctk', 'stk', 'thucnhan', 'status', 'time'];
        $historyValues = [$referenceCode, $username, $gateway, $content, $accountNumber, $totalCredit, 'hoantat', $now];
        if ($this->hasColumn('history_nap_bank', 'source_channel')) {
            $historyColumns[] = 'source_channel';
            $historyValues[] = $sourceChannel;
        }
        $historyMarks = implode(', ', array_fill(0, count($historyColumns), '?'));
        $stmt = $db->prepare("
            INSERT INTO `history_nap_bank` (`" . implode('`, `', $historyColumns) . "`)
            VALUES ({$historyMarks})
        ");
        $stmt->execute($historyValues);

        $reason = 'Nap tien SePay';
        if ($content !== '') {
            $reason .= ': ' . $content;
        }
        if ($this->balanceChangeService) {
            $this->balanceChangeService->record(
                $userId,
                (string) $username,
                $beforeBalance,
                $totalCredit,
                $afterBalance,
                $reason,
                $sourceChannel
            );
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
            'source_channel' => $sourceChannel,
        ]);

        // 13. Log to Terminal
        if (class_exists('TelegramLog')) {
            $logMsg = "💰 NẠP THÀNH CÔNG: " . ($username) . " + " . number_format($totalCredit) . "đ (Mã: " . $depositCode . ")";
            (new TelegramLog())->log($logMsg, 'INFO', 'INCOMING', 'DEPOSIT', [
                'sepay_id' => $sepayId,
                'amount' => $transferAmount,
                'total_credit' => $totalCredit
            ]);
        }

        // 14. Return success
        http_response_code(200);
        return $this->json(['success' => true, 'message' => 'Deposit credited']);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->schemaCache)) {
            return $this->schemaCache[$cacheKey];
        }

        $stmt = Database::getInstance()->getConnection()->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = :table_name
              AND column_name = :column_name
        ");
        $stmt->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);
        $exists = (int) $stmt->fetchColumn() > 0;
        $this->schemaCache[$cacheKey] = $exists;
        return $exists;
    }

    private function ensureHistorySourceChannelSchema(): void
    {
        try {
            if (!$this->hasColumn('history_nap_bank', 'source_channel')) {
                Database::getInstance()->getConnection()
                    ->exec("ALTER TABLE `history_nap_bank` ADD COLUMN `source_channel` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`");
            }
        } catch (Throwable $e) {
            // ignore if ALTER is restricted
        }

        try {
            Database::getInstance()->getConnection()
                ->exec("ALTER TABLE `history_nap_bank` ADD KEY `idx_hnb_source_created` (`source_channel`, `created_at`)");
        } catch (Throwable $e) {
            // ignore if key exists or ALTER is restricted
        }

        unset($this->schemaCache['history_nap_bank.source_channel']);
    }

}

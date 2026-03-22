<?php

/**
 * BinancePayService
 * Handles Binance Pay transaction polling, matching and crediting.
 */
class BinancePayService
{
    private const API_BASE = 'https://api.binance.com';
    private const PAY_TX_ENDPOINT = '/sapi/v1/pay/transactions';
    private const REQUEST_TIMEOUT = 8;
    private const TX_TIME_SKEW_SECONDS = 120;
    private const AMOUNT_SCALE = 8;
    private const DEFAULT_PENDING_TTL_SECONDS = 300;

    public const MIN_USDT = 1.0;
    public const MAX_USDT = 10000.0;

    private string $apiKey;
    private string $apiSecret;
    private string $binanceUid;
    private int $exchangeRateVnd;
    private bool $payEnabled;

    private PDO $db;
    private array $schemaCache = [];
    protected ?TimeService $timeService = null;

    public function __construct(array $config, PDO $db)
    {
        $this->apiKey = trim((string) ($config['binance_api_key'] ?? ''));
        $this->apiSecret = trim((string) ($config['binance_api_secret'] ?? ''));
        $this->binanceUid = trim((string) ($config['binance_uid'] ?? ''));
        $this->exchangeRateVnd = max(1, (int) ($config['binance_rate_vnd'] ?? 25000));
        $this->payEnabled = (int) ($config['binance_pay_enabled'] ?? 0) === 1;
        $this->db = $db;
        if (class_exists('TimeService')) {
            $this->timeService = TimeService::instance();
        }
    }

    public function isEnabled(): bool
    {
        return $this->payEnabled && $this->apiKey !== '' && $this->apiSecret !== '' && $this->binanceUid !== '';
    }

    public function getUid(): string
    {
        return $this->binanceUid;
    }

    public function getExchangeRate(): int
    {
        return $this->exchangeRateVnd;
    }

    public function vndToUsdt(int $vnd): float
    {
        if ($this->exchangeRateVnd <= 0) {
            return 0.0;
        }
        return round($vnd / $this->exchangeRateVnd, 2);
    }

    public function usdtToVnd(float $usdt): int
    {
        return (int) floor($usdt * $this->exchangeRateVnd);
    }

    public function getTransferNote(string $depositCode): string
    {
        $safe = strtoupper((string) preg_replace('/[^a-zA-Z0-9]/', '', $depositCode));
        $safe = trim($safe);
        if ($safe === '') {
            return 'KAI';
        }
        return substr($safe, 0, 32);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getRecentTransactions(int $startTime, int $endTime, int $limit = 100): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $params = [
            'startTime' => max(0, $startTime),
            'endTime' => max($startTime, $endTime),
            'limit' => min(100, max(1, $limit)),
            'timestamp' => $this->nowMs(),
            'recvWindow' => 10000,
        ];

        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->apiSecret);
        $url = self::API_BASE . self::PAY_TX_ENDPOINT . '?' . $queryString . '&signature=' . $signature;

        $ch = curl_init($url);
        if ($ch === false) {
            return [];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'X-MBX-APIKEY: ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);



        if ($response === false || $httpCode !== 200) {
            Logger::warning('BinancePay', 'api_error', 'Binance Pay API error', [
                'http_code' => $httpCode,
                'curl_err' => $curlErr,
                'response' => is_string($response) ? substr($response, 0, 500) : null,
            ]);
            return [];
        }

        $data = json_decode((string) $response, true);
        // Binance Pay API returns {"code":"000000","success":true,...} (NOT "status":"0")
        $isApiSuccess = is_array($data) && (
            (string) ($data['status'] ?? '') === '0'          // Some Binance endpoints
            || (string) ($data['code'] ?? '') === '000000'    // Pay API: code=000000
            || ($data['success'] ?? false) === true           // Pay API: success=true
        );
        if (!$isApiSuccess) {
            Logger::warning('BinancePay', 'api_bad_status', 'Binance Pay API non-zero status', [
                'response' => substr((string) $response, 0, 300),
            ]);
            return [];
        }

        $rows = (array) ($data['data'] ?? []);
        return array_values(array_filter($rows, static fn($row) => is_array($row)));
    }

    /**
     * @param array<string,mixed> $pendingDeposit
     * @param array<int,array<string,mixed>>|null $preloadedTransactions
     * @return array<string,mixed>|null
     */
    public function findMatchingTransaction(array $pendingDeposit, ?array $preloadedTransactions = null): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $expectedAmount = $this->normalizeAmount($pendingDeposit['usdt_amount'] ?? 0);
        if ($expectedAmount === null || (float) $expectedAmount <= 0) {
            return null;
        }

        $expectedPayerUid = $this->normalizeUid($pendingDeposit['payer_uid'] ?? '');
        // Note: expectedPayerUid may be empty-checked later only when API provides a payer UID

        $expectedReceiverUid = $this->normalizeUid($this->binanceUid);
        if ($expectedReceiverUid === '') {
            return null;
        }

        $expectedCurrency = strtoupper(trim((string) ($pendingDeposit['currency'] ?? 'USDT')));
        if ($expectedCurrency === '') {
            $expectedCurrency = 'USDT';
        }

        $createdTs = $this->toTimestamp($pendingDeposit['created_at'] ?? '') ?: ($this->nowTs() - self::DEFAULT_PENDING_TTL_SECONDS);
        $ttlSeconds = $this->resolvePendingTtlSeconds();
        $earliestTs = $createdTs - self::TX_TIME_SKEW_SECONDS;
        $latestTs = $createdTs + $ttlSeconds + self::TX_TIME_SKEW_SECONDS;

        $queryStartMs = max(0, $earliestTs * 1000);
        $queryEndMs = max($queryStartMs, ($this->nowTs() + self::TX_TIME_SKEW_SECONDS) * 1000);

        $transactions = $preloadedTransactions;
        if (!is_array($transactions)) {
            $transactions = $this->getRecentTransactions($queryStartMs, $queryEndMs, 100);
        }

        foreach ($transactions as $tx) {
            if (!$this->isCandidatePaymentTransaction($tx)) {
                continue;
            }

            $txId = $this->extractTransactionId($tx);
            if ($txId === '' || $this->isTransactionProcessed($txId)) {
                continue;
            }

            $txCurrency = strtoupper(trim((string) ($tx['currency'] ?? '')));
            if ($txCurrency !== $expectedCurrency) {
                continue;
            }

            // Payer UID: only enforce if the API returned a payer UID.
            // Binance C2C transactions do NOT include payerInfo.binanceId on the receiver side.
            $txPayerUid = $this->normalizeUid($this->extractPayerUid($tx));
            if ($txPayerUid !== '' && $expectedPayerUid !== '') {
                if (!hash_equals($expectedPayerUid, $txPayerUid)) {
                    continue;
                }
            }

            $txRecvUid = $this->normalizeUid($this->extractReceiverUid($tx));
            if ($txRecvUid === '' || !hash_equals($expectedReceiverUid, $txRecvUid)) {
                continue;
            }

            $txAmount = $this->normalizeAmount($tx['amount'] ?? 0);
            if ($txAmount === null || $txAmount !== $expectedAmount) {
                continue;
            }

            $txTs = $this->extractTxTimestamp($tx);
            if ($txTs === null || $txTs < $earliestTs || $txTs > $latestTs) {
                continue;
            }

            return $tx;
        }

        return null;
    }

    /**
     * @param array<string,mixed> $tx
     * @param array<string,mixed> $pendingDeposit
     * @param array<string,mixed> $user
     * @return array{success:bool,message:string}
     */
    public function processTransaction(array $tx, array $pendingDeposit, array $user): array
    {
        $txId = $this->extractTransactionId($tx);
        $usdtAmountRaw = $this->normalizeAmount($tx['amount'] ?? 0);
        $payerUid = $this->normalizeUid($this->extractPayerUid($tx));
        $receiverUid = $this->normalizeUid($this->extractReceiverUid($tx));
        $currency = strtoupper(trim((string) ($tx['currency'] ?? '')));
        $txTimestamp = $this->extractTxTimestamp($tx);
        $depositId = (int) ($pendingDeposit['id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);
        $username = (string) ($user['username'] ?? '');
        $sourceChannel = (int) ($pendingDeposit['source_channel'] ?? 0);
        $expectedPayerUid = $this->normalizeUid($pendingDeposit['payer_uid'] ?? '');
        $expectedAmountRaw = $this->normalizeAmount($pendingDeposit['usdt_amount'] ?? 0);

        if (
            $txId === ''
            || $usdtAmountRaw === null
            || $expectedAmountRaw === null
            || $depositId <= 0
            || $userId <= 0
            || $username === ''
        ) {
            return ['success' => false, 'message' => 'Invalid transaction data'];
        }
        if ($currency !== 'USDT') {
            return ['success' => false, 'message' => 'Invalid currency. USDT required.'];
        }
        // Payer UID check: only enforce when API provides a payer UID.
        // Binance C2C transactions do NOT include payerInfo.binanceId on the receiver side.
        if ($payerUid !== '' && $expectedPayerUid !== '') {
            if (!hash_equals($expectedPayerUid, $payerUid)) {
                return ['success' => false, 'message' => 'Payer UID does not match.'];
            }
        }
        if ($receiverUid === '' || !hash_equals($this->normalizeUid($this->binanceUid), $receiverUid)) {
            return ['success' => false, 'message' => 'Receiver UID does not match.'];
        }
        if ($usdtAmountRaw !== $expectedAmountRaw) {
            return ['success' => false, 'message' => 'Amount does not match.'];
        }
        if ($txTimestamp === null) {
            return ['success' => false, 'message' => 'Missing transaction time.'];
        }

        try {
            $this->db->beginTransaction();

            if ($this->isTransactionProcessed($txId, true)) {
                $this->db->rollBack();
                return ['success' => true, 'message' => 'Already processed'];
            }

            $lockStmt = $this->db->prepare("
                SELECT `id`, `status`, `created_at`, `bonus_percent`, `source_channel`, `user_id`, `amount`, `order_id`, `usdt_amount`, `payer_uid`
                FROM `pending_deposits`
                WHERE `id` = ?
                LIMIT 1
                FOR UPDATE
            ");
            $lockStmt->execute([$depositId]);
            $locked = $lockStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$locked || (string) ($locked['status'] ?? '') !== 'pending') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Pending deposit is no longer active'];
            }
            if ((int) ($locked['user_id'] ?? 0) !== $userId) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Pending deposit owner mismatch'];
            }

            $strictMatchError = '';
            if (!$this->matchesPendingDepositStrict($tx, $locked, $strictMatchError)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => $strictMatchError !== '' ? $strictMatchError : 'Transaction does not match pending deposit'];
            }

            $sourceChannel = (int) ($locked['source_channel'] ?? $sourceChannel);
            $orderId = (int) ($locked['order_id'] ?? 0);
            $bonusPercent = ($orderId > 0 || $sourceChannel === SourceChannelHelper::BOTTELE) ? 0 : (int) ($locked['bonus_percent'] ?? 0);
            $usdtAmount = (float) $expectedAmountRaw;
            $baseVnd = $this->usdtToVnd($usdtAmount);
            $bonusVnd = (int) floor($baseVnd * max(0, $bonusPercent) / 100);
            $totalCredit = $orderId > 0 ? max(0, (int) ($locked['amount'] ?? 0)) : ($baseVnd + $bonusVnd);

            $beforeBalance = 0;
            $afterBalance = 0;
            if ($orderId <= 0) {
                $this->db->prepare("
                    UPDATE `users`
                    SET `money` = `money` + ?, `tong_nap` = `tong_nap` + ?
                    WHERE `id` = ?
                ")->execute([$totalCredit, $baseVnd, $userId]);

                $balStmt = $this->db->prepare("SELECT `money` FROM `users` WHERE `id` = ? LIMIT 1");
                $balStmt->execute([$userId]);
                $afterBalance = (int) ($balStmt->fetchColumn() ?? 0);
                $beforeBalance = $afterBalance - $totalCredit;
            }

            $nowSql = $this->nowDbSql();
            $nowTs = (string) $this->nowTs();

            $txColumns = ['tx_id', 'username', 'usdt_amount', 'vnd_credit', 'bonus_vnd', 'bonus_percent', 'payer_uid', 'source_channel', 'deposit_id', 'created_at'];
            $txValues = [$txId, $username, $usdtAmount, $totalCredit, $bonusVnd, $bonusPercent, ($payerUid !== '' ? $payerUid : null), $sourceChannel, $depositId, $nowSql];

            if ($this->hasColumn('binance_transactions', 'receiver_uid')) {
                $txColumns[] = 'receiver_uid';
                $txValues[] = ($receiverUid !== '' ? $receiverUid : null);
            }
            if ($this->hasColumn('binance_transactions', 'currency')) {
                $txColumns[] = 'currency';
                $txValues[] = $currency !== '' ? $currency : 'USDT';
            }
            if ($this->hasColumn('binance_transactions', 'transaction_time')) {
                $txColumns[] = 'transaction_time';
                $txValues[] = $txTimestamp;
            }

            $this->db->prepare(
                "INSERT INTO `binance_transactions` (`" . implode('`, `', $txColumns) . "`) VALUES (" .
                implode(', ', array_fill(0, count($txColumns), '?')) . ")"
            )->execute($txValues);

            if ($orderId > 0) {
                $this->db->prepare("
                    UPDATE `pending_deposits`
                    SET `status` = 'completed', `completed_at` = ?
                    WHERE `id` = ? AND `status` = 'pending'
                ")->execute([$nowSql, $depositId]);

                $purchaseService = new PurchaseService();
                $finalize = $purchaseService->finalizeTelegramOrderPayment(
                    array_merge($pendingDeposit, $locked, ['order_id' => $orderId, 'method' => DepositService::METHOD_BINANCE]),
                    [
                        'transaction_id' => $txId,
                        'paid_usdt' => $usdtAmount,
                    ]
                );

                if (empty($finalize['success'])) {
                    throw new RuntimeException((string) ($finalize['message'] ?? 'Khong the xac nhan thanh toan don hang'));
                }

                $this->db->commit();

                $this->notifyTelegramOrderPaid(
                    $userId,
                    (array) ($finalize['order'] ?? []),
                    $txId,
                    $usdtAmount,
                    $totalCredit
                );

                Logger::info('BinancePay', 'order_payment_completed', "Binance order paid: {$username}", [
                    'tx_id' => $txId,
                    'order_id' => $orderId,
                    'usdt' => $usdtAmount,
                    'amount_vnd' => $totalCredit,
                    'payer_uid' => $payerUid,
                    'receiver_uid' => $receiverUid,
                    'deposit_id' => $depositId,
                ]);

                return [
                    'success' => true,
                    'message' => 'Order paid',
                    'tx_id' => $txId,
                ];
            }

            $historyColumns = ['trans_id', 'username', 'type', 'ctk', 'thucnhan', 'status', 'time', 'created_at'];
            $historyValues = [
                $txId,
                $username,
                'Binance',
                'Binance Pay (USDT) | Payer UID: ' . ($payerUid !== '' ? $payerUid : 'unknown') . ' | Receiver UID: ' . ($receiverUid !== '' ? $receiverUid : 'unknown'),
                $totalCredit,
                'completed',
                $nowTs,
                $nowSql,
            ];

            if ($this->hasColumn('history_nap_bank', 'source_channel')) {
                $historyColumns[] = 'source_channel';
                $historyValues[] = $sourceChannel;
            }
            if ($this->hasColumn('history_nap_bank', 'bank_name')) {
                $historyColumns[] = 'bank_name';
                $historyValues[] = 'Binance Pay';
            }
            if ($this->hasColumn('history_nap_bank', 'bank_owner')) {
                $historyColumns[] = 'bank_owner';
                $historyValues[] = null;
            }

            $this->db->prepare(
                "INSERT INTO `history_nap_bank` (`" . implode('`, `', $historyColumns) . "`) VALUES (" .
                implode(', ', array_fill(0, count($historyColumns), '?')) . ")"
            )->execute($historyValues);

            if (class_exists('BalanceChangeService')) {
                (new BalanceChangeService($this->db))->record(
                    $userId,
                    $username,
                    $beforeBalance,
                    $totalCredit,
                    $afterBalance,
                    'Binance Pay deposit: ' . rtrim(rtrim(number_format($usdtAmount, 8, '.', ''), '0'), '.') . ' USDT',
                    $sourceChannel
                );
            }

            $this->db->prepare("
                UPDATE `pending_deposits`
                SET `status` = 'completed', `completed_at` = ?
                WHERE `id` = ? AND `status` = 'pending'
            ")->execute([$nowSql, $depositId]);

            $this->db->commit();

            $this->notifyTelegramAsync(
                $userId,
                $username,
                $usdtAmount,
                $totalCredit,
                $txId
            );

            Logger::info('BinancePay', 'deposit_credited', "Binance deposit credited: {$username}", [
                'tx_id' => $txId,
                'usdt' => $usdtAmount,
                'vnd' => $totalCredit,
                'bonus_vnd' => $bonusVnd,
                'payer_uid' => $payerUid,
                'receiver_uid' => $receiverUid,
                'currency' => $currency,
                'transaction_time' => $txTimestamp,
                'deposit_id' => $depositId,
            ]);

            return [
                'success' => true,
                'message' => 'Deposit credited',
                'tx_id' => $txId,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Logger::warning('BinancePay', 'process_error', 'Binance processing failed: ' . $e->getMessage(), [
                'tx_id' => $txId,
                'deposit_id' => $depositId,
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param array<string,mixed> $tx
     */
    private function isCandidatePaymentTransaction(array $tx): bool
    {
        $currency = strtoupper(trim((string) ($tx['currency'] ?? '')));
        if ($currency !== 'USDT') {
            return false;
        }

        $orderType = strtoupper(trim((string) ($tx['orderType'] ?? '')));
        if ($orderType !== '' && !in_array($orderType, ['C2C', 'C2C_PAYMENT', 'PAY', 'PAID'], true)) {
            return false;
        }

        $status = strtoupper(trim((string) ($tx['status'] ?? $tx['transactionStatus'] ?? '')));
        if ($status !== '' && !in_array($status, ['SUCCESS', 'COMPLETED', 'PAID'], true)) {
            return false;
        }

        $receiverUid = $this->normalizeUid($this->extractReceiverUid($tx));
        $configuredUid = $this->normalizeUid($this->binanceUid);
        if ($receiverUid === '' || $configuredUid === '') {
            return false;
        }
        return hash_equals($configuredUid, $receiverUid);
    }

    /**
     * @param array<string,mixed> $tx
     */
    private function extractTxTimestamp(array $tx): ?int
    {
        foreach (['transactionTime', 'orderCreateTime', 'createTime', 'updateTime'] as $field) {
            if (!isset($tx[$field])) {
                continue;
            }
            $raw = (string) $tx[$field];
            if ($raw === '') {
                continue;
            }

            if (ctype_digit($raw)) {
                $num = (int) $raw;
                if ($num > 9999999999) {
                    $num = (int) floor($num / 1000);
                }
                if ($num > 0) {
                    return $num;
                }
                continue;
            }

            $parsed = $this->toTimestamp($raw);
            if ($parsed !== null) {
                return $parsed;
            }
        }
        return null;
    }

    /**
     * @param array<string,mixed> $tx
     */
    private function extractPayerUid(array $tx): string
    {
        $candidates = [
            $tx['payerInfo']['binanceId'] ?? null,
            $tx['payerInfo']['uid'] ?? null,
            $tx['payer']['binanceId'] ?? null,
            $tx['payerUid'] ?? null,
            $tx['fromUid'] ?? null,
            $tx['from']['binanceId'] ?? null,
            $tx['from']['uid'] ?? null,
        ];
        foreach ($candidates as $raw) {
            $uid = $this->normalizeUid($raw);
            if ($uid !== '') {
                return $uid;
            }
        }
        return '';
    }

    /**
     * @param array<string,mixed> $tx
     */
    private function extractReceiverUid(array $tx): string
    {
        $candidates = [
            $tx['receiverInfo']['binanceId'] ?? null,
            $tx['receiverInfo']['uid'] ?? null,
            $tx['payeeInfo']['binanceId'] ?? null,
            $tx['payeeInfo']['uid'] ?? null,
            $tx['receiver']['binanceId'] ?? null,
            $tx['receiverUid'] ?? null,
            $tx['toUid'] ?? null,
            $tx['to']['binanceId'] ?? null,
            $tx['to']['uid'] ?? null,
        ];
        foreach ($candidates as $raw) {
            $uid = $this->normalizeUid($raw);
            if ($uid !== '') {
                return $uid;
            }
        }
        return '';
    }

    /**
     * Binance responses may expose either transactionId or orderId depending on flow.
     * Normalize to a stable ID for dedup and idempotency.
     *
     * @param array<string,mixed> $tx
     */
    private function extractTransactionId(array $tx): string
    {
        $candidates = [
            $tx['transactionId'] ?? null,
            $tx['orderId'] ?? null,
            $tx['transactionNo'] ?? null,
            $tx['bizId'] ?? null,
            $tx['merchantTradeNo'] ?? null,
        ];

        foreach ($candidates as $raw) {
            $id = trim((string) ($raw ?? ''));
            if ($id === '') {
                continue;
            }
            if (strlen($id) > 100) {
                return 'h_' . substr(hash('sha256', $id), 0, 98);
            }
            return $id;
        }

        return '';
    }

    private function isTransactionProcessed(string $txId, bool $forUpdate = false): bool
    {
        $sql = "SELECT COUNT(*) FROM `binance_transactions` WHERE `tx_id` = ?" . ($forUpdate ? ' FOR UPDATE' : '');
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$txId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Retrieve transaction details for a completed deposit.
     *
     * @return array<string,mixed>|null
     */
    public function getTransactionByDepositId(int $depositId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `binance_transactions` WHERE `deposit_id` = ? LIMIT 1");
        $stmt->execute([$depositId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @param array<string,mixed> $order
     */
    private function notifyTelegramOrderPaid(int $userId, array $order, string $txId, float $usdt, int $amountVnd): void
    {
        try {
            if (!class_exists('UserTelegramLink')) {
                return;
            }

            $link = (new UserTelegramLink())->findByUserId($userId);
            if (!$link || (int) ($link['telegram_id'] ?? 0) <= 0) {
                return;
            }

            $telegramId = (int) $link['telegram_id'];
            $isBotTeleOrder = class_exists('SourceChannelHelper')
                && SourceChannelHelper::fromOrderRow($order) === SourceChannelHelper::BOTTELE;

            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        ['text' => html_entity_decode('&#128230; Orders', ENT_QUOTES, 'UTF-8'), 'callback_data' => 'orders'],
                        ['text' => html_entity_decode('&#127968; Menu', ENT_QUOTES, 'UTF-8'), 'callback_data' => 'menu'],
                    ]
                ]
            ];
            $message = $this->buildTelegramOrderSuccessSummaryAuto($order, $usdt);
            $messageEdited = false;

            if ($isBotTeleOrder && class_exists('PurchaseService') && class_exists('TelegramService')) {
                $messageId = (new PurchaseService())->resolveTelegramPaymentMessageId($order);
                if ($messageId > 0) {
                    $telegram = new TelegramService(null, null, 5);
                    $messageEdited = $telegram->editOrSend((string) $telegramId, $messageId, $message, $replyMarkup);
                }
            }

            if (!$isBotTeleOrder || !$messageEdited) {
                $messageSent = $this->sendTelegramDirectTo((string) $telegramId, $message, $replyMarkup);
                if (!$messageSent && class_exists('TelegramOutbox')) {
                    (new TelegramOutbox())->enqueue($telegramId, $message, 'HTML');
                }

                Logger::info('BinancePay', 'telegram_order_paid_notice', 'Sent Telegram order payment notice', [
                    'user_id' => $userId,
                    'telegram_id' => $telegramId,
                    'order_id' => (int) ($order['id'] ?? 0),
                    'edited_existing_message' => $messageEdited,
                    'sent_direct' => $messageSent,
                    'queued_outbox' => !$messageSent,
                ]);
            } else {
                Logger::info('BinancePay', 'telegram_order_paid_notice', 'Edited Telegram order payment message', [
                    'user_id' => $userId,
                    'telegram_id' => $telegramId,
                    'order_id' => (int) ($order['id'] ?? 0),
                    'edited_existing_message' => true,
                ]);
            }

            // Immediately deliver product as .txt file if completed
            $status = (string) ($order['status'] ?? '');
            $deliveryContent = trim((string) ($order['delivery_content'] ?? $order['stock_content_plain'] ?? ''));
            if ($status === 'completed' && $deliveryContent !== '' && class_exists('TelegramService')) {
                $orderId = (int) ($order['id'] ?? 0);
                $filename = "order_{$orderId}.txt";
                $telegram = new TelegramService(null, null, 5);
                $documentSent = $telegram->sendDocumentFromContent((string) $telegramId, $deliveryContent, $filename);

                Logger::info('BinancePay', 'telegram_order_delivery_sent', 'Auto-delivered Telegram order content', [
                    'user_id' => $userId,
                    'telegram_id' => $telegramId,
                    'order_id' => $orderId,
                    'filename' => $filename,
                    'sent' => $documentSent,
                ]);
            }
        } catch (Throwable $e) {
            // Non-blocking
        }
    }

    /**
     * @param array<string,mixed> $order
     */
    private function buildTelegramOrderPaidMessage(array $order, float $usdt): string
    {
        $orderCode = htmlspecialchars((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $productName = htmlspecialchars((string) ($order['product_name'] ?? 'Product'), ENT_QUOTES, 'UTF-8');
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $status = (string) ($order['status'] ?? '');
        $deliveryContent = trim((string) ($order['delivery_content'] ?? $order['stock_content_plain'] ?? ''));
        $usdtText = rtrim(rtrim(number_format($usdt, 8, '.', ''), '0'), '.');

        $msg = "🎉 <b>BINANCE PAYMENT SUCCESSFUL</b>\n\n";
        $msg .= "🧾 Order ID: <code>{$orderCode}</code>\n";
        $msg .= "📦 Product: <b>{$productName}</b>\n";
        $msg .= "🔢 Quantity: <b>x{$quantity}</b>\n";
        $msg .= "💵 Received: <b>{$usdtText} USDT</b>\n";
        $msg .= "━━━━━━━━━━━━━━";

        if ($status === 'completed' && $deliveryContent !== '') {
            $msg .= "\n\n🔑 <b>DELIVERY</b>\n<code>" . htmlspecialchars($deliveryContent, ENT_QUOTES, 'UTF-8') . "</code>";
            $msg .= "\n\n📄 <i>Your product is also attached as a .txt file below.</i>";
        } elseif ($status === 'processing') {
            $msg .= "\n\n🛠️ Your order is being processed. It will be delivered soon.";
        } else {
            $msg .= "\n\n📩 Order recorded successfully.";
        }

        return $msg;
    }

    /**
     * @param array<string,mixed> $order
     */
    private function buildTelegramOrderSuccessSummaryAuto(array $order, float $usdt): string
    {
        $orderCode = htmlspecialchars((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $productName = htmlspecialchars((string) ($order['product_name'] ?? 'Product'), ENT_QUOTES, 'UTF-8');
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $status = strtolower(trim((string) ($order['status'] ?? '')));
        $deliveryContent = trim((string) ($order['delivery_content'] ?? $order['stock_content_plain'] ?? ''));

        $msg = "&#127881; <b>PAYMENT SUCCESSFUL</b> &#127881;\n";
        $msg .= "-----------------\n\n";
        $msg .= "&#128230; Order Code: <code>{$orderCode}</code>\n";
        $msg .= "&#128722; {$productName}\n";
        $msg .= "&#128290; Quantity: <b>x{$quantity}</b>\n";
        $msg .= "&#128176; Total: <b>$" . number_format(max(0, $usdt), 2, '.', ',') . "</b>\n\n";

        if ($status === 'completed' && $deliveryContent !== '') {
            $msg .= "&#127873; Your product is attached below.";
        } elseif ($status === 'completed') {
            $msg .= "Your order has been completed.";
        } else {
            $msg .= "Payment was confirmed automatically. Your order is being processed.";
        }

        return $msg;
    }

    private function notifyTelegramAsync(
        int $userId,
        string $username,
        float $usdt,
        int $totalVnd,
        string $txId
    ): void {
        if (!class_exists('TelegramOutbox')) {
            return;
        }

        try {
            $outbox = new TelegramOutbox();
            $usdtText = rtrim(rtrim(number_format($usdt, 8, '.', ''), '0'), '.');

            $adminChatId = $this->resolveAdminChatId();
            if ($adminChatId !== 0) {
                $adminMsg = "✅ <b>BINANCE DEPOSIT</b>\n"
                    . "👤 User: <code>{$username}</code>\n"
                    . "💵 Received: <b>{$usdtText} USDT</b>\n"
                    . "💰 Credited: <b>$" . number_format($totalVnd / TelegramConfig::binanceRate(), 2, '.', ',') . "</b>\n"
                    . "🔖 TX: <code>{$txId}</code>";
                $outbox->enqueue($adminChatId, $adminMsg, 'HTML');
            }

            if (class_exists('UserTelegramLink')) {
                $link = (new UserTelegramLink())->findByUserId($userId);
                if ($link && (int) ($link['telegram_id'] ?? 0) > 0) {
                    $telegramId = (int) $link['telegram_id'];
                    $userMsg = "🎉 <b>DEPOSIT SUCCESSFUL</b>\n\n"
                        . "💵 Received: <b>{$usdtText} USDT</b>\n"
                        . "💰 Credited: <b>$" . number_format($totalVnd / TelegramConfig::binanceRate(), 2, '.', ',') . "</b>";

                    $menuMarkup = ['inline_keyboard' => [[['text' => '🏠 Menu', 'callback_data' => 'menu']]]];
                    if (!$this->sendTelegramDirectTo((string) $telegramId, $userMsg, $menuMarkup)) {
                        $outbox->enqueue($telegramId, $userMsg, 'HTML');
                    }
                }
            }
        } catch (Throwable $e) {
            // Non-blocking
        }
    }

    private function sendTelegramDirectTo(string $chatId, string $message, ?array $replyMarkup = null): bool
    {
        if (!class_exists('TelegramService')) {
            return false;
        }

        try {
            $telegram = new TelegramService(null, null, 3);
            if (!$telegram->isConfigured()) {
                return false;
            }

            $options = ['disable_web_page_preview' => true];
            if ($replyMarkup !== null) {
                $options['reply_markup'] = $replyMarkup;
            }

            return $telegram->sendTo($chatId, $message, $options);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function resolveAdminChatId(): int
    {
        $raw = '';
        if (function_exists('get_setting')) {
            $raw = trim((string) get_setting('telegram_chat_id', ''));
        }
        if ($raw === '' && defined('TELEGRAM_CHAT_ID')) {
            $raw = trim((string) TELEGRAM_CHAT_ID);
        }
        if ($raw === '' || !preg_match('/^-?\d+$/', $raw)) {
            return 0;
        }
        return (int) $raw;
    }

    /**
     * @param array<string,mixed> $tx
     * @param array<string,mixed> $pendingDeposit
     */
    private function matchesPendingDepositStrict(array $tx, array $pendingDeposit, string &$error = ''): bool
    {
        $expectedPayerUid = $this->normalizeUid($pendingDeposit['payer_uid'] ?? '');
        $expectedReceiverUid = $this->normalizeUid($this->binanceUid);
        $expectedCurrency = strtoupper(trim((string) ($pendingDeposit['currency'] ?? 'USDT')));
        if ($expectedCurrency === '') {
            $expectedCurrency = 'USDT';
        }
        $expectedAmount = $this->normalizeAmount($pendingDeposit['usdt_amount'] ?? 0);

        if ($expectedReceiverUid === '') {
            $error = 'Missing merchant UID';
            return false;
        }
        if ($expectedAmount === null || (float) $expectedAmount <= 0) {
            $error = 'Invalid pending amount';
            return false;
        }

        $txId = $this->extractTransactionId($tx);
        if ($txId === '') {
            $error = 'Missing transactionId';
            return false;
        }
        if ($this->isTransactionProcessed($txId)) {
            $error = 'Transaction already processed';
            return false;
        }

        // Payer UID: only validate when API provides it (PAY orderType).
        // C2C transactions from Binance do NOT expose payerInfo.binanceId on the receiver side.
        $txPayerUid = $this->normalizeUid($this->extractPayerUid($tx));
        if ($txPayerUid !== '' && $expectedPayerUid !== '') {
            if (!hash_equals($expectedPayerUid, $txPayerUid)) {
                $error = 'Payer UID does not match.';
                return false;
            }
        }

        $txReceiverUid = $this->normalizeUid($this->extractReceiverUid($tx));
        if ($txReceiverUid === '' || !hash_equals($expectedReceiverUid, $txReceiverUid)) {
            $error = 'Receiver UID does not match.';
            return false;
        }

        $txCurrency = strtoupper(trim((string) ($tx['currency'] ?? '')));
        if ($txCurrency !== $expectedCurrency) {
            $error = 'Invalid currency. USDT required.';
            return false;
        }

        $txAmount = $this->normalizeAmount($tx['amount'] ?? 0);
        if ($txAmount === null || $txAmount !== $expectedAmount) {
            $error = 'Amount does not match.';
            return false;
        }

        $createdTs = $this->toTimestamp($pendingDeposit['created_at'] ?? '');
        $txTs = $this->extractTxTimestamp($tx);
        if ($createdTs === null || $txTs === null) {
            $error = 'Missing transaction time.';
            return false;
        }

        $ttlSeconds = $this->resolvePendingTtlSeconds();
        $earliestTs = $createdTs - self::TX_TIME_SKEW_SECONDS;
        $latestTs = $createdTs + $ttlSeconds + self::TX_TIME_SKEW_SECONDS;
        if ($txTs < $earliestTs || $txTs > $latestTs) {
            $error = 'Transaction time is outside the allowed window.';
            return false;
        }

        return true;
    }

    private function normalizeUid($raw): string
    {
        $uid = trim((string) ($raw ?? ''));
        if ($uid === '') {
            return '';
        }
        $uid = preg_replace('/\s+/', '', $uid);
        return (string) $uid;
    }

    private function normalizeAmount($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $number = is_string($raw) ? str_replace(',', '', trim($raw)) : (string) $raw;
        if ($number === '' || !is_numeric($number)) {
            return null;
        }
        return number_format((float) $number, self::AMOUNT_SCALE, '.', '');
    }

    private function resolvePendingTtlSeconds(): int
    {
        if (class_exists('PendingDeposit')) {
            try {
                $ttl = (new PendingDeposit())->getPendingTtlSeconds();
                if (is_int($ttl) && $ttl > 0) {
                    return $ttl;
                }
            } catch (Throwable $e) {
                // Ignore and fallback to default
            }
        }
        return self::DEFAULT_PENDING_TTL_SECONDS;
    }

    private function nowTs(): int
    {
        return $this->timeService ? $this->timeService->nowTs() : time();
    }

    private function nowMs(): int
    {
        return $this->nowTs() * 1000;
    }

    private function nowDbSql(): string
    {
        if ($this->timeService) {
            return $this->timeService->nowSql($this->timeService->getDbTimezone());
        }
        return date('Y-m-d H:i:s', $this->nowTs());
    }

    private function toTimestamp($value): ?int
    {
        if ($this->timeService) {
            return $this->timeService->toTimestamp($value);
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }
        $ts = strtotime($raw);
        return $ts !== false ? $ts : null;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (isset($this->schemaCache[$key])) {
            return $this->schemaCache[$key];
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
        ");
        $stmt->execute([$table, $column]);
        $this->schemaCache[$key] = (int) $stmt->fetchColumn() > 0;
        return $this->schemaCache[$key];
    }
}

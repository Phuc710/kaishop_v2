<?php

/**
 * DepositService
 * Centralized business logic for user deposits.
 */
class DepositService
{
    public const METHOD_BANK_SEPAY = 'bank_sepay';
    public const METHOD_BINANCE = 'binance';
    public const MIN_AMOUNT = 10000;
    public const MAX_AMOUNT = 50000000;

    private PendingDeposit $pendingDepositModel;
    protected ?TimeService $timeService = null;

    public function __construct(?PendingDeposit $pendingDepositModel = null)
    {
        $this->pendingDepositModel = $pendingDepositModel ?: new PendingDeposit();
        if (class_exists('TimeService')) {
            $this->timeService = TimeService::instance();
        }
    }

    /**
     * @param array<string,mixed> $siteConfig
     * @param array<string,mixed> $user
     * @return array<string,mixed>
     */
    public function getProfilePanelData(array $siteConfig, array $user, ?string $requestedMethod = null): array
    {
        $activeDeposit = $this->pendingDepositModel->getActiveByUser((int) ($user['id'] ?? 0));
        $bankName = (string) ($siteConfig['bank_name'] ?? 'MB Bank');
        $bankAccount = (string) ($siteConfig['bank_account'] ?? '');
        $bankOwner = (string) ($siteConfig['bank_owner'] ?? '');
        $bonusTiers = $this->getBonusTiers($siteConfig);
        $ttlSeconds = $this->pendingDepositModel->getPendingTtlSeconds();

        $methods = $this->getAvailableMethods($siteConfig);
        $availableCodes = [];
        $enabledCodes = [];
        foreach ($methods as $method) {
            $code = (string) ($method['code'] ?? '');
            if ($code === '') {
                continue;
            }
            $availableCodes[] = $code;
            if (!empty($method['enabled'])) {
                $enabledCodes[] = $code;
            }
        }

        $activeDepositMethod = self::METHOD_BANK_SEPAY;
        if (is_array($activeDeposit)) {
            $activeDepositMethod = $this->normalizeMethodCode((string) ($activeDeposit['method'] ?? self::METHOD_BANK_SEPAY));
        }

        $requestedMethodCode = $this->normalizeMethodCode((string) $requestedMethod);
        if (is_array($activeDeposit) && in_array($activeDepositMethod, $availableCodes, true)) {
            $activeMethod = $activeDepositMethod;
        } elseif (in_array($requestedMethodCode, $enabledCodes, true)) {
            $activeMethod = $requestedMethodCode;
        } elseif (in_array(self::METHOD_BANK_SEPAY, $enabledCodes, true)) {
            $activeMethod = self::METHOD_BANK_SEPAY;
        } elseif (!empty($enabledCodes)) {
            $activeMethod = (string) $enabledCodes[0];
        } elseif (in_array($requestedMethodCode, $availableCodes, true)) {
            $activeMethod = $requestedMethodCode;
        } else {
            $activeMethod = self::METHOD_BANK_SEPAY;
        }

        $activeDepositPayload = null;
        if (is_array($activeDeposit) && $activeDepositMethod === $activeMethod) {
            $activeDepositPayload = $this->buildActiveDepositPayload(
                $activeDeposit,
                $activeMethod,
                $ttlSeconds,
                $siteConfig,
                $bankName,
                $bankAccount,
                $bankOwner
            );
        }

        return [
            'methods' => $methods,
            'active_method' => $activeMethod,
            'active_deposit_method' => $activeDepositMethod,
            'bankName' => $bankName,
            'bankAccount' => $bankAccount,
            'bankOwner' => $bankOwner,
            'bankShortName' => $this->resolveQrBankName($bankName),
            'binanceRateVnd' => max(1, (int) ($siteConfig['binance_rate_vnd'] ?? 25000)),
            'bonusTiers' => $bonusTiers,
            'activeDeposit' => $activeDeposit,
            'activeDepositPayload' => $activeDepositPayload,
            'ttlSeconds' => $ttlSeconds,
        ];

    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $siteConfig
     * @return array<string,mixed>
     */
    public function createBankDeposit(
        array $user,
        int $amount,
        array $siteConfig,
        int $sourceChannel = SourceChannelHelper::WEB
    ): array {
        if ($amount < self::MIN_AMOUNT) {
            return ['success' => false, 'message' => 'Số tiền nạp tối thiểu là ' . number_format(self::MIN_AMOUNT, 0, ',', '.') . 'đ'];
        }

        if ($amount > self::MAX_AMOUNT) {
            return ['success' => false, 'message' => 'Số tiền nạp tối đa là ' . number_format(self::MAX_AMOUNT, 0, ',', '.') . 'đ'];
        }

        $bonusTiers = $this->getBonusTiers($siteConfig);
        $bonusPercent = ($sourceChannel === SourceChannelHelper::BOTTELE) ? 0 : $this->resolveBonusPercent($amount, $bonusTiers);

        $result = $this->pendingDepositModel->createDeposit(
            (int) ($user['id'] ?? 0),
            (string) ($user['username'] ?? ''),
            $amount,
            $bonusPercent,
            SourceChannelHelper::normalize($sourceChannel)
        );

        if (!$result) {
            return ['success' => false, 'message' => 'Không thể tạo giao dịch, vui lòng thử lại'];
        }

        $bonusAmount = (int) floor($amount * $bonusPercent / 100);
        $bankName = (string) ($siteConfig['bank_name'] ?? 'MB Bank');
        $bankAccount = (string) ($siteConfig['bank_account'] ?? '');
        $bankOwner = (string) ($siteConfig['bank_owner'] ?? '');

        return [
            'success' => true,
            'data' => [
                'method' => self::METHOD_BANK_SEPAY,
                'deposit_code' => (string) $result['deposit_code'],
                'amount' => $amount,
                'bonus_percent' => $bonusPercent,
                'source_channel' => SourceChannelHelper::normalize($sourceChannel),
                'status' => 'pending',
                'status_text' => $this->mapDepositStatusText('pending'),
                'bonus_amount' => $bonusAmount,
                'total_receive' => $amount + $bonusAmount,
                'expires_at' => (string) ($result['expires_at'] ?? ''),
                'expires_at_ts' => $this->toTimestamp($result['expires_at'] ?? ''),
                'ttl_seconds' => $this->pendingDepositModel->getPendingTtlSeconds(),
                'server_now_ts' => $this->nowTs(),
                'bank_name' => $bankName,
                'bank_short_name' => $this->resolveQrBankName($bankName),
                'bank_account' => $bankAccount,
                'bank_owner' => $bankOwner,
                'qr_url' => $this->buildVietQrUrl($bankName, $bankAccount, $amount, (string) $result['deposit_code'], $bankOwner),
            ],
        ];

    }
    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $siteConfig
     * @return array<string,mixed>
     */
    public function createBinanceDeposit(
        array $user,
        float $usdAmount,
        string $payerUid,
        array $siteConfig,
        int $sourceChannel = SourceChannelHelper::WEB
    ): array {
        $payerUid = trim($payerUid);
        if (!preg_match('/^\d{4,20}$/', $payerUid)) {
            return ['success' => false, 'message' => 'Invalid Binance UID'];
        }

        if ($usdAmount < 1) {
            return ['success' => false, 'message' => 'Minimum Binance deposit is $1.00'];
        }
        if ($usdAmount > BinancePayService::MAX_USDT) {
            return ['success' => false, 'message' => 'Maximum Binance deposit is $' . number_format((int) BinancePayService::MAX_USDT)];
        }

        $binanceService = $this->makeBinanceService($siteConfig);
        if (!$binanceService || !$binanceService->isEnabled()) {
            return ['success' => false, 'message' => 'Binance Pay is not configured or not enabled'];
        }

        $usdtFinal = round($usdAmount, 8);
        $usdtFinal = max(BinancePayService::MIN_USDT, min(BinancePayService::MAX_USDT, $usdtFinal));
        $baseVnd = $binanceService->usdtToVnd($usdtFinal);
        if ($baseVnd < self::MIN_AMOUNT) {
            return ['success' => false, 'message' => 'Converted value is below minimum wallet deposit'];
        }

        $bonusTiers = $this->getBonusTiers($siteConfig);
        $bonusPercent = ($sourceChannel === SourceChannelHelper::BOTTELE) ? 0 : $this->resolveBonusPercent($baseVnd, $bonusTiers);

        $result = $this->pendingDepositModel->createBinanceDeposit(
            (int) ($user['id'] ?? 0),
            (string) ($user['username'] ?? ''),
            $baseVnd,
            $usdtFinal,
            $payerUid,
            $bonusPercent,
            SourceChannelHelper::normalize($sourceChannel)
        );

        if (!$result) {
            return ['success' => false, 'message' => 'Could not create Binance session. Please check migration/schema and try again'];
        }

        $bonusVnd = (int) floor($baseVnd * $bonusPercent / 100);
        $totalReceive = $baseVnd + $bonusVnd;
        $transferNote = $binanceService->getTransferNote((string) ($result['deposit_code'] ?? ''));

        return [
            'success' => true,
            'data' => [
                'method' => self::METHOD_BINANCE,
                'deposit_code' => (string) $result['deposit_code'],
                'amount' => $baseVnd,
                'usd_amount' => $usdtFinal,
                'usdt_amount' => $usdtFinal,
                'payer_uid' => $payerUid,
                'exchange_rate' => $binanceService->getExchangeRate(),
                'binance_uid' => $binanceService->getUid(),
                'binance_owner' => trim((string) ($siteConfig['binance_owner'] ?? get_setting('ten_web', 'KaiShop'))),
                'transfer_note' => $transferNote,
                'note_text' => $this->buildBinanceInstructionNote($transferNote),
                'warning_rules' => $this->buildBinanceRules(),
                'bonus_percent' => $bonusPercent,
                'bonus_vnd' => $bonusVnd,
                'total_receive' => $totalReceive,
                'source_channel' => SourceChannelHelper::normalize($sourceChannel),
                'status' => 'pending',
                'status_text' => $this->mapDepositStatusText('pending'),
                'expires_at' => (string) ($result['expires_at'] ?? ''),
                'expires_at_ts' => $this->toTimestamp($result['expires_at'] ?? ''),
                'ttl_seconds' => $this->pendingDepositModel->getPendingTtlSeconds(),
                'server_now_ts' => $this->nowTs(),
            ],
        ];

    }

    public function makeBinanceService(array $siteConfig): ?BinancePayService
    {
        if (!class_exists('BinancePayService')) {
            return null;
        }
        $db = Database::getInstance()->getConnection();
        return new BinancePayService($siteConfig, $db);
    }

    /**
     * @param array<string,mixed> $siteConfig
     * @return array<int,array<string,mixed>>
     */
    public function getAvailableMethods(array $siteConfig): array
    {
        $bankReady = ((int) ($siteConfig['bank_pay_enabled'] ?? 1) === 1)
            && trim((string) ($siteConfig['bank_account'] ?? '')) !== ''
            && trim((string) ($siteConfig['bank_owner'] ?? '')) !== '';
        $binanceReady = ((int) ($siteConfig['binance_pay_enabled'] ?? 0) === 1)
            && trim((string) ($siteConfig['binance_api_key'] ?? '')) !== ''
            && trim((string) ($siteConfig['binance_api_secret'] ?? '')) !== ''
            && trim((string) ($siteConfig['binance_uid'] ?? '')) !== '';

        return [
            [
                'code' => self::METHOD_BANK_SEPAY,
                'label' => 'Ngân hàng',
                'provider' => 'SePay',
                'badge' => 'Auto',
                'enabled' => $bankReady,
                'description' => 'Chuyển khoản ngân hàng tự động',
            ],
            [
                'code' => self::METHOD_BINANCE,
                'label' => 'Binance Pay',
                'provider' => 'Binance',
                'badge' => $binanceReady ? 'Auto' : 'Soon',
                'enabled' => $binanceReady,
                'description' => 'Nạp USDT qua Binance Pay (Funding)',
            ],
            [
                'code' => 'momo',
                'label' => 'MoMo',
                'provider' => 'Sắp mở',
                'badge' => 'Soon',
                'enabled' => false,
                'description' => 'Dự phòng mở rộng phương thức nạp',
            ],
        ];
    }
    /**
     * @param array<string,mixed> $siteConfig
     * @return array<int,array{amount:int,percent:int}>
     */
    public function getBonusTiers(array $siteConfig): array
    {
        $tiers = [
            ['amount' => (int) ($siteConfig['bonus_1_amount'] ?? 100000), 'percent' => (int) ($siteConfig['bonus_1_percent'] ?? 10)],
            ['amount' => (int) ($siteConfig['bonus_2_amount'] ?? 200000), 'percent' => (int) ($siteConfig['bonus_2_percent'] ?? 15)],
            ['amount' => (int) ($siteConfig['bonus_3_amount'] ?? 500000), 'percent' => (int) ($siteConfig['bonus_3_percent'] ?? 20)],
        ];

        $baseButtons = [
            ['amount' => 10000, 'percent' => 0],
            ['amount' => 20000, 'percent' => 0],
            ['amount' => 50000, 'percent' => 0],
        ];

        $merged = array_merge($baseButtons, $tiers);
        usort($merged, static fn($a, $b) => ((int) $a['amount']) <=> ((int) $b['amount']));

        $dedup = [];
        foreach ($merged as $row) {
            $amount = max(0, (int) ($row['amount'] ?? 0));
            $percent = max(0, (int) ($row['percent'] ?? 0));
            if ($amount <= 0) {
                continue;
            }
            if (!isset($dedup[$amount]) || $dedup[$amount]['percent'] < $percent) {
                $dedup[$amount] = ['amount' => $amount, 'percent' => $percent];
            }
        }

        return array_values($dedup);
    }

    /**
     * @param array<int,array{amount:int,percent:int}> $tiers
     */
    public function resolveBonusPercent(int $amount, array $tiers): int
    {
        usort($tiers, static fn($a, $b) => ((int) $b['amount']) <=> ((int) $a['amount']));
        foreach ($tiers as $tier) {
            if ((int) $tier['amount'] > 0 && $amount >= (int) $tier['amount']) {
                return max(0, (int) $tier['percent']);
            }
        }
        return 0;
    }

    public function resolveQrBankName(string $bankName): string
    {
        $map = [
            'MB Bank' => 'MB',
            'Vietcombank' => 'VCB',
            'Techcombank' => 'TCB',
            'VietinBank' => 'CTG',
            'BIDV' => 'BIDV',
            'Agribank' => 'VBA',
            'VPBank' => 'VPB',
            'ACB' => 'ACB',
            'Sacombank' => 'STB',
            'TPBank' => 'TPB',
            'MSB' => 'MSB',
            'OCB' => 'OCB',
            'VIB' => 'VIB',
            'Momo' => 'MOMO',
        ];

        return $map[$bankName] ?? $bankName;
    }

    public function buildVietQrUrl(string $bankName, string $bankAccount, int $amount, string $content, string $accountName): string
    {
        if ($bankAccount === '') {
            return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        }

        $qrBankName = $this->resolveQrBankName($bankName);
        return 'https://img.vietqr.io/image/' . rawurlencode($qrBankName) . '-' . rawurlencode($bankAccount)
            . '-qr_only.png?amount=' . $amount
            . '&addInfo=' . rawurlencode($content)
            . '&accountName=' . rawurlencode($accountName);
    }

    private function normalizeMethodCode(?string $method): string
    {
        $method = strtolower(trim((string) $method));
        if ($method === 'momo') {
            return 'momo';
        }
        if ($method === self::METHOD_BINANCE) {
            return self::METHOD_BINANCE;
        }
        return self::METHOD_BANK_SEPAY;
    }

    /**
     * @param array<string,mixed> $activeDeposit
     * @param array<string,mixed> $siteConfig
     * @return array<string,mixed>
     */
    private function buildActiveDepositPayload(
        array $activeDeposit,
        string $activeMethod,
        int $ttlSeconds,
        array $siteConfig,
        string $bankName,
        string $bankAccount,
        string $bankOwner
    ): array {
        $createdTs = $this->toTimestamp($activeDeposit['created_at'] ?? '');
        $depositCode = (string) ($activeDeposit['deposit_code'] ?? '');
        $depositAmount = (int) ($activeDeposit['amount'] ?? 0);
        $depositStatus = (string) ($activeDeposit['status'] ?? 'pending');

        $base = [
            'method' => $activeMethod,
            'deposit_code' => $depositCode,
            'amount' => $depositAmount,
            'bonus_percent' => (int) ($activeDeposit['bonus_percent'] ?? 0),
            'status' => $depositStatus,
            'status_text' => $this->mapDepositStatusText($depositStatus),
            'created_at' => (string) ($activeDeposit['created_at'] ?? ''),
            'created_at_ts' => $createdTs ?: 0,
            'expires_at_ts' => $createdTs ? ($createdTs + $ttlSeconds) : 0,
            'ttl_seconds' => $ttlSeconds,
            'server_now_ts' => $this->nowTs(),
        ];

        if ($activeMethod === self::METHOD_BINANCE) {
            $binanceService = $this->makeBinanceService($siteConfig);
            $usdtAmount = round((float) ($activeDeposit['usdt_amount'] ?? 0), 8);
            $exchangeRate = $binanceService ? $binanceService->getExchangeRate() : max(1, (int) ($siteConfig['binance_rate_vnd'] ?? 25000));
            $baseVnd = (int) floor($usdtAmount * $exchangeRate);
            $bonusPercent = (int) ($activeDeposit['bonus_percent'] ?? 0);
            $bonusVnd = (int) floor($baseVnd * $bonusPercent / 100);
            $transferNote = $binanceService
                ? $binanceService->getTransferNote($depositCode)
                : substr(strtoupper((string) preg_replace('/[^a-zA-Z0-9]/', '', $depositCode)), 0, 32);

            $base['usdt_amount'] = $usdtAmount;
            $base['usd_amount'] = $usdtAmount;
            $base['payer_uid'] = trim((string) ($activeDeposit['payer_uid'] ?? ''));
            $base['exchange_rate'] = $exchangeRate;
            $base['binance_uid'] = trim((string) ($siteConfig['binance_uid'] ?? ''));
            $base['binance_owner'] = trim((string) ($siteConfig['binance_owner'] ?? get_setting('ten_web', 'KaiShop')));
            $base['transfer_note'] = $transferNote;
            $base['note_text'] = $this->buildBinanceInstructionNote($transferNote);
            $base['warning_rules'] = $this->buildBinanceRules();
            $base['bonus_vnd'] = $bonusVnd;
            $base['total_receive'] = $baseVnd + $bonusVnd;
            return $base;
        }

        $base['bank_name'] = $bankName;
        $base['bank_short_name'] = $this->resolveQrBankName($bankName);
        $base['bank_account'] = $bankAccount;
        $base['bank_owner'] = $bankOwner;
        $base['qr_url'] = $this->buildVietQrUrl($bankName, $bankAccount, $depositAmount, $depositCode, $bankOwner);
        return $base;
    }

    private function buildBinanceInstructionNote(string $transferNote): string
    {
        return 'Binance Pay note (optional): ' . $transferNote;
    }

    /**
     * @return array<int,string>
     */
    private function buildBinanceRules(): array
    {
        return [
            'Send only USDT via Binance Pay/Funding wallet.',
            'Sender UID, receiver UID and amount must match exactly.',
            'Deposit session is valid for 05:00 minutes only.',
            'Wrong coin, wrong UID, wrong amount, or late transfer will not be auto-credited.',
        ];
    }

    private function mapDepositStatusText(string $status): string
    {
        $normalized = strtolower(trim($status));
        if ($normalized === 'completed') {
            return 'Đã hoàn tất';
        }
        if ($normalized === 'expired') {
            return 'Đã hết hạn';
        }
        if ($normalized === 'cancelled') {
            return 'Đã hủy';
        }
        return 'Đang chờ xử lý';
    }
    private function nowTs(): int
    {
        return $this->timeService ? $this->timeService->nowTs() : time();
    }

    private function toTimestamp($value): int
    {
        if ($this->timeService) {
            return (int) ($this->timeService->toTimestamp($value) ?? 0);
        }
        $ts = strtotime((string) $value);
        return $ts ?: 0;
    }
}


<?php

/**
 * DepositService
 * Centralized business logic for user deposits (extensible for future methods)
 */
class DepositService
{
    public const METHOD_BANK_SEPAY = 'bank_sepay';
    public const MIN_AMOUNT = 10000;
    public const MAX_AMOUNT = 50000000;

    private PendingDeposit $pendingDepositModel;

    public function __construct(?PendingDeposit $pendingDepositModel = null)
    {
        $this->pendingDepositModel = $pendingDepositModel ?: new PendingDeposit();
    }

    /**
     * Build profile deposit panel view data (shared by ProfileController / future pages)
     *
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
        foreach ($methods as $method) {
            $availableCodes[] = (string) ($method['code'] ?? '');
        }
        $activeMethod = in_array((string) $requestedMethod, $availableCodes, true)
            ? (string) $requestedMethod
            : self::METHOD_BANK_SEPAY;

        $activeDepositPayload = null;
        if (is_array($activeDeposit)) {
            $createdTs = strtotime((string) ($activeDeposit['created_at'] ?? ''));
            $depositCode = (string) ($activeDeposit['deposit_code'] ?? '');
            $depositAmount = (int) ($activeDeposit['amount'] ?? 0);
            $activeDepositPayload = [
                'deposit_code' => $depositCode,
                'amount' => $depositAmount,
                'bonus_percent' => (int) ($activeDeposit['bonus_percent'] ?? 0),
                'created_at' => (string) ($activeDeposit['created_at'] ?? ''),
                'created_at_ts' => $createdTs ?: 0,
                'expires_at_ts' => $createdTs ? ($createdTs + $ttlSeconds) : 0,
                'ttl_seconds' => $ttlSeconds,
                'server_now_ts' => time(),
                'bank_name' => $bankName,
                'bank_short_name' => $this->resolveQrBankName($bankName),
                'bank_account' => $bankAccount,
                'bank_owner' => $bankOwner,
                'qr_url' => $this->buildVietQrUrl($bankName, $bankAccount, $depositAmount, $depositCode, $bankOwner),
            ];
        }

        return [
            'methods' => $methods,
            'active_method' => $activeMethod,
            'bankName' => $bankName,
            'bankAccount' => $bankAccount,
            'bankOwner' => $bankOwner,
            'bankShortName' => $this->resolveQrBankName($bankName),
            'bonusTiers' => $bonusTiers,
            'activeDeposit' => $activeDeposit,
            'activeDepositPayload' => $activeDepositPayload,
            'ttlSeconds' => $ttlSeconds,
        ];
    }

    /**
     * Create a bank deposit transaction and return normalized payload.
     *
     * @param array<string,mixed> $user
     * @param array<string,mixed> $siteConfig
     * @return array<string,mixed>
     */
    public function createBankDeposit(array $user, int $amount, array $siteConfig): array
    {
        if ($amount < self::MIN_AMOUNT) {
            return ['success' => false, 'message' => 'Số tiền nạp tối thiểu 10.000đ'];
        }

        if ($amount > self::MAX_AMOUNT) {
            return ['success' => false, 'message' => 'Số tiền nạp tối đa 50.000.000đ'];
        }

        $bonusTiers = $this->getBonusTiers($siteConfig);
        $bonusPercent = $this->resolveBonusPercent($amount, $bonusTiers);

        $result = $this->pendingDepositModel->createDeposit(
            (int) ($user['id'] ?? 0),
            (string) ($user['username'] ?? ''),
            $amount,
            $bonusPercent
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
                'bonus_amount' => $bonusAmount,
                'total_receive' => $amount + $bonusAmount,
                'expires_at' => (string) ($result['expires_at'] ?? ''),
                'expires_at_ts' => !empty($result['expires_at']) ? (strtotime((string) $result['expires_at']) ?: 0) : 0,
                'ttl_seconds' => $this->pendingDepositModel->getPendingTtlSeconds(),
                'server_now_ts' => time(),
                'bank_name' => $bankName,
                'bank_short_name' => $this->resolveQrBankName($bankName),
                'bank_account' => $bankAccount,
                'bank_owner' => $bankOwner,
                'qr_url' => $this->buildVietQrUrl($bankName, $bankAccount, $amount, (string) $result['deposit_code'], $bankOwner),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $siteConfig
     * @return array<int,array<string,mixed>>
     */
    public function getAvailableMethods(array $siteConfig): array
    {
        $bankReady = trim((string) ($siteConfig['bank_account'] ?? '')) !== '' && trim((string) ($siteConfig['bank_owner'] ?? '')) !== '';

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
                'code' => 'binance',
                'label' => 'Binance',
                'provider' => 'Sắp mở',
                'badge' => 'Soon',
                'enabled' => false,
                'description' => 'Để trống, sẵn sàng mở rộng sau',
            ],
            [
                'code' => 'momo',
                'label' => 'MoMo',
                'provider' => 'Sắp mở',
                'badge' => 'Soon',
                'enabled' => false,
                'description' => 'Để trống, sẵn sàng mở rộng sau',
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
}

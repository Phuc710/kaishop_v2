<?php

/**
 * PendingDeposit Model
 * Handles pending bank deposit transactions.
 */
class PendingDeposit extends Model
{
    protected $table = 'pending_deposits';
    private const PENDING_TTL_SECONDS = 300;
    protected ?TimeService $timeService = null;
    private ?bool $hasSourceChannelColumn = null;

    public function __construct()
    {
        parent::__construct();
        if (class_exists('TimeService')) {
            $this->timeService = TimeService::instance();
        }
        $this->ensureSourceChannelSchema();
    }

    /**
     * Generate a unique deposit code: "kai" + random alphanumeric.
     */
    private function generateCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = 'kai';
        for ($i = 0; $i < 8; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }

    /**
     * Create a new pending deposit.
     *
     * @return array{id: int, deposit_code: string, expires_at: string}|false
     */
    public function createDeposit(
        int $userId,
        string $username,
        int $amount,
        int $bonusPercent = 0,
        int $sourceChannel = SourceChannelHelper::WEB
    ) {
        // Cancel any existing pending deposits for this user
        $this->cancelAllPendingByUser($userId);

        // Expire old ones globally
        $this->markExpired();

        $code = $this->generateCode();
        $now = $this->nowDbSql();

        $columns = ['user_id', 'username', 'deposit_code', 'amount', 'bonus_percent', 'status', 'created_at'];
        $marks = [':uid', ':uname', ':code', ':amount', ':bonus', "'pending'", ':now'];
        $params = [
            'uid' => $userId,
            'uname' => $username,
            'code' => $code,
            'amount' => $amount,
            'bonus' => $bonusPercent,
            'now' => $now,
        ];

        if ($this->hasSourceChannelColumn()) {
            $columns[] = 'source_channel';
            $marks[] = ':source_channel';
            $params['source_channel'] = SourceChannelHelper::normalize($sourceChannel);
        }

        $sql = "
            INSERT INTO `{$this->table}`
            (`" . implode('`, `', $columns) . "`)
            VALUES (" . implode(', ', $marks) . ")
        ";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);

        if (!$result) {
            return false;
        }

        $expiresAt = $this->formatDbFromTimestamp($this->nowTs() + $this->getPendingTtlSeconds());

        return [
            'id' => (int) $this->db->lastInsertId(),
            'deposit_code' => $code,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Create a Binance Pay pending deposit.
     * Stores the specific USDT amount user must send (used for matching).
     *
     * @return array{id:int,deposit_code:string,expires_at:string}|false
     */
    public function createBinanceDeposit(
        int $userId,
        string $username,
        int $vndAmount,
        float $usdtAmount,
        string $payerUid,
        int $bonusPercent = 0,
        int $sourceChannel = SourceChannelHelper::WEB
    ) {
        if (
            !$this->hasColumnCached($this->table, 'method')
            || !$this->hasColumnCached($this->table, 'usdt_amount')
            || !$this->hasColumnCached($this->table, 'payer_uid')
        ) {
            return false;
        }

        $this->cancelAllPendingByUser($userId);
        $this->markExpired();

        $code = $this->generateCode();
        $now = $this->nowDbSql();

        $columns = ['user_id', 'username', 'deposit_code', 'amount', 'bonus_percent', 'status', 'created_at'];
        $values = [$userId, $username, $code, $vndAmount, $bonusPercent, 'pending', $now];

        if ($this->hasColumnCached($this->table, 'source_channel')) {
            $columns[] = 'source_channel';
            $values[] = SourceChannelHelper::normalize($sourceChannel);
        }
        $columns[] = 'method';
        $values[] = 'binance';
        $columns[] = 'usdt_amount';
        $values[] = round($usdtAmount, 8);
        $columns[] = 'payer_uid';
        $values[] = trim($payerUid);

        $marks = implode(', ', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO `{$this->table}` (`" . implode('`, `', $columns) . "`) VALUES ({$marks})";
        $stmt = $this->db->prepare($sql);
        if (!$stmt->execute($values)) {
            return false;
        }

        return [
            'id' => (int) $this->db->lastInsertId(),
            'deposit_code' => $code,
            'expires_at' => $this->formatDbFromTimestamp($this->nowTs() + $this->getPendingTtlSeconds()),
        ];
    }

    /**
     * Find a pending deposit by its unique code.
     */
    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `deposit_code` = :code LIMIT 1");
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find a pending deposit by SePay transaction ID (for duplicate check).
     */
    public function findBySepayId(int $sepayId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `sepay_transaction_id` = :sid LIMIT 1");
        $stmt->execute(['sid' => $sepayId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Mark a deposit as completed.
     */
    public function markComplete(int $id, int $sepayTransId): bool
    {
        return $this->markCompletedByGateway($id, $sepayTransId);
    }

    /**
     * Mark a pending deposit as completed using an optional gateway transaction ID.
     * For Binance flow, pass null to avoid coupling with SePay transaction IDs.
     */
    public function markCompletedByGateway(int $id, ?int $gatewayTransactionId = null): bool
    {
        $this->markExpired();
        $nowSql = $this->nowDbSql();

        if ($this->hasColumnCached($this->table, 'sepay_transaction_id')) {
            $stmt = $this->db->prepare("
                UPDATE `{$this->table}`
                SET `status` = 'completed', `sepay_transaction_id` = :gateway_id, `completed_at` = :now
                WHERE `id` = :id AND `status` = 'pending'
            ");
            $gatewayValue = ($gatewayTransactionId !== null && $gatewayTransactionId > 0) ? $gatewayTransactionId : null;
            return $stmt->execute(['id' => $id, 'gateway_id' => $gatewayValue, 'now' => $nowSql]);
        }

        $stmt = $this->db->prepare("
            UPDATE `{$this->table}`
            SET `status` = 'completed', `completed_at` = :now
            WHERE `id` = :id AND `status` = 'pending'
        ");
        return $stmt->execute(['id' => $id, 'now' => $nowSql]);
    }

    /**
     * Cancel a deposit by the user (only if pending).
     */
    public function cancelByUser(int $depositId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE `{$this->table}` 
            SET `status` = 'cancelled'
            WHERE `id` = :id AND `user_id` = :uid AND `status` = 'pending'
        ");
        return $stmt->execute(['id' => $depositId, 'uid' => $userId]);
    }

    /**
     * Cancel ALL pending deposits for a user (when creating a new one).
     */
    public function cancelAllPendingByUser(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE `{$this->table}` SET `status` = 'cancelled'
            WHERE `user_id` = :uid AND `status` = 'pending'
        ");
        $stmt->execute(['uid' => $userId]);
    }

    /**
     * Expire all deposits older than 5 minutes.
     */
    public function markExpired(): array
    {
        $ts = $this->nowTs();
        $cutoff = $this->formatDbFromTimestamp($ts - $this->getPendingTtlSeconds());

        // Fetch deposits about to expire (for notification)
        $fetchStmt = $this->db->prepare("
            SELECT * FROM `{$this->table}`
            WHERE `status` = 'pending' AND `created_at` < :cutoff
        ");
        $fetchStmt->execute(['cutoff' => $cutoff]);
        $justExpired = $fetchStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Now mark them as expired
        if (!empty($justExpired)) {
            $stmt = $this->db->prepare("
                UPDATE `{$this->table}` SET `status` = 'expired'
                WHERE `status` = 'pending' AND `created_at` < :cutoff
            ");
            $stmt->execute(['cutoff' => $cutoff]);
        }

        return $justExpired;
    }

    /**
     * Get the active (pending) deposit for a user.
     */
    public function getActiveByUser(int $userId): ?array
    {
        // Expire old ones first
        $this->markExpired();

        $stmt = $this->db->prepare("
            SELECT * FROM `{$this->table}` 
            WHERE `user_id` = :uid AND `status` = 'pending'
            ORDER BY `created_at` DESC LIMIT 1
        ");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getPendingTtlSeconds(): int
    {
        return self::PENDING_TTL_SECONDS;
    }

    /**
     * Fetch pending deposits by method for background workers.
     *
     * @return array<int,array<string,mixed>>
     */
    public function fetchPendingByMethod(string $method, int $limit = 30): array
    {
        $method = trim(strtolower($method));
        if ($method === '') {
            return [];
        }
        if (!$this->hasColumnCached($this->table, 'method')) {
            return [];
        }

        $sql = "
            SELECT *
            FROM `{$this->table}`
            WHERE `status` = 'pending' AND `method` = :method
            ORDER BY `created_at` ASC
            LIMIT :limit
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':method', $method, PDO::PARAM_STR);
        $stmt->bindValue(':limit', max(1, min(200, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Check if a deposit record is logically expired based on created_at and TTL.
     */
    public function isLogicallyExpired(array $deposit): bool
    {
        if (($deposit['status'] ?? '') !== 'pending') {
            return ($deposit['status'] ?? '') === 'expired';
        }
        $createdAt = $this->toTimestamp($deposit['created_at'] ?? '');
        if (!$createdAt)
            return true;
        return ($this->nowTs() - $createdAt) >= $this->getPendingTtlSeconds();
    }

    private function nowTs(): int
    {
        return $this->timeService ? $this->timeService->nowTs() : time();
    }

    private function nowDbSql(): string
    {
        if ($this->timeService) {
            return $this->timeService->nowSql($this->timeService->getDbTimezone());
        }
        return date('Y-m-d H:i:s');
    }

    private function formatDbFromTimestamp(int $ts): string
    {
        if ($this->timeService) {
            return $this->timeService->formatDb($ts);
        }
        return date('Y-m-d H:i:s', $ts);
    }

    private function toTimestamp($value): int
    {
        if ($this->timeService) {
            return (int) ($this->timeService->toTimestamp($value) ?? 0);
        }
        $ts = strtotime((string) $value);
        return $ts ?: 0;
    }

    private function ensureSourceChannelSchema(): void
    {
        if (!$this->tableExists()) {
            return;
        }
        if ($this->hasSourceChannelColumn()) {
            return;
        }

        try {
            $this->db->exec("ALTER TABLE `{$this->table}` ADD COLUMN `source_channel` TINYINT(1) NOT NULL DEFAULT 0 AFTER `bonus_percent`");
        } catch (Throwable $e) {
            // ignore if ALTER is restricted
        }

        try {
            $this->db->exec("ALTER TABLE `{$this->table}` ADD KEY `idx_pd_source_status_created` (`source_channel`, `status`, `created_at`)");
        } catch (Throwable $e) {
            // ignore if key exists or ALTER is restricted
        }

        $this->hasSourceChannelColumn = null;
    }

    private function tableExists(): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = ?
        ");
        $stmt->execute([$this->table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function hasSourceChannelColumn(): bool
    {
        if ($this->hasSourceChannelColumn !== null) {
            return $this->hasSourceChannelColumn;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = ? AND column_name = 'source_channel'
        ");
        $stmt->execute([$this->table]);
        $this->hasSourceChannelColumn = (int) $stmt->fetchColumn() > 0;
        return $this->hasSourceChannelColumn;
    }

    /**
     * Generic column existence check with per-request caching.
     */
    private function hasColumnCached(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        // Re-use hasSourceChannelColumn cache for source_channel specifically
        if ($column === 'source_channel' && $table === $this->table && $this->hasSourceChannelColumn !== null) {
            return $this->hasSourceChannelColumn;
        }
        static $cache = [];
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c
        ");
        $stmt->execute(['t' => $table, 'c' => $column]);
        return $cache[$key] = (int) $stmt->fetchColumn() > 0;
    }
}

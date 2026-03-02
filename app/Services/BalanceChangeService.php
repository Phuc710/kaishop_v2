<?php

/**
 * BalanceChangeService
 * Centralized writer for unified balance change logs.
 */
class BalanceChangeService
{
    private PDO $db;
    private ?TimeService $timeService = null;
    private ?bool $tableExists = null;
    private ?bool $hasSourceChannelColumn = null;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getInstance()->getConnection();
        if (class_exists('TimeService')) {
            $this->timeService = TimeService::instance();
        }
        $this->ensureSourceChannelSchema();
    }

    public function isAvailable(): bool
    {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }

        $stmt = $this->db->query("
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = 'lich_su_bien_dong_so_du'
            LIMIT 1
        ");
        $this->tableExists = (bool) ($stmt ? $stmt->fetchColumn() : false);
        return $this->tableExists;
    }

    public function record(
        int $userId,
        string $username,
        int $beforeBalance,
        int $changeAmount,
        int $afterBalance,
        string $reason,
        int $sourceChannel = SourceChannelHelper::WEB
    ): bool {
        if ($userId <= 0 || $changeAmount === 0 || !$this->isAvailable()) {
            return false;
        }

        $username = trim($username);
        $reason = trim($reason);

        $rawTime = (string) ($this->timeService ? $this->timeService->nowTs() : time());
        $createdAt = $this->timeService
            ? $this->timeService->nowSql($this->timeService->getDbTimezone())
            : date('Y-m-d H:i:s');

        try {
            $columns = ['user_id', 'username', 'before_balance', 'change_amount', 'after_balance', 'reason', 'time', 'created_at'];
            $values = [$userId, $username, $beforeBalance, $changeAmount, $afterBalance, $reason, $rawTime, $createdAt];

            if ($this->hasSourceChannelColumn()) {
                $columns[] = 'source_channel';
                $values[] = SourceChannelHelper::normalize($sourceChannel);
            }

            $marks = implode(', ', array_fill(0, count($columns), '?'));
            $sql = "
                INSERT INTO `lich_su_bien_dong_so_du`
                    (`" . implode('`, `', $columns) . "`)
                VALUES
                    ({$marks})
            ";
            $stmt = $this->db->prepare($sql);

            return $stmt->execute($values);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function ensureSourceChannelSchema(): void
    {
        if (!$this->isAvailable()) {
            return;
        }

        if ($this->hasSourceChannelColumn()) {
            return;
        }

        try {
            $this->db->exec("ALTER TABLE `lich_su_bien_dong_so_du` ADD COLUMN `source_channel` TINYINT(1) NOT NULL DEFAULT 0 AFTER `reason`");
        } catch (Throwable $e) {
            // ignore if ALTER is restricted
        }

        try {
            $this->db->exec("ALTER TABLE `lich_su_bien_dong_so_du` ADD KEY `idx_lsbd_source_created` (`source_channel`, `created_at`)");
        } catch (Throwable $e) {
            // ignore if key exists or ALTER is restricted
        }

        $this->hasSourceChannelColumn = null;
    }

    private function hasSourceChannelColumn(): bool
    {
        if ($this->hasSourceChannelColumn !== null) {
            return $this->hasSourceChannelColumn;
        }

        $stmt = $this->db->query("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'lich_su_bien_dong_so_du'
              AND column_name = 'source_channel'
        ");
        $this->hasSourceChannelColumn = (int) ($stmt ? $stmt->fetchColumn() : 0) > 0;
        return $this->hasSourceChannelColumn;
    }
}

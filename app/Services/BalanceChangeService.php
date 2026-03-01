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

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getInstance()->getConnection();
        if (class_exists('TimeService')) {
            $this->timeService = TimeService::instance();
        }
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
        string $reason
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
            $stmt = $this->db->prepare("
                INSERT INTO `lich_su_bien_dong_so_du`
                    (`user_id`, `username`, `before_balance`, `change_amount`, `after_balance`, `reason`, `time`, `created_at`)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            return $stmt->execute([
                $userId,
                $username,
                $beforeBalance,
                $changeAmount,
                $afterBalance,
                $reason,
                $rawTime,
                $createdAt,
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

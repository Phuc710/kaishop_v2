<?php

/**
 * AccountEcosystemService
 * Merge two user ecosystems into one primary user without deleting business data.
 */
class AccountEcosystemService
{
    private PDO $db;
    private ?TimeService $timeService = null;
    private ?BalanceChangeService $balanceChangeService = null;
    private array $tableExistsCache = [];
    private array $columnCache = [];

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getInstance()->getConnection();
        $this->timeService = class_exists('TimeService') ? TimeService::instance() : null;
        $this->balanceChangeService = class_exists('BalanceChangeService') ? new BalanceChangeService($this->db) : null;
    }

    /**
     * @return array<string,mixed>
     */
    public function mergeIntoPrimary(int $sourceUserId, int $primaryUserId): array
    {
        if ($sourceUserId <= 0 || $primaryUserId <= 0) {
            return ['success' => false, 'message' => 'Invalid user id.'];
        }

        if ($sourceUserId === $primaryUserId) {
            return [
                'success' => true,
                'merged' => false,
                'message' => 'Users are already the same.',
            ];
        }

        $startedTx = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTx = true;
            }

            $source = $this->getUserForUpdate($sourceUserId);
            $primary = $this->getUserForUpdate($primaryUserId);
            if (!$source || !$primary) {
                throw new RuntimeException('User not found for merge.');
            }

            $sourceUsername = (string) ($source['username'] ?? '');
            $primaryUsername = (string) ($primary['username'] ?? '');
            if ($sourceUsername === '' || $primaryUsername === '') {
                throw new RuntimeException('Username is empty.');
            }

            $sourceBalance = (int) ($source['money'] ?? 0);
            $primaryBalanceBefore = (int) ($primary['money'] ?? 0);
            $primaryBalanceAfter = $primaryBalanceBefore + $sourceBalance;

            $sourceTongNap = (int) ($source['tong_nap'] ?? 0);
            $primaryTongNapBefore = (int) ($primary['tong_nap'] ?? 0);
            $primaryTongNapAfter = $primaryTongNapBefore + $sourceTongNap;

            $nowSql = $this->timeService ? $this->timeService->nowSql($this->timeService->getDbTimezone()) : date('Y-m-d H:i:s');

            $updPrimary = $this->db->prepare("
                UPDATE `users`
                SET `money` = ?, `tong_nap` = ?, `updated_at` = ?
                WHERE `id` = ?
                LIMIT 1
            ");
            $updPrimary->execute([$primaryBalanceAfter, $primaryTongNapAfter, $nowSql, $primaryUserId]);

            $updSource = $this->db->prepare("
                UPDATE `users`
                SET `money` = 0, `tong_nap` = 0, `updated_at` = ?
                WHERE `id` = ?
                LIMIT 1
            ");
            $updSource->execute([$nowSql, $sourceUserId]);

            $moved = [
                'orders' => $this->moveByUserAndUsername('orders', $sourceUserId, $primaryUserId, $sourceUsername, $primaryUsername),
                'pending_deposits' => $this->moveByUserAndUsername('pending_deposits', $sourceUserId, $primaryUserId, $sourceUsername, $primaryUsername),
                'history_nap_bank' => $this->moveByUsername('history_nap_bank', $sourceUsername, $primaryUsername),
                'lich_su_hoat_dong' => $this->moveByUsername('lich_su_hoat_dong', $sourceUsername, $primaryUsername),
                'lich_su_bien_dong_so_du' => $this->moveByUserAndUsername('lich_su_bien_dong_so_du', $sourceUserId, $primaryUserId, $sourceUsername, $primaryUsername),
                'lich_su_mua_code' => $this->moveByUsername('lich_su_mua_code', $sourceUsername, $primaryUsername),
                'system_logs' => $this->moveByUserAndUsername('system_logs', $sourceUserId, $primaryUserId, $sourceUsername, $primaryUsername),
                'telegram_link_codes' => $this->moveByUserId('telegram_link_codes', $sourceUserId, $primaryUserId),
                'auth_sessions' => $this->moveByUserId('auth_sessions', $sourceUserId, $primaryUserId),
                'auth_otp_codes' => $this->moveByUserId('auth_otp_codes', $sourceUserId, $primaryUserId),
                'user_fingerprints' => $this->moveByUserAndUsername('user_fingerprints', $sourceUserId, $primaryUserId, $sourceUsername, $primaryUsername),
                'ban_history_user' => $this->moveByColumn('ban_history', 'target_user_id', $sourceUserId, $primaryUserId),
                'ban_history_username' => $this->moveByColumn('ban_history', 'target_username', $sourceUsername, $primaryUsername),
            ];

            if ($sourceBalance !== 0 && $this->balanceChangeService) {
                $this->balanceChangeService->record(
                    $primaryUserId,
                    $primaryUsername,
                    $primaryBalanceBefore,
                    $sourceBalance,
                    $primaryBalanceAfter,
                    'Hop nhat he sinh thai tu ' . $sourceUsername
                );
            }

            $this->writeActivity(
                $primaryUsername,
                'Hop nhat tai khoan Telegram/Web tu: ' . $sourceUsername,
                $sourceBalance
            );

            if ($startedTx && $this->db->inTransaction()) {
                $this->db->commit();
            }

            return [
                'success' => true,
                'merged' => true,
                'source_user_id' => $sourceUserId,
                'primary_user_id' => $primaryUserId,
                'source_balance' => $sourceBalance,
                'primary_balance_before' => $primaryBalanceBefore,
                'primary_balance_after' => $primaryBalanceAfter,
                'moved' => $moved,
            ];
        } catch (Throwable $e) {
            if ($startedTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function getUserForUpdate(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `id` = ? LIMIT 1 FOR UPDATE");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function writeActivity(string $username, string $action, int $amount): void
    {
        if (!$this->tableExists('lich_su_hoat_dong')) {
            return;
        }

        $nowTs = (string) ($this->timeService ? $this->timeService->nowTs() : time());
        try {
            $stmt = $this->db->prepare("
                INSERT INTO `lich_su_hoat_dong` (`username`, `hoatdong`, `gia`, `time`)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$username, $action, (string) $amount, $nowTs]);
        } catch (Throwable $e) {
            // Non-blocking.
        }
    }

    private function moveByUserId(string $table, int $sourceUserId, int $primaryUserId): int
    {
        if (!$this->tableExists($table) || !$this->hasColumn($table, 'user_id')) {
            return 0;
        }

        $sql = "UPDATE `{$table}` SET `user_id` = ? WHERE `user_id` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$primaryUserId, $sourceUserId]);
        return $stmt->rowCount();
    }

    private function moveByUsername(string $table, string $sourceUsername, string $primaryUsername): int
    {
        if (!$this->tableExists($table) || !$this->hasColumn($table, 'username')) {
            return 0;
        }

        $sql = "UPDATE `{$table}` SET `username` = ? WHERE `username` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$primaryUsername, $sourceUsername]);
        return $stmt->rowCount();
    }

    /**
     * @param int|string $sourceValue
     * @param int|string $targetValue
     */
    private function moveByColumn(string $table, string $column, $sourceValue, $targetValue): int
    {
        if (!$this->tableExists($table) || !$this->hasColumn($table, $column)) {
            return 0;
        }

        $sql = "UPDATE `{$table}` SET `{$column}` = ? WHERE `{$column}` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$targetValue, $sourceValue]);
        return $stmt->rowCount();
    }

    private function moveByUserAndUsername(
        string $table,
        int $sourceUserId,
        int $primaryUserId,
        string $sourceUsername,
        string $primaryUsername
    ): int {
        if (!$this->tableExists($table)) {
            return 0;
        }

        $setParts = [];
        $params = [];
        if ($this->hasColumn($table, 'user_id')) {
            $setParts[] = "`user_id` = ?";
            $params[] = $primaryUserId;
        }
        if ($this->hasColumn($table, 'username')) {
            $setParts[] = "`username` = ?";
            $params[] = $primaryUsername;
        }
        if (empty($setParts)) {
            return 0;
        }

        $whereParts = [];
        if ($this->hasColumn($table, 'user_id')) {
            $whereParts[] = "`user_id` = ?";
            $params[] = $sourceUserId;
        }
        if ($this->hasColumn($table, 'username')) {
            $whereParts[] = "`username` = ?";
            $params[] = $sourceUsername;
        }
        if (empty($whereParts)) {
            return 0;
        }

        $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE " . implode(' OR ', $whereParts);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    private function tableExists(string $table): bool
    {
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = ?
        ");
        $stmt->execute([$table]);
        $exists = ((int) $stmt->fetchColumn()) > 0;
        $this->tableExistsCache[$table] = $exists;
        return $exists;
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        if (!isset($this->columnCache[$table])) {
            $stmt = $this->db->query("SHOW COLUMNS FROM `{$table}`");
            $cols = [];
            foreach (($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
                $cols[] = (string) ($row['Field'] ?? '');
            }
            $this->columnCache[$table] = $cols;
        }

        return in_array($column, $this->columnCache[$table], true);
    }
}

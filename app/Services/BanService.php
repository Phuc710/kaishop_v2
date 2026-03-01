<?php

class BanService
{
    private PDO $db;
    private static bool $expiredStateSynced = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->syncExpiredState();
    }

    public function syncExpiredState(): void
    {
        if (self::$expiredStateSynced) {
            return;
        }
        self::$expiredStateSynced = true;

        try {
            $this->db->exec("UPDATE `users` SET `bannd` = 0, `ban_reason` = NULL, `banned_at` = NULL, `ban_expires_at` = NULL, `ban_source` = NULL, `banned_by` = NULL WHERE `bannd` = 1 AND `ban_expires_at` IS NOT NULL AND `ban_expires_at` <= NOW()");
        } catch (Throwable $e) {
            // non-blocking
        }

        try {
            $this->db->exec("DELETE FROM `banned_fingerprints` WHERE `expires_at` IS NOT NULL AND `expires_at` <= NOW()");
        } catch (Throwable $e) {
            // non-blocking
        }

        try {
            $this->db->exec("UPDATE `ban_history` SET `status` = 'expired', `ended_at` = COALESCE(`ended_at`, `expires_at`, NOW()), `ended_by` = COALESCE(`ended_by`, 'system') WHERE `status` = 'active' AND `expires_at` IS NOT NULL AND `expires_at` <= NOW()");
        } catch (Throwable $e) {
            // non-blocking
        }

        $this->backfillActiveStateToHistory();
    }

    public function normalizeDuration(?string $durationKey): array
    {
        $key = strtolower(trim((string) $durationKey));
        $map = [
            '' => ['minutes' => null, 'label' => 'Vinh vien'],
            'permanent' => ['minutes' => null, 'label' => 'Vinh vien'],
            '30m' => ['minutes' => 30, 'label' => '30 phut'],
            '1d' => ['minutes' => 1440, 'label' => '1 ngay'],
            '3d' => ['minutes' => 4320, 'label' => '3 ngay'],
            '7d' => ['minutes' => 10080, 'label' => '7 ngay'],
            '30d' => ['minutes' => 43200, 'label' => '30 ngay'],
        ];

        if (!isset($map[$key])) {
            $key = '';
        }

        $minutes = $map[$key]['minutes'];
        $expiresAt = null;
        if (is_int($minutes) && $minutes > 0) {
            $expiresAt = $this->dbNow()->modify('+' . $minutes . ' minutes')->format('Y-m-d H:i:s');
        }

        return [
            'key' => $key,
            'minutes' => $minutes,
            'label' => $map[$key]['label'],
            'expires_at' => $expiresAt,
        ];
    }

    public function applyAccountBan(array $user, string $reason, ?string $durationKey, string $adminName): bool
    {
        $userId = (int) ($user['id'] ?? 0);
        $username = (string) ($user['username'] ?? '');
        if ($userId <= 0 || $username === '') {
            return false;
        }

        $duration = $this->normalizeDuration($durationKey);
        $startedAt = $this->dbNow()->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare("UPDATE `users` SET `bannd` = 1, `ban_reason` = ?, `banned_at` = ?, `ban_expires_at` = ?, `ban_source` = 'admin', `banned_by` = ? WHERE `id` = ?");
        $ok = $stmt->execute([
            $reason,
            $startedAt,
            $duration['expires_at'],
            $adminName,
            $userId,
        ]);

        if ($ok) {
            $this->closeActiveHistory('account', ['target_user_id' => $userId], 'replaced', $adminName);
            $this->insertHistory([
                'scope' => 'account',
                'target_user_id' => $userId,
                'target_username' => $username,
                'reason' => $reason,
                'source' => 'admin',
                'banned_by' => $adminName,
                'started_at' => $startedAt,
                'expires_at' => $duration['expires_at'],
                'status' => 'active',
            ]);
        }

        return $ok;
    }

    public function applyDeviceBan(array $user, string $fingerprintHash, string $reason, ?string $durationKey, string $adminName): bool
    {
        $userId = (int) ($user['id'] ?? 0);
        $username = (string) ($user['username'] ?? '');
        if ($userId <= 0 || $username === '' || $fingerprintHash === '') {
            return false;
        }

        $duration = $this->normalizeDuration($durationKey);
        $startedAt = $this->dbNow()->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO `banned_fingerprints` (`fingerprint_hash`, `reason`, `banned_by`, `created_at`, `expires_at`, `source`, `target_user_id`, `target_username`)
             VALUES (?, ?, ?, ?, ?, 'admin', ?, ?)
             ON DUPLICATE KEY UPDATE
                `reason` = VALUES(`reason`),
                `banned_by` = VALUES(`banned_by`),
                `created_at` = VALUES(`created_at`),
                `expires_at` = VALUES(`expires_at`),
                `source` = 'admin',
                `target_user_id` = VALUES(`target_user_id`),
                `target_username` = VALUES(`target_username`)"
        );
        $ok = $stmt->execute([
            $fingerprintHash,
            $reason,
            $adminName,
            $startedAt,
            $duration['expires_at'],
            $userId,
            $username,
        ]);

        if ($ok) {
            $this->closeActiveHistory('device', ['target_fingerprint' => $fingerprintHash], 'replaced', $adminName);
            $this->insertHistory([
                'scope' => 'device',
                'target_user_id' => $userId,
                'target_username' => $username,
                'target_fingerprint' => $fingerprintHash,
                'reason' => $reason,
                'source' => 'admin',
                'banned_by' => $adminName,
                'started_at' => $startedAt,
                'expires_at' => $duration['expires_at'],
                'status' => 'active',
            ]);
        }

        return $ok;
    }

    public function releaseAccountBan(string $username, string $endedBy): bool
    {
        $stmt = $this->db->prepare("UPDATE `users` SET `bannd` = 0, `ban_reason` = NULL, `banned_at` = NULL, `ban_expires_at` = NULL, `ban_source` = NULL, `banned_by` = NULL WHERE `username` = ?");
        $ok = $stmt->execute([$username]);
        if ($ok) {
            $this->closeActiveHistory('account', ['target_username' => $username], 'released', $endedBy);
        }
        return $ok;
    }

    public function releaseDeviceBan(string $fingerprintHash, string $endedBy): bool
    {
        $stmt = $this->db->prepare("DELETE FROM `banned_fingerprints` WHERE `fingerprint_hash` = ?");
        $ok = $stmt->execute([$fingerprintHash]);
        if ($ok) {
            $this->closeActiveHistory('device', ['target_fingerprint' => $fingerprintHash], 'released', $endedBy);
        }
        return $ok;
    }

    public function closeIpBanHistoryByIp(string $ip, string $endedBy): void
    {
        $this->closeActiveHistory('ip', ['target_ip' => $ip], 'released', $endedBy);
    }

    public function closeIpBanHistoryByRef(string $refHash, string $endedBy): void
    {
        $this->closeActiveHistory('ip', ['ref_hash' => $refHash], 'released', $endedBy);
    }

    public function recordAutoIpBan(string $ip, string $reason, ?string $expiresAt, string $refHash, string $userAgent): void
    {
        $startedAt = $this->dbNow()->format('Y-m-d H:i:s');
        $this->closeActiveHistory('ip', ['target_ip' => $ip], 'replaced', 'antiflood_system');
        $this->insertHistory([
            'scope' => 'ip',
            'target_ip' => $ip,
            'reason' => $reason,
            'source' => 'antiflood',
            'banned_by' => 'antiflood_system',
            'ref_hash' => $refHash,
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
            'status' => 'active',
        ]);
    }

    public function recordAutoDeviceBan(string $fingerprintHash, string $reason): void
    {
        $startedAt = $this->dbNow()->format('Y-m-d H:i:s');
        $this->closeActiveHistory('device', ['target_fingerprint' => $fingerprintHash], 'replaced', 'antiflood_system');
        $this->insertHistory([
            'scope' => 'device',
            'target_fingerprint' => $fingerprintHash,
            'reason' => $reason,
            'source' => 'antiflood',
            'banned_by' => 'antiflood_system',
            'started_at' => $startedAt,
            'expires_at' => null,
            'status' => 'active',
        ]);
    }

    public function getActiveIpBan(string $ip): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `ip_blacklist` WHERE `ip_address` = ? AND (`expires_at` IS NULL OR `expires_at` > NOW()) ORDER BY `id` DESC LIMIT 1");
        $stmt->execute([$ip]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getActiveDeviceBan(string $fingerprintHash): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `banned_fingerprints` WHERE `fingerprint_hash` = ? AND (`expires_at` IS NULL OR `expires_at` > NOW()) ORDER BY `id` DESC LIMIT 1");
        $stmt->execute([$fingerprintHash]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getActiveAccountBanByUserId(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT `id`, `username`, `ban_reason`, `banned_at`, `ban_expires_at`, `ban_source`, `banned_by` FROM `users` WHERE `id` = ? AND `bannd` = 1 AND (`ban_expires_at` IS NULL OR `ban_expires_at` > NOW()) LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAdminHistory(string $search = ''): array
    {
        if ($search !== '') {
            $like = '%' . $search . '%';
            $stmt = $this->db->prepare(
                "SELECT * FROM `ban_history`
                 WHERE `source` = 'admin'
                   AND (`target_username` LIKE ? OR `target_ip` LIKE ? OR `target_fingerprint` LIKE ? OR `reason` LIKE ? OR `banned_by` LIKE ?)
                 ORDER BY `started_at` DESC
                 LIMIT 200"
            );
            $stmt->execute([$like, $like, $like, $like, $like]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $stmt = $this->db->query("SELECT * FROM `ban_history` WHERE `source` = 'admin' ORDER BY `started_at` DESC LIMIT 200");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function backfillActiveStateToHistory(): void
    {
        try {
            $rows = $this->db->query("SELECT `id`, `username`, `ban_reason`, `banned_at`, `ban_expires_at`, `ban_source`, `banned_by`, `created_at` FROM `users` WHERE `bannd` = 1")->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                $userId = (int) ($row['id'] ?? 0);
                if ($userId <= 0 || $this->hasActiveHistory('account', ['target_user_id' => $userId])) {
                    continue;
                }
                $this->insertHistory([
                    'scope' => 'account',
                    'target_user_id' => $userId,
                    'target_username' => (string) ($row['username'] ?? ''),
                    'reason' => (string) ($row['ban_reason'] ?? ''),
                    'source' => (string) ($row['ban_source'] ?: 'admin'),
                    'banned_by' => (string) ($row['banned_by'] ?: 'legacy_admin'),
                    'started_at' => (string) (($row['banned_at'] ?? '') ?: ($row['created_at'] ?? $this->dbNow()->format('Y-m-d H:i:s'))),
                    'expires_at' => !empty($row['ban_expires_at']) ? (string) $row['ban_expires_at'] : null,
                    'status' => 'active',
                ]);
            }
        } catch (Throwable $e) {
            // non-blocking
        }

        try {
            $rows = $this->db->query("SELECT `fingerprint_hash`, `reason`, `banned_by`, `created_at`, `expires_at`, `source`, `target_user_id`, `target_username` FROM `banned_fingerprints`")->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                $fingerprint = (string) ($row['fingerprint_hash'] ?? '');
                if ($fingerprint === '' || $this->hasActiveHistory('device', ['target_fingerprint' => $fingerprint])) {
                    continue;
                }
                $source = (string) ($row['source'] ?? '');
                if ($source === '') {
                    $source = ((string) ($row['banned_by'] ?? '')) === 'antiflood_system' ? 'antiflood' : 'admin';
                }
                $this->insertHistory([
                    'scope' => 'device',
                    'target_user_id' => !empty($row['target_user_id']) ? (int) $row['target_user_id'] : null,
                    'target_username' => (string) ($row['target_username'] ?? ''),
                    'target_fingerprint' => $fingerprint,
                    'reason' => (string) ($row['reason'] ?? ''),
                    'source' => $source,
                    'banned_by' => (string) (($row['banned_by'] ?? '') ?: ($source === 'antiflood' ? 'antiflood_system' : 'legacy_admin')),
                    'started_at' => (string) (($row['created_at'] ?? '') ?: $this->dbNow()->format('Y-m-d H:i:s')),
                    'expires_at' => !empty($row['expires_at']) ? (string) $row['expires_at'] : null,
                    'status' => 'active',
                ]);
            }
        } catch (Throwable $e) {
            // non-blocking
        }

        try {
            $rows = $this->db->query("SELECT `ip_address`, `reason`, `banned_at`, `expires_at`, `source`, `banned_by`, `ref_hash` FROM `ip_blacklist` WHERE `expires_at` IS NULL OR `expires_at` > NOW()")->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                $ip = (string) ($row['ip_address'] ?? '');
                if ($ip === '' || $this->hasActiveHistory('ip', ['target_ip' => $ip])) {
                    continue;
                }
                $source = (string) ($row['source'] ?? '');
                if ($source === '') {
                    $source = 'antiflood';
                }
                $this->insertHistory([
                    'scope' => 'ip',
                    'target_ip' => $ip,
                    'reason' => (string) ($row['reason'] ?? ''),
                    'source' => $source,
                    'banned_by' => (string) (($row['banned_by'] ?? '') ?: ($source === 'antiflood' ? 'antiflood_system' : 'legacy_admin')),
                    'ref_hash' => (string) ($row['ref_hash'] ?? ''),
                    'started_at' => (string) (($row['banned_at'] ?? '') ?: $this->dbNow()->format('Y-m-d H:i:s')),
                    'expires_at' => !empty($row['expires_at']) ? (string) $row['expires_at'] : null,
                    'status' => 'active',
                ]);
            }
        } catch (Throwable $e) {
            // non-blocking
        }
    }

    private function insertHistory(array $data): void
    {
        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO `ban_history` (`" . implode('`, `', $columns) . "`) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
    }

    private function hasActiveHistory(string $scope, array $filters): bool
    {
        $where = ["`scope` = ?", "`status` = 'active'"];
        $params = [$scope];

        foreach ($filters as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $where[] = "`{$column}` = ?";
            $params[] = $value;
        }

        $stmt = $this->db->prepare("SELECT `id` FROM `ban_history` WHERE " . implode(' AND ', $where) . " LIMIT 1");
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    private function closeActiveHistory(string $scope, array $filters, string $status, string $endedBy): void
    {
        $where = ["`scope` = ?", "`status` = 'active'"];
        $params = [$scope];

        foreach ($filters as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $where[] = "`{$column}` = ?";
            $params[] = $value;
        }

        $sql = "UPDATE `ban_history` SET `status` = ?, `ended_at` = NOW(), `ended_by` = ? WHERE " . implode(' AND ', $where);
        array_unshift($params, $endedBy);
        array_unshift($params, $status);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    private function dbNow(): DateTimeImmutable
    {
        $timezone = function_exists('app_db_timezone') ? app_db_timezone() : date_default_timezone_get();
        return new DateTimeImmutable('now', new DateTimeZone($timezone));
    }
}

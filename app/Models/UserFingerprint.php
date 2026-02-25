<?php

/**
 * UserFingerprint Model
 * Represents the `user_fingerprints` table
 */
class UserFingerprint extends Model
{
    private array $columnExistsCache = [];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'user_fingerprints';
    }

    /**
     * Save a fingerprint record for a user login/register event.
     */
    public function saveFingerprint($userId, $username, $hash, $components, array $context = [])
    {
        $componentsArr = $this->normalizeComponents($components);
        $componentsJson = is_string($components) ? $components : json_encode($componentsArr, JSON_UNESCAPED_UNICODE);
        if (!is_string($componentsJson)) {
            $componentsJson = '{}';
        }

        $ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $acceptLanguage = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        $deviceId = trim((string) ($context['device_id'] ?? ($_POST['device_id'] ?? '')));
        $action = trim((string) ($context['action'] ?? ($_POST['signal_action'] ?? 'auth')));

        $timezone = $this->extractNestedString($componentsArr, ['environment', 'timezone']);
        $language = $this->extractNestedString($componentsArr, ['environment', 'language']);
        if ($language === '') {
            $language = $this->extractNestedString($componentsArr, ['environment', 'languages']);
        }
        $platform = $this->extractNestedString($componentsArr, ['hardware', 'platform']);
        $screenKey = $this->buildScreenKey($componentsArr);
        $ipPrefix = $this->deriveIpPrefix($ipAddress);
        $userAgentHash = hash('sha256', strtolower($userAgent));

        $risk = $this->computeRiskMeta([
            'user_id' => (int) $userId,
            'username' => (string) $username,
            'fingerprint_hash' => (string) $hash,
            'device_id' => $deviceId,
            'ip_prefix' => $ipPrefix,
            'user_agent_hash' => $userAgentHash,
        ]);

        $data = [
            'user_id' => (int) $userId,
            'username' => (string) $username,
            'fingerprint_hash' => (string) $hash,
            'components' => $componentsJson,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ];

        $optional = [
            'device_id' => $deviceId,
            'ip_prefix' => $ipPrefix,
            'user_agent_hash' => $userAgentHash,
            'accept_language' => $acceptLanguage,
            'timezone' => $timezone,
            'language' => $language,
            'platform' => $platform,
            'screen_key' => $screenKey,
            'action' => $action,
            'risk_score' => $risk['score'],
            'risk_flags' => json_encode($risk['flags'], JSON_UNESCAPED_UNICODE),
        ];

        foreach ($optional as $column => $value) {
            if ($this->hasColumn($column)) {
                $data[$column] = is_string($value) ? mb_substr($value, 0, 1000) : $value;
            }
        }

        $keys = array_keys($data);
        $fields = '`' . implode('`, `', $keys) . '`';
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        $sql = "INSERT INTO `{$this->table}` ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
    }

    /**
     * Get fingerprint history for a specific user.
     */
    public function getByUserId($userId, $limit = 20)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find users sharing the same fingerprint hash.
     */
    public function findByHash($hash)
    {
        $sql = "SELECT DISTINCT `username`, `user_id`, `ip_address`, `created_at` 
                FROM `{$this->table}` WHERE `fingerprint_hash` = ? ORDER BY `created_at` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$hash]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findLinkedAccountsByUserId(int $userId, int $days = 30, int $limit = 50): array
    {
        if ($userId <= 0) {
            return [];
        }
        $limit = max(1, min(200, $limit));
        $days = max(1, min(365, $days));

        $hasDeviceId = $this->hasColumn('device_id');
        $hasIpPrefix = $this->hasColumn('ip_prefix');
        $hasUaHash = $this->hasColumn('user_agent_hash');

        $conditions = ["f1.user_id = :uid", "f1.created_at >= (NOW() - INTERVAL {$days} DAY)"];
        $joinMatch = ["f2.user_id <> f1.user_id"];

        $signalCases = ["CASE WHEN f2.fingerprint_hash = f1.fingerprint_hash THEN 'fingerprint_hash' END"];
        $joinMatch[] = "(f2.fingerprint_hash <> '' AND f2.fingerprint_hash = f1.fingerprint_hash)";

        if ($hasDeviceId) {
            $signalCases[] = "CASE WHEN f2.device_id = f1.device_id AND f1.device_id <> '' THEN 'device_id' END";
        }
        if ($hasIpPrefix && $hasUaHash) {
            $signalCases[] = "CASE WHEN f2.ip_prefix = f1.ip_prefix AND f2.user_agent_hash = f1.user_agent_hash AND f1.ip_prefix <> '' AND f1.user_agent_hash <> '' THEN 'ip_prefix+ua' END";
        }

        $orLinks = ["(f2.fingerprint_hash <> '' AND f2.fingerprint_hash = f1.fingerprint_hash)"];
        if ($hasDeviceId) {
            $orLinks[] = "(f1.device_id <> '' AND f2.device_id = f1.device_id)";
        }
        if ($hasIpPrefix && $hasUaHash) {
            $orLinks[] = "(f1.ip_prefix <> '' AND f1.user_agent_hash <> '' AND f2.ip_prefix = f1.ip_prefix AND f2.user_agent_hash = f1.user_agent_hash)";
        }

        $sql = "
            SELECT 
                u.id AS linked_user_id,
                u.username AS linked_username,
                u.email AS linked_email,
                MAX(f2.created_at) AS last_seen,
                COUNT(*) AS matched_events
            FROM `{$this->table}` f1
            INNER JOIN `{$this->table}` f2 ON (" . implode(' OR ', $orLinks) . ")
            INNER JOIN `users` u ON u.id = f2.user_id
            WHERE " . implode(' AND ', $conditions) . "
            GROUP BY u.id, u.username, u.email
            ORDER BY matched_events DESC, last_seen DESC
            LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_values(array_filter($rows, static function ($row) use ($userId) {
            return (int) ($row['linked_user_id'] ?? 0) !== $userId;
        }));
    }

    private function normalizeComponents($components): array
    {
        if (is_array($components)) {
            return $components;
        }
        $raw = trim((string) $components);
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function extractNestedString(array $data, array $path): string
    {
        $cur = $data;
        foreach ($path as $key) {
            if (!is_array($cur) || !array_key_exists($key, $cur)) {
                return '';
            }
            $cur = $cur[$key];
        }
        return is_scalar($cur) ? trim((string) $cur) : '';
    }

    private function buildScreenKey(array $components): string
    {
        $screen = (array) ($components['screen'] ?? []);
        $w = (int) ($screen['width'] ?? 0);
        $h = (int) ($screen['height'] ?? 0);
        $dpr = (string) ($screen['devicePixelRatio'] ?? '');
        if ($w <= 0 || $h <= 0) {
            return '';
        }
        return $w . 'x' . $h . ($dpr !== '' ? '@' . $dpr : '');
    }

    private function deriveIpPrefix(string $ip): string
    {
        $ip = trim($ip);
        if ($ip === '') {
            return '';
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return count($parts) === 4 ? ($parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0/24') : '';
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $bin = @inet_pton($ip);
            if ($bin === false) {
                return '';
            }
            $hex = bin2hex($bin);
            return substr($hex, 0, 16) . '::/64';
        }
        return '';
    }

    private function computeRiskMeta(array $signals): array
    {
        $score = 0;
        $flags = [];
        $username = (string) ($signals['username'] ?? '');
        $userId = (int) ($signals['user_id'] ?? 0);
        $fingerprintHash = trim((string) ($signals['fingerprint_hash'] ?? ''));
        $deviceId = trim((string) ($signals['device_id'] ?? ''));
        $ipPrefix = trim((string) ($signals['ip_prefix'] ?? ''));
        $uaHash = trim((string) ($signals['user_agent_hash'] ?? ''));

        if ($fingerprintHash !== '') {
            $distinct = $this->countDistinctUsersBy('fingerprint_hash', $fingerprintHash, $userId, $username);
            if ($distinct >= 3) {
                $score += 10;
                $flags[] = 'shared_fingerprint_24h:' . $distinct;
            }
        }

        if ($deviceId !== '' && $this->hasColumn('device_id')) {
            $distinct = $this->countDistinctUsersBy('device_id', $deviceId, $userId, $username);
            if ($distinct >= 2) {
                $score += ($distinct >= 4 ? 30 : 20);
                $flags[] = 'shared_device_id_24h:' . $distinct;
            }
        }

        if ($ipPrefix !== '' && $uaHash !== '' && $this->hasColumn('ip_prefix') && $this->hasColumn('user_agent_hash')) {
            $distinct = $this->countDistinctUsersByIpPrefixAndUa($ipPrefix, $uaHash, $userId, $username);
            if ($distinct >= 2) {
                $score += ($distinct >= 4 ? 25 : 15);
                $flags[] = 'shared_ip_prefix_ua_24h:' . $distinct;
            }
        }

        return [
            'score' => min(100, $score),
            'flags' => $flags,
        ];
    }

    private function countDistinctUsersBy(string $column, string $value, int $userId, string $username): int
    {
        if (!$this->hasColumn($column) || $value === '') {
            return 0;
        }
        $sql = "
            SELECT COUNT(DISTINCT user_id) AS c
            FROM `{$this->table}`
            WHERE `{$column}` = ?
              AND `created_at` >= (NOW() - INTERVAL 1 DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        $count = (int) $stmt->fetchColumn();
        return $count;
    }

    private function countDistinctUsersByIpPrefixAndUa(string $ipPrefix, string $uaHash, int $userId, string $username): int
    {
        $sql = "
            SELECT COUNT(DISTINCT user_id) AS c
            FROM `{$this->table}`
            WHERE `ip_prefix` = ?
              AND `user_agent_hash` = ?
              AND `created_at` >= (NOW() - INTERVAL 1 DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ipPrefix, $uaHash]);
        return (int) $stmt->fetchColumn();
    }

    private function hasColumn(string $column): bool
    {
        if (isset($this->columnExistsCache[$column])) {
            return $this->columnExistsCache[$column];
        }
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            $stmt->execute([$this->table, $column]);
            $this->columnExistsCache[$column] = ((int) $stmt->fetchColumn()) > 0;
        } catch (Throwable $e) {
            $this->columnExistsCache[$column] = false;
        }
        return $this->columnExistsCache[$column];
    }
}

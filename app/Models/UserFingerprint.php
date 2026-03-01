<?php

/**
 * UserFingerprint Model
 * Represents the `user_fingerprints` table
 */
class UserFingerprint extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'user_fingerprints';
    }

    /**
     * Save a fingerprint record for a user login/register event.
     */
    public function saveFingerprint($userId, $username, $hash, $components)
    {
        $sql = "INSERT INTO `{$this->table}` 
                (`user_id`, `username`, `fingerprint_hash`, `components`, `ip_address`, `user_agent`)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $username,
            $hash,
            is_string($components) ? $components : json_encode($components, JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
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
     * Get the latest fingerprint record for a specific user.
     */
    public function getLatestByUserId($userId): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get latest fingerprint row for each user ID.
     *
     * @param array<int,int|string> $userIds
     * @return array<int,array<string,mixed>>
     */
    public function getLatestMapByUserIds(array $userIds): array
    {
        $userIds = array_values(array_filter(array_map('intval', $userIds), static fn($id) => $id > 0));
        if ($userIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = "SELECT uf.*
                FROM `{$this->table}` uf
                INNER JOIN (
                    SELECT `user_id`, MAX(`id`) AS `latest_id`
                    FROM `{$this->table}`
                    WHERE `user_id` IN ({$placeholders})
                    GROUP BY `user_id`
                ) latest ON latest.`latest_id` = uf.`id`";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($userIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapped = [];
        foreach ($rows as $row) {
            $mapped[(int) ($row['user_id'] ?? 0)] = $row;
        }

        return $mapped;
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
}

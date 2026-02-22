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

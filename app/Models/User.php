<?php

/**
 * User Model
 * Handles user database operations
 */
class User extends Model {
    protected $table = 'users';
    
    /**
     * Find user by username
     * @param string $username
     * @return array|null
     */
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `username` = ? LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Find user by session token
     * @param string $session
     * @return array|null
     */
    public function findBySession($session) {
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `session` = ? LIMIT 1");
        $stmt->execute([$session]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Check if email exists
     * @param string $email
     * @param int|null $excludeUserId
     * @return bool
     */
    public function emailExists($email, $excludeUserId = null) {
        $sql = "SELECT COUNT(*) FROM `users` WHERE `email` = ?";
        $params = [$email];
        
        if ($excludeUserId) {
            $sql .= " AND `id` != ?";
            $params[] = $excludeUserId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Update user email
     * @param int $userId
     * @param string $email
     * @return bool
     */
    public function updateEmail($userId, $email) {
        return $this->update($userId, ['email' => $email]);
    }
}

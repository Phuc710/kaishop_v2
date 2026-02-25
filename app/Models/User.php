<?php

/**
 * User Model
 * Handles user database operations
 */
class User extends Model
{
    protected $table = 'users';

    /**
     * Count total users
     * @return int
     */
    public function count()
    {
        return (int) $this->query("SELECT COUNT(*) FROM `{$this->table}`")->fetchColumn();
    }

    /**
     * Find user by username
     * @param string $username
     * @return array|null
     */
    public function findByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `username` = ? LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Find user by email
     * @param string $email
     * @return array|null
     */
    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `email` = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Find user by ID
     * @param int $id
     * @return array|null
     */
    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Find user by username or email
     * @param string $usernameOrEmail
     * @return array|null
     */
    public function findByUsernameOrEmail($usernameOrEmail)
    {
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Find user by OTP code
     * @param string $otpcode
     * @return array|null
     */
    public function findByOtpcode($otpcode)
    {
        if (empty($otpcode))
            return null;
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `otpcode` = ? LIMIT 1");
        $stmt->execute([$otpcode]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Find user by session token
     * @param string $session
     * @return array|null
     */
    public function findBySession($session)
    {
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
    public function emailExists($email, $excludeUserId = null)
    {
        if ($excludeUserId) {
            $result = $this->query(
                "SELECT COUNT(*) FROM `{$this->table}` WHERE `email` = ? AND `id` != ?",
                [$email, $excludeUserId]
            )->fetchColumn();
        } else {
            $result = $this->query(
                "SELECT COUNT(*) FROM `{$this->table}` WHERE `email` = ?",
                [$email]
            )->fetchColumn();
        }

        return $result > 0;
    }

    /**
     * Update user email
     * @param int $userId
     * @param string $email
     * @return bool
     */
    public function updateEmail($userId, $email)
    {
        return $this->update($userId, ['email' => $email]);
    }
}

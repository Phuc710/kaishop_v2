<?php

/**
 * PendingDeposit Model
 * Handles pending bank deposit transactions.
 */
class PendingDeposit extends Model
{
    protected $table = 'pending_deposits';

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
    public function createDeposit(int $userId, string $username, int $amount, int $bonusPercent = 0)
    {
        // Cancel any existing pending deposits for this user
        $this->cancelAllPendingByUser($userId);

        // Expire old ones globally
        $this->markExpired();

        $code = $this->generateCode();
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO `{$this->table}` 
            (`user_id`, `username`, `deposit_code`, `amount`, `bonus_percent`, `status`, `created_at`)
            VALUES (:uid, :uname, :code, :amount, :bonus, 'pending', :now)
        ");

        $result = $stmt->execute([
            'uid' => $userId,
            'uname' => $username,
            'code' => $code,
            'amount' => $amount,
            'bonus' => $bonusPercent,
            'now' => $now,
        ]);

        if (!$result) {
            return false;
        }

        $expiresAt = date('Y-m-d H:i:s', strtotime($now) + 300); // 5 minutes

        return [
            'id' => (int) $this->db->lastInsertId(),
            'deposit_code' => $code,
            'expires_at' => $expiresAt,
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
        $stmt = $this->db->prepare("
            UPDATE `{$this->table}` 
            SET `status` = 'completed', `sepay_transaction_id` = :sid, `completed_at` = NOW()
            WHERE `id` = :id AND `status` = 'pending'
        ");
        return $stmt->execute(['id' => $id, 'sid' => $sepayTransId]);
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
    public function markExpired(): void
    {
        $stmt = $this->db->prepare("
            UPDATE `{$this->table}` SET `status` = 'expired'
            WHERE `status` = 'pending' AND `created_at` < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $stmt->execute();
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
}

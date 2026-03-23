<?php

/**
 * ChatGptAllowedInvite Model
 * The source of truth for valid invites — only entries here are considered authorized
 */
class ChatGptAllowedInvite extends Model
{
    protected $table = 'chatgpt_allowed_invites';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create an allowed invite record (named createInvite to avoid clash with Model::create)
     */
    public function createInvite($orderId, $farmId, $email, $inviteId = null)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}`
             (`order_id`, `farm_id`, `target_email`, `invite_id`, `status`, `created_by`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, 'pending', 'system', NOW(), NOW())"
        );
        $stmt->execute([$orderId, $farmId, strtolower(trim($email)), $inviteId]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update invite_id (call after API returns the real invite ID)
     */
    public function setInviteId($id, $inviteId)
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET `invite_id` = ?, `updated_at` = NOW() WHERE `id` = ? LIMIT 1"
        );
        $stmt->execute([$inviteId, $id]);
    }

    /**
     * Check if an email is in the allowed invites for a farm
     */
    public function isAllowed($farmId, $email)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$this->table}`
             WHERE `farm_id` = ? AND LOWER(`target_email`) = ? AND `status` IN ('pending','accepted')"
        );
        $stmt->execute([$farmId, strtolower(trim($email))]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Check if an OpenAI invite_id is known/allowed
     */
    public function isAllowedByInviteId($farmId, $inviteId)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$this->table}`
             WHERE `farm_id` = ? AND `invite_id` = ? AND `status` IN ('pending','accepted')"
        );
        $stmt->execute([$farmId, $inviteId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Get all valid (pending/accepted) emails for a farm — used by cron for cross-check
     */
    public function getAllowedEmailsForFarm($farmId)
    {
        $stmt = $this->db->prepare(
            "SELECT `target_email`, `invite_id`, `status`, `id`
             FROM `{$this->table}`
             WHERE `farm_id` = ? AND `status` IN ('pending','accepted')"
        );
        $stmt->execute([$farmId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get all valid invite_ids for a farm
     */
    public function getAllowedInviteIdsForFarm($farmId)
    {
        $stmt = $this->db->prepare(
            "SELECT `invite_id` FROM `{$this->table}`
             WHERE `farm_id` = ? AND `invite_id` IS NOT NULL AND `status` IN ('pending','accepted')"
        );
        $stmt->execute([$farmId]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'invite_id');
    }

    /**
     * Update status of an allowed invite
     */
    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ? LIMIT 1"
        );
        $stmt->execute([$status, $id]);
    }

    /**
     * Mark invite as accepted by email (when cron detects user joined)
     */
    public function markAcceptedByEmail($farmId, $email)
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}`
             SET `status` = 'accepted', `updated_at` = NOW()
             WHERE `farm_id` = ? AND LOWER(`target_email`) = ? AND `status` = 'pending'"
        );
        $stmt->execute([$farmId, strtolower(trim($email))]);
    }

    /**
     * Mark invite as revoked (when cron or admin revokes it)
     */
    public function markRevokedByInviteId($inviteId, $status = 'revoked')
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET `status` = ?, `updated_at` = NOW()
             WHERE `invite_id` = ?"
        );
        $stmt->execute([$status, $inviteId]);
    }

    public function markExpiredByOrder($orderId)
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}`
             SET `status` = 'expired', `updated_at` = NOW()
             WHERE `order_id` = ? AND `status` IN ('pending', 'accepted')"
        );
        $stmt->execute([(int) $orderId]);
    }

    public function getOpenInvitesByOrder($orderId)
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM `{$this->table}`
             WHERE `order_id` = ? AND `status` IN ('pending', 'accepted')
             ORDER BY `id` DESC"
        );
        $stmt->execute([(int) $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getSnapshotResolutionMap($farmId)
    {
        $stmt = $this->db->prepare(
            "SELECT `invite_id`, `target_email`, `status`
             FROM `{$this->table}`
             WHERE `farm_id` = ?"
        );
        $stmt->execute([(int) $farmId]);

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $inviteId = trim((string) ($row['invite_id'] ?? ''));
            $email = strtolower(trim((string) ($row['target_email'] ?? '')));
            $status = trim((string) ($row['status'] ?? ''));
            if ($inviteId !== '') {
                $map[$inviteId] = $status;
            }
            if ($email !== '' && !isset($map[$email])) {
                $map[$email] = $status;
            }
        }

        return $map;
    }

    public function getByFarm($farmId, $limit = 100)
    {
        $stmt = $this->db->prepare(
            "SELECT ai.*, o.customer_email as order_email, o.order_code
             FROM `{$this->table}` ai
             LEFT JOIN `chatgpt_orders` o ON o.id = ai.order_id
             WHERE ai.`farm_id` = ?
             ORDER BY ai.`created_at` DESC
             LIMIT {$limit}"
        );
        $stmt->execute([$farmId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get all allowed invites across all farms for admin panel
     */
    public function getAll($filters = [], $limit = 200)
    {
        $where = [];
        $params = [];

        if (!empty($filters['farm_id'])) {
            $where[] = 'ai.`farm_id` = ?';
            $params[] = (int) $filters['farm_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'ai.`status` = ?';
            $params[] = $filters['status'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare(
            "SELECT ai.*, f.farm_name, o.order_code
             FROM `{$this->table}` ai
             LEFT JOIN `chatgpt_farms` f ON f.id = ai.farm_id
             LEFT JOIN `chatgpt_orders` o ON o.id = ai.order_id
             {$whereSql}
             ORDER BY ai.`created_at` DESC
             LIMIT {$limit}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

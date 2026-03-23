<?php

/**
 * ChatGptSnapshot Model
 * Manages member and invite snapshot data for each farm
 * These are the "real-time" state records synced by the cron guard
 */
class ChatGptSnapshot extends Model
{
    protected $table = 'chatgpt_farm_members_snapshot'; // default

    public function __construct()
    {
        parent::__construct();
    }

    // ==================== MEMBERS ====================

    /**
     * Upsert a member in the snapshot
     * @param string $source 'approved' | 'detected_unknown'
     */
    public function upsertMember($farmId, $openaiUserId, $email, $role, $source = 'detected_unknown')
    {
        $nowSql = $this->nowSql();
        $stmt = $this->db->prepare(
            "INSERT INTO `chatgpt_farm_members_snapshot`
             (`farm_id`, `openai_user_id`, `email`, `role`, `status`, `source`, `first_seen_at`, `last_seen_at`)
             VALUES (?, ?, ?, ?, 'active', ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               `openai_user_id` = VALUES(`openai_user_id`),
               `role` = VALUES(`role`),
               `status` = 'active',
               `source` = IF(`source` = 'approved', 'approved', VALUES(`source`)),
               `last_seen_at` = VALUES(`last_seen_at`)"
        );
        $stmt->execute([$farmId, $openaiUserId, strtolower(trim($email)), $role, $source, $nowSql, $nowSql]);
    }

    /**
     * Get all members snapshot for a farm
     */
    public function getMembersForFarm($farmId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `chatgpt_farm_members_snapshot` WHERE `farm_id` = ? ORDER BY `first_seen_at` ASC"
        );
        $stmt->execute([$farmId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get all members for admin panel (all farms)
     */
    public function getAllMembers($filters = [])
    {
        $where = [];
        $params = [];

        if (!empty($filters['farm_id'])) {
            $where[] = 'm.`farm_id` = ?';
            $params[] = (int) $filters['farm_id'];
        }
        if (!empty($filters['source'])) {
            $where[] = 'm.`source` = ?';
            $params[] = $filters['source'];
        }
        if (!empty($filters['email'])) {
            $where[] = 'm.`email` LIKE ?';
            $params[] = '%' . $filters['email'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'm.`first_seen_at` >= ?';
            $params[] = $this->normalizeDateBoundary($filters['date_from'], false);
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'm.`first_seen_at` <= ?';
            $params[] = $this->normalizeDateBoundary($filters['date_to'], true);
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare(
            "SELECT m.*, f.farm_name,
                    (SELECT order_code FROM chatgpt_orders WHERE LOWER(customer_email) = LOWER(m.email) AND assigned_farm_id = m.farm_id ORDER BY created_at DESC LIMIT 1) as linked_order_code,
                    (SELECT id FROM chatgpt_orders WHERE LOWER(customer_email) = LOWER(m.email) AND assigned_farm_id = m.farm_id ORDER BY created_at DESC LIMIT 1) as linked_order_id
             FROM `chatgpt_farm_members_snapshot` m
             LEFT JOIN `chatgpt_farms` f ON f.id = m.farm_id
             {$whereSql}
             ORDER BY m.`last_seen_at` DESC
             LIMIT 500"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Mark member as gone (removed from farm)
     */
    public function markMemberGone($farmId, $email)
    {
        $nowSql = $this->nowSql();
        $stmt = $this->db->prepare(
            "UPDATE `chatgpt_farm_members_snapshot`
             SET `status` = 'removed', `last_seen_at` = ?
             WHERE `farm_id` = ? AND `email` = ?"
        );
        $stmt->execute([$nowSql, $farmId, strtolower(trim($email))]);
    }

    /**
     * Mark member as approved (has valid allowed_invite)
     */
    public function markMemberApproved($farmId, $email)
    {
        $nowSql = $this->nowSql();
        $stmt = $this->db->prepare(
            "UPDATE `chatgpt_farm_members_snapshot`
             SET `source` = 'approved', `last_seen_at` = ?
             WHERE `farm_id` = ? AND LOWER(`email`) = ?"
        );
        $stmt->execute([$nowSql, $farmId, strtolower(trim($email))]);
    }

    // ==================== INVITES ====================

    /**
     * Upsert an invite in the snapshot
     */
    public function upsertInvite($farmId, $inviteId, $email, $status, $source = 'detected_unknown', $role = 'reader')
    {
        $nowSql = $this->nowSql();
        $stmt = $this->db->prepare(
            "INSERT INTO `chatgpt_farm_invites_snapshot`
             (`farm_id`, `invite_id`, `email`, `role`, `status`, `source`, `first_seen_at`, `last_seen_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               `email` = VALUES(`email`),
               `role` = VALUES(`role`),
               `status` = VALUES(`status`),
               `source` = IF(`source` = 'approved', 'approved', VALUES(`source`)),
               `last_seen_at` = VALUES(`last_seen_at`)"
        );
        $stmt->execute([$farmId, $inviteId, strtolower(trim($email)), $role, $status, $source, $nowSql, $nowSql]);
    }

    /**
     * Get all invites snapshot for a farm
     */
    public function getInvitesForFarm($farmId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `chatgpt_farm_invites_snapshot` WHERE `farm_id` = ? ORDER BY `first_seen_at` DESC"
        );
        $stmt->execute([$farmId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get all invites for admin panel
     */
    public function getAllInvites($filters = [])
    {
        $where = [];
        $params = [];

        if (!empty($filters['farm_id'])) {
            $where[] = 'i.`farm_id` = ?';
            $params[] = (int) $filters['farm_id'];
        }
        if (!empty($filters['source'])) {
            $where[] = 'i.`source` = ?';
            $params[] = $filters['source'];
        }
        if (!empty($filters['email'])) {
            $where[] = 'i.`email` LIKE ?';
            $params[] = '%' . $filters['email'] . '%';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare(
            "SELECT i.*, f.farm_name
             FROM `chatgpt_farm_invites_snapshot` i
             LEFT JOIN `chatgpt_farms` f ON f.id = i.farm_id
             {$whereSql}
             ORDER BY i.`last_seen_at` DESC
             LIMIT 500"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Mark invite as gone (revoked)
     */
    public function markInviteGone($farmId, $inviteId)
    {
        $nowSql = $this->nowSql();
        $stmt = $this->db->prepare(
            "UPDATE `chatgpt_farm_invites_snapshot`
             SET `status` = 'revoked', `last_seen_at` = ?
             WHERE `farm_id` = ? AND `invite_id` = ?"
        );
        $stmt->execute([$nowSql, $farmId, $inviteId]);
    }

    public function markMissingMembers($farmId, $liveEmails)
    {
        $liveEmails = array_values(array_filter(array_map(function ($email) {
            return strtolower(trim((string) $email));
        }, (array) $liveEmails)));
        $nowSql = $this->nowSql();

        if (empty($liveEmails)) {
            $stmt = $this->db->prepare(
                "UPDATE `chatgpt_farm_members_snapshot`
                 SET `status` = 'removed', `last_seen_at` = ?
                 WHERE `farm_id` = ? AND `status` = 'active'"
            );
            $stmt->execute([$nowSql, (int) $farmId]);
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($liveEmails), '?'));
        $params = array_merge([(int) $farmId], $liveEmails);
        $stmt = $this->db->prepare(
            "UPDATE `chatgpt_farm_members_snapshot`
             SET `status` = 'removed', `last_seen_at` = ?
             WHERE `farm_id` = ? AND `status` = 'active' AND `email` NOT IN ({$placeholders})"
        );
        array_unshift($params, $nowSql);
        $stmt->execute($params);
    }

    public function markMissingInvites($farmId, $liveInviteIds, $resolutionMap = [])
    {
        $currentRows = $this->getInvitesForFarm($farmId);
        $liveInviteIds = array_fill_keys(array_values(array_filter(array_map('strval', (array) $liveInviteIds))), true);

        foreach ($currentRows as $row) {
            $inviteId = trim((string) ($row['invite_id'] ?? ''));
            if ($inviteId === '' || isset($liveInviteIds[$inviteId])) {
                continue;
            }

            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $resolvedStatus = $resolutionMap[$inviteId] ?? $resolutionMap[$email] ?? 'gone';
            $nowSql = $this->nowSql();
            $stmt = $this->db->prepare(
                "UPDATE `chatgpt_farm_invites_snapshot`
                 SET `status` = ?, `last_seen_at` = ?
                 WHERE `farm_id` = ? AND `invite_id` = ?"
            );
            $stmt->execute([$resolvedStatus, $nowSql, (int) $farmId, $inviteId]);
        }
    }

    public function getMemberById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `chatgpt_farm_members_snapshot` WHERE `id` = ? LIMIT 1"
        );
        $stmt->execute([(int) $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getInviteById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `chatgpt_farm_invites_snapshot` WHERE `id` = ? LIMIT 1"
        );
        $stmt->execute([(int) $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function nowSql(): string
    {
        if ($this->timeService) {
            return $this->timeService->nowSql($this->timeService->getDbTimezone());
        }

        return date('Y-m-d H:i:s');
    }

    private function normalizeDateBoundary($date, bool $endOfDay): string
    {
        $raw = trim((string) $date);
        $boundary = $raw . ($endOfDay ? ' 23:59:59' : ' 00:00:00');

        if ($this->timeService) {
            return $this->timeService->formatDb(
                $boundary,
                'Y-m-d H:i:s',
                $this->timeService->getDisplayTimezone()
            );
        }

        return $boundary;
    }
}

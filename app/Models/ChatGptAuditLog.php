<?php

/**
 * ChatGptAuditLog Model
 * Persistent log of all farm actions
 */
class ChatGptAuditLog extends Model
{
    protected $table = 'chatgpt_audit_logs';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Write an audit log entry
     *
     * @param array $data Keys: farm_id, farm_name, action, actor_email, target_email, result, reason, meta
     */
    public function log($data)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO `{$this->table}`
                 (`farm_id`, `farm_name`, `action`, `actor_email`, `target_email`, `result`, `reason`, `meta_json`, `created_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $data['farm_id'] ?? null,
                $data['farm_name'] ?? null,
                $data['action'] ?? 'UNKNOWN',
                $data['actor_email'] ?? 'system',
                $data['target_email'] ?? null,
                $data['result'] ?? 'OK',
                $data['reason'] ?? null,
                isset($data['meta']) ? json_encode($data['meta'], JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (Throwable $e) {
            // Non-blocking: log failure should not break the main flow
            error_log('[ChatGptAuditLog] Failed to write log: ' . $e->getMessage());
        }
    }

    /**
     * Get logs for admin panel
     */
    public function getAll($filters = [], $limit = 200)
    {
        $where = [];
        $params = [];

        if (!empty($filters['farm_id'])) {
            $where[] = '`farm_id` = ?';
            $params[] = (int) $filters['farm_id'];
        }
        if (!empty($filters['action'])) {
            $where[] = '`action` = ?';
            $params[] = $filters['action'];
        }
        if (!empty($filters['target_email'])) {
            $where[] = '`target_email` LIKE ?';
            $params[] = '%' . $filters['target_email'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = '`created_at` >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = '`created_at` <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit = max(1, min(1000, $limit));

        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` {$whereSql}
             ORDER BY `created_at` DESC LIMIT {$limit}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get logs for a specific farm
     */
    public function getForFarm($farmId, $limit = 100)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE `farm_id` = ?
             ORDER BY `created_at` DESC LIMIT {$limit}"
        );
        $stmt->execute([$farmId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get distinct action types for filter dropdown
     */
    public function getActionTypes()
    {
        $predefined = [
            'ORDER_ACTIVATED',
            'ORDER_EXPIRED',
            'FARM_ADDED',
            'FARM_UPDATED',
            'FARM_SYNCED',
            'SYSTEM_INVITE_CREATED',
            'SYSTEM_INVITE_FAILED',
            'INVITE_REVOKED_UNAUTHORIZED',
            'MEMBER_REMOVED_UNAUTHORIZED',
            'MEMBER_REMOVED_POLICY',
            'MEMBER_UPSERTED',
            'INVITE_UPSERTED'
        ];

        $stmt = $this->db->query("SELECT DISTINCT `action` FROM `{$this->table}` ORDER BY `action` ASC");
        $dynamic = array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'action');

        $all = array_unique(array_merge($predefined, $dynamic));
        sort($all);
        return $all;
    }
}

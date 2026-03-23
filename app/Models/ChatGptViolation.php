<?php

/**
 * ChatGptViolation Model
 * Stores policy violations detected by the GPT Business guard.
 */
class ChatGptViolation extends Model
{
    protected $table = 'chatgpt_violations';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create a violation record.
     */
    public function createViolation($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}`
             (`farm_id`, `email`, `type`, `severity`, `reason`, `action_taken`, `created_at`)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            (int) ($data['farm_id'] ?? 0),
            strtolower(trim((string) ($data['email'] ?? ''))),
            (string) ($data['type'] ?? 'unknown_violation'),
            (string) ($data['severity'] ?? 'high'),
            $data['reason'] ?? null,
            $data['action_taken'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * List violations for admin page.
     */
    public function getAll($filters = [], $limit = 200)
    {
        $where = [];
        $params = [];

        if (!empty($filters['farm_id'])) {
            $where[] = 'v.`farm_id` = ?';
            $params[] = (int) $filters['farm_id'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'v.`type` = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['email'])) {
            $where[] = 'v.`email` LIKE ?';
            $params[] = '%' . strtolower(trim((string) $filters['email'])) . '%';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit = max(1, min(1000, (int) $limit));

        $stmt = $this->db->prepare(
            "SELECT v.*, f.`farm_name`
             FROM `{$this->table}` v
             LEFT JOIN `chatgpt_farms` f ON f.`id` = v.`farm_id`
             {$whereSql}
             ORDER BY v.`created_at` DESC
             LIMIT {$limit}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getStats()
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN `severity` = 'critical' THEN 1 ELSE 0 END) AS critical_count,
                SUM(CASE WHEN `severity` = 'high' THEN 1 ELSE 0 END) AS high_count,
                SUM(CASE WHEN `severity` = 'medium' THEN 1 ELSE 0 END) AS medium_count,
                SUM(CASE WHEN `severity` = 'low' THEN 1 ELSE 0 END) AS low_count
             FROM `{$this->table}`"
        );
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTypes()
    {
        $predefined = [
            'unauthorized_invite',
            'unauthorized_member',
            'self_invite_violation',
            'expired_access',
            'manual_policy_action',
        ];

        $stmt = $this->db->query("SELECT DISTINCT `type` FROM `{$this->table}` ORDER BY `type` ASC");
        $dynamic = array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'type');
        $types = array_unique(array_merge($predefined, $dynamic));
        sort($types);
        return $types;
    }
}

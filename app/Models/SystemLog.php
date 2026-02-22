<?php

/**
 * System Log Model
 * Represents the `system_logs` table
 */
class SystemLog extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'system_logs';
    }

    /**
     * Get logs with pagination and filters
     */
    public function getLogs($filters, $limit, $offset)
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $conditions[] = '(username LIKE ? OR module LIKE ? OR action LIKE ? OR description LIKE ? OR ip_address LIKE ?)';
            $params = array_merge($params, [$search, $search, $search, $search, $search]);
        }

        if (!empty($filters['severity'])) {
            $conditions[] = 'severity = ?';
            $params[] = $filters['severity'];
        }

        if (!empty($filters['module'])) {
            $conditions[] = 'module = ?';
            $params[] = $filters['module'];
        }

        $where = implode(' AND ', $conditions);
        $sql = "SELECT * FROM `{$this->table}` WHERE {$where} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total logs for pagination
     */
    public function countLogs($filters)
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $conditions[] = '(username LIKE ? OR module LIKE ? OR action LIKE ? OR description LIKE ? OR ip_address LIKE ?)';
            $params = array_merge($params, [$search, $search, $search, $search, $search]);
        }

        if (!empty($filters['severity'])) {
            $conditions[] = 'severity = ?';
            $params[] = $filters['severity'];
        }

        if (!empty($filters['module'])) {
            $conditions[] = 'module = ?';
            $params[] = $filters['module'];
        }

        $where = implode(' AND ', $conditions);
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` WHERE {$where}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}

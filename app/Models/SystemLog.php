<?php

/**
 * System Log Model
 * Represents the `system_logs` table
 */
class SystemLog extends Model
{
    protected ?TimeService $timeService = null;

    public function __construct()
    {
        parent::__construct();
        $this->table = 'system_logs';
        $this->timeService = class_exists('TimeService') ? TimeService::instance() : null;
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

    /**
     * Get logs for unified journal DataTables
     */
    public function getLogsForJournal(array $filters): array
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            // Search in basic fields OR inside the JSON payload (which contains fingerprint/device/username config)
            $conditions[] = '(username LIKE :search_user OR module LIKE :search_mod OR action LIKE :search_act OR description LIKE :search_desc OR ip_address LIKE :search_ip OR payload LIKE :search_payload)';
            $params['search_user'] = $search;
            $params['search_mod'] = $search;
            $params['search_act'] = $search;
            $params['search_desc'] = $search;
            $params['search_ip'] = $search;
            $params['search_payload'] = $search;
        }

        if (!empty($filters['severity']) && $filters['severity'] !== 'all') {
            $conditions[] = 'severity = :f_severity';
            $params['f_severity'] = $filters['severity'];
        }

        $this->appendDateConditions($conditions, $params, $filters);

        $rangeDate = trim((string) ($filters['time_range'] ?? ''));
        if ($rangeDate !== '') {
            $rangeParts = explode(' - ', $rangeDate);
            if (count($rangeParts) === 2) {
                $sDate = DateTime::createFromFormat('Y-m-d', trim($rangeParts[0]));
                $eDate = DateTime::createFromFormat('Y-m-d', trim($rangeParts[1]));
                if ($sDate && $eDate) {
                    $conditions[] = "created_at >= :rng_start";
                    $conditions[] = "created_at <= :rng_end";
                    $params['rng_start'] = $sDate->format('Y-m-d 00:00:00');
                    $params['rng_end'] = $eDate->format('Y-m-d 23:59:59');
                }
            } else {
                $date = DateTime::createFromFormat('Y-m-d', $rangeDate);
                if ($date instanceof DateTime) {
                    $conditions[] = "created_at BETWEEN :range_s AND :range_e";
                    $params['range_s'] = $date->format('Y-m-d 00:00:00');
                    $params['range_e'] = $date->format('Y-m-d 23:59:59');
                }
            }
        }

        $where = implode(' AND ', $conditions);
        $sql = "SELECT * FROM `{$this->table}` WHERE {$where} ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<int, string> $conditions
     * @param array<string, mixed> $params
     * @param array<string, mixed> $filters
     */
    private function appendDateConditions(array &$conditions, array &$params, array $filters): void
    {
        $dateFilter = (string) ($filters['date_filter'] ?? 'all');

        if ($this->timeService) {
            $now = $this->timeService->nowDateTime($this->timeService->getDbTimezone());
            $todayStart = $now->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $last7Days = $now->modify('-7 days')->format('Y-m-d H:i:s');
            $last30Days = $now->modify('-30 days')->format('Y-m-d H:i:s');
        } else {
            $todayStart = date('Y-m-d 00:00:00');
            $last7Days = date('Y-m-d H:i:s', strtotime('-7 days'));
            $last30Days = date('Y-m-d H:i:s', strtotime('-30 days'));
        }

        if ($dateFilter === 'today') {
            $conditions[] = "created_at >= :df_today";
            $params['df_today'] = $todayStart;
        } elseif ($dateFilter === '7days') {
            $conditions[] = "created_at >= :df_7days";
            $params['df_7days'] = $last7Days;
        } elseif ($dateFilter === '30days') {
            $conditions[] = "created_at >= :df_30days";
            $params['df_30days'] = $last30Days;
        }
    }
}

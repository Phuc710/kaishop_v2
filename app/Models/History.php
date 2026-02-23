<?php

/**
 * History Model
 * Manages user balance history (lich_su_hoat_dong table)
 */
class History extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'lich_su_hoat_dong';
    }

    /**
     * Get user history with filtering and pagination
     */
    public function getUserHistory($username, $filters, $limit, $offset)
    {
        $conditions = ['username = ?'];
        $params = [$username];

        // Search by reason
        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $conditions[] = 'hoatdong LIKE ?';
            $params[] = $search;
        }

        // Filter by date range
        $rangeDate = trim((string) ($filters['time_range'] ?? ''));
        if ($rangeDate !== '') {
            $rangeParts = explode(' to ', $rangeDate); // Flatpickr default ranges usually use ' to '
            if (count($rangeParts) === 2) {
                $sDate = DateTime::createFromFormat('Y-m-d', trim($rangeParts[0]));
                $eDate = DateTime::createFromFormat('Y-m-d', trim($rangeParts[1]));
                if ($sDate && $eDate) {
                    $conditions[] = "(created_at >= ? OR STR_TO_DATE(time, '%d-%m-%Y %H:%i:%s') >= ?)";
                    $conditions[] = "(created_at <= ? OR STR_TO_DATE(time, '%d-%m-%Y %H:%i:%s') <= ?)";
                    $sStr = $sDate->format('Y-m-d 00:00:00');
                    $eStr = $eDate->format('Y-m-d 23:59:59');
                    array_push($params, $sStr, $sStr, $eStr, $eStr);
                }
            } else {
                $date = DateTime::createFromFormat('Y-m-d', $rangeDate);
                if ($date instanceof DateTime) {
                    $conditions[] = "(created_at BETWEEN ? AND ? OR STR_TO_DATE(time, '%d-%m-%Y %H:%i:%s') BETWEEN ? AND ?)";
                    $startStr = $date->format('Y-m-d 00:00:00');
                    $endStr = $date->format('Y-m-d 23:59:59');
                    array_push($params, $startStr, $endStr, $startStr, $endStr);
                }
            }
        }

        // Apply sort_date logic
        if (!empty($filters['sort_date']) && $filters['sort_date'] !== 'all') {
            if ($filters['sort_date'] === 'today') {
                $conditions[] = "(DATE(created_at) = CURDATE() OR STR_TO_DATE(time, '%d-%m-%Y') = CURDATE())";
            } elseif ($filters['sort_date'] === '7') {
                $conditions[] = "(created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) OR STR_TO_DATE(time, '%d-%m-%Y') >= DATE_SUB(CURDATE(), INTERVAL 7 DAY))";
            } elseif ($filters['sort_date'] === '30') {
                $conditions[] = "(created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) OR STR_TO_DATE(time, '%d-%m-%Y') >= DATE_SUB(CURDATE(), INTERVAL 30 DAY))";
            }
        }

        $where = implode(' AND ', $conditions);
        $sql = "SELECT id, hoatdong, gia, time, created_at FROM `{$this->table}` WHERE {$where} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total user history rows
     */
    public function countUserHistory($username, $filters)
    {
        $conditions = ['username = ?'];
        $params = [$username];

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $conditions[] = 'hoatdong LIKE ?';
            $params[] = $search;
        }

        $rangeDate = trim((string) ($filters['time_range'] ?? ''));
        if ($rangeDate !== '') {
            $rangeParts = explode(' to ', $rangeDate);
            if (count($rangeParts) === 2) {
                $sDate = DateTime::createFromFormat('Y-m-d', trim($rangeParts[0]));
                $eDate = DateTime::createFromFormat('Y-m-d', trim($rangeParts[1]));
                if ($sDate && $eDate) {
                    $conditions[] = "(created_at >= ? OR STR_TO_DATE(time, '%d-%m-%Y %H:%i:%s') >= ?)";
                    $conditions[] = "(created_at <= ? OR STR_TO_DATE(time, '%d-%m-%Y %H:%i:%s') <= ?)";
                    $sStr = $sDate->format('Y-m-d 00:00:00');
                    $eStr = $eDate->format('Y-m-d 23:59:59');
                    array_push($params, $sStr, $sStr, $eStr, $eStr);
                }
            } else {
                $date = DateTime::createFromFormat('Y-m-d', $rangeDate);
                if ($date instanceof DateTime) {
                    $conditions[] = "(created_at BETWEEN ? AND ? OR STR_TO_DATE(time, '%d-%m-%Y %H:%i:%s') BETWEEN ? AND ?)";
                    $startStr = $date->format('Y-m-d 00:00:00');
                    $endStr = $date->format('Y-m-d 23:59:59');
                    array_push($params, $startStr, $endStr, $startStr, $endStr);
                }
            }
        }

        // Apply sort_date logic
        if (!empty($filters['sort_date']) && $filters['sort_date'] !== 'all') {
            if ($filters['sort_date'] === 'today') {
                $conditions[] = "(DATE(created_at) = CURDATE() OR STR_TO_DATE(time, '%d-%m-%Y') = CURDATE())";
            } elseif ($filters['sort_date'] === '7') {
                $conditions[] = "(created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) OR STR_TO_DATE(time, '%d-%m-%Y') >= DATE_SUB(CURDATE(), INTERVAL 7 DAY))";
            } elseif ($filters['sort_date'] === '30') {
                $conditions[] = "(created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) OR STR_TO_DATE(time, '%d-%m-%Y') >= DATE_SUB(CURDATE(), INTERVAL 30 DAY))";
            }
        }

        $where = implode(' AND ', $conditions);
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` WHERE {$where}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
    /**
     * Get sum of changes after a specific ID for accurate balance calculation
     */
    public function getSumGiaAfter($username, $id)
    {
        $stmt = $this->db->prepare("SELECT SUM(gia) as total FROM `{$this->table}` WHERE username = ? AND id > ?");
        $stmt->execute([$username, $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    }
}

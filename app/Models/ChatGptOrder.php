<?php

/**
 * ChatGptOrder Model
 * Manages ChatGPT Pro farm purchase orders
 */
class ChatGptOrder extends Model
{
    protected $table = 'chatgpt_orders';

    public function __construct()
    {
        parent::__construct();
    }

    public function generateOrderCode()
    {
        return 'CGP' . strtoupper(bin2hex(random_bytes(5)));
    }

    /**
     * Create a new order
     */
    public function create($data)
    {
        $code = $this->generateOrderCode();
        $expiresAt = $data['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+30 days'));
        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}`
             (`order_code`, `customer_email`, `product_code`, `status`, `assigned_farm_id`, `expires_at`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $code,
            strtolower(trim($data['customer_email'])),
            $data['product_code'] ?? 'chatgpt_pro_add_farm_1_month',
            $data['status'] ?? 'pending',
            $data['assigned_farm_id'] ?? null,
            $expiresAt,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Get order by ID
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT o.*, f.farm_name, f.admin_email as farm_admin_email
             FROM `{$this->table}` o
             LEFT JOIN `chatgpt_farms` f ON f.id = o.assigned_farm_id
             WHERE o.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Check if an email already has an active order
     */
    public function hasActiveOrder($email)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$this->table}`
             WHERE LOWER(`customer_email`) = ? AND `status` IN ('inviting','active')"
        );
        $stmt->execute([strtolower(trim($email))]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Get all orders with optional filters
     */
    public function getAll($filters = [], $limit = 100)
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'o.`status` = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['farm_id'])) {
            $where[] = 'o.`assigned_farm_id` = ?';
            $params[] = (int) $filters['farm_id'];
        }
        if (!empty($filters['email'])) {
            $where[] = 'o.`customer_email` LIKE ?';
            $params[] = '%' . $filters['email'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'o.`created_at` >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'o.`created_at` <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare(
            "SELECT o.*, f.farm_name
             FROM `{$this->table}` o
             LEFT JOIN `chatgpt_farms` f ON f.id = o.assigned_farm_id
             {$whereSql}
             ORDER BY o.`created_at` DESC
             LIMIT {$limit}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Update order status
     */
    public function updateStatus($id, $status, $extra = [])
    {
        $sets = ['`status` = ?', '`updated_at` = NOW()'];
        $params = [$status];

        if (isset($extra['note'])) {
            $sets[] = '`note` = ?';
            $params[] = $extra['note'];
        }
        if (isset($extra['expires_at'])) {
            $sets[] = '`expires_at` = ?';
            $params[] = $extra['expires_at'];
        }
        if (isset($extra['assigned_farm_id'])) {
            $sets[] = '`assigned_farm_id` = ?';
            $params[] = (int) $extra['assigned_farm_id'];
        }

        $params[] = $id;
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $sets) . " WHERE `id` = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get orders that have an invite pending acceptance — for cron sync
     */
    public function getInvitingOrders()
    {
        $stmt = $this->db->prepare(
            "SELECT o.*, f.farm_name, f.admin_api_key, f.admin_email
             FROM `{$this->table}` o
             JOIN `chatgpt_farms` f ON f.id = o.assigned_farm_id
             WHERE o.`status` = 'inviting'"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getInvitingOrdersByFarm($farmId)
    {
        $stmt = $this->db->prepare(
            "SELECT o.*, f.farm_name, f.admin_api_key, f.admin_email
             FROM `{$this->table}` o
             JOIN `chatgpt_farms` f ON f.id = o.assigned_farm_id
             WHERE o.`status` = 'inviting' AND o.`assigned_farm_id` = ?"
        );
        $stmt->execute([(int) $farmId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getExpiredOrders($farmId)
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM `{$this->table}`
             WHERE `assigned_farm_id` = ?
               AND `status` IN ('pending', 'inviting', 'active')
               AND `expires_at` IS NOT NULL
               AND `expires_at` <= NOW()
             ORDER BY `expires_at` ASC, `id` ASC"
        );
        $stmt->execute([(int) $farmId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Stats for admin dashboard
     */
    public function getStats()
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN `status` = 'active'   THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN `status` = 'inviting' THEN 1 ELSE 0 END) as inviting_count,
                SUM(CASE WHEN `status` = 'pending'  THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN `status` = 'failed'   THEN 1 ELSE 0 END) as failed_count,
                SUM(CASE WHEN `status` = 'revoked'  THEN 1 ELSE 0 END) as revoked_count
             FROM `{$this->table}`"
        );
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}

<?php

/**
 * ChatGptAuditLog Model
 * Persistent log of all farm actions
 */
class ChatGptAuditLog extends Model
{
    protected $table = 'chatgpt_audit_logs';

    public const ACTION_CATALOG = [
        'FARM_ADDED' => [
            'label' => 'Thêm farm mới',
            'aliases' => [],
        ],
        'FARM_UPDATED' => [
            'label' => 'Cập nhật farm',
            'aliases' => [],
        ],
        'FARM_SYNCED' => [
            'label' => 'Đồng bộ farm',
            'aliases' => ['FARM_SYNC', 'FARM_SYNC_NOW'],
        ],
        'ORDER_MANUAL_CREATED' => [
            'label' => 'Tạo đơn hàng thủ công',
            'aliases' => ['ORDER_CREATED', 'ORDER_CREATED_MANUAL'],
        ],
        'ORDER_ACTIVATED' => [
            'label' => 'Kích hoạt đơn hàng',
            'aliases' => [],
        ],
        'ORDER_EXPIRED' => [
            'label' => 'Đơn hàng hết hạn',
            'aliases' => [],
        ],
        'SYSTEM_INVITE_CREATED' => [
            'label' => 'Tạo lời mời hệ thống',
            'aliases' => ['INVITE_CREATED', 'SYSTEM_INVITE_SENT'],
        ],
        'SYSTEM_INVITE_FAILED' => [
            'label' => 'Lỗi tạo lời mời',
            'aliases' => ['INVITE_FAILED', 'SYSTEM_INVITE_ERROR'],
        ],
        'INVITE_REVOKED_UNAUTHORIZED' => [
            'label' => 'Thu hồi invite',
            'aliases' => ['INVITE_REVOKED'],
        ],
        'INVITE_UPSERTED' => [
            'label' => 'Cập nhật snapshot invite',
            'aliases' => ['INVITE_SNAPSHOT_UPDATED'],
        ],
        'MEMBER_REMOVED_POLICY' => [
            'label' => 'Xóa thành viên theo chính sách',
            'aliases' => ['MEMBER_POLICY_REMOVED'],
        ],
        'MEMBER_REMOVED_UNAUTHORIZED' => [
            'label' => 'Xóa thành viên không hợp lệ',
            'aliases' => ['MEMBER_UNAUTHORIZED_REMOVED'],
        ],
        'MEMBER_UPSERTED' => [
            'label' => 'Cập nhật snapshot thành viên',
            'aliases' => ['MEMBER_SNAPSHOT_UPDATED'],
        ],
        'PRODUCT_CREATED' => [
            'label' => 'Tạo sản phẩm GPT',
            'aliases' => [],
        ],
        'PRODUCT_UPDATED' => [
            'label' => 'Cập nhật sản phẩm GPT',
            'aliases' => ['PRODUCT_EDITED'],
        ],
        'UNKNOWN' => [
            'label' => 'Không xác định',
            'aliases' => [],
        ],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public static function getActionCatalog(): array
    {
        return self::ACTION_CATALOG;
    }

    public static function normalizeActionName(string $action): string
    {
        $action = strtoupper(trim($action));
        if ($action === '') {
            return 'UNKNOWN';
        }

        foreach (self::ACTION_CATALOG as $canonical => $meta) {
            if ($action === $canonical) {
                return $canonical;
            }

            foreach (($meta['aliases'] ?? []) as $alias) {
                if ($action === strtoupper((string) $alias)) {
                    return $canonical;
                }
            }
        }

        return $action;
    }

    public static function expandActionVariants(string $action): array
    {
        $normalized = self::normalizeActionName($action);
        $variants = [$normalized];

        if (isset(self::ACTION_CATALOG[$normalized])) {
            foreach ((self::ACTION_CATALOG[$normalized]['aliases'] ?? []) as $alias) {
                $variants[] = strtoupper((string) $alias);
            }
        }

        return array_values(array_unique(array_filter($variants)));
    }

    /**
     * Write an audit log entry
     *
     * @param array $data Keys: farm_id, farm_name, action, actor_email, target_email, result, reason, meta
     */
    public function log($data)
    {
        try {
            $createdAt = $this->nowSql();
            $stmt = $this->db->prepare(
                "INSERT INTO `{$this->table}`
                 (`farm_id`, `farm_name`, `action`, `actor_email`, `target_email`, `result`, `reason`, `meta_json`, `created_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
                $createdAt,
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
            $actionVariants = self::expandActionVariants((string) $filters['action']);
            if (count($actionVariants) === 1) {
                $where[] = '`action` = ?';
                $params[] = $actionVariants[0];
            } else {
                $placeholders = implode(', ', array_fill(0, count($actionVariants), '?'));
                $where[] = '`action` IN (' . $placeholders . ')';
                foreach ($actionVariants as $actionVariant) {
                    $params[] = $actionVariant;
                }
            }
        }
        if (!empty($filters['target_email'])) {
            $where[] = '`target_email` LIKE ?';
            $params[] = '%' . $filters['target_email'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = '`created_at` >= ?';
            $params[] = $this->normalizeDateBoundary($filters['date_from'], false);
        }
        if (!empty($filters['date_to'])) {
            $where[] = '`created_at` <= ?';
            $params[] = $this->normalizeDateBoundary($filters['date_to'], true);
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
        $stmt = $this->db->query("SELECT DISTINCT `action` FROM `{$this->table}` ORDER BY `action` ASC");
        $dynamic = array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'action');

        $normalizedDynamic = [];
        foreach ($dynamic as $action) {
            $normalizedDynamic[] = self::normalizeActionName((string) $action);
        }

        $ordered = array_keys(self::ACTION_CATALOG);
        $all = [];
        foreach (array_merge($ordered, $normalizedDynamic) as $action) {
            $action = trim((string) $action);
            if ($action === '' || in_array($action, $all, true)) {
                continue;
            }
            $all[] = $action;
        }

        return $all;
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

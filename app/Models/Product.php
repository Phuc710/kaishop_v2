<?php

/**
 * Product Model
 * Handles product data operations with clean schema
 */
class Product extends Model
{
    protected $table = 'products';
    private ?array $columnMap = null;
    private const WRITABLE_COLUMNS = [
        'name',
        'slug',
        'product_type',
        'price_vnd',
        'source_link',
        'manual_stock',
        'min_purchase_qty',
        'max_purchase_qty',
        'badge_text',
        'category_id',
        'display_order',
        'status',
        'image',
        'gallery',
        'description',
        'seo_description',
        'requires_info',
        'info_instructions',
        'created_at',
        'updated_at',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureColumns();
    }

    public function create($data)
    {
        $filtered = $this->filterWritableColumns((array) $data);
        return parent::create($filtered);
    }

    public function update($id, $data)
    {
        $filtered = $this->filterWritableColumns((array) $data);
        return parent::update($id, $filtered);
    }

    private function filterWritableColumns(array $data): array
    {
        if (empty($data)) {
            return $data;
        }

        $columns = $this->getColumnMap();
        $filtered = [];
        foreach ($data as $key => $value) {
            if (isset($columns[$key])) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    private function getColumnMap(): array
    {
        if (is_array($this->columnMap)) {
            return $this->columnMap;
        }

        $stmt = $this->db->prepare("
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
        ");
        $stmt->execute([$this->table]);

        $dbColumns = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
        $map = [];
        foreach (self::WRITABLE_COLUMNS as $columnName) {
            if (isset($dbColumns[$columnName])) {
                $map[$columnName] = true;
            }
        }

        $this->columnMap = $map;
        return $this->columnMap;
    }

    private function ensureColumns(): void
    {
        $columns = [
            'manual_stock' => "ALTER TABLE {$this->table} ADD COLUMN manual_stock INT NOT NULL DEFAULT 0 AFTER source_link",
        ];

        foreach ($columns as $name => $sql) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
            ");
            $stmt->execute([$this->table, $name]);
            if ((int) $stmt->fetchColumn() > 0) {
                continue;
            }

            try {
                $this->db->exec($sql);
            } catch (Throwable $e) {
                // Leave schema changes to manual migration if ALTER is not allowed.
            }
        }
    }

    /**
     * Find product by ID
     * @param int $id
     * @return array|null
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ? LIMIT 1");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ? $this->hydrateProductRow($row) : null;
    }

    /**
     * Get all active products (for storefront)
     * Ordered by display_order (asc), then newest
     * @return array
     */
    public function getAvailable()
    {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'ON'
                ORDER BY p.display_order ASC, p.id DESC";
        $rows = $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrateProductRow'], $rows);
    }

    /**
     * Get filtered products for admin panel
     * @param array $filters
     * @return array
     */
    public function getFiltered(array $filters = [])
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $where[] = "(p.name LIKE ? OR p.slug LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($filters['status'])) {
            $where[] = "p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = ?";
            $params[] = (int) $filters['category_id'];
        }

        if (!empty($filters['type']) && in_array($filters['type'], ['account', 'link'], true)) {
            $where[] = "p.product_type = ?";
            $params[] = $filters['type'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                {$whereClause} 
                ORDER BY p.display_order ASC, p.id DESC";
        $rows = $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrateProductRow'], $rows);
    }

    /**
     * Find product by slug
     * @param string $slug
     * @return array|null
     */
    public function findBySlug($slug)
    {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.slug = ? LIMIT 1";
        $result = $this->query($sql, [$slug])->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->hydrateProductRow($result) : null;
    }

    /**
     * Find active product by category slug + product slug (public canonical URL)
     */
    public function findByCategoryAndProductSlug(string $categorySlug, string $productSlug): ?array
    {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                INNER JOIN categories c ON p.category_id = c.id
                WHERE c.slug = ? AND p.slug = ? AND p.status = 'ON'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categorySlug, $productSlug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ? $this->hydrateProductRow($row) : null;
    }

    /**
     * Generate unique slug from name
     * @param string $name
     * @param int|null $excludeId
     * @return string
     */
    public function generateSlug($name, $excludeId = null)
    {
        $slug = $this->toSlug($name);
        $baseSlug = $slug;
        $counter = 1;

        while (true) {
            $sql = "SELECT id FROM {$this->table} WHERE slug = ?";
            $params = [$slug];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $existing = $this->db->prepare($sql);
            $existing->execute($params);
            if (!$existing->fetch())
                break;

            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Convert string to URL slug
     */
    private function toSlug($str)
    {
        $map = ['à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'đ' => 'd', 'À' => 'A', 'Á' => 'A', 'Ạ' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ậ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ặ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A', 'È' => 'E', 'É' => 'E', 'Ẹ' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ệ' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Ị' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ò' => 'O', 'Ó' => 'O', 'Ọ' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ộ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ợ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Ụ' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ự' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỵ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Đ' => 'D'];
        $str = strtr($str, $map);
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $str), '-'));
    }

    /**
     * Toggle product status ON/OFF
     * @param int $id
     * @return array
     */
    public function toggleStatus($id)
    {
        $product = $this->find((int) $id);
        if (!$product) {
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
        }

        $newStatus = ($product['status'] === 'ON') ? 'OFF' : 'ON';
        $success = $this->update((int) $id, ['status' => $newStatus]);

        return ['success' => $success, 'new_value' => $newStatus];
    }

    /**
     * Get categories list
     * @return array
     */
    public function getCategories()
    {
        $sql = "SELECT id, name, slug FROM categories WHERE status = 'ON' ORDER BY display_order ASC, name ASC";
        return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get product stats
     * @return array
     */
    public function getStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(status = 'ON') as active,
                    SUM(status = 'OFF') as inactive,
                    SUM(product_type = 'account') as type_account,
                    SUM(product_type = 'link') as type_link
                FROM {$this->table}";
        $row = $this->query($sql)->fetch(PDO::FETCH_ASSOC);
        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'inactive' => (int) ($row['inactive'] ?? 0),
            'type_account' => (int) ($row['type_account'] ?? 0),
            'type_link' => (int) ($row['type_link'] ?? 0),
        ];
    }

    public static function resolveDeliveryMode(array $row): string
    {
        $productType = (string) ($row['product_type'] ?? 'account');
        $requiresInfo = (int) ($row['requires_info'] ?? 0) === 1;

        if ($productType === 'link') {
            return 'source_link';
        }

        if ($requiresInfo) {
            return 'manual_info';
        }

        return 'account_stock';
    }

    public static function isStockManagedProduct(array $row): bool
    {
        return self::resolveDeliveryMode($row) !== 'source_link';
    }

    public static function usesAccountStock(array $row): bool
    {
        return self::resolveDeliveryMode($row) === 'account_stock';
    }

    public static function usesManualStock(array $row): bool
    {
        return self::resolveDeliveryMode($row) === 'manual_info';
    }

    public static function normalizeRuntimeRow(array $row): array
    {
        $row['product_type'] = $row['product_type'] ?? 'account';
        $row['source_link'] = $row['source_link'] ?? null;
        $row['manual_stock'] = max(0, (int) ($row['manual_stock'] ?? 0));
        $row['min_purchase_qty'] = max(1, (int) ($row['min_purchase_qty'] ?? 1));
        $row['max_purchase_qty'] = max(0, (int) ($row['max_purchase_qty'] ?? 0));
        $row['requires_info'] = (int) ($row['requires_info'] ?? 0);
        $row['info_instructions'] = $row['info_instructions'] ?? null;

        if ((string) $row['product_type'] === 'link') {
            $row['min_purchase_qty'] = 1;
            $row['max_purchase_qty'] = 1;
            $row['requires_info'] = 0;
            $row['info_instructions'] = null;
        }

        $row['delivery_mode'] = self::resolveDeliveryMode($row);
        $row['stock_managed'] = self::isStockManagedProduct($row);

        return $row;
    }

    private function hydrateProductRow(array $row): array
    {
        $row = self::normalizeRuntimeRow($row);
        $row['gallery_arr'] = $this->decodeJsonArray($row['gallery'] ?? null);
        $row['category_slug'] = trim((string) ($row['category_slug'] ?? ''));
        $row['delivery_label'] = self::getDeliveryModeLabel($row['delivery_mode'] ?? '');
        $row['public_path'] = $this->buildPublicPath(
            $row['category_slug'],
            (string) ($row['slug'] ?? ''),
            (int) ($row['id'] ?? 0)
        );

        return $row;
    }

    public static function getDeliveryModeLabel(string $mode): string
    {
        switch ($mode) {
            case 'account_stock':
                return 'Tài Khoản';
            case 'manual_info':
                return 'Yêu cầu thông tin';
            case 'source_link':
                return 'Source';
            default:
                return 'Khác';
        }
    }

    private function buildPublicPath(string $categorySlug, string $productSlug, int $productId): string
    {
        $categorySlug = trim($categorySlug, " /|");
        $productSlug = trim($productSlug, " /|");

        if ($categorySlug !== '' && $productSlug !== '') {
            return $categorySlug . '/' . $productSlug;
        }

        return 'product/' . max(0, $productId);
    }

    private function decodeJsonArray($json): array
    {
        if (!is_string($json) || trim($json) === '')
            return [];
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function decodeJsonFlexible($json)
    {
        if (!is_string($json) || trim($json) === '')
            return [];
        $decoded = json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
    }
}

<?php

/**
 * Product model.
 */
class Product extends Model
{
    protected $table = 'products';
    private ?array $columnMap = null;
    private ?array $tableColumns = null;

    public const VISIBILITY_BOTH = 'both';
    public const VISIBILITY_WEB = 'web';
    public const VISIBILITY_TELEGRAM = 'telegram';
    public const VISIBILITY_HIDDEN = 'hidden';

    public const CHANNEL_WEB = 'web';
    public const CHANNEL_TELEGRAM = 'telegram';

    private const WRITABLE_COLUMNS = [
        'name',
        'slug',
        'product_type',
        'price_vnd',
        'old_price',
        'source_link',
        'manual_stock',
        'min_purchase_qty',
        'max_purchase_qty',
        'badge_text',
        'category_id',
        'display_order',
        'status',
        'visibility_mode',
        'show_on_web',
        'show_on_telegram',
        'image',
        'gallery',
        'description',
        'seo_description',
        'requires_info',
        'info_instructions',
        'duration_days',
        'auto_invite',
        'farm_id',
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

        $dbColumns = $this->getTableColumns();
        $map = [];
        foreach (self::WRITABLE_COLUMNS as $columnName) {
            if (isset($dbColumns[$columnName])) {
                $map[$columnName] = true;
            }
        }

        $this->columnMap = $map;
        return $this->columnMap;
    }

    private function getTableColumns(): array
    {
        if (is_array($this->tableColumns)) {
            return $this->tableColumns;
        }

        $stmt = $this->db->prepare("
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
        ");
        $stmt->execute([$this->table]);

        $this->tableColumns = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
        return $this->tableColumns;
    }

    private function hasTableColumn(string $columnName): bool
    {
        $columns = $this->getTableColumns();
        return isset($columns[$columnName]);
    }

    private function resetColumnCaches(): void
    {
        $this->columnMap = null;
        $this->tableColumns = null;
    }

    private function ensureColumns(): void
    {
        $visibilitySchemaChanged = false;

        $columns = [
            'manual_stock' => "ALTER TABLE `{$this->table}` ADD COLUMN `manual_stock` INT NOT NULL DEFAULT 0 AFTER `source_link`",
            'visibility_mode' => "ALTER TABLE `{$this->table}` ADD COLUMN `visibility_mode` ENUM('both','web','telegram','hidden') NOT NULL DEFAULT 'both' AFTER `status`",
            'show_on_web' => "ALTER TABLE `{$this->table}` ADD COLUMN `show_on_web` TINYINT(1) NOT NULL DEFAULT 1 AFTER `visibility_mode`",
            'show_on_telegram' => "ALTER TABLE `{$this->table}` ADD COLUMN `show_on_telegram` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_on_web`",
        ];

        foreach ($columns as $name => $sql) {
            if ($this->hasTableColumn($name)) {
                continue;
            }

            try {
                $this->db->exec($sql);
                $this->resetColumnCaches();
                if (in_array($name, ['visibility_mode', 'show_on_web', 'show_on_telegram'], true)) {
                    $visibilitySchemaChanged = true;
                }
            } catch (Throwable $e) {
                // Manual migration can handle this when ALTER is restricted.
            }
        }

        $indexes = [
            "ALTER TABLE `{$this->table}` ADD KEY `idx_products_visibility_mode` (`visibility_mode`)",
            "ALTER TABLE `{$this->table}` ADD KEY `idx_products_show_on_web` (`show_on_web`)",
            "ALTER TABLE `{$this->table}` ADD KEY `idx_products_show_on_telegram` (`show_on_telegram`)",
        ];

        foreach ($indexes as $sql) {
            try {
                $this->db->exec($sql);
            } catch (Throwable $e) {
                // Ignore duplicate-key or restricted ALTER failures.
            }
        }

        if ($visibilitySchemaChanged) {
            $this->syncVisibilityColumns();
        }
    }

    private function syncVisibilityColumns(): void
    {
        if (!$this->hasTableColumn('visibility_mode')) {
            return;
        }

        try {
            $this->db->exec("
                UPDATE `{$this->table}`
                SET `visibility_mode` = CASE
                    WHEN COALESCE(`status`, 'ON') = 'OFF' THEN 'hidden'
                    ELSE 'both'
                END
                WHERE `visibility_mode` IS NULL
                   OR `visibility_mode` NOT IN ('both','web','telegram','hidden')
            ");
        } catch (Throwable $e) {
            // Best-effort backfill only.
        }

        if (!$this->hasTableColumn('show_on_web') || !$this->hasTableColumn('show_on_telegram')) {
            return;
        }

        try {
            $this->db->exec("
                UPDATE `{$this->table}`
                SET
                    `show_on_web` = CASE WHEN `visibility_mode` IN ('both','web') THEN 1 ELSE 0 END,
                    `show_on_telegram` = CASE WHEN `visibility_mode` IN ('both','telegram') THEN 1 ELSE 0 END,
                    `status` = CASE WHEN `visibility_mode` = 'hidden' THEN 'OFF' ELSE 'ON' END
            ");
        } catch (Throwable $e) {
            // Best-effort backfill only.
        }
    }

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

    public function getAvailable(string $channel = self::CHANNEL_WEB)
    {
        $channelWhere = $this->buildChannelVisibilityWhere($channel, 'p');
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE {$channelWhere}
                ORDER BY p.display_order ASC, p.id DESC";
        $rows = $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrateProductRow'], $rows);
    }

    public function getFiltered(array $filters = [])
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $where[] = "(p.name LIKE ? OR p.slug LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($filters['status'])) {
            $where[] = "p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['visibility_mode'])) {
            $where[] = $this->buildVisibilityModeWhere((string) $filters['visibility_mode'], 'p');
        }

        if (!empty($filters['channel'])) {
            $where[] = $this->buildChannelVisibilityWhere((string) $filters['channel'], 'p');
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

    public function findBySlug($slug)
    {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.slug = ? LIMIT 1";
        $result = $this->query($sql, [$slug])->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->hydrateProductRow($result) : null;
    }

    public function findByCategoryAndProductSlug(string $categorySlug, string $productSlug, string $channel = self::CHANNEL_WEB): ?array
    {
        $channelWhere = $this->buildChannelVisibilityWhere($channel, 'p');
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                INNER JOIN categories c ON p.category_id = c.id
                WHERE c.slug = ? AND p.slug = ? AND {$channelWhere}
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categorySlug, $productSlug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ? $this->hydrateProductRow($row) : null;
    }

    public function findVisibleForChannel(int $id, string $channel = self::CHANNEL_WEB): ?array
    {
        $row = $this->find($id);
        if (!$row) {
            return null;
        }

        return self::isVisibleOnChannel($row, $channel) ? $row : null;
    }

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
            if (!$existing->fetch()) {
                break;
            }

            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function toSlug($str)
    {
        $map = ['Ã ' => 'a', 'Ã¡' => 'a', 'áº¡' => 'a', 'áº£' => 'a', 'Ã£' => 'a', 'Ã¢' => 'a', 'áº§' => 'a', 'áº¥' => 'a', 'áº­' => 'a', 'áº©' => 'a', 'áº«' => 'a', 'Äƒ' => 'a', 'áº±' => 'a', 'áº¯' => 'a', 'áº·' => 'a', 'áº³' => 'a', 'áºµ' => 'a', 'Ã¨' => 'e', 'Ã©' => 'e', 'áº¹' => 'e', 'áº»' => 'e', 'áº½' => 'e', 'Ãª' => 'e', 'á»' => 'e', 'áº¿' => 'e', 'á»‡' => 'e', 'á»ƒ' => 'e', 'á»…' => 'e', 'Ã¬' => 'i', 'Ã­' => 'i', 'á»‹' => 'i', 'á»‰' => 'i', 'Ä©' => 'i', 'Ã²' => 'o', 'Ã³' => 'o', 'á»' => 'o', 'á»' => 'o', 'Ãµ' => 'o', 'Ã´' => 'o', 'á»“' => 'o', 'á»‘' => 'o', 'á»™' => 'o', 'á»•' => 'o', 'á»—' => 'o', 'Æ¡' => 'o', 'á»' => 'o', 'á»›' => 'o', 'á»£' => 'o', 'á»Ÿ' => 'o', 'á»¡' => 'o', 'Ã¹' => 'u', 'Ãº' => 'u', 'á»¥' => 'u', 'á»§' => 'u', 'Å©' => 'u', 'Æ°' => 'u', 'á»«' => 'u', 'á»©' => 'u', 'á»±' => 'u', 'á»­' => 'u', 'á»¯' => 'u', 'á»³' => 'y', 'Ã½' => 'y', 'á»µ' => 'y', 'á»·' => 'y', 'á»¹' => 'y', 'Ä‘' => 'd', 'Ã€' => 'A', 'Ã' => 'A', 'áº ' => 'A', 'áº¢' => 'A', 'Ãƒ' => 'A', 'Ã‚' => 'A', 'áº¦' => 'A', 'áº¤' => 'A', 'áº¬' => 'A', 'áº¨' => 'A', 'áºª' => 'A', 'Ä‚' => 'A', 'áº°' => 'A', 'áº®' => 'A', 'áº¶' => 'A', 'áº²' => 'A', 'áº´' => 'A', 'Ãˆ' => 'E', 'Ã‰' => 'E', 'áº¸' => 'E', 'áºº' => 'E', 'áº¼' => 'E', 'ÃŠ' => 'E', 'á»€' => 'E', 'áº¾' => 'E', 'á»†' => 'E', 'á»‚' => 'E', 'á»„' => 'E', 'ÃŒ' => 'I', 'Ã' => 'I', 'á»Š' => 'I', 'á»ˆ' => 'I', 'Ä¨' => 'I', 'Ã’' => 'O', 'Ã“' => 'O', 'á»Œ' => 'O', 'á»Ž' => 'O', 'Ã•' => 'O', 'Ã”' => 'O', 'á»’' => 'O', 'á»' => 'O', 'á»˜' => 'O', 'á»”' => 'O', 'á»–' => 'O', 'Æ ' => 'O', 'á»œ' => 'O', 'á»š' => 'O', 'á»¢' => 'O', 'á»ž' => 'O', 'á» ' => 'O', 'Ã™' => 'U', 'Ãš' => 'U', 'á»¤' => 'U', 'á»¦' => 'U', 'Å¨' => 'U', 'Æ¯' => 'U', 'á»ª' => 'U', 'á»¨' => 'U', 'á»°' => 'U', 'á»¬' => 'U', 'á»®' => 'U', 'á»²' => 'Y', 'Ã' => 'Y', 'á»´' => 'Y', 'á»¶' => 'Y', 'á»¸' => 'Y', 'Ä' => 'D'];
        $str = strtr($str, $map);
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $str), '-'));
    }

    public function toggleStatus($id)
    {
        $product = $this->find((int) $id);
        if (!$product) {
            return ['success' => false, 'message' => 'San pham khong ton tai'];
        }

        $newMode = ((string) ($product['visibility_mode'] ?? self::resolveVisibilityMode($product)) === self::VISIBILITY_HIDDEN)
            ? self::VISIBILITY_BOTH
            : self::VISIBILITY_HIDDEN;

        return $this->setVisibilityMode((int) $id, $newMode);
    }

    public function setVisibilityMode(int $id, string $mode): array
    {
        $product = $this->find($id);
        if (!$product) {
            return ['success' => false, 'message' => 'San pham khong ton tai'];
        }

        $payload = self::buildVisibilityPayload($mode);
        $success = $this->update($id, $payload);
        if (!$success) {
            return ['success' => false, 'message' => 'Khong the cap nhat trang thai san pham'];
        }

        return [
            'success' => true,
            'new_value' => $payload['status'],
            'new_status' => $payload['status'],
            'visibility_mode' => $payload['visibility_mode'],
            'show_on_web' => $payload['show_on_web'],
            'show_on_telegram' => $payload['show_on_telegram'],
        ];
    }

    public function getCategories()
    {
        $sql = "SELECT id, name, slug FROM categories WHERE status = 'ON' ORDER BY display_order ASC, name ASC";
        return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(): array
    {
        if ($this->hasTableColumn('visibility_mode')) {
            $sql = "SELECT
                        COUNT(*) as total,
                        SUM(status = 'ON') as active,
                        SUM(status = 'OFF') as inactive,
                        SUM(visibility_mode = 'both') as both_count,
                        SUM(visibility_mode = 'web') as web_only,
                        SUM(visibility_mode = 'telegram') as telegram_only,
                        SUM(visibility_mode = 'hidden') as hidden,
                        SUM(show_on_web = 1) as visible_web,
                        SUM(show_on_telegram = 1) as visible_telegram,
                        SUM(product_type = 'account') as type_account,
                        SUM(product_type = 'link') as type_link
                    FROM {$this->table}";
        } else {
            $sql = "SELECT
                        COUNT(*) as total,
                        SUM(status = 'ON') as active,
                        SUM(status = 'OFF') as inactive,
                        SUM(status = 'ON') as both_count,
                        0 as web_only,
                        0 as telegram_only,
                        SUM(status = 'OFF') as hidden,
                        SUM(status = 'ON') as visible_web,
                        SUM(status = 'ON') as visible_telegram,
                        SUM(product_type = 'account') as type_account,
                        SUM(product_type = 'link') as type_link
                    FROM {$this->table}";
        }

        $row = $this->query($sql)->fetch(PDO::FETCH_ASSOC);
        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'inactive' => (int) ($row['inactive'] ?? 0),
            'both' => (int) ($row['both_count'] ?? 0),
            'web_only' => (int) ($row['web_only'] ?? 0),
            'telegram_only' => (int) ($row['telegram_only'] ?? 0),
            'hidden' => (int) ($row['hidden'] ?? 0),
            'visible_web' => (int) ($row['visible_web'] ?? 0),
            'visible_telegram' => (int) ($row['visible_telegram'] ?? 0),
            'type_account' => (int) ($row['type_account'] ?? 0),
            'type_link' => (int) ($row['type_link'] ?? 0),
        ];
    }

    public static function resolveDeliveryMode(array $row): string
    {
        $productType = (string) ($row['product_type'] ?? 'account');
        $requiresInfo = (int) ($row['requires_info'] ?? 0) === 1;

        if ($productType === 'business_invite_auto') {
            return 'business_invite_auto';
        }

        if ($requiresInfo) {
            return 'manual_info';
        }

        return 'account_stock';
    }

    public static function isBusinessInviteAuto(array $row): bool
    {
        return (string) ($row['product_type'] ?? '') === 'business_invite_auto';
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
        $mode = self::resolveDeliveryMode($row);
        return $mode === 'manual_info' || $mode === 'business_invite_auto';
    }

    public static function normalizeVisibilityMode(string $mode): string
    {
        $mode = strtolower(trim($mode));
        return in_array($mode, [
            self::VISIBILITY_BOTH,
            self::VISIBILITY_WEB,
            self::VISIBILITY_TELEGRAM,
            self::VISIBILITY_HIDDEN,
        ], true) ? $mode : self::VISIBILITY_BOTH;
    }

    public static function buildVisibilityPayload(string $mode): array
    {
        $mode = self::normalizeVisibilityMode($mode);
        return [
            'visibility_mode' => $mode,
            'show_on_web' => in_array($mode, [self::VISIBILITY_BOTH, self::VISIBILITY_WEB], true) ? 1 : 0,
            'show_on_telegram' => in_array($mode, [self::VISIBILITY_BOTH, self::VISIBILITY_TELEGRAM], true) ? 1 : 0,
            'status' => $mode === self::VISIBILITY_HIDDEN ? 'OFF' : 'ON',
        ];
    }

    public static function resolveVisibilityMode(array $row): string
    {
        $mode = strtolower(trim((string) ($row['visibility_mode'] ?? '')));
        if (
            in_array($mode, [
                self::VISIBILITY_BOTH,
                self::VISIBILITY_WEB,
                self::VISIBILITY_TELEGRAM,
                self::VISIBILITY_HIDDEN,
            ], true)
        ) {
            return $mode;
        }

        $hasFlags = array_key_exists('show_on_web', $row) || array_key_exists('show_on_telegram', $row);
        if ($hasFlags) {
            return self::visibilityModeFromFlags(
                (int) ($row['show_on_web'] ?? 0),
                (int) ($row['show_on_telegram'] ?? 0)
            );
        }

        return strtoupper(trim((string) ($row['status'] ?? 'ON'))) === 'OFF'
            ? self::VISIBILITY_HIDDEN
            : self::VISIBILITY_BOTH;
    }

    public static function isVisibleOnWeb(array $row): bool
    {
        return self::isVisibleOnChannel($row, self::CHANNEL_WEB);
    }

    public static function isVisibleOnTelegram(array $row): bool
    {
        return self::isVisibleOnChannel($row, self::CHANNEL_TELEGRAM);
    }

    public static function isVisibleOnChannel(array $row, string $channel): bool
    {
        $mode = self::resolveVisibilityMode($row);
        if ($mode === self::VISIBILITY_HIDDEN) {
            return false;
        }

        $channel = strtolower(trim($channel));
        if ($channel === self::CHANNEL_TELEGRAM) {
            return in_array($mode, [self::VISIBILITY_BOTH, self::VISIBILITY_TELEGRAM], true);
        }

        return in_array($mode, [self::VISIBILITY_BOTH, self::VISIBILITY_WEB], true);
    }

    private static function visibilityModeFromFlags(int $showOnWeb, int $showOnTelegram): string
    {
        if ($showOnWeb === 1 && $showOnTelegram === 1) {
            return self::VISIBILITY_BOTH;
        }
        if ($showOnWeb === 1) {
            return self::VISIBILITY_WEB;
        }
        if ($showOnTelegram === 1) {
            return self::VISIBILITY_TELEGRAM;
        }

        return self::VISIBILITY_HIDDEN;
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

        $visibilityPayload = self::buildVisibilityPayload(self::resolveVisibilityMode($row));
        $row['visibility_mode'] = $visibilityPayload['visibility_mode'];
        $row['show_on_web'] = $visibilityPayload['show_on_web'];
        $row['show_on_telegram'] = $visibilityPayload['show_on_telegram'];
        $row['status'] = $visibilityPayload['status'];

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
            case 'business_invite_auto':
                return 'GPT Business (Auto)';
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
        if (!is_string($json) || trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function decodeJsonFlexible($json)
    {
        if (!is_string($json) || trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
    }

    private function buildChannelVisibilityWhere(string $channel, string $alias = 'p'): string
    {
        $columnPrefix = trim($alias) !== '' ? rtrim($alias, '.') . '.' : '';
        $channel = strtolower(trim($channel));

        if ($channel === self::CHANNEL_TELEGRAM) {
            if ($this->hasTableColumn('show_on_telegram')) {
                return "{$columnPrefix}`status` = 'ON' AND {$columnPrefix}`show_on_telegram` = 1";
            }
            if ($this->hasTableColumn('visibility_mode')) {
                return "{$columnPrefix}`visibility_mode` IN ('both','telegram')";
            }
            return "{$columnPrefix}`status` = 'ON'";
        }

        if ($this->hasTableColumn('show_on_web')) {
            return "{$columnPrefix}`status` = 'ON' AND {$columnPrefix}`show_on_web` = 1";
        }
        if ($this->hasTableColumn('visibility_mode')) {
            return "{$columnPrefix}`visibility_mode` IN ('both','web')";
        }

        return "{$columnPrefix}`status` = 'ON'";
    }

    private function buildVisibilityModeWhere(string $mode, string $alias = 'p'): string
    {
        $columnPrefix = trim($alias) !== '' ? rtrim($alias, '.') . '.' : '';
        $mode = self::normalizeVisibilityMode($mode);

        if ($this->hasTableColumn('visibility_mode')) {
            return "{$columnPrefix}`visibility_mode` = " . $this->db->quote($mode);
        }

        if ($mode === self::VISIBILITY_HIDDEN) {
            return "{$columnPrefix}`status` = 'OFF'";
        }

        return "{$columnPrefix}`status` = 'ON'";
    }
}

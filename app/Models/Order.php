<?php

/**
 * Order Model (single-product purchase records).
 */
class Order extends Model
{
    protected $table = 'orders';
    private ?CryptoService $crypto = null;

    public function __construct()
    {
        parent::__construct();
        if (class_exists('CryptoService')) {
            $this->crypto = new CryptoService();
        }
        $this->ensureColumns();
    }

    public function generateOrderCode(): string
    {
        return 'Y' . strtoupper(bin2hex(random_bytes(8)));
    }

    private function ensureColumns(): void
    {
        $cols = [
            'quantity' => "ALTER TABLE `{$this->table}` ADD COLUMN `quantity` INT NOT NULL DEFAULT 1 AFTER `price`",
            'customer_input' => "ALTER TABLE `{$this->table}` ADD COLUMN `customer_input` LONGTEXT NULL AFTER `stock_content`",
            'fulfilled_by' => "ALTER TABLE `{$this->table}` ADD COLUMN `fulfilled_by` VARCHAR(100) NULL AFTER `customer_input`",
            'fulfilled_at' => "ALTER TABLE `{$this->table}` ADD COLUMN `fulfilled_at` DATETIME NULL AFTER `fulfilled_by`",
            'cancel_reason' => "ALTER TABLE `{$this->table}` ADD COLUMN `cancel_reason` TEXT NULL AFTER `fulfilled_at`",
            'user_deleted_at' => "ALTER TABLE `{$this->table}` ADD COLUMN `user_deleted_at` DATETIME NULL AFTER `cancel_reason`",
        ];

        foreach ($cols as $col => $sql) {
            if ($this->hasColumn($col)) {
                continue;
            }
            try {
                $this->db->exec($sql);
            } catch (Throwable $e) {
                // Keep backward compatibility on restricted DB users.
            }
        }
    }

    public function hasColumn(string $column): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$this->table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ? $this->hydrateOrderRow($row) : null;
    }

    public function getByIdForUser(int $id, int $userId): ?array
    {
        $softDeleteEnabled = $this->hasColumn('user_deleted_at');
        $stmt = $this->db->prepare("
            SELECT *
            FROM `{$this->table}`
            WHERE `id` = ? AND `user_id` = ?" . ($softDeleteEnabled ? " AND `user_deleted_at` IS NULL" : "") . "
            LIMIT 1
        ");
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ? $this->hydrateOrderRow($row) : null;
    }

    public function hideForUser(int $id, int $userId): array
    {
        if ($id <= 0 || $userId <= 0) {
            return ['success' => false, 'message' => 'Dữ liệu không hợp lệ.'];
        }

        try {
            if ($this->hasColumn('user_deleted_at')) {
                $stmt = $this->db->prepare("
                    UPDATE `{$this->table}`
                    SET `user_deleted_at` = NOW()
                    WHERE `id` = ? AND `user_id` = ? AND `user_deleted_at` IS NULL
                    LIMIT 1
                ");
                $stmt->execute([$id, $userId]);
            } else {
                // Fallback for DBs where ALTER TABLE is not permitted.
                $stmt = $this->db->prepare("
                    DELETE FROM `{$this->table}`
                    WHERE `id` = ? AND `user_id` = ?
                    LIMIT 1
                ");
                $stmt->execute([$id, $userId]);
            }

            if ($stmt->rowCount() < 1) {
                return ['success' => false, 'message' => 'Đơn hàng không tồn tại hoặc đã bị xóa.'];
            }

            return ['success' => true, 'message' => 'Đã xóa đơn hàng khỏi lịch sử của bạn.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Không thể xóa đơn hàng lúc này.'];
        }
    }

    public function countUserVisibleOrders(int $userId, array $filters = []): int
    {
        [$whereSql, $params] = $this->buildUserHistoryWhere($userId, $filters);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `{$this->table}` {$whereSql}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getUserVisibleOrders(int $userId, array $filters = [], int $offset = 0, int $limit = 10): array
    {
        [$whereSql, $params] = $this->buildUserHistoryWhere($userId, $filters);
        $offset = max(0, $offset);
        $limit = max(1, min(200, $limit));

        $sql = "
            SELECT *
            FROM `{$this->table}`
            {$whereSql}
            ORDER BY `created_at` DESC, `id` DESC
            LIMIT {$offset}, {$limit}
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(fn(array $row) => $this->hydrateOrderRow($row), $rows);
    }

    /**
     * Fetch all user orders using non-search filters only (for smart AJAX search in PHP).
     *
     * @return array<int,array<string,mixed>>
     */
    public function getAllUserVisibleOrders(int $userId, array $filters = []): array
    {
        $filtersNoSearch = $filters;
        $filtersNoSearch['search'] = '';
        [$whereSql, $params] = $this->buildUserHistoryWhere($userId, $filtersNoSearch);

        $sql = "
            SELECT *
            FROM `{$this->table}`
            {$whereSql}
            ORDER BY `created_at` DESC, `id` DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn(array $row) => $this->hydrateOrderRow($row), $rows);
    }

    /**
     * Smart search for user order history (accent-insensitive, multi-token, matches visible short code).
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function smartFilterUserVisibleOrders(array $rows, string $search): array
    {
        $tokens = $this->tokenizeSmartSearch($search);
        if ($tokens === []) {
            return $rows;
        }

        return array_values(array_filter($rows, function (array $row) use ($tokens): bool {
            $status = (string) ($row['status'] ?? '');
            $statusAliases = match ($status) {
                'completed' => 'hoan tat thanh cong xong done completed',
                'pending' => 'cho xu ly pending doi',
                'processing' => 'dang xu ly processing',
                'cancelled' => 'da huy huy cancelled',
                default => $status,
            };

            $price = (int) ($row['price'] ?? 0);
            $quantity = (int) ($row['quantity'] ?? 1);

            $haystack = implode(' ', array_filter([
                (string) ($row['order_code'] ?? ''),
                (string) ($row['order_code_short'] ?? ''),
                (string) ($row['product_name'] ?? ''),
                (string) ($row['created_at'] ?? ''),
                (string) ($row['customer_input'] ?? ''),
                (string) ($row['cancel_reason'] ?? ''),
                (string) ($row['stock_content_plain'] ?? ''),
                (string) $quantity,
                (string) $price,
                number_format($price, 0, ',', '.'),
                $status,
                $statusAliases,
            ], static fn($v) => (string) $v !== ''));

            $normalized = $this->normalizeSmartSearchText($haystack);
            $digits = preg_replace('/\D+/', '', $haystack) ?? '';

            foreach ($tokens as $token) {
                if ($token === '') {
                    continue;
                }
                if (preg_match('/^\d+$/', $token) === 1) {
                    if ($digits === '' || strpos($digits, $token) === false) {
                        return false;
                    }
                    continue;
                }
                if (strpos($normalized, $token) === false) {
                    return false;
                }
            }

            return true;
        }));
    }

    public function getByIdForUpdate(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `id` = ? LIMIT 1 FOR UPDATE");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ? $this->hydrateOrderRow($row) : null;
    }

    public function fulfillPendingOrder(int $id, string $deliveryContent, string $adminUsername): array
    {
        $deliveryContent = trim($deliveryContent);
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID don hang khong hop le.'];
        }
        if ($deliveryContent === '') {
            return ['success' => false, 'message' => 'Vui long nhap noi dung ban giao.'];
        }

        $started = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $started = true;
            }

            $order = $this->getByIdForUpdate($id);
            if (!$order) {
                throw new RuntimeException('Don hang khong ton tai.');
            }

            $status = (string) ($order['status'] ?? '');
            if (!in_array($status, ['pending', 'processing'], true)) {
                throw new RuntimeException('Don hang nay khong o trang thai cho xu ly.');
            }

            $storedContent = ($this->crypto instanceof CryptoService && $this->crypto->isEnabled())
                ? $this->crypto->encryptString($deliveryContent)
                : $deliveryContent;

            $sets = ["`status` = 'completed'", "`stock_content` = ?"];
            $params = [$storedContent];

            if ($this->hasColumn('fulfilled_by')) {
                $sets[] = "`fulfilled_by` = ?";
                $params[] = $adminUsername;
            }
            if ($this->hasColumn('fulfilled_at')) {
                $sets[] = "`fulfilled_at` = NOW()";
            }

            $params[] = $id;
            $sql = "UPDATE `{$this->table}` SET " . implode(', ', $sets) . " WHERE `id` = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            if ($started && $this->db->inTransaction()) {
                $this->db->commit();
            }

            return ['success' => true, 'message' => 'Da giao noi dung va hoan tat don hang.'];
        } catch (Throwable $e) {
            if ($started && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Khong the xu ly don hang luc nay.'];
        }
    }

    public function cancelPendingOrder(int $id, string $adminUsername, string $reason = ''): array
    {
        $reason = trim($reason);
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID don hang khong hop le.'];
        }
        if ($reason === '') {
            return ['success' => false, 'message' => 'Vui long nhap noi dung huy/phan hoi cho user.'];
        }

        $started = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $started = true;
            }

            $order = $this->getByIdForUpdate($id);
            if (!$order) {
                throw new RuntimeException('Don hang khong ton tai.');
            }

            $status = (string) ($order['status'] ?? '');
            if ($status !== 'pending') {
                throw new RuntimeException('Chi co the huy don hang dang pending.');
            }

            $userId = (int) ($order['user_id'] ?? 0);
            $username = trim((string) ($order['username'] ?? ''));
            $price = max(0, (int) ($order['price'] ?? 0));
            if ($userId <= 0) {
                throw new RuntimeException('Khong xac dinh duoc user cua don hang.');
            }
            if ($price <= 0) {
                throw new RuntimeException('Gia tri don hang khong hop le.');
            }

            $userStmt = $this->db->prepare("SELECT id FROM `users` WHERE `id` = ? LIMIT 1 FOR UPDATE");
            $userStmt->execute([$userId]);
            if (!$userStmt->fetch(PDO::FETCH_ASSOC)) {
                throw new RuntimeException('Tai khoan nguoi mua khong ton tai.');
            }

            $refundStmt = $this->db->prepare("UPDATE `users` SET `money` = `money` + ? WHERE `id` = ? LIMIT 1");
            $refundStmt->execute([$price, $userId]);
            if ($refundStmt->rowCount() < 1) {
                throw new RuntimeException('Khong the hoan tien cho nguoi dung.');
            }

            $sets = ["`status` = 'cancelled'"];
            $params = [];
            if ($this->hasColumn('cancel_reason')) {
                $sets[] = "`cancel_reason` = ?";
                $params[] = ($reason !== '' ? $reason : null);
            }
            if ($this->hasColumn('fulfilled_by')) {
                $sets[] = "`fulfilled_by` = ?";
                $params[] = $adminUsername;
            }
            if ($this->hasColumn('fulfilled_at')) {
                $sets[] = "`fulfilled_at` = NOW()";
            }
            $params[] = $id;

            $updateSql = "UPDATE `{$this->table}` SET " . implode(', ', $sets) . " WHERE `id` = ? LIMIT 1";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute($params);

            try {
                $activityStmt = $this->db->prepare("
                    INSERT INTO `lich_su_hoat_dong` (`username`, `hoatdong`, `gia`, `time`)
                    VALUES (?, ?, ?, ?)
                ");
                $activityStmt->execute([
                    $username !== '' ? $username : ('user#' . $userId),
                    'Hoan tien don hang bi huy: ' . (string) ($order['product_name'] ?? ('#' . $id)),
                    (string) $price,
                    (string) time(),
                ]);
            } catch (Throwable $e) {
                // Non-blocking if legacy activity table schema differs.
            }

            if ($started && $this->db->inTransaction()) {
                $this->db->commit();
            }

            return ['success' => true, 'message' => 'Da huy don pending va hoan tien cho user.'];
        } catch (Throwable $e) {
            if ($started && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Khong the huy don luc nay.'];
        }
    }

    private function hydrateOrderRow(array $row): array
    {
        $row['quantity'] = max(1, (int) ($row['quantity'] ?? 1));
        $row['customer_input'] = (string) ($row['customer_input'] ?? '');
        $row['cancel_reason'] = (string) ($row['cancel_reason'] ?? '');
        $row['order_code_short'] = $this->makeShortOrderDisplayCode((string) ($row['order_code'] ?? ''));

        if (isset($row['stock_content']) && is_string($row['stock_content']) && $row['stock_content'] !== '') {
            if ($this->crypto instanceof CryptoService && $this->crypto->isEnabled()) {
                $row['stock_content_plain'] = $this->crypto->decryptString($row['stock_content']);
            } else {
                $row['stock_content_plain'] = $row['stock_content'];
            }
        } else {
            $row['stock_content_plain'] = '';
        }

        return $row;
    }

    private function buildUserHistoryWhere(int $userId, array $filters = []): array
    {
        $where = ["`user_id` = ?"];
        $params = [$userId];
        if ($this->hasColumn('user_deleted_at')) {
            $where[] = "`user_deleted_at` IS NULL";
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = "(`order_code` LIKE ? OR `product_name` LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $timeRange = trim((string) ($filters['time_range'] ?? ''));
        if ($timeRange !== '') {
            $separator = (strpos($timeRange, ' to ') !== false) ? ' to ' : ' - ';
            $range = explode($separator, $timeRange, 2);
            if (count($range) === 2) {
                $from = trim($range[0]);
                $to = trim($range[1]);
                if ($from !== '' && $to !== '') {
                    $where[] = "DATE(`created_at`) BETWEEN ? AND ?";
                    $params[] = $from;
                    $params[] = $to;
                }
            }
        }

        $sortDate = trim((string) ($filters['sort_date'] ?? 'all'));
        if ($sortDate !== '' && $sortDate !== 'all') {
            if ($sortDate === 'today') {
                $where[] = "DATE(`created_at`) = CURDATE()";
            } else {
                $days = (int) $sortDate;
                if ($days > 0) {
                    $where[] = "`created_at` >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
                }
            }
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);
        return [$whereSql, $params];
    }

    private function makeShortOrderDisplayCode(string $orderCode): string
    {
        if ($orderCode === '') {
            return '';
        }
        return strtoupper(substr(hash('sha256', $orderCode), 0, 8));
    }

    /**
     * @return array<int,string>
     */
    private function tokenizeSmartSearch(string $search): array
    {
        $normalized = $this->normalizeSmartSearchText($search);
        if ($normalized === '') {
            return [];
        }
        $parts = preg_split('/\s+/u', $normalized) ?: [];
        return array_values(array_filter($parts, static fn($p) => $p !== ''));
    }

    private function normalizeSmartSearchText(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $value = mb_strtolower($value, 'UTF-8');
        $value = strtr($value, [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
        ]);
        $value = preg_replace('/[^\p{L}\p{N}\s\-\+]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim($value);
    }
}

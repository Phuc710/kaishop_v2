<?php

/**
 * Order Model (single-product purchase records).
 */
class Order extends Model
{
    protected $table = 'orders';
    private ?CryptoService $crypto = null;
    private ProductStock $productStockModel;
    private ?BalanceChangeService $balanceChangeService = null;
    protected ?TimeService $timeService = null;
    private array $columnsCache = [];
    private static bool $historyIndexesEnsured = false;

    public function __construct()
    {
        parent::__construct();
        $this->productStockModel = new ProductStock();
        $this->timeService = class_exists('TimeService') ? TimeService::instance() : null;
        $this->balanceChangeService = class_exists('BalanceChangeService') ? new BalanceChangeService($this->db) : null;
        if (class_exists('CryptoService')) {
            $this->crypto = new CryptoService();
        }
        $this->ensureHistoryIndexes();
    }

    public function generateOrderCode(): string
    {
        return strtoupper(bin2hex(random_bytes(4)));
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
        $where = ['`id` = ?', '`user_id` = ?'];
        $params = [$id, $userId];
        if ($this->hasColumn('user_deleted_at')) {
            $where[] = '`user_deleted_at` IS NULL';
        }

        $stmt = $this->db->prepare("
            SELECT *
            FROM `{$this->table}`
            WHERE " . implode(' AND ', $where) . "
            LIMIT 1
        ");
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ? $this->hydrateOrderRow($row) : null;
    }

    public function hideForUser(int $id, int $userId): array
    {
        if ($id <= 0 || $userId <= 0) {
            return ['success' => false, 'message' => 'Dữ liệu không hợp lệ.'];
        }

        try {
            $nowSql = $this->timeService ? $this->timeService->nowSql($this->timeService->getDbTimezone()) : date('Y-m-d H:i:s');
            if (!$this->hasColumn('user_deleted_at')) {
                return ['success' => false, 'message' => 'CSDL chua co cot user_deleted_at de an lich su don hang.'];
            }

            $stmt = $this->db->prepare("
                UPDATE `{$this->table}`
                SET `user_deleted_at` = ?
                WHERE `id` = ? AND `user_id` = ? AND `user_deleted_at` IS NULL
                LIMIT 1
            ");
            $stmt->execute([$nowSql, $id, $userId]);

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

    public function getUserVisibleOrdersPage(int $userId, array $filters = [], int $offset = 0, int $limit = 10): array
    {
        [$whereSql, $params] = $this->buildUserHistoryWhere($userId, $filters);
        $offset = max(0, $offset);
        $limit = max(1, min(200, $limit));

        $columns = [
            '`id`',
            '`order_code`',
            '`product_name`',
            '`price`',
            '`quantity`',
            '`status`',
            '`created_at`',
            '`fulfilled_at`',
            '`customer_input`',
            '`cancel_reason`',
        ];
        if ($this->hasColumn('stock_content')) {
            $columns[] = '`stock_content`';
        }

        $sql = "
            SELECT " . implode(', ', $columns) . "
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

    public function getProductOrdersQueue(int $productId, array $filters = []): array
    {
        $where = ["`product_id` = ?"];
        $params = [$productId];

        $statusFilter = trim((string) ($filters['status_filter'] ?? ''));
        if ($statusFilter !== '') {
            $where[] = "`status` = ?";
            $params[] = $statusFilter;
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = "(`username` LIKE ? OR `order_code` LIKE ? OR `customer_input` LIKE ? OR UPPER(SUBSTRING(SHA2(`order_code`, 256), 1, 8)) LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . strtoupper($search) . '%';
        }

        $dateFilter = trim((string) ($filters['date_filter'] ?? ''));
        if ($dateFilter !== '' && $dateFilter !== 'all') {
            $days = (int) $dateFilter;
            if ($days > 0) {
                $nowSql = $this->timeService ? $this->timeService->nowSql($this->timeService->getDbTimezone()) : date('Y-m-d H:i:s');
                $where[] = "`created_at` >= DATE_SUB(?, INTERVAL ? DAY)";
                $params[] = $nowSql;
                $params[] = $days;
            }
        }

        $startDate = trim((string) ($filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['end_date'] ?? ''));
        if ($startDate !== '' && $endDate !== '') {
            $where[] = "`created_at` BETWEEN ? AND ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        }

        $limit = max(1, min(500, (int) ($filters['limit'] ?? 20)));
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT * FROM `{$this->table}` {$whereSql} ORDER BY `created_at` DESC LIMIT {$limit}";
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
                'pending', 'processing' => 'dang xu ly cho xu ly pending processing doi',
                'cancelled', 'canceled', 'failed' => 'da huy huy cancelled canceled failed',
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
            if (!in_array($status, ['pending', 'processing', 'completed'], true)) {
                throw new RuntimeException('Don hang nay khong the cap nhat noi dung.');
            }

            $storedContent = ($this->crypto instanceof CryptoService && $this->crypto->isEnabled())
                ? $this->crypto->encryptString($deliveryContent)
                : $deliveryContent;

            $nowSql = TimeService::instance()->nowSql();
            $sets = [
                "`status` = 'completed'",
                "`stock_content` = ?",
                "`fulfilled_by` = ?",
                "`fulfilled_at` = ?",
            ];
            $params = [$storedContent, $adminUsername, $nowSql, $id];
            $sql = "UPDATE `{$this->table}` SET " . implode(', ', $sets) . " WHERE `id` = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            if ($started && $this->db->inTransaction()) {
                $this->db->commit();
            }

            if ($status !== 'completed') {
                $quantity = max(1, (int) ($order['quantity'] ?? 1));
                $totalPrice = (int) ($order['price'] ?? 0);
                $unitPrice = (int) floor($totalPrice / $quantity);

                $this->sendFulfilledOrderMailNonBlocking($order, $nowSql, $deliveryContent);
                $this->notifyAdminOrderCompleted(array_merge($order, [
                    'status' => 'completed',
                    'price' => $unitPrice,
                    'fulfilled_by' => $adminUsername,
                    'fulfilled_at' => $nowSql,
                    'delivery_content' => $deliveryContent,
                    'total_price' => $totalPrice,
                ]));
            }

            return [
                'success' => true,
                'message' => ($status === 'completed' ? 'Da cap nhat noi dung bao hanh.' : 'Da giao noi dung va hoan tat don hang.')
            ];
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

            $userStmt = $this->db->prepare("SELECT id, money FROM `users` WHERE `id` = ? LIMIT 1 FOR UPDATE");
            $userStmt->execute([$userId]);
            $userRow = $userStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$userRow) {
                throw new RuntimeException('Tai khoan nguoi mua khong ton tai.');
            }
            $beforeBalance = (int) ($userRow['money'] ?? 0);
            $afterBalance = $beforeBalance + $price;

            $refundStmt = $this->db->prepare("UPDATE `users` SET `money` = `money` + ? WHERE `id` = ? LIMIT 1");
            $refundStmt->execute([$price, $userId]);
            if ($refundStmt->rowCount() < 1) {
                throw new RuntimeException('Khong the hoan tien cho nguoi dung.');
            }

            if ($this->balanceChangeService) {
                try {
                    $sourceChannel = SourceChannelHelper::fromOrderRow($order);
                    $this->balanceChangeService->record(
                        $userId,
                        $username !== '' ? $username : ('user#' . $userId),
                        $beforeBalance,
                        $price,
                        $afterBalance,
                        'Hoan tien don bi huy: ' . (string) ($order['order_code'] ?? ('#' . $id)),
                        $sourceChannel
                    );
                } catch (Throwable $e) {
                    // Non-blocking in refund flow.
                }
            }

            $sets = [
                "`status` = 'cancelled'",
                "`cancel_reason` = ?",
                "`fulfilled_by` = ?",
                "`fulfilled_at` = ?",
            ];
            $nowSql = $this->timeService ? $this->timeService->nowSql($this->timeService->getDbTimezone()) : date('Y-m-d H:i:s');
            $params = [($reason !== '' ? $reason : null), $adminUsername, $nowSql, $id];

            $updateSql = "UPDATE `{$this->table}` SET " . implode(', ', $sets) . " WHERE `id` = ? LIMIT 1";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute($params);

            $this->productStockModel->releaseByOrderId($id);

            try {
                $activityStmt = $this->db->prepare("
                    INSERT INTO `lich_su_hoat_dong` (`username`, `hoatdong`, `gia`, `time`)
                    VALUES (?, ?, ?, ?)
                ");
                $activityStmt->execute([
                    $username !== '' ? $username : ('user#' . $userId),
                    'Hoan tien don hang bi huy: ' . (string) ($order['product_name'] ?? ('#' . $id)),
                    (string) $price,
                    (string) ($this->timeService ? $this->timeService->nowTs() : time()),
                ]);
            } catch (Throwable $e) {
                // Non-blocking if legacy activity table schema differs.
            }

            if ($started && $this->db->inTransaction()) {
                $this->db->commit();
            }

            $quantity = max(1, (int) ($order['quantity'] ?? 1));
            $totalPrice = (int) ($order['price'] ?? 0);
            $unitPrice = (int) floor($totalPrice / $quantity);
            $this->notifyAdminOrderCancelled(array_merge($order, [
                'status' => 'cancelled',
                'price' => $unitPrice,
                'fulfilled_by' => $adminUsername,
                'fulfilled_at' => $nowSql,
                'cancel_reason' => $reason,
                'total_price' => $totalPrice,
            ]));

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

            $searchLower = mb_strtolower($search, 'UTF-8');

            $statusMatched = [];



            if (strpos($searchLower, 'done') !== false || strpos($searchLower, 'hoàn tất') !== false || strpos($searchLower, 'hoan tat') !== false || strpos($searchLower, 'xong') !== false || strpos($searchLower, 'thành công') !== false || strpos($searchLower, 'thanh cong') !== false || strpos($searchLower, 'completed') !== false) {

                $statusMatched[] = 'completed';

            }

            if (strpos($searchLower, 'chờ') !== false || strpos($searchLower, 'cho') !== false || strpos($searchLower, 'đợi') !== false || strpos($searchLower, 'doi') !== false || strpos($searchLower, 'pending') !== false || strpos($searchLower, 'đang') !== false || strpos($searchLower, 'dang') !== false || strpos($searchLower, 'xử lý') !== false || strpos($searchLower, 'xu ly') !== false || strpos($searchLower, 'processing') !== false) {
                $statusMatched[] = 'pending';
                $statusMatched[] = 'processing';
            }
            if (strpos($searchLower, 'hủy') !== false || strpos($searchLower, 'huy') !== false || strpos($searchLower, 'cancelled') !== false || strpos($searchLower, 'canceled') !== false || strpos($searchLower, 'failed') !== false) {
                $statusMatched[] = 'cancelled';
                $statusMatched[] = 'canceled';
                $statusMatched[] = 'failed';
            }



            $searchConditions = [

                "`order_code` LIKE ?",

                "UPPER(SUBSTRING(SHA2(`order_code`, 256), 1, 8)) LIKE ?",

                "`product_name` LIKE ?",

                "`customer_input` LIKE ?",

                "`cancel_reason` LIKE ?"

            ];

            $likeStr = '%' . $search . '%';

            $searchArray = [$likeStr, $likeStr, $likeStr, $likeStr, $likeStr];



            $numericSearch = preg_replace('/\D+/', '', $search);

            if ($numericSearch !== '') {

                $searchConditions[] = "`price` = ?";

                $searchArray[] = $numericSearch;

                $searchConditions[] = "`quantity` = ?";

                $searchArray[] = $numericSearch;

                $searchConditions[] = "`id` = ?";

                $searchArray[] = $numericSearch;

            }



            if (!empty($statusMatched)) {

                $statusPlaceholders = implode(',', array_fill(0, count($statusMatched), '?'));

                $searchConditions[] = "`status` IN ($statusPlaceholders)";

                $searchArray = array_merge($searchArray, $statusMatched);

            }



            $where[] = '(' . implode(' OR ', $searchConditions) . ')';

            $params = array_merge($params, $searchArray);

        }

        $timeRange = trim((string) ($filters['time_range'] ?? ''));
        if ($timeRange !== '') {
            $separator = (strpos($timeRange, ' to ') !== false) ? ' to ' : ' - ';
            $range = explode($separator, $timeRange, 2);
            if (count($range) === 2) {
                $from = trim($range[0]);
                $to = trim($range[1]);
                if ($from !== '' && $to !== '') {
                    $where[] = "`created_at` >= ? AND `created_at` <= ?";
                    $params[] = $from . ' 00:00:00';
                    $params[] = $to . ' 23:59:59';
                }
            }
        }

        $sortDate = trim((string) ($filters['sort_date'] ?? 'all'));
        if ($sortDate !== '' && $sortDate !== 'all') {
            if ($sortDate === 'today') {
                $where[] = "`created_at` >= CURDATE() AND `created_at` < DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
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

    private function hasColumn(string $column): bool
    {
        if ($column === '') {
            return false;
        }

        if ($this->columnsCache === []) {
            $stmt = $this->db->query("SHOW COLUMNS FROM `{$this->table}`");
            $this->columnsCache = array_map(static function ($row) {
                return (string) ($row['Field'] ?? '');
            }, $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []);
        }

        return in_array($column, $this->columnsCache, true);
    }

    private function ensureHistoryIndexes(): void
    {
        if (self::$historyIndexesEnsured) {
            return;
        }
        self::$historyIndexesEnsured = true;

        try {
            if ($this->hasColumn('user_deleted_at')) {
                if (!$this->indexExists($this->table, 'idx_orders_user_deleted_created')) {
                    $this->db->exec("ALTER TABLE `{$this->table}` ADD KEY `idx_orders_user_deleted_created` (`user_id`, `user_deleted_at`, `created_at`, `id`)");
                }
            } elseif (!$this->indexExists($this->table, 'idx_orders_user_created_id')) {
                $this->db->exec("ALTER TABLE `{$this->table}` ADD KEY `idx_orders_user_created_id` (`user_id`, `created_at`, `id`)");
            }
        } catch (Throwable $e) {
            // Non-blocking if schema changes are unavailable.
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?');
        $stmt->execute([$table, $indexName]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function makeShortOrderDisplayCode(string $orderCode): string
    {
        if ($orderCode === '') {
            return '';
        }
        return strtoupper(substr(hash('sha256', $orderCode), 0, 8));
    }

    /**
     * @param array<string,mixed> $order
     */
    private function sendFulfilledOrderMailNonBlocking(array $order, string $fulfilledAt, string $deliveryContent): void
    {
        if (SourceChannelHelper::fromOrderRow($order) !== SourceChannelHelper::WEB) {
            return;
        }

        $userId = (int) ($order['user_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        try {
            $userStmt = $this->db->prepare("SELECT `id`, `username`, `email` FROM `users` WHERE `id` = ? LIMIT 1");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$user || trim((string) ($user['email'] ?? '')) === '') {
                return;
            }

            $product = [];
            $productId = (int) ($order['product_id'] ?? 0);
            if ($productId > 0) {
                $productStmt = $this->db->prepare("SELECT `id`, `name`, `image`, `product_type`, `requires_info`, `source_link`, `info_instructions` FROM `products` WHERE `id` = ? LIMIT 1");
                $productStmt->execute([$productId]);
                $product = $productStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            }

            $quantity = max(1, (int) ($order['quantity'] ?? 1));
            $totalPrice = (int) ($order['price'] ?? 0);
            $unitPrice = (int) floor($totalPrice / max(1, $quantity));
            $orderedAtDisplay = $this->timeService
                ? $this->timeService->formatDisplay($fulfilledAt, 'H:i:s d/m/Y')
                : date('H:i:s d/m/Y', strtotime($fulfilledAt) ?: time());

            if (!class_exists('MailService')) {
                require_once __DIR__ . '/../Services/MailService.php';
            }

            (new MailService())->sendOrderSuccess($user, [
                'order_code' => (string) ($order['order_code'] ?? ''),
                'order_code_short' => $this->makeShortOrderDisplayCode((string) ($order['order_code'] ?? '')),
                'product_name' => (string) ($order['product_name'] ?? ($product['name'] ?? 'San pham')),
                'product_type' => (string) ($product['product_type'] ?? 'account'),
                'requires_info' => (int) ($product['requires_info'] ?? 0),
                'delivery_mode' => ($product !== [] && (string) ($product['product_type'] ?? '') === 'link')
                    ? 'source_link'
                    : (((int) ($product['requires_info'] ?? 0) === 1 || (string) ($order['status'] ?? '') === 'pending')
                        ? 'manual_info'
                        : 'account_stock'),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'status' => 'completed',
                'ordered_at' => $orderedAtDisplay,
                'created_at' => (string) ($order['created_at'] ?? $fulfilledAt),
                'customer_input' => (string) ($order['customer_input'] ?? ''),
                'delivery_content' => $deliveryContent,
                'source_link' => (string) ($product['source_link'] ?? ''),
                'info_instructions' => (string) ($product['info_instructions'] ?? ''),
            ], is_array($product) ? $product : []);
        } catch (Throwable $e) {
            // Non-blocking
        }
    }

    /**
     * @param array<string,mixed> $order
     */
    private function notifyAdminOrderCompleted(array $order): void
    {
        if (!class_exists('OrderNotificationService')) {
            return;
        }

        try {
            (new OrderNotificationService())->notifyAdminCompletedOrder($order);
        } catch (Throwable $e) {
            // Non-blocking
        }
    }

    /**
     * @param array<string,mixed> $order
     */
    private function notifyAdminOrderCancelled(array $order): void
    {
        if (!class_exists('OrderNotificationService')) {
            return;
        }

        try {
            (new OrderNotificationService())->notifyAdminCancelledOrder($order);
        } catch (Throwable $e) {
            // Non-blocking
        }
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
            'à' => 'a',
            'á' => 'a',
            'ạ' => 'a',
            'ả' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ầ' => 'a',
            'ấ' => 'a',
            'ậ' => 'a',
            'ẩ' => 'a',
            'ẫ' => 'a',
            'ă' => 'a',
            'ằ' => 'a',
            'ắ' => 'a',
            'ặ' => 'a',
            'ẳ' => 'a',
            'ẵ' => 'a',
            'è' => 'e',
            'é' => 'e',
            'ẹ' => 'e',
            'ẻ' => 'e',
            'ẽ' => 'e',
            'ê' => 'e',
            'ề' => 'e',
            'ế' => 'e',
            'ệ' => 'e',
            'ể' => 'e',
            'ễ' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'ị' => 'i',
            'ỉ' => 'i',
            'ĩ' => 'i',
            'ò' => 'o',
            'ó' => 'o',
            'ọ' => 'o',
            'ỏ' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ồ' => 'o',
            'ố' => 'o',
            'ộ' => 'o',
            'ổ' => 'o',
            'ỗ' => 'o',
            'ơ' => 'o',
            'ờ' => 'o',
            'ớ' => 'o',
            'ợ' => 'o',
            'ở' => 'o',
            'ỡ' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'ụ' => 'u',
            'ủ' => 'u',
            'ũ' => 'u',
            'ư' => 'u',
            'ừ' => 'u',
            'ứ' => 'u',
            'ự' => 'u',
            'ử' => 'u',
            'ữ' => 'u',
            'ỳ' => 'y',
            'ý' => 'y',
            'ỵ' => 'y',
            'ỷ' => 'y',
            'ỹ' => 'y',
            'đ' => 'd',
        ]);
        $value = preg_replace('/[^\p{L}\p{N}\s\-\+]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim($value);
    }

    public function getSourceTrend(int $days = 7): array
    {
        $sql = "SELECT DATE(created_at) as date,
                       SUM(CASE WHEN source_channel = 1 THEN 1 ELSE 0 END) as tele_orders,
                       SUM(CASE WHEN source_channel = 0 THEN 1 ELSE 0 END) as web_orders
                FROM `{$this->table}`
                WHERE `created_at` >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        return $this->query($sql, [$days - 1])->fetchAll(PDO::FETCH_ASSOC);
    }
}

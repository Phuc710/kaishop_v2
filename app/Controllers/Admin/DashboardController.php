<?php

/**
 * Admin Dashboard Controller
 */
class DashboardController extends Controller
{
    private $authService;
    private $userModel;
    private $db;
    private ?TimeService $timeService = null;
    private array $schemaCache = [];

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userModel = new User();
        $this->db = $this->userModel->getConnection();
        $this->timeService = class_exists('TimeService') ? TimeService::instance() : null;
    }

    /**
     * Check admin access
     */
    private function requireAdmin()
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();

        if (!isset($user['level']) || $user['level'] != 9) {
            http_response_code(403);
            die('Truy cap bi tu choi - Chi danh cho quan tri vien');
        }
    }

    public function index()
    {
        $this->requireAdmin();
        global $chungapi;

        if (empty($chungapi) && class_exists('Config')) {
            $chungapi = Config::getSiteConfig();
        }

        $rangeKey = $this->normalizeRange((string) $this->get('range', 'all'));
        $rangeMeta = $this->resolveRangeMeta($rangeKey);

        $userStats = $this->fetchUserStats();
        $revenueStats = $this->fetchRevenueStats($rangeMeta);

        $prevRangeMeta = $this->resolvePreviousRangeMeta($rangeKey, $rangeMeta);
        $prevRevenueStats = $this->fetchRevenueStats($prevRangeMeta);

        $channelBreakdown = $this->fetchChannelBreakdown($rangeMeta, (int) ($revenueStats['spend_total'] ?? 0), (int) ($revenueStats['orders_sold'] ?? 0));
        $chartData = $this->buildRevenueChartData($rangeKey, $rangeMeta);
        $topProducts = $this->fetchTopProducts($rangeMeta, 12);
        $recentOrders = $this->fetchRecentOrders($rangeMeta, 20);
        $recentDeposits = $this->fetchRecentDeposits($rangeMeta, 20);

        $lowStockProducts = $this->fetchLowStockProducts(10);
        $topDepositors = $this->fetchTopDepositors($rangeMeta, 10);
        $topSpenders = $this->fetchTopSpenders($rangeMeta, 10);

        $this->view('admin/dashboard', [
            'chungapi' => $chungapi,
            'activeRange' => $rangeKey,
            'rangeMeta' => $rangeMeta,
            'totalUsers' => (int) ($userStats['total_users'] ?? 0),
            'totalBanned' => (int) ($userStats['total_banned'] ?? 0),
            'totalMoney' => (int) ($userStats['total_money'] ?? 0),
            'totalProducts' => (int) ($userStats['total_products'] ?? 0),
            'totalUserDeposited' => (int) ($userStats['total_user_deposit'] ?? 0),
            'revenueStats' => $revenueStats,
            'prevRevenueStats' => $prevRevenueStats,
            'channelBreakdown' => $channelBreakdown,
            'chartData' => $chartData,
            'topProducts' => $topProducts,
            'recentOrders' => $recentOrders,
            'recentDeposits' => $recentDeposits,
            'lowStockProducts' => $lowStockProducts,
            'topDepositors' => $topDepositors,
            'topSpenders' => $topSpenders,
        ]);
    }

    private function normalizeRange(string $range): string
    {
        $range = strtolower(trim($range));
        $allowed = ['all', 'today', 'week', 'month', 'quarter', 'year'];
        return in_array($range, $allowed, true) ? $range : 'all';
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveRangeMeta(string $range): array
    {
        $now = $this->timeService
            ? $this->timeService->nowDateTime($this->timeService->getDbTimezone())
            : new DateTimeImmutable('now');

        $start = null;
        $end = $now;
        $label = 'Tất cả';

        switch ($range) {
            case 'today':
                $label = 'Hôm nay';
                $start = $now->setTime(0, 0, 0);
                break;
            case 'week':
                $label = 'Tuần này';
                $start = $now->modify('monday this week')->setTime(0, 0, 0);
                break;
            case 'month':
                $label = 'Tháng này';
                $start = $now->modify('first day of this month')->setTime(0, 0, 0);
                break;
            case 'quarter':
                $label = 'Quý này';
                $month = (int) $now->format('n');
                $quarterStartMonth = (int) (floor(($month - 1) / 3) * 3) + 1;
                $start = $now->setDate((int) $now->format('Y'), $quarterStartMonth, 1)->setTime(0, 0, 0);
                break;
            case 'year':
                $label = 'Năm này';
                $start = $now->setDate((int) $now->format('Y'), 1, 1)->setTime(0, 0, 0);
                break;
            default:
                $label = 'Tất cả';
                $start = null;
                $end = null;
                break;
        }

        $rangeText = 'Toàn bộ dữ liệu';
        if ($start instanceof DateTimeImmutable && $end instanceof DateTimeImmutable) {
            $rangeText = $start->format('H:i d/m/Y') . ' - ' . $end->format('H:i d/m/Y');
        }

        return [
            'key' => $range,
            'label' => $label,
            'range_text' => $rangeText,
            'start_sql' => $start instanceof DateTimeImmutable ? $start->format('Y-m-d H:i:s') : null,
            'end_sql' => $end instanceof DateTimeImmutable ? $end->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function fetchUserStats(): array
    {
        $stats = [
            'total_users' => 0,
            'total_banned' => 0,
            'total_money' => 0,
            'total_products' => 0,
            'total_user_deposit' => 0,
        ];

        try {
            if ($this->tableExists('users')) {
                $sql = "
                    SELECT
                        SUM(CASE WHEN `level` = 0 THEN 1 ELSE 0 END) AS total_users,
                        SUM(CASE WHEN `level` = 0 AND `bannd` = 1 THEN 1 ELSE 0 END) AS total_banned,
                        COALESCE(SUM(CASE WHEN `level` = 0 AND `money` >= 0 THEN `money` ELSE 0 END), 0) AS total_money,
                        COALESCE(SUM(CASE WHEN `level` = 0 AND `tong_nap` > 0 THEN `tong_nap` ELSE 0 END), 0) AS total_user_deposit
                    FROM `users`
                ";
                $row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];
                $stats['total_users'] = (int) ($row['total_users'] ?? 0);
                $stats['total_banned'] = (int) ($row['total_banned'] ?? 0);
                $stats['total_money'] = (int) ($row['total_money'] ?? 0);
                $stats['total_user_deposit'] = (int) ($row['total_user_deposit'] ?? 0);
            }

            if ($this->tableExists('products')) {
                $stats['total_products'] = (int) $this->db->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
            }
        } catch (Throwable $e) {
            // Non-blocking dashboard fallback.
        }

        return $stats;
    }

    /**
     * @param array<string,mixed> $rangeMeta
     * @return array<string,int>
     */
    private function fetchRevenueStats(array $rangeMeta): array
    {
        $stats = [
            'spend_total' => 0,
            'spend_web' => 0,
            'spend_telegram' => 0,
            'revenue_total' => 0,
            'revenue_web' => 0,
            'revenue_telegram' => 0,
            'orders_sold' => 0,
            'orders_all' => 0,
            'deposit_total' => 0,
            'deposit_count' => 0,
            'net_flow' => 0,
        ];

        try {
            if ($this->tableExists('orders')) {
                $params = [];
                $whereSql = "WHERE 1=1";
                if ($this->hasColumn('orders', 'created_at')) {
                    $whereSql .= $this->buildRangeWhere('o.`created_at`', $rangeMeta, $params, 'ord');
                }

                $soldCondition = $this->hasColumn('orders', 'status')
                    ? "o.`status` IN ('pending','processing','completed')"
                    : "1=1";

                $telegramCondition = $this->buildTelegramOrderCondition('o');
                $webCondition = "NOT ({$telegramCondition})";

                $sql = "
                    SELECT
                        COALESCE(SUM(CASE WHEN {$soldCondition} THEN o.`price` ELSE 0 END), 0) AS spend_total,
                        COALESCE(SUM(CASE WHEN {$soldCondition} AND {$webCondition} THEN o.`price` ELSE 0 END), 0) AS spend_web,
                        COALESCE(SUM(CASE WHEN {$soldCondition} AND {$telegramCondition} THEN o.`price` ELSE 0 END), 0) AS spend_telegram,
                        SUM(CASE WHEN {$soldCondition} THEN 1 ELSE 0 END) AS orders_sold,
                        COUNT(*) AS orders_all
                    FROM `orders` o
                    {$whereSql}
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $stats['spend_web'] = (int) ($row['spend_web'] ?? 0);
                $stats['spend_telegram'] = (int) ($row['spend_telegram'] ?? 0);
                $stats['spend_total'] = $stats['spend_web'] + $stats['spend_telegram'];
                $stats['orders_sold'] = (int) ($row['orders_sold'] ?? 0);
                $stats['orders_all'] = (int) ($row['orders_all'] ?? 0);
            }

            if ($this->tableExists('history_nap_bank') && $this->hasColumn('history_nap_bank', 'thucnhan')) {
                $params = [];
                $whereSql = "WHERE " . $this->buildSuccessfulDepositCondition('h');
                if ($this->hasColumn('history_nap_bank', 'created_at')) {
                    $whereSql .= $this->buildRangeWhere('h.`created_at`', $rangeMeta, $params, 'dep');
                }

                $sql = "
                    SELECT
                        COALESCE(SUM(h.`thucnhan`), 0) AS deposit_total,
                        COUNT(*) AS deposit_count
                    FROM `history_nap_bank` h
                    {$whereSql}
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $stats['deposit_total'] = (int) ($row['deposit_total'] ?? 0);
                $stats['deposit_count'] = (int) ($row['deposit_count'] ?? 0);
            }

            // Keep legacy keys for backward compatibility in old views/widgets.
            $stats['revenue_total'] = $stats['spend_total'];
            $stats['revenue_web'] = $stats['spend_web'];
            $stats['revenue_telegram'] = $stats['spend_telegram'];
            $stats['net_flow'] = $stats['deposit_total'] - ($stats['spend_web'] + $stats['spend_telegram']);
        } catch (Throwable $e) {
            // Non-blocking dashboard fallback.
        }

        return $stats;
    }

    /**
     * @param array<string,mixed> $rangeMeta
     * @return array<int,array<string,mixed>>
     */
    private function fetchChannelBreakdown(array $rangeMeta, int $totalRevenue, int $totalOrders): array
    {
        $defaultRows = [
            [
                'channel_key' => 'web',
                'channel_label' => 'Web',
                'orders_count' => 0,
                'revenue_total' => 0,
                'orders_ratio' => 0,
                'revenue_ratio' => 0,
            ],
            [
                'channel_key' => 'telegram',
                'channel_label' => 'Telegram Bot',
                'orders_count' => 0,
                'revenue_total' => 0,
                'orders_ratio' => 0,
                'revenue_ratio' => 0,
            ],
        ];

        if (!$this->tableExists('orders')) {
            return $defaultRows;
        }

        try {
            $params = [];
            $whereSql = "WHERE 1=1";
            if ($this->hasColumn('orders', 'created_at')) {
                $whereSql .= $this->buildRangeWhere('o.`created_at`', $rangeMeta, $params, 'ch');
            }

            $soldCondition = $this->hasColumn('orders', 'status')
                ? "o.`status` IN ('pending','processing','completed')"
                : "1=1";
            $telegramCondition = $this->buildTelegramOrderCondition('o');

            $sql = "
                SELECT
                    CASE WHEN {$telegramCondition} THEN 'telegram' ELSE 'web' END AS channel_key,
                    COUNT(*) AS orders_count,
                    COALESCE(SUM(o.`price`), 0) AS revenue_total
                FROM `orders` o
                {$whereSql}
                  AND {$soldCondition}
                GROUP BY channel_key
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as $row) {
                $key = ((string) ($row['channel_key'] ?? 'web')) === 'telegram' ? 'telegram' : 'web';
                foreach ($defaultRows as &$target) {
                    if ($target['channel_key'] !== $key) {
                        continue;
                    }
                    $target['orders_count'] = (int) ($row['orders_count'] ?? 0);
                    $target['revenue_total'] = (int) ($row['revenue_total'] ?? 0);
                    break;
                }
                unset($target);
            }
        } catch (Throwable $e) {
            return $defaultRows;
        }

        foreach ($defaultRows as &$row) {
            $row['orders_ratio'] = $totalOrders > 0
                ? round(((int) $row['orders_count'] / $totalOrders) * 100, 1)
                : 0;
            $row['revenue_ratio'] = $totalRevenue > 0
                ? round(((int) $row['revenue_total'] / $totalRevenue) * 100, 1)
                : 0;
        }
        unset($row);

        return $defaultRows;
    }

    /**
     * @param array<string,mixed> $rangeMeta
     * @return array<int,array<string,mixed>>
     */
    private function fetchTopProducts(array $rangeMeta, int $limit = 12): array
    {
        if (!$this->tableExists('orders')) {
            return [];
        }

        $limit = max(1, min(50, $limit));

        try {
            $params = [];
            $whereSql = "WHERE 1=1";
            if ($this->hasColumn('orders', 'created_at')) {
                $whereSql .= $this->buildRangeWhere('o.`created_at`', $rangeMeta, $params, 'tp');
            }
            if ($this->hasColumn('orders', 'status')) {
                $whereSql .= " AND o.`status` IN ('pending','processing','completed')";
            }

            $quantityExpr = $this->hasColumn('orders', 'quantity')
                ? "COALESCE(SUM(o.`quantity`), 0)"
                : "COUNT(*)";

            $sql = "
                SELECT
                    o.`product_name`,
                    COUNT(*) AS orders_count,
                    {$quantityExpr} AS quantity_total,
                    COALESCE(SUM(o.`price`), 0) AS revenue_total
                FROM `orders` o
                {$whereSql}
                GROUP BY o.`product_name`
                ORDER BY quantity_total DESC, orders_count DESC, revenue_total DESC
                LIMIT {$limit}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as &$row) {
                $row['orders_count'] = (int) ($row['orders_count'] ?? 0);
                $row['quantity_total'] = (int) ($row['quantity_total'] ?? 0);
                $row['revenue_total'] = (int) ($row['revenue_total'] ?? 0);
                $row['product_name'] = trim((string) ($row['product_name'] ?? ''));
            }
            unset($row);

            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<string,mixed> $rangeMeta
     * @return array<int,array<string,mixed>>
     */
    private function fetchRecentOrders(array $rangeMeta, int $limit = 20): array
    {
        if (!$this->tableExists('orders')) {
            return [];
        }

        $limit = max(1, min(100, $limit));

        try {
            $params = [];
            $whereSql = "WHERE 1=1";
            if ($this->hasColumn('orders', 'created_at')) {
                $whereSql .= $this->buildRangeWhere('o.`created_at`', $rangeMeta, $params, 'ro');
            }

            $select = [
                "o.`id`",
                "o.`order_code`",
                "o.`username`",
                "o.`product_name`",
                "o.`price`",
                $this->hasColumn('orders', 'quantity') ? "o.`quantity`" : "1 AS quantity",
                $this->hasColumn('orders', 'status') ? "o.`status`" : "'completed' AS status",
                $this->hasColumn('orders', 'source') ? "o.`source`" : "'0' AS source",
                $this->hasColumn('orders', 'telegram_id') ? "o.`telegram_id`" : "NULL AS telegram_id",
                $this->hasColumn('orders', 'created_at') ? "o.`created_at`" : "NULL AS created_at",
            ];

            $sql = "
                SELECT " . implode(",\n                    ", $select) . "
                FROM `orders` o
                {$whereSql}
                ORDER BY " . ($this->hasColumn('orders', 'created_at') ? "o.`created_at` DESC, o.`id` DESC" : "o.`id` DESC") . "
                LIMIT {$limit}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as &$row) {
                $row['id'] = (int) ($row['id'] ?? 0);
                $row['price'] = (int) ($row['price'] ?? 0);
                $row['quantity'] = max(1, (int) ($row['quantity'] ?? 1));
                $row['status'] = trim((string) ($row['status'] ?? ''));
                $row['source'] = trim((string) ($row['source'] ?? ''));
                $row['telegram_id'] = (int) ($row['telegram_id'] ?? 0);
                $row['channel_key'] = $this->isTelegramOrderRow($row) ? 'telegram' : 'web';
                $row['channel_label'] = $row['channel_key'] === 'telegram' ? 'Telegram Bot' : 'Web';
                $row['status_label'] = $this->formatOrderStatusLabel($row['status']);
                $row['created_display'] = $this->formatDashboardDateTime($row['created_at'] ?? '');
            }
            unset($row);

            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<string,mixed> $rangeMeta
     * @return array<int,array<string,mixed>>
     */
    private function fetchRecentDeposits(array $rangeMeta, int $limit = 20): array
    {
        if (
            !$this->tableExists('history_nap_bank')
            || !$this->hasColumn('history_nap_bank', 'thucnhan')
        ) {
            return [];
        }

        $limit = max(1, min(100, $limit));

        try {
            $params = [];
            $whereSql = "WHERE " . $this->buildSuccessfulDepositCondition('h');
            if ($this->hasColumn('history_nap_bank', 'created_at')) {
                $whereSql .= $this->buildRangeWhere('h.`created_at`', $rangeMeta, $params, 'rd');
            }

            $select = [
                "h.`id`",
                $this->hasColumn('history_nap_bank', 'trans_id') ? "h.`trans_id`" : "NULL AS trans_id",
                $this->hasColumn('history_nap_bank', 'username') ? "h.`username`" : "NULL AS username",
                $this->hasColumn('history_nap_bank', 'type') ? "h.`type`" : "NULL AS type",
                $this->hasColumn('history_nap_bank', 'ctk') ? "h.`ctk`" : "NULL AS ctk",
                "h.`thucnhan`",
                $this->hasColumn('history_nap_bank', 'status') ? "h.`status`" : "NULL AS status",
                $this->hasColumn('history_nap_bank', 'created_at') ? "h.`created_at`" : "NULL AS created_at",
            ];

            $sql = "
                SELECT " . implode(",\n                    ", $select) . "
                FROM `history_nap_bank` h
                {$whereSql}
                ORDER BY " . ($this->hasColumn('history_nap_bank', 'created_at') ? "h.`created_at` DESC, h.`id` DESC" : "h.`id` DESC") . "
                LIMIT {$limit}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as &$row) {
                $row['id'] = (int) ($row['id'] ?? 0);
                $row['thucnhan'] = (int) ($row['thucnhan'] ?? 0);
                $row['status'] = trim((string) ($row['status'] ?? ''));
                $row['status_label'] = $this->formatDepositStatusLabel($row['status']);
                $row['created_display'] = $this->formatDashboardDateTime($row['created_at'] ?? '');
            }
            unset($row);

            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<string,mixed> $rangeMeta
     * @return array<string,mixed>
     */
    private function buildRevenueChartData(string $rangeKey, array $rangeMeta): array
    {
        $labels = [];
        $seriesTotal = [];
        $seriesWeb = [];
        $seriesTelegram = [];
        $seriesDeposit = [];
        $windowLabel = '';

        [$windowStart, $windowEnd, $windowLabel] = $this->resolveChartWindow($rangeKey, $rangeMeta);
        if (!$windowStart instanceof DateTimeImmutable || !$windowEnd instanceof DateTimeImmutable) {
            return [
                'labels' => [],
                'revenue_total' => [],
                'revenue_web' => [],
                'revenue_telegram' => [],
                'deposit_total' => [],
                'window_label' => '',
            ];
        }

        $dataMap = [];
        try {
            if ($this->tableExists('orders')) {
                $soldCondition = $this->hasColumn('orders', 'status')
                    ? "o.`status` IN ('pending','processing','completed')"
                    : "1=1";
                $telegramCondition = $this->buildTelegramOrderCondition('o');
                $webCondition = "NOT ({$telegramCondition})";

                $sql = "
                    SELECT
                        DATE(o.`created_at`) AS day_key,
                        COALESCE(SUM(CASE WHEN {$soldCondition} THEN o.`price` ELSE 0 END), 0) AS revenue_total,
                        COALESCE(SUM(CASE WHEN {$soldCondition} AND {$webCondition} THEN o.`price` ELSE 0 END), 0) AS revenue_web,
                        COALESCE(SUM(CASE WHEN {$soldCondition} AND {$telegramCondition} THEN o.`price` ELSE 0 END), 0) AS revenue_telegram
                    FROM `orders` o
                    WHERE o.`created_at` >= :start_at AND o.`created_at` <= :end_at
                    GROUP BY DATE(o.`created_at`)
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'start_at' => $windowStart->format('Y-m-d H:i:s'),
                    'end_at' => $windowEnd->format('Y-m-d H:i:s'),
                ]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                foreach ($rows as $row) {
                    $day = (string) ($row['day_key'] ?? '');
                    if ($day === '') {
                        continue;
                    }
                    $revenueWeb = (int) ($row['revenue_web'] ?? 0);
                    $revenueTelegram = (int) ($row['revenue_telegram'] ?? 0);
                    $dataMap[$day] = [
                        'revenue_total' => $revenueWeb + $revenueTelegram,
                        'revenue_web' => $revenueWeb,
                        'revenue_telegram' => $revenueTelegram,
                        'deposit_total' => 0,
                    ];
                }
            }

            if ($this->tableExists('history_nap_bank') && $this->hasColumn('history_nap_bank', 'thucnhan')) {
                $sql = "
                    SELECT
                        DATE(h.`created_at`) AS day_key,
                        COALESCE(SUM(h.`thucnhan`), 0) AS deposit_total
                    FROM `history_nap_bank` h
                    WHERE " . $this->buildSuccessfulDepositCondition('h') . "
                      AND h.`created_at` >= :start_at
                      AND h.`created_at` <= :end_at
                    GROUP BY DATE(h.`created_at`)
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'start_at' => $windowStart->format('Y-m-d H:i:s'),
                    'end_at' => $windowEnd->format('Y-m-d H:i:s'),
                ]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                foreach ($rows as $row) {
                    $day = (string) ($row['day_key'] ?? '');
                    if ($day === '') {
                        continue;
                    }
                    if (!isset($dataMap[$day])) {
                        $dataMap[$day] = [
                            'revenue_total' => 0,
                            'revenue_web' => 0,
                            'revenue_telegram' => 0,
                            'deposit_total' => 0,
                        ];
                    }
                    $dataMap[$day]['deposit_total'] = (int) ($row['deposit_total'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            return [
                'labels' => [],
                'revenue_total' => [],
                'revenue_web' => [],
                'revenue_telegram' => [],
                'deposit_total' => [],
                'window_label' => $windowLabel,
            ];
        }

        $cursor = $windowStart->setTime(0, 0, 0);
        $cursorEnd = $windowEnd->setTime(0, 0, 0);
        while ($cursor <= $cursorEnd) {
            $dayKey = $cursor->format('Y-m-d');
            $point = $dataMap[$dayKey] ?? [
                'revenue_total' => 0,
                'revenue_web' => 0,
                'revenue_telegram' => 0,
                'deposit_total' => 0,
            ];

            $labels[] = $cursor->format('d/m');
            $seriesTotal[] = (int) ($point['revenue_total'] ?? 0);
            $seriesWeb[] = (int) ($point['revenue_web'] ?? 0);
            $seriesTelegram[] = (int) ($point['revenue_telegram'] ?? 0);
            $seriesDeposit[] = (int) ($point['deposit_total'] ?? 0);
            $cursor = $cursor->modify('+1 day');
        }

        return [
            'labels' => $labels,
            'revenue_total' => $seriesTotal,
            'revenue_web' => $seriesWeb,
            'revenue_telegram' => $seriesTelegram,
            'deposit_total' => $seriesDeposit,
            'window_label' => $windowLabel,
        ];
    }

    /**
     * @param array<string,mixed> $rangeMeta
     * @return array{0:?DateTimeImmutable,1:?DateTimeImmutable,2:string}
     */
    private function resolveChartWindow(string $rangeKey, array $rangeMeta): array
    {
        $now = $this->timeService
            ? $this->timeService->nowDateTime($this->timeService->getDbTimezone())
            : new DateTimeImmutable('now');

        if ($rangeKey === 'all') {
            $start = $now->modify('-29 days')->setTime(0, 0, 0);
            $end = $now;
            return [$start, $end, '30 ngay gan nhat'];
        }

        $startRaw = trim((string) ($rangeMeta['start_sql'] ?? ''));
        $endRaw = trim((string) ($rangeMeta['end_sql'] ?? ''));
        if ($startRaw === '' || $endRaw === '') {
            return [null, null, ''];
        }

        try {
            $tz = new DateTimeZone($this->timeService ? $this->timeService->getDbTimezone() : date_default_timezone_get());
            $start = new DateTimeImmutable($startRaw, $tz);
            $end = new DateTimeImmutable($endRaw, $tz);
            return [$start, $end, (string) ($rangeMeta['label'] ?? '')];
        } catch (Throwable $e) {
            return [null, null, ''];
        }
    }

    /**
     * @param array<string,mixed> $rangeMeta
     * @param array<string,mixed> $params
     */
    private function buildRangeWhere(string $dateExpr, array $rangeMeta, array &$params, string $prefix): string
    {
        $start = (string) ($rangeMeta['start_sql'] ?? '');
        $end = (string) ($rangeMeta['end_sql'] ?? '');
        if ($start === '' || $end === '') {
            return '';
        }

        $params[$prefix . '_start'] = $start;
        $params[$prefix . '_end'] = $end;
        return " AND {$dateExpr} >= :" . $prefix . "_start AND {$dateExpr} <= :" . $prefix . "_end";
    }

    private function buildTelegramOrderCondition(string $alias): string
    {
        $parts = [];
        if ($this->hasColumn('orders', 'source')) {
            $parts[] = "COALESCE(LOWER(TRIM(CAST({$alias}.`source` AS CHAR))), '') IN ('telegram','tele','telebot','bot','tg','1')";
        }
        if ($this->hasColumn('orders', 'telegram_id')) {
            $parts[] = "(COALESCE({$alias}.`telegram_id`, 0) > 0)";
        }

        if (empty($parts)) {
            return "0=1";
        }

        return '(' . implode(' OR ', $parts) . ')';
    }

    private function buildSuccessfulDepositCondition(string $alias): string
    {
        $conditions = ["{$alias}.`thucnhan` > 0"];
        if ($this->hasColumn('history_nap_bank', 'status')) {
            $conditions[] = "("
                . "{$alias}.`status` IS NULL OR {$alias}.`status` = '' OR "
                . "LOWER(TRIM({$alias}.`status`)) IN ('hoantat','thanhcong','success','completed')"
                . ")";
        }
        return implode(' AND ', $conditions);
    }

    /**
     * @param array<string,mixed> $row
     */
    private function isTelegramOrderRow(array $row): bool
    {
        $sourceRaw = trim((string) ($row['source'] ?? ''));
        $source = strtolower($sourceRaw);
        if (
            in_array($source, ['telegram', 'tele', 'telebot', 'bot', 'tg', '1'], true)
            || (is_numeric($sourceRaw) && (int) $sourceRaw === 1)
        ) {
            return true;
        }
        return (int) ($row['telegram_id'] ?? 0) > 0;
    }

    private function formatDashboardDateTime($raw): string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return '--';
        }

        if ($this->timeService) {
            $formatted = $this->timeService->formatDisplay($value, 'H:i d/m/Y', $this->timeService->getDbTimezone());
            if ($formatted !== '') {
                return $formatted;
            }
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return '--';
        }
        return date('H:i d/m/Y', $ts);
    }

    private function formatOrderStatusLabel(string $status): string
    {
        $status = strtolower(trim($status));
        return match ($status) {
            'completed' => 'Hoàn tất',
            'pending' => 'Pending',
            'processing' => 'Đang xử lý',
            'cancelled' => 'Đã hủy',
            default => $status !== '' ? strtoupper($status) : '--',
        };
    }

    private function formatDepositStatusLabel(string $status): string
    {
        $status = strtolower(trim($status));
        return match ($status) {
            'hoantat', 'thanhcong', 'success', 'completed' => 'Hoàn tất',
            'pending' => 'Pending',
            default => $status !== '' ? strtoupper($status) : '--',
        };
    }

    private function resolvePreviousRangeMeta(string $rangeKey, array $rangeMeta): array
    {
        $startSql = $rangeMeta['start_sql'] ?? null;
        $endSql = $rangeMeta['end_sql'] ?? null;

        try {
            $tz = new DateTimeZone($this->timeService ? $this->timeService->getDbTimezone() : date_default_timezone_get());
            if (!$startSql || !$endSql) {
                // For 'all', compare last 30 days vs 30 days before that
                $now = $this->timeService ? $this->timeService->nowDateTime($this->timeService->getDbTimezone()) : new DateTimeImmutable('now', $tz);
                $prevEnd = $now->modify('-30 days')->setTime(23, 59, 59);
                $prevStart = $prevEnd->modify('-30 days')->setTime(0, 0, 0);
            } else {
                $start = new DateTimeImmutable($startSql, $tz);
                $end = new DateTimeImmutable($endSql, $tz);
                $diff = $start->diff($end);

                // Subtract the duration from the current start to get the previous end
                $prevEnd = $start->modify('-1 second');
                // To get previous start, we need to handle the interval carefully
                $prevStart = $prevEnd->sub($diff);
            }

            return [
                'start_sql' => $prevStart->format('Y-m-d H:i:s'),
                'end_sql' => $prevEnd->format('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            return ['start_sql' => null, 'end_sql' => null];
        }
    }

    private function fetchLowStockProducts(int $limit = 10): array
    {
        if (!$this->tableExists('products')) {
            return [];
        }

        try {
            $sql = "
                SELECT p.id, p.name, 
                       COALESCE(s.available, 0) as available_count,
                       p.product_type
                FROM products p
                LEFT JOIN (
                    SELECT product_id, COUNT(*) as available 
                    FROM product_stock 
                    WHERE status = 'available' 
                    GROUP BY product_id
                ) s ON p.id = s.product_id
                WHERE p.status = 'ON'
                  AND (
                    (p.product_type = 'account' AND COALESCE(s.available, 0) < 5)
                    OR (p.product_type = 'link' AND 0 < 0) -- links don't have stock
                  )
                ORDER BY available_count ASC
                LIMIT " . (int) $limit;
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    private function fetchTopSpenders(array $rangeMeta, int $limit = 10): array
    {
        if (!$this->tableExists('orders')) {
            return [];
        }

        try {
            $params = [];
            $whereSql = "WHERE o.status IN ('pending','processing','completed')";
            if ($this->hasColumn('orders', 'created_at')) {
                $whereSql .= $this->buildRangeWhere('o.created_at', $rangeMeta, $params, 'ts');
            }

            $sql = "
                SELECT o.username, SUM(o.price) as total_spent, COUNT(*) as order_count
                FROM orders o
                {$whereSql}
                GROUP BY o.username
                ORDER BY total_spent DESC
                LIMIT " . (int) $limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<string,mixed> $rangeMeta
     * @return array<int,array<string,mixed>>
     */
    private function fetchTopDepositors(array $rangeMeta, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));

        try {
            if (
                $this->tableExists('history_nap_bank')
                && $this->hasColumn('history_nap_bank', 'thucnhan')
                && $this->hasColumn('history_nap_bank', 'username')
            ) {
                $params = [];
                $whereSql = "WHERE " . $this->buildSuccessfulDepositCondition('h');
                if ($this->hasColumn('history_nap_bank', 'created_at')) {
                    $whereSql .= $this->buildRangeWhere('h.created_at', $rangeMeta, $params, 'tdp');
                }

                $sql = "
                    SELECT
                        TRIM(COALESCE(h.username, '')) AS username,
                        COALESCE(SUM(h.thucnhan), 0) AS total_deposit,
                        COUNT(*) AS deposit_count
                    FROM history_nap_bank h
                    {$whereSql}
                    GROUP BY h.username
                    HAVING TRIM(COALESCE(h.username, '')) <> ''
                    ORDER BY total_deposit DESC, deposit_count DESC
                    LIMIT " . (int) $limit;

                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                foreach ($rows as &$row) {
                    $row['username'] = (string) ($row['username'] ?? '');
                    $row['total_deposit'] = (int) ($row['total_deposit'] ?? 0);
                    $row['deposit_count'] = (int) ($row['deposit_count'] ?? 0);
                }
                unset($row);

                return $rows;
            }

            // Fallback all-time from users.tong_nap when deposit history table is unavailable.
            $rangeStart = trim((string) ($rangeMeta['start_sql'] ?? ''));
            $rangeEnd = trim((string) ($rangeMeta['end_sql'] ?? ''));
            $isAllRange = ($rangeStart === '' || $rangeEnd === '');
            if (
                $isAllRange
                && $this->tableExists('users')
                && $this->hasColumn('users', 'username')
                && $this->hasColumn('users', 'tong_nap')
            ) {
                $whereParts = ["COALESCE(u.tong_nap, 0) > 0"];
                if ($this->hasColumn('users', 'level')) {
                    $whereParts[] = "u.level = 0";
                }

                $sql = "
                    SELECT
                        TRIM(COALESCE(u.username, '')) AS username,
                        COALESCE(u.tong_nap, 0) AS total_deposit,
                        0 AS deposit_count
                    FROM users u
                    WHERE " . implode(' AND ', $whereParts) . "
                    ORDER BY total_deposit DESC
                    LIMIT " . (int) $limit;

                $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as &$row) {
                    $row['username'] = (string) ($row['username'] ?? '');
                    $row['total_deposit'] = (int) ($row['total_deposit'] ?? 0);
                    $row['deposit_count'] = (int) ($row['deposit_count'] ?? 0);
                }
                unset($row);

                return $rows;
            }

            return [];
        } catch (Throwable $e) {
            return [];
        }
    }

    private function tableExists(string $table): bool
    {
        $cacheKey = 'table:' . $table;
        if (array_key_exists($cacheKey, $this->schemaCache)) {
            return $this->schemaCache[$cacheKey];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM information_schema.tables
                WHERE table_schema = DATABASE() AND table_name = :table_name
            ");
            $stmt->execute(['table_name' => $table]);
            $exists = (int) $stmt->fetchColumn() > 0;
            $this->schemaCache[$cacheKey] = $exists;
            return $exists;
        } catch (Throwable $e) {
            $this->schemaCache[$cacheKey] = false;
            return false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $cacheKey = 'column:' . $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->schemaCache)) {
            return $this->schemaCache[$cacheKey];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = :table_name
                  AND column_name = :column_name
            ");
            $stmt->execute([
                'table_name' => $table,
                'column_name' => $column,
            ]);
            $exists = (int) $stmt->fetchColumn() > 0;
            $this->schemaCache[$cacheKey] = $exists;
            return $exists;
        } catch (Throwable $e) {
            $this->schemaCache[$cacheKey] = false;
            return false;
        }
    }
}

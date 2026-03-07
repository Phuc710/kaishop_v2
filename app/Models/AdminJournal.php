<?php

/**
 * Admin Journal Model
 * Shared data layer for admin activity/balance log pages.
 */
class AdminJournal extends Model
{
    private $tableExistsCache = [];
    private $tableColumnsCache = [];
    protected ?TimeService $timeService = null;

    public function __construct()
    {
        parent::__construct();
        $this->timeService = class_exists('TimeService') ? TimeService::instance() : null;
        $this->ensureSourceChannelSchema();
    }

    /**
     * Get activity logs with shared filters.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getActivityLogs(array $filters): array
    {
        if (!$this->tableExists('lich_su_hoat_dong')) {
            return [];
        }

        $params = [];
        $conditions = ['1=1'];

        $timeColumn = $this->detectTimeColumn('lich_su_hoat_dong');
        $timeExpr = $this->buildEventTimeExpression('l', $timeColumn);

        $hasUserTable = $this->tableExists('users');
        $hasActivityIp = $this->hasColumn('lich_su_hoat_dong', 'ip');
        $deviceColumn = $this->detectDeviceColumn('lich_su_hoat_dong');

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $searchConditions = [
                'l.username LIKE :search_username',
                'l.hoatdong LIKE :search_action'
            ];
            $params['search_username'] = $search;
            $params['search_action'] = $search;

            if (ctype_digit(trim((string) $filters['search'])) && $hasUserTable) {
                $searchConditions[] = 'u.id = :search_id';
                $params['search_id'] = (int) $filters['search'];
            }

            if ($hasActivityIp) {
                $searchConditions[] = 'l.ip LIKE :search_ip1';
                $params['search_ip1'] = $search;
            } elseif ($hasUserTable && $this->hasColumn('users', 'ip')) {
                $searchConditions[] = 'u.ip LIKE :search_ip2';
                $params['search_ip2'] = $search;
            }

            if ($deviceColumn !== null) {
                $searchConditions[] = "l.`{$deviceColumn}` LIKE :search_dev";
                $params['search_dev'] = $search;
            }

            $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
        }

        $this->appendDateConditions($conditions, $params, $timeExpr, $filters);

        $userJoin = $hasUserTable ? 'LEFT JOIN `users` u ON u.username = l.username' : '';
        $userIdSelect = $hasUserTable && $this->hasColumn('users', 'id') ? 'u.id AS user_id' : 'NULL AS user_id';
        $ipSelect = $hasActivityIp
            ? "COALESCE(NULLIF(TRIM(l.ip), ''), '--') AS ip_address"
            : (($hasUserTable && $this->hasColumn('users', 'ip'))
                ? "COALESCE(NULLIF(TRIM(u.ip), ''), '--') AS ip_address"
                : "'--' AS ip_address");
        $deviceSelect = $deviceColumn !== null
            ? "COALESCE(NULLIF(TRIM(l.`{$deviceColumn}`), ''), '--') AS device_info"
            : "'--' AS device_info";
        $rawTimeSelect = $this->hasColumn('lich_su_hoat_dong', 'time') ? 'l.time AS raw_time' : 'NULL AS raw_time';

        $sql = "
            SELECT
                l.id,
                {$userIdSelect},
                l.username,
                l.hoatdong AS action_name,
                l.gia,
                {$rawTimeSelect},
                {$timeExpr} AS event_time,
                {$ipSelect},
                {$deviceSelect}
            FROM `lich_su_hoat_dong` l
            {$userJoin}
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY event_time DESC, l.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get purchase history from orders table.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getPurchaseHistoryLogs(array $filters): array
    {
        if (!$this->tableExists('orders')) {
            return [];
        }

        $params = [];
        $conditions = ['1=1'];
        $timeExpr = 'o.created_at';
        $sourceExpr = $this->buildOrderSourceChannelExpression('o');

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $conditions[] = '(o.username LIKE :s_user OR o.product_name LIKE :s_prod OR o.order_code LIKE :s_code)';
            $params['s_user'] = $search;
            $params['s_prod'] = $search;
            $params['s_code'] = $search;
        }

        $orderStatus = trim((string) ($filters['order_status'] ?? ''));
        if ($orderStatus !== '' && in_array($orderStatus, ['pending', 'processing', 'completed', 'cancelled'], true)) {
            $conditions[] = 'o.status = :order_status';
            $params['order_status'] = $orderStatus;
        }

        $this->appendSourceChannelCondition($conditions, $params, $sourceExpr, $filters, 'src_order');

        $this->appendDateConditions($conditions, $params, $timeExpr, $filters);

        $hasQuantity = $this->hasColumn('orders', 'quantity');
        $hasCustomerInput = $this->hasColumn('orders', 'customer_input');
        $hasFulfilledBy = $this->hasColumn('orders', 'fulfilled_by');
        $hasFulfilledAt = $this->hasColumn('orders', 'fulfilled_at');

        $sql = "
            SELECT
                o.id,
                o.order_code,
                o.username,
                o.product_name,
                o.price,
                o.status,
                o.payment_method,
                {$sourceExpr} AS source_channel,
                " . ($hasQuantity ? 'o.quantity' : '1') . " AS quantity,
                " . ($hasCustomerInput ? 'o.customer_input' : 'NULL') . " AS customer_input,
                " . ($hasFulfilledBy ? 'o.fulfilled_by' : 'NULL') . " AS fulfilled_by,
                " . ($hasFulfilledAt ? 'o.fulfilled_at' : 'NULL') . " AS fulfilled_at,
                o.created_at AS event_time,
                o.created_at AS raw_time
            FROM `orders` o
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY o.created_at DESC, o.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get balance change logs with shared filters.
     * Falls back to history_nap_bank if no dedicated table exists.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getBalanceChangeLogs(array $filters): array
    {
        $dedicatedRows = [];
        if ($this->tableExists('lich_su_bien_dong_so_du')) {
            $dedicatedRows = $this->getBalanceLogsFromDedicatedTable($filters);
        }

        $bankRows = $this->getBalanceLogsFromBankHistory($filters);

        if (empty($dedicatedRows)) {
            return $bankRows;
        }
        if (empty($bankRows)) {
            return $dedicatedRows;
        }

        return $this->mergeBalanceRows($dedicatedRows, $bankRows);
    }

    /**
     * Get formal deposit logs (Bank/Momo/Cards) for accounting.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getDepositLogs(array $filters): array
    {
        if (!$this->tableExists('history_nap_bank')) {
            return [];
        }

        $params = [];
        $conditions = ['1=1'];
        $hasUsers = $this->tableExists('users');

        $timeColumn = $this->detectTimeColumn('history_nap_bank');
        $timeExpr = $this->buildEventTimeExpression('h', $timeColumn);
        $sourceExpr = $this->buildDepositSourceChannelExpression('h');

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $searchConditions = [
                'h.username LIKE :search_username',
                'h.ctk LIKE :search_reason',
                'h.trans_id LIKE :search_trans'
            ];
            $params['search_username'] = $search;
            $params['search_reason'] = $search;
            $params['search_trans'] = $search;

            if ($this->hasColumn('history_nap_bank', 'stk')) {
                $searchConditions[] = 'h.stk LIKE :search_stk';
                $params['search_stk'] = $search;
            }
            if ($this->hasColumn('history_nap_bank', 'bank_name')) {
                $searchConditions[] = 'h.bank_name LIKE :search_bank_name';
                $params['search_bank_name'] = $search;
            }
            if ($this->hasColumn('history_nap_bank', 'bank_owner')) {
                $searchConditions[] = 'h.bank_owner LIKE :search_bank_owner';
                $params['search_bank_owner'] = $search;
            }

            if (ctype_digit(trim((string) $filters['search'])) && $hasUsers) {
                $searchConditions[] = 'u.id = :search_id';
                $params['search_id'] = (int) $filters['search'];
            }

            $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
        }

        $this->appendDateConditions($conditions, $params, $timeExpr, $filters);
        $this->appendSourceChannelCondition($conditions, $params, $sourceExpr, $filters, 'src_dep');

        $joinUsers = $hasUsers ? 'LEFT JOIN `users` u ON u.username = h.username' : '';
        $userIdSelect = $hasUsers && $this->hasColumn('users', 'id') ? 'u.id AS user_id' : 'NULL AS user_id';

        $sql = "
            SELECT
                h.id,
                {$userIdSelect},
                h.username,
                h.type,
                h.trans_id,
                h.ctk AS reason,
                " . ($this->hasColumn('history_nap_bank', 'stk') ? 'h.stk' : 'NULL AS stk') . ",
                " . ($this->hasColumn('history_nap_bank', 'bank_name') ? 'h.bank_name' : 'NULL AS bank_name') . ",
                " . ($this->hasColumn('history_nap_bank', 'bank_owner') ? 'h.bank_owner' : 'NULL AS bank_owner') . ",
                h.thucnhan AS amount,
                h.status,
                {$sourceExpr} AS source_channel,
                h.time AS raw_time,
                {$timeExpr} AS event_time
            FROM `history_nap_bank` h
            {$joinUsers}
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY event_time DESC, h.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Write user activity log.
     */
    public function writeActivity(string $username, string $activity, $amount = 0): void
    {
        if (!$this->tableExists('lich_su_hoat_dong')) {
            return;
        }

        $sql = "
            INSERT INTO `lich_su_hoat_dong` (`username`, `hoatdong`, `gia`, `time`)
            VALUES (:username, :activity, :amount, :time)
        ";

        $stmt = $this->db->prepare($sql);
        $timeValue = $this->timeService ? (string) $this->timeService->nowTs() : (string) time();
        $stmt->execute([
            'username' => $username,
            'activity' => $activity,
            'amount' => (string) $this->parseAmount($amount),
            'time' => $timeValue,
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    private function getBalanceLogsFromDedicatedTable(array $filters): array
    {
        $params = [];
        $conditions = ['1=1'];

        $timeColumn = $this->detectTimeColumn('lich_su_bien_dong_so_du');
        $timeExpr = $this->buildEventTimeExpression('b', $timeColumn);
        $sourceExpr = $this->buildSimpleSourceChannelExpression('b', 'lich_su_bien_dong_so_du');

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $searchConditions = [];

            if ($this->hasColumn('lich_su_bien_dong_so_du', 'username')) {
                $searchConditions[] = 'b.username LIKE :search_username';
                $params['search_username'] = $search;
            }
            if ($this->hasColumn('lich_su_bien_dong_so_du', 'reason')) {
                $searchConditions[] = 'b.reason LIKE :search_reason';
                $params['search_reason'] = $search;
            }
            if (ctype_digit(trim((string) $filters['search'])) && $this->hasColumn('lich_su_bien_dong_so_du', 'user_id')) {
                $searchConditions[] = 'b.user_id = :search_id';
                $params['search_id'] = (int) $filters['search'];
            }

            if (!empty($searchConditions)) {
                $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
            }
        }

        $this->appendDateConditions($conditions, $params, $timeExpr, $filters);
        $this->appendSourceChannelCondition($conditions, $params, $sourceExpr, $filters, 'src_bal');

        $select = [
            'b.id',
            $this->hasColumn('lich_su_bien_dong_so_du', 'user_id') ? 'b.user_id' : 'NULL AS user_id',
            $this->hasColumn('lich_su_bien_dong_so_du', 'username') ? 'b.username' : "'' AS username",
            $this->hasColumn('lich_su_bien_dong_so_du', 'before_balance') ? 'b.before_balance' : 'NULL AS before_balance',
            $this->hasColumn('lich_su_bien_dong_so_du', 'change_amount') ? 'b.change_amount' : 'NULL AS change_amount',
            $this->hasColumn('lich_su_bien_dong_so_du', 'after_balance') ? 'b.after_balance' : 'NULL AS after_balance',
            $this->hasColumn('lich_su_bien_dong_so_du', 'reason') ? 'b.reason' : "'' AS reason_text",
            "{$sourceExpr} AS source_channel",
            $this->hasColumn('lich_su_bien_dong_so_du', 'time') ? 'b.time AS raw_time' : 'NULL AS raw_time',
            "{$timeExpr} AS event_time",
        ];

        $sql = "
            SELECT " . implode(",\n                ", $select) . "
            FROM `lich_su_bien_dong_so_du` b
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY event_time DESC, b.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    private function getBalanceLogsFromBankHistory(array $filters): array
    {
        if (!$this->tableExists('history_nap_bank')) {
            return [];
        }

        $params = [];
        $conditions = ['1=1'];
        $hasUsers = $this->tableExists('users');

        $timeColumn = $this->detectTimeColumn('history_nap_bank');
        $timeExpr = $this->buildEventTimeExpression('h', $timeColumn);
        $sourceExpr = $this->buildDepositSourceChannelExpression('h');

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $searchConditions = [
                'h.username LIKE :search_username',
                'h.ctk LIKE :search_reason'
            ];
            $params['search_username'] = $search;
            $params['search_reason'] = $search;

            if (ctype_digit(trim((string) $filters['search'])) && $hasUsers) {
                $searchConditions[] = 'u.id = :search_id';
                $params['search_id'] = (int) $filters['search'];
            }

            $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
        }

        // Only completed rows are valid balance events.
        if ($this->hasColumn('history_nap_bank', 'status')) {
            $conditions[] = "(h.status IS NULL OR h.status = '' OR h.status = 'hoantat')";
        }

        $this->appendDateConditions($conditions, $params, $timeExpr, $filters);
        $this->appendSourceChannelCondition($conditions, $params, $sourceExpr, $filters, 'src_bal_bank');

        $joinUsers = $hasUsers ? 'LEFT JOIN `users` u ON u.username = h.username' : '';
        $userIdSelect = $hasUsers && $this->hasColumn('users', 'id') ? 'u.id AS user_id' : 'NULL AS user_id';
        $currentMoneySelect = $hasUsers && $this->hasColumn('users', 'money') ? 'u.money AS current_money' : 'NULL AS current_money';

        $sql = "
            SELECT
                h.id,
                {$userIdSelect},
                h.username,
                h.ctk AS reason,
                h.thucnhan AS raw_change,
                {$sourceExpr} AS source_channel,
                h.time AS raw_time,
                {$timeExpr} AS event_time,
                {$currentMoneySelect}
            FROM `history_nap_bank` h
            {$joinUsers}
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY event_time DESC, h.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Approximate before/after from current user balance and descending events.
        $userBalanceTracker = [];
        foreach ($rows as &$row) {
            $username = (string) ($row['username'] ?? '');
            $change = $this->parseAmount($row['raw_change'] ?? 0);
            $current = $this->parseAmount($row['current_money'] ?? null, true);

            if (!array_key_exists($username, $userBalanceTracker)) {
                $userBalanceTracker[$username] = $current;
            }

            $afterBalance = $userBalanceTracker[$username];
            $beforeBalance = null;
            if ($afterBalance !== null) {
                $beforeBalance = $afterBalance - $change;
                $userBalanceTracker[$username] = $beforeBalance;
            }

            $row['change_amount'] = $change;
            $row['before_balance'] = $beforeBalance;
            $row['after_balance'] = $afterBalance;
            $row['reason_text'] = $row['reason'] ?? '';
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array<int, string> $conditions
     * @param array<string, mixed> $params
     * @param array<string, mixed> $filters
     */
    private function appendDateConditions(array &$conditions, array &$params, string $timeExpr, array $filters): void
    {
        if ($timeExpr === 'NULL') {
            return;
        }

        $dateFilter = (string) ($filters['date_filter'] ?? 'all');
        $rangeDate = trim((string) ($filters['time_range'] ?? ''));

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
            $conditions[] = "{$timeExpr} >= :df_today";
            $params['df_today'] = $todayStart;
        } elseif ($dateFilter === '7days') {
            $conditions[] = "{$timeExpr} >= :df_7days";
            $params['df_7days'] = $last7Days;
        } elseif ($dateFilter === '30days') {
            $conditions[] = "{$timeExpr} >= :df_30days";
            $params['df_30days'] = $last30Days;
        }

        // Specific range: Từ ngày -> Đến ngày
        $startDate = trim((string) ($filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['end_date'] ?? ''));

        if ($startDate !== '') {
            $date = DateTime::createFromFormat('Y-m-d', $startDate);
            if ($date instanceof DateTime) {
                $conditions[] = "{$timeExpr} >= :start_date";
                $params['start_date'] = $date->format('Y-m-d 00:00:00');
            }
        }

        if ($endDate !== '') {
            $date = DateTime::createFromFormat('Y-m-d', $endDate);
            if ($date instanceof DateTime) {
                $conditions[] = "{$timeExpr} <= :end_date";
                $params['end_date'] = $date->format('Y-m-d 23:59:59');
            }
        }

        // DateRangePicker (YYYY-MM-DD - YYYY-MM-DD) or Single date selector
        $rangeDate = trim((string) ($filters['time_range'] ?? ''));
        if ($rangeDate !== '' && $startDate === '' && $endDate === '') {
            $rangeParts = explode(' - ', $rangeDate);
            if (count($rangeParts) === 2) {
                $sDate = DateTime::createFromFormat('Y-m-d', trim($rangeParts[0]));
                $eDate = DateTime::createFromFormat('Y-m-d', trim($rangeParts[1]));
                if ($sDate && $eDate) {
                    $conditions[] = "{$timeExpr} >= :rng_start";
                    $conditions[] = "{$timeExpr} <= :rng_end";
                    $params['rng_start'] = $sDate->format('Y-m-d 00:00:00');
                    $params['rng_end'] = $eDate->format('Y-m-d 23:59:59');
                }
            } else {
                $date = DateTime::createFromFormat('Y-m-d', $rangeDate);
                if ($date instanceof DateTime) {
                    $conditions[] = "{$timeExpr} BETWEEN :range_s AND :range_e";
                    $params['range_s'] = $date->format('Y-m-d 00:00:00');
                    $params['range_e'] = $date->format('Y-m-d 23:59:59');
                }
            }
        }
    }

    private function buildEventTimeExpression(string $alias, ?string $column): string
    {
        if ($column === null) {
            return 'NULL';
        }

        if ($column === 'created_at') {
            return "{$alias}.created_at";
        }

        $safe = "{$alias}.`{$column}`";

        return "
            CASE
                WHEN {$safe} REGEXP '^[0-9]{9,}$' THEN FROM_UNIXTIME(CAST({$safe} AS UNSIGNED))
                WHEN STR_TO_DATE({$safe}, '%Y-%m-%d %H:%i:%s') IS NOT NULL THEN STR_TO_DATE({$safe}, '%Y-%m-%d %H:%i:%s')
                WHEN STR_TO_DATE({$safe}, '%H:%i %d-%m-%Y') IS NOT NULL THEN STR_TO_DATE({$safe}, '%H:%i %d-%m-%Y')
                WHEN STR_TO_DATE({$safe}, '%d-%m-%Y %H:%i:%s') IS NOT NULL THEN STR_TO_DATE({$safe}, '%d-%m-%Y %H:%i:%s')
                ELSE NULL
            END
        ";
    }

    private function detectTimeColumn(string $table): ?string
    {
        $candidates = ['created_at', 'time'];
        foreach ($candidates as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }
        return null;
    }

    private function detectDeviceColumn(string $table): ?string
    {
        $candidates = ['device', 'user_agent', 'agent', 'browser'];
        foreach ($candidates as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }
        return null;
    }

    private function tableExists(string $table): bool
    {
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        $safeTable = $this->db->quote($table);
        $stmt = $this->db->query("SHOW TABLES LIKE {$safeTable}");
        $exists = $stmt ? (bool) $stmt->fetchColumn() : false;

        $this->tableExistsCache[$table] = $exists;
        return $exists;
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        if (!isset($this->tableColumnsCache[$table])) {
            $stmt = $this->db->query("SHOW COLUMNS FROM `{$table}`");
            $columns = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $columns[] = $row['Field'];
            }
            $this->tableColumnsCache[$table] = $columns;
        }

        return in_array($column, $this->tableColumnsCache[$table], true);
    }

    private function ensureSourceChannelSchema(): void
    {
        $targets = [
            'orders' => [
                "ALTER TABLE `orders` ADD COLUMN `source_channel` TINYINT(1) NOT NULL DEFAULT 0 AFTER `source`",
                "ALTER TABLE `orders` ADD KEY `idx_orders_source_created` (`source_channel`, `created_at`)",
            ],
            'history_nap_bank' => [
                "ALTER TABLE `history_nap_bank` ADD COLUMN `source_channel` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`",
                "ALTER TABLE `history_nap_bank` ADD COLUMN `bank_name` VARCHAR(120) NULL AFTER `stk`",
                "ALTER TABLE `history_nap_bank` ADD COLUMN `bank_owner` VARCHAR(150) NULL AFTER `bank_name`",
                "ALTER TABLE `history_nap_bank` ADD KEY `idx_hnb_source_created` (`source_channel`, `created_at`)",
                "ALTER TABLE `history_nap_bank` ADD KEY `idx_hnb_bank_name` (`bank_name`)",
            ],
            'lich_su_bien_dong_so_du' => [
                "ALTER TABLE `lich_su_bien_dong_so_du` ADD COLUMN `source_channel` TINYINT(1) NOT NULL DEFAULT 0 AFTER `reason`",
                "ALTER TABLE `lich_su_bien_dong_so_du` ADD KEY `idx_lsbd_source_created` (`source_channel`, `created_at`)",
            ],
        ];

        foreach ($targets as $table => $sqlList) {
            if (!$this->tableExists($table)) {
                continue;
            }
            foreach ($sqlList as $sql) {
                try {
                    $this->db->exec($sql);
                } catch (Throwable $e) {
                    // ignore if already exists or ALTER is restricted
                }
            }
            unset($this->tableColumnsCache[$table]);
        }
    }

    private function appendSourceChannelCondition(
        array &$conditions,
        array &$params,
        string $sourceExpr,
        array $filters,
        string $paramName
    ): void {
        $sourceFilter = trim((string) ($filters['source_channel'] ?? 'all'));
        if (!in_array($sourceFilter, ['0', '1'], true)) {
            return;
        }

        $conditions[] = "({$sourceExpr}) = :{$paramName}";
        $params[$paramName] = (int) $sourceFilter;
    }

    private function buildOrderSourceChannelExpression(string $alias): string
    {
        if ($this->hasColumn('orders', 'source_channel')) {
            return "COALESCE({$alias}.source_channel, 0)";
        }

        $parts = [];
        if ($this->hasColumn('orders', 'source')) {
            $parts[] = "LOWER(TRIM(CAST({$alias}.source AS CHAR))) IN ('telegram','tele','telebot','bot','tg','1')";
        }
        if ($this->hasColumn('orders', 'telegram_id')) {
            $parts[] = "COALESCE({$alias}.telegram_id, 0) > 0";
        }

        if (empty($parts)) {
            return '0';
        }

        return "CASE WHEN (" . implode(' OR ', $parts) . ") THEN 1 ELSE 0 END";
    }

    private function buildDepositSourceChannelExpression(string $alias): string
    {
        if ($this->hasColumn('history_nap_bank', 'source_channel')) {
            return "COALESCE({$alias}.source_channel, 0)";
        }

        $parts = [];
        if ($this->hasColumn('history_nap_bank', 'type')) {
            $parts[] = "LOWER(COALESCE({$alias}.type, '')) LIKE '%telegram%'";
        }
        if ($this->hasColumn('history_nap_bank', 'username')) {
            $parts[] = "LOWER(COALESCE({$alias}.username, '')) LIKE 'tg\\_%'";
        }
        if (empty($parts)) {
            return '0';
        }

        return "
            CASE
                WHEN " . implode(' OR ', $parts) . "
                THEN 1
                ELSE 0
            END
        ";
    }

    private function buildSimpleSourceChannelExpression(string $alias, string $table): string
    {
        if ($this->hasColumn($table, 'source_channel')) {
            return "COALESCE({$alias}.source_channel, 0)";
        }
        return '0';
    }

    /**
     * @param mixed $value
     */
    private function parseAmount($value, bool $allowNull = false): ?int
    {
        if ($value === null) {
            return $allowNull ? null : 0;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return $allowNull ? null : 0;
        }

        $sign = 1;
        if (strpos($raw, '-') !== false) {
            $sign = -1;
        }

        $digits = preg_replace('/[^0-9]/', '', $raw);
        if ($digits === '') {
            return $allowNull ? null : 0;
        }

        return $sign * (int) $digits;
    }

    /**
     * @param array<int, array<string, mixed>> $dedicatedRows
     * @param array<int, array<string, mixed>> $bankRows
     * @return array<int, array<string, mixed>>
     */
    private function mergeBalanceRows(array $dedicatedRows, array $bankRows): array
    {
        $merged = [];
        $seen = [];

        foreach ([$dedicatedRows, $bankRows] as $rows) {
            foreach ($rows as $row) {
                $key = $this->buildBalanceRowKey($row);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $merged[] = $row;
            }
        }

        usort($merged, function (array $a, array $b): int {
            $ta = $this->extractBalanceRowTimestamp($a);
            $tb = $this->extractBalanceRowTimestamp($b);
            if ($ta === $tb) {
                return ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0));
            }
            return $tb <=> $ta;
        });

        return $merged;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function buildBalanceRowKey(array $row): string
    {
        $username = strtolower(trim((string) ($row['username'] ?? '')));
        $rawTime = trim((string) ($row['raw_time'] ?? ''));
        if ($rawTime === '') {
            $rawTime = trim((string) ($row['event_time'] ?? ''));
        }
        $change = (string) $this->parseAmount($row['change_amount'] ?? ($row['raw_change'] ?? 0));
        $sourceChannel = (string) SourceChannelHelper::normalize($row['source_channel'] ?? SourceChannelHelper::WEB);
        return $username . '|' . $rawTime . '|' . $change . '|' . $sourceChannel;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extractBalanceRowTimestamp(array $row): int
    {
        $eventTime = trim((string) ($row['event_time'] ?? ''));
        if ($eventTime !== '') {
            $ts = $this->timeService
                ? ($this->timeService->toTimestamp($eventTime, $this->timeService->getDbTimezone()) ?? 0)
                : (strtotime($eventTime) ?: 0);
            if ($ts > 0) {
                return $ts;
            }
        }

        $rawTime = trim((string) ($row['raw_time'] ?? ''));
        if ($rawTime === '') {
            return 0;
        }
        if (ctype_digit($rawTime)) {
            return (int) $rawTime;
        }

        return $this->timeService
            ? (int) ($this->timeService->toTimestamp($rawTime, $this->timeService->getDbTimezone()) ?? 0)
            : (int) (strtotime($rawTime) ?: 0);
    }
}

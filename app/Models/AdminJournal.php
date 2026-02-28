<?php

/**
 * Admin Journal Model
 * Shared data layer for admin activity/balance log pages.
 */
class AdminJournal extends Model
{
    private $tableExistsCache = [];
    private $tableColumnsCache = [];

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
        if ($this->tableExists('lich_su_bien_dong_so_du')) {
            return $this->getBalanceLogsFromDedicatedTable($filters);
        }

        return $this->getBalanceLogsFromBankHistory($filters);
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

            if (ctype_digit(trim((string) $filters['search'])) && $hasUsers) {
                $searchConditions[] = 'u.id = :search_id';
                $params['search_id'] = (int) $filters['search'];
            }

            $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
        }

        $this->appendDateConditions($conditions, $params, $timeExpr, $filters);

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
                h.thucnhan AS amount,
                h.status,
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
        $stmt->execute([
            'username' => $username,
            'activity' => $activity,
            'amount' => (string) $this->parseAmount($amount),
            'time' => date('H:i d-m-Y'),
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

        $select = [
            'b.id',
            $this->hasColumn('lich_su_bien_dong_so_du', 'user_id') ? 'b.user_id' : 'NULL AS user_id',
            $this->hasColumn('lich_su_bien_dong_so_du', 'username') ? 'b.username' : "'' AS username",
            $this->hasColumn('lich_su_bien_dong_so_du', 'before_balance') ? 'b.before_balance' : 'NULL AS before_balance',
            $this->hasColumn('lich_su_bien_dong_so_du', 'change_amount') ? 'b.change_amount' : 'NULL AS change_amount',
            $this->hasColumn('lich_su_bien_dong_so_du', 'after_balance') ? 'b.after_balance' : 'NULL AS after_balance',
            $this->hasColumn('lich_su_bien_dong_so_du', 'reason') ? 'b.reason' : "'' AS reason_text",
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

        if ($dateFilter === 'today') {
            $conditions[] = "{$timeExpr} >= :df_today";
            $params['df_today'] = date('Y-m-d 00:00:00');
        } elseif ($dateFilter === '7days') {
            $conditions[] = "{$timeExpr} >= :df_7days";
            $params['df_7days'] = date('Y-m-d H:i:s', strtotime('-7 days'));
        } elseif ($dateFilter === '30days') {
            $conditions[] = "{$timeExpr} >= :df_30days";
            $params['df_30days'] = date('Y-m-d H:i:s', strtotime('-30 days'));
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
}

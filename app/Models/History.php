<?php

/**
 * History Model
 * Unified user balance history feed (purchase activity + deposits)
 */
class History extends Model
{
    private array $tableExistsCache = [];
    private array $columnsCache = [];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'lich_su_hoat_dong';
    }

    /**
     * Get user history with filtering + pagination (DESC by event time)
     */
    public function getUserHistory($userContext, $filters, $limit, $offset)
    {
        $rows = $this->getFilteredUnifiedRows($this->normalizeUserContext($userContext), (array) $filters);
        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);
        return array_slice($rows, $offset, $limit);
    }

    /**
     * Get all filtered rows (DESC) for accurate running-balance calculation before pagination.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getAllUserHistory($userContext, $filters): array
    {
        return $this->getFilteredUnifiedRows($this->normalizeUserContext($userContext), (array) $filters);
    }

    /**
     * Count total user history rows after filters
     */
    public function countUserHistory($userContext, $filters)
    {
        return count($this->getFilteredUnifiedRows($this->normalizeUserContext($userContext), (array) $filters));
    }

    public function canUseFastPagination($userContext): bool
    {
        if (!$this->tableExists('lich_su_bien_dong_so_du')) {
            return false;
        }

        $context = $this->normalizeUserContext($userContext);
        [$whereSql, $params] = $this->buildLsbdUserWhere($context);

        if ($whereSql === '') {
            return false;
        }

        $stmt = $this->db->prepare("SELECT 1 FROM `lich_su_bien_dong_so_du` WHERE {$whereSql} LIMIT 1");
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    private function buildLsbdFastFilters(array $filters): array
    {
        $where = [];
        $params = [];

        $timeRange = trim((string) ($filters['time_range'] ?? ''));
        if ($timeRange !== '') {
            $parts = null;
            foreach ([' to ', ' - '] as $delimiter) {
                if (strpos($timeRange, $delimiter) !== false) {
                    $parts = explode($delimiter, $timeRange, 2);
                    break;
                }
            }
            if (is_array($parts) && count($parts) === 2) {
                $where[] = "DATE(`created_at`) BETWEEN ? AND ?";
                $params[] = trim($parts[0]);
                $params[] = trim($parts[1]);
            } else {
                $where[] = "DATE(`created_at`) = ?";
                $params[] = $timeRange;
            }
        }

        $sortDate = trim((string) ($filters['sort_date'] ?? 'all'));
        if ($sortDate === 'today') {
            $where[] = "DATE(`created_at`) = CURDATE()";
        } elseif ($sortDate === '7') {
            $where[] = "DATE(`created_at`) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        } elseif ($sortDate === '30') {
            $where[] = "DATE(`created_at`) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }

        $whereSql = empty($where) ? '' : implode(' AND ', $where);
        return [$whereSql, $params];
    }

    public function countUserHistoryFast($userContext, $filters): int
    {
        $context = $this->normalizeUserContext($userContext);
        [$whereSql, $params] = $this->buildLsbdUserWhere($context);

        [$extraWhere, $extraParams] = $this->buildLsbdFastFilters($filters);

        if ($extraWhere !== '') {
            $whereSql = "({$whereSql}) AND {$extraWhere}";
            $params = array_merge($params, $extraParams);
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `lich_su_bien_dong_so_du` WHERE {$whereSql}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getUserHistoryFast($userContext, $filters, $limit, $offset): array
    {
        $context = $this->normalizeUserContext($userContext);
        [$whereSql, $params] = $this->buildLsbdUserWhere($context);

        [$extraWhere, $extraParams] = $this->buildLsbdFastFilters($filters);

        if ($extraWhere !== '') {
            $whereSql = "({$whereSql}) AND {$extraWhere}";
            $params = array_merge($params, $extraParams);
        }

        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $cols = ['id', 'before_balance', 'change_amount', 'after_balance', 'reason', 'time', 'created_at'];
        if ($this->hasColumn('lich_su_bien_dong_so_du', 'source_channel')) {
            $cols[] = 'source_channel';
        }

        $sql = "SELECT " . implode(', ', $cols) . " FROM `lich_su_bien_dong_so_du` WHERE {$whereSql} ORDER BY `id` DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $change = (int) ($row['change_amount'] ?? 0);
            $ts = $this->parseEventTimestamp($row['created_at'] ?? null, $row['time'] ?? null);
            $reason = trim((string) ($row['reason'] ?? ''));
            $sourceChannel = (int) ($row['source_channel'] ?? 0);

            $lowerReason = mb_strtolower($reason, 'UTF-8');
            if (strpos($lowerReason, 'nap tien') !== false || strpos($lowerReason, 'nạp tiền') !== false) {
                $source = 'deposit';
                $reasonText = $reason !== '' ? $reason : 'Nạp tiền';
            } elseif (
                strpos($lowerReason, 'thanh toan') !== false || strpos($lowerReason, 'thanh toán') !== false
                || strpos($lowerReason, 'mua') !== false
            ) {
                $source = 'activity';
                $reasonText = $reason !== '' ? $reason : 'Mua hàng';
            } elseif (
                strpos($lowerReason, 'admin') !== false || strpos($lowerReason, 'hoan tien') !== false
                || strpos($lowerReason, 'hoàn tiền') !== false
            ) {
                $source = 'admin';
                $reasonText = $reason !== '' ? $reason : 'Điều chỉnh';
            } else {
                $source = $change >= 0 ? 'deposit' : 'activity';
                $reasonText = $reason !== '' ? $reason : ($change >= 0 ? 'Cộng tiền' : 'Trừ tiền');
            }

            $rows[] = [
                'source' => $source,
                'source_id' => (int) ($row['id'] ?? 0),
                'event_ts' => $ts,
                'event_time' => $ts > 0 ? date('Y-m-d H:i:s', $ts) : (string) ($row['created_at'] ?? ''),
                'raw_time' => (string) ($row['time'] ?? ''),
                'change_amount' => $change,
                'reason_text' => $reasonText,
                'source_channel' => $sourceChannel,
                'has_stored_balances' => true,
                'before_balance' => (int) ($row['before_balance'] ?? 0),
                'after_balance' => (int) ($row['after_balance'] ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * Calculate before/change/after balances from current balance over DESC rows.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function calculateRunningBalances(array $rows, int $currentBalance): array
    {
        $tracker = $currentBalance;
        foreach ($rows as &$row) {
            $change = (int) ($row['change_amount'] ?? 0);
            $after = $tracker;
            $before = $after - $change;

            $row['before_balance'] = $before;
            $row['after_balance'] = $after;
            $tracker = $before;
        }
        unset($row);

        return $rows;
    }

    /**
     * Legacy compatibility (no longer used by controller)
     */
    public function getSumGiaAfter($username, $id)
    {
        $stmt = $this->db->prepare("SELECT SUM(gia) as total FROM `{$this->table}` WHERE username = ? AND id > ?");
        $stmt->execute([$username, $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function getFilteredUnifiedRows(array $userContext, array $filters): array
    {
        $rows = $this->getUnifiedRows($userContext);

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $rows = $this->applySmartSearchFilter($rows, $search);
        }

        $rows = $this->applyTimeRangeFilter($rows, (string) ($filters['time_range'] ?? ''));
        $rows = $this->applyQuickDateFilter($rows, (string) ($filters['sort_date'] ?? 'all'));

        usort($rows, function ($a, $b) {
            $ta = (int) ($a['event_ts'] ?? 0);
            $tb = (int) ($b['event_ts'] ?? 0);
            if ($ta === $tb) {
                $sa = (string) ($a['source'] ?? '');
                $sb = (string) ($b['source'] ?? '');
                if ($sa === $sb) {
                    return ((int) ($b['source_id'] ?? 0)) <=> ((int) ($a['source_id'] ?? 0));
                }
                return strcmp($sb, $sa);
            }
            return $tb <=> $ta;
        });

        return $rows;
    }

    /**
     * Build unified rows for one user.
     * Priority: lich_su_bien_dong_so_du (LSBD) — has stored before/after balances.
     * Fallback: legacy tables (lich_su_hoat_dong + history_nap_bank) for pre-LSBD data.
     *
     * @return array<int,array<string,mixed>>
     */
    private function getUnifiedRows(array $userContext): array
    {
        // Try LSBD first (unified balance log with stored before/after)
        if ($this->tableExists('lich_su_bien_dong_so_du')) {
            $lsbdRows = $this->getLsbdRows($userContext);
            if ($lsbdRows !== []) {
                // LSBD is the authoritative source — skip legacy tables to avoid duplicates
                return $lsbdRows;
            }
        }

        // Fallback: legacy tables for users with no LSBD data (old accounts)
        return $this->getLegacyRows($userContext);
    }

    /**
     * Fetch rows from lich_su_bien_dong_so_du (authoritative source).
     * These rows already have before_balance and after_balance stored accurately.
     *
     * @return array<int,array<string,mixed>>
     */
    private function getLsbdRows(array $userContext): array
    {
        $rows = [];
        try {
            $cols = ['id', 'before_balance', 'change_amount', 'after_balance', 'reason', 'time', 'created_at'];
            if ($this->hasColumn('lich_su_bien_dong_so_du', 'source_channel')) {
                $cols[] = 'source_channel';
            }
            [$whereSql, $params] = $this->buildLsbdUserWhere($userContext);
            if ($whereSql === '') {
                return [];
            }
            $sql = 'SELECT ' . implode(', ', $cols) . ' FROM `lich_su_bien_dong_so_du` WHERE ' . $whereSql;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $change = (int) ($row['change_amount'] ?? 0);
                $ts = $this->parseEventTimestamp($row['created_at'] ?? null, $row['time'] ?? null);
                $reason = trim((string) ($row['reason'] ?? ''));
                $sourceChannel = (int) ($row['source_channel'] ?? 0);

                // Derive readable reason text & source type
                $lowerReason = mb_strtolower($reason, 'UTF-8');
                if (strpos($lowerReason, 'nap tien') !== false || strpos($lowerReason, 'nạp tiền') !== false) {
                    $source = 'deposit';
                    $reasonText = $reason !== '' ? $reason : 'Nạp tiền';
                } elseif (
                    strpos($lowerReason, 'thanh toan') !== false || strpos($lowerReason, 'thanh toán') !== false
                    || strpos($lowerReason, 'mua') !== false
                ) {
                    $source = 'activity';
                    $reasonText = $reason !== '' ? $reason : 'Mua hàng';
                } elseif (
                    strpos($lowerReason, 'admin') !== false || strpos($lowerReason, 'hoan tien') !== false
                    || strpos($lowerReason, 'hoàn tiền') !== false
                ) {
                    $source = 'admin';
                    $reasonText = $reason !== '' ? $reason : 'Điều chỉnh';
                } else {
                    $source = $change >= 0 ? 'deposit' : 'activity';
                    $reasonText = $reason !== '' ? $reason : ($change >= 0 ? 'Cộng tiền' : 'Trừ tiền');
                }

                $rows[] = [
                    'source' => $source,
                    'source_id' => (int) ($row['id'] ?? 0),
                    'event_ts' => $ts,
                    'event_time' => $ts > 0 ? date('Y-m-d H:i:s', $ts) : (string) ($row['created_at'] ?? ''),
                    'raw_time' => (string) ($row['time'] ?? ''),
                    'change_amount' => $change,
                    'reason_text' => $reasonText,
                    'source_channel' => $sourceChannel,
                    // Stored balances — use these directly, skip in-memory calculation
                    'has_stored_balances' => true,
                    'before_balance' => (int) ($row['before_balance'] ?? 0),
                    'after_balance' => (int) ($row['after_balance'] ?? 0),
                ];
            }
        } catch (Throwable $e) {
            // DB error — fall through to legacy
        }
        return $rows;
    }

    /**
     * Fetch rows from legacy tables (backward-compat for pre-LSBD data).
     *
     * @return array<int,array<string,mixed>>
     */
    private function getLegacyRows(array $userContext): array
    {
        $rows = [];
        $username = trim((string) ($userContext['username'] ?? ''));
        if ($username === '') {
            return $rows;
        }

        if ($this->tableExists('lich_su_hoat_dong')) {
            $hasCa = $this->hasColumn('lich_su_hoat_dong', 'created_at');
            $activitySql = 'SELECT id, hoatdong, gia, time' . ($hasCa ? ', created_at' : '') . ' FROM `lich_su_hoat_dong` WHERE `username` = :username';
            $stmt = $this->db->prepare($activitySql);
            $stmt->execute(['username' => $username]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $ts = $this->parseEventTimestamp($row['created_at'] ?? null, $row['time'] ?? null);
                $reason = trim((string) ($row['hoatdong'] ?? ''));
                $rows[] = [
                    'source' => 'activity',
                    'source_id' => (int) ($row['id'] ?? 0),
                    'event_ts' => $ts,
                    'event_time' => $ts > 0 ? date('Y-m-d H:i:s', $ts) : (string) ($row['created_at'] ?? ''),
                    'raw_time' => (string) ($row['time'] ?? ''),
                    'change_amount' => (int) ($row['gia'] ?? 0),
                    'reason_text' => $reason !== '' ? $reason : 'Sản phẩm',
                    'has_stored_balances' => false,
                ];
            }
        }

        if ($this->tableExists('history_nap_bank')) {
            $depositColumns = ['id', 'username'];
            foreach (['ctk', 'thucnhan', 'status', 'type', 'trans_id', 'time', 'created_at'] as $col) {
                if ($this->hasColumn('history_nap_bank', $col)) {
                    $depositColumns[] = $col;
                }
            }

            $sql = 'SELECT ' . implode(', ', array_unique($depositColumns)) . ' FROM `history_nap_bank` WHERE `username` = :username';
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['username' => $username]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $status = mb_strtolower(trim((string) ($row['status'] ?? '')), 'UTF-8');
                if ($status !== '' && !in_array($status, ['hoantat', 'thành công', 'thanh cong', 'success', 'completed'], true)) {
                    continue;
                }

                $change = $this->parseIntLike($row['thucnhan'] ?? 0);
                $ts = $this->parseEventTimestamp($row['created_at'] ?? null, $row['time'] ?? null);
                $ctk = trim((string) ($row['ctk'] ?? ''));
                $type = trim((string) ($row['type'] ?? ''));
                $transId = trim((string) ($row['trans_id'] ?? ''));

                $reasonParts = [$change >= 0 ? 'Nạp tiền' : 'Điều chỉnh số dư'];
                if ($type !== '') {
                    $reasonParts[] = $type;
                }
                $reasonParts[] = $ctk !== '' ? $ctk : ($transId !== '' ? $transId : '');
                $reasonParts = array_filter($reasonParts);

                $rows[] = [
                    'source' => 'deposit',
                    'source_id' => (int) ($row['id'] ?? 0),
                    'event_ts' => $ts,
                    'event_time' => $ts > 0 ? date('Y-m-d H:i:s', $ts) : (string) ($row['created_at'] ?? ''),
                    'raw_time' => (string) ($row['time'] ?? ''),
                    'change_amount' => $change,
                    'reason_text' => implode(': ', array_splice($reasonParts, 0, 1)) . (count($reasonParts) > 0 ? ': ' . implode(' - ', $reasonParts) : ''),
                    'has_stored_balances' => false,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function applyTimeRangeFilter(array $rows, string $timeRange): array
    {
        $timeRange = trim($timeRange);
        if ($timeRange === '') {
            return $rows;
        }

        $parts = null;
        foreach ([' to ', ' - '] as $delimiter) {
            if (strpos($timeRange, $delimiter) !== false) {
                $parts = explode($delimiter, $timeRange, 2);
                break;
            }
        }

        $startTs = null;
        $endTs = null;

        if (is_array($parts) && count($parts) === 2) {
            $start = DateTime::createFromFormat('Y-m-d', trim($parts[0]));
            $end = DateTime::createFromFormat('Y-m-d', trim($parts[1]));
            if ($start instanceof DateTime) {
                $start->setTime(0, 0, 0);
                $startTs = $start->getTimestamp();
            }
            if ($end instanceof DateTime) {
                $end->setTime(23, 59, 59);
                $endTs = $end->getTimestamp();
            }
        } else {
            $single = DateTime::createFromFormat('Y-m-d', $timeRange);
            if ($single instanceof DateTime) {
                $start = (clone $single)->setTime(0, 0, 0);
                $end = (clone $single)->setTime(23, 59, 59);
                $startTs = $start->getTimestamp();
                $endTs = $end->getTimestamp();
            }
        }

        if ($startTs === null && $endTs === null) {
            return $rows;
        }

        return array_values(array_filter($rows, function ($row) use ($startTs, $endTs) {
            $ts = (int) ($row['event_ts'] ?? 0);
            if ($ts <= 0) {
                return false;
            }
            if ($startTs !== null && $ts < $startTs) {
                return false;
            }
            if ($endTs !== null && $ts > $endTs) {
                return false;
            }
            return true;
        }));
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function applyQuickDateFilter(array $rows, string $sortDate): array
    {
        $sortDate = trim($sortDate);
        if ($sortDate === '' || $sortDate === 'all') {
            return $rows;
        }

        $startTs = null;
        $now = time();
        if ($sortDate === 'today') {
            $startTs = strtotime(date('Y-m-d 00:00:00', $now));
        } elseif ($sortDate === '7') {
            $startTs = strtotime('-7 days', $now);
        } elseif ($sortDate === '30') {
            $startTs = strtotime('-30 days', $now);
        }

        if ($startTs === null) {
            return $rows;
        }

        return array_values(array_filter($rows, function ($row) use ($startTs) {
            return (int) ($row['event_ts'] ?? 0) >= $startTs;
        }));
    }

    private function parseEventTimestamp($createdAt, $rawTime): int
    {
        $candidates = [];

        $createdAt = trim((string) $createdAt);
        $rawTime = trim((string) $rawTime);
        if ($createdAt !== '' && $createdAt !== '0000-00-00 00:00:00') {
            $candidates[] = $createdAt;
        }

        if ($rawTime !== '') {
            if (ctype_digit($rawTime)) {
                return (int) $rawTime;
            }
            $candidates[] = $rawTime;
        }

        foreach ($candidates as $candidate) {
            $formats = [
                'Y-m-d H:i:s',
                'H:i d-m-Y',
                'd-m-Y H:i:s',
                'd/m/Y H:i:s',
                'd-m-Y',
                'Y-m-d',
            ];
            foreach ($formats as $format) {
                $dt = DateTime::createFromFormat($format, $candidate);
                if ($dt instanceof DateTime) {
                    return $dt->getTimestamp();
                }
            }

            $ts = strtotime($candidate);
            if ($ts !== false) {
                return $ts;
            }
        }

        return 0;
    }

    private function parseIntLike($value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) round($value);
        }
        $str = trim((string) $value);
        if ($str === '') {
            return 0;
        }
        $negative = strpos($str, '-') !== false;
        $digits = preg_replace('/[^0-9]/', '', $str);
        $num = (int) ($digits !== '' ? $digits : 0);
        return $negative ? -$num : $num;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function applySmartSearchFilter(array $rows, string $search): array
    {
        $tokens = $this->tokenizeSmartSearch($search);
        if ($tokens === []) {
            return $rows;
        }

        return array_values(array_filter($rows, function (array $row) use ($tokens): bool {
            $change = (int) ($row['change_amount'] ?? 0);
            $amountAbs = abs($change);
            $amountPlain = (string) $amountAbs;
            $amountFormatted = number_format($amountAbs, 0, ',', '.');
            $source = (string) ($row['source'] ?? '');
            $sourceLabel = match ($source) {
                'deposit' => 'nap tien deposit bank chuyen khoan',
                'activity' => 'mua hang don hang giao dich',
                default => $source,
            };

            $haystack = implode(' ', array_filter([
                (string) ($row['reason_text'] ?? ''),
                (string) ($row['event_time'] ?? ''),
                (string) ($row['raw_time'] ?? ''),
                (string) ($row['source_id'] ?? ''),
                $source,
                $sourceLabel,
                $change > 0 ? 'cong plus +' : ($change < 0 ? 'tru minus -' : 'khong doi'),
                $amountPlain,
                $amountFormatted,
                $amountFormatted . 'd',
                $amountPlain . 'd',
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

    /**
     * @param mixed $userContext
     * @return array{user_id:int,username:string}
     */
    private function normalizeUserContext($userContext): array
    {
        if (is_array($userContext)) {
            return [
                'user_id' => max(0, (int) ($userContext['user_id'] ?? $userContext['id'] ?? 0)),
                'username' => trim((string) ($userContext['username'] ?? '')),
            ];
        }

        return [
            'user_id' => 0,
            'username' => trim((string) $userContext),
        ];
    }

    /**
     * @param array{user_id:int,username:string} $userContext
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildLsbdUserWhere(array $userContext): array
    {
        $params = [];
        $userId = (int) ($userContext['user_id'] ?? 0);
        $username = trim((string) ($userContext['username'] ?? ''));

        if ($userId > 0 && $this->hasColumn('lich_su_bien_dong_so_du', 'user_id')) {
            $params['user_id'] = $userId;
            return ['`user_id` = :user_id', $params];
        }

        if ($username !== '' && $this->hasColumn('lich_su_bien_dong_so_du', 'username')) {
            $params['username'] = $username;
            return ['`username` = :username', $params];
        }

        return ['', []];
    }

    private function tableExists(string $table): bool
    {
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);
        $exists = (bool) $stmt->fetchColumn();
        $this->tableExistsCache[$table] = $exists;
        return $exists;
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (!$this->tableExists($table)) {
            return false;
        }
        if (!isset($this->columnsCache[$table])) {
            $stmt = $this->db->query("SHOW COLUMNS FROM `{$table}`");
            $this->columnsCache[$table] = array_map(static function ($row) {
                return (string) ($row['Field'] ?? '');
            }, $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []);
        }
        return in_array($column, $this->columnsCache[$table], true);
    }
}

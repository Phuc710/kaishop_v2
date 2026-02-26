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
    public function getUserHistory($username, $filters, $limit, $offset)
    {
        $rows = $this->getFilteredUnifiedRows((string) $username, (array) $filters);
        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);
        return array_slice($rows, $offset, $limit);
    }

    /**
     * Get all filtered rows (DESC) for accurate running-balance calculation before pagination.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getAllUserHistory($username, $filters): array
    {
        return $this->getFilteredUnifiedRows((string) $username, (array) $filters);
    }

    /**
     * Count total user history rows after filters
     */
    public function countUserHistory($username, $filters)
    {
        return count($this->getFilteredUnifiedRows((string) $username, (array) $filters));
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
    private function getFilteredUnifiedRows(string $username, array $filters): array
    {
        $rows = $this->getUnifiedRows($username);

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
     * Build unified raw rows for one user from legacy tables.
     *
     * @return array<int,array<string,mixed>>
     */
    private function getUnifiedRows(string $username): array
    {
        $rows = [];

        if ($this->tableExists('lich_su_hoat_dong')) {
            $activitySql = "SELECT id, hoatdong, gia, time" . ($this->hasColumn('lich_su_hoat_dong', 'created_at') ? ', created_at' : '') . " FROM `lich_su_hoat_dong` WHERE `username` = :username";
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
                ];
            }
        }

        if ($this->tableExists('history_nap_bank')) {
            $depositColumns = ['id', 'username'];
            if ($this->hasColumn('history_nap_bank', 'ctk')) {
                $depositColumns[] = 'ctk';
            }
            if ($this->hasColumn('history_nap_bank', 'thucnhan')) {
                $depositColumns[] = 'thucnhan';
            }
            if ($this->hasColumn('history_nap_bank', 'status')) {
                $depositColumns[] = 'status';
            }
            if ($this->hasColumn('history_nap_bank', 'type')) {
                $depositColumns[] = 'type';
            }
            if ($this->hasColumn('history_nap_bank', 'trans_id')) {
                $depositColumns[] = 'trans_id';
            }
            if ($this->hasColumn('history_nap_bank', 'time')) {
                $depositColumns[] = 'time';
            }
            if ($this->hasColumn('history_nap_bank', 'created_at')) {
                $depositColumns[] = 'created_at';
            }

            $sql = "SELECT " . implode(', ', array_unique($depositColumns)) . " FROM `history_nap_bank` WHERE `username` = :username";
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

                $reasonParts = [];
                if ($change >= 0) {
                    $reasonParts[] = 'Nạp tiền';
                } else {
                    $reasonParts[] = 'Điều chỉnh số dư';
                }
                if ($type !== '') {
                    $reasonParts[] = $type;
                }
                if ($ctk !== '') {
                    $reasonParts[] = $ctk;
                } elseif ($transId !== '') {
                    $reasonParts[] = $transId;
                }

                $rows[] = [
                    'source' => 'deposit',
                    'source_id' => (int) ($row['id'] ?? 0),
                    'event_ts' => $ts,
                    'event_time' => $ts > 0 ? date('Y-m-d H:i:s', $ts) : (string) ($row['created_at'] ?? ''),
                    'raw_time' => (string) ($row['time'] ?? ''),
                    'change_amount' => $change,
                    'reason_text' => implode(': ', array_filter([
                        array_shift($reasonParts),
                        trim(implode(' - ', $reasonParts))
                    ], function ($v) {
                        return $v !== '';
                    })),
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

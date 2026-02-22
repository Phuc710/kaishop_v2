<?php

/**
 * Admin Journal Controller
 * Shared controller for activity and balance change journals.
 * View uses DataTables for client-side pagination/filtering.
 */
class JournalController extends Controller
{
    private $authService;
    private $journalModel;
    private $systemLogModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->journalModel = new AdminJournal();
        $this->systemLogModel = new SystemLog();
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

    /**
     * Activity journal page.
     */
    public function activities()
    {
        $this->requireAdmin();

        $query = $this->buildQueryState();
        $rawRows = $this->journalModel->getActivityLogs($query);

        $this->renderJournal([
            'basePath' => 'admin/logs/activities',
            'pageTitle' => 'Nhật ký hoạt động',
            'pageIcon' => 'fas fa-history',
            'cardTitle' => 'NHẬT KÝ HOẠT ĐỘNG',
            'tableId' => 'activityJournalTable',
            'columns' => [
                ['key' => 'username', 'label' => 'Username', 'align' => 'center'],
                ['key' => 'action', 'label' => 'Hành động', 'align' => 'center'],
                ['key' => 'time', 'label' => 'Thời gian', 'align' => 'center'],
                ['key' => 'ip', 'label' => 'Địa chỉ IP', 'align' => 'center'],
                ['key' => 'device', 'label' => 'Thiết bị', 'align' => 'center'],
            ],
            'rows' => $this->mapActivityRows($rawRows),
            'query' => $query,
        ]);
    }

    /**
     * Balance change journal page.
     */
    public function balanceChanges()
    {
        $this->requireAdmin();

        $query = $this->buildQueryState();
        $rawRows = $this->journalModel->getBalanceChangeLogs($query);

        $this->renderJournal([
            'basePath' => 'admin/logs/balance-changes',
            'pageTitle' => 'Biến động số dư',
            'pageIcon' => 'fas fa-money-check-alt',
            'cardTitle' => 'NHẬT KÝ THAY ĐỔI SỐ DƯ',
            'tableId' => 'balanceChangeTable',
            'columns' => [
                ['key' => 'username', 'label' => 'Username', 'align' => 'center'],
                ['key' => 'before_balance', 'label' => 'Số dư trước', 'align' => 'center'],
                ['key' => 'change_balance', 'label' => 'Số dư thay đổi', 'align' => 'center'],
                ['key' => 'after_balance', 'label' => 'Số dư hiện tại', 'align' => 'center'],
                ['key' => 'time', 'label' => 'Thời gian', 'align' => 'center'],
                ['key' => 'reason', 'label' => 'Lý do', 'align' => 'center'],
            ],
            'rows' => $this->mapBalanceRows($rawRows),
            'query' => $query,
        ]);
    }

    /**
     * System logs page.
     */
    public function systemLogs()
    {
        $this->requireAdmin();
        global $chungapi;

        $filters = [
            'search' => trim((string) $this->get('search', '')),
            'severity' => trim((string) $this->get('severity', '')),
            'module' => trim((string) $this->get('module', '')),
        ];

        $page = max(1, (int) $this->get('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $logs = $this->systemLogModel->getLogs($filters, $perPage, $offset);
        $totalLogs = $this->systemLogModel->countLogs($filters);
        $totalPages = max(1, (int) ceil($totalLogs / $perPage));

        $this->view('admin/logs/system', [
            'chungapi' => $chungapi,
            'logs' => $logs,
            'filters' => $filters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalLogs' => $totalLogs,
        ]);
    }

    /**
     * Shared renderer for journal pages.
     */
    private function renderJournal(array $data): void
    {
        global $chungapi;

        $this->view('admin/logs/journal', array_merge([
            'chungapi' => $chungapi,
            'rows' => [],
            'columns' => [],
        ], $data));
    }

    /**
     * @return array<string, string>
     */
    private function buildQueryState(): array
    {
        return [
            'search' => trim((string) $this->get('search', '')),
            'time_range' => trim((string) $this->get('time_range', '')),
            'date_filter' => $this->normalizeDateFilter((string) $this->get('date_filter', 'all')),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, string|int>>
     */
    private function mapActivityRows(array $rows): array
    {
        $output = [];
        foreach ($rows as $row) {
            $username = $this->formatUsername($row['username'] ?? '');

            $ip = trim((string) ($row['ip_address'] ?? '--')) ?: '--';
            if ($ip !== '--') {
                $ip = '<span class="badge bg-soft-danger text-danger border-danger-light">' . htmlspecialchars($ip) . '</span>';
            }

            $output[] = [
                'username' => $username,
                'action' => trim((string) ($row['action_name'] ?? '--')) ?: '--',
                'time' => $this->formatEventTime($row['event_time'] ?? null, $row['raw_time'] ?? null),
                'ip' => $ip,
                'device' => trim((string) ($row['device_info'] ?? '--')) ?: '--',
            ];
        }

        return $output;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, string|int>>
     */
    private function mapBalanceRows(array $rows): array
    {
        $output = [];
        foreach ($rows as $row) {
            $username = $this->formatUsername($row['username'] ?? '');

            $beforeBal = $this->formatAmount($row['before_balance'] ?? null, false);
            $afterBal = $this->formatAmount($row['after_balance'] ?? null, false);

            $changeAmountStr = $this->formatAmount($row['change_amount'] ?? ($row['raw_change'] ?? null), true);
            $changeColorStyle = '';
            if (strpos($changeAmountStr, '+') !== false) {
                $changeColorStyle = 'color: #24ca24ff;'; // Pure Green
            } elseif (strpos($changeAmountStr, '-') !== false) {
                $changeColorStyle = 'color: #ff0000ff;'; // Pure Red
            } else {
                $changeColorStyle = 'color: #000000;'; // Default
            }

            $output[] = [
                'username' => $username,
                'before_balance' => '<span class="font-weight-bold" style="color:#0000FF;">' . $beforeBal . '</span>',
                'change_balance' => '<span class="font-weight-bold" style="' . $changeColorStyle . '">' . $changeAmountStr . '</span>',
                'after_balance' => '<b style="color:#0000FF; font-size: 15px;">' . $afterBal . '</b>',
                'time' => $this->formatEventTime($row['event_time'] ?? null, $row['raw_time'] ?? null),
                'reason' => trim((string) ($row['reason_text'] ?? ($row['reason'] ?? '--'))) ?: '--',
            ];
        }

        return $output;
    }

    private function formatUsername(string $username): string
    {
        $name = trim($username);
        return $name !== '' ? $name : '--';
    }

    private function formatAmount($value, bool $signed): string
    {
        if ($value === null || $value === '') {
            return '--';
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return '--';
        }

        $isNegative = strpos($raw, '-') !== false;
        $digits = preg_replace('/[^0-9]/', '', $raw);
        if ($digits === '') {
            return '--';
        }

        $number = (int) $digits;
        if ($isNegative) {
            $number *= -1;
        }

        $formatted = number_format(abs($number), 0, '.', ',');
        if ($signed) {
            return ($number > 0 ? '+' : ($number < 0 ? '-' : '')) . $formatted;
        }
        return ($number < 0 ? '-' : '') . $formatted;
    }

    private function formatEventTime($eventTime, $rawTime): string
    {
        $normalized = trim((string) $eventTime);
        if ($normalized === '' || $normalized === '0000-00-00 00:00:00') {
            $raw = trim((string) $rawTime);
            if ($raw === '') {
                return '--';
            }
            if (ctype_digit($raw)) {
                $normalized = date('Y-m-d H:i:s', (int) $raw);
            } else {
                $formats = ['H:i d-m-Y', 'Y-m-d H:i:s', 'd-m-Y H:i:s', 'd/m/Y H:i:s'];
                foreach ($formats as $format) {
                    $date = DateTime::createFromFormat($format, $raw);
                    if ($date instanceof DateTime) {
                        $normalized = $date->format('Y-m-d H:i:s');
                        break;
                    }
                }
                if ($normalized === '') {
                    $normalized = $raw;
                }
            }
        }

        if ($normalized === '' || $normalized === '--') {
            return '--';
        }

        $timeAgo = $this->timeAgo($normalized);
        return sprintf(
            '<span class="badge date-badge" data-toggle="tooltip" data-placement="top" title="%s">%s</span>',
            htmlspecialchars($timeAgo),
            htmlspecialchars($normalized)
        );
    }

    private function timeAgo($datetime, $full = false): string
    {
        $now = new DateTime();
        try {
            $ago = new DateTime($datetime);
        } catch (Exception $e) {
            return $datetime;
        }
        $diff = $now->diff($ago);

        $days = (int) $diff->d;
        $weeks = (int) floor($days / 7);
        $remainingDays = $days % 7;

        $parts = [];
        if ($diff->y)
            $parts['y'] = $diff->y . ' năm';
        if ($diff->m)
            $parts['m'] = $diff->m . ' tháng';
        if ($weeks)
            $parts['w'] = $weeks . ' tuần';
        if ($remainingDays)
            $parts['d'] = $remainingDays . ' ngày';
        if ($diff->h)
            $parts['h'] = $diff->h . ' tiếng';
        if ($diff->i)
            $parts['i'] = $diff->i . ' phút';
        if ($diff->s)
            $parts['s'] = $diff->s . ' giây';

        if (!$full) {
            $parts = array_slice($parts, 0, 1);
        }
        return $parts ? implode(', ', $parts) . ' trước' : 'vừa xong';
    }

    private function normalizePerPage(int $value): int
    {
        $allowed = [10, 20, 50, 100];
        return in_array($value, $allowed, true) ? $value : 20;
    }

    private function normalizeDateFilter(string $value): string
    {
        $allowed = ['all', 'today', '7days', '30days'];
        return in_array($value, $allowed, true) ? $value : 'all';
    }
}

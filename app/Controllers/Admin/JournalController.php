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
     * Transaction history page (formerly Activity journal).
     */
    public function activities()
    {
        $this->requireAdmin();

        $query = $this->buildQueryState();
        $rawRows = $this->journalModel->getActivityLogs($query);

        $this->renderJournal([
            'basePath' => 'admin/logs/activities',
            'pageTitle' => 'Lịch sử giao dịch',
            'pageIcon' => 'fas fa-shopping-cart',
            'cardTitle' => 'LỊCH SỬ GIAO DỊCH',
            'tableId' => 'activityJournalTable',
            'columns' => [
                ['key' => 'time', 'label' => 'Thời gian', 'align' => 'center'],
                ['key' => 'username', 'label' => 'Khách Hàng', 'align' => 'center'],
                ['key' => 'action', 'label' => 'Dịch Vụ / Hàng Hoá', 'align' => 'center'],
                ['key' => 'price', 'label' => 'Thành Tiền', 'align' => 'center'],
                ['key' => 'ip', 'label' => 'Địa chỉ IP', 'align' => 'center'],
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
                ['key' => 'time', 'label' => 'Thời gian', 'align' => 'center'],
                ['key' => 'username', 'label' => 'Khách Hàng', 'align' => 'center'],
                ['key' => 'reason', 'label' => 'Lý do', 'align' => 'center'],
                ['key' => 'before_balance', 'label' => 'Số dư trước', 'align' => 'center'],
                ['key' => 'change_balance', 'label' => 'Biến động', 'align' => 'center'],
                ['key' => 'after_balance', 'label' => 'Số dư hiện tại', 'align' => 'center'],
            ],
            'rows' => $this->mapBalanceRows($rawRows),
            'query' => $query,
        ]);
    }

    /**
     * Deposit history page.
     */
    public function deposits()
    {
        $this->requireAdmin();

        $query = $this->buildQueryState();
        $rawRows = $this->journalModel->getDepositLogs($query);

        $this->renderJournal([
            'basePath' => 'admin/deposits',
            'pageTitle' => 'Lịch sử nạp tiền',
            'pageIcon' => 'fas fa-hand-holding-usd',
            'cardTitle' => 'LỊCH SỬ NẠP TIỀN',
            'tableId' => 'depositJournalTable',
            'columns' => [
                ['key' => 'time', 'label' => 'Thời gian', 'align' => 'center'],
                ['key' => 'username', 'label' => 'Khách Hàng', 'align' => 'center'],
                ['key' => 'trans_id', 'label' => 'Mã GD', 'align' => 'center'],
                ['key' => 'method', 'label' => 'Phương Thức', 'align' => 'center'],
                ['key' => 'amount', 'label' => 'Thực Nhận', 'align' => 'center'],
                ['key' => 'reason', 'label' => 'Nội dung CK', 'align' => 'center'],
                ['key' => 'status', 'label' => 'Trạng Thái', 'align' => 'center'],
            ],
            'rows' => $this->mapDepositRows($rawRows),
            'query' => $query,
        ]);
    }

    /**
     * System logs page.
     */
    public function systemLogs()
    {
        $this->requireAdmin();

        $query = $this->buildQueryState();
        $rawRows = $this->systemLogModel->getLogsForJournal($query);

        $this->renderJournal([
            'basePath' => 'admin/logs/system',
            'pageTitle' => 'Nhật ký hệ thống',
            'pageIcon' => 'fas fa-shield-alt',
            'cardTitle' => 'NHẬT KÝ HỆ THỐNG',
            'tableId' => 'systemLogTable',
            'showSeverityFilter' => true,
            'columns' => [
                ['key' => 'username', 'label' => 'TÊN USER', 'align' => 'center'],
                ['key' => 'severity', 'label' => 'Mức độ', 'align' => 'center'],
                ['key' => 'time', 'label' => 'Thời gian', 'align' => 'center'],
                ['key' => 'module', 'label' => 'Module', 'align' => 'center'],
                ['key' => 'action', 'label' => 'Hành động', 'align' => 'center'],
                ['key' => 'description', 'label' => 'Mô tả chi tiết', 'align' => 'center'],
                ['key' => 'payload', 'label' => 'Payload', 'align' => 'center'],
            ],
            'rows' => $this->mapSystemLogRows($rawRows),
            'query' => $query,
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
            'showSeverityFilter' => false,
        ], $data));
    }

    /**
     * @return array<string, string>
     */
    private function buildQueryState(): array
    {
        return [
            'search' => trim((string) $this->get('search', '')),
            'severity' => trim((string) $this->get('severity', 'all')),
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
                $ip = '<span class="badge bg-light text-dark border">' . htmlspecialchars($ip) . '</span>';
            }

            $output[] = [
                'time' => FormatHelper::eventTime($row['event_time'] ?? null, $row['raw_time'] ?? null),
                'username' => '<a href="' . url('admin/users/edit/' . urlencode($username)) . '" class="font-weight-bold text-dark">' . htmlspecialchars($username) . '</a>',
                'action' => '<span class="font-weight-500 text-dark">' . htmlspecialchars(trim((string) ($row['action_name'] ?? '--'))) . '</span>',
                'price' => FormatHelper::price($row['gia'] ?? null),
                'ip' => $ip,
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

            $output[] = [
                'time' => FormatHelper::eventTime($row['event_time'] ?? null, $row['raw_time'] ?? null),
                'username' => '<a href="' . url('admin/users/edit/' . urlencode($username)) . '" class="font-weight-bold text-dark">' . htmlspecialchars($username) . '</a>',
                'reason' => trim((string) ($row['reason_text'] ?? ($row['reason'] ?? '--'))) ?: '--',
                'before_balance' => FormatHelper::initialBalance($row['before_balance'] ?? null),
                'change_balance' => FormatHelper::balanceChange($row['change_amount'] ?? ($row['raw_change'] ?? null)),
                'after_balance' => FormatHelper::currentBalance($row['after_balance'] ?? null),
            ];
        }

        return $output;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, string|int>>
     */
    private function mapDepositRows(array $rows): array
    {
        $output = [];
        foreach ($rows as $row) {
            $username = $this->formatUsername($row['username'] ?? '');

            $status = trim((string) ($row['status'] ?? 'pending'));
            $statusLabel = '<span class="badge bg-warning text-dark">Chờ Xử Lý</span>';
            if ($status === 'hoantat' || $status === 'success') {
                $statusLabel = '<span class="badge bg-success">Hoàn Tất</span>';
            } elseif ($status === 'thatbai' || $status === 'error' || $status === 'cancel') {
                $statusLabel = '<span class="badge bg-danger">Thất Bại</span>';
            }

            $method = trim((string) ($row['type'] ?? 'Unknown'));

            // Override formatted amount for deposit to force positive display
            $amountVal = (int) ($row['amount'] ?? 0);
            if ($amountVal > 0 && strpos((string) $row['amount'], '+') === false) {
                $amountVal = '+' . $amountVal;
            }

            $output[] = [
                'time' => FormatHelper::eventTime($row['event_time'] ?? null, $row['raw_time'] ?? null),
                'username' => '<a href="' . url('admin/users/edit/' . urlencode($username)) . '" class="font-weight-bold text-dark">' . htmlspecialchars($username) . '</a>',
                'trans_id' => '<span class="font-weight-500 text-monospace">' . htmlspecialchars($row['trans_id'] ?? '--') . '</span>',
                'method' => '<span class="badge bg-light text-dark border">' . htmlspecialchars($method) . '</span>',
                'amount' => FormatHelper::balanceChange($amountVal),
                'reason' => htmlspecialchars(trim((string) ($row['reason'] ?? '--'))),
                'status' => $statusLabel,
            ];
        }

        return $output;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, string|int>>
     */
    private function mapSystemLogRows(array $rows): array
    {
        $output = [];
        foreach ($rows as $log) {
            $displayUser = trim((string) $log['username']);

            // Try extracting from payload if username is empty (e.g for login/register actions)
            if ($displayUser === '' && !empty($log['payload'])) {
                $pData = json_decode($log['payload'], true);
                if (is_array($pData) && !empty($pData['username'])) {
                    $displayUser = trim((string) $pData['username']);
                }
            }

            if ($displayUser !== '') {
                $username = '<a href="' . url('admin/users/edit/' . urlencode($displayUser)) . '" class="font-weight-bold text-dark">' . htmlspecialchars($displayUser) . '</a>';
            } else {
                $username = '<span class="text-muted">Guest</span>';
            }

            $severityLabel = '<span class="badge bg-secondary">' . htmlspecialchars($log['severity']) . '</span>';
            if ($log['severity'] === 'INFO') {
                $severityLabel = '<span class="badge bg-info">INFO</span>';
            } elseif ($log['severity'] === 'WARNING') {
                $severityLabel = '<span class="badge bg-warning text-dark">WARNING</span>';
            } elseif ($log['severity'] === 'DANGER') {
                $severityLabel = '<span class="badge bg-danger pulse"><i class="fas fa-exclamation-triangle"></i> DANGER</span>';
            }

            $moduleLabel = '<span class="badge bg-light text-dark border">' . htmlspecialchars($log['module']) . '</span>';
            $ipLabel = '<span class="badge bg-soft-primary text-primary border-primary-light">' . htmlspecialchars($log['ip_address'] ?? 'N/A') . '</span>';

            $payloadData = '';
            if (!empty($log['payload'])) {
                $encoded = htmlspecialchars($log['payload'], ENT_QUOTES, 'UTF-8');
                $payloadData = '<div class="log-payload" onclick="showPayloadModal(' . $encoded . ')">Xem</div>';
            } else {
                $payloadData = '<span class="text-muted">-</span>';
            }

            $output[] = [
                'time' => FormatHelper::eventTime($log['created_at'], $log['created_at']),
                'severity' => $severityLabel,
                'module' => $moduleLabel,
                'username' => $username,
                'action' => '<span class="font-weight-500 text-dark">' . htmlspecialchars($log['action']) . '</span>',
                'description' => '<span class="text-muted">' . htmlspecialchars($log['description']) . '</span>',
                'payload' => $payloadData,
            ];
        }

        return $output;
    }

    private function formatUsername(string $username): string
    {
        $name = trim($username);
        return $name !== '' ? $name : '--';
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

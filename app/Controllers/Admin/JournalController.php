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
    private $orderModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->journalModel = new AdminJournal();
        $this->systemLogModel = new SystemLog();
        $this->orderModel = new Order();
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

    public function buying()
    {
        $this->requireAdmin();

        $query = $this->buildQueryState();
        $rawRows = $this->journalModel->getPurchaseHistoryLogs($query);

        $this->renderJournal([
            'basePath' => 'admin/logs/buying',
            'pageTitle' => 'Lịch sử mua hàng',
            'pageIcon' => 'fas fa-shopping-bag',
            'cardTitle' => 'LỊCH SỬ MUA HÀNG',
            'tableId' => 'purchaseHistoryTable',
            'columns' => [
                ['key' => 'time', 'label' => 'Thời gian', 'align' => 'center'],
                ['key' => 'order_code', 'label' => 'Mã đơn', 'align' => 'center'],
                ['key' => 'username', 'label' => 'Khách hàng', 'align' => 'center'],
                ['key' => 'product_name', 'label' => 'Sản phẩm', 'align' => 'center'],
                ['key' => 'quantity', 'label' => 'SL', 'align' => 'center'],
                ['key' => 'price', 'label' => 'Giá', 'align' => 'center'],
                ['key' => 'status', 'label' => 'Trạng thái', 'align' => 'center'],
                ['key' => 'actions', 'label' => 'Thao tác', 'align' => 'center'],
            ],
            'rows' => $this->mapPurchaseRows($rawRows),
            'query' => $query,
        ]);
    }


    public function purchaseDetail($id)
    {
        $this->requireAdmin();

        $orderId = (int) $id;
        if ($orderId <= 0) {
            return $this->json(['success' => false, 'message' => 'ID đơn hàng không hợp lệ.'], 400);
        }

        $order = $this->orderModel->getById($orderId);
        if (!$order) {
            return $this->json(['success' => false, 'message' => 'Không tìm thấy đơn hàng.'], 404);
        }

        return $this->json([
            'success' => true,
            'order' => [
                'id' => (int) ($order['id'] ?? 0),
                'order_code' => (string) ($order['order_code'] ?? ''),
                'order_code_short' => (string) ($order['order_code_short'] ?? ''),
                'username' => (string) ($order['username'] ?? ''),
                'product_name' => (string) ($order['product_name'] ?? ''),
                'price' => (int) ($order['price'] ?? 0),
                'quantity' => (int) ($order['quantity'] ?? 1),
                'status' => (string) ($order['status'] ?? ''),
                'customer_input' => (string) ($order['customer_input'] ?? ''),
                'delivery_content' => (string) ($order['stock_content_plain'] ?? ''),
                'fulfilled_by' => (string) ($order['fulfilled_by'] ?? ''),
                'fulfilled_at' => (string) ($order['fulfilled_at'] ?? ''),
                'cancel_reason' => (string) ($order['cancel_reason'] ?? ''),
                'created_at' => (string) ($order['created_at'] ?? ''),
            ],
        ]);
    }

    public function fulfillPurchase()
    {
        $this->requireAdmin();

        $user = $this->authService->getCurrentUser();
        $adminUsername = trim((string) ($user['username'] ?? 'admin'));
        if ($adminUsername === '') {
            $adminUsername = 'admin';
        }

        $orderId = (int) $this->post('order_id', 0);
        $deliveryContent = (string) $this->post('delivery_content', '');

        $result = $this->orderModel->fulfillPendingOrder($orderId, $deliveryContent, $adminUsername);
        if (!empty($result['success']) && class_exists('Logger')) {
            try {
                Logger::info('Billing', 'manual_order_fulfill_success', 'Admin giao noi dung don pending', [
                    'order_id' => $orderId,
                    'admin' => $adminUsername,
                ]);
            } catch (Throwable $e) {
                // optional logging only
            }
        }

        return $this->json($result, !empty($result['success']) ? 200 : 400);
    }

    public function cancelPurchase()
    {
        $this->requireAdmin();

        $user = $this->authService->getCurrentUser();
        $adminUsername = trim((string) ($user['username'] ?? 'admin'));
        if ($adminUsername === '') {
            $adminUsername = 'admin';
        }

        $orderId = (int) $this->post('order_id', 0);
        $cancelReason = (string) $this->post('cancel_reason', '');

        $result = $this->orderModel->cancelPendingOrder($orderId, $adminUsername, $cancelReason);
        if (!empty($result['success']) && class_exists('Logger')) {
            try {
                Logger::info('Billing', 'manual_order_cancel_success', 'Admin huy don pending va hoan tien', [
                    'order_id' => $orderId,
                    'admin' => $adminUsername,
                ]);
            } catch (Throwable $e) {
                // optional logging only
            }
        }

        return $this->json($result, !empty($result['success']) ? 200 : 400);
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
            'pageTitle' => 'Biáº¿n Ä‘á»™ng sá»‘ dÆ°',
            'pageIcon' => 'fas fa-money-check-alt',
            'cardTitle' => 'NHáº¬T KÃ THAY Äá»”I Sá» DÆ¯',
            'tableId' => 'balanceChangeTable',
            'columns' => [
                ['key' => 'time', 'label' => 'Thá»i gian', 'align' => 'center'],
                ['key' => 'username', 'label' => 'KhÃ¡ch HÃ ng', 'align' => 'center'],
                ['key' => 'reason', 'label' => 'LÃ½ do', 'align' => 'center'],
                ['key' => 'before_balance', 'label' => 'Sá»‘ dÆ° trÆ°á»›c', 'align' => 'center'],
                ['key' => 'change_balance', 'label' => 'Biáº¿n Ä‘á»™ng', 'align' => 'center'],
                ['key' => 'after_balance', 'label' => 'Sá»‘ dÆ° hiá»‡n táº¡i', 'align' => 'center'],
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
            'basePath' => 'admin/logs/deposits',
            'pageTitle' => 'Lá»‹ch sá»­ náº¡p tiá»n',
            'pageIcon' => 'fas fa-hand-holding-usd',
            'cardTitle' => 'Lá»ŠCH Sá»¬ Náº P TIá»€N',
            'tableId' => 'depositJournalTable',
            'columns' => [
                ['key' => 'time', 'label' => 'Thá»i gian', 'align' => 'center'],
                ['key' => 'username', 'label' => 'KhÃ¡ch HÃ ng', 'align' => 'center'],
                ['key' => 'trans_id', 'label' => 'MÃ£ GD', 'align' => 'center'],
                ['key' => 'method', 'label' => 'PhÆ°Æ¡ng Thá»©c', 'align' => 'center'],
                ['key' => 'amount', 'label' => 'Thá»±c Nháº­n', 'align' => 'center'],
                ['key' => 'reason', 'label' => 'Ná»™i dung CK', 'align' => 'center'],
                ['key' => 'status', 'label' => 'Tráº¡ng ThÃ¡i', 'align' => 'center'],
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
            'pageTitle' => 'Nháº­t kÃ½ há»‡ thá»‘ng',
            'pageIcon' => 'fas fa-shield-alt',
            'cardTitle' => 'NHáº¬T KÃ Há»† THá»NG',
            'tableId' => 'systemLogTable',
            'showSeverityFilter' => true,
            'columns' => [
                ['key' => 'username', 'label' => 'TÃŠN USER', 'align' => 'center'],
                ['key' => 'severity', 'label' => 'Má»©c Ä‘á»™', 'align' => 'center'],
                ['key' => 'time', 'label' => 'Thá»i gian', 'align' => 'center'],
                ['key' => 'module', 'label' => 'Module', 'align' => 'center'],
                ['key' => 'action', 'label' => 'HÃ nh Ä‘á»™ng', 'align' => 'center'],
                ['key' => 'description', 'label' => 'MÃ´ táº£ chi tiáº¿t', 'align' => 'center'],
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
    private function mapPurchaseRows(array $rows): array
    {
        $output = [];
        foreach ($rows as $row) {
            $username = $this->formatUsername($row['username'] ?? '');

            $status = trim((string) ($row['status'] ?? 'processing'));
            if ($status === 'completed') {
                $statusLabel = '<span class="badge bg-success">Hoan tat</span>';
            } elseif ($status === 'cancelled') {
                $statusLabel = '<span class="badge bg-secondary">Da huy</span>';
            } elseif ($status === 'pending') {
                $statusLabel = '<span class="badge bg-danger">Pending</span>';
            } elseif ($status === 'processing') {
                $statusLabel = '<span class="badge bg-warning text-dark">Dang xu ly</span>';
            } else {
                $statusLabel = '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
            }

            $price = (int) ($row['price'] ?? 0);
            $priceFormatted = '<span class="font-weight-bold" style="color:#ff0000;">-' . number_format($price, 0, '.', ',') . 'd</span>';
            $quantity = max(1, (int) ($row['quantity'] ?? 1));
            $orderId = (int) ($row['id'] ?? 0);
            $rawOrderCode = (string) ($row['order_code'] ?? '--');
            $shortOrderCode = $this->shortOrderCode($rawOrderCode);

            $actionButtons = '<div class="btn-group btn-group-sm" role="group">'
                . '<button type="button" class="btn btn-info js-order-view" data-order-id="' . $orderId . '" title="Xem chi tiet"><i class="fas fa-eye"></i></button>';
            if (in_array($status, ['pending', 'processing'], true)) {
                $actionButtons .= '<button type="button" class="btn btn-success js-order-fulfill" data-order-id="' . $orderId . '" title="Giao hang"><i class="fas fa-paper-plane"></i></button>';
            }
            if ($status === 'pending') {
                $actionButtons .= '<button type="button" class="btn btn-danger js-order-cancel" data-order-id="' . $orderId . '" title="Huy don + hoan tien"><i class="fas fa-times"></i></button>';
            }
            $actionButtons .= '</div>';

            $output[] = [
                'time' => FormatHelper::eventTime($row['event_time'] ?? null, $row['raw_time'] ?? null),
                'order_code' => '<span class="badge bg-light text-dark border font-weight-bold" style="font-family:monospace;">' . htmlspecialchars($shortOrderCode !== '' ? $shortOrderCode : $rawOrderCode) . '</span>',
                'username' => '<a href="' . url('admin/users/edit/' . urlencode($username)) . '" class="font-weight-bold text-dark">' . htmlspecialchars($username) . '</a>',
                'product_name' => '<span class="font-weight-500 text-dark">' . htmlspecialchars(trim((string) ($row['product_name'] ?? '--'))) . '</span>',
                'quantity' => '<span class="badge bg-light text-dark border">' . $quantity . '</span>',
                'price' => $priceFormatted,
                'status' => $statusLabel,
                'actions' => $actionButtons,
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
            $statusLabel = '<span class="badge bg-warning text-dark">Chá» Xá»­ LÃ½</span>';
            if ($status === 'hoantat' || $status === 'success') {
                $statusLabel = '<span class="badge bg-success">HoÃ n Táº¥t</span>';
            } elseif ($status === 'thatbai' || $status === 'error' || $status === 'cancel') {
                $statusLabel = '<span class="badge bg-danger">Tháº¥t Báº¡i</span>';
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
            $log = $this->normalizeSystemLogRow($log);
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

    /**
     * @param array<string, mixed> $log
     * @return array<string, mixed>
     */
    private function normalizeSystemLogRow(array $log): array
    {
        foreach (['username', 'module', 'action', 'description', 'ip_address'] as $field) {
            if (isset($log[$field]) && is_string($log[$field])) {
                $log[$field] = $this->normalizeMojibakeText($log[$field]);
            }
        }

        if (!empty($log['payload']) && is_string($log['payload'])) {
            $payloadRaw = $this->normalizeMojibakeText($log['payload']);
            $decoded = json_decode($payloadRaw, true);
            if (is_array($decoded)) {
                $decoded = $this->normalizeArrayStrings($decoded);
                $log['payload'] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            } else {
                $log['payload'] = $payloadRaw;
            }
        }

        return $log;
    }

    /**
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private function normalizeArrayStrings(array $data): array
    {
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $data[$k] = $this->normalizeMojibakeText($v);
            } elseif (is_array($v)) {
                $data[$k] = $this->normalizeArrayStrings($v);
            }
        }
        return $data;
    }

    private function normalizeMojibakeText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return $text;
        }

        if (!preg_match('/(?:Ãƒ.|Ã„.|Ã¡Âº|Ã¡Â»|Ã†.|Ã¢â‚¬Â¦|Ã¢â‚¬â„¢|Ã¢â‚¬Å“|Ã¢â‚¬|Ã‚.)/u', $text)) {
            return $text;
        }

        $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $text);
        if (!is_string($converted) || $converted === '') {
            return $text;
        }

        return $this->mojibakeScore($converted) < $this->mojibakeScore($text) ? $converted : $text;
    }

    private function mojibakeScore(string $text): int
    {
        preg_match_all('/(?:Ãƒ.|Ã„.|Ã¡Âº|Ã¡Â»|Ã†.|Ã¢â‚¬Â¦|Ã¢â‚¬â„¢|Ã¢â‚¬Å“|Ã¢â‚¬|Ã‚.)/u', $text, $matches);
        return count($matches[0] ?? []);
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

    private function shortOrderCode(string $orderCode): string
    {
        $orderCode = trim($orderCode);
        if ($orderCode === '' || $orderCode === '--') {
            return '';
        }
        return strtoupper(substr(hash('sha256', $orderCode), 0, 8));
    }
}

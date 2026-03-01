<?php

/**
 * User Order History Controller
 * Lịch sử đơn hàng của người dùng (AJAX DataTable)
 */
class OrderHistoryController extends Controller
{
    private $authService;
    private $orderModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->orderModel = new Order();
    }

    public function index()
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        $siteConfig = Config::getSiteConfig();

        $this->view('profile/history-orders', [
            'user' => $user,
            'username' => $user['username'] ?? '',
            'chungapi' => $siteConfig,
            'activePage' => 'order-history',
        ]);
    }

    public function data()
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->authService->getCurrentUser();
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;
        $start = isset($_POST['start']) ? max(0, (int) $_POST['start']) : 0;
        $length = isset($_POST['length']) ? max(1, (int) $_POST['length']) : 10;

        $searchDt = $_POST['search']['value'] ?? '';
        $searchCustom = $_POST['keyword'] ?? '';

        $filters = [
            'search' => (string) ($searchDt !== '' ? $searchDt : $searchCustom),
            'time_range' => (string) ($_POST['time_range'] ?? ''),
            'sort_date' => (string) ($_POST['sort_date'] ?? 'all'),
        ];

        $recordsTotal = $this->orderModel->countUserVisibleOrders($userId, []);
        $searchKeyword = trim((string) ($filters['search'] ?? ''));
        if ($searchKeyword !== '') {
            $allRows = $this->orderModel->getAllUserVisibleOrders($userId, $filters);
            $filteredRows = $this->orderModel->smartFilterUserVisibleOrders($allRows, $searchKeyword);
            $recordsFiltered = count($filteredRows);
            $rows = array_slice($filteredRows, $start, $length);
        } else {
            $recordsFiltered = $this->orderModel->countUserVisibleOrders($userId, $filters);
            $rows = $this->orderModel->getUserVisibleOrders($userId, $filters, $start, $length);
        }

        $data = [];
        $timeService = TimeService::instance();
        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            $statusLabel = match ($status) {
                'completed' => 'Hoàn tất',
                'pending' => 'Chờ xử lý',
                'processing' => 'Đang xử lý',
                'cancelled' => 'Đã hủy',
                default => $status !== '' ? ucfirst($status) : 'Không rõ',
            };

            $timeMeta = $timeService->normalizeApiTime($row['created_at'] ?? null);
            $timeDisplay = (string) ($timeMeta['display'] ?? ((string) ($row['created_at'] ?? '')));
            $timeAgo = $timeMeta['ts'] ? $timeService->diffForHumans($timeMeta['ts']) : (!empty($row['created_at']) ? FormatHelper::timeAgo((string) $row['created_at']) : '');

            $data[] = [
                'id' => (int) ($row['id'] ?? 0),
                'order_code' => (string) ($row['order_code'] ?? ''),
                'order_code_short' => (string) ($row['order_code_short'] ?? ''),
                'product_name' => (string) ($row['product_name'] ?? ''),
                'quantity' => (int) ($row['quantity'] ?? 1),
                'payment' => (int) ($row['price'] ?? 0),
                'status' => $status,
                'status_label' => $statusLabel,
                'time_display' => FormatHelper::eventTime($row['created_at'] ?? null, $row['created_at'] ?? null),
                'time_raw' => $timeDisplay,
                'time_ts' => $timeMeta['ts'],
                'time_iso' => (string) ($timeMeta['iso'] ?? ''),
                'time_iso_utc' => (string) ($timeMeta['iso_utc'] ?? ''),
                'time_ago' => $timeAgo,
                'fulfilled_at' => (string) ($row['fulfilled_at'] ?? ''),
                'fulfilled_at_display' => !empty($row['fulfilled_at']) ? TimeService::instance()->formatDisplay($row['fulfilled_at']) : '',
            ];
        }

        return $this->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function detail($id)
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = $this->authService->getCurrentUser();
        $userId = (int) ($user['id'] ?? 0);
        $order = $this->orderModel->getByIdForUser((int) $id, $userId);

        if (!$order) {
            return $this->json(['success' => false, 'message' => 'Không tìm thấy đơn hàng.'], 404);
        }

        return $this->json([
            'success' => true,
            'order' => [
                'id' => (int) ($order['id'] ?? 0),
                'order_code' => (string) ($order['order_code'] ?? ''),
                'order_code_short' => (string) ($order['order_code_short'] ?? ''),
                'product_name' => (string) ($order['product_name'] ?? ''),
                'quantity' => (int) ($order['quantity'] ?? 1),
                'price' => (int) ($order['price'] ?? 0),
                'status' => (string) ($order['status'] ?? ''),
                'created_at' => (string) ($order['created_at'] ?? ''),
                'created_at_ts' => TimeService::instance()->toTimestamp($order['created_at'] ?? null),
                'created_at_iso' => TimeService::instance()->toIso8601($order['created_at'] ?? null),
                'created_at_iso_utc' => TimeService::instance()->toIso8601Utc($order['created_at'] ?? null),
                'created_at_display' => TimeService::instance()->formatDisplay($order['created_at'] ?? null),
                'customer_input' => (string) ($order['customer_input'] ?? ''),
                'delivery_content' => ((string) ($order['status'] ?? '') === 'pending') ? '' : (string) ($order['stock_content_plain'] ?? ''),
                'cancel_reason' => (string) ($order['cancel_reason'] ?? ''),
                'fulfilled_at' => (string) ($order['fulfilled_at'] ?? ''),
                'fulfilled_at_display' => !empty($order['fulfilled_at']) ? TimeService::instance()->formatDisplay($order['fulfilled_at']) : '',
            ],
        ]);
    }

    public function download($id)
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        $userId = (int) ($user['id'] ?? 0);
        $order = $this->orderModel->getByIdForUser((int) $id, $userId);

        if (!$order) {
            http_response_code(404);
            echo 'Order not found';
            exit;
        }

        $content = [];
        $content[] = 'MA_DON_HANG: ' . ((string) ($order['order_code_short'] ?? '') ?: (string) ($order['order_code'] ?? ''));
        $content[] = 'SAN_PHAM: ' . (string) ($order['product_name'] ?? '');
        $content[] = 'SO_LUONG: ' . (int) ($order['quantity'] ?? 1);
        $content[] = 'THANH_TOAN: ' . (int) ($order['price'] ?? 0) . ' VND';
        $content[] = 'TRANG_THAI: ' . (string) ($order['status'] ?? '');
        $content[] = 'THOI_GIAN: ' . (string) ($order['created_at'] ?? '');

        $customerInput = trim((string) ($order['customer_input'] ?? ''));
        $deliveryContent = trim((string) ($order['stock_content_plain'] ?? ''));
        $cancelReason = trim((string) ($order['cancel_reason'] ?? ''));

        if ($customerInput !== '') {
            $content[] = '';
            $content[] = '--- THONG_TIN_BAN_DA_GUI ---';
            $content[] = $customerInput;
        }

        if ($deliveryContent !== '' && (string) ($order['status'] ?? '') !== 'pending') {
            $content[] = '';
            $content[] = '--- NOI_DUNG_BAN_GIAO ---';
            $content[] = $deliveryContent;
        }

        if ($cancelReason !== '') {
            $content[] = '';
            $content[] = '--- LY_DO_HUY / PHAN_HOI ---';
            $content[] = $cancelReason;
        }

        $filename = 'order-' . preg_replace('/[^A-Z0-9]/', '', (string) ($order['order_code_short'] ?? 'ORDER')) . '.txt';
        $body = implode(PHP_EOL, $content) . PHP_EOL;

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $body;
        exit;
    }

    public function delete()
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = $this->authService->getCurrentUser();
        $userId = (int) ($user['id'] ?? 0);
        $orderId = (int) $this->post('order_id', 0);

        $result = $this->orderModel->hideForUser($orderId, $userId);
        return $this->json($result, !empty($result['success']) ? 200 : 400);
    }
}

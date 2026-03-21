<?php

/**
 * ChatGptController — Public-facing
 * Handles product page + order purchase flow
 */
class ChatGptController extends Controller
{
    private $farmModel;
    private $orderModel;
    private $allowModel;
    private $auditLog;
    private $farmService;

    public function __construct()
    {
        $this->farmModel = new ChatGptFarm();
        $this->orderModel = new ChatGptOrder();
        $this->allowModel = new ChatGptAllowedInvite();
        $this->auditLog = new ChatGptAuditLog();
        $this->farmService = new ChatGptFarmService();
    }

    /**
     * GET /chatgpt/pro-1-month-add-farm
     * Product page with Gmail entry form
     */
    public function product()
    {
        $availableSeats = $this->farmModel->getTotalAvailableSeats();

        $this->view('chatgpt/product', [
            'availableSeats' => $availableSeats,
            'stock' => $availableSeats,
            'error' => $_SESSION['cgpt_error'] ?? null,
            'success' => $_SESSION['cgpt_success'] ?? null,
        ]);

        unset($_SESSION['cgpt_error'], $_SESSION['cgpt_success']);
    }

    /**
     * POST /chatgpt/pro-1-month-add-farm/order
     * Process order: validate email → pick farm → send invite → save DB
     */
    public function order()
    {
        // CSRF / basic validation
        $email = strtolower(trim((string) $this->post('customer_email', '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['cgpt_error'] = 'Vui lòng nhập Gmail hợp lệ.';
            $this->redirect(url('chatgpt/pro-1-month-add-farm'));
            return;
        }

        // Check duplicate active order
        if ($this->orderModel->hasActiveOrder($email)) {
            $_SESSION['cgpt_error'] = 'Email này đã có đơn hàng đang hoạt động. Vui lòng dùng Gmail khác.';
            $this->redirect(url('chatgpt/pro-1-month-add-farm'));
            return;
        }

        // Find best farm
        $farm = $this->farmModel->getBestAvailableFarm();
        if (!$farm) {
            $_SESSION['cgpt_error'] = 'Hiện tại không có slot trống. Vui lòng thử lại sau.';
            $this->redirect(url('chatgpt/pro-1-month-add-farm'));
            return;
        }

        $farmId = (int) $farm['id'];

        // Create order record (status = pending first)
        $orderId = $this->orderModel->create([
            'customer_email' => $email,
            'product_code' => 'chatgpt_pro_add_farm_1_month',
            'status' => 'pending',
            'assigned_farm_id' => $farmId,
        ]);

        // Send invite via OpenAI API
        $inviteResult = $this->farmService->createInvite($farm, $email, 'reader');

        if (!$inviteResult['success']) {
            $this->orderModel->updateStatus($orderId, 'failed', [
                'note' => 'Invite API error: ' . ($inviteResult['error'] ?? 'unknown')
            ]);
            $this->auditLog->log([
                'farm_id' => $farmId,
                'farm_name' => $farm['farm_name'],
                'action' => 'SYSTEM_INVITE_FAILED',
                'actor_email' => 'system',
                'target_email' => $email,
                'result' => 'FAIL',
                'reason' => $inviteResult['error'] ?? 'api_error',
                'meta' => ['order_id' => $orderId],
            ]);
            $_SESSION['cgpt_error'] = 'Lỗi khi gửi invite. Vui lòng liên hệ hỗ trợ. (order #' . $orderId . ')';
            $this->redirect(url('chatgpt/pro-1-month-add-farm'));
            return;
        }

        // Update order to inviting
        $this->orderModel->updateStatus($orderId, 'inviting');

        // Save allowed invite
        $allowId = $this->allowModel->createInvite($orderId, $farmId, $email, $inviteResult['invite_id'] ?? null);

        // Increment seat_used
        $this->farmModel->incrementSeatUsed($farmId);

        // Write audit log
        $this->auditLog->log([
            'farm_id' => $farmId,
            'farm_name' => $farm['farm_name'],
            'action' => 'SYSTEM_INVITE_CREATED',
            'actor_email' => 'system',
            'target_email' => $email,
            'result' => 'OK',
            'reason' => 'user_purchase',
            'meta' => [
                'order_id' => $orderId,
                'invite_id' => $inviteResult['invite_id'] ?? null,
            ],
        ]);

        // Redirect to success
        $_SESSION['cgpt_order_id'] = $orderId;
        $_SESSION['cgpt_order_email'] = $email;
        $this->redirect(url('chatgpt/pro-1-month-add-farm/success'));
    }

    /**
     * GET /chatgpt/pro-1-month-add-farm/success
     * Order success page
     */
    public function success()
    {
        $orderId = (int) ($_SESSION['cgpt_order_id'] ?? 0);
        $email = (string) ($_SESSION['cgpt_order_email'] ?? '');

        if ($orderId <= 0 || $email === '') {
            $this->redirect(url('chatgpt/pro-1-month-add-farm'));
            return;
        }

        $order = $this->orderModel->getById($orderId);

        $this->view('chatgpt/order_success', [
            'order' => $order,
            'email' => $email,
        ]);
    }
}

<?php

/**
 * ChatGptAdminController — Admin Panel
 * Manages farms, orders, members, invites, and audit logs
 * Requires admin level 9
 */
class ChatGptAdminController extends Controller
{
    private $authService;
    private $farmModel;
    private $orderModel;
    private $allowModel;
    private $snapModel;
    private $auditLog;
    private $farmService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->farmModel = new ChatGptFarm();
        $this->orderModel = new ChatGptOrder();
        $this->allowModel = new ChatGptAllowedInvite();
        $this->snapModel = new ChatGptSnapshot();
        $this->auditLog = new ChatGptAuditLog();
        $this->farmService = new ChatGptFarmService();
    }

    private function requireAdmin()
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        if (!isset($user['level']) || (int) $user['level'] !== 9) {
            http_response_code(403);
            die('Truy cập bị từ chối');
        }
    }

    // ==================== FARMS ====================

    public function farms()
    {
        $this->requireAdmin();
        $farms = $this->farmModel->getAll();
        $stats = $this->farmModel->getStats();
        $orderStats = $this->orderModel->getStats();
        $this->view('admin/chatgpt/farms', [
            'farms' => $farms,
            'stats' => $stats,
            'orderStats' => $orderStats,
        ]);
    }

    public function farmAdd()
    {
        $this->requireAdmin();
        $this->view('admin/chatgpt/farms_add', [
            'error' => $_SESSION['cgpt_admin_error'] ?? null,
        ]);
        unset($_SESSION['cgpt_admin_error']);
    }

    public function farmStore()
    {
        $this->requireAdmin();

        $farmName = trim((string) $this->post('farm_name', ''));
        $adminEmail = strtolower(trim((string) $this->post('admin_email', '')));
        $apiKey = trim((string) $this->post('admin_api_key', ''));
        $seatTotal = max(1, (int) $this->post('seat_total', 4));

        if ($farmName === '' || $adminEmail === '' || $apiKey === '') {
            $_SESSION['cgpt_admin_error'] = 'Vui lòng điền đầy đủ thông tin.';
            $this->redirect(url('admin/chatgpt/farms/add'));
            return;
        }

        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['cgpt_admin_error'] = 'Admin Email không hợp lệ.';
            $this->redirect(url('admin/chatgpt/farms/add'));
            return;
        }

        // Test API key
        $testFarm = ['id' => 0, 'admin_api_key' => $apiKey];
        if (!$this->farmService->validateKey($testFarm)) {
            $_SESSION['cgpt_admin_error'] = 'API Key không hợp lệ hoặc không kết nối được OpenAI. Kiểm tra lại key.';
            $this->redirect(url('admin/chatgpt/farms/add'));
            return;
        }

        // Encrypt API key before saving
        $encryptedKey = $this->farmService->encryptKey($apiKey);

        $this->farmModel->create([
            'farm_name' => $farmName,
            'admin_email' => $adminEmail,
            'admin_api_key' => $encryptedKey,
            'seat_total' => $seatTotal,
        ]);

        $this->auditLog->log([
            'farm_id' => null,
            'farm_name' => $farmName,
            'action' => 'FARM_ADDED',
            'actor_email' => $this->authService->getCurrentUser()['email'] ?? 'admin',
            'result' => 'OK',
            'reason' => 'manual_add',
        ]);

        $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành công', 'message' => "Đã thêm farm [{$farmName}] thành công."];
        $this->redirect(url('admin/chatgpt/farms'));
    }

    public function farmEdit($id)
    {
        $this->requireAdmin();
        $farm = $this->farmModel->getById((int) $id);
        if (!$farm) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Farm không tồn tại.'];
            $this->redirect(url('admin/chatgpt/farms'));
            return;
        }
        // Mask API key for display
        $farm['admin_api_key_masked'] = substr($farm['admin_api_key'], 0, 10) . '...';
        $this->view('admin/chatgpt/farms_edit', [
            'farm' => $farm,
            'error' => $_SESSION['cgpt_admin_error'] ?? null,
        ]);
        unset($_SESSION['cgpt_admin_error']);
    }

    public function farmUpdate($id)
    {
        $this->requireAdmin();
        $farmId = (int) $id;
        $farm = $this->farmModel->getById($farmId);
        if (!$farm) {
            $this->redirect(url('admin/chatgpt/farms'));
            return;
        }

        $data = [
            'farm_name' => trim((string) $this->post('farm_name', $farm['farm_name'])),
            'admin_email' => strtolower(trim((string) $this->post('admin_email', $farm['admin_email']))),
            'seat_total' => max(1, (int) $this->post('seat_total', $farm['seat_total'])),
            'status' => (string) $this->post('status', $farm['status']),
        ];

        $newKey = trim((string) $this->post('admin_api_key', ''));
        if ($newKey !== '') {
            // Test new key
            $testFarm = ['id' => $farmId, 'admin_api_key' => $newKey];
            if (!$this->farmService->validateKey($testFarm)) {
                $_SESSION['cgpt_admin_error'] = 'API Key mới không hợp lệ.';
                $this->redirect(url('admin/chatgpt/farms/edit/' . $farmId));
                return;
            }
            $data['admin_api_key'] = $this->farmService->encryptKey($newKey);
        }

        $this->farmModel->update($farmId, $data);
        $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành công', 'message' => 'Đã cập nhật farm.'];
        $this->redirect(url('admin/chatgpt/farms'));
    }

    // ==================== ORDERS ====================

    public function orders()
    {
        $this->requireAdmin();
        $filters = [
            'status' => $this->get('status', ''),
            'farm_id' => (int) $this->get('farm_id', 0),
            'email' => $this->get('email', ''),
        ];
        $orders = $this->orderModel->getAll($filters, 200);
        $farms = $this->farmModel->getAll();
        $stats = $this->orderModel->getStats();

        $this->view('admin/chatgpt/orders', [
            'orders' => $orders,
            'farms' => $farms,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    // ==================== MEMBERS ====================

    public function members()
    {
        $this->requireAdmin();
        $filters = [
            'farm_id' => (int) $this->get('farm_id', 0),
            'source' => $this->get('source', ''),
        ];
        $members = $this->snapModel->getAllMembers($filters);
        $farms = $this->farmModel->getAll();

        $this->view('admin/chatgpt/members', [
            'members' => $members,
            'farms' => $farms,
            'filters' => $filters,
        ]);
    }

    // ==================== INVITES ====================

    public function invites()
    {
        $this->requireAdmin();
        $filters = [
            'farm_id' => (int) $this->get('farm_id', 0),
            'source' => $this->get('source', ''),
        ];
        $invites = $this->snapModel->getAllInvites($filters);
        $allowed = $this->allowModel->getAll([], 200);
        $farms = $this->farmModel->getAll();

        $this->view('admin/chatgpt/invites', [
            'invites' => $invites,
            'allowed' => $allowed,
            'farms' => $farms,
            'filters' => $filters,
        ]);
    }

    // ==================== LOGS ====================

    public function logs()
    {
        $this->requireAdmin();
        $filters = [
            'farm_id' => (int) $this->get('farm_id', 0),
            'action' => $this->get('action', ''),
            'target_email' => $this->get('target_email', ''),
            'date_from' => $this->get('date_from', ''),
            'date_to' => $this->get('date_to', ''),
        ];
        $logs = $this->auditLog->getAll($filters, 300);
        $farms = $this->farmModel->getAll();
        $actionTypes = $this->auditLog->getActionTypes();

        $this->view('admin/chatgpt/logs', [
            'logs' => $logs,
            'farms' => $farms,
            'actionTypes' => $actionTypes,
            'filters' => $filters,
        ]);
    }

    // ==================== QUICK ACTIONS ====================

    /**
     * POST /admin/chatgpt/farms/sync-now/{id}
     * Manually trigger a single farm sync (calls guard logic inline)
     */
    public function farmSyncNow($id)
    {
        $this->requireAdmin();
        $farmId = (int) $id;
        $farm = $this->farmModel->getById($farmId);
        if (!$farm) {
            return $this->json(['success' => false, 'message' => 'Farm không tồn tại'], 404);
        }

        $liveMembers = $this->farmService->listMembers($farm);
        $liveInvites = $this->farmService->listInvites($farm);

        $allowedEmails = array_column($this->allowModel->getAllowedEmailsForFarm($farmId), 'target_email');
        $allowedInviteIds = $this->allowModel->getAllowedInviteIdsForFarm($farmId);

        foreach ($liveMembers as $m) {
            $email = strtolower(trim($m['email'] ?? ''));
            $userId = $m['id'] ?? '';
            $role = $m['role'] ?? 'reader';
            if ($email === '')
                continue;
            $source = in_array($email, $allowedEmails, true) ? 'approved' : 'detected_unknown';
            $this->snapModel->upsertMember($farmId, $userId, $email, $role, $source);
        }

        foreach ($liveInvites as $inv) {
            $inviteId = $inv['id'] ?? '';
            $email = strtolower(trim($inv['email'] ?? ''));
            $status = $inv['status'] ?? 'pending';
            if ($inviteId === '' || $email === '')
                continue;
            $source = (in_array($inviteId, $allowedInviteIds, true) || in_array($email, $allowedEmails, true))
                ? 'approved' : 'detected_unknown';
            $this->snapModel->upsertInvite($farmId, $inviteId, $email, $status, $source);
        }

        $this->farmModel->touchSyncAt($farmId);

        return $this->json([
            'success' => true,
            'message' => 'Sync thành công',
            'members' => count($liveMembers),
            'invites' => count($liveInvites),
        ]);
    }
}

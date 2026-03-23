<?php
namespace Admin;

use AuthService;
use ChatGptAdminViewService;
use ChatGptAllowedInvite;
use ChatGptAuditLog;
use ChatGptFarm;
use ChatGptFarmService;
use ChatGptGuardService;
use ChatGptOrder;
use ChatGptSnapshot;
use ChatGptViolation;
use Controller;

/**
 * ChatGptAdminController
 * Admin panel for GPT Business farm management.
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
    private $viewService;
    private $guardService;
    private $violationModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->farmModel = new ChatGptFarm();
        $this->orderModel = new ChatGptOrder();
        $this->allowModel = new ChatGptAllowedInvite();
        $this->snapModel = new ChatGptSnapshot();
        $this->auditLog = new ChatGptAuditLog();
        $this->farmService = new ChatGptFarmService();
        $this->viewService = new ChatGptAdminViewService();
        $this->guardService = new ChatGptGuardService();
        $this->violationModel = new ChatGptViolation();
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

    public function farms()
    {
        $this->requireAdmin();
        $farms = $this->farmModel->getAll();
        $stats = $this->farmModel->getStats();
        $orderStats = $this->orderModel->getStats();
        $this->view('admin/chatgpt/farms', $this->viewService->buildFarmPageData($farms, $stats, $orderStats));
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
            $_SESSION['cgpt_admin_error'] = 'Email quản trị không hợp lệ.';
            $this->redirect(url('admin/chatgpt/farms/add'));
            return;
        }

        if (!$this->farmService->validateKey(['id' => 0, 'admin_api_key' => $apiKey])) {
            $_SESSION['cgpt_admin_error'] = 'API key không hợp lệ hoặc không kết nối được OpenAI.';
            $this->redirect(url('admin/chatgpt/farms/add'));
            return;
        }

        $farmId = $this->farmModel->create([
            'farm_name' => $farmName,
            'admin_email' => $adminEmail,
            'admin_api_key' => $this->farmService->encryptKey($apiKey),
            'seat_total' => $seatTotal,
        ]);

        $this->auditLog->log([
            'farm_id' => $farmId,
            'farm_name' => $farmName,
            'action' => 'FARM_ADDED',
            'actor_email' => $this->authService->getCurrentUser()['email'] ?? 'admin',
            'result' => 'OK',
            'reason' => 'Quản trị viên thêm farm mới.',
            'meta' => [
                'seat_total' => $seatTotal,
                'admin_email' => $adminEmail,
            ],
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

        $farm['admin_api_key_masked'] = substr((string) $farm['admin_api_key'], 0, 10) . '...';
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
            if (!$this->farmService->validateKey(['id' => $farmId, 'admin_api_key' => $newKey])) {
                $_SESSION['cgpt_admin_error'] = 'API key mới không hợp lệ.';
                $this->redirect(url('admin/chatgpt/farms/edit/' . $farmId));
                return;
            }
            $data['admin_api_key'] = $this->farmService->encryptKey($newKey);
        }

        $this->farmModel->update($farmId, $data);
        $this->auditLog->log([
            'farm_id' => $farmId,
            'farm_name' => $data['farm_name'],
            'action' => 'FARM_UPDATED',
            'actor_email' => $this->authService->getCurrentUser()['email'] ?? 'admin',
            'result' => 'OK',
            'reason' => 'Quản trị viên cập nhật farm.',
            'meta' => [
                'seat_total' => $data['seat_total'],
                'status' => $data['status'],
                'admin_email' => $data['admin_email'],
            ],
        ]);

        $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành công', 'message' => 'Đã cập nhật farm.'];
        $this->redirect(url('admin/chatgpt/farms'));
    }

    public function orders()
    {
        $this->requireAdmin();
        $filters = [
            'status' => $this->get('status', ''),
            'farm_id' => (int) $this->get('farm_id', 0),
            'email' => $this->get('email', ''),
            'date_from' => $this->get('date_from', ''),
            'date_to' => $this->get('date_to', ''),
        ];
        $orders = $this->orderModel->getAll($filters, 200);
        $farms = $this->farmModel->getAll();
        $stats = $this->orderModel->getStats();

        $this->view('admin/chatgpt/orders', array_merge(
            $this->viewService->buildOrdersPageData($orders, $stats),
            [
                'farms' => $farms,
                'filters' => $filters,
            ]
        ));
    }

    public function members()
    {
        $this->requireAdmin();
        $filters = [
            'farm_id' => (int) $this->get('farm_id', 0),
            'source' => $this->get('source', ''),
            'email' => $this->get('email', ''),
            'date_from' => $this->get('date_from', ''),
            'date_to' => $this->get('date_to', ''),
        ];
        $members = $this->snapModel->getAllMembers($filters);
        $farms = $this->farmModel->getAll();

        $this->view('admin/chatgpt/members', array_merge(
            $this->viewService->buildMembersPageData($members),
            [
                'farms' => $farms,
                'filters' => $filters,
            ]
        ));
    }

    public function invites()
    {
        $this->requireAdmin();
        $filters = [
            'farm_id' => (int) $this->get('farm_id', 0),
            'source' => $this->get('source', ''),
            'email' => $this->get('email', ''),
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

        $this->view('admin/chatgpt/logs', array_merge(
            $this->viewService->buildLogsPageData($logs, $actionTypes),
            [
                'farms' => $farms,
                'filters' => $filters,
            ]
        ));
    }

    public function violations()
    {
        $this->requireAdmin();
        $filters = [
            'farm_id' => (int) $this->get('farm_id', 0),
            'type' => $this->get('type', ''),
            'email' => $this->get('email', ''),
        ];
        $violations = $this->violationModel->getAll($filters, 300);
        $stats = $this->violationModel->getStats();
        $types = $this->violationModel->getTypes();
        $farms = $this->farmModel->getAll();

        $this->view('admin/chatgpt/violations', array_merge(
            $this->viewService->buildViolationsPageData($violations, $stats, $types),
            [
                'farms' => $farms,
                'filters' => $filters,
            ]
        ));
    }

    public function farmSyncNow($id)
    {
        $this->requireAdmin();
        $result = $this->guardService->processFarmById((int) $id, $this->authService->getCurrentUser()['email'] ?? 'admin', 'manual_sync');
        if (!$result['success']) {
            return $this->json($result, 404);
        }

        return $this->json([
            'success' => true,
            'message' => 'Đồng bộ farm thành công.',
            'summary' => $result,
        ]);
    }

    public function memberRemove($id)
    {
        $this->requireAdmin();
        $result = $this->guardService->removeMemberBySnapshotId((int) $id, $this->authService->getCurrentUser()['email'] ?? 'admin');
        if ($this->isAjax()) {
            return $this->json($result, $result['success'] ? 200 : 400);
        }

        $_SESSION['notify'] = [
            'type' => $result['success'] ? 'success' : 'error',
            'title' => $result['success'] ? 'Thành công' : 'Lỗi',
            'message' => $result['message'],
        ];
        $this->redirect(url('admin/chatgpt/members'));
    }

    public function inviteRevoke($id)
    {
        $this->requireAdmin();
        $result = $this->guardService->revokeInviteBySnapshotId((int) $id, $this->authService->getCurrentUser()['email'] ?? 'admin');
        if ($this->isAjax()) {
            return $this->json($result, $result['success'] ? 200 : 400);
        }

        $_SESSION['notify'] = [
            'type' => $result['success'] ? 'success' : 'error',
            'title' => $result['success'] ? 'Thành công' : 'Lỗi',
            'message' => $result['message'],
        ];
        $this->redirect(url('admin/chatgpt/invites'));
    }

    public function orderRetryInvite($id)
    {
        $this->requireAdmin();
        $result = $this->guardService->retryOrderInvite((int) $id, $this->authService->getCurrentUser()['email'] ?? 'admin');
        if ($this->isAjax()) {
            return $this->json($result, $result['success'] ? 200 : 400);
        }

        $_SESSION['notify'] = [
            'type' => $result['success'] ? 'success' : 'error',
            'title' => $result['success'] ? 'Thành công' : 'Lỗi',
            'message' => $result['message'],
        ];
        $this->redirect(url('admin/chatgpt/orders'));
    }
}

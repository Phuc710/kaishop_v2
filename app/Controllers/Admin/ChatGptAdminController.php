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
use Product;

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
    private $productModel;

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
        $this->productModel = new Product();
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
        $this->rejectInvalidCsrf(url('admin/gpt-business/farms/add'));

        $farmName = trim((string) $this->post('farm_name', ''));
        $adminEmail = strtolower(trim((string) $this->post('admin_email', '')));
        $apiKey = trim((string) $this->post('admin_api_key', ''));
        $seatTotal = max(1, (int) $this->post('seat_total', 4));

        if ($farmName === '' || $adminEmail === '' || $apiKey === '') {
            $_SESSION['cgpt_admin_error'] = 'Vui lòng điền đầy đủ thông tin.';
            $this->redirect(url('admin/gpt-business/farms/add'));
            return;
        }

        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['cgpt_admin_error'] = 'Email quản trị không hợp lệ.';
            $this->redirect(url('admin/gpt-business/farms/add'));
            return;
        }

        if (!$this->farmService->validateKey(['id' => 0, 'admin_api_key' => $apiKey])) {
            $_SESSION['cgpt_admin_error'] = 'API key không hợp lệ hoặc không kết nối được OpenAI.';
            $this->redirect(url('admin/gpt-business/farms/add'));
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
        $this->redirect(url('admin/gpt-business/farms'));
    }

    public function farmEdit($id)
    {
        $this->requireAdmin();
        $farm = $this->farmModel->getById((int) $id);
        if (!$farm) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Farm không tồn tại.'];
            $this->redirect(url('admin/gpt-business/farms'));
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
        $this->rejectInvalidCsrf(url('admin/gpt-business/farms/edit/' . (int) $id));
        $farmId = (int) $id;
        $farm = $this->farmModel->getById($farmId);
        if (!$farm) {
            $this->redirect(url('admin/gpt-business/farms'));
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
                $this->redirect(url('admin/gpt-business/farms/edit/' . $farmId));
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
        $this->redirect(url('admin/gpt-business/farms'));
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

    public function orderAdd()
    {
        $this->requireAdmin();
        $farms = $this->farmModel->getAll();
        $this->view('admin/chatgpt/orders_add', [
            'farms' => $farms,
            'error' => $_SESSION['cgpt_admin_error'] ?? null,
        ]);
        unset($_SESSION['cgpt_admin_error']);
    }

    public function orderStore()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf(url('admin/gpt-business/orders/add'));

        $email = strtolower(trim((string) $this->post('customer_email', '')));
        $farmId = (int) $this->post('assigned_farm_id', 0);
        $months = max(1, (int) $this->post('months', 1));
        $note = trim((string) $this->post('note', ''));
        $sendInvite = (string) $this->post('send_invite', '0') === '1';
        $actorEmail = $this->authService->getCurrentUser()['email'] ?? 'admin';

        if ($email === '') {
            $_SESSION['cgpt_admin_error'] = 'Vui lòng nhập email khách hàng.';
            $this->redirect(url('admin/gpt-business/orders/add'));
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['cgpt_admin_error'] = 'Email không hợp lệ.';
            $this->redirect(url('admin/gpt-business/orders/add'));
            return;
        }

        // Check duplicate active order
        if ($this->orderModel->hasActiveOrder($email)) {
            $_SESSION['cgpt_admin_error'] = 'Email này đã có đơn hàng đang hoạt động hoặc đang chờ invite.';
            $this->redirect(url('admin/gpt-business/orders/add'));
            return;
        }

        if (class_exists('TimeService')) {
            $expiresAt = \TimeService::instance()
                ->nowDateTime(\TimeService::instance()->getDbTimezone())
                ->modify('+' . $months . ' months')
                ->format('Y-m-d H:i:s');
        } else {
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$months} months"));
        }

        $orderId = $this->orderModel->create([
            'customer_email' => $email,
            'assigned_farm_id' => $farmId > 0 ? $farmId : null,
            'expires_at' => $expiresAt,
            'status' => 'pending',
            'product_code' => 'chatgpt_business_manual',
        ]);

        if ($note !== '') {
            $this->orderModel->updateStatus($orderId, 'pending', ['note' => $note]);
        }

        $this->auditLog->log([
            'farm_id' => $farmId > 0 ? $farmId : null,
            'action' => 'ORDER_MANUAL_CREATED',
            'actor_email' => $actorEmail,
            'result' => 'OK',
            'reason' => 'Quản trị viên tạo đơn hàng thủ công.',
            'meta' => [
                'order_id' => $orderId,
                'customer_email' => $email,
                'months' => $months,
                'expires_at' => $expiresAt,
            ],
        ]);

        // ---------- AUTO-INVITE ----------
        if ($sendInvite) {
            $fakeProduct = [
                'duration_days' => $months * 30,
                'farm_id' => $farmId > 0 ? $farmId : null,
            ];
            $inviteResult = $this->guardService->createAutoInviteForOrder(
                $orderId,
                $fakeProduct,
                $email,
                $actorEmail
            );
            if ($inviteResult['success']) {
                $this->orderModel->updateStatus($orderId, 'inviting', [
                    'assigned_farm_id' => $inviteResult['cg_order_id'] ? null : ($farmId ?: null),
                ]);
                $_SESSION['notify'] = [
                    'type' => 'success',
                    'title' => 'Thành công',
                    'message' => "Đã tạo đơn và gửi invite tới [{$email}]. Farm: " . ($inviteResult['farm_name'] ?? ''),
                ];
            } else {
                $_SESSION['notify'] = [
                    'type' => 'warning',
                    'title' => 'Tạo đơn thành công nhưng gửi invite thất bại',
                    'message' => $inviteResult['message'] ?? 'Không rõ lỗi. Đơn hàng ở trạng thái Pending.',
                ];
            }
            $this->redirect(url('admin/gpt-business/orders'));
            return;
        }
        // ---------------------------------

        $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành công', 'message' => "Đã tạo đơn hàng cho [{$email}] thành công."];
        $this->redirect(url('admin/gpt-business/orders'));
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
        $this->rejectInvalidCsrf('', true);
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
        $this->rejectInvalidCsrf(url('admin/gpt-business/members'), $this->isAjax());
        $result = $this->guardService->removeMemberBySnapshotId((int) $id, $this->authService->getCurrentUser()['email'] ?? 'admin');
        if ($this->isAjax()) {
            return $this->json($result, $result['success'] ? 200 : 400);
        }

        $_SESSION['notify'] = [
            'type' => $result['success'] ? 'success' : 'error',
            'title' => $result['success'] ? 'Thành công' : 'Lỗi',
            'message' => $result['message'],
        ];
        $this->redirect(url('admin/gpt-business/members'));
    }

    public function inviteRevoke($id)
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf(url('admin/gpt-business/invites'), $this->isAjax());
        $result = $this->guardService->revokeInviteBySnapshotId((int) $id, $this->authService->getCurrentUser()['email'] ?? 'admin');
        if ($this->isAjax()) {
            return $this->json($result, $result['success'] ? 200 : 400);
        }

        $_SESSION['notify'] = [
            'type' => $result['success'] ? 'success' : 'error',
            'title' => $result['success'] ? 'Thành công' : 'Lỗi',
            'message' => $result['message'],
        ];
        $this->redirect(url('admin/gpt-business/invites'));
    }

    public function orderRetryInvite($id)
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf(url('admin/gpt-business/orders'), $this->isAjax());
        $result = $this->guardService->retryOrderInvite((int) $id, $this->authService->getCurrentUser()['email'] ?? 'admin');
        if ($this->isAjax()) {
            return $this->json($result, $result['success'] ? 200 : 400);
        }

        $_SESSION['notify'] = [
            'type' => $result['success'] ? 'success' : 'error',
            'title' => $result['success'] ? 'Thành công' : 'Lỗi',
            'message' => $result['message'],
        ];
        $this->redirect(url('admin/gpt-business/orders'));
    }

    public function productEdit()
    {
        $this->requireAdmin();
        // Tìm sản phẩm GPT Business duy nhất
        $product = $this->productModel->getFiltered(['search' => 'ChatGPT Plus - Business']);
        $product = $product[0] ?? null;

        $this->view('admin/chatgpt/product_edit', [
            'product' => $product ? (array) $product : null,
            'categories' => $this->productModel->getCategories(),
            'farms' => $this->farmModel->getAll(),
        ]);
    }

    public function productUpdate()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf(url('admin/gpt-business/product'));
        $id = (int) $this->post('id', 0);

        $name = trim((string) $this->post('name', ''));
        $priceVnd = max(0, (int) $this->post('price_vnd', 0));
        $oldPrice = max(0, (int) $this->post('old_price', 0));
        $description = (string) $this->post('description', '');
        $infoInstructions = trim((string) $this->post('info_instructions', ''));
        $catId = (int) $this->post('category_id', 0);
        $slug = trim((string) $this->post('slug', ''));
        $displayOrder = max(0, (int) $this->post('display_order', 0));
        $visibilityMode = (string) $this->post('visibility_mode', 'both');
        $seoDescription = trim((string) $this->post('seo_description', ''));
        $image = trim((string) $this->post('image', ''));
        $minPurchaseQty = max(1, (int) $this->post('min_purchase_qty', 1));
        $maxPurchaseQty = max(0, (int) $this->post('max_purchase_qty', 0));
        $manualStock = 0;

        // Dedicated GPT Business product is always auto-invite.
        $deliveryMode = 'business_invite_auto';
        $productType = 'business_invite_auto';
        $requiresInfo = 1;

        $durationDays = max(1, (int) $this->post('duration_days', 30));
        $autoInvite = 1;
        $farmId = (int) $this->post('farm_id', 0);

        $galleryInput = $this->post('gallery', []);
        $gallery = [];
        if (is_array($galleryInput)) {
            foreach ($galleryInput as $item) {
                $item = trim((string) $item);
                if ($item !== '')
                    $gallery[] = $item;
            }
            $gallery = array_values(array_unique($gallery));
        }

        if ($name === '') {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Vui lòng nhập tên sản phẩm'];
            $this->redirect(url('admin/gpt-business/product'));
            return;
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'price_vnd' => $priceVnd,
            'old_price' => $oldPrice,
            'description' => $description,
            'requires_info' => $requiresInfo,
            'info_instructions' => $infoInstructions,
            'category_id' => $catId,
            'display_order' => $displayOrder,
            'visibility_mode' => $visibilityMode,
            'seo_description' => $seoDescription,
            'image' => $image,
            'gallery' => json_encode($gallery),
            'min_purchase_qty' => $minPurchaseQty,
            'max_purchase_qty' => $maxPurchaseQty,
            'manual_stock' => $manualStock,
            'status' => 'ON',
            'product_type' => $productType,
            'duration_days' => $durationDays,
            'auto_invite' => $autoInvite,
            'farm_id' => $farmId > 0 ? $farmId : null,
        ];

        if ($id > 0) {
            $this->productModel->update($id, $data);
            $action = 'PRODUCT_UPDATED';
            $msg = 'Cập nhật cấu hình sản phẩm thành công';
        } else {
            if ($slug === '')
                $data['slug'] = 'chatgpt-business-' . (class_exists('TimeService') ? \TimeService::instance()->nowTs() : time());
            $id = $this->productModel->create($data);
            $action = 'PRODUCT_CREATED';
            $msg = 'Tạo sản phẩm GPT Business thành công';
        }

        $this->auditLog->log([
            'action' => $action,
            'actor_email' => $this->authService->getCurrentUser()['email'] ?? 'admin',
            'result' => 'OK',
            'reason' => 'Admin managed GPT product detail via dedicated module.',
            'meta' => ['product_id' => $id, 'name' => $data['name']],
        ]);

        $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành công', 'message' => $msg];
        $this->redirect(url('admin/gpt-business/product'));
    }
}

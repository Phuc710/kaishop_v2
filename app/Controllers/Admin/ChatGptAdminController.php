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
        $openaiOrgId = trim((string) $this->post('openai_org_id', ''));

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

        if (!$this->farmService->validateKey(['id' => 0, 'admin_api_key' => $apiKey, 'openai_org_id' => $openaiOrgId])) {
            $_SESSION['cgpt_admin_error'] = 'API key không hợp lệ hoặc không kết nối được OpenAI.';
            $this->redirect(url('admin/gpt-business/farms/add'));
            return;
        }

        $farmId = $this->farmModel->create([
            'farm_name' => $farmName,
            'admin_email' => $adminEmail,
            'admin_api_key' => $this->farmService->encryptKey($apiKey),
            'openai_org_id' => $openaiOrgId,
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
            'openai_org_id' => trim((string) $this->post('openai_org_id', $farm['openai_org_id'])),
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

        $rawEmails = (string) $this->post('customer_email', '');
        $farmId = (int) $this->post('assigned_farm_id', 0);
        $months = max(1, (int) $this->post('months', 1));
        $note = trim((string) $this->post('note', ''));
        $sendInvite = (string) $this->post('send_invite', '0') === '1';
        $actorEmail = $this->authService->getCurrentUser()['email'] ?? 'admin';

        // Split emails by newline, comma, or space
        $emailLines = preg_split('/[,\n\r\s]+/', $rawEmails, -1, PREG_SPLIT_NO_EMPTY);
        $uniqueEmails = array_unique(array_map('strtolower', array_map('trim', $emailLines)));

        if (empty($uniqueEmails)) {
            $_SESSION['cgpt_admin_error'] = 'Vui lòng nhập ít nhất một email khách hàng.';
            $this->redirect(url('admin/gpt-business/orders/add'));
            return;
        }

        $successCount = 0;
        $failCount = 0;
        $skipCount = 0;
        $errors = [];

        foreach ($uniqueEmails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failCount++;
                $errors[] = "Email [{$email}] không hợp lệ.";
                continue;
            }

            // Check duplicate active/pending order
            if ($this->orderModel->hasActiveOrder($email)) {
                $skipCount++;
                continue;
            }

            if (class_exists('TimeService')) {
                $expiresAt = \TimeService::instance()
                    ->nowDateTime(\TimeService::instance()->getDbTimezone())
                    ->modify('+' . $months . ' months')
                    ->format('Y-m-d H:i:s');
            } else {
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$months} months"));
            }

            if ($sendInvite) {
                // FIXED: Use GuardService to create the order AND send invite in ONE flow
                // This avoids the double-creation bug.
                $fakeProduct = [
                    'duration_days' => $months * 30,
                    'farm_id' => $farmId > 0 ? $farmId : null,
                ];
                $inviteResult = $this->guardService->createAutoInviteForOrder(
                    0, // source_order_id = 0 for manual admin orders
                    $fakeProduct,
                    $email,
                    $actorEmail
                );

                if ($inviteResult['success']) {
                    $successCount++;
                    $orderId = (int) ($inviteResult['cg_order_id'] ?? 0);
                    if ($orderId > 0 && $note !== '') {
                        $this->orderModel->updateStatus($orderId, 'inviting', ['note' => $note]);
                    }
                } else {
                    $failCount++;
                    $errors[] = "Email [{$email}]: " . ($inviteResult['message'] ?? 'Lỗi gửi invite OpenAI.');
                }
            } else {
                // Create a pending order manually
                $orderId = $this->orderModel->create([
                    'customer_email' => $email,
                    'assigned_farm_id' => $farmId > 0 ? $farmId : null,
                    'expires_at' => $expiresAt,
                    'status' => 'pending',
                    'product_code' => 'chatgpt_business_manual',
                ]);

                if ($orderId > 0) {
                    $successCount++;
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
                } else {
                    $failCount++;
                    $errors[] = "Email [{$email}]: Không thể tạo bản ghi database.";
                }
            }
        }

        // Build notification summary
        $msg = "Hoàn tất xử lý " . count($uniqueEmails) . " email.";
        $msg .= " Thành công: {$successCount}.";
        if ($skipCount > 0)
            $msg .= " Bỏ qua do đã có đơn: {$skipCount}.";
        if ($failCount > 0)
            $msg .= " Thất bại: {$failCount}.";

        if ($failCount > 0) {
            $_SESSION['cgpt_admin_error'] = $msg . " <br>Chi tiết lỗi: " . implode(', ', $errors);
        } else {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Hoàn tất', 'message' => $msg];
        }

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

    public function debug()
    {
        $this->requireAdmin();
        $farms = $this->farmModel->getAll();
        $selectedFarmId = (int) $this->post('farm_id', $this->get('farm_id', $farms[0]['id'] ?? 0));
        $selectedFarm = $selectedFarmId > 0 ? $this->farmModel->getById($selectedFarmId) : null;
        $actionResult = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->rejectInvalidCsrf(url('admin/gpt-business/debug'));
            $action = trim((string) $this->post('action', ''));

            if (!$selectedFarm) {
                $actionResult = $this->buildDebugActionResult(
                    false,
                    $action !== '' ? $action : 'unknown',
                    ['id' => $selectedFarmId, 'farm_name' => 'Unknown farm', 'status' => 'missing'],
                    [],
                    null,
                    'Farm không tồn tại hoặc chưa được chọn.'
                );
            } else {
                $actionResult = $this->handleDebugAction($selectedFarm, $action);
                $selectedFarm = $this->farmModel->getById((int) $selectedFarm['id']) ?: $selectedFarm;
            }
        }

        $allowedInvites = $selectedFarm ? $this->allowModel->getByFarm((int) $selectedFarm['id'], 200) : [];
        $localMembers = $selectedFarm ? array_reverse($this->snapModel->getMembersForFarm((int) $selectedFarm['id'])) : [];
        $localInvites = $selectedFarm ? $this->snapModel->getInvitesForFarm((int) $selectedFarm['id']) : [];

        $this->view('admin/chatgpt/debug', [
            'farms' => $farms,
            'selectedFarm' => $selectedFarm,
            'selectedFarmId' => $selectedFarm ? (int) $selectedFarm['id'] : $selectedFarmId,
            'actionResult' => $actionResult,
            'allowedInvites' => $allowedInvites,
            'localMembers' => $localMembers,
            'localInvites' => $localInvites,
            'debugRoles' => ['reader', 'member', 'admin', 'owner'],
            'debugMethods' => ['GET', 'POST', 'DELETE'],
        ]);
    }

    private function handleDebugAction(array $farm, string $action): array
    {
        $farmId = (int) ($farm['id'] ?? 0);
        $actorEmail = $this->authService->getCurrentUser()['email'] ?? 'admin';

        switch ($action) {
            case 'validate_key':
                $inviteProbe = $this->farmService->request($farm, 'GET', '/organization/invites?limit=1');
                $userProbe = $this->farmService->request($farm, 'GET', '/organization/users?limit=1');
                $success = $this->isDebugHttpSuccess($inviteProbe) && $this->isDebugHttpSuccess($userProbe);
                return $this->buildDebugActionResult(
                    $success,
                    $action,
                    $farm,
                    [
                        'probes' => [
                            ['method' => 'GET', 'endpoint' => '/organization/invites?limit=1'],
                            ['method' => 'GET', 'endpoint' => '/organization/users?limit=1'],
                        ],
                    ],
                    [
                        'invite_probe' => $inviteProbe,
                        'user_probe' => $userProbe,
                    ],
                    $success ? null : 'API key không truy cập được đầy đủ các endpoint quản trị.'
                );

            case 'sync_guard':
                $summary = $this->guardService->processFarm($farm, $actorEmail, 'debug_panel');
                return $this->buildDebugActionResult(
                    (bool) ($summary['success'] ?? false),
                    $action,
                    $farm,
                    ['source' => 'debug_panel'],
                    [
                        'summary' => $summary,
                        'farm_after_sync' => $this->farmModel->getById($farmId),
                    ],
                    $summary['success'] ?? false ? null : ($summary['message'] ?? 'Không thể đồng bộ guard.')
                );

            case 'sync_seats':
                $membersResponse = $this->farmService->request($farm, 'GET', '/organization/users?limit=100');
                $invitesResponse = $this->farmService->request($farm, 'GET', '/organization/invites?limit=100');
                $success = $this->isDebugHttpSuccess($membersResponse) && $this->isDebugHttpSuccess($invitesResponse);
                $payload = [
                    'members_response' => $membersResponse,
                    'invites_response' => $invitesResponse,
                ];
                $error = null;

                if ($success) {
                    $seatUsed = $this->farmModel->syncSeatUsageFromLiveData(
                        $farmId,
                        (int) ($farm['seat_total'] ?? 0),
                        $membersResponse['data'] ?? [],
                        $invitesResponse['data'] ?? [],
                        (string) ($farm['admin_email'] ?? '')
                    );
                    $payload['seat_sync'] = [
                        'seat_used' => $seatUsed,
                        'farm_after_sync' => $this->farmModel->getById($farmId),
                    ];
                } else {
                    $error = 'Không thể lấy đủ dữ liệu live để đồng bộ seat_used.';
                }

                return $this->buildDebugActionResult(
                    $success,
                    $action,
                    $farm,
                    [
                        'requests' => [
                            ['method' => 'GET', 'endpoint' => '/organization/users?limit=100'],
                            ['method' => 'GET', 'endpoint' => '/organization/invites?limit=100'],
                        ],
                    ],
                    $payload,
                    $error
                );

            case 'get_org':
                $orgResponse = $this->farmService->request($farm, 'GET', '/organization');
                $fallbackResponse = null;
                $response = $orgResponse;
                $requestMeta = ['method' => 'GET', 'endpoint' => '/organization'];

                if (!$this->isDebugHttpSuccess($orgResponse)) {
                    $fallbackResponse = $this->farmService->request($farm, 'GET', '/organizations');
                    if ($this->isDebugHttpSuccess($fallbackResponse)) {
                        $response = $fallbackResponse;
                        $requestMeta = [
                            'method' => 'GET',
                            'endpoint' => '/organizations',
                            'fallback_from' => '/organization',
                        ];
                    }
                }

                return $this->buildDebugActionResult(
                    $this->isDebugHttpSuccess($response),
                    $action,
                    $farm,
                    $requestMeta,
                    [
                        'primary_response' => $orgResponse,
                        'fallback_response' => $fallbackResponse,
                    ],
                    $this->isDebugHttpSuccess($response) ? null : 'Không lấy được thông tin organization từ OpenAI.'
                );

            case 'list_invites':
                $inviteLimit = max(1, min(200, (int) $this->post('limit', 50)));
                $invites = $this->farmService->request($farm, 'GET', '/organization/invites?limit=' . $inviteLimit);
                return $this->buildDebugActionResult(
                    $this->isDebugHttpSuccess($invites),
                    $action,
                    $farm,
                    ['method' => 'GET', 'endpoint' => '/organization/invites?limit=' . $inviteLimit],
                    $invites,
                    $this->isDebugHttpSuccess($invites) ? null : 'Không lấy được danh sách invite.'
                );

            case 'list_users':
                $userLimit = max(1, min(200, (int) $this->post('limit', 50)));
                $users = $this->farmService->request($farm, 'GET', '/organization/users?limit=' . $userLimit);
                return $this->buildDebugActionResult(
                    $this->isDebugHttpSuccess($users),
                    $action,
                    $farm,
                    ['method' => 'GET', 'endpoint' => '/organization/users?limit=' . $userLimit],
                    $users,
                    $this->isDebugHttpSuccess($users) ? null : 'Không lấy được danh sách member.'
                );

            case 'create_invite':
                $email = strtolower(trim((string) $this->post('email', '')));
                $role = trim((string) $this->post('role', 'reader'));
                $allowedRoles = ['reader', 'member', 'admin', 'owner'];
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $this->buildDebugActionResult(false, $action, $farm, [], null, 'Email invite không hợp lệ.');
                }
                if (!in_array($role, $allowedRoles, true)) {
                    $role = 'reader';
                }

                $createResponse = $this->farmService->request($farm, 'POST', '/organization/invites', [
                    'email' => $email,
                    'role' => $role,
                ]);
                $notes = [];

                if ($this->isDebugHttpSuccess($createResponse)) {
                    $inviteId = trim((string) ($createResponse['id'] ?? ''));
                    $allowedRows = $this->allowModel->getAllowedEmailsForFarm($farmId);
                    $linkedToAllowed = false;
                    foreach ($allowedRows as $row) {
                        $targetEmail = strtolower(trim((string) ($row['target_email'] ?? '')));
                        if ($targetEmail !== $email) {
                            continue;
                        }

                        $allowedInviteId = trim((string) ($row['invite_id'] ?? ''));
                        if ($inviteId !== '' && $allowedInviteId === '' && !empty($row['id'])) {
                            $this->allowModel->setInviteId((int) $row['id'], $inviteId);
                            $linkedToAllowed = true;
                            break;
                        }

                        if ($allowedInviteId !== '') {
                            $linkedToAllowed = true;
                            break;
                        }
                    }

                    if ($inviteId !== '') {
                        $this->snapModel->upsertInvite(
                            $farmId,
                            $inviteId,
                            $email,
                            (string) ($createResponse['status'] ?? 'pending'),
                            $linkedToAllowed ? 'approved' : 'detected_unknown',
                            (string) ($createResponse['role'] ?? $role)
                        );
                    }

                    if ($linkedToAllowed) {
                        $notes[] = 'Invite đã được nối với bản ghi allowed_invites hiện có của farm.';
                    } else {
                        $notes[] = 'Invite này chưa nằm trong allowed_invites. Nếu chạy guard sync, hệ thống có thể tự thu hồi.';
                    }
                }

                return $this->buildDebugActionResult(
                    $this->isDebugHttpSuccess($createResponse),
                    $action,
                    $farm,
                    [
                        'method' => 'POST',
                        'endpoint' => '/organization/invites',
                        'body' => ['email' => $email, 'role' => $role],
                    ],
                    $createResponse,
                    $this->isDebugHttpSuccess($createResponse) ? null : 'OpenAI từ chối tạo invite.',
                    $notes
                );

            case 'revoke_invite':
                $inviteId = trim((string) $this->post('invite_id', ''));
                if ($inviteId === '') {
                    return $this->buildDebugActionResult(false, $action, $farm, [], null, 'Thiếu invite_id.');
                }

                $revokeResponse = $this->farmService->request($farm, 'DELETE', '/organization/invites/' . rawurlencode($inviteId));
                if ($this->isDebugHttpSuccess($revokeResponse)) {
                    $this->allowModel->markRevokedByInviteId($inviteId);
                    $this->snapModel->markInviteGone($farmId, $inviteId);
                }

                return $this->buildDebugActionResult(
                    $this->isDebugHttpSuccess($revokeResponse),
                    $action,
                    $farm,
                    ['method' => 'DELETE', 'endpoint' => '/organization/invites/' . $inviteId],
                    $revokeResponse,
                    $this->isDebugHttpSuccess($revokeResponse) ? null : 'Không thể thu hồi invite.'
                );

            case 'remove_member':
                $userId = trim((string) $this->post('user_id', ''));
                $memberEmail = strtolower(trim((string) $this->post('member_email', '')));
                if ($userId === '') {
                    return $this->buildDebugActionResult(false, $action, $farm, [], null, 'Thiếu user_id.');
                }

                $removeResponse = $this->farmService->request($farm, 'DELETE', '/organization/users/' . rawurlencode($userId));
                if ($this->isDebugHttpSuccess($removeResponse) && $memberEmail !== '') {
                    $this->snapModel->markMemberGone($farmId, $memberEmail);
                }

                return $this->buildDebugActionResult(
                    $this->isDebugHttpSuccess($removeResponse),
                    $action,
                    $farm,
                    ['method' => 'DELETE', 'endpoint' => '/organization/users/' . $userId],
                    $removeResponse,
                    $this->isDebugHttpSuccess($removeResponse) ? null : 'Không thể xóa member khỏi organization.'
                );

            case 'custom_request':
                $method = strtoupper(trim((string) $this->post('request_method', 'GET')));
                $endpoint = '/' . ltrim(trim((string) $this->post('endpoint', '')), '/');
                $requestBodyRaw = trim((string) $this->post('request_body', ''));
                $allowedMethods = ['GET', 'POST', 'DELETE'];
                if (!in_array($method, $allowedMethods, true)) {
                    return $this->buildDebugActionResult(false, $action, $farm, [], null, 'Method không được hỗ trợ.');
                }
                if ($endpoint === '/' || trim($endpoint, '/') === '') {
                    return $this->buildDebugActionResult(false, $action, $farm, [], null, 'Endpoint không được để trống.');
                }

                $requestBody = [];
                if ($requestBodyRaw !== '') {
                    $decodedBody = json_decode($requestBodyRaw, true);
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedBody)) {
                        return $this->buildDebugActionResult(false, $action, $farm, [], null, 'JSON body không hợp lệ. Chỉ chấp nhận object hoặc array hợp lệ.');
                    }
                    $requestBody = $decodedBody;
                }

                $customResponse = $this->farmService->request($farm, $method, $endpoint, $requestBody);
                return $this->buildDebugActionResult(
                    $this->isDebugHttpSuccess($customResponse),
                    $action,
                    $farm,
                    [
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'body' => $requestBody,
                    ],
                    $customResponse,
                    $this->isDebugHttpSuccess($customResponse) ? null : 'Request tùy chỉnh trả về lỗi.'
                );
        }

        return $this->buildDebugActionResult(false, $action !== '' ? $action : 'unknown', $farm, [], null, 'Action debug không được hỗ trợ.');
    }

    private function buildDebugActionResult($success, $action, array $farm, array $request = [], $response = null, $error = null, array $notes = []): array
    {
        return [
            'success' => (bool) $success,
            'action' => (string) $action,
            'farm' => [
                'id' => (int) ($farm['id'] ?? 0),
                'name' => (string) ($farm['farm_name'] ?? ('Farm #' . ((int) ($farm['id'] ?? 0)))),
                'status' => (string) ($farm['status'] ?? ''),
                'admin_email' => (string) ($farm['admin_email'] ?? ''),
            ],
            'request' => $request,
            'response' => $response,
            'http_code' => is_array($response) ? ($response['_http_code'] ?? null) : null,
            'error' => $error ?: (is_array($response) ? ($response['_error'] ?? null) : null),
            'notes' => array_values(array_filter($notes, static function ($note) {
                return is_string($note) && trim($note) !== '';
            })),
            'timestamp' => class_exists('\TimeService')
                ? \TimeService::instance()->formatDisplay(time(), 'd/m/Y H:i:s')
                : date('d/m/Y H:i:s'),
        ];
    }

    private function isDebugHttpSuccess(array $response): bool
    {
        $code = (int) ($response['_http_code'] ?? 0);
        return $code >= 200 && $code < 300;
    }
}

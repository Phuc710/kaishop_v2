<?php

/**
 * Admin Finance Controller
 * Quản lý mã giảm giá — CRUD đầy đủ + nhật ký sử dụng
 */
class FinanceController extends Controller
{
    private $authService;
    private $giftCodeModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->giftCodeModel = new GiftCode();
    }

    private function requireAdmin()
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        if (!isset($user['level']) || $user['level'] != 9) {
            http_response_code(403);
            die('Truy cập bị từ chối');
        }
    }

    /**
     * Lấy danh sách sản phẩm cho select box
     */
    private function getProductList()
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, name FROM products WHERE status = ? ORDER BY name ASC");
            $stmt->execute(['ON']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    // ========== GIFTCODES ==========

    /**
     * Danh sách mã giảm giá
     */
    public function giftcodes()
    {
        $this->requireAdmin();
        global $chungapi;

        $giftcodes = $this->giftCodeModel->getAll();

        // Gắn tên sản phẩm cho mỗi mã
        foreach ($giftcodes as &$gc) {
            if (!empty($gc['product_ids'])) {
                $gc['product_names'] = $this->giftCodeModel->getProductNames($gc['product_ids']);
            } else {
                $gc['product_names'] = [];
            }
            // Tính lượt còn lại
            $gc['remaining'] = max(0, (int) $gc['soluong'] - (int) $gc['dadung']);
        }

        $this->view('admin/finance/giftcodes', [
            'giftcodes' => $giftcodes,
            'chungapi' => $chungapi,
        ]);
    }

    /**
     * Form thêm mã giảm giá
     */
    public function addGiftcode()
    {
        $this->requireAdmin();
        global $chungapi;

        $this->view('admin/finance/add_giftcode', [
            'chungapi' => $chungapi,
            'products' => $this->getProductList(),
        ]);
    }

    /**
     * Xử lý thêm mã giảm giá
     */
    public function storeGiftcode()
    {
        $this->requireAdmin();

        $data = [
            'giftcode' => strtoupper(trim($this->post('giftcode'))),
            'giamgia' => (int) $this->post('giamgia'),
            'soluong' => (int) $this->post('soluong'),
            'min_order' => (int) $this->post('min_order'),
            'max_order' => (int) $this->post('max_order'),
            'type' => 'all',
            'product_ids' => null,
        ];

        // Xử lý sản phẩm áp dụng
        $selectedProducts = $this->post('product_ids');
        if (!empty($selectedProducts) && is_array($selectedProducts)) {
            $data['type'] = 'product';
            $data['product_ids'] = implode(',', array_map('intval', $selectedProducts));
        }

        // Validate
        if (empty($data['giftcode'])) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Mã giảm giá không được trống'];
            $this->redirect(url('admin/finance/giftcodes/add'));
            return;
        }

        // Kiểm tra trùng
        $existing = $this->giftCodeModel->findByCode($data['giftcode']);
        if ($existing) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Mã "' . $data['giftcode'] . '" đã tồn tại'];
            $this->redirect(url('admin/finance/giftcodes/add'));
            return;
        }

        $this->giftCodeModel->store($data);
        $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành công', 'message' => 'Đã tạo mã giảm giá'];
        $this->redirect(url('admin/finance/giftcodes'));
    }

    /**
     * Form sửa mã giảm giá
     */
    public function editGiftcode($id)
    {
        $this->requireAdmin();
        global $chungapi;

        $giftcode = $this->giftCodeModel->findById($id);
        if (!$giftcode) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Mã không tồn tại'];
            $this->redirect(url('admin/finance/giftcodes'));
            return;
        }

        // Parse product_ids thành mảng
        $giftcode['product_ids_array'] = !empty($giftcode['product_ids'])
            ? array_map('intval', explode(',', $giftcode['product_ids']))
            : [];

        $this->view('admin/finance/edit_giftcode', [
            'chungapi' => $chungapi,
            'giftcode' => $giftcode,
            'products' => $this->getProductList(),
        ]);
    }

    /**
     * Xử lý cập nhật mã giảm giá
     */
    public function updateGiftcode($id)
    {
        $this->requireAdmin();

        $data = [
            'giftcode' => strtoupper(trim($this->post('giftcode'))),
            'giamgia' => (int) $this->post('giamgia'),
            'soluong' => (int) $this->post('soluong'),
            'min_order' => (int) $this->post('min_order'),
            'max_order' => (int) $this->post('max_order'),
            'status' => $this->post('status') ?: 'ON',
            'type' => 'all',
            'product_ids' => null,
        ];

        $selectedProducts = $this->post('product_ids');
        if (!empty($selectedProducts) && is_array($selectedProducts)) {
            $data['type'] = 'product';
            $data['product_ids'] = implode(',', array_map('intval', $selectedProducts));
        }

        $this->giftCodeModel->updateById($id, $data);
        $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành công', 'message' => 'Cập nhật thành công'];
        $this->redirect(url('admin/finance/giftcodes'));
    }

    /**
     * Xóa mã giảm giá (AJAX)
     */
    public function deleteGiftcode($id)
    {
        $this->requireAdmin();

        $this->giftCodeModel->deleteById($id);
        $this->json(['success' => true, 'message' => 'Đã xóa mã giảm giá']);
    }

    /**
     * Nhật ký sử dụng mã giảm giá
     */
    public function giftcodeLog($id)
    {
        $this->requireAdmin();
        global $chungapi;

        $giftcode = $this->giftCodeModel->findById($id);
        if (!$giftcode) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Mã không tồn tại'];
            $this->redirect(url('admin/finance/giftcodes'));
            return;
        }

        $logs = $this->giftCodeModel->getUsageLog($giftcode['giftcode']);

        $this->view('admin/finance/giftcode_log', [
            'chungapi' => $chungapi,
            'giftcode' => $giftcode,
            'logs' => $logs,
        ]);
    }
}

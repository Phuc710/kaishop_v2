<?php

/**
 * Admin Product Controller
 * Handles product CRUD, stock management, and toggle operations
 */
class AdminProductController extends Controller
{
    private AuthService $authService;
    private Product $productModel;
    private ProductStock $stockModel;
    private ProductInventoryService $inventoryService;
    private Order $orderModel;
    private $timeService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->productModel = new Product();
        $this->stockModel = new ProductStock();
        $this->orderModel = new Order();
        $this->inventoryService = new ProductInventoryService($this->stockModel);
        $this->timeService = class_exists('TimeService') ? TimeService::instance() : null;
    }

    private function requireAdmin(): void
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        if (!isset($user['level']) || (int) $user['level'] !== 9) {
            http_response_code(403);
            die('Truy cập bị từ chối - Chỉ dành cho quản trị viên');
        }
    }

    // ==================== PRODUCT LIST ====================
    public function index()
    {
        $this->requireAdmin();
        global $chungapi;

        $filters = [
            'search' => $this->get('search', ''),
            'visibility_mode' => $this->get('visibility_mode', ''),
            'category_id' => $this->get('category_id', ''),
            'type' => $this->get('type', ''),
        ];

        $products = $this->productModel->getFiltered($filters);
        foreach ($products as &$productRow) {
            $productRow = FormatHelper::attachTimeMeta($productRow, 'created_at');
        }
        unset($productRow);
        $categories = $this->productModel->getCategories();
        $stats = $this->productModel->getStats();

        $stockStats = $this->inventoryService->getStatsForProducts($products);

        $this->view('admin/products/index', [
            'products' => $products,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $filters,
            'chungapi' => $chungapi,
            'stockStats' => $stockStats,
        ]);
    }

    // ==================== TOGGLE / DELETE ====================
    public function toggleStatus()
    {
        $this->requireAdmin();
        $id = (int) $this->post('id', 0);
        $mode = trim((string) $this->post('mode', ''));
        if ($id <= 0)
            return $this->json(['success' => false, 'message' => 'Thiếu ID'], 400);
        if ($mode !== '') {
            return $this->json($this->productModel->setVisibilityMode($id, $mode));
        }
        return $this->json($this->productModel->toggleStatus($id));
    }

    public function delete()
    {
        $this->requireAdmin();
        $id = (int) $this->post('id', 0);
        if ($id <= 0)
            return $this->json(['success' => false, 'message' => 'Thiếu ID'], 400);
        if (!$this->productModel->find($id))
            return $this->json(['success' => false, 'message' => 'Không tìm thấy'], 404);
        if ($this->productModel->delete($id))
            return $this->json(['success' => true, 'message' => 'Đã xóa sản phẩm']);
        return $this->json(['success' => false, 'message' => 'Lỗi máy chủ'], 500);
    }

    // ==================== ADD ====================
    public function add()
    {
        $this->requireAdmin();
        global $chungapi;
        $this->view('admin/products/add', [
            'chungapi' => $chungapi,
            'categories' => $this->productModel->getCategories(),
        ]);
    }

    public function store()
    {
        $this->requireAdmin();
        [$data, $errors] = $this->buildAdminProductSavePayload();
        if (!empty($errors)) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => $errors[0]];
            $this->redirect(url('admin/products/add'));
        }

        $productId = (int) $this->productModel->create($data);

        // Only stock-managed account products import initial stock from textarea
        if (Product::isStockManagedProduct($data)) {
            $rawStock = trim((string) $this->post('initial_stock', ''));
            if ($rawStock !== '' && $productId > 0) {
                $this->stockModel->importBulk($productId, $rawStock);
            }
        }

        $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành công', 'message' => 'Thêm sản phẩm thành công'];
        $this->redirect(url('admin/products'));
    }

    // ==================== EDIT ====================
    public function edit($id)
    {
        $this->requireAdmin();
        global $chungapi;

        $product = $this->productModel->find((int) $id);
        if (!$product) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Sản phẩm không tồn tại'];
            $this->redirect(url('admin/products'));
        }

        $stockStats = $this->inventoryService->getStats($product);
        $accountStockCount = $this->stockModel->countAvailable((int) ($product['id'] ?? 0));

        $this->view('admin/products/edit', [
            'chungapi' => $chungapi,
            'product' => $product,
            'categories' => $this->productModel->getCategories(),
            'stockStats' => $stockStats,
            'accountStockCount' => $accountStockCount,
        ]);
    }

    public function update($id)
    {
        $this->requireAdmin();
        $id = (int) $id;
        if (!$this->productModel->find($id)) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Sản phẩm không tồn tại'];
            $this->redirect(url('admin/products'));
        }

        [$data, $errors] = $this->buildAdminProductSavePayload($id);
        if (!empty($errors)) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => $errors[0]];
            $this->redirect(url('admin/products/edit/' . $id));
        }

        $this->productModel->update($id, $data);

        $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành công', 'message' => 'Cập nhật sản phẩm thành công'];
        $this->redirect(url('admin/products'));
    }

    // ==================== STOCK (KHO) ====================
    public function stock($id)
    {
        $this->requireAdmin();
        global $chungapi;

        $id = (int) $id;
        $product = $this->productModel->find($id);
        if (!$product) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Sản phẩm không tồn tại.'];
            $this->redirect(url('admin/products'));
        }

        $filters = [
            'status_filter' => $this->get('status_filter', ''),
            'search' => $this->get('search', ''),
            'date_filter' => $this->get('date_filter', ''),
            'start_date' => $this->get('start_date', ''),
            'end_date' => $this->get('end_date', ''),
            'limit' => $this->get('limit', 20),
        ];

        try {
            $handler = $this->inventoryService->getHandler($product);
            $items = $handler->getItems($filters);
            $stats = $handler->getStats();
            $partialView = $handler->getPartialView();

            if ($this->isAjax()) {
                return $this->json([
                    'success' => true,
                    'items' => $items,
                    'stats' => $stats,
                    'isManualQueue' => !empty($stats['is_manual_queue']),
                    'isSourceHistory' => !empty($stats['is_source_link']),
                ]);
            }

            $this->view('admin/products/stock', [
                'chungapi' => $chungapi,
                'product' => $product,
                'items' => $items,
                'stats' => $stats,
                'statusFilter' => $filters['status_filter'],
                'search' => $filters['search'],
                'dateFilter' => $filters['date_filter'],
                'limit' => $filters['limit'],
                'isManualQueue' => !empty($stats['is_manual_queue']),
                'isSourceHistory' => !empty($stats['is_source_link']),
                'partialView' => $partialView
            ]);
        } catch (Exception $e) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => $e->getMessage()];
            $this->redirect(url('admin/products'));
        }
    }

    public function stockImport($id)
    {
        $this->requireAdmin();
        $id = (int) $id;
        $product = $this->productModel->find($id);
        if (!$product) {
            return $this->json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        try {
            $handler = $this->inventoryService->getHandler($product);
            $result = $handler->handleImport($this->post('content', ''));

            if ($result['success'] && isset($result['added'])) {
                $result['message'] = "Đã nhập {$result['added']} item. Bỏ qua " . ($result['skipped'] ?? 0) . " trùng lặp.";
            }

            return $this->json($result);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function stockAction($productId)
    {
        $this->requireAdmin();
        $productId = (int) $productId;
        $product = $this->productModel->find($productId);
        if (!$product) {
            return $this->json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        $action = (string) $this->post('action', '');
        $id = (int) $this->post('id', 0); // Item ID or Order ID

        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Thiếu ID mục tiêu'], 400);
        }

        $handler = $this->inventoryService->getHandler($product);

        $params = $_POST;
        // Add admin username for order-related actions
        $user = $this->authService->getCurrentUser();
        $params['admin_username'] = trim((string) ($user['username'] ?? 'admin')) ?: 'admin';

        $result = $handler->handleAction($action, $id, $params);
        return $this->json($result, ($result['success'] ?? false) ? 200 : 400);
    }

    public function stockDelete()
    {
        // For backward compatibility or internal redirects
        $id = (int) $this->post('id');
        // We need the product ID to get the handler, but old stockDelete didn't pass it.
        // For Account, we can still use the old model if we want, or just update the JS.
        // Let's just update the JS and keep this as a simple wrapper for Account type if possible.
        return $this->json(['success' => false, 'message' => 'Vui lòng sử dụng stockAction'], 400);
    }

    // ==================== BUILD SAVE PAYLOAD ====================
    private function buildAdminProductSavePayload(?int $excludeId = null): array
    {
        $errors = [];

        $name = trim((string) $this->post('name', ''));
        $productType = $this->post('product_type', 'account') === 'link' ? 'link' : 'account';
        $priceVnd = max(0, (int) $this->post('price_vnd', 0));
        $oldPrice = max(0, (int) $this->post('old_price', 0));
        $sourceLink = trim((string) $this->post('source_link', ''));
        $manualStock = max(0, (int) $this->post('manual_stock', 0));
        $requiresInfo = in_array((string) $this->post('requires_info', '0'), ['1', 'true', 'on'], true) ? 1 : 0;
        $infoInstructions = trim((string) $this->post('info_instructions', ''));
        $minPurchaseQty = max(1, (int) $this->post('min_purchase_qty', 1));
        $maxPurchaseQty = max(0, (int) $this->post('max_purchase_qty', 0));
        $description = (string) $this->post('description', '');
        $image = trim((string) $this->post('image', ''));
        $badgeText = trim((string) $this->post('badge_text', ''));
        $seoDescription = trim((string) $this->post('seo_description', ''));
        $catId = (int) $this->post('category_id', 0);
        $displayOrder = max(0, (int) $this->post('display_order', 0));
        $visibilityMode = Product::normalizeVisibilityMode((string) $this->post('visibility_mode', Product::VISIBILITY_BOTH));
        $slug = trim((string) $this->post('slug', ''));

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

        if ($name === '')
            $errors[] = 'Vui lòng nhập tên sản phẩm';
        if ($priceVnd <= 0)
            $errors[] = 'Giá bán phải lớn hơn 0';
        if ($catId <= 0)
            $errors[] = 'Vui lòng chọn danh mục cho sản phẩm';
        if ($productType === 'link' && $sourceLink === '') {
            $errors[] = 'Vui lòng nhập link download cho sản phẩm loại Source Link';
        }

        if ($productType === 'link') {
            $requiresInfo = 0;
            $infoInstructions = '';
            $manualStock = 0;
            $minPurchaseQty = 1;
            $maxPurchaseQty = 1;
        } elseif ($requiresInfo !== 1) {
            $manualStock = 0;
        }

        if ($minPurchaseQty < 1)
            $errors[] = 'Số lượng mua tối thiểu phải từ 1';
        if ($maxPurchaseQty > 0 && $maxPurchaseQty < $minPurchaseQty)
            $errors[] = 'Số lượng mua tối đa phải >= tối thiểu hoặc = 0';

        $slug = $slug === '' ? $this->productModel->generateSlug($name, $excludeId) : $this->productModel->generateSlug($slug, $excludeId);

        $data = [
            'name' => $name,
            'slug' => $slug,
            'product_type' => $productType,
            'price_vnd' => $priceVnd,
            'old_price' => $oldPrice,
            'source_link' => $productType === 'link' && $sourceLink !== '' ? $sourceLink : null,
            'manual_stock' => ($productType === 'account' && $requiresInfo === 1) ? $manualStock : 0,
            'requires_info' => $productType === 'account' ? $requiresInfo : 0,
            'info_instructions' => ($productType === 'account' && $requiresInfo === 1 && $infoInstructions !== '') ? $infoInstructions : null,
            'min_purchase_qty' => $minPurchaseQty,
            'max_purchase_qty' => $maxPurchaseQty,
            'badge_text' => $badgeText !== '' ? $badgeText : null,
            'category_id' => $catId > 0 ? $catId : null,
            'display_order' => $displayOrder,
            'image' => $image !== '' ? $image : null,
            'gallery' => !empty($gallery) ? json_encode($gallery, JSON_UNESCAPED_UNICODE) : null,
            'description' => $description,
            'seo_description' => $seoDescription !== '' ? $seoDescription : null,
        ];

        $data = array_merge($data, Product::buildVisibilityPayload($visibilityMode));

        if ($excludeId === null) {
            $data['created_at'] = $this->timeService ? $this->timeService->nowSql() : date('Y-m-d H:i:s');
        }

        return [$data, $errors];
    }

    private function normalizeJsonTextarea(string $raw, string $fieldLabel, array &$errors): ?string
    {
        $raw = trim($raw);
        if ($raw === '')
            return null;
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = $fieldLabel . ' không đúng định dạng JSON';
            return null;
        }
        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

}


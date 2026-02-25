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

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->productModel = new Product();
        $this->stockModel = new ProductStock();
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
            'status' => $this->get('status', ''),
            'category_id' => $this->get('category_id', ''),
            'type' => $this->get('type', ''),
        ];

        $products = $this->productModel->getFiltered($filters);
        $categories = $this->productModel->getCategories();
        $stats = $this->productModel->getStats();

        // Load stock stats for account products
        $accountProductIds = array_map(
            fn($p) => (int) $p['id'],
            array_filter($products, fn($p) => ($p['product_type'] ?? 'account') === 'account')
        );
        $stockStats = $this->stockModel->getStatsForProducts($accountProductIds);

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
        if ($id <= 0)
            return $this->json(['success' => false, 'message' => 'Thiếu ID'], 400);
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

        // If account type: import initial stock from textarea
        if (($data['product_type'] ?? 'account') === 'account') {
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

        $this->view('admin/products/edit', [
            'chungapi' => $chungapi,
            'product' => $product,
            'categories' => $this->productModel->getCategories(),
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
        if (!$product || ($product['product_type'] ?? 'account') !== 'account') {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Sản phẩm không tồn tại hoặc không phải loại tài khoản'];
            $this->redirect(url('admin/products'));
        }

        $statusFilter = $this->get('status_filter', '');
        $items = $this->stockModel->getByProduct($id, $statusFilter);
        $stats = $this->stockModel->getStats($id);

        $this->view('admin/products/stock', [
            'chungapi' => $chungapi,
            'product' => $product,
            'items' => $items,
            'stats' => $stats,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function stockImport($id)
    {
        $this->requireAdmin();
        $id = (int) $id;
        $product = $this->productModel->find($id);
        if (!$product || ($product['product_type'] ?? 'account') !== 'account') {
            return $this->json(['success' => false, 'message' => 'Sản phẩm không hợp lệ'], 400);
        }

        $rawText = trim((string) $this->post('content', ''));
        if ($rawText === '') {
            return $this->json(['success' => false, 'message' => 'Nội dung trống'], 400);
        }

        $result = $this->stockModel->importBulk($id, $rawText);
        return $this->json([
            'success' => true,
            'message' => "Đã nhập {$result['added']} item. Bỏ qua {$result['skipped']} trùng lặp.",
            'added' => $result['added'],
            'skipped' => $result['skipped'],
        ]);
    }

    public function stockDelete()
    {
        $this->requireAdmin();
        $id = (int) $this->post('id', 0);
        if ($id <= 0)
            return $this->json(['success' => false, 'message' => 'Thiếu ID'], 400);

        if ($this->stockModel->deleteAvailable($id)) {
            return $this->json(['success' => true, 'message' => 'Đã xóa item']);
        }
        return $this->json(['success' => false, 'message' => 'Không thể xóa (đã bán hoặc không tồn tại)'], 400);
    }

    public function stockUpdate()
    {
        $this->requireAdmin();
        $id = (int) $this->post('id', 0);
        $content = trim((string) $this->post('content', ''));

        if ($id <= 0)
            return $this->json(['success' => false, 'message' => 'Thiếu ID'], 400);
        if ($content === '')
            return $this->json(['success' => false, 'message' => 'Nội dung không được để trống'], 400);

        if ($this->stockModel->updateContent($id, $content)) {
            return $this->json(['success' => true, 'message' => 'Đã cập nhật']);
        }
        return $this->json(['success' => false, 'message' => 'Không thể cập nhật (đã bán hoặc lỗi)'], 400);
    }

    // ==================== BUILD SAVE PAYLOAD ====================
    private function buildAdminProductSavePayload(?int $excludeId = null): array
    {
        $errors = [];

        $name = trim((string) $this->post('name', ''));
        $productType = $this->post('product_type', 'account') === 'link' ? 'link' : 'account';
        $priceVnd = max(0, (int) $this->post('price_vnd', 0));
        $sourceLink = trim((string) $this->post('source_link', ''));
        $description = (string) $this->post('description', '');
        $image = trim((string) $this->post('image', ''));
        $badgeText = trim((string) $this->post('badge_text', ''));
        $seoDescription = trim((string) $this->post('seo_description', ''));
        $catId = (int) $this->post('category_id', 0);
        $displayOrder = max(0, (int) $this->post('display_order', 0));
        $status = $this->post('status', 'ON') === 'OFF' ? 'OFF' : 'ON';
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
        if ($productType === 'link' && $sourceLink === '')
            $errors[] = 'Vui lòng nhập link download cho sản phẩm loại Source Link';

        $slug = $slug === '' ? $this->productModel->generateSlug($name, $excludeId) : $this->productModel->generateSlug($slug, $excludeId);

        $data = [
            'name' => $name,
            'slug' => $slug,
            'product_type' => $productType,
            'price_vnd' => $priceVnd,
            'source_link' => $productType === 'link' && $sourceLink !== '' ? $sourceLink : null,
            'badge_text' => $badgeText !== '' ? $badgeText : null,
            'category_id' => $catId > 0 ? $catId : null,
            'display_order' => $displayOrder,
            'status' => $status,
            'image' => $image !== '' ? $image : null,
            'gallery' => !empty($gallery) ? json_encode($gallery, JSON_UNESCAPED_UNICODE) : null,
            'description' => $description,
            'seo_description' => $seoDescription !== '' ? $seoDescription : null,
        ];

        if ($excludeId === null) {
            $data['created_at'] = date('Y-m-d H:i:s');
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

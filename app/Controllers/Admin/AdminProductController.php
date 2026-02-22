<?php

/**
 * Admin Product Controller
 * Handles product CRUD and toggle operations in admin panel
 */
class AdminProductController extends Controller
{
    private $authService;
    private $productModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->productModel = new Product();
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
            die('Truy cập bị từ chối - Chỉ dành cho quản trị viên');
        }
    }

    /**
     * List products with filter/search
     */
    public function index()
    {
        $this->requireAdmin();
        global $chungapi;

        $filters = [
            'search' => $this->get('search', ''),
            'status' => $this->get('status', ''),
            'category_id' => $this->get('category_id', ''),
            'hidden' => $this->get('hidden', ''),
        ];

        $products = $this->productModel->getFiltered($filters);
        $categories = $this->productModel->getCategories();
        $stats = $this->productModel->getStats();

        $this->view('admin/products/index', [
            'products' => $products,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $filters,
            'chungapi' => $chungapi,
        ]);
    }

    /**
     * AJAX: Toggle is_hidden
     */
    public function toggleHide()
    {
        $this->requireAdmin();
        $id = $this->post('id');
        if (!$id) {
            return $this->json(['success' => false, 'message' => 'Missing ID']);
        }
        $result = $this->productModel->toggleField((int) $id, 'is_hidden');
        return $this->json($result);
    }

    /**
     * AJAX: Toggle is_pinned
     */
    public function togglePin()
    {
        $this->requireAdmin();
        $id = $this->post('id');
        if (!$id) {
            return $this->json(['success' => false, 'message' => 'Missing ID']);
        }
        $result = $this->productModel->toggleField((int) $id, 'is_pinned');
        return $this->json($result);
    }

    /**
     * AJAX: Toggle is_active
     */
    public function toggleActive()
    {
        $this->requireAdmin();
        $id = $this->post('id');
        if (!$id) {
            return $this->json(['success' => false, 'message' => 'Missing ID']);
        }
        $result = $this->productModel->toggleField((int) $id, 'is_active');
        return $this->json($result);
    }

    /**
     * AJAX Delete product
     */
    public function delete()
    {
        $this->requireAdmin();

        $id = (int) $this->post('id', 0);
        if (!$id) {
            return $this->json(['success' => false, 'message' => 'Thiếu ID sản phẩm']);
        }

        $product = $this->productModel->find($id);
        if (!$product) {
            return $this->json(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        }

        if ($this->productModel->delete($id)) {
            return $this->json(['success' => true, 'message' => 'Xóa sản phẩm thành công']);
        }

        return $this->json(['success' => false, 'message' => 'Lỗi máy chủ']);
    }

    /**
     * Show add product form
     */
    public function add()
    {
        $this->requireAdmin();
        global $chungapi;

        $categories = $this->productModel->getCategories();

        $this->view('admin/products/add', [
            'chungapi' => $chungapi,
            'categories' => $categories,
        ]);
    }

    /**
     * Process add product
     */
    public function store()
    {
        $this->requireAdmin();

        $name = trim($this->post('name', ''));
        $price = (int) $this->post('price_vnd', 0);
        $desc = $this->post('description', '');
        $image = trim($this->post('image', ''));
        $catId = (int) $this->post('category_id', 0);
        $displayOrder = (int) $this->post('display_order', 0);
        $isActive = (int) $this->post('is_active', 1);
        $slug = trim($this->post('slug', ''));

        // Gallery: array of image URLs
        $galleryInput = $this->post('gallery', []);
        if (is_array($galleryInput)) {
            $galleryInput = array_values(array_filter(array_map('trim', $galleryInput)));
        } else {
            $galleryInput = [];
        }

        if (empty($name) || $price <= 0) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Vui lòng nhập đầy đủ thông tin'];
            $this->redirect(url('admin/products/add'));
        }

        // Auto-generate slug if empty
        if (empty($slug)) {
            $slug = $this->productModel->generateSlug($name);
        } else {
            $slug = $this->productModel->generateSlug($slug);
        }

        // Get category name
        $catModel = new Category();
        $cat = $catModel->find($catId);
        $catName = $cat ? $cat['name'] : '';

        $this->productModel->create([
            'name' => $name,
            'slug' => $slug,
            'price_vnd' => $price,
            'description' => $desc,
            'image' => $image,
            'gallery' => !empty($galleryInput) ? json_encode($galleryInput) : null,
            'category' => $catName,
            'category_id' => $catId,
            'display_order' => $displayOrder,
            'is_active' => $isActive,
            'is_hidden' => 0,
            'is_pinned' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Thêm sản phẩm thành công'];
        $this->redirect(url('admin/products'));
    }

    /**
     * Show edit product form
     */
    public function edit($id)
    {
        $this->requireAdmin();
        global $chungapi;

        $product = $this->productModel->find((int) $id);
        if (!$product) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Sản phẩm không tồn tại'];
            $this->redirect(url('admin/products'));
        }

        // Parse gallery
        $product['gallery_arr'] = [];
        if (!empty($product['gallery'])) {
            $decoded = json_decode($product['gallery'], true);
            if (is_array($decoded)) {
                $product['gallery_arr'] = $decoded;
            }
        }

        $categories = $this->productModel->getCategories();

        $this->view('admin/products/edit', [
            'chungapi' => $chungapi,
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    /**
     * Process update product
     */
    public function update($id)
    {
        $this->requireAdmin();

        $product = $this->productModel->find((int) $id);
        if (!$product) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Sản phẩm không tồn tại'];
            $this->redirect(url('admin/products'));
        }

        $name = trim($this->post('name', ''));
        $price = (int) $this->post('price_vnd', 0);
        $desc = $this->post('description', '');
        $image = trim($this->post('image', ''));
        $catId = (int) $this->post('category_id', 0);
        $displayOrder = (int) $this->post('display_order', 0);
        $isActive = (int) $this->post('is_active', 1);
        $slug = trim($this->post('slug', ''));

        // Gallery
        $galleryInput = $this->post('gallery', []);
        if (is_array($galleryInput)) {
            $galleryInput = array_values(array_filter(array_map('trim', $galleryInput)));
        } else {
            $galleryInput = [];
        }

        if (empty($name) || $price <= 0) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Vui lòng nhập đầy đủ thông tin'];
            $this->redirect(url('admin/products/edit/' . $id));
        }

        // Slug
        if (empty($slug)) {
            $slug = $this->productModel->generateSlug($name, (int) $id);
        } else {
            $slug = $this->productModel->generateSlug($slug, (int) $id);
        }

        // Get category name
        $catModel = new Category();
        $cat = $catModel->find($catId);
        $catName = $cat ? $cat['name'] : '';

        $this->productModel->update((int) $id, [
            'name' => $name,
            'slug' => $slug,
            'price_vnd' => $price,
            'description' => $desc,
            'image' => $image,
            'gallery' => !empty($galleryInput) ? json_encode($galleryInput) : null,
            'category' => $catName,
            'category_id' => $catId,
            'display_order' => $displayOrder,
            'is_active' => $isActive,
        ]);

        $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cập nhật sản phẩm thành công'];
        $this->redirect(url('admin/products'));
    }
}

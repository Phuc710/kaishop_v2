<?php

/**
 * Admin Category Controller
 * Full CRUD for categories with PDO model
 */
class CategoryController extends Controller
{
    private $authService;
    private $categoryModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->categoryModel = new Category();
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
     * List categories
     */
    public function index()
    {
        $this->requireAdmin();
        global $chungapi;

        // Auto add slug column if it doesn't exist
        $db = Database::getInstance()->getConnection();
        try {
            $db->exec("ALTER TABLE `categories` ADD COLUMN `slug` VARCHAR(100) NULL AFTER `name`");
        } catch (PDOException $e) {
            // Column might already exist
        }

        $categories = $this->categoryModel->getAll();
        $stats = $this->categoryModel->getStats();

        // Add product count per category
        foreach ($categories as $index => $cat) {
            $categories[$index]['product_count'] = $this->categoryModel->countProducts($cat['id']);
        }

        $this->view('admin/categories/index', [
            'categories' => $categories,
            'stats' => $stats,
            'chungapi' => $chungapi,
        ]);
    }

    /**
     * Show add category form
     */
    public function add()
    {
        $this->requireAdmin();
        global $chungapi;

        $this->view('admin/categories/add', [
            'chungapi' => $chungapi ?? null,
        ]);
    }

    /**
     * Process add category
     */
    public function store()
    {
        $this->requireAdmin();

        $name = trim($this->post('name', ''));
        $slug = trim($this->post('slug', ''));
        $icon = trim($this->post('icon', ''));
        $description = trim($this->post('description', ''));
        $displayOrder = (int) $this->post('display_order', 0);
        $status = $this->post('status', 'ON');

        if (empty($name)) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Vui lòng nhập tên danh mục'];
            $this->redirect(url('admin/categories/add'));
        }

        if (empty($slug)) {
            $slug = FormatHelper::toSlug($name);
        } else {
            $slug = FormatHelper::toSlug($slug);
        }

        // Check if slug already exists
        $existing = $this->categoryModel->findBySlug($slug);
        if ($existing) {
            $slug = $slug . '-' . time();
        }

        $this->categoryModel->create([
            'name' => $name,
            'slug' => $slug,
            'icon' => $icon,
            'description' => $description,
            'display_order' => $displayOrder,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Thêm danh mục thành công'];
        $this->redirect(url('admin/categories'));
    }

    /**
     * Show edit category form
     */
    public function edit($id)
    {
        $this->requireAdmin();
        global $chungapi;

        $category = $this->categoryModel->find((int) $id);

        if (!$category) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Danh mục không tồn tại'];
            $this->redirect(url('admin/categories'));
        }

        $this->view('admin/categories/edit', [
            'chungapi' => $chungapi ?? null,
            'category' => $category,
        ]);
    }

    /**
     * Process update category
     */
    public function update($id)
    {
        $this->requireAdmin();

        $category = $this->categoryModel->find((int) $id);
        if (!$category) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Danh mục không tồn tại'];
            $this->redirect(url('admin/categories'));
        }

        $name = trim($this->post('name', ''));
        $slug = trim($this->post('slug', ''));
        $icon = trim($this->post('icon', ''));
        $description = trim($this->post('description', ''));
        $displayOrder = (int) $this->post('display_order', 0);
        $status = $this->post('status', 'ON');

        if (empty($name)) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Vui lòng nhập đầy đủ thông tin'];
            $this->redirect(url('admin/categories/edit/' . $id));
        }

        if (empty($slug)) {
            $slug = FormatHelper::toSlug($name);
        } else {
            $slug = FormatHelper::toSlug($slug);
        }

        // Check unique slug ignore self
        $existing = $this->categoryModel->findBySlug($slug);
        if ($existing && $existing['id'] != $id) {
            $slug = $slug . '-' . time();
        }

        $this->categoryModel->update((int) $id, [
            'name' => $name,
            'slug' => $slug,
            'icon' => $icon,
            'description' => $description,
            'display_order' => $displayOrder,
            'status' => $status,
        ]);

        $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cập nhật thành công'];
        $this->redirect(url('admin/categories'));
    }

    /**
     * AJAX Delete category
     */
    public function delete()
    {
        $this->requireAdmin();

        $id = (int) $this->post('id', 0);
        if (!$id) {
            return $this->json(['success' => false, 'message' => 'Thiếu ID']);
        }

        $category = $this->categoryModel->find($id);
        if (!$category) {
            return $this->json(['success' => false, 'message' => 'Danh mục không tồn tại']);
        }

        // Check if category has products
        $productCount = $this->categoryModel->countProducts($id);
        if ($productCount > 0) {
            return $this->json(['success' => false, 'message' => "Không thể xóa — danh mục đang có {$productCount} sản phẩm"]);
        }

        if ($this->categoryModel->delete($id)) {
            return $this->json(['success' => true, 'message' => 'Xóa danh mục thành công']);
        }

        return $this->json(['success' => false, 'message' => 'Lỗi máy chủ']);
    }
}

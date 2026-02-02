<?php

/**
 * Admin Category Controller
 */
class CategoryController extends Controller {
    private $authService;
    
    public function __construct() {
        $this->authService = new AuthService();
    }
    
    /**
     * Check admin access
     */
    private function requireAdmin() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        
        if (!isset($user['level']) || $user['level'] != 9) {
            http_response_code(403);
            die('Access denied - Admin only');
        }
    }
    
    /**
     * List categories
     */
    public function index() {
        $this->requireAdmin();
        global $connection, $chungapi;
        
        // Deletion logic
        if (isset($_GET['delete'])) {
            $delete = $_GET['delete'];
            $result = $connection->query("DELETE FROM `categories` WHERE `id` = '$delete'");
            if ($result) {
                $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Xóa danh mục thành công'];
            } else {
                $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ hoặc danh mục đang được sử dụng'];
            }
            $this->redirect(url('admin/categories'));
        }
        
        $categories = $connection->query("SELECT * FROM `categories` ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/categories/index', [
            'categories' => $categories,
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Show add category form
     */
    public function add() {
        $this->requireAdmin();
        global $chungapi;
        
        $this->view('admin/categories/add', [
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Process add category
     */
    public function store() {
        $this->requireAdmin();
        global $connection;
        
        $name = $this->post('name');
        $status = $this->post('status');

        if (empty($name)) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Vui lòng nhập tên danh mục'];
            $this->redirect(url('admin/categories/add'));
        } else {
            $name = $connection->real_escape_string($name);
            $sql = "INSERT INTO `categories` (`name`, `status`, `created_at`) VALUES ('$name', '$status', NOW())";
            
            if ($connection->query($sql)) {
                $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Thêm danh mục thành công'];
                $this->redirect(url('admin/categories'));
            } else {
                $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
                $this->redirect(url('admin/categories/add'));
            }
        }
    }
    
    /**
     * Show edit category form
     */
    public function edit($id) {
        $this->requireAdmin();
        global $chungapi, $connection;
        
        $category = $connection->query("SELECT * FROM `categories` WHERE `id` = '$id'")->fetch_assoc();
        
        if (!$category) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Danh mục không tồn tại'];
            $this->redirect(url('admin/categories'));
        }
        
        $this->view('admin/categories/edit', [
            'chungapi' => $chungapi,
            'category' => $category
        ]);
    }
    
    /**
     * Process update category
     */
    public function update($id) {
        $this->requireAdmin();
        global $connection;
        
        $name = $this->post('name');
        $status = $this->post('status');

        if (empty($name)) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Vui lòng nhập đầy đủ thông tin'];
            $this->redirect(url('admin/categories/edit/' . $id));
        } else {
            $name = $connection->real_escape_string($name);
            $sql = "UPDATE `categories` SET `name` = '$name', `status` = '$status' WHERE `id` = '$id'";
            
            if ($connection->query($sql)) {
                $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cập nhật thành công'];
                $this->redirect(url('admin/categories'));
            } else {
                $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
                $this->redirect(url('admin/categories/edit/' . $id));
            }
        }
    }
}

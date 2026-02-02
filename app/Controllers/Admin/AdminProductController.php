<?php

/**
 * Admin Product Controller
 */
class AdminProductController extends Controller {
    private $authService;
    private $productModel;
    
    public function __construct() {
        $this->authService = new AuthService();
        $this->productModel = new Product();
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
     * List products
     */
    public function index() {
        $this->requireAdmin();
        global $connection, $chungapi;
        
        // Deletion logic (moved from list-product.php)
        if (isset($_GET['delete'])) {
            $delete = $_GET['delete'];
            $result = $connection->query("DELETE FROM `products` WHERE `id` = '" . $delete . "' ");
            if ($result) {
                $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Xóa thành công'];
            } else {
                $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ'];
            }
            $this->redirect(url('admin/products'));
        }
        
        $products = $this->productModel->getAvailable(); // Note: This only gets 'ON'. For admin, we should get ALL.
        $allProducts = $connection->query("SELECT * FROM `products` ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/products/index', [
            'products' => $allProducts,
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Show add product form
     */
    public function add() {
        $this->requireAdmin();
        global $chungapi, $connection;
        
        $categories = $connection->query("SELECT * FROM `categories` WHERE `status` = 'ON' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/products/add', [
            'chungapi' => $chungapi,
            'categories' => $categories
        ]);
    }
    
    /**
     * Process add product
     */
    public function store() {
        $this->requireAdmin();
        global $connection;
        
        $name = $this->post('name');
        $price = $this->post('price');
        $description = $this->post('description');
        $image = $this->post('image');
        $category = $this->post('category');
        $status = $this->post('status');

        if (empty($name) || empty($price)) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Vui lòng nhập đầy đủ thông tin'];
            $this->redirect(url('admin/products/add'));
        } else {
            // Basic sanitization/escaping for now since it was using raw query
            $name = $connection->real_escape_string($name);
            $description = $connection->real_escape_string($description);
            $image = $connection->real_escape_string($image);
            $category = $connection->real_escape_string($category);
            
            $sql = "INSERT INTO `products` (`name`, `price`, `description`, `image`, `category`, `status`, `created_at`) 
                    VALUES ('$name', '$price', '$description', '$image', '$category', '$status', NOW())";
            
            if ($connection->query($sql)) {
                $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Thêm sản phẩm thành công'];
                $this->redirect(url('admin/products'));
            } else {
                $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
                $this->redirect(url('admin/products/add'));
            }
        }
    }

    /**
     * Show edit product form
     */
    public function edit($id) {
        $this->requireAdmin();
        global $chungapi, $connection;
        
        $product = $connection->query("SELECT * FROM `products` WHERE `id` = '$id'")->fetch_assoc();
        
        if (!$product) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Sản phẩm không tồn tại'];
            $this->redirect(url('admin/products'));
        }
        
        $categories = $connection->query("SELECT * FROM `categories` WHERE `status` = 'ON' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/products/edit', [
            'chungapi' => $chungapi,
            'product' => $product,
            'categories' => $categories
        ]);
    }
    
    /**
     * Process update product
     */
    public function update($id) {
        $this->requireAdmin();
        global $connection;
        
        $name = $this->post('name');
        $price = $this->post('price');
        $description = $this->post('description');
        $image = $this->post('image');
        $category = $this->post('category');
        $status = $this->post('status');

        if (empty($name) || empty($price)) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Vui lòng nhập đầy đủ thông tin'];
            $this->redirect(url('admin/products/edit/' . $id));
        } else {
            $name = $connection->real_escape_string($name);
            $description = $connection->real_escape_string($description);
            $image = $connection->real_escape_string($image);
            $category = $connection->real_escape_string($category);
            
            $sql = "UPDATE `products` SET 
                    `name` = '$name', 
                    `price` = '$price', 
                    `description` = '$description', 
                    `image` = '$image', 
                    `category` = '$category', 
                    `status` = '$status' 
                    WHERE `id` = '$id'";
            
            if ($connection->query($sql)) {
                $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cập nhật thành công'];
                $this->redirect(url('admin/products'));
            } else {
                $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
                $this->redirect(url('admin/products/edit/' . $id));
            }
        }
    }
}

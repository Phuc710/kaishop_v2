<?php

/**
 * Admin Dashboard Controller
 */
class DashboardController extends Controller
{
    private $authService;
    private $userModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userModel = new User();
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
     * Show admin dashboard
     */
    public function index()
    {
        $this->requireAdmin();
        global $chungapi, $connection;

        // Thống kê
        $totalUsers = $connection->query("SELECT COUNT(*) as total FROM users WHERE level = 0")->fetch_assoc()['total'];
        $totalBanned = $connection->query("SELECT COUNT(*) as total FROM users WHERE bannd = 1")->fetch_assoc()['total'];
        $totalMoney = $connection->query("SELECT COALESCE(SUM(money), 0) as total FROM users WHERE money >= 0 AND level = 0")->fetch_assoc()['total'];
        $totalProducts = $connection->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];

        $this->view('admin/dashboard', [
            'chungapi' => $chungapi,
            'totalUsers' => $totalUsers,
            'totalBanned' => $totalBanned,
            'totalMoney' => $totalMoney,
            'totalProducts' => $totalProducts,
        ]);
    }
}

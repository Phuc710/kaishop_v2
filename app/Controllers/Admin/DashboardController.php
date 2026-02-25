<?php

/**
 * Admin Dashboard Controller
 */
class DashboardController extends Controller
{
    private AuthService $authService;
    private User $userModel;
    private PDO $db;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userModel = new User();
        $this->db = Database::getInstance()->getConnection();
    }

    private function requireAdmin(): void
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();

        if (!isset($user['level']) || (int) $user['level'] !== 9) {
            http_response_code(403);
            die('Truy cap bi tu choi - Chi danh cho quan tri vien');
        }
    }

    public function index()
    {
        $this->requireAdmin();
        global $chungapi;

        $totalUsers = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE level = 0")->fetchColumn();
        $totalBanned = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE bannd = 1")->fetchColumn();
        $totalMoney = (int) $this->db->query("SELECT COALESCE(SUM(money), 0) FROM users WHERE money >= 0 AND level = 0")->fetchColumn();
        $totalProducts = (int) $this->db->query("SELECT COUNT(*) FROM products")->fetchColumn();

        $this->view('admin/dashboard', [
            'chungapi' => $chungapi,
            'totalUsers' => $totalUsers,
            'totalBanned' => $totalBanned,
            'totalMoney' => $totalMoney,
            'totalProducts' => $totalProducts,
        ]);
    }
}


<?php

/**
 * Admin Dashboard Controller
 */
class DashboardController extends Controller {
    private $authService;
    private $userModel;
    
    public function __construct() {
        $this->authService = new AuthService();
        $this->userModel = new User();
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
     * Show admin dashboard
     */
    public function index() {
        $this->requireAdmin();
        global $chungapi, $ketnoi;
        
        // Get statistics
        $totalUsers = $this->userModel->query("SELECT COUNT(*) as total FROM users")->fetch()['total'];
        $totalDomains = $this->userModel->query("SELECT COUNT(*) as total FROM history_domain")->fetch()['total'];
        $totalHosting = $this->userModel->query("SELECT COUNT(*) as total FROM lich_su_mua_host")->fetch()['total'];
        
        $this->view('admin/dashboard', [
            'chungapi' => $chungapi,
            'totalUsers' => $totalUsers,
            'totalDomains' => $totalDomains,
            'totalHosting' => $totalHosting
        ]);
    }
}

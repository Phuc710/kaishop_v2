<?php

/**
 * Admin Middleware
 * Checks if user is admin
 */
class AdminMiddleware {
    private $authService;
    
    public function __construct() {
        $this->authService = new AuthService();
    }
    
    /**
     * Check if user is admin
     */
    public function handle() {
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        $user = $this->authService->getCurrentUser();
        
        if (!isset($user['level']) || $user['level'] != 9) {
            http_response_code(403);
            die('Truy cập bị từ chối - Chỉ dành cho quản trị viên');
        }
        
        return true;
    }
}

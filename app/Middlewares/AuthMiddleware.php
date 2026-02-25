<?php

/**
 * Auth Middleware
 * Checks if user is authenticated
 */
class AuthMiddleware {
    private $authService;
    
    public function __construct() {
        $this->authService = new AuthService();
    }
    
    /**
     * Check if user is logged in
     * Redirect to login if not
     */
    public function handle() {
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        return true;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        if (!$this->authService->isLoggedIn()) {
            return false;
        }
        
        $user = $this->authService->getCurrentUser();
        return isset($user['level']) && $user['level'] == 9;
    }
}

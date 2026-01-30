<?php

/**
 * Authentication Service
 * Handles user authentication logic
 */
class AuthService {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['session']) && !empty($_SESSION['session']);
    }
    
    /**
     * Get current logged in user
     * @return array|null
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $session = $_SESSION['session'];
        return $this->userModel->findBySession($session);
    }
    
    /**
     * Get current user ID
     * @return int|null
     */
    public function getUserId() {
        $user = $this->getCurrentUser();
        return $user ? $user['id'] : null;
    }
    
    /**
     * Require authentication (redirect if not logged in)
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }
}

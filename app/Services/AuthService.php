<?php

/**
 * Authentication Service
 * Handles user authentication logic
 */
class AuthService {
    private $userModel;
    private $authSecurity;
    
    public function __construct() {
        $this->userModel = new User();
        $this->authSecurity = class_exists('AuthSecurityService') ? new AuthSecurityService() : null;
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn() {
        if (isset($_SESSION['session']) && !empty($_SESSION['session'])) {
            $user = $this->userModel->findBySession($_SESSION['session']);
            if ($user) {
                return true;
            }
            unset($_SESSION['session']);
        }

        if ($this->authSecurity) {
            $user = $this->authSecurity->bootstrapFromCookies();
            return $user !== null;
        }

        return false;
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
        $user = $this->userModel->findBySession($session);
        if ($user) {
            return $user;
        }

        if ($this->authSecurity) {
            return $this->authSecurity->bootstrapFromCookies();
        }

        return null;
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

    public function logout(): void
    {
        if ($this->authSecurity) {
            $this->authSecurity->revokeCurrentAuthSessionFromCookies();
        }
        session_destroy();
    }
}

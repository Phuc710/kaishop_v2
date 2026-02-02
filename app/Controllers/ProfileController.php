<?php

/**
 * Profile Controller
 * Handles user profile operations
 */
class ProfileController extends Controller {
    private $userModel;
    private $authService;
    private $validator;
    
    public function __construct() {
        $this->userModel = new User();
        $this->authService = new AuthService();
        $this->validator = new UserValidator();
    }
    
    /**
     * Show profile page
     */
    public function index() {
        // Require authentication
        $this->authService->requireAuth();
        
        // Get current user
        $user = $this->authService->getCurrentUser();
        $username = $user['username'];
        
        // Get site config
        $siteConfig = Config::getSiteConfig();
        
        // Render view
        $this->view('profile/index', [
            'user' => $user,
            'username' => $username,
            'chungapi' => $siteConfig
        ]);
    }
    
    /**
     * Update profile (AJAX endpoint)
     */
    public function update() {
        // Require authentication
        if (!$this->authService->isLoggedIn()) {
            return $this->json([
                'success' => false,
                'message' => 'Not logged in'
            ], 401);
        }
        
        $user = $this->authService->getCurrentUser();
        $newEmail = trim($this->post('email', ''));
        
        // Validate email
        $errors = $this->validator->validateEmail($newEmail);
        
        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'message' => $errors[0]
            ], 400);
        }
        
        // Check if email already exists (for other users)
        if ($this->userModel->emailExists($newEmail, $user['id'])) {
            return $this->json([
                'success' => false,
                'message' => 'Email is already in use by another account'
            ], 400);
        }
        
        // Update email
        $success = $this->userModel->updateEmail($user['id'], $newEmail);
        
        if ($success) {
            return $this->json([
                'success' => true,
                'message' => 'Email updated successfully'
            ]);
        } else {
            return $this->json([
                'success' => false,
                'message' => 'An error occurred, please try again'
            ], 500);
        }
    }
}

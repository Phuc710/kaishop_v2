<?php

/**
 * Admin User Controller
 * Handles admin user management
 */
class AdminUserController extends Controller {
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
     * Show user list
     */
    public function index() {
        $this->requireAdmin();
        global $chungapi;
        
        $users = $this->userModel->all();
        
        $this->view('admin/users/index', [
            'chungapi' => $chungapi,
            'users' => $users
        ]);
    }
    
    /**
     * Edit user (AJAX)
     */
    public function edit() {
        $this->requireAdmin();
        
        $userId = $this->post('user_id');
        $data = [
            'money' => $this->post('money'),
            'level' => $this->post('level'),
            'bannd' => $this->post('bannd')
        ];
        
        $this->userModel->update($userId, $data);
        
        return $this->json(['success' => true, 'message' => 'Cập nhật thành công']);
    }
    
    /**
     * Delete user
     */
    public function delete() {
        $this->requireAdmin();
        
        $userId = $this->post('user_id');
        $this->userModel->delete($userId);
        
        return $this->json(['success' => true, 'message' => 'Xóa thành công']);
    }
}

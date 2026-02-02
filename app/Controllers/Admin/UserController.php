<?php

/**
 * Admin User Controller
 * Handles admin user management
 */
class UserController extends Controller {
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
     * Show edit user form
     */
    public function edit($username) {
        $this->requireAdmin();
        global $chungapi, $connection;
        
        $user = $connection->query("SELECT * FROM `users` WHERE `username` = '$username'")->fetch_assoc();
        
        if (!$user) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Thành viên không tồn tại'];
            $this->redirect(url('admin/users'));
        }
        
        $this->view('admin/users/edit', [
            'chungapi' => $chungapi,
            'toz_user' => $user
        ]);
    }

    /**
     * Update user details
     */
    public function update($username) {
        $this->requireAdmin();
        global $connection;

        $email = $this->post('email');
        $level = $this->post('level');
        $bannd = $this->post('bannd');
        $new_username = $this->post('username');

        $sql = "UPDATE `users` SET 
                `username` = '$new_username',
                `email` = '$email',
                `bannd` = '$bannd',
                `level` = '$level' 
                WHERE `username` = '$username'";

        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cập nhật thành công'];
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Có lỗi xảy ra: ' . $connection->error];
        }
        
        $this->redirect(url('admin/users/edit/' . $new_username));
    }

    /**
     * Add money to user
     */
    public function addMoney($username) {
        $this->requireAdmin();
        global $connection;

        $amount = $this->post('tien_cong');
        $reason = $this->post('rs_cong');
        $now = time();

        $create = $connection->query("UPDATE `users` SET `money` = `money` + '$amount', `tong_nap` = `tong_nap` + '$amount' WHERE `username` = '$username'");
        
        if ($create) {
            $connection->query("INSERT INTO `history_nap_bank` SET 
                `trans_id` = NULL,
                `username` = '$username',
                `type` = 'Hệ thống',
                `ctk` = '$reason',
                `stk` = NULL,
                `thucnhan` = '$amount',
                `status` = 'hoantat',
                `time` = '$now'");

            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cộng tiền thành công'];
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Có lỗi xảy ra'];
        }

        $this->redirect(url('admin/users/edit/' . $username));
    }

    /**
     * Subtract money from user
     */
    public function subMoney($username) {
        $this->requireAdmin();
        global $connection;

        $amount = $this->post('tien_tru');
        $reason = $this->post('rs_tru');
        $now = time();

        $create = $connection->query("UPDATE `users` SET `money` = `money` - '$amount', `tong_nap` = `tong_nap` - '$amount' WHERE `username` = '$username'");
        
        if ($create) {
            $connection->query("INSERT INTO `history_nap_bank` SET 
                `trans_id` = NULL,
                `username` = '$username',
                `type` = 'Hệ thống',
                `ctk` = '$reason',
                `stk` = NULL,
                `thucnhan` = '-$amount',
                `status` = 'hoantat',
                `time` = '$now'");

            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Trừ tiền thành công'];
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Có lỗi xảy ra'];
        }

        $this->redirect(url('admin/users/edit/' . $username));
    }
    
    /**
     * Delete user
     */
    public function delete() {
        $this->requireAdmin();
        
        $userId = $this->post('user_id');
        $this->userModel->delete($userId);
        
        return $this->json(['success' => true, 'message' => 'Deleted successfully']);
    }
}

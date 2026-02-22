<?php

/**
 * Admin User Controller
 * Handles admin user management
 */
class UserController extends Controller
{
    private $authService;
    private $userModel;
    private $fingerprintModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userModel = new User();
        $this->fingerprintModel = new UserFingerprint();
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
     * Show user list
     */
    public function index()
    {
        $this->requireAdmin();
        global $chungapi;

        $users = $this->userModel->all();

        $totalUsers = count($users);
        $bannedUsers = 0;
        $adminUsers = 0;

        foreach ($users as $u) {
            if ($u['bannd'] == 1)
                $bannedUsers++;
            if ($u['level'] == 9)
                $adminUsers++;
        }

        $this->view('admin/users/index', [
            'chungapi' => $chungapi,
            'users' => $users,
            'totalUsers' => $totalUsers,
            'bannedUsers' => $bannedUsers,
            'adminUsers' => $adminUsers
        ]);
    }

    /**
     * Show edit user form
     */
    public function edit($username)
    {
        $this->requireAdmin();
        global $chungapi, $connection;

        $user = $connection->query("SELECT * FROM `users` WHERE `username` = '$username'")->fetch_assoc();

        if (!$user) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Thành viên không tồn tại'];
            $this->redirect(url('admin/users'));
        }

        $fingerprints = $this->fingerprintModel->getByUserId($user['id'], 20);

        $this->view('admin/users/edit', [
            'chungapi' => $chungapi,
            'toz_user' => $user,
            'fingerprints' => $fingerprints
        ]);
    }

    /**
     * Update user details
     */
    public function update($username)
    {
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
    public function addMoney($username)
    {
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

            Logger::info('Billing', 'admin_add_money', "Admin cộng tiền cho {$username}", ['amount' => $amount, 'reason' => $reason]);

            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cộng tiền thành công'];
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Có lỗi xảy ra'];
        }

        $this->redirect(url('admin/users/edit/' . $username));
    }

    /**
     * Subtract money from user
     */
    public function subMoney($username)
    {
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

            Logger::info('Billing', 'admin_sub_money', "Admin trừ tiền của {$username}", ['amount' => $amount, 'reason' => $reason]);

            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Trừ tiền thành công'];
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Có lỗi xảy ra'];
        }

        $this->redirect(url('admin/users/edit/' . $username));
    }

    /**
     * Delete user
     */
    public function delete()
    {
        $this->requireAdmin();

        $userId = $this->post('user_id');
        $this->userModel->delete($userId);

        return $this->json(['success' => true, 'message' => 'Xóa thành công']);
    }

    /**
     * Ban user with reason (AJAX)
     */
    public function banUser($username)
    {
        $this->requireAdmin();
        global $connection;

        $reason = trim($this->post('reason', ''));
        if ($reason === '') {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập lý do ban.']);
        }

        $safeUser = $connection->real_escape_string($username);
        $safeReason = $connection->real_escape_string($reason);

        $result = $connection->query("UPDATE `users` SET `bannd` = 1, `ban_reason` = '{$safeReason}' WHERE `username` = '{$safeUser}'");

        if ($result) {
            Logger::warning('Admin', 'ban_user', "Admin ban user: {$username}", [
                'username' => $username,
                'reason' => $reason
            ]);
            return $this->json(['success' => true, 'message' => 'Đã khóa tài khoản ' . $username]);
        }

        return $this->json(['success' => false, 'message' => 'Có lỗi xảy ra.']);
    }

    /**
     * Unban user (AJAX)
     */
    public function unbanUser($username)
    {
        $this->requireAdmin();
        global $connection;

        $safeUser = $connection->real_escape_string($username);

        $result = $connection->query("UPDATE `users` SET `bannd` = 0, `ban_reason` = NULL WHERE `username` = '{$safeUser}'");

        if ($result) {
            Logger::info('Admin', 'unban_user', "Admin mở khóa user: {$username}", [
                'username' => $username
            ]);
            return $this->json(['success' => true, 'message' => 'Đã mở khóa tài khoản ' . $username]);
        }

        return $this->json(['success' => false, 'message' => 'Có lỗi xảy ra.']);
    }
}

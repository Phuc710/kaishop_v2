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
    private $timeService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userModel = new User();
        $this->fingerprintModel = new UserFingerprint();
        $this->timeService = class_exists('TimeService') ? TimeService::instance() : null;
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
        foreach ($users as &$userRow) {
            $userRow = $this->attachListTimeMeta($userRow, ['created_at', 'time']);
        }
        unset($userRow);

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
     * Ban Device with reason (AJAX)
     */
    public function banDevice($username)
    {
        $this->requireAdmin();
        global $connection;

        $reason = trim($this->post('reason', ''));
        if ($reason === '') {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập lý do khóa.']);
        }

        $user = $this->userModel->findByUsername($username);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Người dùng không tồn tại.']);
        }

        if (empty($user['fingerprint'])) {
            return $this->json(['success' => false, 'message' => 'Chưa có dữ liệu Fingerprint của người dùng này để khóa thiết bị.']);
        }

        // Ensure banned_fingerprints table exists
        $db = Database::getInstance()->getConnection();
        $db->exec("CREATE TABLE IF NOT EXISTS `banned_fingerprints` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `fingerprint_hash` VARCHAR(255) NOT NULL,
            `reason` TEXT DEFAULT NULL,
            `banned_by` VARCHAR(100) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_fp` (`fingerprint_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $fpHash = mysqli_real_escape_string($connection, $user['fingerprint']);
        $safeReason = mysqli_real_escape_string($connection, $reason);
        $adminName = mysqli_real_escape_string($connection, $_SESSION['admin'] ?? 'Admin');

        // Insert into banned_fingerprints
        $sql = "INSERT INTO `banned_fingerprints` (`fingerprint_hash`, `reason`, `banned_by`) 
                VALUES ('$fpHash', '$safeReason', '$adminName') 
                ON DUPLICATE KEY UPDATE `reason` = '$safeReason', `banned_by` = '$adminName'";
        $connection->query($sql);

        // Also ban the account simultaneously for double protection
        $safeUser = $connection->real_escape_string($username);
        $connection->query("UPDATE `users` SET `bannd` = 1, `ban_reason` = '{$safeReason}' WHERE `username` = '{$safeUser}'");

        Logger::warning('Admin', 'ban_device', 'Khóa thiết bị Fingerprint: ' . $username, ['fingerprint' => $fpHash]);

        return $this->json(['success' => true, 'message' => 'Đã khóa tài khoản và thiết bị của ' . $username]);
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

        // Also Unban Device if they have one currently banned
        $user = $this->userModel->findByUsername($username);
        if ($user && !empty($user['fingerprint'])) {
            $fpHash = $connection->real_escape_string($user['fingerprint']);
            $connection->query("DELETE FROM `banned_fingerprints` WHERE `fingerprint_hash` = '{$fpHash}'");
        }

        if ($result) {
            Logger::info('Admin', 'unban_user', "Admin mở khóa user & thiết bị: {$username}", [
                'username' => $username
            ]);
            return $this->json(['success' => true, 'message' => 'Đã mở khóa tài khoản ' . $username]);
        }

        return $this->json(['success' => false, 'message' => 'Có lỗi xảy ra.']);
    }
    /**
     * @param array<string,mixed> $row
     * @param array<int,string> $candidates
     * @return array<string,mixed>
     */
    private function attachListTimeMeta(array $row, array $candidates): array
    {
        $value = null;
        foreach ($candidates as $field) {
            $candidate = $row[$field] ?? null;
            if ($candidate !== null && trim((string) $candidate) !== '' && (string) $candidate !== '0000-00-00 00:00:00') {
                $value = $candidate;
                break;
            }
        }

        $meta = $this->normalizeTimeMeta($value);
        $row['list_time_ts'] = $meta['ts'];
        $row['list_time_iso'] = $meta['iso'];
        $row['list_time_iso_utc'] = $meta['iso_utc'];
        $row['list_time_display'] = $meta['display'];
        return $row;
    }

    /**
     * @return array{ts:int|null,iso:string,iso_utc:string,display:string}
     */
    private function normalizeTimeMeta($value): array
    {
        if ($this->timeService) {
            return $this->timeService->normalizeApiTime($value);
        }

        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return ['ts' => null, 'iso' => '', 'iso_utc' => '', 'display' => ''];
        }
        $ts = strtotime($raw);
        if ($ts === false) {
            return ['ts' => null, 'iso' => '', 'iso_utc' => '', 'display' => $raw];
        }

        return [
            'ts' => $ts,
            'iso' => date('c', $ts),
            'iso_utc' => gmdate('c', $ts),
            'display' => date('Y-m-d H:i:s', $ts),
        ];
    }
}

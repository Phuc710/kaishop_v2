<?php

/**
 * Admin User Controller
 * Handles admin user management.
 */
class UserController extends Controller
{
    private AuthService $authService;
    private User $userModel;
    private UserFingerprint $fingerprintModel;
    private PDO $db;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userModel = new User();
        $this->fingerprintModel = new UserFingerprint();
        $this->db = Database::getInstance()->getConnection();
    }

    private function requireAdmin(): void
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();

        if (!isset($user['level']) || (int) $user['level'] !== 9) {
            http_response_code(403);
            die('Truy cap bi tu choi - Chi danh cho quan tri vien');
        }
    }

    public function index()
    {
        $this->requireAdmin();
        global $chungapi;

        $users = $this->userModel->all();
        $totalUsers = count($users);
        $bannedUsers = 0;
        $adminUsers = 0;

        foreach ($users as $u) {
            if ((int) ($u['bannd'] ?? 0) === 1) {
                $bannedUsers++;
            }
            if ((int) ($u['level'] ?? 0) === 9) {
                $adminUsers++;
            }
        }

        $this->view('admin/users/index', [
            'chungapi' => $chungapi,
            'users' => $users,
            'totalUsers' => $totalUsers,
            'bannedUsers' => $bannedUsers,
            'adminUsers' => $adminUsers,
        ]);
    }

    public function edit($username)
    {
        $this->requireAdmin();
        global $chungapi;

        $user = $this->userModel->findByUsername((string) $username);
        if (!$user) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Loi', 'message' => 'Thanh vien khong ton tai'];
            $this->redirect(url('admin/users'));
        }

        $fingerprints = $this->fingerprintModel->getByUserId((int) $user['id'], 20);

        $this->view('admin/users/edit', [
            'chungapi' => $chungapi,
            'toz_user' => $user,
            'fingerprints' => $fingerprints,
        ]);
    }

    public function update($username)
    {
        $this->requireAdmin();

        $oldUsername = trim((string) $username);
        $newUsername = trim((string) $this->post('username'));
        $email = trim((string) $this->post('email'));
        $level = (int) $this->post('level');
        $bannd = (int) $this->post('bannd');

        if ($newUsername === '' || $oldUsername === '') {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Loi', 'message' => 'Ten dang nhap khong hop le'];
            $this->redirect(url('admin/users'));
        }

        try {
            $stmt = $this->db->prepare("UPDATE `users` SET `username` = ?, `email` = ?, `bannd` = ?, `level` = ? WHERE `username` = ? LIMIT 1");
            $stmt->execute([$newUsername, $email, $bannd, $level, $oldUsername]);
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thanh Cong', 'message' => 'Cap nhat thanh cong'];
        } catch (Throwable $e) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Loi', 'message' => 'Co loi xay ra'];
        }

        $this->redirect(url('admin/users/edit/' . rawurlencode($newUsername)));
    }

    public function addMoney($username)
    {
        $this->requireAdmin();

        $username = trim((string) $username);
        $amount = (int) $this->post('tien_cong');
        $reason = trim((string) $this->post('rs_cong'));
        $now = (string) time();

        if ($username === '' || $amount <= 0) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Loi', 'message' => 'So tien khong hop le'];
            $this->redirect(url('admin/users/edit/' . rawurlencode($username)));
        }

        try {
            $this->db->beginTransaction();

            $up = $this->db->prepare("UPDATE `users` SET `money` = `money` + ?, `tong_nap` = `tong_nap` + ? WHERE `username` = ? LIMIT 1");
            $up->execute([$amount, $amount, $username]);
            if ($up->rowCount() < 1) {
                throw new RuntimeException('User not found');
            }

            $log = $this->db->prepare("
                INSERT INTO `history_nap_bank`
                (`trans_id`, `username`, `type`, `ctk`, `stk`, `thucnhan`, `status`, `time`)
                VALUES (NULL, ?, 'He thong', ?, NULL, ?, 'hoantat', ?)
            ");
            $log->execute([$username, $reason, $amount, $now]);

            $this->db->commit();

            Logger::info('Billing', 'admin_add_money', "Admin cong tien cho {$username}", [
                'amount' => $amount,
                'reason' => $reason,
            ]);
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thanh Cong', 'message' => 'Cong tien thanh cong'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Loi', 'message' => 'Co loi xay ra'];
        }

        $this->redirect(url('admin/users/edit/' . rawurlencode($username)));
    }

    public function subMoney($username)
    {
        $this->requireAdmin();

        $username = trim((string) $username);
        $amount = (int) $this->post('tien_tru');
        $reason = trim((string) $this->post('rs_tru'));
        $now = (string) time();

        if ($username === '' || $amount <= 0) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Loi', 'message' => 'So tien khong hop le'];
            $this->redirect(url('admin/users/edit/' . rawurlencode($username)));
        }

        try {
            $this->db->beginTransaction();

            $lock = $this->db->prepare("SELECT `id`, `money`, `tong_nap` FROM `users` WHERE `username` = ? LIMIT 1 FOR UPDATE");
            $lock->execute([$username]);
            $row = $lock->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                throw new RuntimeException('User not found');
            }

            if ((int) ($row['money'] ?? 0) < $amount) {
                throw new RuntimeException('Insufficient balance');
            }

            $up = $this->db->prepare("UPDATE `users` SET `money` = `money` - ?, `tong_nap` = GREATEST(`tong_nap` - ?, 0) WHERE `id` = ? LIMIT 1");
            $up->execute([$amount, $amount, (int) $row['id']]);

            $log = $this->db->prepare("
                INSERT INTO `history_nap_bank`
                (`trans_id`, `username`, `type`, `ctk`, `stk`, `thucnhan`, `status`, `time`)
                VALUES (NULL, ?, 'He thong', ?, NULL, ?, 'hoantat', ?)
            ");
            $log->execute([$username, $reason, -$amount, $now]);

            $this->db->commit();

            Logger::info('Billing', 'admin_sub_money', "Admin tru tien cua {$username}", [
                'amount' => $amount,
                'reason' => $reason,
            ]);
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thanh Cong', 'message' => 'Tru tien thanh cong'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Loi', 'message' => 'Co loi xay ra'];
        }

        $this->redirect(url('admin/users/edit/' . rawurlencode($username)));
    }

    public function delete()
    {
        $this->requireAdmin();
        $userId = (int) $this->post('user_id');
        $this->userModel->delete($userId);
        return $this->json(['success' => true, 'message' => 'Xoa thanh cong']);
    }

    public function banUser($username)
    {
        $this->requireAdmin();

        $username = trim((string) $username);
        $reason = trim((string) $this->post('reason', ''));
        if ($reason === '') {
            return $this->json(['success' => false, 'message' => 'Vui long nhap ly do ban.']);
        }

        try {
            $stmt = $this->db->prepare("UPDATE `users` SET `bannd` = 1, `ban_reason` = ? WHERE `username` = ? LIMIT 1");
            $stmt->execute([$reason, $username]);

            Logger::warning('Admin', 'ban_user', "Admin ban user: {$username}", [
                'username' => $username,
                'reason' => $reason,
            ]);
            return $this->json(['success' => true, 'message' => 'Da khoa tai khoan ' . $username]);
        } catch (Throwable $e) {
            return $this->json(['success' => false, 'message' => 'Co loi xay ra.']);
        }
    }

    public function banDevice($username)
    {
        $this->requireAdmin();

        $reason = trim((string) $this->post('reason', ''));
        if ($reason === '') {
            return $this->json(['success' => false, 'message' => 'Vui long nhap ly do khoa.']);
        }

        $user = $this->userModel->findByUsername((string) $username);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Nguoi dung khong ton tai.']);
        }
        if (empty($user['fingerprint'])) {
            return $this->json(['success' => false, 'message' => 'Chua co du lieu Fingerprint cua nguoi dung nay de khoa thiet bi.']);
        }

        $fpHash = (string) $user['fingerprint'];
        $adminName = (string) ($_SESSION['admin'] ?? 'Admin');

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO `banned_fingerprints` (`fingerprint_hash`, `reason`, `banned_by`)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE `reason` = VALUES(`reason`), `banned_by` = VALUES(`banned_by`)
            ");
            $stmt->execute([$fpHash, $reason, $adminName]);

            $banUser = $this->db->prepare("UPDATE `users` SET `bannd` = 1, `ban_reason` = ? WHERE `username` = ? LIMIT 1");
            $banUser->execute([$reason, (string) $username]);

            $this->db->commit();

            Logger::warning('Admin', 'ban_device', 'Khoa thiet bi Fingerprint: ' . $username, ['fingerprint' => $fpHash]);
            return $this->json(['success' => true, 'message' => 'Da khoa tai khoan va thiet bi cua ' . $username]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->json(['success' => false, 'message' => 'Co loi xay ra.']);
        }
    }

    public function unbanUser($username)
    {
        $this->requireAdmin();

        $username = trim((string) $username);

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE `users` SET `bannd` = 0, `ban_reason` = NULL WHERE `username` = ? LIMIT 1");
            $stmt->execute([$username]);

            $user = $this->userModel->findByUsername($username);
            if ($user && !empty($user['fingerprint'])) {
                $del = $this->db->prepare("DELETE FROM `banned_fingerprints` WHERE `fingerprint_hash` = ?");
                $del->execute([(string) $user['fingerprint']]);
            }

            $this->db->commit();

            Logger::info('Admin', 'unban_user', "Admin mo khoa user & thiet bi: {$username}", [
                'username' => $username,
            ]);
            return $this->json(['success' => true, 'message' => 'Da mo khoa tai khoan ' . $username]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->json(['success' => false, 'message' => 'Co loi xay ra.']);
        }
    }
}

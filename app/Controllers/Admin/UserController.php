<?php

/**
 * Admin User Controller
 * Handles admin user management.
 */
class UserController extends Controller
{
    private $authService;
    private $userModel;
    private $fingerprintModel;
    private $banService;
    private $timeService;
    private ?BalanceChangeService $balanceChangeService = null;
    private array $schemaCache = [];

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userModel = new User();
        $this->fingerprintModel = new UserFingerprint();
        $this->banService = new BanService();
        $this->timeService = class_exists('TimeService') ? TimeService::instance() : null;
        $this->balanceChangeService = class_exists('BalanceChangeService') ? new BalanceChangeService() : null;
        $this->ensureHistorySourceChannelSchema();
    }

    /**
     * Check admin access.
     */
    private function requireAdmin()
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();

        if (!isset($user['level']) || (int) $user['level'] !== 9) {
            http_response_code(403);
            die('Truy cập bị từ chối - Chỉ dành cho quản trị viên');
        }
    }

    /**
     * Show user list.
     */
    public function index()
    {
        $this->requireAdmin();
        global $chungapi;

        $users = $this->userModel->all();
        $fingerprintMap = $this->fingerprintModel->getLatestMapByUserIds(array_column($users, 'id'));

        foreach ($users as &$userRow) {
            $latestFingerprint = $fingerprintMap[(int) ($userRow['id'] ?? 0)] ?? null;
            if (is_array($latestFingerprint)) {
                $userRow['fingerprint'] = (string) ($latestFingerprint['fingerprint_hash'] ?? '');
                $userRow['ip_address'] = (string) ($latestFingerprint['ip_address'] ?? ($userRow['ip_address'] ?? ''));
                $userRow['user_agent'] = (string) ($latestFingerprint['user_agent'] ?? ($userRow['user_agent'] ?? ''));
            }

            $userRow = $this->attachListTimeMeta($userRow, ['created_at', 'time']);
        }
        unset($userRow);

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

    /**
     * Show edit user form.
     */
    public function edit($username)
    {
        $this->requireAdmin();
        global $chungapi, $connection;

        $safeUsername = $connection->real_escape_string((string) $username);
        $user = $connection->query("SELECT * FROM `users` WHERE `username` = '{$safeUsername}' LIMIT 1")->fetch_assoc();

        if (!$user) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Thành viên không tồn tại'];
            $this->redirect(url('admin/users'));
        }

        $fingerprints = $this->fingerprintModel->getByUserId($user['id'], 20);
        $latestFingerprint = $fingerprints[0] ?? null;
        if (is_array($latestFingerprint)) {
            $user['fingerprint'] = (string) ($latestFingerprint['fingerprint_hash'] ?? '');
            $user['ip_address'] = (string) ($latestFingerprint['ip_address'] ?? ($user['ip_address'] ?? ''));
            $user['user_agent'] = (string) ($latestFingerprint['user_agent'] ?? ($user['user_agent'] ?? ''));
        }

        $this->view('admin/users/edit', [
            'chungapi' => $chungapi,
            'toz_user' => $user,
            'fingerprints' => $fingerprints,
        ]);
    }

    /**
     * Update user details.
     */
    public function update($username)
    {
        $this->requireAdmin();
        global $connection;

        $email = (string) $this->post('email');
        $level = (string) $this->post('level');
        $bannd = (string) $this->post('bannd');
        $newUsername = (string) $this->post('username');

        $safeOldUsername = $connection->real_escape_string((string) $username);
        $safeNewUsername = $connection->real_escape_string($newUsername);
        $safeEmail = $connection->real_escape_string($email);
        $safeLevel = $connection->real_escape_string($level);
        $safeBannd = $connection->real_escape_string($bannd);

        $sql = "UPDATE `users` SET
                `username` = '{$safeNewUsername}',
                `email` = '{$safeEmail}',
                `bannd` = '{$safeBannd}',
                `level` = '{$safeLevel}'
                WHERE `username` = '{$safeOldUsername}'";

        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành công', 'message' => 'Cập nhật thành công'];
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Có lỗi xảy ra: ' . $connection->error];
        }

        $this->redirect(url('admin/users/edit/' . $newUsername));
    }

    /**
     * Add money to user.
     */
    public function addMoney($username)
    {
        $this->requireAdmin();
        global $connection;

        $safeUsername = $connection->real_escape_string((string) $username);
        $amountRaw = (string) $this->post('tien_cong');
        $amount = $this->normalizeMoneyAmount($amountRaw);
        $reason = trim((string) $this->post('rs_cong'));
        $safeReason = $connection->real_escape_string($reason);
        if ($amount <= 0) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Số tiền cộng không hợp lệ'];
            return $this->redirect(url('admin/users/edit/' . $username));
        }

        $userRes = $connection->query("SELECT `id`, `money` FROM `users` WHERE `username` = '{$safeUsername}' LIMIT 1");
        $userRow = $userRes ? $userRes->fetch_assoc() : null;
        if (!$userRow) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Không tìm thấy tài khoản'];
            return $this->redirect(url('admin/users'));
        }

        $userId = (int) ($userRow['id'] ?? 0);
        $beforeBalance = (int) ($userRow['money'] ?? 0);
        $afterBalance = $beforeBalance + $amount;
        $now = $this->timeService ? $this->timeService->nowTs() : time();

        $create = $connection->query("UPDATE `users` SET `money` = `money` + {$amount}, `tong_nap` = `tong_nap` + {$amount} WHERE `username` = '{$safeUsername}'");

        if ($create) {
            $connection->query("INSERT INTO `history_nap_bank` SET
                `trans_id` = NULL,
                `username` = '{$safeUsername}',
                `type` = 'Hệ thống',
                `ctk` = '{$safeReason}',
                `stk` = NULL,
                `thucnhan` = '{$amount}',
                `status` = 'hoantat',
                source_channel = '0',
                `time` = '{$now}'");

            if ($this->balanceChangeService) {
                $this->balanceChangeService->record(
                    $userId,
                    (string) $username,
                    $beforeBalance,
                    $amount,
                    $afterBalance,
                    'Admin cộng tiền' . ($reason !== '' ? ': ' . $reason : ''),
                    SourceChannelHelper::WEB
                );
            }

            Logger::info('Billing', 'admin_add_money', "Admin cộng tiền cho {$username}", ['amount' => $amount, 'reason' => $reason]);
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cộng tiền thành công'];
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Có lỗi xảy ra'];
        }

        $this->redirect(url('admin/users/edit/' . $username));
    }

    /**
     * Subtract money from user.
     */
    public function subMoney($username)
    {
        $this->requireAdmin();
        global $connection;

        $safeUsername = $connection->real_escape_string((string) $username);
        $amountRaw = (string) $this->post('tien_tru');
        $amount = $this->normalizeMoneyAmount($amountRaw);
        $reason = trim((string) $this->post('rs_tru'));
        $safeReason = $connection->real_escape_string($reason);
        if ($amount <= 0) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Số tiền trừ không hợp lệ'];
            return $this->redirect(url('admin/users/edit/' . $username));
        }

        $userRes = $connection->query("SELECT `id`, `money` FROM `users` WHERE `username` = '{$safeUsername}' LIMIT 1");
        $userRow = $userRes ? $userRes->fetch_assoc() : null;
        if (!$userRow) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Không tìm thấy tài khoản'];
            return $this->redirect(url('admin/users'));
        }

        $userId = (int) ($userRow['id'] ?? 0);
        $beforeBalance = (int) ($userRow['money'] ?? 0);
        $afterBalance = $beforeBalance - $amount;
        $now = $this->timeService ? $this->timeService->nowTs() : time();

        $create = $connection->query("UPDATE `users` SET `money` = `money` - {$amount}, `tong_nap` = `tong_nap` - {$amount} WHERE `username` = '{$safeUsername}'");

        if ($create) {
            $connection->query("INSERT INTO `history_nap_bank` SET
                `trans_id` = NULL,
                `username` = '{$safeUsername}',
                `type` = 'Hệ thống',
                `ctk` = '{$safeReason}',
                `stk` = NULL,
                `thucnhan` = '-{$amount}',
                `status` = 'hoantat',
                source_channel = '0',
                `time` = '{$now}'");

            if ($this->balanceChangeService) {
                $this->balanceChangeService->record(
                    $userId,
                    (string) $username,
                    $beforeBalance,
                    -$amount,
                    $afterBalance,
                    'Admin trừ tiền' . ($reason !== '' ? ': ' . $reason : ''),
                    SourceChannelHelper::WEB
                );
            }

            Logger::info('Billing', 'admin_sub_money', "Admin trừ tiền của {$username}", ['amount' => $amount, 'reason' => $reason]);
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Trừ tiền thành công'];
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Có lỗi xảy ra'];
        }

        $this->redirect(url('admin/users/edit/' . $username));
    }

    /**
     * Delete user.
     */
    public function delete()
    {
        $this->requireAdmin();

        $userId = $this->post('user_id');
        $this->userModel->delete($userId);

        return $this->json(['success' => true, 'message' => 'Xóa thành công']);
    }

    /**
     * Ban user with reason.
     */
    public function banUser($username)
    {
        $this->requireAdmin();

        $reason = trim((string) $this->post('reason', ''));
        if ($reason === '') {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập lý do khóa.']);
        }

        $user = $this->userModel->findByUsername($username);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Người dùng không tồn tại.']);
        }

        $durationKey = (string) $this->post('duration', '');
        $duration = $this->banService->normalizeDuration($durationKey);
        $adminName = (string) ($_SESSION['admin'] ?? 'Admin');
        $result = $this->banService->applyAccountBan($user, $reason, $durationKey, $adminName);

        if ($result) {
            Logger::warning('Admin', 'ban_user', "Admin ban user: {$username}", [
                'username' => $username,
                'reason' => $reason,
                'duration' => $duration['label'],
            ]);
            return $this->json([
                'success' => true,
                'message' => 'Đã khóa tài khoản ' . $username . ' - ' . $duration['label'],
            ]);
        }

        return $this->json(['success' => false, 'message' => 'Có lỗi xảy ra.']);
    }

    /**
     * Ban user device by latest known fingerprint.
     */
    public function banDevice($username)
    {
        $this->requireAdmin();

        $reason = trim((string) $this->post('reason', ''));
        if ($reason === '') {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập lý do khóa.']);
        }

        $user = $this->userModel->findByUsername($username);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Người dùng không tồn tại.']);
        }

        $latestFingerprint = $this->fingerprintModel->getLatestByUserId((int) ($user['id'] ?? 0));
        if (!$latestFingerprint || empty($latestFingerprint['fingerprint_hash'])) {
            return $this->json(['success' => false, 'message' => 'Chưa có dữ liệu Fingerprint của người dùng này để khóa thiết bị.']);
        }

        $durationKey = (string) $this->post('duration', '');
        $duration = $this->banService->normalizeDuration($durationKey);
        $adminName = (string) ($_SESSION['admin'] ?? 'Admin');
        $fpHash = (string) $latestFingerprint['fingerprint_hash'];
        $deviceBanResult = $this->banService->applyDeviceBan($user, $fpHash, $reason, $durationKey, $adminName);
        $accountBanResult = $this->banService->applyAccountBan($user, $reason, $durationKey, $adminName);

        if ($deviceBanResult && $accountBanResult) {
            Logger::warning('Admin', 'ban_device', 'Khóa thiết bị Fingerprint: ' . $username, [
                'fingerprint' => $fpHash,
                'reason' => $reason,
                'duration' => $duration['label'],
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Đã khóa tài khoản và thiết bị của ' . $username . ' - ' . $duration['label'],
            ]);
        }

        return $this->json(['success' => false, 'message' => 'Không thể khóa thiết bị lúc này.']);
    }

    /**
     * Unban user and latest known fingerprint if present.
     */
    public function unbanUser($username)
    {
        $this->requireAdmin();
        $adminName = (string) ($_SESSION['admin'] ?? 'Admin');
        $result = $this->banService->releaseAccountBan((string) $username, $adminName);

        $user = $this->userModel->findByUsername($username);
        $latestFingerprint = $user ? $this->fingerprintModel->getLatestByUserId((int) ($user['id'] ?? 0)) : null;
        if ($latestFingerprint && !empty($latestFingerprint['fingerprint_hash'])) {
            $this->banService->releaseDeviceBan((string) $latestFingerprint['fingerprint_hash'], $adminName);
        }

        if ($result) {
            Logger::info('Admin', 'unban_user', "Admin mở khóa user và thiết bị: {$username}", [
                'username' => $username,
            ]);
            return $this->json(['success' => true, 'message' => 'Đã mở khóa tài khoản ' . $username]);
        }

        return $this->json(['success' => false, 'message' => 'Có lỗi xảy ra.']);
    }

    private function normalizeMoneyAmount(string $raw): int
    {
        $clean = preg_replace('/\D+/', '', $raw) ?? '';
        return (int) $clean;
    }

    private function ensureHistorySourceChannelSchema(): void
    {
        $pdo = $this->userModel->getConnection();
        try {
            if (!$this->hasColumn($pdo, 'history_nap_bank', 'source_channel')) {
                $pdo->exec("ALTER TABLE `history_nap_bank` ADD COLUMN `source_channel` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`");
            }
        } catch (Throwable $e) {
            // ignore if ALTER is restricted
        }

        try {
            $pdo->exec("ALTER TABLE `history_nap_bank` ADD KEY `idx_hnb_source_created` (`source_channel`, `created_at`)");
        } catch (Throwable $e) {
            // ignore if key exists or ALTER is restricted
        }

        unset($this->schemaCache['history_nap_bank.source_channel']);
    }

    private function hasColumn(PDO $pdo, string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->schemaCache)) {
            return $this->schemaCache[$cacheKey];
        }

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = :table_name
              AND column_name = :column_name
        ");
        $stmt->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);
        $exists = (int) $stmt->fetchColumn() > 0;
        $this->schemaCache[$cacheKey] = $exists;
        return $exists;
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



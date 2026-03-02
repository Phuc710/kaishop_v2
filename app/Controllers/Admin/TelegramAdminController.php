<?php

/**
 * TelegramAdminController
 * Admin management pages for Telegram Bot ecosystem
 */
class TelegramAdminController extends Controller
{
    private TelegramService $telegram;
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->telegram = new TelegramService();
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

    // ─── Dashboard ────────────────────────────────────────────

    public function index(): void
    {
        $this->requireAdmin();

        $botInfo = [];
        $webhookInfo = [];

        try {
            $botInfo = $this->telegram->getMe() ?: [];
        } catch (\Throwable $e) {
            $botInfo = [];
        }

        // Auto-sync bot username to setting
        if (!empty($botInfo['ok']) && !empty($botInfo['result']['username'])) {
            $botUsername = $botInfo['result']['username'];
            if ($botUsername !== get_setting('telegram_bot_user')) {
                try {
                    $db = (new UserTelegramLink())->getConnection();
                    $stmt = $db->prepare("UPDATE `setting` SET `telegram_bot_user` = ? ORDER BY `id` ASC LIMIT 1");
                    $stmt->execute([$botUsername]);
                    Config::clearSiteConfigCache();
                } catch (\Throwable $e) {
                    // Ignore, non-critical
                }
            }
        }

        try {
            $webhookInfo = $this->telegram->getWebhookInfo() ?: [];
        } catch (\Throwable $e) {
            $webhookInfo = [];
        }

        $outboxModel = new TelegramOutbox();
        $outboxStats = $outboxModel->getStats();
        $recentOutbox = $outboxModel->fetchRecent(10);

        $linkModel = new UserTelegramLink();
        $db = $linkModel->getConnection();

        $totalLinks = (int) $db->query("SELECT COUNT(*) FROM `user_telegram_links`")->fetchColumn();

        $now = TimeService::instance()->nowSql();
        $todayStart = substr($now, 0, 10) . ' 00:00:00';
        $stmtNew = $db->prepare("SELECT COUNT(*) FROM `user_telegram_links` WHERE `linked_at` >= ?");
        $stmtNew->execute([$todayStart]);
        $newLinksToday = (int) $stmtNew->fetchColumn();

        $totalUsers = (int) $db->query("SELECT COUNT(*) FROM `users`")->fetchColumn();

        // --- Chart Data ---
        // Reuse $db from $linkModel->getConnection();

        // 1. Linked users last 7 days
        $linkedUsersChart = $db->query("
            SELECT DATE(linked_at) as date, COUNT(*) as count 
            FROM user_telegram_links 
            WHERE linked_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(linked_at)
            ORDER BY date ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 2. Order Sources last 7 days (Web vs Telegram)
        $orderStatsChart = $db->query("
            SELECT DATE(created_at) as date,
                   SUM(CASE WHEN source_channel = 1 THEN 1 ELSE 0 END) as tele_orders,
                   SUM(CASE WHEN source_channel = 0 THEN 1 ELSE 0 END) as web_orders
            FROM orders
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/telegram/index', [
            'chungapi' => Config::getSiteConfig(),
            'botInfo' => $botInfo,
            'webhookInfo' => $webhookInfo,
            'outboxStats' => $outboxStats,
            'recentOutbox' => $recentOutbox,
            'totalLinks' => $totalLinks,
            'newLinksToday' => $newLinksToday,
            'totalUsers' => $totalUsers,
            'linkedUsersChart' => $linkedUsersChart,
            'orderStatsChart' => $orderStatsChart,
        ]);
    }

    // ─── Settings ─────────────────────────────────────────────

    public function settings(): void
    {
        $this->requireAdmin();

        $siteConfig = Config::getSiteConfig();

        // These are now cached in TelegramService (TTL 5m and 1m)
        $botInfo = $this->telegram->getMe();
        $webhookInfo = $this->telegram->getWebhookInfo();

        $channels = (new TelegramNotificationChannel())->fetchAll();
        $outboxModel = new TelegramOutbox();
        $outboxStats = $outboxModel->getStats();

        $this->view('admin/telegram/settings', [
            'chungapi' => $siteConfig,
            'siteConfig' => $siteConfig,
            'botInfo' => $botInfo,
            'webhookInfo' => $webhookInfo,
            'channels' => $channels,
            'outboxStats' => $outboxStats,
        ]);
    }

    public function updateSettings(): void
    {
        $this->requireAdmin();

        $fields = [
            'telegram_bot_token',
            'telegram_chat_id',
            'telegram_webhook_secret',
            'telegram_admin_ids',
            'telegram_order_cooldown',
        ];

        $db = (new UserTelegramLink())->getConnection();
        foreach ($fields as $field) {
            $value = trim((string) $this->post($field, ''));
            if ($value !== '') {
                $stmt = $db->prepare("UPDATE `setting` SET `{$field}` = ? ORDER BY `id` ASC LIMIT 1");
                $stmt->execute([$value]);
            }
        }
        Config::clearSiteConfigCache();

        $this->json(['success' => true, 'message' => 'Đã cập nhật cấu hình Telegram']);
    }

    // ─── Webhook Actions ──────────────────────────────────────

    public function setWebhookAction(): void
    {
        $this->requireAdmin();
        $baseUrl = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');
        $webhookUrl = $baseUrl . '/api/telegram/webhook';
        $secret = get_setting('telegram_webhook_secret', '');

        $result = $this->telegram->setWebhook($webhookUrl, $secret ?: null);

        $this->json([
            'success' => !empty($result['ok']),
            'message' => !empty($result['ok']) ? 'Đã thiết lập Webhook thành công' : ($result['description'] ?? 'Lỗi'),
        ]);
    }

    public function deleteWebhookAction(): void
    {
        $this->requireAdmin();
        $result = $this->telegram->deleteWebhook();
        $this->json([
            'success' => !empty($result['ok']),
            'message' => !empty($result['ok']) ? 'Đã xóa Webhook' : ($result['description'] ?? 'Lỗi'),
        ]);
    }

    public function testNotification(): void
    {
        $this->requireAdmin();
        $chatId = get_setting('telegram_chat_id', '');
        if ($chatId === '') {
            $this->json(['success' => false, 'message' => 'Chưa cấu hình Chat ID']);
            return;
        }

        $now = TimeService::instance()->nowSql();
        $msg = "✅ <b>Test thành công!</b>\nTin nhắn từ KaiShop Admin Panel.\n🕐 {$now}";
        $success = $this->telegram->sendTo($chatId, $msg);

        $this->json([
            'success' => (bool) $success,
            'message' => $success ? 'Đã gửi tin nhắn test' : 'Gửi thất bại, kiểm tra Token và Chat ID',
        ]);
    }

    public function syncBotAction(): void
    {
        $this->requireAdmin();
        $botLogic = new TelegramBotService($this->telegram);
        $result = $botLogic->initializeBot();

        $this->json([
            'success' => !empty($result['ok']),
            'message' => !empty($result['ok']) ? 'Đã đồng bộ Menu & Lệnh Bot thành công' : ($result['description'] ?? 'Lỗi đồng bộ'),
        ]);
    }

    // ─── Notification Channels ──────────────────────────────

    public function notificationChannels(): void
    {
        $this->requireAdmin();
        $channels = (new TelegramNotificationChannel())->fetchAll();
        $this->json(['success' => true, 'channels' => $channels]);
    }

    public function addChannelAction(): void
    {
        $this->requireAdmin();
        $chatId = trim((string) $this->post('chat_id', ''));
        $label = trim((string) $this->post('label', ''));

        if ($chatId === '') {
            $this->json(['success' => false, 'message' => 'Chat ID/Channel name không được để trống']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $success = $model->add($chatId, $label ?: null);

        $this->json([
            'success' => $success,
            'message' => $success ? 'Đã thêm kênh nhận thông báo' : 'Kênh này đã tồn tại hoặc có lỗi',
        ]);
    }

    public function toggleChannelAction(): void
    {
        $this->requireAdmin();
        $id = (int) $this->post('id', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID không hợp lệ']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $success = $model->toggle($id);
        $this->json(['success' => $success]);
    }

    public function deleteChannelAction(): void
    {
        $this->requireAdmin();
        $id = (int) $this->post('id', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID không hợp lệ']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $success = $model->delete($id);
        $this->json(['success' => $success]);
    }

    // ─── User Links ───────────────────────────────────────────

    public function links(): void
    {
        $this->requireAdmin();

        $linkModel = new UserTelegramLink();
        $db = $linkModel->getConnection();

        $keyword = trim((string) $this->get('q', ''));
        $where = '';
        $params = [];

        if ($keyword !== '') {
            $where = "WHERE l.`telegram_id` LIKE ? OR l.`telegram_username` LIKE ? OR u.`username` LIKE ? OR u.`email` LIKE ?";
            $likeQ = "%{$keyword}%";
            $params = [$likeQ, $likeQ, $likeQ, $likeQ];
        }

        $sql = "SELECT l.*, u.username AS web_username, u.email AS web_email
                FROM `user_telegram_links` l
                LEFT JOIN `users` u ON u.id = l.user_id
                {$where}
                ORDER BY l.id DESC LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $links = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/telegram/links', [
            'links' => $links,
            'keyword' => $keyword,
        ]);
    }

    public function unlinkAction(): void
    {
        $this->requireAdmin();
        $userId = (int) $this->post('user_id', 0);
        if ($userId <= 0) {
            $this->json(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }

        $linkModel = new UserTelegramLink();
        $success = $linkModel->unlinkUser($userId);

        $this->json([
            'success' => $success,
            'message' => $success ? 'Đã hủy liên kết' : 'Không thể hủy liên kết',
        ]);
    }

    // ─── Outbox ───────────────────────────────────────────────

    public function outbox(): void
    {
        $this->requireAdmin();

        $outboxModel = new TelegramOutbox();
        $db = $outboxModel->getConnection();

        $statusFilter = trim((string) $this->get('status', ''));
        $validStatuses = ['pending', 'sent', 'fail'];
        $where = '';
        $params = [];

        if (in_array($statusFilter, $validStatuses, true)) {
            $where = "WHERE `status` = ?";
            $params = [$statusFilter];
        }

        $sql = "SELECT * FROM `telegram_outbox` {$where} ORDER BY `id` DESC LIMIT 200";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = $outboxModel->getStats();

        // Variables for filters array, as per instruction
        $status = $statusFilter; // Map existing statusFilter to $status
        $search = trim((string) $this->get('search', '')); // Assume 'search' can be passed via GET

        $this->view('admin/telegram/outbox', [
            'chungapi' => Config::getSiteConfig(),
            'stats' => $stats,
            'messages' => $messages,
            'filters' => [
                'status' => $status,
                'search' => $search
            ]
        ]);
    }

    public function outboxRetry(): void
    {
        $this->requireAdmin();
        $ids = $this->post('ids', '');

        $outboxModel = new TelegramOutbox();
        $db = $outboxModel->getConnection();

        if ($ids === 'all_fails') {
            $db->exec("UPDATE `telegram_outbox` SET `status` = 'pending', `try_count` = 0 WHERE `status` = 'fail'");
        } else {
            $idArr = array_filter(array_map('intval', explode(',', (string) $ids)));
            if (!empty($idArr)) {
                $placeholders = implode(',', array_fill(0, count($idArr), '?'));
                $stmt = $db->prepare("UPDATE `telegram_outbox` SET `status` = 'pending', `try_count` = 0 WHERE `id` IN ({$placeholders})");
                $stmt->execute($idArr);
            }
        }

        $this->json(['success' => true, 'message' => 'Đã đặt lại để gửi lại']);
    }

    public function outboxDelete(): void
    {
        $this->requireAdmin();
        $ids = array_filter(array_map('intval', explode(',', (string) $this->post('ids', ''))));

        if (!empty($ids)) {
            $outboxModel = new TelegramOutbox();
            $db = $outboxModel->getConnection();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("DELETE FROM `telegram_outbox` WHERE `id` IN ({$placeholders})");
            $stmt->execute($ids);
        }

        $this->json(['success' => true, 'message' => 'Đã xóa']);
    }

    // ─── Broadcast ────────────────────────────────────────────

    public function broadcast(): void
    {
        $this->requireAdmin();
        $this->view('admin/telegram/broadcast', [
            'chungapi' => Config::getSiteConfig(),
        ]);
    }

    public function broadcastAction(): void
    {
        $this->requireAdmin();
        $message = trim((string) $this->post('message', ''));
        if ($message === '') {
            $this->json(['success' => false, 'message' => 'Nội dung tin nhắn không được để trống']);
            return;
        }

        $userModel = new TelegramUser();
        $tids = $userModel->getAllActive();

        if (empty($tids)) {
            $this->json(['success' => false, 'message' => 'Chưa có người dùng nào tương tác với Bot']);
            return;
        }

        $outbox = new TelegramOutbox();
        $count = 0;
        foreach ($tids as $tid) {
            $outbox->enqueue((int) $tid, $message);
            $count++;
        }

        $this->json(['success' => true, 'message' => "Đã thêm {$count} tin nhắn vào hàng đợi gửi (Outbox)"]);
    }

    // ─── Logs ─────────────────────────────────────────────────

    public function logs(): void
    {
        $this->requireAdmin();

        $db = (new SystemLog())->getConnection();
        $sql = "SELECT * FROM `system_logs` WHERE `module` = 'telegram' ORDER BY `id` DESC LIMIT 500";
        $logs = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/telegram/logs', [
            'logs' => $logs,
        ]);
    }

    // ─── Orders from Bot ──────────────────────────────────────

    public function orders(): void
    {
        $this->requireAdmin();

        $orderModel = new Order();
        $db = $orderModel->getConnection();

        // Dynamic column detection
        $filterMode = 'none';
        try {
            $check = $db->query("SHOW COLUMNS FROM `orders` LIKE 'source_channel'")->fetch();
            if ($check) {
                $filterMode = 'all';
            }
        } catch (\Throwable $e) {
        }

        $orders = [];
        if ($filterMode === 'all') {
            try {
                $sql = "SELECT o.*, u.username AS buyer_username
                        FROM `orders` o
                        LEFT JOIN `users` u ON u.id = o.user_id
                        WHERE o.`source_channel` = 1 OR o.`source` = 'telegram'
                        ORDER BY o.id DESC LIMIT 500";
                $orders = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {
                $filterMode = 'none';
            }
        }

        $this->view('admin/telegram/orders', [
            'chungapi' => Config::getSiteConfig(),
            'orders' => $orders,
            'filterMode' => $filterMode
        ]);
    }
}

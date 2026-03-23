<?php

/**
 * TelegramAdminController
 * Admin management pages for Telegram Bot ecosystem
 */
class TelegramAdminController extends Controller
{
    private $telegram;
    private $authService;

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

    // ——— Settings ———————————————————————————————————————————

    public function settings()
    {
        $this->requireAdmin();
        if (
            $this->hasSensitiveQueryParams([
                'telegram_bot_token',
                'telegram_chat_id',
                'telegram_admin_ids',
                'telegram_order_cooldown',
                'telegram_webhook_secret',
            ])
        ) {
            $this->redirect(url('admin/telegram/settings'));
        }

        $siteConfig = Config::getSiteConfig();
        $lastCronRun = $siteConfig['last_cron_run'] ?? null;

        // 1. Core Bot & Webhook Info
        $botInfo = $this->telegram->getMe();
        $webhookInfo = $this->telegram->getWebhookInfo();

        // 2. Models
        $channelModel = new TelegramNotificationChannel();
        $outboxModel = new TelegramOutbox();
        $linkModel = new UserTelegramLink();
        $userModel = new User();
        $orderModel = new Order();

        // 3. Fetch Data
        $channels = $channelModel->fetchAll();
        $outboxStats = $outboxModel->getStats();
        $totalLinks = $linkModel->getTotalCount();
        $totalUsers = $userModel->count();

        // 4. Order Stats (Telegram Bot specifically: source_channel = 1)
        $db = $orderModel->getConnection();
        $orderStats = [
            'total' => (int) $db->query("SELECT COUNT(*) FROM `orders` WHERE `source_channel` = 1")->fetchColumn(),
            'pending' => (int) $db->query("SELECT COUNT(*) FROM `orders` WHERE `source_channel` = 1 AND `status` = 'pending'")->fetchColumn(),
            'completed' => (int) $db->query("SELECT COUNT(*) FROM `orders` WHERE `source_channel` = 1 AND `status` = 'completed'")->fetchColumn(),
            'cancelled' => (int) $db->query("SELECT COUNT(*) FROM `orders` WHERE `source_channel` = 1 AND `status` = 'cancelled'")->fetchColumn(),
        ];

        $this->view('admin/telegram/settings', [
            'chungapi' => $siteConfig,
            'siteConfig' => $siteConfig,
            'botInfo' => $botInfo,
            'webhookInfo' => $webhookInfo,
            'channels' => $channels,
            'outboxStats' => $outboxStats,
            'totalLinks' => $totalLinks,
            'totalUsers' => $totalUsers,
            'orderStats' => $orderStats,
            'lastCronRun' => $lastCronRun,
        ]);
    }

    public function updateSettings()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);

        $fields = [
            'telegram_bot_token',
            'telegram_chat_id',
            'telegram_webhook_secret',
            'telegram_webhook_path',
            'telegram_admin_ids',
            'telegram_main_channel_id',
            'telegram_support_channel',
            'telegram_support_admin',
            'telegram_maintenance_enabled',
            'telegram_maintenance_message',
        ];

        $db = (new UserTelegramLink())->getConnection();

        // Backward-compatible schema self-heal for older databases
        $this->ensureSettingColumn(
            $db,
            'telegram_webhook_path',
            "VARCHAR(120) DEFAULT 'bottelekaishop_default' AFTER `telegram_webhook_secret`"
        );
        $this->ensureSettingColumn(
            $db,
            'telegram_maintenance_enabled',
            "TINYINT(1) NOT NULL DEFAULT 0 AFTER `telegram_order_cooldown`"
        );
        $this->ensureSettingColumn(
            $db,
            'telegram_main_channel_id',
            "VARCHAR(120) NULL AFTER `telegram_chat_id`"
        );
        $this->ensureSettingColumn(
            $db,
            'telegram_maintenance_message',
            "TEXT NULL AFTER `telegram_maintenance_enabled`
        "
        );
        $this->ensureSettingColumn(
            $db,
            'telegram_support_channel',
            "VARCHAR(255) DEFAULT 'https://t.me/' AFTER `telegram_main_channel_id`"
        );
        $this->ensureSettingColumn(
            $db,
            'telegram_support_admin',
            "VARCHAR(120) DEFAULT '@' AFTER `telegram_support_channel`"
        );

        foreach ($fields as $field) {
            $value = $this->post($field, null);

            // Check existence for dynamic path or maintenance flags
            if ($field === 'telegram_webhook_path' && !$this->hasSettingColumn($db, $field))
                continue;
            if ($field === 'telegram_maintenance_enabled' && !$this->hasSettingColumn($db, $field))
                continue;
            if ($field === 'telegram_maintenance_message' && !$this->hasSettingColumn($db, $field))
                continue;

            if ($value !== null) {
                $value = trim((string) $value);

                // Specific validation for webhook path
                if ($field === 'telegram_webhook_path' && $value !== '') {
                    $normalized = trim($value, '/');
                    if ($normalized === '' || !preg_match('/^[A-Za-z0-9_\\-\\/]{3,120}$/', $normalized)) {
                        $this->json(['success' => false, 'message' => 'Đường dẫn Webhook không hợp lệ.']);
                        return;
                    }
                    $value = $normalized;
                }

                $stmt = $db->prepare("UPDATE `setting` SET `{$field}` = ? ORDER BY `id` ASC LIMIT 1");
                $stmt->execute([$value]);
            }
        }
        Config::clearSiteConfigCache();

        $this->json(['success' => true, 'message' => 'Đã cập nhật cấu hình Telegram']);
    }

    // ——— Webhook Actions ————————————————————————————————————

    public function setWebhookAction()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);
        $db = (new UserTelegramLink())->getConnection();

        // 1. Optionally save new path first
        $newPath = trim((string) $this->post('path', ''));
        if ($newPath !== '') {
            $normalized = trim($newPath, '/');
            if ($normalized !== '' && preg_match('/^[A-Za-z0-9_\-\/]{3,120}$/', $normalized)) {
                $stmt = $db->prepare("UPDATE `setting` SET `telegram_webhook_path` = ? ORDER BY `id` ASC LIMIT 1");
                $stmt->execute([$normalized]);
                Config::clearSiteConfigCache();
            } else {
                $this->json(['success' => false, 'message' => 'Đường dẫn không hợp lệ.']);
                return;
            }
        }

        // 2. Perform Registration
        $baseUrl = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');
        $path = get_setting('telegram_webhook_path', 'bottelekaishop_default');
        $webhookUrl = $baseUrl . '/api/' . ltrim($path, '/');
        $secret = TelegramConfig::webhookSecret();

        $result = $this->telegram->setWebhook($webhookUrl, $secret ?: null);

        $this->json([
            'success' => !empty($result['ok']),
            'message' => !empty($result['ok']) ? 'Đã lưu và kích hoạt Webhook thành công!' : ($result['description'] ?? 'Lỗi từ Telegram API'),
        ]);
    }

    /**
     * KÍCH HOẠT WEBHOOK — đăng ký ngay path hiện có trong DB, không cần nhập path mới.
     */
    public function activateWebhookAction()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);

        $baseUrl = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');
        $path = trim((string) get_setting('telegram_webhook_path', ''));

        if ($path === '') {
            $this->json(['success' => false, 'message' => 'Chưa có Webhook Path trong cấu hình. Hãy nhập path trước.']);
            return;
        }

        $webhookUrl = $baseUrl . '/api/' . ltrim($path, '/');
        $secret = TelegramConfig::webhookSecret();

        $result = $this->telegram->setWebhook($webhookUrl, $secret ?: null);

        $this->json([
            'success' => !empty($result['ok']),
            'message' => !empty($result['ok'])
                ? '⚡ Kích hoạt Webhook thành công! URL: ' . $webhookUrl
                : ('Lỗi: ' . ($result['description'] ?? 'Telegram API không phản hồi')),
        ]);
    }

    public function deleteWebhookAction()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);
        $result = $this->telegram->deleteWebhook();
        $this->json([
            'success' => !empty($result['ok']),
            'message' => !empty($result['ok']) ? 'Đã tạm dừng hoạt động Webhook' : ($result['description'] ?? 'Lỗi'),
        ]);
    }

    public function testNotification()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);
        $chatId = (string) TelegramConfig::primaryAdminId();
        if ($chatId === '' || $chatId === '0') {
            $this->json(['success' => false, 'message' => 'Chưa cấu hình Chat ID']);
            return;
        }

        $now = TimeService::instance()->nowSql();
        $msg = "✅ <b>Test kết nối thành công!</b>\nTin nhắn được gửi từ KaiShop Admin.\n🕒 Thời gian: {$now}";
        $success = $this->telegram->sendTo($chatId, $msg);

        $this->json([
            'success' => (bool) $success,
            'message' => $success ? 'Đã gửi tin nhắn test thành công' : 'Gửi thất bại, hãy kiểm tra lại Token/Chat ID',
        ]);
    }

    public function syncBotAction()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);
        $botLogic = new TelegramBotService($this->telegram);
        $result = $botLogic->initializeBot();
        $this->json([
            'success' => !empty($result['ok']),
            'message' => !empty($result['ok']) ? 'Đã đồng bộ Menu & Lệnh Bot thành công' : ($result['description'] ?? 'Lỗi đồng bộ'),
        ]);
    }

    public function terminal()
    {
        $this->requireAdmin();
        $allowed = ['all', 'today', 'week', 'month'];
        $period = $this->get('period', 'all');
        if (!in_array($period, $allowed, true)) {
            $period = 'all';
        }

        $logModel = new TelegramLog();
        $logs = $logModel->fetchFiltered($period);
        $maxId = $logModel->maxIdFiltered($period);

        $siteConfig = Config::getSiteConfig();
        $lastCronRun = $siteConfig['last_cron_run'] ?? null;

        $this->view('admin/telegram/terminal', [
            'logs' => $logs,
            'maxId' => $maxId,
            'period' => $period,
            'lastCronRun' => $lastCronRun,
        ]);
    }

    public function terminalPoll()
    {
        $this->requireAdmin();
        $allowed = ['all', 'today', 'week', 'month'];
        $period = $this->get('period', 'all');
        if (!in_array($period, $allowed, true)) {
            $period = 'all';
        }
        $afterId = max(0, (int) $this->get('after', 0));
        $logModel = new TelegramLog();
        $rows = $logModel->fetchAfterFiltered($afterId, $period, 100);
        $newMax = $afterId;
        foreach ($rows as $r) {
            if ((int) $r['id'] > $newMax) {
                $newMax = (int) $r['id'];
            }
        }

        $this->json([
            'success' => true,
            'rows' => $rows,
            'maxId' => $newMax,
            'period' => $period,
        ]);
    }

    // ——— Notification Channels ——————————————————————————————

    public function notificationChannels()
    {
        $this->requireAdmin();
        $channels = (new TelegramNotificationChannel())->fetchAll();
        $this->json(['success' => true, 'channels' => $channels]);
    }

    public function addChannelAction()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);
        $chatId = trim((string) $this->post('chat_id', ''));
        $label = trim((string) $this->post('label', ''));

        if ($chatId === '') {
            $this->json(['success' => false, 'message' => 'Chat ID/Channel không được để trống']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $success = $model->add($chatId, $label ?: null);

        $this->json([
            'success' => $success,
            'message' => $success ? 'Đã thêm kênh nhận thông báo thành công' : 'Kênh này đã tồn tại hoặc có lỗi',
        ]);
    }

    public function toggleChannelAction()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);
        $id = (int) $this->post('id', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID không hợp lệ']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $success = $model->toggle($id);
        $this->json(['success' => $success]);
    }

    public function updateChannelAction()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);

        $id = (int) $this->post('id', 0);
        $chatId = trim((string) $this->post('chat_id', ''));
        $label = trim((string) $this->post('label', ''));

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID không hợp lệ']);
            return;
        }
        if ($chatId === '') {
            $this->json(['success' => false, 'message' => 'Chat ID không được để trống']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $found = $model->find($id);
        if (!$found) {
            $this->json(['success' => false, 'message' => 'Kênh không tồn tại']);
            return;
        }

        try {
            $success = $model->updateChannel($id, $chatId, $label !== '' ? $label : null);
            $this->json([
                'success' => (bool) $success,
                'message' => $success ? 'Đã cập nhật kênh thành công' : 'Không thể cập nhật kênh',
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Không thể cập nhật (có thể trùng Chat ID)',
            ]);
        }
    }

    public function deleteChannelAction()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);
        $id = (int) $this->post('id', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID không hợp lệ']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $success = $model->delete($id);
        $this->json(['success' => $success]);
    }

    // ——— Orders —————————————————————————————————————————————

    public function orders()
    {
        $this->requireAdmin();
        $db = (new UserTelegramLink())->getConnection();

        // Check if DB schema supports telegram tracking
        $stmtCol = $db->query("SHOW COLUMNS FROM `orders` LIKE 'source_channel'");
        $hasSource = $stmtCol->fetch();
        $filterMode = $hasSource ? 'active' : 'none';

        if ($filterMode === 'active') {
            $sql = "SELECT o.*, u.username as buyer_username
                    FROM `orders` o
                    LEFT JOIN `users` u ON u.id = o.user_id
                    WHERE o.`source_channel` = 1
                    ORDER BY o.`id` DESC LIMIT 500";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $orders = [];
        }

        $siteConfig = Config::getSiteConfig();
        $lastCronRun = $siteConfig['last_cron_run'] ?? null;

        $this->view('admin/telegram/orders', [
            'orders' => $orders,
            'filterMode' => $filterMode,
            'lastCronRun' => $lastCronRun,
        ]);
    }

    // ——— User Links —————————————————————————————————————————

    public function sendMainChannelAlertAction()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);

        $defaultChatId = TelegramConfig::mainChannelId();
        if ($defaultChatId === '') {
            $defaultChatId = TelegramConfig::primaryAdminId();
        }

        $chatId = trim((string) $this->post('chat_id', $defaultChatId));
        $message = trim((string) $this->post('message', ''));

        if ($chatId === '') {
            $this->json(['success' => false, 'message' => 'Thiếu Telegram Chat ID / Channel']);
            return;
        }
        if ($message === '') {
            $this->json(['success' => false, 'message' => 'Nội dung ALERT không được để trống']);
            return;
        }

        $response = $this->telegram->apiCall('sendMessage', [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);

        $ok = !empty($response['ok']);
        $errorDescription = $response['description'] ?? 'Lỗi không xác định';

        $this->json([
            'success' => $ok,
            'message' => $ok ? 'Đã gửi ALERT vào Main Channel' : 'Gửi ALERT thất bại: ' . $errorDescription,
        ]);
    }

    // ——— Outbox —————————————————————————————————————————————

    public function outbox()
    {
        $this->requireAdmin();

        $outboxModel = new TelegramOutbox();
        $db = $outboxModel->getConnection();

        $statusFilter = trim((string) $this->get('status', 'all'));
        $period = trim((string) $this->get('period', 'all'));
        $search = trim((string) $this->get('search', ''));

        $whereBlocks = [];
        $params = [];

        if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'sent', 'fail'], true)) {
            $whereBlocks[] = "o.`status` = ?";
            $params[] = $statusFilter;
        }

        if ($period !== 'all') {
            switch ($period) {
                case 'today':
                    $whereBlocks[] = "DATE(o.`created_at`) = CURDATE()";
                    break;
                case 'yesterday':
                    $whereBlocks[] = "DATE(o.`created_at`) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                    break;
                case 'week':
                    $whereBlocks[] = "o.`created_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $whereBlocks[] = "o.`created_at` >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
            }
        }

        if ($search !== '') {
            $whereBlocks[] = "(CAST(o.`telegram_id` AS CHAR) LIKE ? OR o.`message` LIKE ? OR u.`username` LIKE ?)";
            $likeSearch = "%{$search}%";
            $params[] = $likeSearch;
            $params[] = $likeSearch;
            $params[] = $likeSearch;
        }

        $whereSql = !empty($whereBlocks) ? "WHERE " . implode(' AND ', $whereBlocks) : "";

        $sql = "SELECT o.*, u.username as web_username
                FROM `telegram_outbox` o
                LEFT JOIN `user_telegram_links` l ON l.telegram_id = o.telegram_id
                LEFT JOIN `users` u ON u.id = l.user_id
                {$whereSql}
                ORDER BY o.`id` DESC LIMIT 300";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = $outboxModel->getStats();
        $siteConfig = Config::getSiteConfig();
        $lastCronRun = $siteConfig['last_cron_run'] ?? null;

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'rows' => $messages,
                'stats' => $stats,
                'lastCronRun' => $lastCronRun,
                'filters' => [
                    'status' => $statusFilter,
                    'period' => $period,
                    'search' => $search,
                ],
            ]);
        }

        $this->view('admin/telegram/outbox', [
            'chungapi' => $siteConfig,
            'stats' => $stats,
            'messages' => $messages,
            'lastCronRun' => $lastCronRun,
            'filters' => [
                'status' => $statusFilter,
                'period' => $period,
                'search' => $search,
            ],
        ]);
    }

    public function outboxRetry()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);
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

        $this->json(['success' => true, 'message' => 'Đã đặt lại trạng thái để gửi lại']);
    }

    public function outboxDelete()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);
        $idsRaw = $this->post('ids', '');

        $outboxModel = new TelegramOutbox();
        $db = $outboxModel->getConnection();

        if ($idsRaw === 'all') {
            $db->exec("DELETE FROM `telegram_outbox` WHERE `status` IN ('sent', 'fail')");
            $this->json(['success' => true, 'message' => 'Đã xóa hàng đợi đã xử lý']);
            return;
        }

        $ids = array_filter(array_map('intval', explode(',', (string) $idsRaw)));

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("DELETE FROM `telegram_outbox` WHERE `id` IN ({$placeholders})");
            $stmt->execute($ids);
            $this->json(['success' => true, 'message' => 'Đã xóa các bản ghi được chọn']);
        } else {
            $this->json(['success' => false, 'message' => 'Vui lòng chọn bản ghi cần xóa']);
        }
    }


    // ——— Broadcast ——————————————————————————————————————————

    public function broadcastAction()
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);
        $message = trim((string) $this->post('message', ''));
        if ($message === '') {
            $this->json(['success' => false, 'message' => 'Nội dung tin nhắn không được để trống']);
            return;
        }

        $userModel = new TelegramUser();
        $tids = $userModel->getAllActive();

        if (empty($tids)) {
            $this->json(['success' => false, 'message' => 'Chưa có người dùng nào (active) để gửi tin']);
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

    /**
     * @param array<int,string> $keys
     */
    private function hasSensitiveQueryParams($keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $_GET)) {
                return true;
            }
        }
        return false;
    }

    private function hasSettingColumn($db, $column)
    {
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'setting'
              AND column_name = :column
        ");
        $stmt->execute(['column' => $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function ensureSettingColumn($db, $column, $definition)
    {
        if ($this->hasSettingColumn($db, $column)) {
            return;
        }

        try {
            $db->exec("ALTER TABLE `setting` ADD COLUMN `{$column}` {$definition}");
        } catch (Throwable $e) {
            // Ignore here; caller logic already handles missing columns safely.
        }
    }
}

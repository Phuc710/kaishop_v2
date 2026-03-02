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
            die('Truy cáº­p bá»‹ tá»« chá»‘i - Chá»‰ dÃ nh cho quáº£n trá»‹ viÃªn');
        }
    }

    // â”€â”€â”€ Settings â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function settings(): void
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
        ]);
    }

    public function updateSettings(): void
    {
        $this->requireAdmin();

        $fields = [
            'telegram_bot_token',
            'telegram_chat_id',
            'telegram_webhook_secret',
            'telegram_webhook_path',
            'telegram_admin_ids',
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
            'telegram_maintenance_message',
            "TEXT NULL AFTER `telegram_maintenance_enabled`"
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
                        $this->json(['success' => false, 'message' => 'ÄÆ°á»ng dáº«n Webhook khÃ´ng há»£p lá»‡.']);
                        return;
                    }
                    $value = $normalized;
                }

                $stmt = $db->prepare("UPDATE `setting` SET `{$field}` = ? ORDER BY `id` ASC LIMIT 1");
                $stmt->execute([$value]);
            }
        }
        Config::clearSiteConfigCache();

        $this->json(['success' => true, 'message' => 'ÄÃ£ cáº­p nháº­t cáº¥u hÃ¬nh Telegram']);
    }

    // â”€â”€â”€ Webhook Actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function setWebhookAction(): void
    {
        $this->requireAdmin();
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
                $this->json(['success' => false, 'message' => 'ÄÆ°á»ng dáº«n khÃ´ng há»£p lá»‡.']);
                return;
            }
        }

        // 2. Perform Registration
        $baseUrl = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');
        $path = get_setting('telegram_webhook_path', 'bottelekaishop_default');
        $webhookUrl = $baseUrl . '/api/' . ltrim($path, '/');
        $secret = get_setting('telegram_webhook_secret', '');

        $result = $this->telegram->setWebhook($webhookUrl, $secret ?: null);

        $this->json([
            'success' => !empty($result['ok']),
            'message' => !empty($result['ok']) ? 'ÄÃ£ lÆ°u vÃ  kÃ­ch hoáº¡t Webhook thÃ nh cÃ´ng!' : ($result['description'] ?? 'Lá»—i tá»« Telegram API'),
        ]);
    }

    public function deleteWebhookAction(): void
    {
        $this->requireAdmin();
        $result = $this->telegram->deleteWebhook();
        $this->json([
            'success' => !empty($result['ok']),
            'message' => !empty($result['ok']) ? 'ÄÃ£ táº¡m dá»«ng hoáº¡t Ä‘á»™ng Webhook' : ($result['description'] ?? 'Lá»—i'),
        ]);
    }

    public function testNotification(): void
    {
        $this->requireAdmin();
        $chatId = get_setting('telegram_chat_id', '');
        if ($chatId === '') {
            $this->json(['success' => false, 'message' => 'ChÆ°a cáº¥u hÃ¬nh Chat ID']);
            return;
        }

        $now = TimeService::instance()->nowSql();
        $msg = "âœ… <b>Test káº¿t ná»‘i thÃ nh cÃ´ng!</b>\nTin nháº¯n Ä‘Æ°á»£c gá»­i tá»« KaiShop Admin.\nðŸ• Thá»i gian: {$now}";
        $success = $this->telegram->sendTo($chatId, $msg);

        $this->json([
            'success' => (bool) $success,
            'message' => $success ? 'Đã gửi tin nhắn test thành công' : 'Gửi thất bại, hãy kiểm tra lại Token/Chat ID',
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

    public function terminal(): void
    {
        $this->requireAdmin();
        $logModel = new TelegramLog();
        $logs = $logModel->fetchRecent(150);
        $maxId = $logModel->maxId();
        $this->view('admin/telegram/terminal', [
            'logs' => $logs,
            'maxId' => $maxId,
        ]);
    }

    public function terminalPoll(): void
    {
        $this->requireAdmin();
        $afterId = (int) ($_GET['after'] ?? 0);
        $logModel = new TelegramLog();
        $rows = $logModel->fetchAfter($afterId, 100);
        $newMax = $afterId;
        foreach ($rows as $r) {
            if ((int) $r['id'] > $newMax)
                $newMax = (int) $r['id'];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['rows' => $rows, 'maxId' => $newMax], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ─── Notification Channels ───────────────────────────────────────────────

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
            $this->json(['success' => false, 'message' => 'Chat ID/Channel khÃ´ng Ä‘Æ°á»£c Ä‘á»ƒ trá»‘ng']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $success = $model->add($chatId, $label ?: null);

        $this->json([
            'success' => $success,
            'message' => $success ? 'ÄÃ£ thÃªm kÃªnh nháº­n thÃ´ng bÃ¡o thÃ nh cÃ´ng' : 'KÃªnh nÃ y Ä‘Ã£ tá»“n táº¡i hoáº·c cÃ³ lá»—i',
        ]);
    }

    public function toggleChannelAction(): void
    {
        $this->requireAdmin();
        $id = (int) $this->post('id', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID khÃ´ng há»£p lá»‡']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $success = $model->toggle($id);
        $this->json(['success' => $success]);
    }

    public function updateChannelAction(): void
    {
        $this->requireAdmin();

        $id = (int) $this->post('id', 0);
        $chatId = trim((string) $this->post('chat_id', ''));
        $label = trim((string) $this->post('label', ''));

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID khÃ´ng há»£p lá»‡']);
            return;
        }
        if ($chatId === '') {
            $this->json(['success' => false, 'message' => 'Chat ID khÃ´ng Ä‘Æ°á»£c Ä‘á»ƒ trá»‘ng']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $found = $model->find($id);
        if (!$found) {
            $this->json(['success' => false, 'message' => 'KÃªnh khÃ´ng tá»“n táº¡i']);
            return;
        }

        try {
            $success = $model->updateChannel($id, $chatId, $label !== '' ? $label : null);
            $this->json([
                'success' => (bool) $success,
                'message' => $success ? 'ÄÃ£ cáº­p nháº­t kÃªnh thÃ nh cÃ´ng' : 'KhÃ´ng thá»ƒ cáº­p nháº­t kÃªnh',
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'KhÃ´ng thá»ƒ cáº­p nháº­t (cÃ³ thá»ƒ trÃ¹ng Chat ID)',
            ]);
        }
    }

    public function deleteChannelAction(): void
    {
        $this->requireAdmin();
        $id = (int) $this->post('id', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID khÃ´ng há»£p lá»‡']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $success = $model->delete($id);
        $this->json(['success' => $success]);
    }

    // â”€â”€â”€ User Links â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function sendMainChannelAlertAction(): void
    {
        $this->requireAdmin();

        $chatId = trim((string) $this->post('chat_id', get_setting('telegram_chat_id', '')));
        $message = trim((string) $this->post('message', ''));

        if ($chatId === '') {
            $this->json(['success' => false, 'message' => 'Thiếu Telegram Chat ID / Channel']);
            return;
        }
        if ($message === '') {
            $this->json(['success' => false, 'message' => 'Nội dung ALERT không được để trống']);
            return;
        }

        $ok = (bool) $this->telegram->sendTo($chatId, $message);
        $this->json([
            'success' => $ok,
            'message' => $ok ? 'Đã gửi ALERT vào Main Channel' : 'Gửi ALERT thất bại, hãy kiểm tra token/chat id',
        ]);
    }

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
            $this->json(['success' => false, 'message' => 'Thiáº¿u ID ngÆ°á»i dÃ¹ng']);
            return;
        }

        $linkModel = new UserTelegramLink();
        $success = $linkModel->unlinkUser($userId);

        $this->json([
            'success' => $success,
            'message' => $success ? 'ÄÃ£ há»§y liÃªn káº¿t thÃ nh cÃ´ng' : 'KhÃ´ng thá»ƒ thá»±c hiá»‡n há»§y liÃªn káº¿t',
        ]);
    }

    // â”€â”€â”€ Outbox â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
        $search = trim((string) $this->get('search', ''));

        $this->view('admin/telegram/outbox', [
            'chungapi' => Config::getSiteConfig(),
            'stats' => $stats,
            'messages' => $messages,
            'filters' => [
                'status' => $statusFilter,
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

        $this->json(['success' => true, 'message' => 'ÄÃ£ Ä‘áº·t láº¡i tráº¡ng thÃ¡i Ä‘á»ƒ gá»­i láº¡i']);
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

        $this->json(['success' => true, 'message' => 'ÄÃ£ xÃ³a cÃ¡c báº£n ghi Ä‘Æ°á»£c chá»n']);
    }

    // â”€â”€â”€ Broadcast â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function broadcastAction(): void
    {
        $this->requireAdmin();
        $message = trim((string) $this->post('message', ''));
        if ($message === '') {
            $this->json(['success' => false, 'message' => 'Ná»™i dung tin nháº¯n khÃ´ng Ä‘Æ°á»£c Ä‘á»ƒ trá»‘ng']);
            return;
        }

        $userModel = new TelegramUser();
        $tids = $userModel->getAllActive();

        if (empty($tids)) {
            $this->json(['success' => false, 'message' => 'ChÆ°a cÃ³ ngÆ°á»i dÃ¹ng nÃ o (active) Ä‘á»ƒ gá»­i tin']);
            return;
        }

        $outbox = new TelegramOutbox();
        $count = 0;
        foreach ($tids as $tid) {
            $outbox->enqueue((int) $tid, $message);
            $count++;
        }

        $this->json(['success' => true, 'message' => "ÄÃ£ thÃªm {$count} tin nháº¯n vÃ o hÃ ng Ä‘á»£i gá»­i (Outbox)"]);
    }

    /**
     * @param array<int,string> $keys
     */
    private function hasSensitiveQueryParams(array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $_GET)) {
                return true;
            }
        }
        return false;
    }

    private function hasSettingColumn(PDO $db, string $column): bool
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

    private function ensureSettingColumn(PDO $db, string $column, string $definition): void
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

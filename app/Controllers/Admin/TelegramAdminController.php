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
            die('Truy cÃ¡ÂºÂ­p bÃ¡Â»â€¹ tÃ¡Â»Â« chÃ¡Â»â€˜i - ChÃ¡Â»â€° dÃƒÂ nh cho quÃ¡ÂºÂ£n trÃ¡Â»â€¹ viÃƒÂªn');
        }
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Settings Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

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
                        $this->json(['success' => false, 'message' => 'Ã„ÂÃ†Â°Ã¡Â»Âng dÃ¡ÂºÂ«n Webhook khÃƒÂ´ng hÃ¡Â»Â£p lÃ¡Â»â€¡.']);
                        return;
                    }
                    $value = $normalized;
                }

                $stmt = $db->prepare("UPDATE `setting` SET `{$field}` = ? ORDER BY `id` ASC LIMIT 1");
                $stmt->execute([$value]);
            }
        }
        Config::clearSiteConfigCache();

        $this->json(['success' => true, 'message' => 'Ã„ÂÃƒÂ£ cÃ¡ÂºÂ­p nhÃ¡ÂºÂ­t cÃ¡ÂºÂ¥u hÃƒÂ¬nh Telegram']);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Webhook Actions Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

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
                $this->json(['success' => false, 'message' => 'Ã„ÂÃ†Â°Ã¡Â»Âng dÃ¡ÂºÂ«n khÃƒÂ´ng hÃ¡Â»Â£p lÃ¡Â»â€¡.']);
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
            'message' => !empty($result['ok']) ? 'Ã„ÂÃƒÂ£ lÃ†Â°u vÃƒÂ  kÃƒÂ­ch hoÃ¡ÂºÂ¡t Webhook thÃƒÂ nh cÃƒÂ´ng!' : ($result['description'] ?? 'LÃ¡Â»â€”i tÃ¡Â»Â« Telegram API'),
        ]);
    }

    public function deleteWebhookAction(): void
    {
        $this->requireAdmin();
        $result = $this->telegram->deleteWebhook();
        $this->json([
            'success' => !empty($result['ok']),
            'message' => !empty($result['ok']) ? 'Ã„ÂÃƒÂ£ tÃ¡ÂºÂ¡m dÃ¡Â»Â«ng hoÃ¡ÂºÂ¡t Ã„â€˜Ã¡Â»â„¢ng Webhook' : ($result['description'] ?? 'LÃ¡Â»â€”i'),
        ]);
    }

    public function testNotification(): void
    {
        $this->requireAdmin();
        $chatId = get_setting('telegram_chat_id', '');
        if ($chatId === '') {
            $this->json(['success' => false, 'message' => 'ChÃ†Â°a cÃ¡ÂºÂ¥u hÃƒÂ¬nh Chat ID']);
            return;
        }

        $now = TimeService::instance()->nowSql();
        $msg = "Ã¢Å“â€¦ <b>Test kÃ¡ÂºÂ¿t nÃ¡Â»â€˜i thÃƒÂ nh cÃƒÂ´ng!</b>\nTin nhÃ¡ÂºÂ¯n Ã„â€˜Ã†Â°Ã¡Â»Â£c gÃ¡Â»Â­i tÃ¡Â»Â« KaiShop Admin.\nÃ°Å¸â€¢â€™ ThÃ¡Â»Âi gian: {$now}";
        $success = $this->telegram->sendTo($chatId, $msg);

        $this->json([
            'success' => (bool) $success,
            'message' => $success ? 'Ã„ÂÃƒÂ£ gÃ¡Â»Â­i tin nhÃ¡ÂºÂ¯n test thÃƒÂ nh cÃƒÂ´ng' : 'GÃ¡Â»Â­i thÃ¡ÂºÂ¥t bÃ¡ÂºÂ¡i, hÃƒÂ£y kiÃ¡Â»Æ’m tra lÃ¡ÂºÂ¡i Token/Chat ID',
        ]);
    }

    public function syncBotAction(): void
    {
        $this->requireAdmin();
        $botLogic = new TelegramBotService($this->telegram);
        $result = $botLogic->initializeBot();
        $this->json([
            'success' => !empty($result['ok']),
            'message' => !empty($result['ok']) ? 'Ã„ÂÃƒÂ£ Ã„â€˜Ã¡Â»â€œng bÃ¡Â»â„¢ Menu & LÃ¡Â»â€¡nh Bot thÃƒÂ nh cÃƒÂ´ng' : ($result['description'] ?? 'LÃ¡Â»â€”i Ã„â€˜Ã¡Â»â€œng bÃ¡Â»â„¢'),
        ]);
    }

    public function terminal(): void
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

    public function terminalPoll(): void
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

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Notification Channels Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

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
            $this->json(['success' => false, 'message' => 'Chat ID/Channel khÃƒÂ´ng Ã„â€˜Ã†Â°Ã¡Â»Â£c Ã„â€˜Ã¡Â»Æ’ trÃ¡Â»â€˜ng']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $success = $model->add($chatId, $label ?: null);

        $this->json([
            'success' => $success,
            'message' => $success ? 'Ã„ÂÃƒÂ£ thÃƒÂªm kÃƒÂªnh nhÃ¡ÂºÂ­n thÃƒÂ´ng bÃƒÂ¡o thÃƒÂ nh cÃƒÂ´ng' : 'KÃƒÂªnh nÃƒÂ y Ã„â€˜ÃƒÂ£ tÃ¡Â»â€œn tÃ¡ÂºÂ¡i hoÃ¡ÂºÂ·c cÃƒÂ³ lÃ¡Â»â€”i',
        ]);
    }

    public function toggleChannelAction(): void
    {
        $this->requireAdmin();
        $id = (int) $this->post('id', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID khÃƒÂ´ng hÃ¡Â»Â£p lÃ¡Â»â€¡']);
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
            $this->json(['success' => false, 'message' => 'ID khÃƒÂ´ng hÃ¡Â»Â£p lÃ¡Â»â€¡']);
            return;
        }
        if ($chatId === '') {
            $this->json(['success' => false, 'message' => 'Chat ID khÃƒÂ´ng Ã„â€˜Ã†Â°Ã¡Â»Â£c Ã„â€˜Ã¡Â»Æ’ trÃ¡Â»â€˜ng']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $found = $model->find($id);
        if (!$found) {
            $this->json(['success' => false, 'message' => 'KÃƒÂªnh khÃƒÂ´ng tÃ¡Â»â€œn tÃ¡ÂºÂ¡i']);
            return;
        }

        try {
            $success = $model->updateChannel($id, $chatId, $label !== '' ? $label : null);
            $this->json([
                'success' => (bool) $success,
                'message' => $success ? 'Ã„ÂÃƒÂ£ cÃ¡ÂºÂ­p nhÃ¡ÂºÂ­t kÃƒÂªnh thÃƒÂ nh cÃƒÂ´ng' : 'KhÃƒÂ´ng thÃ¡Â»Æ’ cÃ¡ÂºÂ­p nhÃ¡ÂºÂ­t kÃƒÂªnh',
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'KhÃƒÂ´ng thÃ¡Â»Æ’ cÃ¡ÂºÂ­p nhÃ¡ÂºÂ­t (cÃƒÂ³ thÃ¡Â»Æ’ trÃƒÂ¹ng Chat ID)',
            ]);
        }
    }

    public function deleteChannelAction(): void
    {
        $this->requireAdmin();
        $id = (int) $this->post('id', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID khÃƒÂ´ng hÃ¡Â»Â£p lÃ¡Â»â€¡']);
            return;
        }

        $model = new TelegramNotificationChannel();
        $success = $model->delete($id);
        $this->json(['success' => $success]);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Orders Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function orders(): void
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

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ User Links Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function sendMainChannelAlertAction(): void
    {
        $this->requireAdmin();

        $chatId = trim((string) $this->post('chat_id', get_setting('telegram_chat_id', '')));
        $message = trim((string) $this->post('message', ''));

        if ($chatId === '') {
            $this->json(['success' => false, 'message' => 'ThiÃ¡ÂºÂ¿u Telegram Chat ID / Channel']);
            return;
        }
        if ($message === '') {
            $this->json(['success' => false, 'message' => 'NÃ¡Â»â„¢i dung ALERT khÃƒÂ´ng Ã„â€˜Ã†Â°Ã¡Â»Â£c Ã„â€˜Ã¡Â»Æ’ trÃ¡Â»â€˜ng']);
            return;
        }

        $ok = (bool) $this->telegram->sendTo($chatId, $message);
        $this->json([
            'success' => $ok,
            'message' => $ok ? 'Ã„ÂÃƒÂ£ gÃ¡Â»Â­i ALERT vÃƒÂ o Main Channel' : 'GÃ¡Â»Â­i ALERT thÃ¡ÂºÂ¥t bÃ¡ÂºÂ¡i, hÃƒÂ£y kiÃ¡Â»Æ’m tra token/chat id',
        ]);
    }

    public function links(): void
    {
        $this->requireAdmin();

        $linkModel = new UserTelegramLink();
        $db = $linkModel->getConnection();

        $keyword = trim((string) $this->get('q', ''));
        $unlinkFilter = trim((string) $this->get('unlink', 'all'));
        $period = trim((string) $this->get('period', 'all'));
        $limit = (int) $this->get('limit', 10);

        $allowedUnlinkFilters = ['all', 'unlink', 'link'];
        if (!in_array($unlinkFilter, $allowedUnlinkFilters, true)) {
            $unlinkFilter = 'all';
        }

        $allowedPeriods = ['all', 'today', '7', '15', '30'];
        if (!in_array($period, $allowedPeriods, true)) {
            $period = 'all';
        }

        $allowedLimits = [10, 20, 50, 100];
        if (!in_array($limit, $allowedLimits, true)) {
            $limit = 10;
        }

        $whereParts = [];
        $params = [];

        if ($unlinkFilter === 'unlink') {
            $whereParts[] = 'l.`id` IS NULL';
        } elseif ($unlinkFilter === 'link') {
            $whereParts[] = 'l.`id` IS NOT NULL';
        }

        if ($keyword !== '') {
            $likeQ = "%{$keyword}%";
            $whereParts[] = "(CAST(u.`id` AS CHAR) LIKE ? OR u.`username` LIKE ? OR u.`email` LIKE ? OR CAST(l.`telegram_id` AS CHAR) LIKE ? OR l.`telegram_username` LIKE ?)";
            $params = array_merge($params, [$likeQ, $likeQ, $likeQ, $likeQ, $likeQ]);
        }

        if ($period === 'today') {
            $whereParts[] = 'DATE(COALESCE(l.`linked_at`, u.`created_at`)) = CURDATE()';
        } elseif (in_array($period, ['7', '15', '30'], true)) {
            $days = (int) $period;
            $whereParts[] = "COALESCE(l.`linked_at`, u.`created_at`) >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        }

        $whereSql = !empty($whereParts) ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

        $sql = "SELECT
                    u.`id` AS `user_id`,
                    u.`username` AS `web_username`,
                    u.`email` AS `web_email`,
                    u.`created_at` AS `user_created_at`,
                    l.`id` AS `link_id`,
                    l.`telegram_id`,
                    l.`telegram_username`,
                    l.`first_name`,
                    l.`linked_at`,
                    l.`last_active`
                FROM `users` u
                LEFT JOIN `user_telegram_links` l ON l.`user_id` = u.`id`
                {$whereSql}
                ORDER BY
                    CASE WHEN l.`id` IS NULL THEN 1 ELSE 0 END ASC,
                    l.`linked_at` DESC,
                    u.`id` DESC
                LIMIT {$limit}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $links = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalUsers = (int) $db->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
        $totalLinked = (int) $db->query("SELECT COUNT(*) FROM `user_telegram_links`")->fetchColumn();
        $totalUnlinked = max(0, $totalUsers - $totalLinked);
        $filteredCount = count($links);

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'rows' => $links,
                'stats' => [
                    'total_users' => $totalUsers,
                    'total_linked' => $totalLinked,
                    'total_unlinked' => $totalUnlinked,
                    'filtered_count' => $filteredCount,
                ],
                'filters' => [
                    'q' => $keyword,
                    'unlink' => $unlinkFilter,
                    'period' => $period,
                    'limit' => $limit,
                ],
            ]);
        }

        $siteConfig = Config::getSiteConfig();
        $lastCronRun = $siteConfig['last_cron_run'] ?? null;

        $this->view('admin/telegram/links', [
            'links' => $links,
            'keyword' => $keyword,
            'filters' => [
                'unlink' => $unlinkFilter,
                'period' => $period,
                'limit' => $limit,
            ],
            'stats' => [
                'total_users' => $totalUsers,
                'total_linked' => $totalLinked,
                'total_unlinked' => $totalUnlinked,
                'filtered_count' => $filteredCount,
            ],
            'lastCronRun' => $lastCronRun,
        ]);
    }

    public function unlinkAction(): void
    {
        $this->requireAdmin();
        $userId = (int) $this->post('user_id', 0);
        if ($userId <= 0) {
            $this->json(['success' => false, 'message' => 'ThiÃ¡ÂºÂ¿u ID ngÃ†Â°Ã¡Â»Âi dÃƒÂ¹ng']);
            return;
        }

        $linkModel = new UserTelegramLink();
        $success = $linkModel->unlinkUser($userId);

        $this->json([
            'success' => $success,
            'message' => $success ? 'Ã„ÂÃƒÂ£ hÃ¡Â»Â§y liÃƒÂªn kÃ¡ÂºÂ¿t thÃƒÂ nh cÃƒÂ´ng' : 'KhÃƒÂ´ng thÃ¡Â»Æ’ thÃ¡Â»Â±c hiÃ¡Â»â€¡n hÃ¡Â»Â§y liÃƒÂªn kÃ¡ÂºÂ¿t',
        ]);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Outbox Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function outbox(): void
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

        $this->json(['success' => true, 'message' => 'Ã„ÂÃƒÂ£ Ã„â€˜Ã¡ÂºÂ·t lÃ¡ÂºÂ¡i trÃ¡ÂºÂ¡ng thÃƒÂ¡i Ã„â€˜Ã¡Â»Æ’ gÃ¡Â»Â­i lÃ¡ÂºÂ¡i']);
    }

    public function outboxDelete(): void
    {
        $this->requireAdmin();
        $idsRaw = $this->post('ids', '');

        $outboxModel = new TelegramOutbox();
        $db = $outboxModel->getConnection();

        if ($idsRaw === 'all') {
            $db->exec("DELETE FROM `telegram_outbox` WHERE `status` IN ('sent', 'fail')");
            $this->json(['success' => true, 'message' => 'Da xoa hang doi da xu ly']);
            return;
        }

        $ids = array_filter(array_map('intval', explode(',', (string) $idsRaw)));

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("DELETE FROM `telegram_outbox` WHERE `id` IN ({$placeholders})");
            $stmt->execute($ids);
            $this->json(['success' => true, 'message' => 'Da xoa cac ban ghi duoc chon']);
        } else {
            $this->json(['success' => false, 'message' => 'Vui long chon ban ghi can xoa']);
        }
    }


    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Broadcast Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function broadcastAction(): void
    {
        $this->requireAdmin();
        $message = trim((string) $this->post('message', ''));
        if ($message === '') {
            $this->json(['success' => false, 'message' => 'NÃ¡Â»â„¢i dung tin nhÃ¡ÂºÂ¯n khÃƒÂ´ng Ã„â€˜Ã†Â°Ã¡Â»Â£c Ã„â€˜Ã¡Â»Æ’ trÃ¡Â»â€˜ng']);
            return;
        }

        $userModel = new TelegramUser();
        $tids = $userModel->getAllActive();

        if (empty($tids)) {
            $this->json(['success' => false, 'message' => 'ChÃ†Â°a cÃƒÂ³ ngÃ†Â°Ã¡Â»Âi dÃƒÂ¹ng nÃƒÂ o (active) Ã„â€˜Ã¡Â»Æ’ gÃ¡Â»Â­i tin']);
            return;
        }

        $outbox = new TelegramOutbox();
        $count = 0;
        foreach ($tids as $tid) {
            $outbox->enqueue((int) $tid, $message);
            $count++;
        }

        $this->json(['success' => true, 'message' => "Ã„ÂÃƒÂ£ thÃƒÂªm {$count} tin nhÃ¡ÂºÂ¯n vÃƒÂ o hÃƒÂ ng Ã„â€˜Ã¡Â»Â£i gÃ¡Â»Â­i (Outbox)"]);
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

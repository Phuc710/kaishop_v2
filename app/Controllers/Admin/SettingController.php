<?php

class SettingController extends Controller
{
    private $authService;
    private $maintenanceService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->maintenanceService = new MaintenanceService();
    }

    private function requireAdmin()
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();

        if (!isset($user['level']) || (int) $user['level'] !== 9) {
            http_response_code(403);
            die('Truy cáº­p bá»‹ tá»« chá»‘i - Chá»‰ dÃ nh cho quáº£n trá»‹ viÃªn');
        }
    }

    public function index()
    {
        $this->requireAdmin();
        if ($this->hasSensitiveQueryParams([
            'telegram_bot_token',
            'telegram_chat_id',
            'telegram_admin_ids',
            'telegram_order_cooldown',
            'telegram_webhook_secret',
            'pass_mail_auto',
            'sepay_api_key',
        ])) {
            $this->redirect(url('admin/setting'));
        }
        global $chungapi;

        $maintenanceConfig = $this->maintenanceService->getConfig();
        $maintenanceStartInput = $this->maintenanceService->toDateTimeLocalInput($maintenanceConfig['start_at'] ?? null);
        $maintenanceEndInput = $this->maintenanceService->toDateTimeLocalInput($maintenanceConfig['end_at'] ?? null);

        $this->view('admin/setting', [
            'chungapi' => $chungapi,
            'maintenanceConfig' => $maintenanceConfig,
            'maintenanceStartInput' => $maintenanceStartInput,
            'maintenanceEndInput' => $maintenanceEndInput,
        ]);
    }

    public function update()
    {
        $this->requireAdmin();

        if (!$this->validateCsrf()) {
            return $this->json(['status' => 'error', 'message' => 'Lá»—i xÃ¡c thá»±c (CSRF). Vui lÃ²ng táº£i láº¡i trang.']);
        }

        $action = $this->post('action');
        $data = $this->post();

        switch ($action) {
            case 'update_general':
                return $this->updateSettings([
                    'logo',
                    'logo_footer',
                    'favicon',
                    'fb_admin',
                    'tele_admin',
                    'tiktok_admin',
                    'youtube_admin',
                    'ten_web',
                    'sdt_admin',
                    'email_cf',
                    'mo_ta',
                ]);

            case 'update_smtp':
                return $this->updateSettings([
                    'ten_nguoi_gui',
                    'email_auto',
                    'pass_mail_auto',
                ]);

            case 'update_notification':
                return $this->updateSettings([
                    'popup_template',
                    'thongbao',
                    'home_hero_html',
                ]);

            case 'update_telegram':
                return $this->updateTelegramSettings();

            case 'update_bank':
                return $this->updateSettings([
                    'bank_name',
                    'bank_account',
                    'bank_owner',
                    'sepay_api_key',
                    'bonus_1_amount',
                    'bonus_1_percent',
                    'bonus_2_amount',
                    'bonus_2_percent',
                    'bonus_3_amount',
                    'bonus_3_percent',
                ]);

            case 'update_maintenance':
                $res = $this->maintenanceService->saveConfig($data);
                if ($res['success']) {
                    Logger::info('System', 'update_maintenance', 'Cáº­p nháº­t cáº¥u hÃ¬nh báº£o trÃ¬');
                }
                return $this->json([
                    'status' => $res['success'] ? 'success' : 'error',
                    'message' => $res['message'],
                ]);

            case 'toggle_maintenance_manual':
                $res = $this->maintenanceService->setManualMode(!empty($data['maintenance_enabled']));
                if ($res['success']) {
                    Logger::info(
                        'System',
                        'toggle_maintenance_manual',
                        !empty($data['maintenance_enabled']) ? 'Báº­t báº£o trÃ¬ thá»§ cÃ´ng' : 'Táº¯t báº£o trÃ¬ thá»§ cÃ´ng'
                    );
                }
                return $this->json([
                    'status' => $res['success'] ? 'success' : 'error',
                    'message' => $res['message'],
                    'maintenance' => $res['maintenance'] ?? null,
                ]);

            case 'clear_maintenance':
                $res = $this->maintenanceService->clearNow();
                if ($res['success']) {
                    Logger::info('System', 'clear_maintenance', 'Táº¯t cháº¿ Ä‘á»™ báº£o trÃ¬ ngay láº­p tá»©c');
                }
                return $this->json([
                    'status' => $res['success'] ? 'success' : 'error',
                    'message' => $res['message'],
                ]);

            default:
                return $this->json(['status' => 'error', 'message' => 'HÃ nh Ä‘á»™ng khÃ´ng há»£p lá»‡']);
        }
    }

    private function updateSettings(array $keys)
    {
        global $connection;
        $sets = [];
        $action = $this->post('action', 'update_settings');

        // Handle File Uploads
        $uploadDir = 'assets/uploads/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($keys as $key) {
            // Check if file is uploaded for this key
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$key];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'ico', 'svg', 'webp'];

                if (in_array($ext, $allowed)) {
                    $newFilename = $key . '_' . time() . '.' . $ext;
                    $uploadPath = $uploadDir . $newFilename;

                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        $safePath = $connection->real_escape_string($uploadPath);
                        $sets[] = "`{$key}` = '{$safePath}'";
                        continue; // Skip text input if file uploaded successfully
                    }
                }
            }

            // Handle Text Input if no file or upload failed
            if (isset($_POST[$key])) {
                $value = $connection->real_escape_string($_POST[$key]);
                $sets[] = "`{$key}` = '{$value}'";
            }
        }

        if (empty($sets)) {
            return $this->json(['status' => 'error', 'message' => 'KhÃ´ng cÃ³ dá»¯ liá»‡u thay Ä‘á»•i']);
        }

        $sql = "UPDATE `setting` SET " . implode(', ', $sets) . " ORDER BY `id` ASC LIMIT 1";

        if ($connection->query($sql)) {
            Logger::info('Admin', $action, 'Cáº­p nháº­t cÃ i Ä‘áº·t há»‡ thá»‘ng: ' . implode(', ', $keys));

            if (class_exists('Config')) {
                Config::clearSiteConfigCache();
            }

            return $this->json(['status' => 'success', 'message' => 'Cáº­p nháº­t thÃ nh cÃ´ng']);
        }

        return $this->json(['status' => 'error', 'message' => 'Lá»—i database: ' . $connection->error]);
    }

    private function updateTelegramSettings()
    {
        global $connection;

        $token = trim((string) $this->post('telegram_bot_token', ''));
        $chatId = trim((string) $this->post('telegram_chat_id', ''));
        $clearToken = in_array((string) $this->post('clear_telegram_bot_token', '0'), ['1', 'true', 'on'], true);

        if ($token !== '' && !preg_match('/^\d{6,}:[A-Za-z0-9_-]{20,}$/', $token)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Telegram Bot Token khÃ´ng há»£p lá»‡ (Ä‘á»‹nh dáº¡ng thÆ°á»ng lÃ  123456789:ABC...)',
            ]);
        }

        $isNumericChatId = preg_match('/^-?\d+$/', $chatId) === 1;
        $isChannelChat = preg_match('/^@[A-Za-z0-9_]{5,}$/', $chatId) === 1;
        if ($chatId !== '' && !$isNumericChatId && !$isChannelChat) {
            return $this->json([
                'status' => 'error',
                'message' => 'Telegram Chat ID khÃ´ng há»£p lá»‡ (vÃ­ dá»¥: -1001234567890 hoáº·c @channel_name)',
            ]);
        }

        $sets = [];
        $changedFields = [];

        if ($clearToken) {
            $sets[] = "`telegram_bot_token` = ''";
            $changedFields[] = 'telegram_bot_token:cleared';
        } elseif ($token !== '') {
            $safeToken = $connection->real_escape_string($token);
            $sets[] = "`telegram_bot_token` = '{$safeToken}'";
            $changedFields[] = 'telegram_bot_token:updated';
        }

        if (isset($_POST['telegram_chat_id'])) {
            $safeChatId = $connection->real_escape_string($chatId);
            $sets[] = "`telegram_chat_id` = '{$safeChatId}'";
            $changedFields[] = 'telegram_chat_id:updated';
        }

        if (empty($sets)) {
            return $this->json([
                'status' => 'error',
                'message' => 'KhÃ´ng cÃ³ thay Ä‘á»•i nÃ o Ä‘á»ƒ lÆ°u',
            ]);
        }

        $sql = "UPDATE `setting` SET " . implode(', ', $sets) . " ORDER BY `id` ASC LIMIT 1";
        if (!$connection->query($sql)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Lá»—i database: ' . $connection->error,
            ]);
        }

        if (class_exists('Config')) {
            Config::clearSiteConfigCache();
        }

        Logger::info('Admin', 'update_telegram', 'Cáº­p nháº­t cáº¥u hÃ¬nh Telegram', [
            'fields' => $changedFields,
            'has_token' => $clearToken ? false : ($token !== '' ? true : null),
            'has_chat_id' => $chatId !== '',
        ]);

        return $this->json([
            'status' => 'success',
            'message' => 'ÄÃ£ lÆ°u cáº¥u hÃ¬nh Telegram',
        ]);
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
}


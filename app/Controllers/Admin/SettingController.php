<?php

/**
 * Admin Setting Controller
 * Handles website configuration
 */
class SettingController extends Controller
{
    private $authService;
    private $maintenanceService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->maintenanceService = new MaintenanceService();
    }

    /**
     * Check admin access
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
     * Show settings page
     */
    public function index()
    {
        $this->requireAdmin();
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

    /**
     * Update settings (AJAX)
     */
    public function update()
    {
        $this->requireAdmin();

        // Simple CSRF check from base Controller
        if (!$this->validateCsrf()) {
            return $this->json(['status' => 'error', 'message' => 'Lỗi xác thực (CSRF). Vui lòng tải lại trang.']);
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
                    'mo_ta'
                ]);

            case 'update_smtp':
                return $this->updateSettings([
                    'ten_nguoi_gui',
                    'email_auto',
                    'pass_mail_auto'
                ]);

            case 'update_notification':
                return $this->updateSettings([
                    'popup_template',
                    'thongbao'
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
                    'bonus_3_percent'
                ]);

            case 'update_maintenance':
                $res = $this->maintenanceService->saveConfig($data);
                if ($res['success']) {
                    Logger::info('System', 'update_maintenance', 'Cập nhật cấu hình bảo trì');
                }
                return $this->json([
                    'status' => $res['success'] ? 'success' : 'error',
                    'message' => $res['message']
                ]);

            case 'clear_maintenance':
                $res = $this->maintenanceService->clearNow();
                if ($res['success']) {
                    Logger::info('System', 'clear_maintenance', 'Tắt chế độ bảo trì ngay lập tức');
                }
                return $this->json([
                    'status' => $res['success'] ? 'success' : 'error',
                    'message' => $res['message']
                ]);

            default:
                return $this->json(['status' => 'error', 'message' => 'Hành động không hợp lệ']);
        }
    }

    /**
     * Helper to update multiple settings in the `setting` table
     */
    private function updateSettings(array $keys)
    {
        global $connection;
        $sets = [];
        $action = $this->post('action', 'update_settings');

        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $value = $connection->real_escape_string($_POST[$key]);
                $sets[] = "`{$key}` = '{$value}'";
            }
        }

        if (empty($sets)) {
            return $this->json(['status' => 'error', 'message' => 'Không có dữ liệu thay đổi']);
        }

        $sql = "UPDATE `setting` SET " . implode(', ', $sets) . " ORDER BY `id` ASC LIMIT 1";

        if ($connection->query($sql)) {
            // Log the update
            Logger::info('Admin', $action, 'Cập nhật cài đặt hệ thống: ' . implode(', ', $keys));

            // Clear cache if Config class exists
            if (class_exists('Config')) {
                Config::clearSiteConfigCache();
            }
            return $this->json(['status' => 'success', 'message' => 'Cập nhật thành công']);
        }

        return $this->json(['status' => 'error', 'message' => 'Lỗi database: ' . $connection->error]);
    }

    /**
     * Update Telegram bot settings with secure handling:
     * - bot token is never exposed in logs
     * - empty token input keeps existing token
     * - explicit clear checkbox to remove token
     */
    private function updateTelegramSettings()
    {
        global $connection;

        $token = trim((string) $this->post('telegram_bot_token', ''));
        $chatId = trim((string) $this->post('telegram_chat_id', ''));
        $clearToken = in_array((string) $this->post('clear_telegram_bot_token', '0'), ['1', 'true', 'on'], true);

        if ($token !== '' && !preg_match('/^\d{6,}:[A-Za-z0-9_-]{20,}$/', $token)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Telegram Bot Token không hợp lệ (định dạng thường là 123456789:ABC...)',
            ]);
        }

        $isNumericChatId = preg_match('/^-?\d+$/', $chatId) === 1;
        $isChannelChat = preg_match('/^@[A-Za-z0-9_]{5,}$/', $chatId) === 1;
        if ($chatId !== '' && !$isNumericChatId && !$isChannelChat) {
            return $this->json([
                'status' => 'error',
                'message' => 'Telegram Chat ID không hợp lệ (ví dụ: -1001234567890 hoặc @channel_name)',
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
                'message' => 'Không có thay đổi nào để lưu',
            ]);
        }

        $sql = "UPDATE `setting` SET " . implode(', ', $sets) . " ORDER BY `id` ASC LIMIT 1";
        if (!$connection->query($sql)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Lỗi database: ' . $connection->error,
            ]);
        }

        if (class_exists('Config')) {
            Config::clearSiteConfigCache();
        }

        Logger::info('Admin', 'update_telegram', 'Cập nhật cấu hình Telegram', [
            'fields' => $changedFields,
            'has_token' => $clearToken ? false : ($token !== '' ? true : null),
            'has_chat_id' => $chatId !== '',
        ]);

        return $this->json([
            'status' => 'success',
            'message' => 'Đã lưu cấu hình Telegram',
        ]);
    }
}

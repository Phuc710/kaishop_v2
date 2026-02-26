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

        $this->view('admin/setting', [
            'chungapi' => $chungapi,
            'maintenanceConfig' => $maintenanceConfig
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
}

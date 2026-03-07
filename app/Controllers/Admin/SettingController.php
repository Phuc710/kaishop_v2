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
            die('Truy cập bị từ chối - Chỉ dành cho quản trị viên');
        }
    }

    public function index()
    {
        $this->requireAdmin();
        if (
            $this->hasSensitiveQueryParams([
                'telegram_bot_token',
                'telegram_chat_id',
                'telegram_admin_ids',
                'telegram_order_cooldown',
                'telegram_webhook_secret',
                'pass_mail_auto',
                'sepay_api_key',
                'binance_api_key',
                'binance_api_secret',
            ])
        ) {
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
                    'mo_ta',
                    'support_tele',
                    'discord_admin',
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



            case 'update_bank':
                return $this->updateSettings([
                    'bank_name',
                    'bank_account',
                    'bank_owner',
                    'sepay_api_key',
                    'deposit_warning_bank',
                    'bank_pay_enabled',
                ]);

            case 'update_bonus':
                return $this->updateSettings([
                    'bonus_1_amount',
                    'bonus_1_percent',
                    'bonus_2_amount',
                    'bonus_2_percent',
                    'bonus_3_amount',
                    'bonus_3_percent',
                ]);

            case 'update_status':
                $key = $this->post('key');
                $value = (int) $this->post('value');
                $allowedKeys = ['bank_pay_enabled', 'binance_pay_enabled'];
                if (!in_array($key, $allowedKeys)) {
                    return $this->json(['status' => 'error', 'message' => 'Trường cập nhật không hợp lệ']);
                }
                // Inject value into $_POST so updateSettings() can find it
                $_POST[$key] = (string) $value;
                return $this->updateSettings([$key]);

            case 'update_binance':
                return $this->updateSettings([
                    'binance_api_key',
                    'binance_api_secret',
                    'binance_uid',
                    'binance_rate_vnd',
                    'binance_pay_enabled',
                    'deposit_warning_binance',
                ]);

            case 'update_maintenance':
                $res = $this->maintenanceService->saveConfig($data);
                if ($res['success']) {
                    Logger::info('System', 'update_maintenance', 'Cập nhật cấu hình bảo trì');
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
                        !empty($data['maintenance_enabled']) ? 'Bật bảo trì thủ công' : 'Tắt bảo trì thủ công'
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
                    Logger::info('System', 'clear_maintenance', 'Tắt chế độ bảo trì ngay lập tức');
                }
                return $this->json([
                    'status' => $res['success'] ? 'success' : 'error',
                    'message' => $res['message'],
                ]);

            default:
                return $this->json(['status' => 'error', 'message' => 'Hành động không hợp lệ']);
        }
    }

    private function updateSettings(array $keys)
    {
        global $connection;
        $sets = [];
        $action = $this->post('action', 'update_settings');

        // Keys that must be encrypted before storing in DB
        $encryptedKeys = [
            'binance_api_key',
            'binance_api_secret',
            'sepay_api_key',
            'telegram_bot_token',
            'telegram_webhook_secret',
            'pass_mail_auto',
        ];

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
                $rawValue = $_POST[$key];

                // Encrypt sensitive keys before storing
                if (in_array($key, $encryptedKeys, true) && class_exists('SecureCrypto') && $rawValue !== '') {
                    $rawValue = SecureCrypto::encrypt($rawValue);
                }

                $value = $connection->real_escape_string($rawValue);
                $sets[] = "`{$key}` = '{$value}'";
            }
        }

        if (empty($sets)) {
            $receivedKeys = array_keys($_POST);
            return $this->json(['status' => 'error', 'message' => 'Không có dữ liệu thay đổi. Keys nhận được: ' . implode(', ', $receivedKeys)]);
        }

        $sql = "UPDATE `setting` SET " . implode(', ', $sets) . " ORDER BY `id` ASC LIMIT 1";

        if ($connection->query($sql)) {
            Logger::info('Admin', $action, 'Cập nhật cài đặt hệ thống: ' . implode(', ', $keys));

            if (class_exists('Config')) {
                Config::clearSiteConfigCache();
            }

            return $this->json(['status' => 'success', 'message' => 'Cập nhật thành công']);
        }

        return $this->json(['status' => 'error', 'message' => 'Lỗi database: ' . $connection->error]);
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

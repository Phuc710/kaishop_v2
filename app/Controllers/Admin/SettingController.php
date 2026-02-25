<?php

/**
 * Admin Setting Controller
 * Handles website configuration.
 */
class SettingController extends Controller
{
    private AuthService $authService;
    private MaintenanceService $maintenanceService;
    private PDO $db;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->maintenanceService = new MaintenanceService();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Check admin access.
     */
    private function requireAdmin(): void
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();

        if (!isset($user['level']) || (int) $user['level'] !== 9) {
            http_response_code(403);
            die('Truy cap bi tu choi - Chi danh cho quan tri vien');
        }
    }

    /**
     * Ensure DB schema exists for newer setting columns.
     */
    private function ensureSchema(): void
    {
        global $connection;

        if (!($connection instanceof mysqli)) {
            $this->maintenanceService->ensureSchema();
            return;
        }

        $columns = [
            'contact_page_title' => "ALTER TABLE `setting` ADD COLUMN `contact_page_title` varchar(255) DEFAULT NULL",
            'contact_page_subtitle' => "ALTER TABLE `setting` ADD COLUMN `contact_page_subtitle` text DEFAULT NULL",
            'contact_email_label' => "ALTER TABLE `setting` ADD COLUMN `contact_email_label` varchar(150) DEFAULT NULL",
            'contact_phone_label' => "ALTER TABLE `setting` ADD COLUMN `contact_phone_label` varchar(150) DEFAULT NULL",
            'contact_support_note' => "ALTER TABLE `setting` ADD COLUMN `contact_support_note` text DEFAULT NULL",
            'policy_page_title' => "ALTER TABLE `setting` ADD COLUMN `policy_page_title` varchar(255) DEFAULT NULL",
            'policy_page_subtitle' => "ALTER TABLE `setting` ADD COLUMN `policy_page_subtitle` text DEFAULT NULL",
            'policy_content_html' => "ALTER TABLE `setting` ADD COLUMN `policy_content_html` longtext DEFAULT NULL",
            'policy_notice_text' => "ALTER TABLE `setting` ADD COLUMN `policy_notice_text` text DEFAULT NULL",
            'terms_page_title' => "ALTER TABLE `setting` ADD COLUMN `terms_page_title` varchar(255) DEFAULT NULL",
            'terms_page_subtitle' => "ALTER TABLE `setting` ADD COLUMN `terms_page_subtitle` text DEFAULT NULL",
            'terms_content_html' => "ALTER TABLE `setting` ADD COLUMN `terms_content_html` longtext DEFAULT NULL",
            'terms_notice_text' => "ALTER TABLE `setting` ADD COLUMN `terms_notice_text` text DEFAULT NULL",
        ];

        foreach ($columns as $column => $sql) {
            $columnEsc = $connection->real_escape_string($column);
            $check = $connection->query("SHOW COLUMNS FROM `setting` LIKE '{$columnEsc}'");
            if (!$check || $check->num_rows === 0) {
                @$connection->query($sql);
            }
        }

        $this->maintenanceService->ensureSchema();
    }

    public function index()
    {
        $this->requireAdmin();
        $this->ensureSchema();
        global $chungapi;

        $maintenanceConfig = $this->maintenanceService->getConfig();
        $maintenanceState = $this->maintenanceService->getState(true);

        $this->view('admin/setting', [
            'chungapi' => $chungapi,
            'maintenanceConfig' => $maintenanceConfig,
            'maintenanceState' => $maintenanceState,
        ]);
    }

    public function update()
    {
        $this->requireAdmin();
        $this->ensureSchema();

        $action = (string) $this->post('action');

        switch ($action) {
            case 'update_general':
                return $this->updateGeneral();
            case 'update_contact_page':
                return $this->updateContactPage();
            case 'update_legal_pages':
                return $this->updateLegalPages();
            case 'update_smtp':
                return $this->updateSmtp();
            case 'update_notification':
                return $this->updateNotification();
            case 'update_bank':
                return $this->updateBank();
            case 'update_maintenance':
                return $this->updateMaintenance();
            case 'clear_maintenance':
                return $this->clearMaintenance();
            default:
                return $this->json(['status' => 'error', 'message' => 'Hanh dong khong hop le']);
        }
    }

    /**
     * Update single-row `setting` table using prepared statements.
     *
     * @param array<string, mixed> $fields
     */
    private function updateSettingRow(array $fields): bool
    {
        if (empty($fields)) {
            return false;
        }

        $sets = [];
        $values = [];
        foreach ($fields as $field => $value) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $field)) {
                throw new InvalidArgumentException('Invalid setting field');
            }
            $sets[] = "`{$field}` = ?";
            $values[] = $value;
        }

        $sql = "UPDATE `setting` SET " . implode(', ', $sets) . " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    private function clearSiteConfigCache(): void
    {
        if (class_exists('Config')) {
            Config::clearSiteConfigCache();
        }
    }

    private function ok(string $message): void
    {
        $this->clearSiteConfigCache();
        $this->json(['status' => 'success', 'message' => $message]);
    }

    private function err(string $message): void
    {
        $this->json(['status' => 'error', 'message' => $message]);
    }

    private function updateGeneral()
    {
        $ok = $this->updateSettingRow([
            'ten_web' => trim((string) $this->post('ten_web')),
            'logo' => trim((string) $this->post('logo')),
            'logo_footer' => trim((string) $this->post('logo_footer')),
            'favicon' => trim((string) $this->post('favicon')),
            'mo_ta' => trim((string) $this->post('mo_ta')),
            'fb_admin' => trim((string) $this->post('fb_admin')),
            'sdt_admin' => trim((string) $this->post('sdt_admin')),
            'tele_admin' => trim((string) $this->post('tele_admin')),
            'tiktok_admin' => trim((string) $this->post('tiktok_admin')),
            'youtube_admin' => trim((string) $this->post('youtube_admin')),
            'email_cf' => trim((string) $this->post('email_cf')),
        ]);

        if ($ok) {
            $this->ok('Cap nhat thanh cong!');
        }
        $this->err('Co loi xay ra!');
    }

    private function updateContactPage()
    {
        $ok = $this->updateSettingRow([
            'contact_page_title' => trim((string) $this->post('contact_page_title', '')),
            'contact_page_subtitle' => trim((string) $this->post('contact_page_subtitle', '')),
            'contact_email_label' => trim((string) $this->post('contact_email_label', '')),
            'contact_phone_label' => trim((string) $this->post('contact_phone_label', '')),
            'contact_support_note' => trim((string) $this->post('contact_support_note', '')),
        ]);

        if ($ok) {
            $this->ok('Da luu cau hinh trang lien he.');
        }
        $this->err('Khong the luu cau hinh trang lien he.');
    }

    private function updateLegalPages()
    {
        $fields = [
            'policy_page_title',
            'policy_page_subtitle',
            'policy_content_html',
            'policy_notice_text',
            'terms_page_title',
            'terms_page_subtitle',
            'terms_content_html',
            'terms_notice_text',
        ];

        $payload = [];
        foreach ($fields as $field) {
            $payload[$field] = (string) $this->post($field, '');
        }

        $ok = $this->updateSettingRow($payload);
        if ($ok) {
            $this->ok('Da luu cau hinh Chinh sach / Dieu khoan.');
        }
        $this->err('Khong the luu cau hinh Chinh sach / Dieu khoan.');
    }

    private function updateSmtp()
    {
        $ok = $this->updateSettingRow([
            'ten_nguoi_gui' => trim((string) $this->post('ten_nguoi_gui')),
            'email_auto' => trim((string) $this->post('email_auto')),
            'pass_mail_auto' => trim((string) $this->post('pass_mail_auto')),
        ]);

        if ($ok) {
            $this->ok('Cap nhat cau hinh SMTP thanh cong!');
        }
        $this->err('Co loi xay ra!');
    }

    private function updateNotification()
    {
        $ok = $this->updateSettingRow([
            'thongbao' => (string) $this->post('thongbao'),
            'popup_template' => trim((string) $this->post('popup_template', '1')),
        ]);

        if ($ok) {
            $this->ok('Cap nhat thong bao thanh cong!');
        }
        $this->err('Co loi xay ra!');
    }

    private function updateBank()
    {
        $ok = $this->updateSettingRow([
            'bank_name' => trim((string) $this->post('bank_name', '')),
            'bank_account' => trim((string) $this->post('bank_account', '')),
            'bank_owner' => trim((string) $this->post('bank_owner', '')),
            'sepay_api_key' => trim((string) $this->post('sepay_api_key', '')),
            'bonus_1_amount' => (int) $this->post('bonus_1_amount', 100000),
            'bonus_1_percent' => (int) $this->post('bonus_1_percent', 10),
            'bonus_2_amount' => (int) $this->post('bonus_2_amount', 200000),
            'bonus_2_percent' => (int) $this->post('bonus_2_percent', 15),
            'bonus_3_amount' => (int) $this->post('bonus_3_amount', 500000),
            'bonus_3_percent' => (int) $this->post('bonus_3_percent', 20),
        ]);

        if ($ok) {
            $this->ok('Cap nhat cau hinh ngan hang & khuyen mai thanh cong!');
        }
        $this->err('Co loi xay ra!');
    }

    private function updateMaintenance()
    {
        $result = $this->maintenanceService->saveConfig([
            'maintenance_enabled' => $this->post('maintenance_enabled'),
            'maintenance_start_at' => $this->post('maintenance_start_at'),
            'maintenance_duration_minutes' => $this->post('maintenance_duration_minutes', 60),
            'maintenance_notice_minutes' => $this->post('maintenance_notice_minutes', 5),
            'maintenance_message' => $this->post('maintenance_message'),
        ]);

        return $this->json([
            'status' => !empty($result['success']) ? 'success' : 'error',
            'message' => (string) ($result['message'] ?? 'Khong the cap nhat bao tri'),
        ]);
    }

    private function clearMaintenance()
    {
        $result = $this->maintenanceService->clearNow();

        return $this->json([
            'status' => !empty($result['success']) ? 'success' : 'error',
            'message' => (string) ($result['message'] ?? 'Khong the ket thuc bao tri'),
        ]);
    }
}

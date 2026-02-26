<?php
/**
 * Legacy compatibility bootstrap (required by config/app.php).
 * Restores globals/helpers used across legacy includes and hybrid MVC pages.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', BASE_PATH);
}

if (!defined('HETHONG_PATH')) {
    define('HETHONG_PATH', BASE_PATH . '/hethong');
}

if (!class_exists('EnvHelper') && file_exists(BASE_PATH . '/app/Helpers/EnvHelper.php')) {
    require_once BASE_PATH . '/app/Helpers/EnvHelper.php';
    EnvHelper::load(BASE_PATH . '/.env');
}

if (!defined('APP_DIR')) {
    define('APP_DIR', (string) (class_exists('EnvHelper') ? EnvHelper::get('APP_DIR', '') : ''));
}

require_once BASE_PATH . '/database/connection.php';
require_once BASE_PATH . '/hethong/UrlHelper.php';

if (!class_exists('Config')) {
    class Config
    {
        private static ?array $siteConfigCache = null;

        public static function getSiteConfig(bool $forceRefresh = false): array
        {
            global $connection;

            if (!$forceRefresh && self::$siteConfigCache !== null) {
                return self::$siteConfigCache;
            }

            $defaults = [
                'ten_web' => 'KaiShop',
                'mo_ta' => 'Dịch vụ số chất lượng cao.',
                'logo' => '',
                'logo_footer' => '',
                'favicon' => '',
                'banner' => '',
                'key_words' => '',
                'sdt_admin' => '',
                'email_cf' => '',
                'contact_page_title' => '',
                'contact_page_subtitle' => '',
                'contact_email_label' => '',
                'contact_phone_label' => '',
                'contact_support_note' => '',
                'policy_page_title' => '',
                'policy_page_subtitle' => '',
                'policy_content_html' => '',
                'policy_notice_text' => '',
                'terms_page_title' => '',
                'terms_page_subtitle' => '',
                'terms_content_html' => '',
                'terms_notice_text' => '',
                'bank_name' => 'MB Bank',
                'bank_account' => '09696969690',
                'bank_owner' => 'NGUYEN THANH PHUC',
                'sepay_api_key' => '',
                'tele_admin' => 'https://t.me/kaishop25',
                'youtube_admin' => 'https://www.youtube.com/@KaiOfficial-0x',
                'tiktok_admin' => 'https://www.tiktok.com/@kai_01s.',
            ];

            if (!($connection instanceof mysqli)) {
                self::$siteConfigCache = $defaults;
                return self::$siteConfigCache;
            }

            try {
                $result = $connection->query("SELECT * FROM setting LIMIT 1");
                $row = $result ? ($result->fetch_assoc() ?: []) : [];
                self::$siteConfigCache = array_merge($defaults, is_array($row) ? $row : []);
            } catch (Throwable $e) {
                self::$siteConfigCache = $defaults;
            }

            return self::$siteConfigCache;
        }

        public static function clearSiteConfigCache(): void
        {
            self::$siteConfigCache = null;
        }
    }
}

if (!function_exists('check_string')) {
    function check_string($data)
    {
        return trim((string) $data);
    }
}

if (!function_exists('format_cash')) {
    function format_cash($number): string
    {
        return number_format((float) $number, 0, ',', '.');
    }
}

if (!function_exists('tien')) {
    function tien($number): string
    {
        return format_cash($number);
    }
}

if (!function_exists('get_setting')) {
    function get_setting(string $key, $default = '')
    {
        global $chungapi;
        if (!is_array($chungapi)) {
            $chungapi = Config::getSiteConfig();
        }

        if (array_key_exists($key, $chungapi)) {
            return $chungapi[$key];
        }

        $siteConfig = Config::getSiteConfig();
        return array_key_exists($key, $siteConfig) ? $siteConfig[$key] : $default;
    }
}

if (!function_exists('myip')) {
    function myip(): string
    {
        return (string) ($_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}

if (!function_exists('xoadau')) {
    function xoadau(string $str): string
    {
        $str = trim($str);
        if ($str === '') {
            return '';
        }

        $map = [
            'à' => 'a',
            'á' => 'a',
            'ạ' => 'a',
            'ả' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ầ' => 'a',
            'ấ' => 'a',
            'ậ' => 'a',
            'ẩ' => 'a',
            'ẫ' => 'a',
            'ă' => 'a',
            'ằ' => 'a',
            'ắ' => 'a',
            'ặ' => 'a',
            'ẳ' => 'a',
            'ẵ' => 'a',
            'è' => 'e',
            'é' => 'e',
            'ẹ' => 'e',
            'ẻ' => 'e',
            'ẽ' => 'e',
            'ê' => 'e',
            'ề' => 'e',
            'ế' => 'e',
            'ệ' => 'e',
            'ể' => 'e',
            'ễ' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'ị' => 'i',
            'ỉ' => 'i',
            'ĩ' => 'i',
            'ò' => 'o',
            'ó' => 'o',
            'ọ' => 'o',
            'ỏ' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ồ' => 'o',
            'ố' => 'o',
            'ộ' => 'o',
            'ổ' => 'o',
            'ỗ' => 'o',
            'ơ' => 'o',
            'ờ' => 'o',
            'ớ' => 'o',
            'ợ' => 'o',
            'ở' => 'o',
            'ỡ' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'ụ' => 'u',
            'ủ' => 'u',
            'ũ' => 'u',
            'ư' => 'u',
            'ừ' => 'u',
            'ứ' => 'u',
            'ự' => 'u',
            'ử' => 'u',
            'ữ' => 'u',
            'ỳ' => 'y',
            'ý' => 'y',
            'ỵ' => 'y',
            'ỷ' => 'y',
            'ỹ' => 'y',
            'đ' => 'd',
            'À' => 'a',
            'Á' => 'a',
            'Ạ' => 'a',
            'Ả' => 'a',
            'Ã' => 'a',
            'Â' => 'a',
            'Ầ' => 'a',
            'Ấ' => 'a',
            'Ậ' => 'a',
            'Ẩ' => 'a',
            'Ẫ' => 'a',
            'Ă' => 'a',
            'Ằ' => 'a',
            'Ắ' => 'a',
            'Ặ' => 'a',
            'Ẳ' => 'a',
            'Ẵ' => 'a',
            'È' => 'e',
            'É' => 'e',
            'Ẹ' => 'e',
            'Ẻ' => 'e',
            'Ẽ' => 'e',
            'Ê' => 'e',
            'Ề' => 'e',
            'Ế' => 'e',
            'Ệ' => 'e',
            'Ể' => 'e',
            'Ễ' => 'e',
            'Ì' => 'i',
            'Í' => 'i',
            'Ị' => 'i',
            'Ỉ' => 'i',
            'Ĩ' => 'i',
            'Ò' => 'o',
            'Ó' => 'o',
            'Ọ' => 'o',
            'Ỏ' => 'o',
            'Õ' => 'o',
            'Ô' => 'o',
            'Ồ' => 'o',
            'Ố' => 'o',
            'Ộ' => 'o',
            'Ổ' => 'o',
            'Ỗ' => 'o',
            'Ơ' => 'o',
            'Ờ' => 'o',
            'Ớ' => 'o',
            'Ợ' => 'o',
            'Ở' => 'o',
            'Ỡ' => 'o',
            'Ù' => 'u',
            'Ú' => 'u',
            'Ụ' => 'u',
            'Ủ' => 'u',
            'Ũ' => 'u',
            'Ư' => 'u',
            'Ừ' => 'u',
            'Ứ' => 'u',
            'Ự' => 'u',
            'Ử' => 'u',
            'Ữ' => 'u',
            'Ỳ' => 'y',
            'Ý' => 'y',
            'Ỵ' => 'y',
            'Ỷ' => 'y',
            'Ỹ' => 'y',
            'Đ' => 'd',
        ];
        $str = strtr($str, $map);
        $str = strtolower($str);
        $str = preg_replace('/[^a-z0-9]+/i', '-', $str) ?? '';
        return trim($str, '-');
    }
}

if (!function_exists('display_banned_page')) {
    function display_banned_page(string $reason = ''): void
    {
        global $chungapi;
        if ($reason !== '') {
            $_SESSION['banned_reason'] = $reason;
        }
        http_response_code(403);
        if (file_exists(BASE_PATH . '/banned.php')) {
            require BASE_PATH . '/banned.php';
        } elseif (file_exists(BASE_PATH . '/403.php')) {
            require BASE_PATH . '/403.php';
        } else {
            echo 'Tai khoan/thiết bị bị khóa.';
        }
        exit;
    }
}

global $chungapi, $user, $username;
$chungapi = Config::getSiteConfig();
$user = null;
$username = null;
$ip_address = myip();

// Bootstrap current user from secure auth cookies/session for legacy includes/pages.
try {
    if (class_exists('AuthService')) {
        $authService = new AuthService();
        if ($authService->isLoggedIn()) {
            $user = $authService->getCurrentUser();
            $username = is_array($user) ? (string) ($user['username'] ?? '') : null;
            if (is_array($user) && (int) ($user['level'] ?? 0) === 9) {
                $_SESSION['admin'] = (string) ($user['username'] ?? 'admin');
            } elseif (isset($_SESSION['admin'])) {
                unset($_SESSION['admin']);
            }
        }
    }
} catch (Throwable $e) {
    // Non-blocking for public pages.
}

// Maintenance gate for legacy/public requests (admin bypass).
try {
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
    $appDir = rtrim((string) APP_DIR, '/');
    if ($appDir !== '' && strpos($requestPath, $appDir) === 0) {
        $requestPath = substr($requestPath, strlen($appDir));
        if ($requestPath === false || $requestPath === '') {
            $requestPath = '/';
        }
    }
    if ($requestPath[0] !== '/') {
        $requestPath = '/' . $requestPath;
    }

    $isAdminSession = !empty($_SESSION['admin']);
    $isAdminPath = strpos($requestPath, '/admin') === 0;
    $maintenanceWhitelist = [
        '/bao-tri',
        '/api/system/maintenance-status',
        '/api/sepay/webhook',
    ];
    $isWhitelisted = in_array($requestPath, $maintenanceWhitelist, true);

    if (!$isAdminSession && !$isAdminPath && !$isWhitelisted && class_exists('MaintenanceService')) {
        $maintenanceService = new MaintenanceService();
        $state = $maintenanceService->getState(true);
        if (!empty($state['active'])) {
            header('Location: ' . url('bao-tri'));
            exit;
        }
    }
} catch (Throwable $e) {
    // Non-blocking
}

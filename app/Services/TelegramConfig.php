<?php

/**
 * TelegramConfig — Centralized OOP configuration & constants for the Telegram Bot ecosystem
 *
 * Mục đích:
 *  - Tập trung MỌI hằng số (constants) và giá trị mặc định liên quan Telegram
 *  - Không còn magic numbers rải rác trong TelegramBotService / TelegramBotController
 *  - Cung cấp typed getter để đọc cả từ .env lẫn database setting
 *
 * Cách dùng:
 *   $token     = TelegramConfig::botToken();
 *   $adminIds  = TelegramConfig::adminIds();  // trả về int[]
 *   $rateLimit = TelegramConfig::rateLimit();
 */
final class TelegramConfig
{
    // ================================================================
    // RATE LIMITING
    // ================================================================

    /** Số lệnh tối đa / user / phút */
    public const RATE_LIMIT_DEFAULT = 30;

    /** Cửa sổ rate-limit tính bằng giây */
    public const RATE_LIMIT_WINDOW = 60;

    /** Số request tối đa / IP / phút (từ phía Telegram servers) */
    public const IP_RATE_LIMIT_MAX = 120;

    /** Timeout cURL cho mỗi API call (giây) */
    public const CURL_TIMEOUT = 5;

    /** Connect timeout cURL (giây) */
    public const CURL_CONNECT_TIMEOUT = 3;

    // ================================================================
    // PURCHASE COOLDOWN
    // ================================================================

    /** Cooldown mặc định giữa 2 lần mua (giây) */
    public const BUY_COOLDOWN_DEFAULT = 10;

    /** TTL phiên mua hàng Telegram (giây) */
    public const PURCHASE_SESSION_TTL_DEFAULT = 900; // 15 phút

    // ================================================================
    // OUTBOX WORKER
    // ================================================================

    /** Số message tối đa lấy mỗi lần cron chạy */
    public const OUTBOX_BATCH_SIZE = 30;

    /** Số lần retry tối đa trước khi đánh dấu fail */
    public const OUTBOX_MAX_RETRIES = 3;

    /** Giữ lại message đã gửi trong X ngày trước khi cleanup */
    public const OUTBOX_RETENTION_DAYS = 7;

    // ================================================================
    // RATE-LIMIT FILE STORAGE
    // ================================================================

    /**
     * Thư mục lưu rate-limit files — dùng sys_get_temp_dir() để tương thích cả Windows lẫn Linux
     */
    public static function rateDir(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kaishop_tg_rl';
    }

    /** Cooldown sub-directory */
    public static function cooldownDir(): string
    {
        return self::rateDir() . DIRECTORY_SEPARATOR . 'cooldown';
    }

    // ================================================================
    // OTP
    // ================================================================

    /** Thời gian sống của OTP link code (giây) */
    public const OTP_TTL_SECONDS = 300; // 5 phút

    // ================================================================
    // CACHE TTL (AppCache)
    // ================================================================

    /** TTL cache getMe() của TelegramService (giây) */
    public const CACHE_TTL_GET_ME = 300; // 5 phút

    /** TTL cache getWebhookInfo() (giây) */
    public const CACHE_TTL_WEBHOOK = 60;  // 1 phút

    // ================================================================
    // TYPED GETTERS (đọc từ DB setting + .env fallback)
    // ================================================================

    /**
     * Bot token — ưu tiên .env, fallback DB
     */
    public static function botToken(): string
    {
        $env = self::env('TELEGRAM_BOT_TOKEN');
        if ($env !== '')
            return $env;
        return trim((string) get_setting('telegram_bot_token', ''));
    }

    /**
     * Admin Chat ID chính (primary admin)
     */
    public static function primaryAdminId(): int
    {
        $env = self::env('TELEGRAM_CHAT_ID');
        if ($env !== '')
            return (int) $env;
        return (int) get_setting('telegram_chat_id', 0);
    }

    /**
     * Tất cả Admin IDs (bao gồm primary + telegram_admin_ids)
     * @return int[]
     */
    public static function adminIds(): array
    {
        $ids = [];

        // Primary admin
        $primary = self::primaryAdminId();
        if ($primary > 0)
            $ids[] = $primary;

        // Multiple admins từ DB
        $extra = trim((string) get_setting('telegram_admin_ids', ''));
        if ($extra !== '') {
            foreach (explode(',', $extra) as $id) {
                $id = (int) trim($id);
                if ($id > 0 && !in_array($id, $ids, true)) {
                    $ids[] = $id;
                }
            }
        }

        return $ids;
    }

    /**
     * Kiểm tra 1 telegram_id có phải admin không
     */
    public static function isAdmin(int $telegramId): bool
    {
        return in_array($telegramId, self::adminIds(), true);
    }

    /**
     * Webhook secret token
     * Ưu tiên DB (admin có thể đổi qua Settings) → fallback .env
     * QUAN TRỌNG: phải dùng cùng 1 nguồn với lúc setWebhook, không được đọc 2 nguồn khác nhau.
     */
    public static function webhookSecret(): string
    {
        // DB trước — admin đổi trong Settings là có hiệu lực ngay
        $db = trim((string) get_setting('telegram_webhook_secret', ''));
        if ($db !== '')
            return $db;
        // Fallback .env nếu DB chưa có giá trị
        return self::env('TELEGRAM_WEBHOOK_SECRET');
    }

    /**
     * Rate limit — số lệnh/user/phút (lấy từ DB setting hoặc constant mặc định)
     */
    public static function rateLimit(): int
    {
        return (int) get_setting('telegram_rate_limit', self::RATE_LIMIT_DEFAULT);
    }

    /**
     * Purchase cooldown (giây)
     */
    public static function buyCooldown(): float
    {
        return 1.5;
    }

    /**
     * Purchase session TTL (giây) cho flow mua hàng Telegram.
     * Clamp trong khoảng [120s .. 3600s] để tránh config lỗi.
     */
    public static function purchaseSessionTtl(): int
    {
        $ttl = (int) get_setting('telegram_purchase_session_ttl', self::PURCHASE_SESSION_TTL_DEFAULT);
        return max(120, min(3600, $ttl));
    }

    /**
     * Kiểm tra trạng thái bảo trì riêng của Bot Telegram
     */
    public static function isMaintenanceEnabled(): bool
    {
        return (int) get_setting('telegram_maintenance_enabled', 0) === 1;
    }

    /**
     * Nội dung tin nhắn khi Bot đang bảo trì
     */
    public static function maintenanceMessage(): string
    {
        $msg = trim((string) get_setting('telegram_maintenance_message', ''));
        if ($msg === '') {
            return "🛠 <b>HỆ THỐNG ĐANG BẢO TRÌ</b>\nChúng tôi đang nâng cấp dịch vụ, vui lòng quay lại sau.";
        }
        return $msg;
    }

    /**
     * Webhook URL production — random-named path để tránh scan
     * Path ngẫu nhiên: /api/bot_{secret}/index.php
     * Nếu muốn đổi path, sửa WEBHOOK_PATH_SEGMENT bên dưới + di chuyển file.
     */
    public const WEBHOOK_PATH_SEGMENT = 'bottelekaishop_7f3kx9m2p4a2Agr3';

    public static function webhookUrl(): string
    {
        $base = rtrim(defined('BASE_URL') ? BASE_URL : (string) self::env('BASE_URL'), '/');
        // Ưu tiên route-based nếu server hỗ trợ; sử dụng random-named path mặc định
        return $base . '/api/' . self::WEBHOOK_PATH_SEGMENT . '/index.php';
    }

    // ================================================================
    // INTERNAL
    // ================================================================

    /** Đọc biến môi trường */
    private static function env(string $key): string
    {
        if (class_exists('EnvHelper')) {
            $v = trim((string) EnvHelper::get($key, ''));
            if ($v !== '')
                return $v;
        }
        $v = trim((string) ($_ENV[$key] ?? ''));
        if ($v !== '')
            return $v;
        return trim((string) getenv($key));
    }

    /** Không cho khởi tạo — utility class */
    private function __construct()
    {
    }
}

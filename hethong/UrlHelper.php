<?php
/**
 * URL Helper Class - Centralized URL Management
 * Chỉ cần đổi APP_DIR trong config.php
 */
class UrlHelper
{
    private static $BASE_PATH = null;

    /**
     * Tự động lấy BASE_PATH từ APP_DIR constant
     */
    private static function getBasePath()
    {
        if (self::$BASE_PATH === null) {
            // Nếu APP_DIR đã được define trong config.php, dùng nó
            if (defined('APP_DIR')) {
                self::$BASE_PATH = APP_DIR;
            } else {
                // Fallback: tự động detect
                self::$BASE_PATH = '';
            }
        }
        return self::$BASE_PATH;
    }
    // ====================================

    /**
     * Get base URL path
     */
    public static function base($path = '')
    {
        $cleanPath = ltrim($path, '/');
        return self::getBasePath() . ($cleanPath ? '/' . $cleanPath : '');
    }

    /**
     * Get asset URL (css, js, images)  
     */
    public static function asset($path)
    {
        return self::base(ltrim($path, '/'));
    }

    /**
     * Get AJAX endpoint URL
     */
    public static function ajax($path)
    {
        return self::base(ltrim($path, '/'));
    }

    /**
     * Get page URL
     */
    public static function page($path)
    {
        return self::base(ltrim($path, '/'));
    }

    /**
     * Redirect to a URL
     */
    public static function redirect($path)
    {
        header('Location: ' . self::base($path));
        exit();
    }

    /**
     * Get current URL
     */
    public static function current()
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Check if current URL matches
     */
    public static function is($path)
    {
        return self::current() === self::base($path);
    }
}

// Global helper functions
function url($path = '')
{
    return UrlHelper::base($path);
}

function asset($path)
{
    return UrlHelper::asset($path);
}

function ajax_url($path)
{
    return UrlHelper::ajax($path);
}
?>
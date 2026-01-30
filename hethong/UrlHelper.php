<?php
/**
 * URL Helper Class - Centralized URL Management
 * Chỉ cần đổi BASE_PATH khi deploy lên production
 */
class UrlHelper
{
    // ===== THAY ĐỔI DUY NHẤT Ở ĐÂY =====
    // Local: '/kaishop_v2'
    // Production: '' (để trống)
    private static $BASE_PATH = '/kaishop_v2';

    /**
     * Tự động cấu hình BASE_PATH dựa trên môi trường
     */
    public static function init()
    {
        $localhosts = ['localhost', '127.0.0.1', '::1'];
        if (!in_array($_SERVER['HTTP_HOST'], $localhosts)) {
            self::$BASE_PATH = '';
        }
    }
    // ====================================

    /**
     * Get base URL path
     */
    public static function base($path = '')
    {
        $cleanPath = ltrim($path, '/');
        return self::$BASE_PATH . ($cleanPath ? '/' . $cleanPath : '');
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
UrlHelper::init();
?>
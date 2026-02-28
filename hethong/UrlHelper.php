<?php
/**
 * URL Helper Class - Centralized URL Management
 * Chỉ cần đổi APP_DIR trong config.php
 */
class UrlHelper
{
    private static $BASE_PATH = null;
    private static $ASSET_VERSION_CACHE = [];

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
        return self::base(self::appendAssetVersion(ltrim((string) $path, '/')));
    }

    private static function appendAssetVersion(string $path): string
    {
        if ($path === '' || preg_match('~^(?:https?:)?//|^(?:data|blob):~i', $path)) {
            return $path;
        }

        $parts = parse_url($path);
        if ($parts === false) {
            return $path;
        }

        $query = (string) ($parts['query'] ?? '');
        if ($query !== '') {
            parse_str($query, $queryParams);
            if (isset($queryParams['v'])) {
                return $path;
            }
        }

        $cleanPath = ltrim((string) ($parts['path'] ?? $path), '/');
        if ($cleanPath === '') {
            return $path;
        }

        if (isset(self::$ASSET_VERSION_CACHE[$cleanPath])) {
            $version = self::$ASSET_VERSION_CACHE[$cleanPath];
        } else {
            $localPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanPath);
            $version = is_file($localPath) ? (string) (@filemtime($localPath) ?: '') : '';
            self::$ASSET_VERSION_CACHE[$cleanPath] = $version;
        }

        if ($version === '') {
            return $path;
        }

        return $path . ($query !== '' ? '&' : '?') . 'v=' . rawurlencode($version);
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

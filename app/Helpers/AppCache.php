<?php

/**
 * AppCache — File-based general-purpose cache helper
 *
 * Thay thế SimpleCache với tên rõ ràng hơn + cải tiến:
 *  - Atomic write (LOCK_EX) tránh race condition
 *  - has() để kiểm tra mà không deserialize
 *  - flush() để xóa toàn bộ cache
 *  - Cấu hình cache dir qua hằng số CACHE_DIR (nếu có)
 */
class AppCache
{
    private static string $cacheDir = '';

    // ----------------------------------------------------------------
    // Public API
    // ----------------------------------------------------------------

    /**
     * Đọc cache. Trả về null nếu miss hoặc hết TTL.
     *
     * @template T
     * @param string $key    Cache key
     * @param int    $ttl    Seconds — 0 = không kiểm tra TTL
     * @return T|null
     */
    public static function get(string $key, int $ttl = 300)
    {
        $file = self::filePath($key);

        if (!file_exists($file)) {
            return null;
        }

        if ($ttl > 0 && (time() - (int) @filemtime($file)) >= $ttl) {
            @unlink($file); // dọn sớm
            return null;
        }

        $raw = @file_get_contents($file);
        if ($raw === false || $raw === '') {
            return null;
        }

        $value = @unserialize($raw);
        return ($value === false && $raw !== serialize(false)) ? null : $value;
    }

    /**
     * Ghi cache (atomic write với LOCK_EX).
     *
     * @param string $key
     * @param mixed  $value
     */
    public static function set(string $key, $value): void
    {
        $dir = self::cacheDir();
        $file = $dir . '/' . self::hash($key) . '.cache';

        @file_put_contents($file, serialize($value), LOCK_EX);
    }

    /**
     * Kiểm tra cache có tồn tại và còn hạn không (không deserialize).
     */
    public static function has(string $key, int $ttl = 300): bool
    {
        $file = self::filePath($key);
        if (!file_exists($file))
            return false;
        if ($ttl > 0 && (time() - (int) @filemtime($file)) >= $ttl)
            return false;
        return true;
    }

    /**
     * Xóa 1 key.
     */
    public static function delete(string $key): void
    {
        $file = self::filePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Xóa toàn bộ cache directory.
     */
    public static function flush(): int
    {
        $count = 0;
        $dir = self::cacheDir();
        foreach (glob($dir . '/*.cache') ?: [] as $file) {
            if (@unlink($file))
                $count++;
        }
        return $count;
    }

    /**
     * Xóa các file cache đã hết hạn (dùng trong cron cleanup).
     */
    public static function gc(int $maxAge = 3600): int
    {
        $count = 0;
        $cutoff = time() - $maxAge;
        $dir = self::cacheDir();
        foreach (glob($dir . '/*.cache') ?: [] as $file) {
            if (@filemtime($file) < $cutoff && @unlink($file)) {
                $count++;
            }
        }
        return $count;
    }

    // ----------------------------------------------------------------
    // Internal helpers
    // ----------------------------------------------------------------

    private static function cacheDir(): string
    {
        if (self::$cacheDir !== '') {
            return self::$cacheDir;
        }

        // Hỗ trợ hằng số CACHE_DIR từ app config
        if (defined('CACHE_DIR') && is_string(CACHE_DIR) && CACHE_DIR !== '') {
            self::$cacheDir = rtrim(CACHE_DIR, '/\\');
        } else {
            self::$cacheDir = rtrim(__DIR__ . '/../../storage/cache', '/\\');
        }

        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0755, true);
        }

        return self::$cacheDir;
    }

    private static function hash(string $key): string
    {
        return md5($key);
    }

    private static function filePath(string $key): string
    {
        return self::cacheDir() . '/' . self::hash($key) . '.cache';
    }
}

/**
 * Backward-compatibility alias — giữ lại để không phá code cũ.
 * @deprecated Dùng AppCache thay thế.
 */
if (!class_exists('SimpleCache')) {
    class_alias('AppCache', 'SimpleCache');
}

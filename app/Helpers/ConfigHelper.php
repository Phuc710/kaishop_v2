<?php

/**
 * Configuration Helper
 * Centralized configuration and helper functions
 */
class Config {
    
    /**
     * Get site configuration
     * @return array
     */
    public static function getSiteConfig() {
        global $chungapi;
        return $chungapi;
    }
    
    /**
     * Get database connection (mysqli - for old code compatibility)
     * @return mysqli
     */
    public static function getDbConnection() {
        global $ketnoi;
        return $ketnoi;
    }
    
    /**
     * Format currency (Vietnamese Dong)
     * @param int $amount
     * @return string
     */
    public static function formatMoney($amount) {
        return number_format($amount, 0, ',', '.');
    }
    
    /**
     * Get user level name
     * @param int $level
     * @return string
     */
    public static function getLevelName($level) {
        $levels = [
            0 => 'Member',
            1 => 'VIP',
            2 => 'Premium',
            9 => 'Admin'
        ];
        return $levels[$level] ?? 'Unknown';
    }
    
    /**
     * Sanitize input (anti-XSS)
     * @param string $data
     * @return string
     */
    public static function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

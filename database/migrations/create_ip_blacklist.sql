-- AntiFlood: IP Blacklist table
-- Run this SQL to create the ip_blacklist table for auto-banning

CREATE TABLE IF NOT EXISTS `ip_blacklist` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `reason` VARCHAR(500) DEFAULT NULL,
    `banned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME DEFAULT NULL COMMENT 'NULL = permanent ban',
    `hit_count` INT UNSIGNED NOT NULL DEFAULT 1,
    `user_agent` VARCHAR(1000) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_ip` (`ip_address`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

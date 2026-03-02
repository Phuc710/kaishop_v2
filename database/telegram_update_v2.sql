-- ============================================================
-- SQL UPDATE SCRIPT FOR ADVANCED TELEGRAM NOTIFICATIONS
-- Run this if you are upgrading from an older version.
-- ============================================================

-- 1. Create Extra Notification Channels Table
CREATE TABLE IF NOT EXISTS `telegram_notification_channels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `chat_id` VARCHAR(64) NOT NULL,
  `label` VARCHAR(100) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_chat_id` (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create Universal User Tracking Table
CREATE TABLE IF NOT EXISTS `telegram_users` (
  `telegram_id` BIGINT PRIMARY KEY,
  `username` VARCHAR(64) NULL,
  `first_name` VARCHAR(255) NULL,
  `is_blocked` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `last_seen_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Ensure necessary columns exist in the 'setting' table
-- (Note: telegram_admin_ids and telegram_order_cooldown should already exist, but we ensure here)
SET @dbname = DATABASE();
SET @tablename = 'setting';

-- Add telegram_admin_ids if missing
SET @colname = 'telegram_admin_ids';
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = @colname) = 0,
    CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @colname, '` TEXT NULL COMMENT "Multiple IDs separated by comma" AFTER `telegram_bot_user`'),
    'SELECT "Column already exists"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add telegram_order_cooldown if missing
SET @colname = 'telegram_order_cooldown';
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = @colname) = 0,
    CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @colname, '` INT NOT NULL DEFAULT 300 AFTER `', @colname, '`'),
    'SELECT "Column already exists"'
);
-- Note: Fixed logic for 'AFTER' to be safer
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = @colname) = 0,
    CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @colname, '` INT NOT NULL DEFAULT 300'),
    'SELECT "Column already exists"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

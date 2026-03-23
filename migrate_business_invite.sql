-- Database migration for business_invite_auto feature
-- Safe for MySQL/MariaDB: only adds columns if they do not already exist.

-- 1. products table
SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'products'
      AND COLUMN_NAME = 'duration_days'
);
SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `products` ADD COLUMN `duration_days` INT NOT NULL DEFAULT 30 AFTER `info_instructions`',
    'SELECT ''products.duration_days already exists'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'products'
      AND COLUMN_NAME = 'auto_invite'
);
SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `products` ADD COLUMN `auto_invite` TINYINT(1) NOT NULL DEFAULT 1 AFTER `duration_days`',
    'SELECT ''products.auto_invite already exists'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'products'
      AND COLUMN_NAME = 'farm_id'
);
SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `products` ADD COLUMN `farm_id` INT NULL AFTER `auto_invite`',
    'SELECT ''products.farm_id already exists'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- product_type is VARCHAR(50), so no SQL change is required here.

-- 2. chatgpt_orders table
SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'chatgpt_orders'
      AND COLUMN_NAME = 'source_order_id'
);
SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `chatgpt_orders` ADD COLUMN `source_order_id` BIGINT UNSIGNED NULL AFTER `assigned_farm_id`',
    'SELECT ''chatgpt_orders.source_order_id already exists'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

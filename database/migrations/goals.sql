-- ============================================================
-- Migration: Financial Goals Module
-- Tables: goals, goal_transactions, goal_tags
-- Created: 2026-04-17
-- ============================================================

CREATE TABLE IF NOT EXISTS `goals` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(255) NOT NULL,
  `target_amount`  BIGINT NOT NULL DEFAULT 0,
  `current_amount` BIGINT NOT NULL DEFAULT 0,
  `deadline`       DATE NULL,
  `status`         ENUM('active','completed','archived') NOT NULL DEFAULT 'active',
  `note`           LONGTEXT NULL,
  `emoji`          VARCHAR(10) NOT NULL DEFAULT '🎯',
  `color`          VARCHAR(20) NOT NULL DEFAULT '#845adf',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_goals_status` (`status`),
  KEY `idx_goals_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `goal_transactions` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `goal_id`    INT UNSIGNED NOT NULL,
  `type`       ENUM('add','subtract') NOT NULL DEFAULT 'add',
  `amount`     BIGINT NOT NULL,
  `note`       VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gt_goal` (`goal_id`),
  KEY `idx_gt_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `goal_tags` (
  `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `goal_id`  INT UNSIGNED NOT NULL,
  `tag_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tags_goal` (`goal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

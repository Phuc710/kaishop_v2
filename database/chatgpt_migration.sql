-- ============================================================
-- ChatGPT Pro Business Farm - Database Migration
-- Version: 1.0
-- Created: 2026-03-18
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Table: chatgpt_farms
-- Mỗi farm = 1 admin + 4 slot user
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatgpt_farms` (
  `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `farm_name`     VARCHAR(120)     NOT NULL,
  `admin_email`   VARCHAR(255)     NOT NULL,
  `admin_api_key` TEXT             NOT NULL COMMENT 'Encrypted admin API key',
  `seat_total`    TINYINT UNSIGNED NOT NULL DEFAULT 4,
  `seat_used`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `status`        ENUM('active','full','locked') NOT NULL DEFAULT 'active',
  `last_sync_at`  DATETIME         DEFAULT NULL,
  `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_seat` (`status`, `seat_used`, `seat_total`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: chatgpt_orders
-- Đơn mua hàng
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatgpt_orders` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code`       VARCHAR(32)  NOT NULL UNIQUE,
  `customer_email`   VARCHAR(255) NOT NULL,
  `product_code`     VARCHAR(80)  NOT NULL DEFAULT 'chatgpt_pro_add_farm_1_month',
  `status`           ENUM('pending','inviting','active','failed','revoked') NOT NULL DEFAULT 'pending',
  `assigned_farm_id` INT UNSIGNED DEFAULT NULL,
  `expires_at`       DATETIME     DEFAULT NULL COMMENT 'When the 1-month access expires',
  `note`             TEXT         DEFAULT NULL,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`customer_email`),
  KEY `idx_status` (`status`),
  KEY `idx_farm` (`assigned_farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: chatgpt_allowed_invites
-- Danh sách invite hợp lệ (whitelist sống còn)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatgpt_allowed_invites` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`     INT UNSIGNED NOT NULL,
  `farm_id`      INT UNSIGNED NOT NULL,
  `target_email` VARCHAR(255) NOT NULL,
  `invite_id`    VARCHAR(120) DEFAULT NULL COMMENT 'OpenAI invite ID returned by API',
  `status`       ENUM('pending','accepted','expired','revoked') NOT NULL DEFAULT 'pending',
  `created_by`   VARCHAR(50)  NOT NULL DEFAULT 'system',
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_farm_email` (`farm_id`, `target_email`),
  KEY `idx_order` (`order_id`),
  KEY `idx_invite_id` (`invite_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: chatgpt_farm_members_snapshot
-- Snapshot thành viên hiện tại
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatgpt_farm_members_snapshot` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `farm_id`      INT UNSIGNED NOT NULL,
  `openai_user_id` VARCHAR(120) DEFAULT NULL,
  `email`        VARCHAR(255) NOT NULL,
  `role`         VARCHAR(50)  NOT NULL DEFAULT 'reader',
  `status`       VARCHAR(50)  NOT NULL DEFAULT 'active',
  `source`       ENUM('approved','detected_unknown') NOT NULL DEFAULT 'detected_unknown',
  `first_seen_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen_at`  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uidx_farm_email` (`farm_id`, `email`),
  KEY `idx_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: chatgpt_farm_invites_snapshot
-- Snapshot invite hiện tại
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatgpt_farm_invites_snapshot` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `farm_id`      INT UNSIGNED NOT NULL,
  `invite_id`    VARCHAR(120) NOT NULL,
  `email`        VARCHAR(255) NOT NULL,
  `role`         VARCHAR(50)  NOT NULL DEFAULT 'reader',
  `status`       VARCHAR(50)  NOT NULL DEFAULT 'pending',
  `source`       ENUM('approved','detected_unknown') NOT NULL DEFAULT 'detected_unknown',
  `first_seen_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen_at`  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uidx_farm_invite` (`farm_id`, `invite_id`),
  KEY `idx_farm_email` (`farm_id`, `email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: chatgpt_violations
-- Bản ghi vi phạm
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatgpt_violations` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `farm_id`      INT UNSIGNED NOT NULL,
  `email`        VARCHAR(255) NOT NULL,
  `type`         VARCHAR(80)  NOT NULL COMMENT 'unauthorized_invite | unauthorized_member | self_invite_violation',
  `severity`     ENUM('low','medium','high','critical') NOT NULL DEFAULT 'high',
  `reason`       TEXT         DEFAULT NULL,
  `action_taken` VARCHAR(120) DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_farm` (`farm_id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: chatgpt_audit_logs
-- Audit trail đầy đủ
-- Format: YYYY-MM-DD HH:MM:SS | FARM | ACTION | TARGET | RESULT
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatgpt_audit_logs` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `farm_id`      INT UNSIGNED DEFAULT NULL,
  `farm_name`    VARCHAR(120) DEFAULT NULL,
  `action`       VARCHAR(100) NOT NULL COMMENT 'SYSTEM_INVITE_CREATED | INVITE_REVOKED_UNAUTHORIZED | MEMBER_REMOVED_UNAUTHORIZED | MEMBER_REMOVED_POLICY',
  `actor_email`  VARCHAR(255) DEFAULT NULL,
  `target_email` VARCHAR(255) DEFAULT NULL,
  `result`       ENUM('OK','FAIL','SKIPPED') NOT NULL DEFAULT 'OK',
  `reason`       VARCHAR(255) DEFAULT NULL,
  `meta_json`    JSON         DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_farm` (`farm_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

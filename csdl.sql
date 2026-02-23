
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET NAMES utf8mb4;

START TRANSACTION;
SET FOREIGN_KEY_CHECKS = 0;


CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL COMMENT 'URL ảnh icon danh mục',
  `description` text DEFAULT NULL COMMENT 'Mô tả SEO',
  `display_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự ưu tiên (nhỏ = cao)',
  `status` varchar(10) NOT NULL DEFAULT 'ON',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_categories_status` (`status`),
  KEY `idx_categories_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `banned_fingerprints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fingerprint_hash` varchar(64) NOT NULL,
  `reason` text DEFAULT NULL,
  `banned_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bf_hash` (`fingerprint_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL COMMENT 'URL slug (auto-gen từ tên)',
  `price` bigint(20) NOT NULL DEFAULT 0,
  `price_vnd` bigint(20) NOT NULL DEFAULT 0,
  `old_price` bigint(20) NOT NULL DEFAULT 0,
  `description` longtext DEFAULT NULL,
  `image` text DEFAULT NULL,
  `gallery` text DEFAULT NULL COMMENT 'JSON array chứa nhiều link ảnh',
  `category` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự ưu tiên (nhỏ = cao)',
  `status` varchar(20) NOT NULL DEFAULT 'ON',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_products_slug` (`slug`),
  KEY `idx_products_status` (`status`),
  KEY `idx_products_category` (`category`),
  KEY `idx_products_category_id` (`category_id`),
  KEY `idx_products_hidden` (`is_hidden`),
  KEY `idx_products_pinned` (`is_pinned`),
  KEY `idx_products_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `gift_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `giftcode` varchar(100) NOT NULL,
  `giamgia` int(11) NOT NULL DEFAULT 0,
  `type` varchar(50) NOT NULL DEFAULT 'all',
  `product_ids` text DEFAULT NULL,
  `min_order` bigint(20) NOT NULL DEFAULT 0,
  `max_order` bigint(20) NOT NULL DEFAULT 0,
  `expired_at` datetime DEFAULT NULL,
  `soluong` int(11) NOT NULL DEFAULT 0,
  `dadung` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'ON',
  `time` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_giftcode` (`giftcode`),
  KEY `idx_gift_code_status` (`status`),
  KEY `idx_gift_code_type` (`type`),
  KEY `idx_gift_code_expired` (`expired_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lich_su_mua_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trans_id` varchar(150) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `loaicode` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'thanhcong',
  `time` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lsmc_username` (`username`),
  KEY `idx_lsmc_loaicode` (`loaicode`),
  KEY `idx_lsmc_trans` (`trans_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lich_su_hoat_dong` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `hoatdong` varchar(255) NOT NULL,
  `gia` bigint(20) DEFAULT 0,
  `time` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lshd_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(190) NOT NULL,
  `level` tinyint(4) NOT NULL DEFAULT 0,
  `tong_nap` bigint(20) NOT NULL DEFAULT 0,
  `money` bigint(20) NOT NULL DEFAULT 0,
  `otpcode` varchar(100) DEFAULT NULL,
  `session` varchar(100) DEFAULT NULL,
  `bannd` tinyint(1) NOT NULL DEFAULT 0,
  `ban_reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fingerprint` varchar(64) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `time` varchar(100) DEFAULT NULL,
  `ip` varchar(100) DEFAULT NULL,
  `api_key` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_username` (`username`),
  UNIQUE KEY `uniq_users_email` (`email`),
  UNIQUE KEY `uniq_users_session` (`session`),
  KEY `idx_users_level` (`level`),
  KEY `idx_users_bannd` (`bannd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ten_web` varchar(150) DEFAULT 'KaiShop',
  `logo` text DEFAULT NULL,
  `logo_footer` text DEFAULT NULL,
  `banner` text DEFAULT NULL,
  `favicon` text DEFAULT NULL,
  `key_words` text DEFAULT NULL,
  `mo_ta` text DEFAULT NULL,
  `fb_admin` varchar(255) DEFAULT NULL,
  `sdt_admin` varchar(50) DEFAULT NULL,
  `tele_admin` varchar(255) DEFAULT NULL,
  `tiktok_admin` varchar(255) DEFAULT NULL,
  `youtube_admin` varchar(255) DEFAULT NULL,
  `email_auto` varchar(255) DEFAULT NULL,
  `pass_mail_auto` varchar(255) DEFAULT NULL,
  `ten_nguoi_gui` varchar(255) DEFAULT NULL,
  `email_cf` varchar(255) DEFAULT NULL,
  `apikey` varchar(100) DEFAULT NULL,
  `thongbao` longtext DEFAULT NULL,
  `popup_template` varchar(10) NOT NULL DEFAULT '1' COMMENT '0=tắt, 1=mặc định, 2=thông báo',
  `license` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Keep one blank settings row so app can read config without undefined index warnings
INSERT INTO `setting` (
  `id`, `ten_web`, `logo`, `logo_footer`, `banner`, `favicon`, `key_words`, `mo_ta`, `fb_admin`, `sdt_admin`, `tele_admin`, `tiktok_admin`, `youtube_admin`,
  `email_auto`, `pass_mail_auto`, `ten_nguoi_gui`,
  `email_cf`, `apikey`, `thongbao`, `license`
) VALUES (
  1, 'KaiShop', '', '', 'KaiShop', '', 'KaiShop, Shop account', 'Dịch vụ KaiShop uy tín chất lượng', 'https://facebook.com/phamlinh7114', '0812420710', 'https://t.me/yourtelegram', 'https://tiktok.com/@yourtiktok', 'https://youtube.com/@youryoutube',
  NULL, NULL, NULL,
  NULL, NULL, '<div style="text-align:center;">
  <b>KaiShop — Hệ thống bán tài khoản tự động</b><br>
  <b>Phiên bản: v1.1</b><br>
  <span>Khi dùng dịch vụ chính hãng, bạn được hỗ trợ tốt hơn và nâng cấp tính năng với chi phí tối ưu.</span>
</div>', ''
);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

CREATE TABLE IF NOT EXISTS `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `payload` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `severity` enum('INFO','WARNING','DANGER') DEFAULT 'INFO',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_module` (`module`),
  KEY `idx_severity` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_fingerprints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `fingerprint_hash` varchar(64) NOT NULL,
  `components` longtext DEFAULT NULL COMMENT 'JSON: canvas, webgl, fonts, screen, etc.',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_uf_user` (`user_id`),
  KEY `idx_uf_hash` (`fingerprint_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

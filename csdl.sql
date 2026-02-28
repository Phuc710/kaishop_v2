
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
-- Production recommendation: keep DB/session timezone in UTC.
-- App/UI will convert by APP_DISPLAY_TIMEZONE (e.g. Asia/Ho_Chi_Minh).
SET time_zone = "+00:00";
SET NAMES utf8mb4;

START TRANSACTION;
SET FOREIGN_KEY_CHECKS = 0;


CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(100) DEFAULT NULL COMMENT 'URL slug',
  `icon` varchar(255) DEFAULT NULL COMMENT 'URL ảnh icon danh mục',
  `description` text DEFAULT NULL COMMENT 'Mô tả SEO',
  `display_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự ưu tiên (nhỏ = cao)',
  `status` varchar(10) NOT NULL DEFAULT 'ON',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_categories_slug` (`slug`),
  KEY `idx_categories_status` (`status`),
  KEY `idx_categories_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `banned_fingerprints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fingerprint_hash` varchar(64) NOT NULL,
  `reason` text NULL,
  `banned_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bf_hash` (`fingerprint_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL COMMENT 'URL slug (auto-gen từ tên)',
  `product_type` enum('account','link') NOT NULL DEFAULT 'account' COMMENT 'account = bán tk từ kho, link = bán link download',
  `price_vnd` bigint(20) NOT NULL DEFAULT 0 COMMENT 'Giá bán (VNĐ)',
  `source_link` text DEFAULT NULL COMMENT 'Link download (Mega/GDrive...) - chỉ dùng khi product_type=link',
  `manual_stock` int(11) NOT NULL DEFAULT 0 COMMENT 'Stock số lượng cho sản phẩm yêu cầu info',
  `min_purchase_qty` int(11) NOT NULL DEFAULT 1 COMMENT 'So luong mua toi thieu',
  `max_purchase_qty` int(11) NOT NULL DEFAULT 0 COMMENT '0 = khong gioi han cau hinh',
  `badge_text` varchar(100) DEFAULT NULL COMMENT 'Badge hiển thị (NEW, HOT, SALE...)',
  `category_id` int(11) DEFAULT NULL COMMENT 'FK tới categories.id',
  `display_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự ưu tiên (nhỏ = cao)',
  `status` varchar(20) NOT NULL DEFAULT 'ON' COMMENT 'ON = hiện, OFF = ẩn',
  `image` text DEFAULT NULL COMMENT 'URL ảnh chính (thumbnail)',
  `gallery` text DEFAULT NULL COMMENT 'JSON array chứa nhiều link ảnh gallery',
  `description` longtext DEFAULT NULL COMMENT 'Mô tả chi tiết sản phẩm',
  `seo_description` text DEFAULT NULL COMMENT 'SEO meta description',
  `requires_info` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = yeu cau user nhap thong tin => don pending',
  `info_instructions` text DEFAULT NULL COMMENT 'Huong dan thong tin user can cung cap khi mua',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_products_slug` (`slug`),
  KEY `idx_products_status` (`status`),
  KEY `idx_products_type` (`product_type`),
  KEY `idx_products_category_id` (`category_id`),
  KEY `idx_products_order` (`display_order`),
  KEY `idx_products_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Bảng kho hàng tài khoản (mỗi dòng = 1 tài khoản chờ bán)
CREATE TABLE IF NOT EXISTS `product_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `content` text NOT NULL COMMENT 'Nội dung tài khoản (1 dòng)',
  `content_hash` char(64) DEFAULT NULL COMMENT 'SHA-256 content plaintext de chong trung',
  `status` enum('available','sold') NOT NULL DEFAULT 'available',
  `order_id` int(11) DEFAULT NULL COMMENT 'ID đơn hàng đã mua (nullable)',
  `buyer_id` int(11) DEFAULT NULL COMMENT 'ID người mua',
  `note` varchar(255) DEFAULT NULL,
  `sold_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stock_product` (`product_id`),
  KEY `idx_stock_status` (`status`),
  KEY `idx_stock_order` (`order_id`),
  KEY `idx_stock_created_at` (`created_at`),
  KEY `idx_stock_sold_at` (`sold_at`),
  UNIQUE KEY `uniq_stock_product_hash` (`product_id`,`content_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_code` varchar(40) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` bigint(20) NOT NULL DEFAULT 0,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `status` varchar(30) NOT NULL DEFAULT 'completed',
  `stock_id` int(11) DEFAULT NULL,
  `stock_content` longtext NULL,
  `customer_input` longtext NULL,
  `fulfilled_by` varchar(100) DEFAULT NULL,
  `fulfilled_at` datetime DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `user_deleted_at` datetime DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'wallet',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_orders_code` (`order_code`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_product` (`product_id`),
  KEY `idx_orders_created` (`created_at`)
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
  KEY `idx_gift_code_expired` (`expired_at`),
  KEY `idx_gift_code_time` (`time`),
  KEY `idx_gift_code_created_at` (`created_at`)
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
  KEY `idx_lshd_username` (`username`),
  KEY `idx_lshd_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_updated_at` datetime DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `twofa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `level` tinyint(4) NOT NULL DEFAULT 0,
  `tong_nap` bigint(20) NOT NULL DEFAULT 0,
  `money` bigint(20) NOT NULL DEFAULT 0,
  `otpcode` varchar(100) DEFAULT NULL,
  `otpcode_expires_at` datetime DEFAULT NULL,
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

CREATE TABLE IF NOT EXISTS `auth_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_selector` varchar(64) NOT NULL,
  `access_token_hash` char(64) NOT NULL,
  `refresh_token_hash` char(64) NOT NULL,
  `legacy_session_token` varchar(100) DEFAULT NULL,
  `access_expires_at` datetime NOT NULL,
  `refresh_expires_at` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` longtext NULL,
  `device_fingerprint` varchar(128) DEFAULT NULL,
  `device_hash` char(64) DEFAULT NULL,
  `device_os` varchar(100) DEFAULT NULL,
  `device_browser` varchar(100) DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `remember_me` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `revoke_reason` varchar(64) DEFAULT NULL,
  `last_rotated_at` datetime DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_auth_sessions_selector` (`session_selector`),
  KEY `idx_auth_sessions_user` (`user_id`),
  KEY `idx_auth_sessions_status` (`status`),
  KEY `idx_auth_sessions_status_refresh` (`status`,`refresh_expires_at`),
  KEY `idx_auth_sessions_selector_status` (`session_selector`,`status`),
  KEY `idx_auth_sessions_legacy_session` (`legacy_session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `auth_otp_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `challenge_id` varchar(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `purpose` varchar(30) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `code_hash` char(64) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `max_attempts` int(11) NOT NULL DEFAULT 5,
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `consumed_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` longtext NULL,
  `device_hash` char(64) DEFAULT NULL,
  `metadata_json` longtext NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_auth_otp_challenge` (`challenge_id`),
  KEY `idx_auth_otp_user` (`user_id`),
  KEY `idx_auth_otp_purpose` (`purpose`),
  KEY `idx_auth_otp_challenge_purpose` (`challenge_id`,`purpose`),
  KEY `idx_auth_otp_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `user_trusted_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `device_hash` char(64) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` longtext NULL,
  `os` varchar(100) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `trusted_until` datetime DEFAULT NULL,
  `last_seen_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_device` (`user_id`,`device_hash`),
  KEY `idx_user_device_until` (`trusted_until`),
  KEY `idx_user_device_user_until` (`user_id`,`trusted_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `auth_login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(30) NOT NULL,
  `username_or_email` varchar(190) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `reason` varchar(190) DEFAULT NULL,
  `user_agent` longtext NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_auth_attempt_action_ip_time` (`action`,`ip_address`,`created_at`),
  KEY `idx_auth_attempt_user_ip_time` (`username_or_email`,`ip_address`,`created_at`),
  KEY `idx_auth_attempt_action_user_ip_success_time` (`action`,`username_or_email`,`ip_address`,`success`,`created_at`)
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
  `contact_page_title` varchar(255) DEFAULT NULL,
  `contact_page_subtitle` text DEFAULT NULL,
  `contact_email_label` varchar(150) DEFAULT NULL,
  `contact_phone_label` varchar(150) DEFAULT NULL,
  `contact_support_note` text DEFAULT NULL,
  `policy_page_title` varchar(255) DEFAULT NULL,
  `policy_page_subtitle` text DEFAULT NULL,
  `policy_content_html` longtext DEFAULT NULL,
  `policy_notice_text` text DEFAULT NULL,
  `terms_page_title` varchar(255) DEFAULT NULL,
  `terms_page_subtitle` text DEFAULT NULL,
  `terms_content_html` longtext DEFAULT NULL,
  `terms_notice_text` text DEFAULT NULL,
  `apikey` varchar(100) DEFAULT NULL,
  `thongbao` longtext DEFAULT NULL,
  `popup_template` varchar(10) NOT NULL DEFAULT '1' COMMENT '0=tắt, 1=mặc định, 2=thông báo',
  `license` text DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT 'MB Bank',
  `bank_account` varchar(100) DEFAULT NULL,
  `bank_owner` varchar(100) DEFAULT NULL,
  `sepay_api_key` varchar(255) DEFAULT NULL,
  `telegram_bot_token` varchar(255) DEFAULT NULL,
  `telegram_chat_id` varchar(64) DEFAULT NULL,
  `bonus_1_amount` bigint(20) DEFAULT 100000,
  `bonus_1_percent` int(11) DEFAULT 10,
  `bonus_2_amount` bigint(20) DEFAULT 200000,
  `bonus_2_percent` int(11) DEFAULT 15,
  `bonus_3_amount` bigint(20) DEFAULT 500000,
  `bonus_3_percent` int(11) DEFAULT 20,
  `maintenance_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `maintenance_start_at` datetime DEFAULT NULL,
  `maintenance_end_at` datetime DEFAULT NULL,
  `maintenance_notice_minutes` int(11) NOT NULL DEFAULT 5,
  `maintenance_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Keep one blank settings row so app can read config without undefined index warnings
INSERT INTO `setting` (
  `id`, `ten_web`, `logo`, `logo_footer`, `banner`, `favicon`, `key_words`, `mo_ta`, `fb_admin`, `sdt_admin`, `tele_admin`, `tiktok_admin`, `youtube_admin`,
  `email_auto`, `pass_mail_auto`, `ten_nguoi_gui`,
  `email_cf`, `contact_page_title`, `contact_page_subtitle`, `contact_email_label`, `contact_phone_label`, `contact_support_note`, `policy_page_title`, `policy_page_subtitle`, `policy_content_html`, `policy_notice_text`, `terms_page_title`, `terms_page_subtitle`, `terms_content_html`, `terms_notice_text`, `apikey`, `thongbao`, `license`,
  `bank_name`, `bank_account`, `bank_owner`, `sepay_api_key`, `telegram_bot_token`, `telegram_chat_id`,
  `bonus_1_amount`, `bonus_1_percent`, `bonus_2_amount`, `bonus_2_percent`, `bonus_3_amount`, `bonus_3_percent`,
  `maintenance_enabled`, `maintenance_start_at`, `maintenance_end_at`, `maintenance_notice_minutes`, `maintenance_message`
) VALUES (
  1, 'KaiShop', '', '', 'KaiShop', '', 'KaiShop, Shop account', 'Dịch vụ KaiShop uy tín chất lượng', 'https://facebook.com/phamlinh7114', '0812420710', 'https://t.me/kaishop25', 'https://www.tiktok.com/@kai_01s.', 'https://www.youtube.com/@KaiOfficial-0x',
  NULL, NULL, NULL,
  NULL, 'Liên hệ KaiShop', 'Liên hệ hỗ trợ nhanh qua email, Zalo hoặc các kênh mạng xã hội bên dưới.', 'Email hỗ trợ', 'Số điện thoại / Zalo', 'Hỗ trợ trong giờ làm việc hoặc qua kênh online.',
  'Chính sách & Quy định', '', NULL, 'Khi tiếp tục sử dụng dịch vụ tại website, bạn xác nhận đã đọc, hiểu và đồng ý với các chính sách/quy định được công bố.',
  'Điều khoản & Điều kiện', '', NULL, 'Bằng việc sử dụng dịch vụ, bạn xác nhận đã đọc, hiểu và đồng ý với Điều khoản & Điều kiện này.',
  NULL, '<div style="text-align:center;">
  <b>KaiShop — Hệ thống bán tài khoản tự động</b><br>
  <b>Phiên bản: v1.1</b><br>
  <span>Khi dùng dịch vụ chính hãng, bạn được hỗ trợ tốt hơn và nâng cấp tính năng với chi phí tối ưu.</span>
</div>', '',
  'MB Bank', '', '', '', '', '',
  100000, 10, 200000, 15, 500000, 20,
  0, NULL, NULL, 5, 'Hệ thống đang bảo trì để nâng cấp dịch vụ. Vui lòng quay lại sau ít phút.'
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
  KEY `idx_severity` (`severity`),
  KEY `idx_system_logs_created_at` (`created_at`),
  KEY `idx_system_logs_severity_created` (`severity`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_fingerprints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `fingerprint_hash` varchar(64) NOT NULL,
  `components` longtext DEFAULT NULL COMMENT 'JSON: canvas, webgl, fonts, screen, etc.',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_id` varchar(80) DEFAULT NULL,
  `ip_prefix` varchar(64) DEFAULT NULL,
  `user_agent_hash` char(64) DEFAULT NULL,
  `accept_language` varchar(255) DEFAULT NULL,
  `timezone` varchar(120) DEFAULT NULL,
  `language` varchar(120) DEFAULT NULL,
  `platform` varchar(120) DEFAULT NULL,
  `screen_key` varchar(64) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `risk_score` int(11) NOT NULL DEFAULT 0,
  `risk_flags` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_uf_user` (`user_id`),
  KEY `idx_uf_hash` (`fingerprint_hash`),
  KEY `idx_uf_device_id` (`device_id`),
  KEY `idx_uf_ip_ua_time` (`ip_prefix`,`user_agent_hash`,`created_at`),
  KEY `idx_uf_risk_time` (`risk_score`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pending_deposits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `deposit_code` varchar(50) NOT NULL,
  `amount` bigint(20) NOT NULL,
  `bonus_percent` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','completed','cancelled','expired') DEFAULT 'pending',
  `sepay_transaction_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_deposit_code` (`deposit_code`),
  UNIQUE KEY `uniq_pd_sepay_transaction_id` (`sepay_transaction_id`),
  KEY `idx_pd_user` (`user_id`),
  KEY `idx_pd_status` (`status`),
  KEY `idx_pd_user_status_created` (`user_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `history_nap_bank` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trans_id` varchar(150) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'Bank',
  `ctk` text DEFAULT NULL COMMENT 'Nội dung chuyển khoản / lý do',
  `stk` varchar(100) DEFAULT NULL COMMENT 'Số tài khoản nguồn',
  `thucnhan` bigint(20) DEFAULT 0 COMMENT 'Số tiền thực nhận (có dấu +/-)',
  `status` varchar(50) DEFAULT 'pending',
  `time` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hnb_username` (`username`),
  KEY `idx_hnb_trans` (`trans_id`),
  KEY `idx_hnb_created_at` (`created_at`),
  KEY `idx_hnb_status_created` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

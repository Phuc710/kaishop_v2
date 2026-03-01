# Database Schema — Telegram Bot

Dưới đây là cấu trúc bảng và các cột phục vụ hệ sinh thái Telegram tích hợp sâu vào Website.

## 1. Bảng Mapping & OTP

### `user_telegram_links`
Lưu trữ liên kết giữa thực thể Web User và Telegram Identity.
```sql
CREATE TABLE IF NOT EXISTS `user_telegram_links` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `telegram_id` BIGINT NOT NULL,
  `telegram_username` VARCHAR(64) NULL,
  `first_name` VARCHAR(255) NULL,
  `linked_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `last_active` DATETIME NULL,
  UNIQUE KEY `uniq_tg` (`telegram_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `telegram_link_codes`
Mã OTP tạm thời (5 phút) để xác thực liên kết tài khoản.

---

## 2. Bảng Outbox (Hàng đợi gửi tin)

### `telegram_outbox`
Trung tâm xử lý tin nhắn không đồng bộ (Broadcast, Bill thông báo).
```sql
CREATE TABLE IF NOT EXISTS `telegram_outbox` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `telegram_id` BIGINT NOT NULL,
  `message` TEXT NOT NULL,
  `parse_mode` VARCHAR(20) DEFAULT 'HTML',
  `status` ENUM('pending','sent','fail') DEFAULT 'pending',
  `try_count` INT DEFAULT 0,
  `last_error` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `sent_at` DATETIME NULL,
  KEY `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 3. Các cột mở rộng trong `setting`

Dùng để cấu hình hành vi của Bot qua `TelegramConfig.php`:

| Column | Mô tả |
| :--- | :--- |
| `telegram_bot_token` | API Token chính thức. |
| `telegram_chat_id` | Chat ID của Admin chính (nhận log nạp tiền). |
| `telegram_admin_ids` | Danh sách ID Admin phụ (comma-separated). |
| `telegram_webhook_secret` | Token xác thực cuộc gọi từ Telegram Server. |
| `telegram_rate_limit` | Số lệnh tối đa/phút/user (mặc định 30). |
| `telegram_order_cooldown` | Số giây chờ giữa 2 lần thanh toán (mặc định 10). |
| `last_cron_run` | Lưu timestamp lần cuối Cron worker chạy (Monitor). |

---

## 4. Migration & Cập nhật Schema

Các thay đổi mới nhất cho Production (Session 01/03/2026) được lưu tại:
`database/migrations/20260301_telegram_production_optimizations.sql`

Dùng lệnh SQL trên để cập nhật toàn bộ index và cột mới cho hệ thống.

# Admin Panel — Quản Lý Telegram Bot

## Tổng quan

Hệ thống Admin cho Telegram Bot gồm 6 trang, tất cả nằm dưới `/admin/telegram/`:

| Route | Trang | Mô tả |
| :--- | :--- | :--- |
| `/admin/telegram` | Dashboard | Tổng quan sức khỏe hệ thống |
| `/admin/telegram/settings` | Cấu hình | Token, Webhook, Templates, Rate limit |
| `/admin/telegram/links` | User Links | Quản lý liên kết user ↔ Telegram |
| `/admin/telegram/outbox` | Outbox | Hàng đợi tin nhắn + Worker monitor |
| `/admin/telegram/logs` | Nhật ký | Log webhook, commands, errors |
| `/admin/telegram/orders` | Đơn hàng Bot | Đơn hàng có nguồn gốc từ Bot |

---

## 1. Dashboard (`/admin/telegram`)

Mục tiêu: **Phát hiện lỗi trong 10 giây**.

### Stat Cards (hàng đầu)

| Card | Dữ liệu | Nguồn |
| :--- | :--- | :--- |
| 🤖 Bot Status | Token OK / Webhook OK | `TelegramService::getMe()` + `getWebhookInfo()` |
| ✉️ Outbox Queue | Pending / Sent / Fail | `TelegramOutbox::getStats()` |
| 🔗 Users Linked | Tổng đã link, mới link hôm nay | COUNT từ `user_telegram_links` |
| 🛒 Bot Orders | Tổng đơn, doanh thu | `orders WHERE order_source='telegram'` |
| 💳 Deposits | Pending / Completed | `pending_deposits` stats |
| ⏱ Worker Health | Last run, cảnh báo nếu > X phút | Cần thêm cơ chế lưu `last_cron_run` |

### Bảng — Recent Outbox Messages

Hiện 10 message gần nhất: ID, Telegram ID, Message preview, Status, Try count, Created at.

---

## 2. Cấu hình Bot (`/admin/telegram/settings`)

### Các trường cấu hình

| Trường | Kiểu | Mô tả |
| :--- | :--- | :--- |
| `telegram_bot_token` | password | Token từ @BotFather. Ẩn dạng `****` |
| `telegram_chat_id` | text | Chat ID admin chính |
| `telegram_admin_ids` | textarea | Nhiều admin ID, phân cách bằng dấu phẩy |
| `telegram_webhook_secret` | text | Secret token cho webhook verify |
| `telegram_mode` | select | `webhook` / `polling` |
| `telegram_last_update_id` | readonly | ID update cuối cùng (polling) |

### Webhook Controls (nút bấm)

- **Set Webhook** → POST `/admin/telegram/webhook/set`
- **Delete Webhook** → POST `/admin/telegram/webhook/delete`
- **Test Notification** → POST `/admin/telegram/test`

### Template Messages

| Setting Key | Mô tả |
| :--- | :--- |
| `telegram_template_menu` | Text menu chính |
| `telegram_template_topup` | Text thông báo nạp tiền thành công |
| `telegram_template_order` | Text thông báo mua hàng thành công |

### Rate Limit & Anti-spam

| Setting Key | Default | Mô tả |
| :--- | :--- | :--- |
| `telegram_rate_limit` | 10 | Số lệnh tối đa / phút / user |
| `telegram_order_cooldown` | 10 | Cooldown mua hàng (giây) |

---

## 3. User Links (`/admin/telegram/links`)

### Bảng hiển thị

| Column | Nguồn |
| :--- | :--- |
| User ID | `user_telegram_links.user_id` |
| Username Web | JOIN `users.username` |
| Telegram ID | `telegram_id` |
| @Username TG | `telegram_username` |
| Linked At | `linked_at` |
| Last Active | `last_active` |

### Hành động

- **Unlink**: Hủy liên kết
- **Force Link**: Admin nhập Telegram ID → gán vào User ID
- **Regenerate OTP**: Reset mã liên kết cho user

### Search

Tìm theo: Telegram ID / Username web / Email / User ID.

---

## 4. Outbox & Worker (`/admin/telegram/outbox`)

### Bảng hiển thị

| Column | Nguồn |
| :--- | :--- |
| ID | `telegram_outbox.id` |
| Telegram ID | `telegram_id` |
| Message Preview | Truncated `message` |
| Status | `pending` / `sent` / `fail` |
| Try Count | `try_count` |
| Last Error | `last_error` |
| Created At | `created_at` |

### Filters

Tab filter: All / Pending / Sent / Fail.

### Bulk Actions

- **Retry Selected**: Reset `status=pending`, `try_count=0` cho các message đã chọn
- **Retry All Fails**: Reset tất cả `fail` → `pending`
- **Mark as Sent**: Đánh dấu đã gửi thủ công
- **Delete**: Xóa rác

### Worker Monitor

- Last cron run timestamp
- Messages/minute throughput
- Cảnh báo đỏ nếu fail liên tục > 5 phút

---

## 5. Nhật ký (`/admin/telegram/logs`)

Query từ `system_logs WHERE module = 'telegram'`.

### Filters

- Theo user / command / thời gian / severity
- Log webhook payload (ẩn thông tin nhạy cảm)

---

## 6. Đơn hàng Bot (`/admin/telegram/orders`)

Filter `orders WHERE order_source = 'telegram'`.

### Hành động đặc biệt

- **Resend Goods**: Gửi lại `stock_content` cho user đã link qua Telegram
- **Refund Wallet**: Hoàn tiền ví nếu lỗi

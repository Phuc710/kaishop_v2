# Webhook, Polling & Outbox Worker

## 1. Webhook Async (Production)

Phiên bản tối ưu sử dụng cơ chế phản hồi sớm để tránh Telegram timeout/retry.

### Endpoint
```
POST /api/telegram/webhook
File: public/telegram/webhook.php
```

### Luồng xử lý Async
```mermaid
graph TD
    T[Telegram Server] -->|POST Update| W[webhook.php]
    W -->|parseAndVerify| C[TelegramBotController]
    C -->|Check Secret & IP Rate Limit| W
    W -->|200 OK + fastcgi_finish_request| T
    Note over T: Telegram nhận 200 sớm
    W -->|processUpdateAsync| C
    C -->|processUpdate| S[TelegramBotService]
    S -->|Logic: Buy/Deposit...| DB[(Database)]
```

# Lệnh Quản Trị Telegram (Admin Commands)

Hệ thống cung cấp bộ công cụ quản trị mạnh mẽ ngay trên Telegram dành cho các quản trị viên.

## Phân quyền Admin

Admin được xác định qua lớp `TelegramConfig`:
- **Admin chính**: ID trong `telegram_chat_id`.
- **Admin phụ**: Cấu hình trong `telegram_admin_ids` (comma-separated).

Mọi lệnh Admin đều thực hiện kiểm tra qua `TelegramConfig::isAdmin($telegramId)`.

---

## `/stats` — Thống kê nâng cao

Lệnh cung cấp cái nhìn tổng quan về "sức khỏe" hệ dẫn và tình hình kinh doanh trong ngày.

**Nội dung hiển thị:**
- 📈 **Doanh thu & Đơn hàng**: Hôm nay.
- 👤 **Người dùng**: Tổng User và User mới liên kết hôm nay.
- 💳 **Nạp tiền**: Tổng số yêu cầu Deposit đang chờ xử lý.
- ✉️ **Outbox**: Trạng thái hàng đợi tin nhắn (Pending / Sent / Fail).
- ⚙️ **Worker Health**: Timestamp lần cuối `cron.php` chạy thành công.

---

## `/broadcast` — Thông báo hàng loạt

Sử dụng Outbox Pattern để gửi tin nhắn tới toàn bộ người dùng đã liên kết mà không bị block/limit bởi Telegram API.

**Cú pháp:** `/broadcast <nội_dung_thông_báo>`

**Quy trình:**
1. Bot quét toàn bộ ID trong bảng `user_telegram_links`.
2. Đẩy (push) nội dung vào bảng `telegram_outbox`.
3. Worker Cron sẽ gửi đi một cách an toàn (Parallel gửi nhiều người cùng lúc nhưng tuân thủ giới hạn tốc độ).

---

## `/maintenance` — Chế độ bảo trì

Điều khiển trạng thái hoạt động của toàn bộ hệ thống ngay lập tức.

**Cú pháp:** 
- `/maintenance on`: Bật bảo trì (Chỉ Admin mới có thể truy cập Web & Bot).
- `/maintenance off`: Tắt bảo trì (Công khai hệ thống).

---

## `/setbank` — Cấu hình nạp tiền nhanh

Thay đổi thông tin số tài khoản ngân hàng nhận tiền mà không cần vào Admin Panel Web.

**Cú pháp:** `/setbank Tên Ngân Hàng | STK | Tên Chủ TK`

---

## Hướng dẫn sử dụng Admin Menu
Admin có thể gõ `/start` để thấy Menu phím ảo riêng, bao gồm các lối tắt nhanh tới trang Thống kê và Trợ giúp.

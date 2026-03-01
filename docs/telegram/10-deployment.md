# Hướng Dẫn Triển Khai (Deployment)

## Development (Local XAMPP)

### 1. Cấu hình `.env`

```env
TELEGRAM_BOT_TOKEN=123456789:ABC-DEF...
TELEGRAM_CHAT_ID=1234567890
TELEGRAM_WEBHOOK_SECRET=random_secret_here
```

### 2. Chạy Bot (Polling Mode)

Vì localhost không có HTTPS, dùng polling:

```bash
php public/telegram/cron.php --poll
```

Script sẽ loop vô hạn: xử lý outbox + lấy updates từ Telegram.

### 3. Test

- Mở Telegram → Tìm bot → Gõ `/start`
- Kiểm tra terminal hiện "Processing Update ID: ..."

---

## Production (Server có HTTPS)

### 1. Cấu hình `.env`

```env
BASE_URL=https://kaishop.id.vn
TELEGRAM_BOT_TOKEN=123456789:ABC-DEF...
TELEGRAM_CHAT_ID=1234567890
TELEGRAM_WEBHOOK_SECRET=your_strong_secret
```

### 2. Thiết lập Webhook

**Cách 1**: Vào Admin Panel → `/admin/telegram` → Click "THIẾT LẬP WEBHOOK".

**Cách 2**: Gọi API thủ công:
```bash
curl -X POST "https://api.telegram.org/bot{TOKEN}/setWebhook" \
  -d "url=https://kaishop.id.vn/api/telegram/webhook" \
  -d "secret_token=your_strong_secret"
```

### 3. Cron cho Outbox Worker

Thiết lập Crontab để xử lý hàng đợi tin nhắn và dọn dẹp hệ thống:

```cron
# Mỗi 1 phút: Xử lý Outbox (Parallel cURL) + Cleanup GC
* * * * * php /path/to/public/telegram/cron.php >> /var/log/kaishop-tg-worker.log 2>&1
```

Script `cron.php` sẽ tự động thực hiện:
- Gửi tin nhắn song song qua `curl_multi`.
- Dọn dẹp OTP hết hạn và Outbox cũ (> 7 ngày).
- Xóa các file rate-limit rác.
- Cập nhật `last_cron_run` vào Database.

---

## Database Migration

Chạy file migration mới nhất để áp dụng các tối ưu Production và OOP:

```bash
# Migration chính cho Production (Session 01/03/2026)
mysql -u root kaishop_v2 < database/migrations/20260301_telegram_production_optimizations.sql
```

---

## Checklist Production

- [ ] `.env` đã cấu hình `TELEGRAM_BOT_TOKEN` và `TELEGRAM_WEBHOOK_SECRET`.
- [ ] Đã chạy migration `20260301_telegram_production_optimizations.sql`.
- [ ] Webhook đã thiết lập (Kiểm tra trong Admin → Trạng thái: **ĐÃ BẬT**).
- [ ] Cron Worker đang chạy (Kiểm tra `last_cron_run` trong DB hoặc Admin Panel).
- [ ] `/start` hiển thị Menu phân quyền (Admin/User).
- [ ] Thử mua hàng Standalone (không cần link) thành công.
- [ ] (Tùy chọn) Thử lệnh `/broadcast` để gửi tin nhắn hàng loạt.

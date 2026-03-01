# Lệnh Quản Trị Telegram (Admin Commands)

## Xác định Admin

Bot nhận diện admin qua `telegram_chat_id` trong bảng `setting`:

```php
private function isAdmin(int $telegramId): bool
{
    $adminId = (int) get_setting('telegram_chat_id', 0);
    return $telegramId === $adminId;
}
```

> **Nâng cấp đề xuất**: Hỗ trợ nhiều Admin bằng cột `telegram_admin_ids` (comma-separated list).

## /stats — Thống kê nhanh

**Quyền**: Admin only.

```
📊 THỐNG KÊ HỆ THỐNG

👤 Tổng user web: 150
🔗 Đã liên kết Telegram: 45

✉️ Hàng đợi Outbox:
🔹 Chờ gửi: 3
🔹 Đã gửi: 1,250
🔹 Lỗi: 12
```

**Nâng cấp đề xuất**:
- Thêm doanh thu hôm nay, đơn hàng hôm nay
- Thêm deposits pending/completed
- Link nhanh ra: tổng đã bán, top sản phẩm hot

## /setbank — Cập nhật ngân hàng

**Quyền**: Admin only.

```
/setbank MB Bank|0123456789|NGUYEN THANH PHUC
```

→ Cập nhật `bank_name`, `bank_account`, `bank_owner` trong bảng `setting`.
→ Clear config cache ngay lập tức.

## /broadcast — Gửi thông báo hàng loạt (Chưa implement)

**Quyền**: Admin only.

```
/broadcast 🔥 Khuyến mãi nạp 50k tặng 10%!
```

**Logic đề xuất**:
1. Admin gõ `/broadcast <nội dung>`.
2. Bot hỏi xác nhận: "Gửi tới X user? [✅ Gửi] [❌ Hủy]"
3. Nếu confirm → loop `user_telegram_links` → push vào `telegram_outbox`.
4. Worker Cron gửi dần (non-blocking, tránh rate limit API).

## /maintenance — Bảo trì hệ thống (Chưa implement)

**Quyền**: Admin only.

```
/maintenance on    → Bật bảo trì
/maintenance off   → Tắt bảo trì
```

**Logic đề xuất**:
- Gọi `MaintenanceService::setManualMode(true/false)`.
- Bot confirm: "✅ Đã bật/tắt bảo trì."

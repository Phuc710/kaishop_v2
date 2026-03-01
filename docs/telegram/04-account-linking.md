# Liên Kết Tài Khoản Web ↔ Telegram

## Tại sao cần link?

Khi user mua hàng qua Bot, hệ thống cần biết user đó là ai trên Web để trừ tiền ví. Liên kết sẽ mapping `user_id` (web) ↔ `telegram_id`.

Một số chức năng **yêu cầu link**: `/wallet`, `/deposit`, `/orders`, `/menu`, mua hàng.
Một số chức năng **không yêu cầu link**: `/start`, `/shop` (xem sản phẩm), `/help`.

## Luồng Link (OTP)

```
[Web] User vào Profile → Click "Tạo mã liên kết"
      → API POST /api/telegram/generate-link
      → Server tạo mã 6 số vào bảng telegram_link_codes (hết hạn 5 phút)
      → Hiện mã + countdown 05:00 trên UI

[Telegram] User gõ: /link 123456
      → Bot gọi TelegramLinkCode::verifyCode('123456')
      → Nếu đúng + chưa hết hạn → Lưu vào user_telegram_links
      → Bot: 🎉 Liên kết thành công! → Hiện Menu
```

## Database

### Bảng `telegram_link_codes`

| Column | Type | Mô tả |
| :--- | :--- | :--- |
| `user_id` | INT | FK → users.id |
| `code` | VARCHAR(32) | Mã OTP 6 số |
| `expires_at` | DATETIME | Hết hạn sau 5 phút |
| `used_at` | DATETIME | NULL = chưa dùng |

### Bảng `user_telegram_links`

| Column | Type | Mô tả |
| :--- | :--- | :--- |
| `user_id` | INT | UNIQUE — 1 user = 1 telegram |
| `telegram_id` | BIGINT | UNIQUE — 1 telegram = 1 user |
| `telegram_username` | VARCHAR(64) | @username (nullable) |
| `first_name` | VARCHAR(255) | Tên hiển thị |
| `linked_at` | DATETIME | Thời điểm link |
| `last_active` | DATETIME | Lần hoạt động cuối |

## Hủy liên kết

- **Từ Web**: POST `/api/telegram/unlink` → Xóa row trong `user_telegram_links`.
- **Từ Admin**: Admin có thể unlink bất kỳ user nào.

## Persistence OTP

- Nếu user reload trang, OTP cũ vẫn hiện (nếu còn hạn) — server trả `activeTgOtp` khi render profile.
- Countdown JS chạy client-side, hết hạn → ẩn khung OTP tự động.
- Cleanup: Model có `cleanExpired()` xóa OTP hết hạn — có thể gọi từ Cron.

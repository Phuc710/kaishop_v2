# KaiShop Telegram Bot - Technical Documentation

Đây là tài liệu chi tiết về hệ thống tích hợp Telegram Bot vào nền tảng KaiShop (v2). Hệ thống được thiết kế theo kiến trúc Hybrid (Webhook + Long Polling) và sử dụng Outbox Pattern để gửi thông báo tự động.

---

## 🏗 Tổng quan Kiến trúc

Hệ thống được chia thành 4 lớp chính:
1.  **Transport Layer**: Xử lý giao tiếp với Telegram API qua Webhook (Production) hoặc Long Polling (Development).
2.  **Service Layer**: Xử lý logic nghiệp vụ (Command Routing, Shop Logic, Wallet Logic).
3.  **Model Layer**: Tương tác Database (Lưu trữ liên kết user, OTP, và hàng đợi tin nhắn).
4.  **Admin Layer**: Dashboard quản lý trạng thái, webhook và theo dõi hàng đợi.

### 🔄 Luồng xử lý tin nhắn
- **Webhook**: Telegram gửi POST request đến `public/telegram/webhook.php` -> Controller xử lý.
- **Outbox Pattern**: Các sự kiện (mua hàng, nạp tiền) không gửi tin nhắn ngay lập tức mà đẩy vào bảng `telegram_outbox`. Worker (`cron.php`) sẽ quét và gửi đi để đảm bảo không bị nghẽn (Non-blocking).

---

## 📂 Cấu trúc File & Vị trí Code

### 1. Controllers & Entry Points
| File | Chức năng |
| :--- | :--- |
| [TelegramBotController.php](file:///c:/xampp/htdocs/kaishop_v2/app/Controllers/Api/TelegramBotController.php) | Entry point chính cho Webhook từ Telegram. |
| [TelegramAdminController.php](file:///c:/xampp/htdocs/kaishop_v2/app/Controllers/Admin/TelegramAdminController.php) | Dashboard quản lý trong Admin Panel. |
| [webhook.php](file:///c:/xampp/htdocs/kaishop_v2/public/telegram/webhook.php) | File proxy giúp Telegram gọi trực tiếp mà không qua Router phức tạp (Performance). |
| [cron.php](file:///c:/xampp/htdocs/kaishop_v2/public/telegram/cron.php) | Worker xử lý gửi tin nhắn chờ và Long Polling test local. |

### 2. Services (Logic)
| File | Chức năng |
| :--- | :--- |
| [TelegramService.php](file:///c:/xampp/htdocs/kaishop_v2/app/Services/TelegramService.php) | Wrapper cấp thấp cho Telegram API (cURL, Keyboard Builder). |
| [TelegramBotService.php](file:///c:/xampp/htdocs/kaishop_v2/app/Services/TelegramBotService.php) | **Bộ não của Bot**: Xử lý lệnh `/start`, `/shop`, `/setbank`, v.v. |

### 3. Models (Database)
| File | Chức năng |
| :--- | :--- |
| [UserTelegramLink.php](file:///c:/xampp/htdocs/kaishop_v2/app/Models/UserTelegramLink.php) | Quản lý bảng `user_telegram_links` (Web ID <-> Telegram ID). |
| [TelegramOutbox.php](file:///c:/xampp/htdocs/kaishop_v2/app/Models/TelegramOutbox.php) | Quản lý hàng đợi tin nhắn `telegram_outbox`. |
| [TelegramLinkCode.php](file:///c:/xampp/htdocs/kaishop_v2/app/Models/TelegramLinkCode.php) | Xử lý mã OTP 6 số để liên kết tài khoản. |

---

## 🛠 Tính năng & Thuật toán

### 1. Thuật toán Liên kết Tài khoản (OTP Linking)
Để đảm bảo an toàn, hệ thống không yêu cầu mật khẩu trên Telegram:
- **Bước 1**: User vào Web -> Click "Tạo mã" -> Sinh mã 6 số ngẫu nhiên vào bảng `telegram_link_codes` (hết hạn sau 5p).
- **Bước 2**: User nhắn `/link 123456` cho Bot.
- **Bước 3**: Bot kiểm tra mã, nếu khớp sẽ lưu mapping vào `user_telegram_links`.

### 2. Hệ thống Shop & Mua hàng trực tiếp
Sử dụng **Inline Keyboards** để tạo trải nghiệm như App:
- `/shop`: Lấy Danh mục -> Sản phẩm -> Chi tiết sản phẩm.
- Khi Click "Xác nhận mua": Bot gọi `PurchaseService` của Web để trừ tiền ví và giao hàng ngay trên chat.

### 3. Lệnh Quản trị (Admin-only)
Bot tự động nhận diện Admin dựa trên `TELEGRAM_CHAT_ID` trong cấu hình:
- `/setbank`: Cập nhật ngân hàng nhanh mà không cần vào Admin Panel.
- `/stats`: Xem báo cáo nhanh về doanh thu, user và hàng đợi.

### 4. Cơ chế Persistence (Polling)
Trong file `cron.php`, hệ thống lưu `telegram_last_update_id` vào bảng `setting`. 
- **Thuật toán**: Khi script chạy lại, nó lấy `offset = last_id + 1` để gọi Telegram API -> Đảm bảo không xử lý lặp lại tin nhắn cũ.
- **Cache**: Script sẽ tự động xóa cache hệ thống (`Config::clearSiteConfigCache`) sau mỗi vòng lặp để đảm bảo lấy được ID mới nhất từ Database.

### 5. Đồng bộ Thời gian (TimeService)
Để giải quyết vấn đề lệch múi giờ giữa PHP và MySQL, hệ thống sử dụng [TimeService.php](file:///c:/xampp/htdocs/kaishop_v2/app/Services/TimeService.php):
- **Nguyên tắc**: Không sử dụng `NOW()` của MySQL hay `date()` trực tiếp trong Model.
- **Thực thi**: Toàn bộ các Model của Bot (`TelegramLinkCode`, `UserTelegramLink`, `TelegramOutbox`) đều gọi `TimeService::instance()->nowSql()` để đảm bảo thời gian tạo mã OTP, thời gian liên kết và thời gian gửi tin nhắn luôn đồng nhất 100%.


---

## 🛡 Bảo mật & Tin cậy

1.  **Secret Token**: Webhook sử dụng `TELEGRAM_WEBHOOK_SECRET`. Chỉ có request từ đúng server Telegram mới được chấp nhận.
2.  **SQL Isolation**: Mọi truy vấn Telegram đều qua Model và sử dụng Prepared Statements (`PDO` hoặc `mysqli`).
3.  **Local Test Support**: Hỗ trợ chế độ `--poll` cho server local (XAMPP) không có HTTPS/Domain thật.

---

## 📈 Hướng dẫn Bảo trì

- **Kiểm tra Log**: Admin Panel -> Nhật ký Telegram.
- **Khởi chạy Worker**: Chạy lệnh `php public/telegram/cron.php --poll` trong terminal để Bot hoạt động.
- **Cập nhật Token**: Thay đổi trong file `.env` hoặc trang Cài đặt Admin.

---
*Tài liệu được tạo tự động bởi Antigravity AI - KaiShop Project 2026*

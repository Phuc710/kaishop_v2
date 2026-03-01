# Bảo Mật & Chống Abuse

Hệ thống Telegram Bot của KaiShop được thiết kế với nhiều lớp bảo mật để đảm bảo tính ổn định và chống tấn công từ chối dịch vụ (DoS).

## 1. Xác thực Webhook (Secret Token)

Telegram gửi header `X-Telegram-Bot-Api-Secret-Token` trong mỗi yêu cầu.
- **Xác thực**: Lớp `TelegramBotController` kiểm tra khớp với `TelegramConfig::webhookSecret()`.
- **Phòng thủ**: Yêu cầu không khớp sẽ bị từ chối ngay lập tức (HTTP 403) trước khi xử lý logic.

## 2. Rate Limiting (Giới hạn tần suất)

Hệ thống sử dụng **File-based Rate Limiting**, đảm bảo giới hạn tồn tại xuyên suốt giữa các Webhook request độc lập.

- **IP Rate Limit**: Giới hạn 120 requests / phút cho mỗi địa chỉ IP gọi tới Webhook (Bảo vệ entry point).
- **User Rate Limit**: Giới hạn (mặc định 30) lệnh / phút cho mỗi người dùng Telegram.
- **Cấu hình**: Chỉnh sửa qua `telegram_rate_limit` trong bảng `setting`.

## 3. Chống Double-Click (Purchase Cooldown)

Để tránh user bấm nút "Mua hàng" nhiều lần dẫn tới trừ tiền nhiều lần:
- **Thời gian chờ**: Mặc định 10 giây giữa 2 lần bấm nút thanh toán.
- **Xử lý**: Nếu vi phạm, Bot sẽ gửi cảnh báo và không thực hiện trừ tiền.
- **Cấu hình**: Chỉnh sửa qua `telegram_order_cooldown` trong bảng `setting`.

## 4. Multi-Admin Authorization

Quyền quản trị viên không chỉ bó hẹp trong 1 ID.
- **Check**: Dùng `TelegramConfig::isAdmin($telegramId)`.
- **Admin IDs**: Hỗ trợ danh sách ID phân cách bởi dấu phẩy trong cài đặt hệ thống.

## 5. Đồng bộ trạng thái BAN

Bot tự động kiểm tra trạng thái người dùng tại mỗi tương tác:
- Nếu User Web bị Ban (`bannd=1`) -> Bot từ chối mọi phản hồi.
- Nếu User Telegram chưa liên kết -> Shadow Account vẫn được kiểm tra tính hợp lệ.

## 6. An toàn dữ liệu

- **SQL Injection**: 100% sử dụng Prepared Statements qua PDO.
- **XSS**: Mọi dữ liệu hiển thị trên Bot được render dưới dạng HTML an toàn, các ký tự đặc biệt được Bot API tự động xử lý hoặc qua `htmlspecialchars` nếu cần.
- **Lazy DB Connection**: Kết nối cơ sở dữ liệu chỉ được mở khi thực sự có lệnh cần xử lý, tối ưu tài nguyên server.

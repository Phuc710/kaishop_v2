# Tích Hợp Telegram Bot

Hệ thống Telegram Bot của KaiShop V2 được thiết kế để cung cấp trải nghiệm mua sắm và quản lý ví ngay trên ứng dụng Telegram, đảm bảo tính ổn định và tốc độ phản hồi nhanh.

## 🤖 Kiến Trúc Thành Phần

- **`TelegramService.php`**: Lớp bao bọc (wrapper) cho Telegram Bot API. Xử lý các yêu cầu HTTP (cURL) tới Telegram như `sendMessage`, `sendPhoto`, `editMessage`, và các tiện ích xây dựng Inline Keyboard.
- **`TelegramBotService.php`**: Trái tim của Bot. Chứa toàn bộ logic xử lý tin nhắn, câu lệnh (commands) và các nút bấm (callbacks).
- **`TelegramOutbox.php` (Model)**: Bảng hàng đợi tin nhắn gửi đi. Giúp gửi tin nhắn không đồng bộ (asynchronous) để không làm treo luồng xử lý chính.

## 🔄 Quy Trình Xử Lý (Message Flow)

1. **Webhook / Polling**: Nhận Update từ Telegram.
2. **`processUpdate()`**: Phân tích Update (là tin nhắn mới hay nút bấm).
3. **Routing**:
    - Câu lệnh: `/start`, `/shop`, `/wallet`, `/orders`.
    - Trạng thái (Input Modes): Nhập số lượng, nhập thông tin, nhập mã giảm giá.
    - Callbacks: Các nút bấm bắt đầu bằng `cat_`, `prod_`, `buy_`, `deposit_`.

## 🛒 Quy Trình Mua Hàng Tự Động

Luồng mua hàng trên Bot được quản lý cực kỳ chặt chẽ qua các bước:
1. **Chọn sản phẩm**: Hiển thị ảnh, giá và tồn kho.
2. **Nhập số lượng**: Validate theo tồn kho hiện có và giới hạn mua.
3. **Nhập thông tin (nếu cần)**: Yêu cầu thông tin bổ sung cho các sản phẩm đặc thù.
4. **Mã giảm giá**: Tích hợp kiểm tra mã giảm giá trực tiếp.
5. **Xác nhận & Thanh toán**: Kiểm tra số dư ví, trừ tiền và giao hàng ngay lập tức hoặc ghi nhận đơn hàng.

## ⚡ Tối Ưu Hóa & Trải Nghiệm

- **Edit Or Send**: Bot ưu tiên sửa tin nhắn cũ (`editMessageText`) thay vì gửi tin mới để giữ lịch sử chat gọn gàng.
- **Session Management**: Lưu trữ trạng thái tạm thời (số lượng, mã sản phẩm) trong file hệ thống để xử lý các bước nhập liệu từ người dùng.
- **Outbox Worker**: Một script chạy ngầm (`cron.php`) sẽ quét bảng outbox để gửi các thông báo quan trọng (như thông báo nạp tiền thành công) song song với luồng xử lý bot chính.
- **Interactive UI**: Sử dụng các phím tắt nhanh (Quick deposit buttons) và các nút "Quay lại" tại mọi bước để user không bị kẹt.

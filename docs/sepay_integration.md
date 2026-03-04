# Tích Hợp Thanh Toán SePay

KaiShop V2 sử dụng SePay để tự động hóa quy trình nạp tiền qua ngân hàng, giúp giao dịch được xử lý chính xác và gần như ngay lập tức.

## 🔗 Luồng Webhook (Webhook Flow)

1. **Giao dịch**: Người dùng chuyển khoản với nội dung chuyển khoản (deposit code) được cấp.
2. **SePay POST**: SePay gửi một bản tin Webhook chứa thông tin giao dịch tới `/api/sepay/webhook`.
3. **Xác thực**:
    - Kiểm tra `Authorization` header với API Key được cấu hình.
    - Validate JSON body và các trường dữ liệu bắt buộc.
4. **Xử lý**:
    - Tìm kiếm yêu cầu nạp tiền (`PendingDeposit`) khớp với mã nạp.
    - Kiểm tra trùng lặp (Anti-duplicate) dựa trên ID giao dịch từ SePay.
    - Cộng tiền vào tài khoản người dùng và ghi nhật ký thay đổi số dư (`BalanceChangeService`).
5. **Thông báo**: Gửi tin nhắn xác nhận nạp tiền thành công tới Telegram.

## 🛡 Bảo Mật & Chống Gian Lận (Security & Anti-Fraud)

- **API Key Verification**: Chỉ chấp nhận các kết nối có API Key khớp với cấu hình trong hệ thống.
- **Transaction Deduplication**: Mỗi `sepay_id` chỉ được xử lý một lần duy nhất. Nếu SePay retry webhook, hệ thống sẽ trả về 200 OK ngay mà không cộng tiền lại.
- **Expiration Policy**: Các yêu cầu nạp tiền có thời gian sống (TTL - mặc định 5 phút). Quá thời gian này, giao dịch sẽ bị Cron đánh dấu là "Hết hạn" và thông báo cho người dùng.
- **Async Schema Cache**: Tối ưu hóa hiệu suất bằng cách cache thông tin cấu trúc bảng để giảm thiểu các truy vấn vào `information_schema` trên mỗi request.

## ⚡ Tối Ưu Hóa Hiệu Suất (Performance Optimizations)

Để đạt được tốc độ xử lý nhanh nhất (< 1 giây để user nhận tin):
- **Direct Send + Outbox Fallback**: Hệ thống cố gắng gửi tin nhắn Telegram trực tiếp trong luồng Webhook. Nếu Telegram chậm hoặc lỗi, tin nhắn sẽ được đẩy vào `telegram_outbox` để Cron xử lý sau.
- **Fire-and-Forget**: Sử dụng cURL với timeout ngắn (3s) để không làm treo quá trình phản hồi lại cho SePay.
- **Reduced Queries**: Loại bỏ các bước quét `markExpired` không cần thiết trong luồng webhook, chuyển sang xử lý tập trung tại Cron.

## 📦 Các Thành Phần Liên Quan

- **`SepayWebhookController.php`**: Điều phối toàn bộ quy trình nhận và xử lý webhook.
- **`PendingDeposit.php` (Model)**: Quản lý trạng thái các yêu cầu nạp tiền (Pending, Completed, Expired).
- **`DepositService.php`**: Cung cấp các hằng số cấu hình (Min amount, Bonus percent) và các tiện ích liên quan.

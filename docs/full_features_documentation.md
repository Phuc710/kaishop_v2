# Tổng Quan Toàn Bộ Chức Năng Hệ Thống (Full Features Documentation)

Tài liệu này cung cấp cái nhìn chi tiết nhất về mọi chức năng, module và quy trình xử lý trong hệ thống KaiShop V2.

---

## 🔐 1. Quản Trị Tài Khoản & Bảo Mật (Auth & Security)

Hệ thống bảo mật đa lớp để bảo vệ cả người dùng và quản trị viên.

- **Đăng ký/Đăng nhập**: Xử lý qua `AuthController`, tích hợp kiểm tra định danh duy nhất và chống spam.
- **Bảo mật Session/CSRF**: Mọi request POST đều được bảo vệ bởi `csrf_token` để chống tấn công giả mạo.
- **AntiFlood & Rate Limit**: Tích hợp `AntiFloodService` để chặn các hành vi spam request hoặc tấn công Brute-force.
- **Hệ Thống Ban (Khóa)**: `BanService` cho phép khóa người dùng trên Web hoặc trên Telegram Bot dựa trên IP, UserID hoặc TelegramID. Trang `banned.php` hiển thị thông báo chuyên nghiệp với hiệu ứng Matrix.
- **Device Tracking**: Theo dõi vân tay trình duyệt (`UserFingerprint`) để phát hiện các hành vi gian lận hoặc đăng nhập bất thường.

## 🛒 2. Quản Lý Sản Phẩm & Bán Hàng (E-commerce)

Module cốt lõi xử lý việc trình bày và bán nội dung số.

- **Danh Mục (Categories)**: Phân loại sản phẩm linh hoạt, hỗ trợ hiển thị/ẩn và sắp xếp ưu tiên.
- **Sản Phẩm (Products)**: 
    - Hỗ trợ 3 loại sản phẩm: **Tài khoản** (bán lẻ từng dòng), **Yêu cầu thông tin** (User nhập info khi mua), **Source Code/Link** (bán link tải về).
    - Quản lý giá tiền, ảnh đại diện, mô tả chi tiết và hướng dẫn sử dụng sau khi mua.
- **Kho Hàng (Inventory)**: 
    - `ProductStock` quản lý các dòng dữ liệu (tài khoản/mật khẩu).
    - Tự động trừ kho khi bán thành công.
    - `ProductInventoryService` giúp đồng bộ số lượng tồn kho theo thời gian thực.
- **Quy Trình Mua Hàng (`PurchaseService`)**:
    - Kiểm tra số dư → Kiểm tra tồn kho → Áp dụng mã giảm giá → Trừ tiền → Xuất thông tin hàng → Ghi lịch sử.
    - Đảm bảo tính nhất quán dữ liệu (Atomicity) - nếu một bước lỗi, toàn bộ phiên mua sẽ bị hủy.

## 💰 3. Hệ Thống Tài Chính (Finance & Deposits)

Tự động hóa hoàn toàn quy trình nạp tiền và thanh toán.

- **Nạp Tiền SePay**: 
    - Tự động nhận diện giao dịch qua Webhook.
    - Khởi tạo mã nạp duy nhất cho mỗi phiên (TTL 5 phút).
    - Tự động cộng tiền và thưởng (bonus) theo cấu hình admin.
- **Lịch Sử Biến Động Số Dư**: `BalanceChangeService` ghi lại mọi thay đổi (nạp tiền, mua hàng, hoàn tiền) kèm lý do chi tiết.
- **Mã Giảm Giá (GiftCodes)**: Quản lý mã giảm giá theo số lượng, thời gian hết hạn và giới hạn sử dụng cho từng user.

## 🤖 4. Hệ Thống Telegram Bot (Telegram Ecosystem)

Bot không chỉ là công cụ thông báo mà còn là một cửa hàng thu nhỏ.

- **Telegram Bot Service**: Đồng bộ tài khoản Web và Bot qua mã liên kết (OTP).
- **Shop trên Bot**: Duyệt danh mục, xem chi tiết sản phẩm và mua hàng trực tiếp bằng phím bấm (Inline Buttons).
- **Thông Báo Đơn Hàng**: Tự động gửi thông tin tài khoản đã mua tới Bot ngay sau khi thanh toán trên Web.
- **Admin Commands**: Cho phép admin xem thống kê nhanh, log giao dịch và quản lý hệ thống ngay trên chat Telegram.

## 📧 5. Dịch Vụ Thông Báo (Communication Services)

- **Mail Service**: Sử dụng PHPMailer với giao diện HTML cao cấp cho các email: Chào mừng, OTP, Quên mật khẩu, Thông báo đơn hàng.
- **Outbox Worker**: Hệ thống hàng đợi (queue) đảm bảo tin nhắn không bị thất lạc khi API Telegram hoặc SMTP gặp sự cố.

## ⚙️ 6. Quản Trị Hệ Thống (Administrative Tools)

Trang Admin toàn diện cho phép kiểm soát mọi khía cạnh.

- **Dashboard**: Thống kê doanh thu, đơn hàng, người dùng mới theo ngày/tháng/năm với biểu đồ trực quan.
- **Nhật Ký Quản Trị (Admin Journal)**: Lưu lại mọi hành động thay đổi cấu hình, chỉnh sửa sản phẩm của Admin để truy vết.
- **System Logs**: Ghi lại các lỗi hệ thống để kỹ thuật viên dễ dàng xử lý.
- **Cấu Hình Linh Hoạt**: Chỉnh sửa mọi thông số từ tên website, API Key, thông tin liên hệ cho tới cấu hình SMTP ngay trên giao diện web.

---

## 🏗 Kiến Trúc OOP Nâng Cao

Hệ thống tuân thủ nghiêm ngặt các nguyên tắc OOP:
- **Encapsulation**: Mọi logic nghiệp vụ nằm trong Services, Controllers chỉ điều phối.
- **Inheritance**: Models và Controllers kế thừa từ các lớp Core để tái sử dụng mã nguồn.
- **Singleton**: Kết nối Database và các Config được duy trì duy nhất trong một vòng đời request.
- **Clean Code**: Tên hàm, biến rõ ràng, có comment giải thích cho từng nghiệp vụ phức tạp.

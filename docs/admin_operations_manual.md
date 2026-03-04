# Hướng Dẫn Vận Hành Hệ Thống Quản Trị (Admin Operations Manual)

Tài liệu này hướng dẫn cách sử dụng các công cụ quản trị trong KaiShop V2 để quản lý nội dung và giám sát hoạt động hệ thống.

---

## 📦 1. Quản Lý Sản Phẩm & Kho Hàng

### Thêm Danh Mục Mới
- Truy cập **Quản lý danh mục** → **Thêm mới**.
- Nhập tên, mô tả và chọn icon/ảnh đại diện.
- Đặt trạng thái **Hiển thị** hoặc **Ẩn** tùy nhu cầu.

### Thêm Sản Phẩm & Nhập Kho
- Chọn **Quản lý sản phẩm** → **Thêm sản phẩm**.
- Thiết lập loại sản phẩm:
    - **Tài khoản**: Cần nhập kho dữ liệu theo định dạng mỗi dòng một tài khoản.
    - **Yêu cầu thông tin**: Thiết lập lời nhắc (prompt) cho người dùng nhập info.
    - **Source code**: Đính kèm link tải và mật khẩu giải nén.
- **Quản lý kho**: Click icon "Kho" trên danh sách sản phẩm để thêm/xóa dữ liệu. Hệ thống tự động báo hết hàng trên cả Web và Bot Telegram khi kho trống.

## 👥 2. Quản Lý Người Dùng & Tài Chính

### Thành Viên
- Xem danh sách người dùng, số dư, tổng nạp và ngày tham gia.
- Có thể trực tiếp thay đổi số dư người dùng (Cộng/Trừ tiền) kèm lý do.
- Khóa người dùng nếu phát hiện vi phạm.

### Giao Dịch Nạp Tiền
- Giám sát mọi yêu cầu nạp tiền từ SePay.
- Xem chi tiết: Mã giao dịch, nội dung chuyển khoản, số tiền thực nhận và bonus.
- Hỗ trợ xử lý thủ công các trường hợp sai nội dung hoặc sai số tiền.

## 📊 3. Giám Sát & Báo Cáo

### Nhật Ký Hoạt Động (Admin Journal)
- Theo dõi mọi thay đổi từ phía cộng tác viên hoặc admin khác.
- Lưu trữ: Người thay đổi, nội dung cũ, nội dung mới và thời gian.

### Hệ Thống Logs
- **System Logs**: Lỗi code, lỗi API SePay/Telegram.
- **Telegram Logs**: Lịch sử tin nhắn đi/đến từ Bot và người dùng.

## ⚙️ 4. Cấu Hình Hệ Thống

### Cài Đặt Chung
- Thay đổi tên Website, Logo, Favicon và thông tin liên hệ (Hotline, Telegram support).
- Thiết lập SEO: Meta title, Description, Keywords.

### Cấu Hình API & Email
- **SMTP**: Thiết lập Server, Port, User, Pass để Bot gửi mail (thử nghiệm bằng công cụ Test Mail).
- **SePay**: Nhập API Key và số tài khoản để kích hoạt nạp tiền tự động.
- **Telegram**: Nhập Bot Token và Secret để kết nối ứng dụng chat.

## 🛠 5. Chế Độ Bảo Trì
- Kích hoạt chế độ bảo trì toàn trang hoặc bảo trì từng loại nạp tiền.
- Khi bật, người dùng sẽ nhận được thông báo lịch sự và các tính năng nhạy cảm sẽ bị tạm khóa.

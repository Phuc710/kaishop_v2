# Kiến Trúc Bảo Mật & Phòng Thủ (Security Architecture)

Hệ thống KaiShop V2 được thiết kế với nhiều lớp bảo mật để chống lại các lỗ hổng phổ biến và đảm bảo an toàn cho dữ liệu người dùng.

---

## 🔐 1. Bảo Mật Xác Thực (Authentication Security)

- **Mật Khẩu**: Lưu trữ dưới dạng Hash (`password_hash` của PHP) sử dụng thuật toán BCRYPT, không bao giờ lưu mật khẩu dưới dạng văn bản thuần.
- **Session Management**: Sử dụng session an toàn trên Server, tự động xóa phiên làm việc khi người dùng đăng xuất.
- **CSRF Protection**: Mọi Form và Request thay đổi dữ liệu (POST/PUT/DELETE) đều yêu cầu `csrf_token` được tạo duy nhất cho mỗi phiên.

## 🛡 2. Chống Tấn Công Spam & Flood (Anti-Flood)

Hệ thống sử dụng `AntiFloodService` để giám sát tần suất gửi yêu cầu:
- **Rate Limiting**: Giới hạn số lượng request từ một địa chỉ IP trong một khoảng thời gian ngắn.
- **Action Blocking**: Nếu vượt ngưỡng, IP sẽ bị tạm khóa (cooldown) để bảo vệ tài nguyên Server.
- **Bot Detection**: Phân tích hành vi để phân biệt người dùng thật và các script tự động.

## 🚫 3. Hệ Thống Khóa Đa Tầng (Multi-Layer Banning)

Kiểm soát quyền truy cập chặt chẽ thông qua `BanService`:
- **Web Ban**: Khóa tài khoản dựa trên `username` hoặc `IP`. Người dùng bị khóa sẽ không thể đăng nhập hoặc xem sản phẩm.
- **Telegram Ban**: Khóa dựa trên `telegram_id`. Bot sẽ từ chối mọi tương tác từ người dùng nằm trong danh sách đen.
- **Centralized Log**: Mọi hành vi bị chặn đều được ghi nhật ký để admin xem xét.

## 🛡 4. Bảo Vệ Dữ Liệu & API

- **Prepared Statements**: Tuyệt đối sử dụng tham số hóa (Prepared Statements) cho mọi truy vấn SQL thông qua lớp `Model.php`, loại bỏ hoàn toàn nguy cơ SQL Injection.
- **API Key Security**: Kết nối với SePay được xác thực qua Authorization header với độ dài API Key tiêu chuẩn.
- **Data Validation**: Sử dụng các Validators chuyên biệt để kiểm tra tính hợp lệ của dữ liệu đầu vào (Email, Số điện thoại, Số dư) trước khi xử lý.

## 👁 5. Giám Sát & Truy Vết (Auditing)

- **Admin Journal**: Bản ghi bất biến về mọi hành động của quản trị viên (thêm/sửa/xóa).
- **Security Logs**: Tự động ghi lại các hành vi đáng nghi như cố tình truy cập khu vực Admin mà không có quyền hoặc nhập sai mật khẩu nhiều lần.
- **Device Fingerprinting**: Lưu trữ thông tin trình duyệt và thiết bị của người dùng để đối chiếu trong các trường hợp tranh chấp hoặc khôi phục tài khoản.

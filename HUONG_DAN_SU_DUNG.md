# HƯỚNG DẪN SỬ DỤNG VÀ CÀI ĐẶT WEB KAISHOP V2

Tài liệu này cung cấp hướng dẫn chi tiết về cách cài đặt, cấu hình và sử dụng mã nguồn KaiShop V2 cho cả người dùng và quản trị viên.

## 1. Yêu Cầu Hệ Thống

*   **Web Server:** Apache (khuyến nghị dùng XAMPP/Laragon trên Windows hoặc LAMP/LEMP trên Linux).
*   **PHP:** Phiên bản 7.4 - 8.x (khuyến nghị 7.4 hoặc 8.0 để ổn định nhất).
*   **Database:** MySQL / MariaDB.
*   **Mod Rewrite:** Bắt buộc bật (để hỗ trợ đường dẫn thân thiện `.htaccess`).

## 2. Hướng Dẫn Cài Đặt

### Bước 1: Chuẩn bị mã nguồn
*   Giải nén mã nguồn vào thư mục gốc của web server (ví dụ: `C:\xampp\htdocs\kaishop_v2`).

### Bước 2: Nhập Cơ Sở Dữ Liệu (Database)
1.  Truy cập phpMyAdmin (thường là `http://localhost/phpmyadmin`).
2.  Tạo một database mới (ví dụ: `kaishop_db`).
3.  Chọn database vừa tạo, nhấn **Import (Nhập)**.
4.  Chọn file `csdl.sql` trong thư mục gốc của mã nguồn và nhấn **Go (Thực hiện)**.

### Bước 3: Cấu hình kết nối
Mở file `hethong/ketnoi.php` và chỉnh sửa thông tin cho phù hợp:
```php
<?php
session_start();
$chungapi_local = 'localhost'; // Máy chủ database
$chungapi_ten = 'root';        // Tên đăng nhập database
$chungapi_matkhau = '';        // Mật khẩu database
$chungapi_dulieu = 'kaishop_db'; // Tên database đã tạo ở Bước 2
// ...
?>
```

### Bước 4: Cấu hình Đường dẫn (Quan trọng)
Mở file `hethong/UrlHelper.php` và chỉnh sửa biến `$BASE_PATH`:

*   **Nếu chạy Local (ví dụ: localhost/kaishop_v2):**
    ```php
    private static $BASE_PATH = '/kaishop_v2';
    ```
*   **Nếu chạy trên Domain chính (ví dụ: kaishop.vn):**
    ```php
    private static $BASE_PATH = ''; // Để trống
    ```

### Bước 5: Cấu hình .htaccess
Mở file `.htaccess` ở thư mục gốc và chỉnh sửa `RewriteBase` và đường dẫn lỗi:

*   **Nếu chạy Local:**
    ```apache
    RewriteBase /kaishop_v2/
    ErrorDocument 404 /kaishop_v2/404.php
    ```
*   **Nếu chạy trên Domain chính:**
    ```apache
    RewriteBase /
    ErrorDocument 404 /404.php
    ```

---

## 3. Hướng Dẫn Dành Cho Thành Viên (User)

Giao diện người dùng cho phép khách hàng đăng ký, đăng nhập và sử dụng các dịch vụ trực tuyến.

### 3.1. Tài khoản
*   **Đăng ký/Đăng nhập:** Người dùng có thể tạo tài khoản mới hoặc đăng nhập để quản lý dịch vụ.
*   **Thông tin cá nhân:** Cập nhật thông tin, đổi mật khẩu tại trang `Profile`.
*   **Bảo mật:** Hệ thống hỗ trợ lấy lại mật khẩu qua Email (cần cấu hình SMTP trong Admin).

### 3.2. Nạp tiền
Hệ thống hỗ trợ 2 hình thức nạp tiền:
1.  **Nạp thẻ cào:** Chọn loại thẻ, mệnh giá và nhập mã thẻ/serial. Hệ thống xử lý tự động (cần kết nối API trong Admin).
2.  **Chuyển khoản (Nap Bank):** Hiển thị thông tin ngân hàng của Admin. Người dùng chuyển khoản theo nội dung hướng dẫn.

### 3.3. Sử dụng Dịch vụ
Khách hàng có thể mua và quản lý các dịch vụ sau:
*   **Mua Mã Nguồn (Source Code):** Xem danh sách code, mua code và tải về ngay lập tức.
*   **Tạo Website:** Chọn mẫu web có sẵn và tạo website tự động (yêu cầu hosting/server phù hợp).
*   **Mua Hosting:** Chọn gói hosting, thanh toán và quản lý hosting trực tiếp.
*   **Mua Tên Miền (Domain):** Tra cứu và đăng ký tên miền.
*   **Tạo Logo:** Dịch vụ thiết kế logo trực tuyến.
*   **Dịch vụ Subdomain:** Đăng ký subdomain miễn phí/có phí.

---

## 4. Hướng Dẫn Dành Cho Quản Trị Viên (Admin)

Để truy cập trang quản trị, truy cập đường dẫn: `http://domain-cua-ban/admin`
*(Lưu ý: Tài khoản Admin cần có `level = 9` trong bảng `users`)*

### 4.1. Quản Lý Thành Viên
*   **Danh sách thành viên:** Xem, tìm kiếm thành viên.
*   **Chỉnh sửa:**
    *   Cộng/Trừ tiền thành viên.
    *   Thay đổi cấp độ (Thành viên/CTV/Admin).
    *   Khoá tài khoản (Ban).

### 4.2. Cấu Hình Hệ Thống (Settings)
Tại menu **Cài Đặt**, bạn có thể tùy chỉnh:
*   **Thông tin Web:** Tên web, tiêu đề, mô tả, từ khóa (SEO).
*   **Giao diện:** Logo, Favicon, Banner.
*   **Thông báo:** Nội dung thông báo hiển thị (Popup/Marquee).
*   **Cấu hình SMTP:** Để gửi mail quên mật khẩu, thông báo đơn hàng.
*   **Bảo trì:** Bật/Tắt chế độ bảo trì website.

### 4.3. Quản Lý Dịch Vụ
Admin có quyền thêm, sửa, xóa các sản phẩm dịch vụ:
*   **Code:** Thêm mã nguồn mới, up ảnh, đặt giá, link tải.
*   **Mẫu Web:** Quản lý các mẫu website cho phép người dùng tạo.
*   **Hosting:**
    *   **Quản lý Server:** Thêm thông tin Server (IP, tài khoản Reseller/Root).
    *   **Gói Host:** Tạo các gói host (Dung lượng, Băng thông, Giá).
*   **Tên Miền & Subdomain:** Quản lý bảng giá tên miền và danh sách subdomain đã tạo.

### 4.4. Quản Lý Tài Chính
*   **Ngân hàng:** Thêm/Sửa/Xóa tài khoản ngân hàng hiển thị trang nạp tiền.
*   **Duyệt nạp tiền:** Xem lịch sử nạp thẻ và cộng tiền thủ công cho giao dịch chuyển khoản.
*   **Giftcode:** Tạo mã quà tặng để tặng tiền cho thành viên.

---

## 5. Các Vấn Đề Thường Gặp (FAQ)

**Q: Tại sao tôi truy cập vào web bị lỗi 404?**
A: Hãy kiểm tra file `.htaccess` và cấu hình `$BASE_PATH` trong `hethong/UrlHelper.php` xem đã khớp với đường dẫn thư mục chưa.

**Q: Làm sao để cấp quyền Admin?**
A: Truy cập phpMyAdmin, bảng `users`, sửa cột `level` của tài khoản mong muốn thành `9`.

**Q: Hình ảnh không hiển thị?**
A: Kiểm tra đường dẫn ảnh trong code hoặc database. Nếu dùng `$BASE_PATH` sai, ảnh sẽ lỗi link. Đảm bảo thư mục `assets/images` có quyền ghi (chmod 755 hoặc 777 nếu cần).

**Q: Gửi mail không được?**
A: Kiểm tra cấu hình Google SMTP trong trang Admin. Đảm bảo đã bật "Less Secure Apps" hoặc dùng "App Password" của Gmail.

---
*Tài liệu được tạo tự động bởi Trợ lý AI Antigravity.*

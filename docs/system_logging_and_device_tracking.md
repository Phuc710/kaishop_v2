# Tài Liệu Hệ Thống: System Logs & Device Fingerprinting 

Tài liệu này tổng hợp toàn bộ cơ chế theo dõi hành vi người dùng bằng Fingerprint nâng cao và cách hệ thống lưu trữ/truy xuất Logs (Nhật ký hệ thống).

---

## 1. Device Fingerprinting (Dấu Vân Tay Thiết Bị)

**Mục đích:** Thu thập một mã băm (`hash`) duy nhất dựa trên đặc điểm phần cứng và trình duyệt của người dùng để nhận diện các thiết bị giả mạo hoặc truy vết hành vi mờ ám (dành cho bảo mật Product).

### Các lớp thu thập (Layers)
Mã thu thập Fingerprint được viết trong thư viện JavaScript tùy chỉnh:
- **Vị trí file:** `assets/js/fingerprint.js`

Các thành phần được thu thập:
1. **Canvas & WebGL (`L3`)**: Vẽ các hình khối ẩn và lưu lại mã đồ hoạ của render engine. Thu thập tên Card màn hình (GPU vendor/renderer).
2. **Audio (`L3`)**: Tạo tín hiệu âm thanh giả lập qua `OfflineAudioContext` để lấy mã hash âm thanh đặc trưng của phần cứng.
3. **Fonts (`L3`)**: Kiểm tra danh sách Font chữ được cài đặt trên máy.
4. **Hardware_Info (`L4`)**: Lấy số nhân CPU (`hardwareConcurrency`), dung lượng RAM (`deviceMemory`), và nhận dạng màn hình cảm ứng (`maxTouchPoints`).
5. **Modern Client Hints**: Dùng `navigator.userAgentData` để lấy chính xác thương hiệu trình duyệt (v.d: `Google Chrome`, `Microsoft Edge`) và Platform cấp độ phần cứng.

### Ứng dụng thực tế
Fingerprint được gọi khi người dùng vào trang Admin, Đăng nhập (`login.php`), hoặc Đăng ký (`register.php`). Mã hash sẽ được lưu vào bảng `users` (Cột: `fingerprint`) và đẩy nguyên mảng JSON vào bảng `system_logs` (thông qua Payload).

---

## 2. Truy Xuất & Nhận Diện Thiết Bị (User Agent Parsing)

Thay vì chỉ lưu lại chuỗi `User-Agent` dài ngoằng không thể đọc được, hệ thống đã được trang bị một bộ Parser riêng để dịch chuỗi đó ra ngôn ngữ con người (OS, Browser, Device Type).

- **Vị trí file:** `app/Helpers/UserAgentParser.php`
- **Cách hoạt động:** Nhận chuỗi `$_SERVER['HTTP_USER_AGENT']` và dùng Regular Expression (Regex) để phân loại.

**Hỗ trợ nhận diện:**
- **OS (Hệ điều hành):** Windows 11/10/8/7, macOS, Linux, Ubuntu, iOS (iPhone/iPad), Android.
- **Browser (Trình duyệt):** Chrome, Safari, Firefox, Edge, Opera, Cốc Cốc, Yandex.
- **Device Type (Loại thiết bị):** Desktop, Mobile, Tablet.

---

## 3. Cơ Chế Lưu Logs (System Logging)

Mọi hành động quan trọng trong hệ thống (như Đăng nhập, Đổi cấu hình, Thực hiện thanh toán...) đều được lưu lại để quản trị viên (Admin) dễ dàng Tracking.

- **Vị trí file Controller:** `app/Helpers/Logger.php`

### Cách gọi Log tiêu chuẩn
Mỗi khi cần log lại sự kiện, các Developer sẽ gọi 1 trong 3 hàm tương ứng với Mức Độ (Severity):
```php
Logger::info('Tên_Module', 'tên_hành_động', 'Mô tả thân thiện', ['dữ_liệu_tuỳ_chọn' => 'value']);
Logger::warning('...'); // Dành cho các lỗi nhẹ, cảnh báo
Logger::danger('...'); // Dành cho các lỗi nghiêm trọng (hack, sai pass nhiều lần)
```

### Quá trình tự động Enrich (Làm giàu dữ liệu)
Bên trong hàm `Logger::log()`, hệ thống sẽ:
1. Lấy thông tin tài khoản người đang thao tác (`user_id`, `username`).
2. Lấy `IP_Address`.
3. Gọi `UserAgentParser::parse()` để bóc tách thông tin OS, Browser.
4. Tự động ***trích ghép*** các thông tin Device (OS, Browser, Device Type) vào cái mảng `$payload` mà Developer gửi vào.
5. Mã hoá tất cả thành JSON và lưu vào bảng `system_logs`.

---

## 4. Giao Diện Quản Trị Hệ Thống (Admin UI)

Trang hiển thị cho phép quản trị viên xem, tìm kiếm, và lọc nâng cao tất cả các hoạt động trên hệ thống. 
Giao diện này dùng chung (Unified Layout) với trang `Biến động số dư` và `Nhật ký hoạt động`, đảm bảo tính thống nhất UX/UI chuẩn Product.

- **Vị trí UI:** `views/admin/logs/journal.php`
- **Controller xử lý:** `app/Controllers/Admin/JournalController.php`
- **Model truy vấn SQL:** `app/Models/SystemLog.php`

### Tính năng DataTables nổi bật
- **Tìm kiếm thông minh (Smart Search trên chuỗi JSON):** Hàm `getLogsForJournal()` của SQL đã được cấu hình để tìm kiếm chữ không chỉ trong Tên hay Hành động, mà nó còn tự động `LIKE` tìm vào sâu cả trong cột `Payload` JSON. => Admin có thể copy nguyên đoạn mã Fingerprint Device Hash dán vào ô tìm kiếm là sẽ hiện ra mọi lịch sử của đúng thiết bị đó ngay tức khắc!
- **Lọc theo Mức độ (Severity Filter):** Dropdown tuỳ chọn giúp lọc nhanh các thao tác cảnh báo (Warning/Danger).
- **Timeago Hover:** Cột `Thời gian` sẽ hiển thị lịch chuẩn, nhưng di chuột vào (Hover tooltip) sẽ tính toán và hiện `... tiếng trước`, `... ngày trước`.
- **Hiển thị Payload thân thiện:** Tên ảo (Guest action) được fallback tự động để check trong Payload lấy tên thực khi người dùng mới Register/Login mà chưa có Session. Có Modal đẹp đẽ để xem trực quan chuỗi JSON.

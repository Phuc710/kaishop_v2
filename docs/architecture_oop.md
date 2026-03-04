# Kiến Trúc Hệ Thống & OOP

KaiShop V2 được xây dựng dựa trên kiến trúc **MVC (Model-View-Controller)** kết hợp với lớp **Service Layer** để xử lý logic nghiệp vụ, đảm bảo tính đóng gói và dễ dàng bảo trì.

## 🏗 Cấu Trúc Thư Mục Core

Hệ thống sử dụng các lớp nền tảng nằm tại `/core/`:

- **`Database.php`**: Singleton pattern để quản lý kết nối PDO tới MySQL.
- **`Model.php`**: Lớp cơ sở cho tất cả Models. Cung cấp các phương thức CRUD cơ bản (`find`, `all`, `create`, `update`, `delete`).
- **`Controller.php`**: Lớp cơ sở cho Controllers. Quản lý việc render view, trả về JSON, redirect và các tiện ích xử lý request (GET, POST).
- **`Router.php`**: Quản lý định tuyến từ URL tới các phương thức trong Controller.

## 🛠 Lớp Ứng Dụng (Application Layer)

Nằm trong thư mục `/app/`, được chia thành các thành phần:

### 1. Controllers (`/app/Controllers/`)
Điều hướng luồng xử lý và giao tiếp giữa View và Model/Service.
- Chia làm `Admin/` (quản trị) và `Api/` (xử lý webhook, ajax).

### 2. Models (`/app/Models/`)
Đại diện cho các bảng trong cơ sở dữ liệu. 
- Mọi Model đều kế thừa từ `Model.php`.
- Ví dụ: `Order.php`, `Product.php`, `User.php`.

### 3. Services (`/app/Services/`)
Nơi chứa logic nghiệp vụ phức tạp. Đây là tầng trung gian giúp Controller không bị quá tải.
- **`AuthService.php`**: Xử lý đăng nhập, bảo mật.
- **`TelegramBotService.php`**: Logic xử lý tin nhắn và tương tác Bot.
- **`PurchaseService.php`**: Quy trình mua hàng, trừ tiền, kiểm tra tồn kho.

### 4. Middlewares (`/app/Middlewares/`)
Kiểm tra điều kiện trước khi vào Controller (ví dụ: `CheckAuth`, `CheckAdmin`).

## 🧱 Nguyên Tắc Lập Trình (Coding Standards)

- **OOP Toàn Diện**: Mọi thành phần đều được đóng gói trong Class.
- **Dependency Injection**: Các Service thường được khởi tạo và sử dụng lẫn nhau để đảm bảo tính module.
- **Prepared Statements**: Luôn sử dụng PDO tham số hóa để chống SQL Injection (đã được tích hợp sẵn trong `Model.php`).
- **Centralized Time Management**: Sử dụng `TimeService` để đồng bộ hóa thời gian trên toàn hệ thống.

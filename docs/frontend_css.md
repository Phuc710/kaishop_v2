# Hệ Thống Giao Diện & CSS

KaiShop V2 sử dụng hệ thống CSS linh hoạt, kết hợp giữa các thư viện hiện đại và CSS thuần (Vanilla CSS) được module hóa để tối ưu hiệu suất và khả năng tùy chỉnh.

## 🎨 Cấu Trúc CSS (`/assets/css/`)

Giao diện được chia thành các file riêng biệt dựa trên chức năng:

- **`style.css`**: File CSS chính, chứa các biến màu sắc (CSS Variables), reset và các thành phần dùng chung toàn trang (Header, Footer, Banners).
- **`user-pages.css`**: Tập trung vào các trang dành cho người dùng (Profile, Lịch sử nạp, Dashboard người dùng).
- **`admin.css` & `admin-pages.css`**: Hệ thống giao diện quản trị với sidebar, bảng dữ liệu và form điều khiển.
- **`responsive.css`**: Xử lý các điểm gãy (breakpoints) để đảm bảo trang web hiển thị tốt trên Mobile và Tablet.
- **`home.css`**: Các style đặc thù cho trang chủ (Grid sản phẩm, Hero section).

## 💎 Design System & UI Components

### 1. Palette Màu Sắc
Hệ thống sử dụng các biến CSS để quản lý màu sắc đồng nhất:
- `--primary-color`: Màu chủ đạo (thường là Blue/Indigo).
- `--secondary-color`: Màu phụ.
- `--bg-body`: Màu nền chính (nhẹ nhàng, thường là xám nhạt #f0f4f8).
- `--card-bg`: Màu nền của các khối nội dung (Trắng nội dung).

### 2. Thành Phần Điều Khiển (UI Elements)
- **Buttons**: Các hiệu ứng hover mượt mà, hỗ trợ nhiều kích thước và màu sắc trạng thái (Success, Danger, Warning).
- **Modals**: Giao diện Modal hiện đại, hỗ trợ các hiệu ứng animation khi mở/đóng.
- **Tables**: Tích hợp với DataTables để xử lý dữ liệu lớn, hỗ trợ phân trang và tìm kiếm.

## 🚀 Thư Viện Hỗ Trợ

- **Bootstrap**: Sử dụng cho hệ thống Grid và các utility classes cơ bản.
- **AOS (Animate On Scroll)**: Xử lý các hiệu ứng hoạt ảnh khi người dùng cuộn trang.
- **Swiper & GLightbox**: Các thư viện xử lý trình chiếu ảnh và slider sản phẩm.
- **Confetti-effect**: Hiệu ứng pháo giấy chúc mừng khi hoàn thành hành động (như nạp tiền thành công).

## 🌙 Chế Độ Giao Diện (Themes)
Hệ thống hỗ trợ cấu trúc thân thiện với các tùy chỉnh giao diện đơn giản thông qua việc ghi đè (override) các biến CSS tại `style.css`.
- Các trang lỗi (404, 500) và trang Banned có những phong cách riêng đặc thù (như hiệu ứng Matrix trên trang Banned).

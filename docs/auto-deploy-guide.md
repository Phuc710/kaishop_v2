# Hướng dẫn Cấu hình Auto-Deploy (GitHub -> Hosting)

Hệ thống tự động cập nhật code lên Hosting mỗi khi bạn thực hiện lệnh `git push` lên nhánh `main` trên GitHub.

---

## 🏗 Cơ chế hoạt động
1. **Developer**: `git push origin main`.
2. **GitHub**: Gửi một tín hiệu (Webhook) kèm mật khẩu bí mật tới `https://kaishop.id.vn/deploy.php`.
3. **Server (deploy.php)**: 
   - Kiểm tra mật khẩu (Secret) trong file `.env`.
   - Nếu khớp, tự động chạy lệnh `git pull origin main`.
   - Ghi log kết quả vào `storage/logs/deploy.log`.

---

## 📝 Các bước cài đặt chi tiết

### Bước 1: Chuẩn bị file `deploy.php`
File này phải nằm ở thư mục gốc của trang web (`public_html/`). Nội dung file đã được bao gồm trong bộ code.

### Bước 2: Cấu hình Mật khẩu bí mật (Secret)
Mật khẩu này dùng để đảm bảo chỉ GitHub mới có quyền ra lệnh cho server cập nhật code.

1. **Trên Hosting**: Mở file `.env` và thêm/sửa dòng sau:
   ```env
   DEPLOY_SECRET=KaiDeploy_7uTplfiWt0OfROs_B3MPmpLt8Z349qSW
   ```
2. **Trên GitHub**: 
   - Vào Repo -> **Settings** -> **Webhooks**.
   - Tìm Webhook của bạn (nếu chưa có thì nhấn **Add webhook**).
   - **Payload URL**: `https://kaishop.id.vn/deploy.php`
   - **Content type**: `application/json`
   - **Secret**: `KaiDeploy_7uTplfiWt0OfROs_B3MPmpLt8Z349qSW` (Phải khớp hoàn toàn với file .env).
   - **SSL verification**: Enable.
   - Nhấn **Add/Update Webhook**.

---

## 🚀 Cách sử dụng hàng ngày
Mỗi khi bạn sửa code ở máy tính (Local):
```bash
git add .
git commit -m "mô tả thay đổi"
git push origin main
```
Ngay sau lệnh này, server sẽ tự động cập nhật code mới nhất.

---

## 🛠 Xử lý sự cố (Troubleshooting)

### 1. Lỗi 403 Forbidden trên GitHub
- **Nguyên nhân**: Sai mật khẩu (Secret) hoặc file `.env` trên host chưa có dòng `DEPLOY_SECRET`.
- **Cách fix**: Kiểm tra lại dòng `DEPLOY_SECRET` trong file `.env` trên hosting.

### 2. Code không cập nhật (Mặc dù Webhook báo 200 OK)
- **Nguyên nhân**: Thư mục trên host bị kẹt Git hoặc sai nhánh.
- **Cách fix**: Mở **Terminal** trên hosting và chạy:
  ```bash
  cd domains/kaishop.id.vn/public_html
  git fetch origin
  git reset --hard origin/main
  ```

### 3. Xem lịch sử Deploy
Kiểm tra file log trên Hosting tại đường dẫn:
`public_html/storage/logs/deploy.log`

---
*Tài liệu được khởi tạo bởi Antigravity AI - 05/03/2026*

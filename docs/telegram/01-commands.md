# Danh Sách Lệnh Bot Telegram

## Lệnh User (Mọi người dùng)

| Lệnh | Mô tả | Yêu cầu Shadow Account? |
| :--- | :--- | :--- |
| `/start` | Chào mừng + hướng dẫn. Nhận diện Admin/User, hiện Menu. | Không |
| `/menu` | Hiện Menu chính (Inline Keyboard) với phân quyền | Không |
| `/shop` | Hiện danh mục sản phẩm | Không |
| `/wallet` | Xem số dư ví, tổng nạp | Có (tự tạo) |
| `/deposit <số_tiền>` | Tạo mã nạp tiền Bank Transfer (hết hạn 5 phút) | Có (tự tạo) |
| `/orders` | Xem 5 đơn hàng gần nhất | Có (tự tạo) |
| `/link <otp>` | Liên kết tài khoản web bằng mã OTP (tùy chọn) | Không |
| `/unlink` | Hủy liên kết tài khoản | Không |
| `/help` | Hiện danh sách tất cả lệnh (phân quyền) | Không |

> 💡 **Shadow Account**: Nếu user chưa `/link`, hệ thống tự tạo tài khoản `tg_{id}` để mua hàng ngay.

## Lệnh Admin

| Lệnh | Mô tả | Implemented |
| :--- | :--- | :--- |
| `/stats` | Thống kê nhanh: user, đơn hôm nay, doanh thu, outbox, worker health | ✅ |
| `/broadcast <nội dung>` | Gửi thông báo tới tất cả user đã link (qua Outbox Pattern) | ✅ |
| `/maintenance on\|off` | Bật/Tắt bảo trì web | ✅ |
| `/setbank <bank\|stk\|chủ TK>` | Cập nhật thông tin ngân hàng nhanh | ✅ |

## Inline Callback (Nút bấm)

| Callback Data | Hành động |
| :--- | :--- |
| `shop` | Mở danh mục sản phẩm |
| `cat_{id}` | Xem sản phẩm trong danh mục |
| `prod_{id}` | Xem chi tiết sản phẩm |
| `buy_{prodId}_{qty}` | Hiện màn xác nhận mua (kèm kiểm tra số dư) |
| `do_buy_{prodId}_{qty}` | Thực hiện mua hàng (với cooldown chặn double-click) |
| `wallet` | Xem ví |
| `deposit_menu` | Hướng dẫn nạp tiền |
| `orders` | Xem đơn hàng |
| `menu` | Quay về Menu chính |
| `help` | Hiện danh sách lệnh |
| `stats_admin` | Thống kê nhanh (chỉ Admin) |

## Xử lý lỗi

- Lệnh không tồn tại → `❌ Lệnh không hợp lệ. Gửi /help để xem danh sách lệnh.`
- `/deposit` không có số tiền → Hiện hướng dẫn cú pháp
- `/link` không có mã → Hiện hướng dẫn lấy OTP trên Website
- Số dư không đủ khi mua → Hiện thông báo + nút Nạp tiền

## Đăng ký Commands với BotFather

Gửi `/setcommands` cho @BotFather:

```
start - Bắt đầu / Chào mừng
menu - Mở Menu chính
shop - Xem sản phẩm
wallet - Xem số dư ví
deposit - Nạp tiền
orders - Lịch sử mua hàng
link - Liên kết tài khoản Web
unlink - Hủy liên kết
help - Trợ giúp & Danh sách lệnh
```

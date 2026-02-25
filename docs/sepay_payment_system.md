# Hệ Thống Nạp Tiền Ngân Hàng — SePay Webhook

> Tài liệu hướng dẫn thiết lập và vận hành thanh toán tự động qua SePay.

---

## Kiến Trúc Tổng Quan

```
Khách Hàng → /deposit → Tạo GD (pending) → Chuyển khoản ngân hàng
                                                     ↓
SePay phát hiện giao dịch → POST /api/sepay/webhook → Match code → Cộng tiền
                                                     ↓
                                          Long Polling → 🎉 Thành công!
```

---

## Cách Cấu Hình (Admin)

### Bước 1: Cài đặt trên KaiShop Admin

Truy cập **Admin → Cài đặt → CẤU HÌNH NGÂN HÀNG (SePay)** và điền:

| Field | Mô tả | Ví dụ |
|---|---|---|
| Tên Ngân Hàng | Tên hiển thị cho user | `MB Bank` |
| Số Tài Khoản | STK nhận tiền | `09696969690` |
| Chủ Tài Khoản | Tên chủ tài khoản | `NGUYEN THANH PHUC` |
| SePay API Key | API Key webhook từ SePay | `sepay_MaiYeuEm_2026_...` |

### Bước 2: Tạo Webhook trên SePay

Truy cập [my.sepay.vn](https://my.sepay.vn) → **WebHooks** → **Thêm mới**:

| Cấu hình | Giá trị |
|---|---|
| **Đặt tên** | `Kaishop` (tuỳ ý) |
| **Sự kiện** | Có tiền **vào** |
| **Tài khoản** | Chọn tài khoản ngân hàng đã đăng ký |
| **Bỏ qua nếu không có code** | **Có** |
| **Gọi đến URL** | `https://kaishop.id.vn/api/sepay/webhook` |
| **Xác thực thanh toán** | **Đúng** |
| **Gọi lại khi** | HTTP Status Code không 200-299 |
| **Kiểu chứng thực** | **API Key** |
| **Content type** | `application/json` |
| **API Key** | Cùng key đã điền ở Admin Settings |

### Bước 3: Cấu hình Code Thanh Toán trên SePay

SePay → **Công ty → Cấu hình chung → Cấu trúc mã thanh toán**:
- Tiền tố: `kai` (hệ thống tạo mã dạng `kaiXXXXXXXXXXX`)
- SePay sẽ tự nhận diện mã bắt đầu bằng `kai` trong nội dung CK

---

## Flow Chi Tiết

### 1. User Tạo Giao Dịch
```
POST /deposit/create   body: amount=100000
```
- Hệ thống tạo `pending_deposits` row (status=`pending`)
- Tạo mã duy nhất: `kaiABC123XYZ456` (15 ký tự random)
- Giao dịch pending tự huỷ hạn sau **5 phút**
- Mỗi user chỉ có **1 pending** tại một thời điểm

### 2. User Chuyển Khoản
- Nội dung CK phải chứa mã `kaiXXX` để SePay tự nhận diện
- SePay detect giao dịch → bắn POST webhook

### 3. Webhook Nhận & Xử Lý
```
POST /api/sepay/webhook
Header: Authorization: Apikey sepay_xxx
Body: { id, transferAmount, content, transferType, ... }
```

**Quy trình xử lý trong `SepayWebhookController::handle()`:**
1. Validate API Key từ header `Authorization`
2. Parse JSON body
3. Chỉ xử lý `transferType === "in"` (tiền vào)
4. **Chống trùng**: kiểm tra `sepay_transaction_id` đã tồn tại chưa
5. **Extract code**: regex tìm pattern `kai[A-Z0-9]{10,20}` trong `content`
6. Tìm `pending_deposits` matching → verify amount
7. **Cộng tiền**: `money += amount + bonus`, `tong_nap += amount`
8. Ghi `history_nap_bank` (lịch sử nạp)
9. Đánh dấu `completed`
10. Response `{"success": true}` HTTP 200

### 4. Long Polling (Frontend)
```
GET /deposit/status/{code}   mỗi 3 giây
```
- Trả về `{"status": "pending", "remaining": 270}`
- Khi webhook xử lý xong → `{"status": "completed", "new_balance": 210000}`
- Frontend nhận `completed` → SweetAlert 🎉 → redirect `/profile`

---

## Bonus Tự Động

| Mệnh giá | Bonus |
|---|---|
| ≥ 100.000đ | +10% |
| ≥ 200.000đ | +15% |
| ≥ 500.000đ | +20% |

Ví dụ: Nạp 200.000đ → nhận 200.000 + 30.000 (15%) = **230.000đ**

---

## Database

### Bảng `pending_deposits`
| Column | Type | Mô tả |
|---|---|---|
| `deposit_code` | VARCHAR(50) UNIQUE | Mã giao dịch (`kaiXXX`) |
| `amount` | BIGINT | Số tiền nạp |
| `bonus_percent` | INT | % bonus |
| `status` | ENUM | `pending`, `completed`, `cancelled`, `expired` |
| `sepay_transaction_id` | INT | ID giao dịch SePay (chống trùng) |

### Bảng `history_nap_bank`
Lưu lịch sử nạp hoàn tất, hiển thị trong Admin → Lịch sử nạp tiền.

### Cột mới trong `setting`
`bank_name`, `bank_account`, `bank_owner`, `sepay_api_key`

---

## Bảo Mật

- **API Key validation**: Header `Authorization: Apikey XXX` phải khớp với `sepay_api_key` trong DB
- **Anti-duplicate**: `sepay_transaction_id` unique — cùng 1 webhook gửi 2 lần chỉ xử lý 1
- **Auto-expire**: Giao dịch pending quá 5 phút tự chuyển `expired`
- **Single pending**: Tạo GD mới sẽ huỷ tất cả GD pending cũ của user
- **System Logging**: Mọi hành động (tạo, huỷ, webhook) đều ghi `system_logs`

---

## Routes

```php
// User-facing
GET  /deposit                → DepositController@index
POST /deposit/create         → DepositController@create
GET  /deposit/status/{code}  → DepositController@status
POST /deposit/cancel         → DepositController@cancel

// SePay Webhook (external API)
POST /api/sepay/webhook      → Api\SepayWebhookController@handle
```

---

## Xử Lý Lỗi

| Tình huống | Xử lý |
|---|---|
| Webhook không có code thanh toán | Log + return `{"success": true}` |
| Code không tìm thấy trong DB | Log + return `{"success": true}` |
| GD đã xử lý rồi | Return `{"success": true}` (tránh retry) |
| Số tiền không khớp | Log warning, vẫn xử lý theo số thực nhận |
| API Key sai | Return 401 Unauthorized |

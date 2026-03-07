# Binance Pay - Auto Deposit System

## Tổng quan

Hệ thống nạp tiền tự động qua Binance Pay. User chuyển USDT → hệ thống tự phát hiện → cộng tiền VND vào tài khoản.

## Flow hoạt động

```
User tạo lệnh nạp (web/telegram)
    │
    ├── Nhập Binance UID + Số tiền USDT
    ├── Hệ thống tạo pending_deposit (status=pending, TTL=5 phút)
    │
    ▼
User mở Binance App → Pay → Chuyển USDT đến Recipient UID
    │
    ▼
Cron Job chạy mỗi 10 giây (processPendingBinanceDeposits)
    │
    ├── 1. Lấy danh sách pending deposits (method=binance)
    ├── 2. Gọi Binance API: GET /sapi/v1/pay/transactions
    ├── 3. So khớp giao dịch: amount + receiverUID + time window
    ├── 4. Nếu khớp → processTransaction() → cộng tiền + ghi lịch sử
    │
    ▼
User nhận thông báo thành công (web + telegram)
```

## Cron Job trên Hosting

**Chỉ cần 1 lệnh duy nhất** cho tất cả (Binance scan + Telegram + outbox):

```bash
php /path/to/public/telegram/cron.php
```

### Cấu hình Cron trên Hosting:
```cron
* * * * * php /home/user/public_html/public/telegram/cron.php >> /dev/null 2>&1
```
→ Chạy mỗi phút. Bên trong file cron.php có **Sweep Window** (mặc định 50 giây, quét mỗi 10 giây) nên tự lặp lại nhiều lần trong 1 phút.

### Trên Local (XAMPP):
```powershell
while($true) { php public/telegram/cron.php; Start-Sleep -Seconds 5 }
```
**Lưu ý:** Không dùng `--poll` nếu server đã có Webhook active.

## Binance API Endpoint

- **URL:** `https://api.binance.com/sapi/v1/pay/transactions`
- **Method:** GET (signed with HMAC-SHA256)
- **Response format:**
```json
{
  "code": "000000",
  "message": "success",
  "data": [
    {
      "uid": 100000001,
      "counterpartyId": 100000002,
      "orderId": "ORD_EXAMPLE_123456",
      "orderType": "C2C",
      "transactionId": "TX_EXAMPLE_789012",
      "transactionTime": 1741336000000,
      "amount": "1",
      "currency": "USDT",
      "walletType": 1,
      "payerInfo": { 
        "name": "User_Example", 
        "type": "USER", 
        "binanceId": 100000003, 
        "unmaskData": false 
      },
      "receiverInfo": { 
        "binanceId": 100000001, 
        "unmaskData": false 
      }
    }
  ],
  "success": true
}
```

### Chi tiết các field quan trọng:

| Field | Kiểu dữ liệu | Ý nghĩa & Lưu ý |
|---|---|---|
| `code` | `string` | `000000` là thành công (khác với `status: 0` của các API Binance khác) |
| `transactionId` | `string` | ID giao dịch duy nhất dùng để đối soát chống nạp đè (duplicate) |
| `orderType` | `string` | `C2C` (user gửi cá nhân), `C2C_PAYMENT`, `PAY`, `PAID` |
| `transactionTime`| `integer` | Unix Timestamp tính bằng **milliseconds (ms)** |
| `amount` | `string` | Số tiền USDT (luôn là chuỗi để tránh sai số dấu phẩy động) |
| `payerInfo` | `object` | Thông tin người nạp. `binanceId` có thể bị rỗng nếu là `C2C` từ bên ngoài |
| `receiverInfo` | `object` | Thông tin Shop. Phải khớp với `binance_uid` cài trong Setting |
| `status` | `string` | Thường nằm trong `data[0]`, giá trị: `SUCCESS`, `COMPLETED`, `PAID` |

## Tiêu chí khớp giao dịch (Matching Criteria)

| Tiêu chí | Mô tả |
|---|---|
| **Receiver UID** | `receiverInfo.binanceId` phải khớp với UID shop (bắt buộc) |
| **Payer UID** | Chỉ check khi API trả về `payerInfo.binanceId` (C2C có thể không có) |
| **Số tiền** | So sánh chính xác 8 chữ số thập phân (e.g. `1.00000000`) |
| **Thời gian** | Trong cửa sổ: `created_at - 120s` → `created_at + TTL + 120s` |
| **Currency** | Phải là `USDT` |
| **Order Type** | Cho phép: `C2C`, `C2C_PAYMENT`, `PAY`, `PAID` |
| **Status** | Cho phép: `SUCCESS`, `COMPLETED`, `PAID` (hoặc rỗng) |

## Chống Duplicate

1. **`extractTransactionId()`** → lấy `transactionId` / `orderId` từ Binance response
2. **`isTransactionProcessed()`** → check bảng `binance_transactions` xem đã xử lý chưa
3. **DB Transaction + FOR UPDATE lock** → race-safe khi nhiều cron instance chạy song song
4. **Single Run Lock** → `acquireSingleRunLock('kaishop_telegram_cron')` chỉ cho 1 instance chạy

## Bảo mật

| Cơ chế | Chi tiết |
|---|---|
| **API Keys** | Mã hóa AES-256-GCM trong DB (SecureCrypto) |
| **HMAC Signing** | Mọi request Binance đều ký bằng HMAC-SHA256 |
| **UID Matching** | `hash_equals()` chống timing attack |
| **Amount Precision** | `number_format($amount, 8)` chống floating-point |
| **Time Window** | 5 phút TTL + ±120s skew, chống replay attack |
| **CSRF** | Tất cả POST endpoints đều có token |
| **Ownership** | Check `user_id` trước khi cộng tiền |

## Cấu trúc Database

### `pending_deposits`
- `method = 'binance'` — phân biệt với bank transfer
- `usdt_amount` — số USDT cần chuyển
- `payer_uid` — UID Binance của người gửi (user tự nhập)
- `status` — pending → completed/expired/cancelled
- TTL: 300 giây (5 phút)

### `binance_transactions`
- `tx_id` — Binance transactionId (unique, dedup key)
- `usdt_amount`, `vnd_credit`, `bonus_vnd`, `bonus_percent`
- `payer_uid`, `receiver_uid`, `currency`, `transaction_time`

## Lưu ý quan trọng

1. **C2C vs PAY:** Binance C2C transactions (`orderType: "C2C"`) có thể KHÔNG trả về `payerInfo.binanceId` khi query từ phía receiver. Hệ thống đã xử lý trường hợp này bằng relaxed matching.

2. **API Response Format:** Binance Pay API dùng `"code": "000000"` + `"success": true` thay vì `"status": "0"`. Code đã handle cả 2 format.

3. **User quốc tế:** Hỗ trợ mọi timezone. Binance API trả timestamp UTC (milliseconds), server convert về Unix seconds → so sánh chính xác.

4. **Rate Limiting:** Binance API giới hạn requests. Sweep window 10-giây interval giúp tránh bị block.
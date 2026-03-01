# Ví & Nạp Tiền Qua Telegram Bot

## Xem số dư (/wallet)

```
User: /wallet
Bot:  💰 THÔNG TIN VÍ

      👤 Tài khoản: phucc710
      💵 Số dư: 150,000đ
      📈 Tổng nạp: 500,000đ

      👉 Nạp thêm tiền bằng lệnh: /deposit <số_tiền>
      [💳 Nạp tiền ngay]  [🔙 Menu]
```

- Yêu cầu: Phải link tài khoản.
- Dùng chung `User::findById()` để lấy `money`, `tong_nap`.

## Nạp tiền (/deposit)

### Luồng nạp

```
User: /deposit 50000
Bot:  💳 THÔNG TIN CHUYỂN KHOẢN

      🏦 Ngân hàng: MB Bank
      👤 Chủ TK: NGUYEN THANH PHUC
      🔢 Số TK: 09696969690
      💰 Số tiền: 50,000đ
      📝 Nội dung: KS_phucc710_A1B2C3

      ⏰ THỜI GIAN: Giao dịch hết hạn sau 5 phút.
      ⚠️ LƯU Ý: Phải đúng nội dung chuyển khoản để được cộng tiền tự động.
```

### Backend

```php
// TelegramBotService::cmdDeposit()
$res = $this->depositService->createBankDeposit($user, $amount, $siteConfig);
```

- Dùng chung `DepositService::createBankDeposit()` — cùng logic với Web.
- Tạo row trong bảng `pending_deposits`.
- Khi SePay webhook confirm chuyển khoản → tự động cộng tiền + gửi thông báo qua Outbox.

### Cấu hình

Thông tin bank lấy từ bảng `setting`:
- `bank_name`, `bank_account`, `bank_owner`
- `sepay_api_key` (cho webhook tự động)
- `bonus_1_amount`, `bonus_1_percent`, ... (thưởng nạp)

### Xử lý lỗi

| Tình huống | Hành vi |
| :--- | :--- |
| Chưa link | `⚠️ Bạn chưa liên kết tài khoản.` |
| Hết hạn (> 5 phút) | SePay Webhook từ chối cộng tiền tự động. |
| Thiếu tham số | Hiện hướng dẫn: `/deposit <số_tiền>` + ví dụ |

# Luồng Mua Hàng Qua Telegram Bot

## Tổng quan

Bot hỗ trợ mua hàng trực tiếp, dùng chung backend `PurchaseService::purchaseWithWallet()` với Web. Hệ thống hỗ trợ 3 loại sản phẩm:

| Loại | `product_type` | Giao hàng |
| :--- | :--- | :--- |
| Tài khoản (từ kho) | `account` | Giao ngay nội dung từ `product_stock` |
| Source code / Link | `link` | Giao link download |
| Yêu cầu thông tin | `requires_info=1` | Đơn pending, Admin xử lý thủ công |

## Flow chi tiết

```
User: /shop
Bot:  🛍 DANH MỤC SẢN PHẨM
      [📁 Gmail]  [📁 VPN]  [📁 Tool]

User: Click [📁 Gmail]
Bot:  🎁 DANH SÁCH SẢN PHẨM
      [🎁 Gmail Trial (Ver SĐT) | 10,000đ]
      [🎁 Gmail Cổ 2006-2025 | 30,000đ]

User: Click [🎁 Gmail Trial]
Bot:  📦 Gmail Trial (Ver SĐT)
      💰 Giá: 10,000đ
      📦 Tồn kho: 4
      📝 Gmail Trial bắt buộc phải verify SĐT...
      [🛒 MUA NGAY]  [🔙 Quay lại]

User: Click [🛒 MUA NGAY]
Bot:  🛒 XÁC NHẬN MUA HÀNG
      Sản phẩm: Gmail Trial (Ver SĐT)
      Số lượng: 1
      Thành tiền: 10,000đ
      🛑 Hệ thống sẽ trừ tiền vào ví web của bạn.
      [❌ HỦY]  [✅ XÁC NHẬN MUA]

User: Click [✅ XÁC NHẬN MUA]
Bot:  🎉 THANH TOÁN THÀNH CÔNG!
      🧾 Mã đơn: 13J9YECHXTLI
      📱 Sản phẩm: Gmail Trial (Ver SĐT)
      🔑 Nội dung:
      28nguyenan1009@gmail.com | Zhy99!!! | ver
```

## Xử lý lỗi

| Tình huống | Hành vi Bot |
| :--- | :--- |
| Chưa link tài khoản | `⚠️ Bạn phải liên kết tài khoản web trước khi mua. Gửi /start để hướng dẫn.` |
| Không đủ tiền | `❌ LỖI: Số dư không đủ. Bạn cần nạp thêm X đ.` |
| Hết hàng (stock=0) | Nút "MUA NGAY" không hiện. Hiện `📦 Tồn kho: Hết hàng` |
| Sản phẩm đã tắt | Bot không trả về sản phẩm (filter `status=ON`) |

## Code backend dùng chung

```php
// TelegramBotService::cbDoBuy()
$result = $this->purchaseService->purchaseWithWallet($prodId, $user, ['quantity' => $qty]);
```

Hàm `purchaseWithWallet()` là hàm gốc của Web — xử lý:
- Validate sản phẩm, stock
- Trừ wallet
- Tạo order
- Giao stock_content (nếu có)
- Return `['success' => true, 'order' => [...]]`

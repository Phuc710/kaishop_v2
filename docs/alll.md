🏗 Kiến Trúc Tổng Quan (MVC + Service Layer)
index.php → config/app.php + config/routes.php → Router → Controller → Service → Model → DB
Layer	Thư mục	Vai trò
Core	/core/	Router, Database (Singleton), Model base, Controller base
Controllers	/app/Controllers/	Nhận request, gọi service, trả view/JSON
Services	/app/Services/	Business logic phức tạp
Models	/app/Models/	CRUD với DB (PDO)
Views	/views/	HTML templates
Config	/config/	app.php (autoload) + routes.php (toàn bộ routes)
🗺 Route Map (config/routes.php)
User-facing
Method	URL	Handler
GET	/	HomeController@index
GET/POST	/login, /register, /logout	AuthController
GET	/product/{id}	ProductController@show
POST	/product/{id}/purchase	ProductController@purchase
GET	/balance	DepositController@balance → redirect /balance/bank
GET	/balance/{method}	DepositController@balanceMethod
GET	/history-balance	HistoryController@index
POST	/api/history-balance	HistoryController@data (DataTables AJAX)
POST	/deposit/create	DepositController@create
GET	/deposit/status/{code}	DepositController@status
GET	/deposit/status-wait/{code}	DepositController@statusWait (long-polling)
POST	/deposit/cancel	DepositController@cancel
API / Webhooks
Method	URL	Handler
POST	/api/sepay/webhook	Api\SepayWebhookController@handle
POST	/api/telegram/webhook	TelegramBotController@handleWebhook
Admin
URL Pattern	Description
/admin	Dashboard
/admin/users/**	Quản lý user (addMoney, subMoney, ban)
/admin/logs/balance-changes	Xem lịch sử biến động số dư
/admin/logs/deposits	Xem lịch sử nạp tiền
/admin/finance/giftcodes/**	Mã giảm giá
/admin/telegram/**	Tất cả config Telegram
💰 Hệ Thống Balance — Flow Chi Tiết
1. Nạp Tiền (Deposit Flow)
User → POST /deposit/create
       ↓
DepositController@create
  → AuthService::requireAuth()
  → validateCsrf()
  → DepositService::createBankDeposit($user, $amount, $siteConfig)
      ├── Validate MIN (10,000đ) / MAX (50,000,000đ)
      ├── Tính bonusPercent từ bonusTiers (config admin)
      └── PendingDeposit::createDeposit()
          ├── cancelAllPendingByUser() — hủy pending cũ
          ├── markExpired() — expire toàn global
          ├── generate code: "kai" + 8 random chars
          └── INSERT vào pending_deposits
  → Logger::info('Billing', 'deposit_created', ...)
  → Return JSON {success, data: {deposit_code, qr_url, ...}}
User chuyển khoản (nội dung = deposit_code)
       ↓
SePay POST /api/sepay/webhook
       ↓
SepayWebhookController@handle
  1. Validate API Key (header: "Apikey {KEY}") — fail-closed
  2. Parse JSON body
  3. Extract fields (sepayId, transferAmount, content, bankName, bankOwner...)
  4. Anti-duplicate: findBySepayId() — nếu đã xử lý → return 200
  5. Extract depositCode từ content (regex: /\b(kai[A-Z0-9]{8,20})\b/)
  6. findByCode() → check status (pending/expired)
  7. Verify amount (log nếu mismatch, vẫn xử lý)
  8. Tính bonus: bonusAmount = transferAmount * bonusPercent / 100
     totalCredit = transferAmount + bonusAmount
  9. UPDATE users SET money += totalCredit, tong_nap += transferAmount
  10. Gửi Telegram trực tiếp (max 3s) → fallback TelegramOutbox queue
  11. PendingDeposit::markComplete(id, sepayId)
  12. INSERT history_nap_bank (với source_channel, bank_name, bank_owner)
  13. BalanceChangeService::record() → INSERT lich_su_bien_dong_so_du
  14. Logger::info + TelegramLog::log
  15. Return {success: true, message: "Deposit credited"}
2. Mua Hàng (Purchase Flow)
POST /product/{id}/purchase
↓
ProductController@purchase
↓
PurchaseService (app/Services/PurchaseService.php)
  → Kiểm tra balance >= giá
  → Kiểm tra tồn kho
  → Áp dụng GiftCode (nếu có)
  → Trừ tiền: UPDATE users SET money = money - price
  → Xuất stock từ product_stock
  → Ghi lịch sử: INSERT lich_su_hoat_dong (change_amount âm)
  → BalanceChangeService::record() (change_amount âm)
3. Xem Lịch Sử Balance
GET /history-balance → HistoryController@index → view profile/history-balance
POST /api/history-balance → HistoryController@data (DataTables AJAX)
  → History Model::getUnifiedRows() — merge 2 nguồn:
      ├── lich_su_hoat_dong (purchases)   → source='activity'
      └── history_nap_bank (deposits)     → source='deposit'
  → Lọc theo search/time_range/sort_date
  → Sort DESC theo event_ts
  → calculateRunningBalances() — tính before/after từ current balance
🗃 Các Bảng DB Liên Quan Đến Balance
Bảng	Mô tả	Các cột quan trọng
users	Người dùng	money, tong_nap
pending_deposits	Giao dịch nạp đang chờ	deposit_code, amount, bonus_percent, status, source_channel, sepay_transaction_id
history_nap_bank	Lịch sử nạp hoàn tất	trans_id, username, thucnhan, type, ctk, stk, bank_name, bank_owner, source_channel
lich_su_hoat_dong	Lịch sử mua hàng	username, hoatdong, gia (âm = trừ tiền)
lich_su_bien_dong_so_du	Log biến động số dư tập trung	user_id, before_balance, change_amount, after_balance, reason, source_channel
🔧 Services Quan Trọng (Balance-related)
Service	File	Chức năng
BalanceChangeService	Services/BalanceChangeService.php	Ghi log mọi thay đổi số dư (unified writer)
DepositService	Services/DepositService.php	Logic nạp tiền, tính bonus tiers, QR code, danh sách phương thức
PurchaseService	Services/PurchaseService.php	Logic mua hàng, trừ tiền, xuất kho
TimeService	Services/TimeService.php	Quản lý thời gian tập trung (nowTs, nowSql, normalizeApiTime)
🔑 SourceChannelHelper
Tất cả transactions đều ghi source_channel:

0 = WEB (user qua website)
1 = TELEGRAM (user qua Telegram Bot)
Cột source_channel được auto-migrate vào các bảng khi chưa tồn tại (trong constructor của PendingDeposit, BalanceChangeService, SepayWebhookController).
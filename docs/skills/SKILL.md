---
name: kaishop_coding_skill
description: Tài liệu kĩ năng code chuẩn toàn diện cho dự án KaiShop V2 — kiến trúc MVC, database, helper, quy ước, và quy trình phát triển.
---
USE VIETNAMESE SPEAKING 
# KaiShop V2 — SKILL: Kĩ Năng Code Chuẩn
CODE LÀ KHÔNG ĐƯỢC HARD COde nha linh động OOP
## 1. Cấu Trúc Thư Mục Dự Án

```
kaishop_v2/
├── index.php              # Entry point (Router dispatch)
├── .env                   # Biến môi trường (DB, SMTP, API keys)
├── csdl.sql               # Schema DB chuẩn — BẮT BUỘC cập nhật khi thay đổi DB
│
├── config/
│   ├── app.php            # Autoload, BASE_URL, session, timezone
│   └── routes.php         # Tất cả routes [method, path, handler]
│
├── core/                  # Framework core (KHÔNG SỬA nếu không cần thiết)
│   ├── Router.php         # Dispatch URL → Controller@method
│   ├── Controller.php     # Base: view(), json(), redirect(), post(), get()
│   ├── Model.php          # Base: find(), all(), create(), update(), delete(), query()
│   └── Database.php       # Singleton PDO connection
│
├── app/
│   ├── Controllers/       # PascalCase, extends Controller
│   │   └── Admin/         # Admin controllers (namespace Admin\\)
│   ├── Models/            # PascalCase, extends Model, $table = 'ten_bang'
│   ├── Services/          # Logic nghiệp vụ phức tạp (>3 dòng logic thuần)
│   ├── Helpers/           # EnvHelper, FormatHelper, etc.
│   ├── Validators/        # Validate input
│   └── Middlewares/       # Auth, CSRF, etc.
│
├── views/                 # View files (chỉ HTML + PHP echo)
│   ├── wallet/index.php   # Ví dụ: views theo feature
│   └── ...
│
├── hethong/               # Hệ thống shared components
│   ├── config.php         # Legacy config + helper functions + get_setting()
│   ├── UrlHelper.php      # url(), asset(), ajax_url()
│   ├── SwalHelper.php     # SwalPHP class (PHP-side alerts)
│   ├── head2.php          # <head> shared (CSS, meta)
│   ├── nav.php            # Navigation bar
│   ├── foot.php           # Footer + scripts + popup include
│   └── popup.php          # Popup notification component
│
├── admin/                 # Admin panel (legacy, dần migrate sang MVC)
│   ├── head.php / nav.php / foot.php
│   ├── setting.php        # Cài đặt website
│   └── ...
│
├── assets/                # Static files
│   ├── css/               # styles.css, admin.css, policy.css
│   └── js/                # swal-helper.js, script.js, etc.
│
├── database/
│   ├── connection.php     # mysqli connection (legacy)
│   └── database_helper.php
│
└── docs/                  # Tài liệu dự án
    ├── skills/            # SKILL docs (file này)
    └── *.md               # Các ghi chú khác
```

---

## 2. Kiến Trúc MVC

### Luồng Request
```
Browser → index.php → Router.dispatch() → Controller@method → Model/Service → View/JSON
```

### 2.1 Route (`config/routes.php`)
```php
// Format: [METHOD, PATH, HANDLER]
['GET', '/wallet', 'WalletController@index'],
['POST', '/wallet/deposit', 'WalletController@deposit'],

// Route có param:
['GET', '/product/{id}', 'ProductController@show'],

// Admin (namespace):
['GET', '/admin/users', 'Admin\\UserController@index'],
```

> **BẮT BUỘC:** Mọi endpoint phải khai báo route. KHÔNG tạo file `.php` gọi trực tiếp.

### 2.2 Controller (`app/Controllers/`)
```php
class WalletController extends Controller {
    private $walletModel;

    public function __construct() {
        $this->walletModel = new Wallet();
    }

    // GET → Render view
    public function index() {
        $this->view('wallet/index', ['balance' => 100000]);
    }

    // POST → Xử lý logic, trả JSON
    public function deposit() {
        $amount = $this->post('amount', 0);
        if ($amount <= 0) {
            return $this->json(['success' => false, 'message' => 'Số tiền không hợp lệ'], 400);
        }
        return $this->json(['success' => true, 'message' => 'Nạp tiền thành công']);
    }
}
```

**Base Controller methods:**
| Method | Mô tả |
|---|---|
| `$this->view('path', $data)` | Render file `views/path.php`, extract `$data` |
| `$this->json($data, $code)` | Trả JSON + exit |
| `$this->redirect($url)` | Redirect + exit |
| `$this->post($key, $default)` | Lấy `$_POST[$key]` |
| `$this->get($key, $default)` | Lấy `$_GET[$key]` |

### 2.3 Model (`app/Models/`)
```php
class Wallet extends Model {
    protected $table = 'wallets';

    public function getBalanceByUserId($userId) {
        $stmt = $this->db->prepare("SELECT balance FROM `{$this->table}` WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 0;
    }
}
```

**Base Model methods (PDO):**
| Method | Mô tả |
|---|---|
| `find($id)` | SELECT * WHERE id = ? |
| `all()` | SELECT * |
| `create($data)` | INSERT INTO, return ID |
| `update($id, $data)` | UPDATE SET WHERE id = ? |
| `delete($id)` | DELETE WHERE id = ? |
| `query($sql, $params)` | Raw prepared statement |

### 2.4 View (`views/`)
```html
<!-- views/wallet/index.php -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <base href="../../" />
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Ví của tôi</title>
</head>
<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
    <main>
        <h3>Số dư: <?= number_format($balance) ?>đ</h3>
    </main>
    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>
</html>
```

---

## 3. Database Schema (`csdl.sql`)

### Bảng hiện có

| Bảng | Mô tả | Cột chính |
|---|---|---|
| `categories` | Danh mục sản phẩm | id, name, status |
| `products` | Sản phẩm | id, name, price, category_id, status, is_hidden, is_pinned |
| `khocode` | Kho source code | id, title, gia, link, demo, status |
| `gift_code` | Mã giảm giá | id, giftcode, giamgia, type, product_ids, min/max_order, expired_at |
| `users` | Người dùng | id, username, password, email, level, money, tong_nap, bannd, ip_address, user_agent, last_login |
| `lich_su_mua_code` | Lịch sử mua code | id, trans_id, username, loaicode, status |
| `lich_su_hoat_dong` | Lịch sử hoạt động | id, username, hoatdong, gia |
| `setting` | Cài đặt website | id, ten_web, logo, popup_template, thongbao, email/smtp... |

### Quy tắc cập nhật Database

> **BẮT BUỘC:** Khi thêm/sửa cột hoặc bảng, PHẢI cập nhật file `csdl.sql` để đồng bộ.

**Thêm cột mới:**
```sql
-- 1. Chạy ALTER trên DB hiện tại
ALTER TABLE `setting` ADD COLUMN `popup_template` VARCHAR(10) NOT NULL DEFAULT '1';

-- 2. Cập nhật CREATE TABLE trong csdl.sql
-- 3. Cập nhật INSERT mẫu trong csdl.sql (nếu có)
```

**Thêm bảng mới:**
```sql
-- 1. Thêm CREATE TABLE vào csdl.sql (trước COMMIT)
CREATE TABLE `ten_bang_moi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ten_cot` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ON',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ten_bang_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Chạy SQL trên DB hiện tại
-- 3. Tạo Model tương ứng trong app/Models/
```

---

## 4. Helper Functions

### 4.1 URL Helper — KHÔNG BAO GIỜ HARDCODE URL

> **QUAN TRỌNG:** Toàn bộ URL trong dự án đều đi qua UrlHelper.
> Chỉ cần đổi **1 dòng** `APP_DIR` trong `hethong/config.php` là TẤT CẢ tự đổi theo.

**Cấu hình trung tâm** (`hethong/config.php`):
```php
// Localhost:   define('APP_DIR', '/kaishop_v2');
// Production:  define('APP_DIR', '');   ← chỉ đổi dòng này khi deploy
```

**Khi lên production** (ví dụ `kaishop.id.vn`):
- Đổi `APP_DIR` thành `''` (rỗng)
- Toàn bộ `url()`, `asset()`, `ajax_url()` tự cập nhật — KHÔNG cần sửa bất kì file nào khác

**Các hàm URL helper** (`hethong/UrlHelper.php`):
```php
// Tạo link trang:
url('admin/users')            // Local:  /kaishop_v2/admin/users
                              // Prod:   /admin/users

// Tạo link asset (CSS, JS, ảnh):
asset('assets/css/style.css') // Local:  /kaishop_v2/assets/css/style.css
                              // Prod:   /assets/css/style.css

// Tạo link AJAX:
ajax_url('api/products')      // Local:  /kaishop_v2/api/products
                              // Prod:   /api/products
```

**Trong View (HTML):**
```html
<!-- ĐÚng ✅ — dùng helper -->
<a href="<?= url('product/1') ?>">Sản phẩm</a>
<img src="<?= asset('assets/images/logo.png') ?>">
<script src="<?= asset('assets/js/main.js') ?>"></script>

<!-- SAI ❌ — hardcode path -->
<a href="/kaishop_v2/product/1">Sản phẩm</a>
<img src="/kaishop_v2/assets/images/logo.png">
```

**Trong JavaScript (AJAX):**
```html
<script>
// ĐÚng ✅
fetch('<?= url("api/products") ?>')

// SAI ❌
fetch('/kaishop_v2/api/products')
</script>
```

### 4.2 Setting Helper
```php
get_setting('ten_web', 'KaiShop')     // Đọc từ bảng setting
get_setting('tele_admin', '#')        // Fallback nếu rỗng
```

### 4.3 SweetAlert2
**JavaScript** (`assets/js/swal-helper.js`):
```js
SwalHelper.toast('Thông báo', 'success');
SwalHelper.success('OK!');
SwalHelper.error('Lỗi!');
SwalHelper.successRedirect('Đã lưu!', '/admin');
SwalHelper.confirmDelete(() => { /* xử lý xoá */ });
```

**PHP** (`hethong/SwalHelper.php`):
```php
SwalPHP::successBack('Thành công!');
SwalPHP::errorBack('Lỗi!');
SwalPHP::successRedirect('OK!', '/admin');
```

### 4.4 Environment
```php
EnvHelper::get('DB_HOST', 'localhost');  // Đọc từ .env
```

---

## 5. Quy Tắc Bắt Buộc

### Code Style
1. **Class/Controller:** PascalCase → `PaymentController`, `UserService`
2. **Method/Function:** camelCase → `getBalance()`, `showLogin()`
3. **Biến DB:** snake_case → `tong_nap`, `created_at`
4. **Constant:** UPPER_SNAKE → `BASE_URL`, `DB_HOST`

### Bảo mật
1. **KHÔNG** hardcode token, password, API key trong `.php` — dùng `.env`
2. **KHÔNG** dùng `mysql_query()` hoặc `mysqli_query()` trực tiếp trong code mới — dùng PDO prepared statement qua Model
3. **KHÔNG** echo input user trực tiếp — dùng `htmlspecialchars()`

### Architecture
1. **KHÔNG** tạo file `.php` endpoint riêng — phải qua Router + Controller
2. **KHÔNG** viết SQL trong View — chỉ Controller/Model
3. **KHÔNG** viết logic phức tạp (>3 dòng) trong Controller — tách ra Service
4. **KHÔNG** dùng CDN bên ngoài — download về `assets/` (ngoại trừ Turnstile, reCaptcha, GTranslate)
5. **KHÔNG** dùng `alert()` — dùng `SwalHelper`

### DRY
- Giá trị lặp lại >1 lần → gom vào Helper/constant
- HTML shared → đặt trong `hethong/` (head2, nav, foot)

---

## 6. Quy Trình Thêm Chức Năng Mới

### Bước 1: Kiểm tra DB
- Cần bảng mới? → Tạo SQL + cập nhật `csdl.sql`
- Cần cột mới? → ALTER + cập nhật `csdl.sql`

### Bước 2: Route
```php
// config/routes.php
['GET', '/feature', 'FeatureController@index'],
['POST', '/feature/action', 'FeatureController@action'],
```

### Bước 3: Model (nếu cần DB)
```php
// app/Models/Feature.php
class Feature extends Model {
    protected $table = 'features';
}
```

### Bước 4: Controller
```php
// app/Controllers/FeatureController.php
class FeatureController extends Controller {
    public function index() {
        $this->view('feature/index', [...]);
    }
}
```

### Bước 5: View
```php
// views/feature/index.php
// Include head2.php, nav.php, foot.php
// AJAX dùng fetch() + SwalHelper
```

### Bước 6: Verify
- PHP syntax check: `php -l file.php`
- Test trên localhost
- Kiểm tra responsive mobile

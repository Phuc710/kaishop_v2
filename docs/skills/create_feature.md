---
name: create_mvc_feature
description: Hướng dẫn chi tiết quy trình để phát triển và thêm một chức năng mới theo chuẩn kiến trúc MVC của dự án.
---

# Hướng Dẫn Phát Triển Chức Năng Mới (MVC)

Để đảm bảo source code của `kaishop_v2` luôn gọn gàng, đồng bộ một style chuẩn (đồng bộ 1 style code) và tuân thủ mô hình MVC, mọi chức năng mới khi được thêm vào hệ thống cần tuân thủ cấu trúc sau:

## Quy Trình 4 Bước Thêm Chức Năng Mới

### 1. Khai báo Route (`config/routes.php`)
Mọi Request (GET/POST) đều phải đi qua `index.php` và được điều hướng bằng Router. Không tạo các file `.php` độc lập để gọi trực tiếp.

```php
// config/routes.php
// Đăng ký một chức năng mới, ví dụ: Quản lý ví tiền
['GET', '/wallet', 'WalletController@index'],
['POST', '/wallet/deposit', 'WalletController@deposit'],
```

### 2. Tạo Controller xử lý logic (`app/Controllers/`)
Tạo một Class kế thừa từ class `Controller` gốc. Controller chỉ quản lý luồng dữ liệu, không chứa HTML.

```php
// app/Controllers/WalletController.php
class WalletController extends Controller {
    private $walletModel;

    public function __construct() {
        $this->walletModel = new Wallet(); // Gọi Model
    }

    // Xử lý View (GET)
    public function index() {
        // Kiểm tra đăng nhập (nếu cần)
        $this->view('wallet/index', [
            'balance' => 100000 // Truyền data sang view
        ]);
    }

    // Xử lý Logic Form/AJAX (POST)
    public function deposit() {
        $amount = $this->post('amount', 0);
        
        if ($amount <= 0) {
            // Trả về JSON chuẩn format của dự án
            return $this->json(['success' => false, 'message' => 'Số tiền không hợp lệ'], 400);
        }

        // ... logic cộng tiền
        return $this->json(['success' => true, 'message' => 'Nạp tiền thành công']);
    }
}
```

### 3. Tạo View hiển thị (`views/`)
Không đặt code logic truy vấn Database trong View.
- Kế thừa giao diện chung bằng cách require `head2.php`, `nav.php`, và `foot.php`.
- Xử lý các request AJAX bằng Fetch API, gọi các function hỗ trợ giao diện như `SwalHelper`.

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
        <button onclick="deposit()">Nạp thêm</button>
    </main>

    <script>
        function deposit() {
            fetch('<?= BASE_URL ?>/wallet/deposit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'amount=50000'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    SwalHelper.successOkRedirect(data.message, location.href, 1000);
                } else {
                    SwalHelper.error(data.message);
                }
            });
        }
    </script>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>
</html>
```

### 4. Tương tác với CSDL qua Model (`app/Models/`)
Nếu cần tương tác với bảng mới/database, hãy tạo Model extends từ `Model` chung.

```php
// app/Models/Wallet.php
class Wallet extends Model {
    protected $table = 'wallets';

    public function getBalanceByUserId($userId) {
        // Sử dụng PDO prepare statements chống SQL Injection
        $stmt = $this->db->prepare("SELECT balance FROM `{$this->table}` WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 0;
    }
}
```

## Quy Ước Code Chung Bắt Buộc
1. **Không dùng `mysql_query` hoặc `mysqli_query` trực tiếp (Spaghetti code).** Mọi thao tác Database phải qua PDO stmt trong Model (hỗ trợ SQL Injection).
2. **Không dùng file `.php` trực tiếp làm Backend Endpoint cho AJAX.** Phải đi qua `Router` và Controller.
3. **Phản hồi từ Server phải là JSON Format:**
`return $this->json(['success' => bool, 'message' => 'text']);`
4. **Hiển thị thông báo (Alert):** Dùng `SwalHelper.success()` hoặc `SwalHelper.error()`, thay vì `alert()`.

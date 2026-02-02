<!DOCTYPE html>
<html lang="en">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Thanh Toán Hosting | <?= $chungapi['ten_web']; ?></title>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
    <style>
        .billing-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .billing-btn {
            background-color: #fff;
            border: 2px solid #ccc;
            border-radius: 6px;
            padding: 10px;
            font-size: 14px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            color: #e53935;
        }

        .billing-btn.selected {
            border-color: #ff5722;
            background-color: #fff3e0;
        }

        .billing-btn span {
            display: block;
            font-weight: bold;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <?php
    $id_host = $_GET['id'] ?? 0;

    // Kiểm tra ID hợp lệ
    if (!is_numeric($id_host)) {
        die("ID không hợp lệ");
    }

    // Chuẩn bị câu truy vấn an toàn
    $stmt = $connection->prepare("SELECT * FROM `list_host` WHERE `id` = ? AND `status` = 'ON'");
    $stmt->bind_param("i", $id_host); // "i" là kiểu integer
    $stmt->execute();
    $result = $stmt->get_result();
    $toz_host = $result->fetch_array();

    if (!$toz_host) {
        die("Không tìm thấy gói hosting.");
    }

    $gia_host = $toz_host['gia_host'];

    // Tính giá theo chu kỳ
    $data_price = [
        1 => $gia_host * 1,
        3 => $gia_host * 3,
        6 => $gia_host * 6,
        12 => $gia_host * 12
    ];
    ?>
    <div class="w-breadcrumb-area">
        <div class="breadcrumb-img">
            <div class="breadcrumb-left">
                <img src="<?= asset('assets/images/banner-bg-03.png') ?>" alt="img">
            </div>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-12">
                    <nav aria-label="breadcrumb" class="page-breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                            <li class="breadcrumb-item" aria-current="page">Hosting</li>
                        </ol>
                    </nav>
                    <h2 class="breadcrumb-title">
                        Đăng ký dịch vụ HOSTING - <?= $toz_host['name_host'] ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5" id="order">
        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title">Sản phẩm đã chọn</h3>
                        <div class="bg-light p-3 rounded mb-3">
                            <p class="fw-bold text-uppercase"><?= $toz_host['name_host'] ?></p>
                            <ul class="list-unstyled">
                                <li><i class="bx bx-check-double"></i> Dung lượng: <?= $toz_host['dung_luong']; ?></li>
                                <li><i class="bx bx-check-double"></i> Miền khác: Không giới hạn</li>
                                <li><i class="bx bx-check-double"></i> Miền con: Không giới hạn</li>
                                <li><i class="bx bx-check-double"></i> Băng thông: Không giới hạn</li>
                                <li><i class="bx bx-check-double"></i> Thông số khác: Không giới hạn</li>
                                <li><i class="bx bx-check-double"></i> Miễn phí chứng chỉ SSL</li>
                                <li><i class="bx bx-check-double"></i> Vị trí máy chủ: Việt Nam</li>
                            </ul>
                        </div>

                        <div class="payment-section">
                            <h4>Chu kỳ thanh toán</h4>
                            <div class="billing-options">
                                <?php foreach ($data_price as $month => $price): ?>
                                    <button class="billing-btn" data-month="<?= $month ?>" data-price="<?= $price ?>">
                                        <?= $month ?> Tháng<br><span><?= tien($price); ?> đ</span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3 mt-4">
                            <label for="domain" class="form-label fw-bold">Đặt tên miền máy chủ</label>
                            <input type="text" id="domain" class="form-control" placeholder="Nhập tên miền của bạn">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Email quản trị</label>
                            <input type="text" id="email" class="form-control" placeholder="Nhập email của bạn">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm api-sidebar-menu">
                    <div class="card-body">
                        <h3 class="card-title">Thống kê đơn hàng</h3>
                        <div class="bg-light p-3 rounded mb-3">
                            <p><?= $toz_host['name_host'] ?>: <span id="price_item"><?= number_format($gia_host); ?>
                                    đ</span></p>
                            <p id="price_month">0 Tháng: <?= number_format($gia_host); ?> đ</p>
                            <p id="totalAmount" class="fw-bold text-danger">Tổng tiền thanh toán:
                                <?= tien($gia_host); ?> đ</p>
                            <p id="into_discount"></p>
                        </div>
                        <div class="mb-3">
                            <input type="text" id="giftcode" class="form-control" placeholder="Nhập mã giảm giá nếu có">
                        </div>
                        <button type="button" onclick="thanhtoan()" class="btn btn-primary w-100">
                            <span id="button1" class="indicator-label">Thanh Toán</span>
                            <span id="button2" class="indicator-progress" style="display: none;"> <i
                                    class="fa fa-spinner fa-spin"></i> Đang xử lý.. </span>
                        </button>
                        <a href="javascript:history.back()" class="btn btn-link d-block mt-2">Quay lại</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const billingButtons = document.querySelectorAll('.billing-btn');

            if (billingButtons.length > 0) {
                billingButtons[0].classList.add('selected'); // Mặc định chọn nút đầu tiên

                const price = parseInt(billingButtons[0].dataset.price);
                const months = billingButtons[0].dataset.month;

                document.querySelector("#price_month").innerText = months + " Tháng: " + price.toLocaleString() + " đ";
                document.querySelector("#totalAmount").innerText = "Tổng tiền thanh toán: " + price.toLocaleString() + " đ";
            }

            billingButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    billingButtons.forEach(b => b.classList.remove('selected'));
                    btn.classList.add('selected');

                    const price = parseInt(btn.dataset.price);
                    const months = btn.dataset.month;

                    document.querySelector("#price_month").innerText = months + " Tháng: " + price.toLocaleString() + " đ";
                    document.querySelector("#totalAmount").innerText = "Tổng tiền thanh toán: " + price.toLocaleString() + " đ";
                });
            });
        });

        function thanhtoan() {
            const button1 = document.getElementById("button1");
            const button2 = document.getElementById("button2");

            const domain = document.getElementById("domain").value.trim();
            const email = document.getElementById("email").value.trim();
            const selectedBtn = document.querySelector(".billing-btn.selected");

            const giftcode = document.getElementById("giftcode").value;
            const username = "<?= $username; ?>";
            const goi = "<?= $id_host; ?>";

            const duration = selectedBtn.dataset.month;
            const price = selectedBtn.dataset.price;

            button1.style.display = "none";
            button2.style.display = "inline-block";
            button2.disabled = true;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", BASE_URL + "/ajax/hosting/process_hosting.php");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function () {
                button1.style.display = "inline-block";
                button2.style.display = "none";
                button2.disabled = false;

                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showMessage("Mua hosting thành công!", "success");
                        setTimeout(() => window.location.href = BASE_URL + "/history-hosting", 2000);
                    } else {
                        showMessage(response.message, "error");
                    }
                } else {
                    showMessage("Lỗi máy chủ: " + xhr.statusText, "error");
                }
            };

            xhr.onerror = function () {
                button1.style.display = "inline-block";
                button2.style.display = "none";
                button2.disabled = false;
                showMessage("Không thể kết nối đến máy chủ.", "error");
            };

            xhr.send(
                "username=" + encodeURIComponent(username) +
                "&goi=" + encodeURIComponent(goi) +
                "&domain=" + encodeURIComponent(domain) +
                "&email=" + encodeURIComponent(email) +
                "&duration=" + encodeURIComponent(duration) +
                "&giftcode=" + encodeURIComponent(giftcode) +
                "&price=" + encodeURIComponent(price)
            );
        }
    </script>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>
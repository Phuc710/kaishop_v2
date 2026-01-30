<!DOCTYPE html>
<html lang="en">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <?php require __DIR__ . '/../../hethong/head.php'; ?>
    <title>Nạp Thẻ | <?= $chungapi['ten_web']; ?></title>
    <!-- Thêm Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
    <main>
        <section class="py-110">
            <div class="container">
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="settings-card">
                            <div class="settings-card-head">
                                <h4>NẠP THẺ CÀO</h4>
                            </div>
                            <div class="settings-card-body">
                                <div class="mb-3">
                                    <label for="type_the" class="form-label">Loại thẻ</label>
                                    <select name="type_the" id="type_the"
                                        class="custom-style-select nice-select select-dropdown mb-3">
                                        <option value="VIETTEL">Viettel</option>
                                        <option value="VINAPHONE">Vinaphone</option>
                                        <option value="MOBIFONE">Mobifone</option>
                                        <option value="VNMOBI">Vietnammobile</option>
                                        <option value="ZING">Zing</option>
                                        <option value="GARENA">Garena</option>
                                        <option value="GATE">GATE</option>
                                        <option value="VCOIN">Vcoin</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="menh_gia" class="form-label">Mệnh giá</label>
                                    <select name="menh_gia" id="menh_gia" onchange="totalPrice()"
                                        class="custom-style-select nice-select select-dropdown mb-3">
                                        <option value="10000">10.000đ</option>
                                        <option value="20000">20.000đ</option>
                                        <option value="50000">50.000đ</option>
                                        <option value="100000">100.000đ</option>
                                        <option value="200000">200.000đ</option>
                                        <option value="500000">500.000đ</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="seri_the" class="form-label">Số serial</label>
                                    <input type="text" class="form-control shadow-none" id="seri_the" name="seri_the"
                                        placeholder="Nhập số serial" required="">
                                </div>
                                <div class="mb-3">
                                    <label for="ma_the" class="form-label">Mã thẻ</label>
                                    <input type="text" class="form-control shadow-none" id="ma_the" name="ma_the"
                                        placeholder="Nhập mã thẻ" required="">
                                </div>
                                <div class="mb-3">
                                    <button type="button" onclick="nap()" class="btn btn-primary w-100">
                                        <span id="button1" class="indicator-label">Nạp Ngay</span>
                                        <span id="button2" class="indicator-progress" style="display: none;"> <i
                                                class="fa fa-spinner fa-spin"></i> Đang xử lý.. </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="settings-card">
                            <div class="settings-card-head">
                                <h4>LƯU Ý</h4>
                            </div>
                            <div class="settings-card-body">
                                <p><span style="color:#e74c3c"><strong>Theo quy định của nhà mạng, nạp sai mệnh giá sẽ
                                            bị mất 50% giá trị thẻ.</strong></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <h3 class="text-24 fw-bold text-dark-300 mb-4">Lịch sử nạp thẻ</h3>
                    <form method="GET" action="" class="row g-3 mb-4">
                        <div class="col-lg-3 col-md-4 col-6">
                            <input class="form-control shadow-none" name="serial" type="text"
                                value="<?= isset($_GET['serial']) ? htmlspecialchars($_GET['serial']) : '' ?>"
                                placeholder="Nhập số serial">
                        </div>
                        <div class="col-lg-3 col-md-4 col-6">
                            <input class="form-control shadow-none" name="pin" type="text"
                                value="<?= isset($_GET['pin']) ? htmlspecialchars($_GET['pin']) : '' ?>"
                                placeholder="Nhập mã thẻ">
                        </div>
                        <div class="col-lg-3 col-md-4 col-6">
                            <input class="form-control shadow-none" name="purchase_date" id="purchase_date" type="text"
                                value="<?= isset($_GET['purchase_date']) ? htmlspecialchars($_GET['purchase_date']) : '' ?>"
                                placeholder="Chọn khoảng thời gian">
                        </div>
                        <div class="col-lg-3 col-md-4 col-6 d-flex gap-2">
                            <button type="submit" class="shop-widget-btn flex-grow-1"><i class="fas fa-search"></i> Tìm
                                kiếm</button>
                            <a href="/nap-the" class="shop-widget-btn flex-grow-1"><i class="far fa-trash-alt"></i> Bỏ
                                lọc</a>
                        </div>
                    </form>
                    <div class="overflow-x-auto">
                        <table class="w-100 dashboard-table table text-nowrap">
                            <thead>
                                <tr>
                                    <th scope="col" class="py-2 px-4">ID</th>
                                    <th scope="col" class="py-2 px-4">MÃ THẺ</th>
                                    <th scope="col" class="py-2 px-4">LOẠI THẺ</th>
                                    <th scope="col" class="py-2 px-4">MỆNH GIÁ</th>
                                    <th scope="col" class="py-2 px-4">THỰC NHẬN</th>
                                    <th scope="col" class="py-2 px-4">TRẠNG THÁI</th>
                                    <th scope="col" class="py-2 px-4">THỜI GIAN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Xử lý phân trang
                                $items_per_page = 10;
                                $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
                                $offset = ($page - 1) * $items_per_page;

                                // Xây dựng truy vấn SQL
                                $query = "SELECT * FROM `history_nap_the` WHERE `username` = ?";
                                $params = [$username];
                                $types = "s";

                                if (!empty($_GET['serial'])) {
                                    $query .= " AND `serial` LIKE ?";
                                    $params[] = "%" . $_GET['serial'] . "%";
                                    $types .= "s";
                                }

                                if (!empty($_GET['pin'])) {
                                    $query .= " AND `pin` LIKE ?";
                                    $params[] = "%" . $_GET['pin'] . "%";
                                    $types .= "s";
                                }

                                if (!empty($_GET['purchase_date'])) {
                                    $dates = explode(" to ", $_GET['purchase_date']);
                                    if (count($dates) === 2) {
                                        $query .= " AND `time` BETWEEN ? AND ?";
                                        $params[] = trim($dates[0]);
                                        $params[] = trim($dates[1]);
                                        $types .= "ss";
                                    }
                                }

                                // Đếm tổng số bản ghi
                                $count_query = str_replace("SELECT *", "SELECT COUNT(*)", $query);
                                $stmt = $ketnoi->prepare($count_query);
                                $stmt->bind_param($types, ...$params);
                                $stmt->execute();
                                $total_items = $stmt->get_result()->fetch_row()[0];
                                $total_pages = ceil($total_items / $items_per_page);

                                // Truy vấn dữ liệu với LIMIT
                                $query .= " ORDER BY `id` DESC LIMIT ? OFFSET ?";
                                $params[] = $items_per_page;
                                $params[] = $offset;
                                $types .= "ii";

                                $stmt = $ketnoi->prepare($query);
                                $stmt->bind_param($types, ...$params);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows === 0) {
                                    echo '<tr><td colspan="7" class="text-center py-4">Không có lịch sử nạp thẻ nào.</td></tr>';
                                } else {
                                    $i = $offset + 1;
                                    while ($row = $result->fetch_assoc()) {
                                        ?>
                                        <tr>
                                            <td class="text-dark"><?= $i++; ?></td>
                                            <td class="text-dark"><?= htmlspecialchars($row['pin']); ?></td>
                                            <td class="text-dark"><?= htmlspecialchars($row['loaithe']); ?></td>
                                            <td class="text-danger"><?= tien($row['menhgia']); ?>đ</td>
                                            <td class="text-success"><?= tien($row['thuc_nhan']); ?>đ</td>
                                            <td><span
                                                    class="status-badge <?= strtolower($row['status']); ?>"><?= napthe($row['status']); ?></span>
                                            </td>
                                            <td class="text-dark"><?= htmlspecialchars($row['time']); ?></td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Phân trang -->
                    <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-end mt-4">
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                            href="?page=<?= $page - 1 . (isset($_GET['serial']) ? '&serial=' . urlencode($_GET['serial']) : '') . (isset($_GET['pin']) ? '&pin=' . urlencode($_GET['pin']) : '') . (isset($_GET['purchase_date']) ? '&purchase_date=' . urlencode($_GET['purchase_date']) : '') ?>">Trước</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link"
                                                href="?page=<?= $i . (isset($_GET['serial']) ? '&serial=' . urlencode($_GET['serial']) : '') . (isset($_GET['pin']) ? '&pin=' . urlencode($_GET['pin']) : '') . (isset($_GET['purchase_date']) ? '&purchase_date=' . urlencode($_GET['purchase_date']) : '') ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                            href="?page=<?= $page + 1 . (isset($_GET['serial']) ? '&serial=' . urlencode($_GET['serial']) : '') . (isset($_GET['pin']) ? '&pin=' . urlencode($_GET['pin']) : '') . (isset($_GET['purchase_date']) ? '&purchase_date=' . urlencode($_GET['purchase_date']) : '') ?>">Sau</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <script>
        function nap() {
            const button1 = document.getElementById("button1");
            const button2 = document.getElementById("button2");

            if (button2.disabled) return;

            const username = "<?= $username ?>";
            const type_the = document.getElementById("type_the").value;
            const menh_gia = document.getElementById("menh_gia").value;
            const ma_the = document.getElementById("ma_the").value;
            const seri_the = document.getElementById("seri_the").value;

            button1.style.display = "none";
            button2.style.display = "inline-block";
            button2.disabled = true;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "/kaishop_v2/ajax/naptien/deposit_card.php");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onload = function () {
                button1.style.display = "inline-block";
                button2.style.display = "none";
                button2.disabled = false;

                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showMessage("Nạp thẻ thành công!", "success");
                        setTimeout(() => {
                            window.location.href = BASE_URL + "/nap-the";
                        }, 3000);
                    } else {
                        showMessage(response.message, "error");
                    }
                } else {
                    showMessage("Lỗi: " + xhr.statusText, "error");
                }
            };

            xhr.onerror = function () {
                button1.style.display = "inline-block";
                button2.style.display = "none";
                button2.disabled = false;
                showMessage("Lỗi kết nối đến máy chủ!", "error");
            };

            xhr.send(
                "username=" + encodeURIComponent(username) +
                "&type_the=" + encodeURIComponent(type_the) +
                "&menh_gia=" + encodeURIComponent(menh_gia) +
                "&ma_the=" + encodeURIComponent(ma_the) +
                "&seri_the=" + encodeURIComponent(seri_the)
            );
        }

        // Khởi tạo Flatpickr
        document.addEventListener("DOMContentLoaded", function () {
            flatpickr("#purchase_date", {
                mode: "range",
                dateFormat: "Y-m-d",
                placeholder: "Chọn khoảng thời gian"
            });
        });
    </script>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>
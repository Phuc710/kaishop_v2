<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Tên Miền Giá Rẻ | <?= $chungapi['ten_web']; ?></title>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
    <script>
        function tongmien() {
            var ten_mien = document.getElementById("ten_mien").value; // Cần có input với id 'ten_mien'
            var duoimien = document.getElementById("duoimien").value; // Cần có input với id 'duoi_mien'

            // Kiểm tra nếu cả ten_mien và duoi_mien có giá trị
            if (ten_mien && duoi_mien) {
                $.ajax({
                    type: "POST",
                    url: "/ajax/domain/tongmien.php",
                    data: {
                        ten_mien: ten_mien,
                        duoi_mien: duoi_mien
                    },
                    success: function (result) {
                        var menhGiaList = JSON.parse(result);
                        var selectHtml = '';

                        menhGiaList.forEach(function (item) {
                            selectHtml += `
                            <label class="col-lg-4 col-form-label required fw-semibold fs-3">Tên miền</label>
                            <input type="text" name="domain" class="form-control form-control-lg form-control-solid" readonly value="${item.domain}" />
                        `;
                        });

                        document.getElementById("menh_gia_container").innerHTML = selectHtml;
                    },
                    error: function () {
                        alert("Đã xảy ra lỗi khi gửi yêu cầu.");
                    }
                });
            } else {
                alert("Vui lòng nhập đầy đủ tên miền và đuôi miền.");
            }
        }
    </script>

    <main>
        <section class="py-110 bg-offWhite">
            <div class="container">
                <div class="rounded-3">
                    <section class="space-y-6">
                        <div class="row justify-content-center">

                            <!-- THÔNG TIN THANH TOÁN -->
                            <div class="col-md-6 mb-5">
                                <div class="profile-info-card">
                                    <div class="profile-info-header">
                                        <h4 class="text-18 fw-semibold text-dark-300">MUA TÊN MIỀN</h4>
                                    </div>
                                    <div class="profile-info-body bg-white">
                                        <div class="mb-3">
                                            <label for="url" class="form-label">Tên Miền</label>
                                            <input type="text" class="form-control shadow-none" id="ten_mien"
                                                placeholder="Nhập tên miền...">
                                            <div class="form-text">Ví dụ: dailycode</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="duoimien" class="form-label">Đuôi miền</label>
                                            <select class="form-select shadow-none" id="duoimien" onchange="checkGia()"
                                                required>
                                                <option value="">Chọn Đuôi Domain</option>
                                                <?php
                                                $result = mysqli_query($ketnoi, "SELECT * FROM `ds_domain` WHERE `status` = 'ON' ORDER BY `id` DESC");
                                                while ($row = mysqli_fetch_assoc($result)) { ?>
                                                    <option value="<?= $row['duoimien']; ?>"><?= $row['duoimien']; ?> -
                                                        <?= tien($row['gia']); ?>đ
                                                    </option>
                                                <?php } ?>
                                            </select>
                                            <div class="form-text">Chọn đuôi miền</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="months" class="form-label">Hạn Đăng Ký</label>
                                            <select class="form-select shadow-none" id="hsd" onchange="checkGia()"
                                                required>
                                                <option value="1">1 Năm</option>
                                            </select>
                                            <div class="form-text">Chọn số năm sử dụng tên miền</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="nameserver" class="form-label">Nameserver</label>
                                            <textarea class="form-control shadow-none" rows="4"
                                                placeholder="Nhập NS1 ở dòng đầu, NS2 ở dòng thứ hai"
                                                id="nameserver"></textarea>
                                            <div class="form-text">Nhập NS1 ở dòng đầu, NS2 ở dòng thứ hai</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="url" class="form-label">Mã giảm giá</label>
                                            <input type="text" class="form-control shadow-none" id="giftcode"
                                                placeholder="Nhập mã giảm giá nếu có">
                                        </div>
                                        <button type="button" onclick="buy()" class="btn btn-primary w-100">
                                            <span id="button1" class="indicator-label">Thanh Toán</span>
                                            <span id="button2" class="indicator-progress" style="display: none;"> <i
                                                    class="fa fa-spinner fa-spin"></i> Đang xử lý.. </span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </section>
                </div>
            </div>
        </section>
    </main>
    <script>
        function buy() {
            const button1 = document.getElementById("button1");
            const button2 = document.getElementById("button2");

            if (button2.disabled) {
                return;
            }

            const username = "<?= $username ?>";
            const ten_mien = document.getElementById("ten_mien").value;
            const duoimien = document.getElementById("duoimien").value;
            const giftcode = document.getElementById("giftcode").value;
            const nameserver = document.getElementById("nameserver").value;

            button1.style.display = "none";
            button2.style.display = "inline-block";
            button2.disabled = true;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "/ajax/domain/xulydomain.php");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onload = function () {
                button1.style.display = "inline-block";
                button2.style.display = "none";
                button2.disabled = false;

                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showMessage("Mua domain thành công!", "success");
                        setTimeout(() => {
                            window.location.href = BASE_URL + "/history-mien";
                        }, 2000);
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
                "&ten_mien=" + encodeURIComponent(ten_mien) +
                "&duoimien=" + encodeURIComponent(duoimien) +
                "&giftcode=" + encodeURIComponent(giftcode) +
                "&nameserver=" + encodeURIComponent(nameserver)
            );
        }
    </script>
    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
    </body>

</html>
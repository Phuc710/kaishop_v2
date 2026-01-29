<!DOCTYPE html>
<html lang="en">
<head>
    <?php require __DIR__.'/../../hethong/head2.php'; ?>
    <title>Subdomain | <?= $chungapi['ten_web']; ?></title>
</head>
<body>
    <?php require __DIR__.'/../../hethong/nav.php'; ?>

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
                                        <h4 class="text-18 fw-semibold text-dark-300">THUÊ SUBDOMAIN</h4>
                                    </div>
                                    <div class="profile-info-body bg-white">
                                        <div class="mb-3">
                                            <label class="form-label">Tên Miền</label>
                                            <input type="text" class="form-control shadow-none" id="tenmien" placeholder="Nhập tên miền...">
                                            <div class="form-text">Ví dụ: dailycode</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Đuôi miền</label>
                                            <select class="form-select shadow-none" id="duoimien" onchange="checkGia()" required>
                                                <option value="">Chọn Đuôi Domain</option>
                                                <?php
                                                $result = mysqli_query($ketnoi, "SELECT * FROM `khosubdomain` WHERE `status` = 'ON' ");
                                                while ($row = mysqli_fetch_assoc($result)) { ?>
                                                    <option value="<?= $row['duoimien']; ?>">
                                                        <?= $row['duoimien']; ?> - <?= $row['thoihan']; ?> ngày - <?= tien($row['gia']); ?>đ
                                                    </option>
                                                <?php } ?>
                                            </select>
                                            <div class="form-text">Chọn đuôi miền</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tên miền là</label>
                                            <input type="text" class="form-control shadow-none" id="domain" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Địa chỉ IP</label>
                                            <input type="text" class="form-control shadow-none" id="ip" placeholder="Nhập IP host của bạn">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Mã giảm giá</label>
                                            <input type="text" class="form-control shadow-none" id="giftcode" placeholder="Nhập mã giảm giá nếu có">
                                        </div>
                                        <button type="button" onclick="thanhtoan()" class="btn btn-primary w-100">
                                            <span id="button1" class="indicator-label">Thanh Toán</span>
                                            <span id="button2" class="indicator-progress" style="display: none;">
                                                <i class="fa fa-spinner fa-spin"></i> Đang xử lý..
                                            </span>
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
    function thanhtoan() {
        const button1 = document.getElementById("button1");
        const button2 = document.getElementById("button2");

        button1.style.display = "none";
        button2.style.display = "inline-block";
        button2.disabled = true;

        const tenmien = document.getElementById("tenmien").value;
        const duoimien = document.getElementById("duoimien").value;
        const ip = document.getElementById("ip").value;

        Swal.fire({
            title: 'Xác nhận mua miền',
            text: "Bạn có chắc chắn muốn mua miền này?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "/ajax/subdomain/xulydomain.php");
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function () {
                    button1.style.display = "inline-block";
                    button2.style.display = "none";
                    button2.disabled = false;

                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            Swal.fire({
                                icon: "success",
                                text: "Mua miền thành công, vui lòng đợi kích hoạt!",
                            }).then(function () {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: "error",
                                text: response.message,
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: "error",
                            text: "Error: " + xhr.statusText,
                        });
                    }
                };
                xhr.onerror = function () {
                    button1.style.display = "inline-block";
                    button2.style.display = "none";
                    button2.disabled = false;

                    Swal.fire({
                        icon: "error",
                        text: "Error: " + xhr.statusText,
                    });
                };
                xhr.send(
                    "tenmien=" + encodeURIComponent(tenmien) +
                    "&duoimien=" + encodeURIComponent(duoimien) +
                    "&ip=" + encodeURIComponent(ip)
                );
            } else {
                button1.style.display = "inline-block";
                button2.style.display = "none";
                button2.disabled = false;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var tenmienInput = document.getElementById('tenmien');
        var duoimienSelect = document.getElementById('duoimien');
        var domainInput = document.getElementById('domain');

        tenmienInput.addEventListener('input', updateDomain);
        duoimienSelect.addEventListener('change', updateDomain);

        function updateDomain() {
            var tenmienValue = tenmienInput.value;
            var duoimienValue = duoimienSelect.value;

            if (tenmienValue.trim() === '' || duoimienValue.trim() === '') {
                Swal.fire({
                    icon: 'error',
                    text: 'Vui lòng chọn tên miền và đuôi miền',
                });
            } else if (!isValidDomain(tenmienValue)) {
                Swal.fire({
                    icon: 'error',
                    text: 'Tên miền không hợp lệ (chỉ chứa chữ cái & số)',
                });
            } else {
                domainInput.value = tenmienValue + duoimienValue;
            }
        }

        function isValidDomain(domain) {
            var domainRegex = /^[a-zA-Z0-9]+$/;
            return domainRegex.test(domain);
        }
    });
</script>

    <?php require __DIR__.'/../../hethong/foot.php'; ?>
</body>
</html>
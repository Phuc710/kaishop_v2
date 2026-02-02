<!DOCTYPE html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Quản Lí Hosting | <?= htmlspecialchars($chungapi['ten_web']); ?></title>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
    <?php
    if (isset($_GET['id'])) {
        $id = mysqli_real_escape_string($connection, $_GET['id']);
        $check_host = $connection->query("SELECT * FROM `lich_su_mua_host` WHERE `id` = '$id'");
        if ($check_host->num_rows == 1) {
            $toz_host = $check_host->fetch_array();
            $loai_host = $connection->query("SELECT * FROM `list_host` WHERE `name_host` = '" . $toz_host['goi_host'] . "'")->fetch_array();
            if ($toz_host['username'] != $username) {
                echo '<script>alert("Hosting không tồn tại hay không phải của bạn!"); window.location.href = BASE_URL + "/history-hosting";</script>';
                exit();
            }
        } else {
            echo '<script>alert("Hosting không tồn tại!"); window.location.href = BASE_URL + "/history-hosting";</script>';
            exit();
        }
    } else {
        echo '<script>alert("Không tìm thấy ID!"); window.location.href = BASE_URL + "/history-hosting";</script>';
        exit();
    }
    ?>


    <?php $server_host = $connection->query("SELECT * FROM `list_server_host` WHERE `id` = '" . $toz_host['server_host'] . "'")->fetch_array(); ?>

    <main>
        <section class="py-110">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card shadow-sm p-3">
                            <div class="pb-4 mb-4">
                                <div class="d-flex align-items-center mb-2">
                                    <h3 class="h5 fw-bold text-dark mb-0"><?=$toz_host['goi_host'];?> - <?=$server_host['name_server'];?></h3>
                                    <span class=""><?=host($toz_host['status']);?></span>
                                </div>
                                <a href="https://<?=$toz_host['domain'];?>" class="text-primary text-decoration-underline"><?=$toz_host['domain'];?></a>
                            </div>
 
                            <div class="border-top pt-4 row row-cols-1 row-cols-md-2 gy-4 text-muted mb-6">
                                <div>
                                    <div class="text-secondary">Thanh toán lần đầu</div>
                                    <div class="fw-medium"><?=tien($toz_host['gia_host']);?> VND</div>
                                </div>
                                <div>
                                    <div class="text-secondary">Ngày đăng ký</div>
                                    <div class="fw-medium"><?=ngay($toz_host['ngay_mua']);?></div>
                                </div>
                                <div>
                                    <div class="text-secondary">Ngày hết hạn</div>
                                    <div class="fw-medium"><?=ngay($toz_host['ngay_het']);?></div>
                                </div>
                                <div>
                                    <div class="text-secondary">Số tiền thanh toán định kỳ</div>
                                    <div class="fw-medium"><?=tien($toz_host['gia_host']);?> VND</div>
                                </div>
                                <div>
                                    <div class="text-secondary">Hình thức thanh toán</div>
                                    <div class="fw-medium">Số dư tài khoản</div>
                                </div>
                            </div>
 
                            <div class="row gy-4">
                                <div class="col-md-6">
                                    <label for="username" class="form-label fw-medium">Link Cpanel</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?=$server_host['link_login'];?>:2083" readonly>
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="https://<?=$server_host['link_login'];?>:2083">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="username" class="form-label fw-medium">Tài Khoản</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" value="<?=$toz_host['tk_host'];?>" readonly>
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="<?=$toz_host['tk_host'];?>">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                    </div>
                                </div>
 
                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-medium">Mật khẩu</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" value="<?=$toz_host['mk_host'];?>" readonly>
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="<?=$toz_host['mk_host'];?>">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="ip" class="form-label fw-medium">Địa chỉ IP</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" value="<?=$server_host['ip_whm'];?>" readonly>
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="<?=$server_host['ip_whm'];?>">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="username" class="form-label fw-medium">Nameserver 1</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?=$server_host['ns1'];?>" readonly>
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="<?=$server_host['ns2'];?>">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="ip" class="form-label fw-medium">namesever 2</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?=$server_host['ns2'];?>" readonly>
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="<?=$server_host['ns2'];?>">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                    </div>
                                </div>
 
 
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
 
                        <div class="alert alert-danger mb-1" role="alert">
                            <span>Lưu ý: Chức năng cài lại hosting sẽ đưa hosting về ban đầu và sẽ mất dữ liệu cũ</span>
                        </div>
                        <div class="card mb-4 shadow-sm">
                            <div class="card-body">
                                <h2 class="h5 card-title mb-4">Liên kết với cPanel</h2>
<div class="row g-3 text-center">
    <div class="col-6 col-md-3">
        <div id="button1_4" onclick="resethost()" class="text-center border rounded p-3 h-100 shadow-sm bg-white" style="cursor: pointer;">
            <img src="<?=asset('assets/images/rshost.svg')?>" alt="Cài đặt lại hosting" class="mb-2 img-fluid" style="max-height: 60px;">
            <p class="mb-0 fw-bold text-dark">Cài đặt lại hosting</p>
        </div>
        <div id="button2_4" class="text-center border rounded p-3 h-100 shadow-sm bg-white" style="display: none;">
            <div class="spinner-border text-primary mb-2" role="status"></div>
            <p class="mb-0 fw-bold text-muted">Đang xử lý...</p>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div id="button1_3" onclick="changemien()" class="text-center border rounded p-3 h-100 shadow-sm bg-white" style="cursor: pointer;">
            <img src="<?=asset('assets/images/doimien.svg')?>" alt="Đổi miền chính" class="mb-2 img-fluid" style="max-height: 60px;">
            <p class="mb-0 fw-bold text-dark">Đổi miền chính</p>
        </div>
        <div id="button2_3" class="text-center border rounded p-3 h-100 shadow-sm bg-white" style="display: none;">
            <div class="spinner-border text-primary mb-2" role="status"></div>
            <p class="mb-0 fw-bold text-muted">Đang xử lý...</p>
        </div>
    </div>
</div>
</div>
</div>
<div class="col-md-6">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5 card-title mb-4">Liên kết nhanh</h2>

            <div class="list-group w-100"> <!-- full width -->
                <!-- Đăng nhập cPanel -->
                <button type="button" class="list-group-item list-group-item-action bg-light-hover mb-1 border-0"
                    style="border-radius: 0;" 
                    onclick="window.open('https://<?=$server_host['hostname'];?>:2083/login/?user=<?=$toz_host['tk_host'];?>&pass=<?=$toz_host['mk_host'];?>', '_blank')">
                    <img src="<?=asset('assets/images/loginnhanh.svg')?>" width="25" height="25" class="me-2">Đăng nhập vào cPanel
                </button>

                <!-- Thay đổi mật khẩu -->
                <button id="button1_1" onclick="changepass()" class="list-group-item list-group-item-action bg-light-hover mb-1 text-start border-0">
                    <img src="<?=asset('assets/images/doimatkhau.svg')?>" width="25" height="25" class="me-2">Thay đổi mật khẩu
                </button>
                <button id="button2_1" class="list-group-item bg-secondary-subtle mb-1 text-start border-0" disabled style="display:none;">
                    <img src="<?=asset('assets/images/doimatkhau.svg')?>" width="25" height="25" class="me-2">Đang xử lý...
                </button>
                
                <!-- Gia hạn -->
                <button id="button1_0" onclick="giahan()" class="list-group-item list-group-item-action bg-light-hover mb-1 text-start border-0">
                    <img src="<?=asset('assets/images/giahan.svg')?>" width="25" height="25" class="me-2">Gia hạn
                </button>
                <button id="button2_0" class="list-group-item bg-secondary-subtle mb-1 text-start border-0" disabled style="display:none;">
                    <img src="<?=asset('assets/images/giahan.svg')?>" width="25" height="25" class="me-2">Đang xử lý...
                </button>
            </div>
        </div>
    </div>
</div>
</section>
</main>

<script>
function giahan() {
    const button1_0 = document.getElementById("button1_0");
    const button2_0 = document.getElementById("button2_0");

    button1_0.style.display = "none";
    button2_0.style.display = "inline-block";
    button2_0.disabled = true;

    const username = "<?=$username;?>";
    const idhost = "<?=$id;?>";
    const gia_host = parseInt("<?=$toz_host['gia_host'];?>");
    const giahan = 1;

    const tongTienFormatted = gia_host.toLocaleString('vi-VN') + "đ";

    Swal.fire({
        title: "Xác nhận gia hạn",
        html: `Bạn có muốn gia hạn hosting này thêm <b>1 tháng</b> với giá <b>${tongTienFormatted}</b>?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Đồng ý",
        cancelButtonText: "Hủy bỏ",
        customClass: {
            confirmButton: "btn btn-primary mx-2",
            cancelButton: "btn btn-secondary"
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "/ajax/hosting/giahan");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onload = function () {
                button1_0.style.display = "inline-block";
                button2_0.style.display = "none";
                button2_0.disabled = false;

                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Thành công",
                            text: "Gia hạn thành công! Hosting đã được cập nhật thời hạn.",
                            confirmButtonText: "Đóng",
                            customClass: {
                                confirmButton: "btn btn-success"
                            },
                            buttonsStyling: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Thất bại",
                            text: response.message,
                            confirmButtonText: "Đóng",
                            customClass: {
                                confirmButton: "btn btn-danger"
                            },
                            buttonsStyling: false
                        });
                    }
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Lỗi máy chủ",
                        text: "Không thể xử lý yêu cầu: " + xhr.statusText,
                        confirmButtonText: "Đóng",
                        customClass: {
                            confirmButton: "btn btn-danger"
                        },
                        buttonsStyling: false
                    });
                }
            };

            xhr.onerror = function () {
                button1_0.style.display = "inline-block";
                button2_0.style.display = "none";
                button2_0.disabled = false;

                Swal.fire({
                    icon: "error",
                    title: "Lỗi kết nối",
                    text: "Không thể kết nối đến máy chủ!",
                    confirmButtonText: "Thử lại",
                    customClass: {
                        confirmButton: "btn btn-warning"
                    },
                    buttonsStyling: false
                });
            };

            xhr.send(
                "username=" + encodeURIComponent(username) +
                "&idhost=" + encodeURIComponent(idhost) +
                "&giahan=" + encodeURIComponent(giahan)
            );
        } else {
            button1_0.style.display = "inline-block";
            button2_0.style.display = "none";
            button2_0.disabled = false;
        }
    });
}
</script>

<script>
function changepass() {
    const button1_1 = document.getElementById("button1_1");
    const button2_1 = document.getElementById("button2_1");

    button1_1.style.display = "none";
    button2_1.style.display = "inline-block";
    button2_1.disabled = true;

    const username = "<?=$username;?>";
    const idhost = "<?=$id;?>";

    Swal.fire({
        title: "Xác nhận",
        text: "Bạn có chắc chắn muốn đặt lại mật khẩu cho hosting này?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Đồng ý",
        cancelButtonText: "Hủy bỏ",
        customClass: {
            confirmButton: "btn btn-primary mx-2",
            cancelButton: "btn btn-secondary"
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "/ajax/hosting/changepass");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onload = function() {
                button1_1.style.display = "inline-block";
                button2_1.style.display = "none";
                button2_1.disabled = false;

                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Thành công",
                            text: "Mật khẩu mới đã được đặt lại thành công!",
                            confirmButtonText: "Đóng",
                            customClass: {
                                confirmButton: "btn btn-success"
                            },
                            buttonsStyling: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Thất bại",
                            text: response.message,
                            confirmButtonText: "Đóng",
                            customClass: {
                                confirmButton: "btn btn-danger"
                            },
                            buttonsStyling: false
                        });
                    }
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Lỗi máy chủ",
                        text: "Không thể xử lý yêu cầu: " + xhr.statusText,
                        confirmButtonText: "Đóng",
                        customClass: {
                            confirmButton: "btn btn-danger"
                        },
                        buttonsStyling: false
                    });
                }
            };

            xhr.onerror = function() {
                button1_1.style.display = "inline-block";
                button2_1.style.display = "none";
                button2_1.disabled = false;

                Swal.fire({
                    icon: "error",
                    title: "Lỗi kết nối",
                    text: "Không thể kết nối đến máy chủ. Vui lòng thử lại sau.",
                    confirmButtonText: "Đóng",
                    customClass: {
                        confirmButton: "btn btn-warning"
                    },
                    buttonsStyling: false
                });
            };

            xhr.send(
                "username=" + encodeURIComponent(username) +
                "&idhost=" + encodeURIComponent(idhost)
            );
        } else {
            button1_1.style.display = "inline-block";
            button2_1.style.display = "none";
            button2_1.disabled = false;
        }
    });
}
</script>

<script>
function changemien() {
    const button1_3 = document.getElementById("button1_3");
    const button2_3 = document.getElementById("button2_3");

    button1_3.style.display = "none";
    button2_3.style.display = "inline-block";
    button2_3.disabled = true;

    const username = "<?=$username;?>";
    const idhost = "<?=$id;?>";

    Swal.fire({
        title: 'Thay đổi tên miền',
        input: 'text',
        inputPlaceholder: 'Nhập tên miền mới (vd: example.com)',
        showCancelButton: true,
        confirmButtonText: '✔ Thay đổi',
        cancelButtonText: '✖ Hủy',
        inputValidator: (value) => {
            if (!value) {
                return 'Bạn cần nhập tên miền!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const domain = result.value.trim();
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "/ajax/hosting/changedomain");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onload = function () {
                button1_3.style.display = "inline-block";
                button2_3.style.display = "none";
                button2_3.disabled = false;

                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        Swal.fire("Thành công!", "Tên miền đã được thay đổi.", "success")
                            .then(() => location.reload());
                    } else {
                        Swal.fire("Lỗi!", response.message, "error");
                    }
                } else {
                    Swal.fire("Lỗi máy chủ!", xhr.statusText, "error");
                }
            };

            xhr.onerror = function () {
                button1_3.style.display = "inline-block";
                button2_3.style.display = "none";
                button2_3.disabled = false;
                Swal.fire("Lỗi kết nối!", "Không thể kết nối đến máy chủ.", "error");
            };

            xhr.send(
                "username=" + encodeURIComponent(username) +
                "&idhost=" + encodeURIComponent(idhost) +
                "&domain=" + encodeURIComponent(domain)
            );
        } else {
            button1_3.style.display = "inline-block";
            button2_3.style.display = "none";
            button2_3.disabled = false;
        }
    });
}
</script>
<script>
function resethost() {
    const button1_4 = document.getElementById("button1_4");
    const button2_4 = document.getElementById("button2_4");

    button1_4.style.display = "none";
    button2_4.style.display = "inline-block";
    button2_4.disabled = true;

    const username = "<?=$username;?>";
    const idhost = "<?=$id;?>";

    Swal.fire({
        title: "Bạn có chắc chắn?",
        text: "Reset host nhằm mục đích là khi bạn đã nghịch sai gì đó dẫn đến host hỏng hóc, bạn nghi ngờ host bị ai đó truy cập và gắn mã độc, bạn muốn xoá toàn bộ dữ liệu... Khi thực hiện reset host, host của bạn sẽ bị reset dẫn đến mất hết dữ liệu và không thể khôi phục lại dữ liệu đó, host của bạn sẽ trở về như trạng thái như lúc mới mua CẢNH BÁO: KHÔNG NÊN LẠM DỤNG CHỨC NĂNG NÀY. RESET NHIỀU CÓ THỂ DẪN TỚI KHÔNG LOGIN ĐƯỢC VÀ MẤT HOST. . Bạn có chắc chắn muốn reset chứ?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Reset ngay',
        cancelButtonText: 'Hủy bỏ',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "/ajax/hosting/resethost");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                button1_4.style.display = "inline-block";
                button2_4.style.display = "none";
                button2_4.disabled = false;

                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Thành công",
                            text: "Gửi yêu cầu reset thành công, vui lòng đợi 2-3 phút để hệ thống xử lý!",
                            confirmButtonText: "OK"
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Thất bại",
                            text: response.message,
                            confirmButtonText: "Đóng"
                        });
                    }
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Lỗi máy chủ",
                        text: "Không thể xử lý yêu cầu: " + xhr.statusText,
                        confirmButtonText: "Đóng"
                    });
                }
            };
            xhr.onerror = function() {
                button1_4.style.display = "inline-block";
                button2_4.style.display = "none";
                button2_4.disabled = false;

                Swal.fire({
                    icon: "error",
                    title: "Lỗi kết nối",
                    text: "Không thể kết nối đến máy chủ. Vui lòng thử lại sau.",
                    confirmButtonText: "Đóng"
                });
            };
            xhr.send(
                "username=" + encodeURIComponent(username) +
                "&idhost=" + encodeURIComponent(idhost)
            );
        } else {
            button1_4.style.display = "inline-block";
            button2_4.style.display = "none";
            button2_4.disabled = false;
        }
    });
}
</script>

<script>
function xoahost() {
    const button1_5 = document.getElementById("button1_5");
    const button2_5 = document.getElementById("button2_5");

    button1_5.style.display = "none";
    button2_5.style.display = "inline-block";
    button2_5.disabled = true;

    const username = "<?=$username;?>";
    const idhost = "<?=$id;?>";

    Swal.fire({
        title: 'Xác nhận',
        text: "Bạn có chắc chắn muốn thực hiện xoá hosting này? Một khi đã xoá sẽ không khôi phục được kể cả dữ liệu!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Đồng ý',
        cancelButtonText: 'Hủy bỏ',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "/ajax/hosting/xoahost");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                button1_5.style.display = "inline-block";
                button2_5.style.display = "none";
                button2_5.disabled = false;

                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        Swal.fire({
                            icon: "success",
                            text: "Yêu cầu xoá host thành công!",
                        }).then(function() {
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
            xhr.onerror = function() {
                button1_5.style.display = "inline-block";
                button2_5.style.display = "none";
                button2_5.disabled = false;

                Swal.fire({
                    icon: "error",
                    text: "Error: " + xhr.statusText,
                });
            };
            xhr.send(
                "username=" + encodeURIComponent(username) +
                "&idhost=" + encodeURIComponent(idhost)
            );
        } else {
            button1_5.style.display = "inline-block";
            button2_5.style.display = "none";
            button2_5.disabled = false;
        }
    });
}
</script>
                    <script>
                    function showitem1() {
                        document.getElementById("item1").style.display = "block";
                        document.getElementById("item2").style.display = "none";
                        document.getElementById("item3").style.display = "none";
                        document.getElementById("item4").style.display = "none";
                        document.getElementById("btn1").classList.add("active");
                        document.getElementById("btn2").classList.remove("active");
                        document.getElementById("btn3").classList.remove("active");
                        document.getElementById("btn4").classList.remove("active");
                    }

                    function showitem2() {
                        document.getElementById("item1").style.display = "none";
                        document.getElementById("item2").style.display = "block";
                        document.getElementById("item3").style.display = "none";
                        document.getElementById("item4").style.display = "none";
                        document.getElementById("btn1").classList.remove("active");
                        document.getElementById("btn2").classList.add("active");
                        document.getElementById("btn3").classList.remove("active");
                        document.getElementById("btn4").classList.remove("active");
                    }

                    function showitem3() {
                        document.getElementById("item1").style.display = "none";
                        document.getElementById("item2").style.display = "none";
                        document.getElementById("item3").style.display = "block";
                        document.getElementById("item4").style.display = "none";
                        document.getElementById("btn1").classList.remove("active");
                        document.getElementById("btn2").classList.remove("active");
                        document.getElementById("btn3").classList.add("active");
                        document.getElementById("btn4").classList.remove("active");
                    }

                    function showitem4() {
                        document.getElementById("item1").style.display = "none";
                        document.getElementById("item2").style.display = "none";
                        document.getElementById("item3").style.display = "none";
                        document.getElementById("item4").style.display = "block";
                        document.getElementById("btn1").classList.remove("active");
                        document.getElementById("btn2").classList.remove("active");
                        document.getElementById("btn3").classList.remove("active");
                        document.getElementById("btn4").classList.add("active");
                    }
                    </script>
                </div>
                        </div>
                        <?php require __DIR__.'/../../hethong/foot.php';?>
                        <!--end::Content-->
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Page-->
            </div>
            <!--end::Root-->

</html>
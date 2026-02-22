<?php
require_once dirname(__DIR__) . '/hethong/config.php';

// Handle AJAX forms
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_SESSION['admin'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'update_general') {
        $create = $connection->query("UPDATE `setting` SET
            `ten_web` = '" . $_POST['ten_web'] . "',
            `logo` = '" . $_POST['logo'] . "',
            `logo_footer` = '" . $_POST['logo_footer'] . "',
            `favicon` = '" . $_POST['favicon'] . "',
            `mo_ta` = '" . $_POST['mo_ta'] . "',
            `fb_admin` = '" . $_POST['fb_admin'] . "',
            `sdt_admin` = '" . $_POST['sdt_admin'] . "',
            `tele_admin` = '" . $_POST['tele_admin'] . "',
            `tiktok_admin` = '" . $_POST['tiktok_admin'] . "',
            `youtube_admin` = '" . $_POST['youtube_admin'] . "',
            `email_cf` = '" . $_POST['email_cf'] . "' ");

        if ($create)
            echo json_encode(['status' => 'success', 'message' => 'Cập nhật thành công!']);
        else
            echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra!']);
        exit;
    }

    if ($_POST['action'] === 'update_smtp') {
        $create = $connection->query("UPDATE `setting` SET
            `ten_nguoi_gui` = '" . $_POST['ten_nguoi_gui'] . "',
            `email_auto` = '" . $_POST['email_auto'] . "',
            `pass_mail_auto` = '" . $_POST['pass_mail_auto'] . "' ");

        if ($create)
            echo json_encode(['status' => 'success', 'message' => 'Cập nhật cấu hình SMTP thành công!']);
        else
            echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra!']);
        exit;
    }

    if ($_POST['action'] === 'update_notification') {
        $popup_template = $connection->real_escape_string($_POST['popup_template'] ?? '1');
        $create = $connection->query("UPDATE `setting` SET
            `thongbao` = '" . $_POST['thongbao'] . "',
            `popup_template` = '$popup_template' ");

        if ($create)
            echo json_encode(['status' => 'success', 'message' => 'Cập nhật thông báo thành công!']);
        else
            echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra!']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once('head.php'); ?>
    <title>Cài đặt Website | Admin Panel</title>
    <?php require_once('nav.php'); ?>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Cài đặt</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Trang chủ</a></li>
                                <li class="breadcrumb-item active">Cài đặt</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <form id="form-general" class="form-horizontal" enctype="multipart/form-data" action=""
                                    method="post">
                                    <div class="card-header">
                                        <h3 class="text-center">Thông tin</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group text-center mt-2 mb-3">
                                                    <h5><i class="fas fa-image text-primary mr-1"></i> CẤU HÌNH HÌNH ẢNH
                                                    </h5>
                                                    <hr style="width: 150px; border-top: 2px solid #007bff;">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="exampleInputEmail1">LINK ẢNH LOGO (Header)</label>
                                                    <input type="text" class="form-control" name="logo"
                                                        placeholder="https://..." value="<?= $chungapi['logo']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="exampleInputEmail1">LINK ẢNH LOGO (Footer)</label>
                                                    <input type="text" class="form-control" name="logo_footer"
                                                        placeholder="https://..."
                                                        value="<?= $chungapi['logo_footer'] ?? $chungapi['logo']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="exampleInputEmail1">FAVICON</label>
                                                    <input type="text" class="form-control" name="favicon"
                                                        placeholder="https://..." value="<?= $chungapi['favicon']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-12 mt-4">
                                                <div class="form-group text-center mb-3">
                                                    <h5><i class="fas fa-share-alt text-success mr-1"></i> SOCIAL</h5>
                                                    <hr style="width: 150px; border-top: 2px solid #28a745;">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="exampleInputEmail1">LINK FACEBOOK</label>
                                                    <input type="url" class="form-control" name="fb_admin"
                                                        placeholder="https://facebook.com/..."
                                                        value="<?= $chungapi['fb_admin']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="exampleInputEmail1">LINK TELEGRAM</label>
                                                    <input type="text" class="form-control" name="tele_admin"
                                                        placeholder="https://t.me/yourtelegram"
                                                        value="<?= $chungapi['tele_admin'] ?? ''; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="exampleInputEmail1">LINK TIKTOK</label>
                                                    <input type="url" class="form-control" name="tiktok_admin"
                                                        placeholder="https://tiktok.com/@..."
                                                        value="<?= $chungapi['tiktok_admin'] ?? ''; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="exampleInputEmail1">LINK YOUTUBE</label>
                                                    <input type="url" class="form-control" name="youtube_admin"
                                                        placeholder="https://youtube.com/@..."
                                                        value="<?= $chungapi['youtube_admin'] ?? ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-12 mt-4">
                                                <div class="form-group text-center mb-3">
                                                    <h5><i class="fas fa-info-circle text-info mr-1"></i> THÔNG TIN WEB
                                                    </h5>
                                                    <hr style="width: 150px; border-top: 2px solid #17a2b8;">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="exampleInputEmail1">TÊN WEBSITE</label>
                                                    <input type="text" class="form-control" name="ten_web"
                                                        placeholder="DAILYCODE.VN" value="<?= $chungapi['ten_web']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="exampleInputEmail1">SĐT ZALO</label>
                                                    <input type="text" class="form-control" name="sdt_admin"
                                                        placeholder="0812420710" value="<?= $chungapi['sdt_admin']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="exampleInputEmail1">EMAIL LIÊN HỆ</label>
                                                    <input type="text" class="form-control" name="email_cf"
                                                        placeholder="hotro@kaishop.vn"
                                                        value="<?= $chungapi['email_cf']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="exampleInputEmail1">MÔ TẢ WEBSITE</label>
                                                    <input type="text" class="form-control" name="mo_ta"
                                                        placeholder="Mô tả website" value="<?= $chungapi['mo_ta']; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <button name="submit" type="submit" class="btn btn-info btn-block">LƯU THAY
                                            ĐỔI</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>



                    <div class="row lg-12">
                        <section class="col-lg-6 connectedSortable">
                            <form id="form-smtp" action="" method="POST">
                                <div class="card card-primary card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-cogs mr-1"></i>
                                            CẤU HÌNH SMTP GMAIL

                                        </h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn bg-success btn-sm"
                                                data-card-widget="collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <a href="https://myaccount.google.com/apppasswords"
                                                style="text-decoration: none; font-weight: bolder;">👉 Password cho
                                                SMTP</a>
                                        </div>

                                        <div class="form-group">
                                            <label>Tên người gửi</label>
                                            <input type="text" name="ten_nguoi_gui"
                                                value="<?= $chungapi['ten_nguoi_gui']; ?>" class="form-control"
                                                placeholder="KaiShop">
                                        </div>
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="text" name="email_auto" value="<?= $chungapi['email_auto']; ?>"
                                                class="form-control" placeholder="[EMAIL_ADDRESS]">
                                        </div>
                                        <div class="form-group">
                                            <label>Mật khẩu ứng dụng</label>
                                            <input type="text" name="pass_mail_auto"
                                                value="<?= $chungapi['pass_mail_auto']; ?>" class="form-control"
                                                placeholder="vyqpzmaalbtlxqbo">
                                        </div>
                                        <div class="card-footer clearfix">
                                            <button name="submit_smtp" class="btn btn-info btn-icon-left m-b-10"
                                                type="submit">
                                                <i class="fas fa-save mr-1"></i>SAVE
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </section>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">

                                <form id="form-notification" class="form-horizontal" enctype="multipart/form-data"
                                    method="post">

                                    <div class="card-header">
                                        <h3 class="text-center">
                                            <i class="fas fa-bell text-warning mr-1"></i> Cấu hình Thông báo
                                        </h3>
                                    </div>

                                    <div class="card-body">

                                        <div class="row">

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><i class="fas fa-window-restore mr-1"></i> CHỌN KIỂU
                                                        POPUP</label>

                                                    <select class="form-control" name="popup_template">
                                                        <option value="0" <?= ($chungapi['popup_template'] ?? '1') === '0' ? 'selected' : '' ?>>Tắt Popup</option>
                                                        <option value="1" <?= ($chungapi['popup_template'] ?? '1') === '1' ? 'selected' : '' ?>>Mặc định (Khuyến mãi)</option>
                                                        <option value="2" <?= ($chungapi['popup_template'] ?? '1') === '2' ? 'selected' : '' ?>>Thông báo (Nội dung bên dưới)</option>
                                                    </select>

                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="alert alert-info mt-4 mb-0">
                                                    <strong>Mặc định:</strong> Popup khuyến mãi.<br>
                                                    <strong>Thông báo:</strong> Hiện nội dung textarea bên dưới.<br>
                                                    <strong>Tắt:</strong> Không hiển thị popup.
                                                </div>
                                            </div>

                                        </div>

                                        <div class="form-group mt-3">
                                            <label><i class="fas fa-edit mr-1"></i> NỘI DUNG THÔNG BÁO:</label>

                                            <textarea class="textarea" name="thongbao"
                                                placeholder="Nhập nội dung thông báo tại đây (hiện khi chọn kiểu Thông báo)"
                                                style="width:100%;height:200px;font-size:14px;line-height:18px;border:1px solid #ddd;padding:10px;"><?= htmlspecialchars($chungapi['thongbao'] ?? '') ?></textarea>

                                        </div>

                                    </div>

                                    <button name="submit3" type="submit" class="btn btn-info btn-block">
                                        <i class="fas fa-save mr-1"></i> LƯU THÔNG BÁO
                                    </button>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
    </div>

    <?php require_once('foot.php'); ?>

    <!-- AJAX Script for Settings -->
    <script>
        $(document).ready(function () {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            function handleFormSubmit(formId, actionName) {
                $('#' + formId).on('submit', function (e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    formData.append('action', actionName);

                    var btn = $(this).find('button[type="submit"]');
                    var originalText = btn.html();
                    btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu...');
                    btn.prop('disabled', true);

                    $.ajax({
                        url: '',
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function (response) {
                            btn.html(originalText);
                            btn.prop('disabled', false);

                            if (response.status === 'success') {
                                Toast.fire({
                                    icon: 'success',
                                    title: response.message
                                });
                            } else {
                                Toast.fire({
                                    icon: 'error',
                                    title: response.message || 'Có lỗi xảy ra!'
                                });
                            }
                        },
                        error: function () {
                            btn.html(originalText);
                            btn.prop('disabled', false);
                            Toast.fire({
                                icon: 'error',
                                title: 'Lỗi máy chủ!'
                            });
                        }
                    });
                });
            }

            handleFormSubmit('form-general', 'update_general');
            handleFormSubmit('form-smtp', 'update_smtp');
            handleFormSubmit('form-notification', 'update_notification');
        });
    </script>
</body>

</html>
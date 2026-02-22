<?php
// Prevent direct access
if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}

// If already logged in, redirect
if (isset($_SESSION['session']) && !empty($_SESSION['session'])) {
    header('Location: ' . BASE_URL . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <base href="../../../../" />
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Đặt lại mật khẩu |
        <?= $siteConfig['ten_web'] ?? 'KaiShop' ?>
    </title>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main>
        <section class="py-5 bg-offWhite">
            <div class="container">
                <div class="rounded-3">
                    <div class="row">
                        <div class="col-lg-6 p-3 p-lg-5 m-auto">
                            <div class="login-userset">
                                <div class="login-card">
                                    <div class="login-heading">
                                        <h3>Đặt lại mật khẩu</h3>
                                        <p>Nhập mật khẩu mới cho tài khoản của bạn</p>
                                    </div>

                                    <input type="hidden" id="otpcode" value="<?= htmlspecialchars($otpcode ?? '') ?>">

                                    <div class="form-wrap form-focus pass-group">
                                        <span class="form-icon">
                                            <i class="toggle-password feather-eye-off"></i>
                                        </span>
                                        <input type="password" id="password" class="pass-input form-control floating">
                                        <label class="focus-label">Mật khẩu mới</label>
                                    </div>

                                    <div class="form-wrap form-focus pass-group">
                                        <span class="form-icon">
                                            <i class="toggle-password feather-eye-off"></i>
                                        </span>
                                        <input type="password" id="repassword" class="pass-input form-control floating">
                                        <label class="focus-label">Nhập lại mật khẩu mới</label>
                                    </div>

                                    <button type="button" onclick="resetPassword()" class="btn btn-primary w-100">
                                        <span id="btnText" class="indicator-label">Cập nhật mật khẩu</span>
                                        <span id="btnLoading" class="indicator-progress" style="display: none;">
                                            <i class="fa fa-spinner fa-spin"></i> Đang xử lý...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        function resetPassword() {
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            const otpcode = document.getElementById('otpcode').value;
            const password = document.getElementById('password').value.trim();
            const repassword = document.getElementById('repassword').value.trim();

            if (btnLoading.style.display !== 'none') return;

            if (!password || !repassword) {
                SwalHelper.error('Vui lòng nhập đầy đủ thông tin.');
                return;
            }

            if (password !== repassword) {
                SwalHelper.error('Mật khẩu nhập lại không khớp.');
                return;
            }

            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
            btnLoading.parentElement.disabled = true;

            fetch('<?= BASE_URL ?>/password-reset/' + encodeURIComponent(otpcode), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'password=' + encodeURIComponent(password)
            })
                .then(response => response.json())
                .then(data => {
                    btnText.style.display = 'inline-block';
                    btnLoading.style.display = 'none';
                    btnLoading.parentElement.disabled = false;

                    if (data.success) {
                        SwalHelper.successOkRedirect(
                            data.message || 'Mật khẩu đã được cập nhật thành công.',
                            '<?= BASE_URL ?>/login'
                        );
                    } else {
                        SwalHelper.error(data.message || 'Mã khôi phục không hợp lệ.');
                    }
                })
                .catch(() => {
                    btnText.style.display = 'inline-block';
                    btnLoading.style.display = 'none';
                    btnLoading.parentElement.disabled = false;
                    SwalHelper.error('Không thể kết nối đến máy chủ.');
                });
        }

        document.getElementById('repassword').addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                resetPassword();
            }
        });
    </script>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>
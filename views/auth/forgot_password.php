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
    <base href="../../../" />
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Quên mật khẩu |
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
                                        <h3>Quên mật khẩu?</h3>
                                        <p>Nhập tên đăng nhập hoặc email để nhận liên kết khôi phục</p>
                                    </div>

                                    <div class="form-wrap form-focus">
                                        <span class="form-icon">
                                            <i class="feather-mail"></i>
                                        </span>
                                        <input type="text" id="username" class="form-control floating">
                                        <label class="focus-label">Tên đăng nhập / Email</label>
                                    </div>

                                    <button type="button" onclick="forgotPassword()" class="btn btn-primary w-100">
                                        <span id="btnText" class="indicator-label">Gửi yêu cầu khôi phục</span>
                                        <span id="btnLoading" class="indicator-progress" style="display: none;">
                                            <i class="fa fa-spinner fa-spin"></i> Đang xử lý...
                                        </span>
                                    </button>

                                    <div class="acc-in mt-3">
                                        <p>Đã nhớ mật khẩu?
                                            <a href="<?= BASE_URL ?>/login">Đăng nhập ngay</a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        function forgotPassword() {
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            const username = document.getElementById('username').value.trim();

            if (btnLoading.style.display !== 'none') return;

            if (!username) {
                SwalHelper.error('Vui lòng nhập tên đăng nhập hoặc email.');
                return;
            }

            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
            btnLoading.parentElement.disabled = true;

            fetch('<?= BASE_URL ?>/password-reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'username=' + encodeURIComponent(username)
            })
                .then(response => response.json())
                .then(data => {
                    btnText.style.display = 'inline-block';
                    btnLoading.style.display = 'none';
                    btnLoading.parentElement.disabled = false;

                    if (data.success) {
                        SwalHelper.successOkRedirect(
                            data.message || 'Email đặt lại mật khẩu đã được gửi.',
                            '<?= BASE_URL ?>/login'
                        );
                    } else {
                        SwalHelper.error(data.message || 'Tài khoản không tồn tại.');
                    }
                })
                .catch(() => {
                    btnText.style.display = 'inline-block';
                    btnLoading.style.display = 'none';
                    btnLoading.parentElement.disabled = false;
                    SwalHelper.error('Không thể kết nối đến máy chủ.');
                });
        }

        document.getElementById('username').addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                forgotPassword();
            }
        });
    </script>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>
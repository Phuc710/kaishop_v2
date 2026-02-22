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
    <title>Đăng ký | <?= $siteConfig['ten_web'] ?? 'KaiShop' ?></title>
    <script src="<?= BASE_URL ?>/assets/js/fingerprint.js"></script>
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
                                        <h3>Tạo tài khoản mới</h3>
                                        <p>Nhập thông tin để tạo tài khoản của bạn</p>
                                    </div>

                                    <div class="form-wrap form-focus">
                                        <span class="form-icon">
                                            <i class="feather-user"></i>
                                        </span>
                                        <input type="text" id="username" class="form-control floating"
                                            autocomplete="username">
                                        <label class="focus-label">Tên đăng nhập</label>
                                    </div>

                                    <div class="form-wrap form-focus">
                                        <span class="form-icon">
                                            <i class="feather-mail"></i>
                                        </span>
                                        <input type="email" id="email" class="form-control floating"
                                            autocomplete="email">
                                        <label class="focus-label">Email</label>
                                    </div>

                                    <div class="form-wrap form-focus pass-group">
                                        <span class="form-icon">
                                            <i class="toggle-password feather-eye-off"></i>
                                        </span>
                                        <input type="password" id="password" class="pass-input form-control floating"
                                            autocomplete="new-password">
                                        <label class="focus-label">Mật khẩu</label>
                                    </div>

                                    <button type="button" onclick="register()" class="btn btn-primary w-100">
                                        <span id="button1" class="indicator-label">Tạo tài khoản</span>
                                        <span id="button2" class="indicator-progress" style="display: none;">
                                            <i class="fa fa-spinner fa-spin"></i> Đang xử lý...
                                        </span>
                                    </button>

                                    <div class="acc-in">
                                        <p>Đã có tài khoản?
                                            <a href="<?= BASE_URL ?>/login">Đăng nhập</a>
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
        async function register() {
            const button1 = document.getElementById('button1');
            const button2 = document.getElementById('button2');
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();

            if (button2.disabled) return;

            if (!username || !email || !password) {
                SwalHelper.error('Vui lòng nhập đầy đủ thông tin.');
                return;
            }

            button1.style.display = 'none';
            button2.style.display = 'inline-block';
            button2.disabled = true;

            // Collect fingerprint
            let fpHash = '';
            let fpComponents = '';
            try {
                const fp = await KaiFingerprint.collect();
                fpHash = fp.hash;
                fpComponents = JSON.stringify(fp.components);
            } catch (e) {
                // Silent fail
            }

            let body = 'username=' + encodeURIComponent(username) +
                '&email=' + encodeURIComponent(email) +
                '&password=' + encodeURIComponent(password);
            if (fpHash) {
                body += '&fingerprint=' + encodeURIComponent(fpHash);
                body += '&fp_components=' + encodeURIComponent(fpComponents);
            }

            fetch('<?= BASE_URL ?>/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body
            })
                .then(response => response.json())
                .then(data => {
                    button1.style.display = 'inline-block';
                    button2.style.display = 'none';
                    button2.disabled = false;

                    if (data.success) {
                        SwalHelper.successOkRedirect(
                            data.message || 'Đăng ký thành công.',
                            '<?= BASE_URL ?>/'
                        );
                    } else {
                        SwalHelper.error(data.message || 'Đăng ký thất bại.');
                    }
                })
                .catch(() => {
                    button1.style.display = 'inline-block';
                    button2.style.display = 'none';
                    button2.disabled = false;
                    SwalHelper.error('Không thể kết nối đến máy chủ.');
                });
        }
    </script>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>
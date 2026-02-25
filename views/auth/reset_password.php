<?php
if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}

if (isset($_SESSION['session']) && !empty($_SESSION['session'])) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$siteName = $siteConfig['ten_web'] ?? ($chungapi['ten_web'] ?? 'KaiShop');
$seoTitle = 'Đặt lại mật khẩu | ' . $siteName;
$seoDescription = 'Đặt lại mật khẩu tài khoản tại ' . $siteName . '.';
$seoKeywords = 'đặt lại mật khẩu, khôi phục, ' . $siteName;
$seoRobots = 'noindex, nofollow';
$GLOBALS['pageAssets'] = ['interactive_bundle' => false];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <base href="../../../../" />
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title><?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="<?= BASE_URL ?>/assets/js/auth-forms.js"></script>
</head>

<body class="auth-layout">
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="auth-page">
        <section class="py-5 bg-offWhite auth-page-section">
            <div class="container auth-page-container">
                <div class="rounded-3">
                    <div class="row auth-page-row">
                        <div class="auth-page-col">
                            <div class="login-userset">
                                <div class="login-card auth-card-white">
                                    <div class="login-heading">
                                        <h3>Đặt lại mật khẩu</h3>
                                        <p>Nhập mật khẩu mới cho tài khoản của bạn</p>
                                    </div>

                                    <form id="resetPasswordForm" onsubmit="resetPassword(); return false;">
                                    <input type="hidden" id="otpcode"
                                        value="<?= htmlspecialchars($otpcode ?? '', ENT_QUOTES, 'UTF-8') ?>">

                                    <div class="form-wrap form-focus pass-group">
                                        <span class="form-icon"><i
                                                class="toggle-password fa-regular fa-eye-slash"></i></span>
                                        <input type="password" id="password" class="pass-input form-control floating"
                                            autocomplete="new-password" placeholder=" " required minlength="6">
                                        <label class="focus-label">Mật khẩu mới</label>
                                    </div>

                                    <div class="form-wrap form-focus pass-group">
                                        <span class="form-icon"><i
                                                class="toggle-password fa-regular fa-eye-slash"></i></span>
                                        <input type="password" id="repassword" class="pass-input form-control floating"
                                            autocomplete="new-password" placeholder=" " required minlength="6">
                                        <label class="focus-label">Nhập lại mật khẩu mới</label>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <span id="btnText" class="indicator-label">Cập nhật mật khẩu</span>
                                        <span id="btnLoading" class="indicator-progress" style="display:none;">
                                            <i class="fa fa-spinner fa-spin"></i> Đang xử lý...
                                        </span>
                                    </button>
                                    </form>
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
            const form = document.getElementById('resetPasswordForm');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            const button = btnLoading.parentElement;
            const otpcode = document.getElementById('otpcode').value;
            const password = document.getElementById('password').value.trim();
            const repassword = document.getElementById('repassword').value.trim();

            if (btnLoading.style.display !== 'none') return;
            if (form && !form.reportValidity()) return;
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
            button.disabled = true;

            fetch('<?= BASE_URL ?>/password-reset/' + encodeURIComponent(otpcode), {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'password=' + encodeURIComponent(password)
            })
                .then(r => r.json())
                .then(data => {
                    btnText.style.display = 'inline-block';
                    btnLoading.style.display = 'none';
                    button.disabled = false;
                    if (data.success) {
                        SwalHelper.successOkRedirect(data.message || 'Mật khẩu đã được cập nhật thành công.', '<?= BASE_URL ?>/login');
                    } else {
                        SwalHelper.error(data.message || 'Mã khôi phục không hợp lệ.');
                    }
                })
                .catch(() => {
                    btnText.style.display = 'inline-block';
                    btnLoading.style.display = 'none';
                    button.disabled = false;
                    SwalHelper.error('Không thể kết nối đến máy chủ.');
                });
        }
    </script>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>

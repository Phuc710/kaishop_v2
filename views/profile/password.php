    <!DOCTYPE html>
    <html lang="vi">

    <head>
        <?php require __DIR__ . '/../../hethong/head2.php'; ?>
        <title>Đổi Mật Khẩu | <?= htmlspecialchars((string) ($chungapi['ten_web'] ?? 'KaiShop')) ?></title>
    </head>

    <body>
        <?php require __DIR__ . '/../../hethong/nav.php'; ?>

        <main class="bg-light">
            <section class="py-5">
                <div class="container user-page-container">
                    <div class="row">
                        <div class="col-lg-3 col-md-4 mb-4">
                            <?php $activePage = 'password';
                            require __DIR__ . '/../../hethong/user_sidebar.php'; ?>
                        </div>

                        <div class="col-lg-9 col-md-8">
                            <div class="profile-card mb-4">
                                <div class="profile-card-header">
                                    <h5 class="text-dark mb-0">Thay đổi mật khẩu</h5>
                                </div>
                                <div class="profile-card-body">
                                    <p class="text-muted mb-4" style="font-size:14px;">
                                        Thay đổi mật khẩu đăng nhập giúp bảo vệ tài khoản của bạn tốt hơn.
                                    </p>

                                    <form id="password-form" class="row g-3" novalidate>
                                        <div class="col-md-4">
                                            <label for="password1" class="form-label user-label">Mật khẩu hiện tại</label>
                                            <div class="custom-input-wrap">
                                                <input type="password" class="form-control custom-bg-input" id="password1"
                                                    name="password1" required>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="password2" class="form-label user-label">Mật khẩu mới</label>
                                            <div class="custom-input-wrap">
                                                <input type="password" class="form-control custom-bg-input" id="password2"
                                                    name="password2" required>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="password3" class="form-label user-label">Nhập lại mật khẩu
                                                mới</label>
                                            <div class="custom-input-wrap">
                                                <input type="password" class="form-control custom-bg-input" id="password3"
                                                    name="password3" required>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-4">
                                            <button type="button" id="btn-save-password"
                                                class="btn btn-save-green shadow-sm px-4 font-weight-bold w-100"
                                                style="border-radius: 8px;">
                                                CẬP NHẬT MẬT KHẨU
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="profile-card">
                                <div class="profile-card-header d-flex align-items-center justify-content-between gap-3">
                                    <h5 class="text-dark mb-0">Xác minh 2 bước (OTP Gmail)</h5>
                                    <span class="badge bg-light text-dark border" id="twofa-status-badge">
                                        <?= ((int) ($user['twofa_enabled'] ?? 0) === 1) ? 'Đang bật' : 'Đang tắt' ?>
                                    </span>
                                </div>
                                <div class="profile-card-body">
                                    <form id="security-form" class="row g-3 align-items-center">
                                    <div class="col-md-6">
                                        <div class="custom-input-wrap">
                                            <div class="form-check form-switch pt-2 ps-0" style="min-height: auto;">
                                                <input class="form-check-input ms-0" type="checkbox" role="switch"
                                                    id="twofa_enabled_input" name="twofa_enabled" value="1"
                                                    style="cursor:pointer; width: 2.3rem; height: 1.25rem;"
                                                    <?= ((int) ($user['twofa_enabled'] ?? 0) === 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label ms-5 pt-1" for="twofa_enabled_input"
                                                    style="cursor:pointer; font-size: 14.5px;">
                                                    Bật OTP 6 số khi đăng nhập
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                Chỉ khi bật mục này, tài khoản mới cần nhập OTP khi đăng nhập.
                                            </small>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
    <script src="<?= asset('assets/js/auth-forms.js') ?>"></script>
    <script>
        (function () {
            const BASE = '<?= BASE_URL ?>';
            const endpoints = {
                passwordUpdate: '<?= url('password/update') ?>',
                securityUpdate: '<?= url('password/security') ?>',
                logout: '<?= url('logout') ?>'
            };

            function setButtonState(button, loading, loadingText, idleText) {
                if (!button) return;
                button.disabled = !!loading;
                button.innerHTML = loading
                    ? '<i class="fa fa-spinner fa-spin"></i> ' + loadingText
                    : idleText;
            }

            async function postForm(url, formData) {
                if (window.KaiAuthForms && typeof window.KaiAuthForms.fetchFormJson === 'function') {
                    const params = new URLSearchParams();
                    formData.forEach((value, key) => params.append(key, value));
                    const { response, data } = await window.KaiAuthForms.fetchFormJson(url, params, { timeoutMs: 15000 });
                    return { ok: response.ok, data };
                }

                const controller = new AbortController();
                const timer = setTimeout(() => controller.abort(), 15000);
                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        cache: 'no-store',
                        signal: controller.signal
                    });
                    const raw = await response.text();
                    const data = JSON.parse(String(raw || '').replace(/^\uFEFF/, '').trim() || '{}');
                    return { ok: response.ok, data };
                } finally {
                    clearTimeout(timer);
                }
            }

            const passwordForm = document.getElementById('password-form');
            const passwordButton = document.getElementById('btn-save-password');
            const twofaInput = document.getElementById('twofa_enabled_input');
            const statusBadge = document.getElementById('twofa-status-badge');

            passwordButton?.addEventListener('click', async function () {
                const formData = new FormData(passwordForm);
                setButtonState(passwordButton, true, 'Đang xử lý...', 'CẬP NHẬT MẬT KHẨU');

                try {
                    const { data } = await postForm(endpoints.passwordUpdate, formData);
                    if (data && data.success) {
                        SwalHelper.toast(data.message || 'Đổi mật khẩu thành công', 'success');
                        passwordForm.reset();
                        setButtonState(passwordButton, false, '', 'CẬP NHẬT MẬT KHẨU');
                        setTimeout(() => {
                            window.location.href = endpoints.logout;
                        }, 1200);
                        return;
                    }
                    SwalHelper.error((data && data.message) || 'Không thể cập nhật mật khẩu.');
                } catch (error) {
                    SwalHelper.error('Không thể kết nối đến máy chủ!');
                }

                setButtonState(passwordButton, false, '', 'CẬP NHẬT MẬT KHẨU');
            });

            twofaInput?.addEventListener('change', async function () {
                const isChecked = this.checked;
                const formData = new FormData();
                formData.append('twofa_enabled', isChecked ? '1' : '0');

                // Visual feedback during save
                if (statusBadge) statusBadge.innerHTML = '<i class="fa fa-spinner fa-spin"></i>...';
                this.disabled = true;

                try {
                    const { data } = await postForm(endpoints.securityUpdate, formData);
                    if (data && data.success) {
                        const enabled = Number(data.twofa_enabled || 0) === 1;
                        if (statusBadge) {
                            statusBadge.textContent = enabled ? 'Đang bật' : 'Đang tắt';
                        }
                        SwalHelper.toast(data.message || 'Đã cập nhật bảo mật', 'success');
                    } else {
                        // Revert on error
                        this.checked = !isChecked;
                        if (statusBadge) {
                            statusBadge.textContent = !isChecked ? 'Đang bật' : 'Đang tắt';
                        }
                        SwalHelper.error((data && data.message) || 'Không thể lưu cài đặt bảo mật.');
                    }
                } catch (error) {
                    this.checked = !isChecked;
                    if (statusBadge) {
                        statusBadge.textContent = !isChecked ? 'Đang bật' : 'Đang tắt';
                    }
                    SwalHelper.error('Không thể kết nối đến máy chủ!');
                } finally {
                    this.disabled = false;
                }
            });
        })();
    </script>
</body>

</html>
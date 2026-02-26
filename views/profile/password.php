<?php
$userPageTitle = 'Đổi mật khẩu';
$userPageAssetFlags = [
    'interactive_bundle' => false,
];
$activePage = 'password';
require __DIR__ . '/layout/header.php';
?>

<div class="profile-card">
    <div class="profile-card-header profile-card-header--with-actions">
        <div>
            <h5 class="text-dark mb-1">Thay đổi mật khẩu</h5>
            <div class="user-card-subtitle">Thay đổi mật khẩu đăng nhập để tăng bảo mật cho tài khoản của bạn.</div>
        </div>
    </div>
    <div class="profile-card-body">
        <form id="password-form" class="row g-3" novalidate>
            <div class="col-lg-4 col-md-12">
                <label class="form-label user-label">Mật khẩu hiện tại</label>
                <div class="custom-input-wrap">
                    <input type="password" name="password1" class="form-control custom-bg-input"
                        placeholder="Nhập mật khẩu hiện tại">
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <label class="form-label user-label">Mật khẩu mới</label>
                <div class="custom-input-wrap">
                    <input type="password" name="password2" class="form-control custom-bg-input"
                        placeholder="Nhập mật khẩu mới">
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <label class="form-label user-label">Nhập lại mật khẩu mới</label>
                <div class="custom-input-wrap">
                    <input type="password" name="password3" class="form-control custom-bg-input"
                        placeholder="Nhập lại mật khẩu mới">
                </div>
            </div>
            <div class="col-12 mt-4">
                <button type="button" id="btn-save-password" class="btn btn-save-green w-100">
                    CẬP NHẬT MẬT KHẨU
                </button>
            </div>
        </form>
    </div>
</div>

<div class="profile-card mt-4">
    <div class="profile-card-header d-flex align-items-center justify-content-between gap-3">
        <h5 class="text-dark mb-0">Xác minh 2 bước (OTP Gmail)</h5>
        <span class="user-status-badge <?= ((int) ($user['twofa_enabled'] ?? 0) === 1) ? 'is-on' : 'is-off' ?>"
            id="twofa-status-badge">
            <?= ((int) ($user['twofa_enabled'] ?? 0) === 1) ? 'Đang bật' : 'Đang tắt' ?>
        </span>
    </div>
    <div class="profile-card-body">
        <form id="security-form" class="row g-3 align-items-center">
            <div class="col-lg-7 col-md-9">
                <div class="user-security-switch">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="twofa_enabled_input"
                            name="twofa_enabled" value="1" <?= ((int) ($user['twofa_enabled'] ?? 0) === 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="twofa_enabled_input">
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

<script src="<?= asset('assets/js/auth-forms.js') ?>"></script>
<script>
    (function () {
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

        function setTwofaBadge(statusBadge, enabled, loading) {
            if (!statusBadge) return;
            statusBadge.classList.remove('is-on', 'is-off', 'is-loading');
            if (loading) {
                statusBadge.classList.add('is-loading');
                statusBadge.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang lưu';
                return;
            }
            statusBadge.classList.add(enabled ? 'is-on' : 'is-off');
            statusBadge.textContent = enabled ? 'Đang bật' : 'Đang tắt';
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
                    setTimeout(function () {
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

            setTwofaBadge(statusBadge, isChecked, true);
            this.disabled = true;

            try {
                const { data } = await postForm(endpoints.securityUpdate, formData);
                if (data && data.success) {
                    const enabled = Number(data.twofa_enabled || 0) === 1;
                    setTwofaBadge(statusBadge, enabled, false);
                    SwalHelper.toast(data.message || 'Đã cập nhật bảo mật', 'success');
                } else {
                    this.checked = !isChecked;
                    setTwofaBadge(statusBadge, !isChecked, false);
                    SwalHelper.error((data && data.message) || 'Không thể lưu cài đặt bảo mật.');
                }
            } catch (error) {
                this.checked = !isChecked;
                setTwofaBadge(statusBadge, !isChecked, false);
                SwalHelper.error('Không thể kết nối đến máy chủ!');
            } finally {
                this.disabled = false;
            }
        });
    })();
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>

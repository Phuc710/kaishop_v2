<?php
$profileSection = (string) ($profileSection ?? 'profile');
$isBalanceSection = $profileSection === 'balance';
$userPageTitle = $isBalanceSection ? 'Nạp tiền' : 'Thông tin tài khoản';
$userPageAssetFlags = [
    'interactive_bundle' => false,
];
$activePage = $isBalanceSection ? 'balance' : 'profile';
require __DIR__ . '/layout/header.php';
?>

<?php if ($isBalanceSection): ?>
    <?php require __DIR__ . '/balance.php'; ?>
    <script src="<?= asset('assets/js/balance-success.js') ?>"></script>
    <script src="<?= asset('assets/js/balance-bank.js') ?>"></script>
<?php else: ?>
    <?php
    $currentBalance = (int) ($user['money'] ?? 0);
    $totalDeposit = (int) ($user['tong_nap'] ?? 0);
    $usedBalance = max(0, $totalDeposit - $currentBalance);
    $twofaEnabled = (int) ($user['twofa_enabled'] ?? 0) === 1;
    ?>

    <div class="profile-card">
        <div class="profile-card-header profile-card-header--with-actions">
            <div>
                <h5 class="text-dark mb-1">VÍ CỦA TÔI</h5>
            </div>
        </div>
        <div class="profile-card-body p-4">
            <div class="row g-3">
                <div class="col-xl-4 col-md-6">
                    <div class="stat-box neutral">
                        <div class="user-label user-label--sm">Số dư hiện tại</div>
                        <div class="balance-amount " style="color: #198754 !important;"><?= tien($currentBalance); ?>đ</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="stat-box neutral">
                        <div class="user-label user-label--sm">Tổng tiền nạp</div>
                        <div class="balance-amount"><?= tien($totalDeposit); ?>đ</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-12">
                    <div class="stat-box neutral">
                        <div class="user-label user-label--sm">Số dư đã sử dụng</div>
                        <div class="balance-amount"><?= tien($usedBalance); ?>đ</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-card" id="profile-info-card">
        <div class="profile-card-header profile-card-header--with-actions">
            <div>
                <h5 class="text-dark mb-1">HỒ SƠ CỦA BẠN</h5>
            </div>
            <div class="profile-card-header-actions">
                <button type="button" id="btn-edit" class="btn btn-edit-profile">Chỉnh sửa thông tin</button>
            </div>
        </div>
        <div class="profile-card-body p-4">
            <form id="profile-form" class="row g-4" novalidate>
                <input type="hidden" name="twofa_enabled" value="<?= $twofaEnabled ? '1' : '0' ?>">

                <div class="col-md-6">
                    <label class="form-label user-label">Tên đăng nhập</label>
                    <div class="custom-input-wrap">
                        <input type="text" class="form-control custom-readonly"
                            value="<?= htmlspecialchars((string) $username, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label user-label">Địa chỉ Email</label>
                    <div class="custom-input-wrap">
                        <input type="email" name="email" id="email_input" class="form-control custom-readonly"
                            value="<?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label user-label">Ngày đăng ký</label>
                    <div class="custom-input-wrap">
                        <input type="text" class="form-control custom-readonly"
                            value="<?= htmlspecialchars((string) ($user['time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label user-label">Đăng nhập gần nhất</label>
                    <div class="custom-input-wrap">
                        <input type="text" class="form-control custom-readonly"
                            value="<?= htmlspecialchars((string) ($user['ip'] ?? 'Chưa cập nhật'), ENT_QUOTES, 'UTF-8'); ?>"
                            readonly>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            let editMode = false;
            const form = document.getElementById('profile-form');
            const emailInput = document.getElementById('email_input');
            const editBtn = document.getElementById('btn-edit');

            function setEditMode(enabled) {
                editMode = !!enabled;
                if (!emailInput || !editBtn) {
                    return;
                }

                if (editMode) {
                    emailInput.removeAttribute('readonly');
                    emailInput.classList.remove('custom-readonly');
                    editBtn.innerHTML = 'Lưu thay đổi';
                    editBtn.classList.remove('btn-edit-profile');
                    editBtn.classList.add('btn-save-green');
                    emailInput.focus();
                    return;
                }

                emailInput.setAttribute('readonly', 'readonly');
                emailInput.classList.add('custom-readonly');
                editBtn.innerHTML = 'Chỉnh sửa thông tin';
                editBtn.classList.remove('btn-save-green');
                editBtn.classList.add('btn-edit-profile');
                editBtn.disabled = false;
            }

            async function submitProfile() {
                const formData = new FormData(form);

                if (window.KaiAuthForms && typeof window.KaiAuthForms.fetchFormJson === 'function') {
                    const params = new URLSearchParams();
                    formData.forEach((value, key) => params.append(key, value));
                    const result = await window.KaiAuthForms.fetchFormJson('<?= url('profile/update') ?>', params, { timeoutMs: 15000 });
                    return result.data || {};
                }

                const response = await fetch('<?= url('profile/update') ?>', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    cache: 'no-store'
                });
                const raw = await response.text();
                return JSON.parse(String(raw || '').replace(/^\uFEFF/, '').trim() || '{}');
            }

            if (!form || !emailInput || !editBtn) {
                return;
            }

            editBtn.addEventListener('click', async function () {
                if (!editMode) {
                    setEditMode(true);
                    return;
                }

                editBtn.disabled = true;
                editBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang lưu...';

                try {
                    const data = await submitProfile();
                    if (data && data.success) {
                        SwalHelper.toast(data.message || 'Đã cập nhật thông tin', 'success');
                        setEditMode(false);
                        return;
                    }
                    SwalHelper.error((data && data.message) ? data.message : 'Có lỗi xảy ra');
                    editBtn.disabled = false;
                    editBtn.innerHTML = 'Lưu thay đổi';
                } catch (error) {
                    SwalHelper.error('Có lỗi xảy ra, vui lòng thử lại!');
                    editBtn.disabled = false;
                    editBtn.innerHTML = 'Lưu thay đổi';
                }
            });
        })();
    </script>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>
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
    <script src="<?= asset('assets/js/balance-binance.js') ?>"></script>
<?php else: ?>
    <style>
        .kai-simple-currency-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 5px 15px;
            background: #fff;
            border: 1.5px solid #000;
            border-radius: 12px;
            color: #000 !important;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            outline: none !important;
            min-height: 40px;
        }

        .kai-simple-currency-toggle:hover {
            background: #f8f9fa;
            transform: scale(1.02);
        }

        .kai-simple-currency-toggle img,
        #currency-toggle-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            font-size: 1.2rem;
            line-height: 1;
            border-color: 2px solid #000 !important;

        }


        .kai-simple-currency-toggle.is-usd {
            background: #eff6ff;
            border-color: 2px solid #000 !important;
        }

        .avatar-upload-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 10px;
        }

        .avatar-preview-container {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #ff6900;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.25s ease;
            margin: 0 auto;
        }

        .avatar-preview-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
            font-size: 12px;
            font-weight: 600;
        }

        .avatar-upload-overlay i {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .avatar-preview-container.editable {
            cursor: pointer;
        }

        .avatar-preview-container.editable:hover .avatar-upload-overlay {
            opacity: 1;
        }

        .avatar-preview-container.editable:hover {
            transform: scale(1.03);
            border-color: #0d28fd;
        }
    </style>
    <?php
    $currentBalance = (int) ($user['money'] ?? 0);
    $totalDeposit = (int) ($user['tong_nap'] ?? 0);
    $usedBalance = max(0, $totalDeposit - $currentBalance);
    $twofaEnabled = (int) ($user['twofa_enabled'] ?? 0) === 1;
    $walletExchangeRate = (int) get_setting('binance_rate_vnd', 25000);
    if ($walletExchangeRate <= 0)
        $walletExchangeRate = 25000;
    ?>

    <div class="profile-card" data-wallet-card data-exchange-rate="<?= $walletExchangeRate ?>">
        <div class="profile-card-header profile-card-header--with-actions">
            <div>
                <h5 class="text-dark mb-1">VÍ CỦA TÔI</h5>
            </div>
            <div class="profile-card-header-actions">
                <button type="button" id="btn-toggle-currency" class="kai-simple-currency-toggle"
                    title="Chuyển đổi tiền tệ">
                    <span id="currency-toggle-icon">
                        <img src="<?= asset('assets/images/vn.png') ?>" alt="VND">
                    </span>
                    <span id="currency-toggle-label">VND</span>
                </button>
            </div>
        </div>
        <div class="profile-card-body p-4">
            <div class="row g-3">
                <div class="col-xl-4 col-md-6">
                    <div class="stat-box neutral">
                        <div class="user-label user-label--sm">Số dư hiện tại</div>
                        <div class="balance-amount" style="color: #198754 !important;" data-wallet-amount
                            data-price-vnd="<?= $currentBalance ?>"><?= tien($currentBalance); ?></div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="stat-box neutral">
                        <div class="user-label user-label--sm">Tổng tiền nạp</div>
                        <div class="balance-amount" data-wallet-amount data-price-vnd="<?= $totalDeposit ?>">
                            <?= tien($totalDeposit); ?>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-12">
                    <div class="stat-box neutral">
                        <div class="user-label user-label--sm">Số dư đã sử dụng</div>
                        <div class="balance-amount" data-wallet-amount data-price-vnd="<?= $usedBalance ?>">
                            <?= tien($usedBalance); ?>
                        </div>
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

                <!-- Avatar Upload Section -->
                <div class="col-md-12 text-center mb-2">
                    <div class="avatar-upload-wrapper">
                        <?php
                        $userAvatar = trim((string) ($user['avatar_url'] ?? ''));
                        if ($userAvatar === '') {
                            $userAvatar = asset('assets/images/avt.png');
                        }
                        ?>
                        <div class="avatar-preview-container" id="avatar-preview-container">
                            <img id="avatar-preview" src="<?= htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar">
                            <div class="avatar-upload-overlay">
                                <i class="fas fa-camera"></i>
                                <span>Thay ảnh</span>
                            </div>
                        </div>
                        <input type="file" id="avatar-file-input" accept="image/*" style="display: none;">
                        <input type="hidden" name="avatar_url" id="avatar_url_hidden" value="<?= htmlspecialchars(($user['avatar_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <small class="text-muted d-block mt-2" style="font-size: 11px;">Đăng ảnh vuông (1:1), tự động nén & chuyển sang WebP.</small>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label user-label">Họ và tên</label>
                    <div class="custom-input-wrap">
                        <input type="text" name="full_name" id="full_name_input" class="form-control custom-readonly"
                            value="<?= htmlspecialchars((string) ($user['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            readonly>
                    </div>
                </div>

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
                        <?php
                        $displayValue = !empty($user['last_login']) ? TimeService::instance()->formatDisplay($user['last_login'], 'H:i d/m/Y') : 'Chưa cập nhật';
                        ?>
                        <input type="text" class="form-control custom-readonly" value="<?= $displayValue ?>" readonly>
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
            const fullNameInput = document.getElementById('full_name_input');
            const editBtn = document.getElementById('btn-edit');
            const avatarContainer = document.getElementById('avatar-preview-container');
            const avatarFileInput = document.getElementById('avatar-file-input');
            const avatarPreview = document.getElementById('avatar-preview');
            const avatarUrlHidden = document.getElementById('avatar_url_hidden');

            function setEditMode(enabled) {
                editMode = !!enabled;
                if (!emailInput || !editBtn) {
                    return;
                }

                if (editMode) {
                    emailInput.removeAttribute('readonly');
                    emailInput.classList.remove('custom-readonly');
                    if (fullNameInput) {
                        fullNameInput.removeAttribute('readonly');
                        fullNameInput.classList.remove('custom-readonly');
                    }
                    if (avatarContainer) {
                        avatarContainer.classList.add('editable');
                    }
                    editBtn.innerHTML = 'Lưu thay đổi';
                    editBtn.classList.remove('btn-edit-profile');
                    editBtn.classList.add('btn-save-green');
                    if (fullNameInput) fullNameInput.focus();
                    else emailInput.focus();
                    return;
                }

                emailInput.setAttribute('readonly', 'readonly');
                emailInput.classList.add('custom-readonly');
                if (fullNameInput) {
                    fullNameInput.setAttribute('readonly', 'readonly');
                    fullNameInput.classList.add('custom-readonly');
                }
                if (avatarContainer) {
                    avatarContainer.classList.remove('editable');
                }
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

            if (avatarContainer && avatarFileInput) {
                avatarContainer.addEventListener('click', function () {
                    if (editMode) {
                        avatarFileInput.click();
                    }
                });

                avatarFileInput.addEventListener('change', async function () {
                    const file = avatarFileInput.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('avatar_file', file);

                    if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.loading === 'function') {
                        SwalHelper.loading('Đang tải ảnh đại diện...');
                    }
                    editBtn.disabled = true;

                    try {
                        const response = await fetch('<?= url('profile/upload-avatar') ?>', {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin',
                            cache: 'no-store'
                        });
                        const raw = await response.text();
                        const data = JSON.parse(String(raw || '').replace(/^\uFEFF/, '').trim() || '{}');

                        if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.closeLoading === 'function') {
                            SwalHelper.closeLoading();
                        }
                        editBtn.disabled = false;

                        if (data && data.success) {
                            avatarPreview.src = data.avatar_url;
                            avatarUrlHidden.value = data.avatar_url;
                            // Update sidebar / navbar avatars dynamically if they exist on the page
                            const navAvatars = document.querySelectorAll('img[alt="User Avatar"]');
                            navAvatars.forEach(img => {
                                img.src = data.avatar_url;
                            });
                            SwalHelper.toast('Đã tải lên ảnh đại diện thành công. Vui lòng nhấn Lưu thay đổi để lưu cấu hình.', 'success');
                        } else {
                            SwalHelper.error(data.message || 'Lỗi tải ảnh đại diện');
                        }
                    } catch (err) {
                        if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.closeLoading === 'function') {
                            SwalHelper.closeLoading();
                        }
                        editBtn.disabled = false;
                        SwalHelper.error('Không thể kết nối đến máy chủ.');
                    }
                });
            }

            editBtn.addEventListener('click', async function () {
                if (!editMode) {
                    setEditMode(true);
                    return;
                }

                editBtn.disabled = true;
                editBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang lưu...';

                if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.loading === 'function') {
                    SwalHelper.loading('Đang cập nhật hồ sơ...');
                }

                try {
                    const data = await submitProfile();
                    if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.closeLoading === 'function') {
                        SwalHelper.closeLoading();
                    }
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
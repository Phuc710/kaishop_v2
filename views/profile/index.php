<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Thông tin tài khoản | <?= htmlspecialchars((string) ($chungapi['ten_web'] ?? 'KaiShop'), ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="robots" content="noindex, nofollow">
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="bg-light">
        <section class="py-5">
            <div class="container user-page-container">
                <div class="row">
                    <div class="col-lg-3 col-md-4 mb-4">
                        <?php $activePage = $activePage ?? 'profile';
                        require __DIR__ . '/../../hethong/user_sidebar.php'; ?>
                    </div>

                    <div class="col-lg-9 col-md-8">
                        <div class="profile-card" id="profile-info-card">
                            <div class="profile-card-header">
                                <h1 class="h5 text-dark mb-0">Ví của tôi</h1>
                            </div>
                            <div class="profile-card-body pt-0">
                                <div class="row g-3">
                                    <div class="col-md-4 col-sm-12">
                                        <div class="stat-box neutral">
                                            <div class="user-label">Số dư hiện tại</div>
                                            <div class="balance-amount balance-amount--primary">
                                                <?= tien($user['money']); ?>đ
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="stat-box neutral">
                                            <div class="user-label">Tổng tiền nạp</div>
                                            <div class="balance-amount"><?= tien($user['tong_nap']); ?>đ</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="stat-box neutral">
                                            <div class="user-label">Số dư đã sử dụng</div>
                                            <?php $used = (int) $user['tong_nap'] - (int) $user['money']; ?>
                                            <div class="balance-amount">
                                                <?= tien($used > 0 ? $used : 0); ?>đ
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="profile-card">
                            <div class="profile-card-header mb-3">
                                <h2 class="h5 text-dark mb-0">Hồ sơ của bạn</h2>
                                <button type="button" id="btn-edit" class="btn btn-edit-profile">Chỉnh sửa thông
                                    tin</button>
                            </div>
                            <div class="profile-card-body pt-0">
                                <form id="profile-form" class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label user-label">Tên đăng nhập</label>
                                        <div class="custom-input-wrap">
                                            <input type="text" class="form-control custom-readonly"
                                                value="<?= htmlspecialchars((string) $username, ENT_QUOTES, 'UTF-8'); ?>"
                                                readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label user-label">Địa chỉ Email</label>
                                        <div class="custom-input-wrap">
                                            <input type="email" name="email" id="email_input"
                                                class="form-control custom-readonly"
                                                value="<?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label user-label">Ngày đăng ký</label>
                                        <div class="custom-input-wrap">
                                            <input type="text" class="form-control custom-readonly"
                                                value="<?= htmlspecialchars((string) ($user['time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                readonly>
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
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>

    <script>
        (function () {
            let editMode = false;
            const emailInput = document.getElementById('email_input');
            const twofaInput = document.getElementById('twofa_enabled_input');
            const editBtn = document.getElementById('btn-edit');
            const form = document.getElementById('profile-form');

            if (editBtn && emailInput && form) {
                editBtn.addEventListener('click', function () {
                    if (!editMode) {
                        editMode = true;
                        emailInput.removeAttribute('readonly');
                        emailInput.classList.remove('custom-readonly');
                        if (twofaInput) twofaInput.disabled = false;
                        emailInput.focus();
                        editBtn.innerHTML = 'Lưu thay đổi';
                        editBtn.classList.remove('btn-edit-profile');
                        editBtn.classList.add('btn-save-green');
                        return;
                    }

                    editBtn.disabled = true;
                    editBtn.innerHTML = 'Đang lưu...';

                    const formData = new FormData(form);
                    if (twofaInput) {
                        formData.set('twofa_enabled', twofaInput.checked ? '1' : '0');
                    }

                    fetch('<?= url('profile/update') ?>', {
                        method: 'POST',
                        body: formData
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (data && data.success) {
                                SwalHelper.toast(data.message, 'success');
                                editMode = false;
                                emailInput.setAttribute('readonly', 'readonly');
                                emailInput.classList.add('custom-readonly');
                                if (twofaInput) twofaInput.disabled = true;
                                editBtn.disabled = false;
                                editBtn.innerHTML = 'Chỉnh sửa thông tin';
                                editBtn.classList.remove('btn-save-green');
                                editBtn.classList.add('btn-edit-profile');
                            } else {
                                SwalHelper.error((data && data.message) ? data.message : 'Có lỗi xảy ra');
                                editBtn.disabled = false;
                                editBtn.innerHTML = 'Lưu thay đổi';
                            }
                        })
                        .catch(function () {
                            SwalHelper.error('Có lỗi xảy ra, vui lòng thử lại!');
                            editBtn.disabled = false;
                            editBtn.innerHTML = 'Lưu thay đổi';
                        });
                });
            }

        })();
    </script>
</body>

</html>
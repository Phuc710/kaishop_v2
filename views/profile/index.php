<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Thông Tin Tài Khoản | <?= $chungapi['ten_web']; ?></title>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="bg-light">
        <section class="py-5">
            <div class="container user-page-container">
                <div class="row">
                    <!-- Sidebar (DRY component) -->
                    <div class="col-lg-3 col-md-4 mb-4">
                        <?php $activePage = 'profile';
                        require __DIR__ . '/../../hethong/user_sidebar.php'; ?>
                    </div>

                    <!-- Main Content -->
                    <div class="col-lg-9 col-md-8">

                        <!-- Block 1: Ví của tôi -->
                        <div class="profile-card">
                            <div class="profile-card-header">
                                <h5 class="text-dark">Ví của tôi</h5>
                            </div>
                            <div class="profile-card-body pt-0">
                                <div class="row g-3">
                                    <div class="col-md-4 col-sm-12">
                                        <div class="stat-box neutral">
                                            <div class="user-label">Số dư hiện tại</div>
                                            <div class="balance-amount" style="font-size: 32px; color:green">
                                                <?= tien($user['money']); ?>đ
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="stat-box neutral">
                                            <div class="user-label">Tổng tiền nạp</div>
                                            <div class="fw-bold fs-5 text-dark mt-2"><?= tien($user['tong_nap']); ?>đ
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="stat-box neutral">
                                            <div class="user-label">Số dư đã sử dụng</div>
                                            <?php $used = $user['tong_nap'] - $user['money']; ?>
                                            <div class="fw-bold fs-5 text-dark mt-2">
                                                <?= tien($used > 0 ? $used : 0); ?>đ
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Block 2: Hồ sơ của bạn -->
                        <div class="profile-card">
                            <div class="profile-card-header mb-3">
                                <h5 class="text-dark mb-0">Hồ sơ của bạn</h5>
                                <button type="button" id="btn-edit" class="btn btn-edit-profile">Chỉnh sửa thông
                                    tin</button>
                            </div>
                            <div class="profile-card-body pt-0">
                                <form id="profile-form" class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label user-label">Tên đăng nhập</label>
                                        <div class="custom-input-wrap">
                                            <input type="text" class="form-control custom-readonly"
                                                value="<?= $username; ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label user-label">Địa chỉ Email</label>
                                        <div class="custom-input-wrap">
                                            <input type="email" name="email" id="email_input"
                                                class="form-control custom-readonly" value="<?= $user['email']; ?>"
                                                readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label user-label">Ngày đăng ký</label>
                                        <div class="custom-input-wrap">
                                            <input type="text" class="form-control custom-readonly"
                                                value="<?= $user['time']; ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label user-label">Đăng nhập gần nhất</label>
                                        <div class="custom-input-wrap">
                                            <input type="text" class="form-control custom-readonly"
                                                value="<?= $user['ip'] ?? 'Chưa cập nhật'; ?>" readonly>
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
        let editMode = false;
        const emailInput = document.getElementById('email_input');
        const editBtn = document.getElementById('btn-edit');
        const form = document.getElementById('profile-form');

        editBtn.addEventListener('click', function () {
            if (!editMode) {
                // Switch to edit mode
                editMode = true;
                emailInput.removeAttribute('readonly');
                emailInput.classList.remove('custom-readonly');
                emailInput.focus();
                editBtn.innerHTML = 'Lưu thay đổi';
                editBtn.classList.remove('btn-edit-profile');
                editBtn.classList.add('btn-save-green');
            } else {
                // Submit via AJAX
                editBtn.disabled = true;
                editBtn.innerHTML = 'Đang lưu...';

                const formData = new FormData(form);

                fetch('<?= BASE_URL ?>/profile/update', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            SwalHelper.toast(data.message, 'success');
                            
                            // Reset button directly to "Chỉnh sửa thông tin" state (True AJAX)
                            editMode = false;
                            emailInput.setAttribute('readonly', 'readonly');
                            emailInput.classList.add('custom-readonly');
                            
                            editBtn.disabled = false;
                            editBtn.innerHTML = 'Chỉnh sửa thông tin';
                            editBtn.classList.remove('btn-save-green');
                            editBtn.classList.add('btn-edit-profile');
                            
                        } else {
                            SwalHelper.error(data.message);
                            editBtn.disabled = false;
                            editBtn.innerHTML = 'Lưu thay đổi';
                        }
                    })
                    .catch(() => {
                        SwalHelper.error('Có lỗi xảy ra, vui lòng thử lại!');
                        editBtn.disabled = false;
                        editBtn.innerHTML = 'Lưu thay đổi';
                    });
            }
        });
    </script>
</body>

</html>
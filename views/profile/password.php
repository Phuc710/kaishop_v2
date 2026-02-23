<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Đổi Mật Khẩu | <?= $chungapi['ten_web']; ?></title>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="bg-light">
        <section class="py-5">
            <div class="container user-page-container">
                <div class="row">
                    <!-- Sidebar (DRY component) -->
                    <div class="col-lg-3 col-md-4 mb-4">
                        <?php $activePage = 'password';
                        require __DIR__ . '/../../hethong/user_sidebar.php'; ?>
                    </div>

                    <!-- Main Content -->
                    <div class="col-lg-9 col-md-8">
                        <div class="profile-card">
                            <div class="profile-card-header">
                                <h5 class="text-dark">Thay đổi mật khẩu</h5>
                            </div>
                            <div class="profile-card-body">
                                <p class="text-muted mb-4" style="font-size: 14px;">Thay đổi mật khẩu đăng nhập của bạn
                                    là một cách dễ dàng để giữ an toàn cho tài khoản của bạn.</p>
                                <form id="password-form" class="row g-3">
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
                                        <button type="button" id="btn-save" class="btn btn-save-green w-100">
                                            <span class="indicator-label">CẬP NHẬT</span>
                                        </button>
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
        document.getElementById('btn-save').addEventListener('click', function () {
            const btn = this;
            const form = document.getElementById('password-form');
            const formData = new FormData(form);

            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

            fetch('<?= BASE_URL ?>/password/update', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        SwalHelper.toast(data.message, 'success');
                        
                        // Reset form
                        form.reset();
                        
                        // Reset button
                        btn.disabled = false;
                        btn.innerHTML = 'CẬP NHẬT';
                        
                        // Optional: Redirect to logout after a short delay since password changed
                        setTimeout(() => {
                            window.location.href = BASE_URL + '/logout';
                        }, 2000);
                        
                    } else {
                        SwalHelper.error(data.message);
                        btn.disabled = false;
                        btn.innerHTML = 'CẬP NHẬT';
                    }
                })
                .catch(() => {
                    SwalHelper.error('Không thể kết nối đến máy chủ!');
                    btn.disabled = false;
                    btn.innerHTML = 'CẬP NHẬT';
                });
        });
    </script>
</body>

</html>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Thông Tin Tài Khoản | <?= $chungapi['ten_web']; ?></title>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
    
    <main>
        <section class="py-110">
            <div class="container">
                <?php require __DIR__ . '/../../hethong/settings_head.php'; ?>
                
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-xl-7">
                        <div class="settings-card">
                            <div class="settings-card-head">
                                <h4>THÔNG TIN TÀI KHOẢN</h4>
                            </div>
                            <div class="settings-card-body">
                                <form id="profile-form" method="POST" class="row g-4">
                                    
                                    <div class="col-md-6">
                                        <div>
                                            <label class="form-label">Tài khoản</label>
                                            <input type="text" class="form-control shadow-none" 
                                                   value="<?= $username; ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div>
                                            <label class="form-label">Số dư</label>
                                            <input type="text" class="form-control shadow-none"
                                                   value="<?= tien($user['money']); ?>đ" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div>
                                            <label class="form-label">Tổng nạp</label>
                                            <input type="text" class="form-control shadow-none"
                                                   value="<?= tien($user['tong_nap']); ?>đ" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div>
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" id="email_input" 
                                                   class="form-control shadow-none"
                                                   value="<?= $user['email']; ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div>
                                            <label class="form-label">Loại cấp bậc</label>
                                            <input type="text" class="form-control shadow-none"
                                                   value="<?= capbac($user['level']); ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div>
                                            <label class="form-label">Ngày đăng ký</label>
                                            <input type="text" class="form-control shadow-none"
                                                   value="<?= $user['time']; ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <button type="button" id="update-btn" class="btn btn-primary">
                                            Cập Nhật
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
    
    <style>
        /* Readonly fields styling */
        input[readonly].form-control {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        input:not([readonly]).form-control {
            background-color: #ffffff;
        }
    </style>
    
    <script>
        let editMode = false;
        const emailInput = document.getElementById('email_input');
        const updateBtn = document.getElementById('update-btn');
        const form = document.getElementById('profile-form');
        
        updateBtn.addEventListener('click', function() {
            if (!editMode) {
                // Switch to edit mode
                editMode = true;
                emailInput.removeAttribute('readonly');
                emailInput.focus();
                updateBtn.innerHTML = 'Lưu thay đổi';
                updateBtn.classList.remove('btn-primary');
                updateBtn.classList.add('btn-success');
            } else {
                // Submit form via AJAX
                const formData = new FormData(form);
                
                updateBtn.disabled = true;
                updateBtn.innerHTML = 'Đang xử lý...';
                
                fetch('<?= BASE_URL ?>/profile/update', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            text: data.message
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: data.message
                        });
                        updateBtn.disabled = false;
                        updateBtn.innerHTML = 'Lưu thay đổi';
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: 'Có lỗi xảy ra, vui lòng thử lại!'
                    });
                    updateBtn.disabled = false;
                    updateBtn.innerHTML = 'Lưu thay đổi';
                });
            }
        });
    </script>
</body>
</html>

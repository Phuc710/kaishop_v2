<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->


<head>

<?php require __DIR__ . '/../hethong/head2.php'; ?>

<?php
// Xử lý cập nhật thông tin profile
if (isset($_POST['update'])) {
    $new_email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // Validation
    if (empty($new_email)) {
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "Lỗi!",
                text: "Email không được để trống!"
            });
        </script>';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "Lỗi!",
                text: "Email không hợp lệ!"
            });
        </script>';
    } else {
        // Kiểm tra email đã tồn tại chưa (trừ email của user hiện tại)
        $check_email = $connection->query("SELECT * FROM `users` WHERE `email` = '$new_email' AND `username` != '$username' ");
        
        if ($check_email->num_rows > 0) {
            echo '<script>
                Swal.fire({
                    icon: "error",
                    title: "Lỗi!",
                    text: "Email này đã được sử dụng bởi tài khoản khác!"
                });
            </script>';
        } else {
            // Update email vào database
            $update = $connection->query("UPDATE `users` SET `email` = '$new_email' WHERE `username` = '$username' ");
            
            if ($update) {
                // Cập nhật lại thông tin user trong session
                $user = $connection->query("SELECT * FROM `users` WHERE `session` = '$session' ")->fetch_array();
                
                echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "Thành công!",
                        text: "Cập nhật email thành công!"
                    }).then(function() {
                        window.location.href = "' . url('profile') . '";
                    });
                </script>';
            } else {
                echo '<script>
                    Swal.fire({
                        icon: "error",
                        title: "Lỗi!",
                        text: "Có lỗi xảy ra, vui lòng thử lại!"
                    });
                </script>';
            }
        }
    }
}
?>

    <title>Thông Tin Tài Khoản | <?= $chungapi['ten_web']; ?></title>
    <?php require __DIR__ . '/../hethong/nav.php'; ?>
<main>
        <section class="py-110">
            <div class="container">
    <?php require __DIR__ . '/../hethong/settings_head.php'; ?>
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-xl-7">
                        <div class="settings-card">
                            <div class="settings-card-head">
                                <h4>THÔNG TIN TÀI KHOẢN</h4>
                            </div>
                            <div class="settings-card-body">
                                <form method="POST" action="" enctype="multipart/form-data" class="row g-4">
                                    <!-- <div class="col-md-12">
                                        <div>
                                            <label for="profile_picture" class="form-label">Chọn ảnh đại
                                                diện mới</label>
                                            <input type="file" class="form-control shadow-none" id="profile_picture"
                                                name="profile_picture" accept="image/*">
                                            <i>Chỉ cho phép các định dạng như: jpeg,png,gif. Kích thước ảnh
                                                tối đa 2MB</i>
                                        </div>
                                    </div> -->
                                    <div class="col-md-6">
                                        <div>
                                            <label for="fname" class="form-label">Tài khoản</label>
                                            <input type="text" class="form-control shadow-none" value="<?= $username; ?>"
                                                readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div>
                                            <label for="fname" class="form-label">Số dư</label>
                                            <input type="text" class="form-control shadow-none"
                                                value="<?= tien($user['money']); ?>đ" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div>
                                            <label for="fname" class="form-label">Tổng nạp</label>
                                            <input type="text" class="form-control shadow-none"
                                                value="<?= tien($user['tong_nap']); ?>đ" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div>
                                            <label for="fname" class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control shadow-none"
                                                value="<?= $user['email']; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div>
                                            <label for="fname" class="form-label">Loại cấp bậc</label>
                                            <input type="text" class="form-control shadow-none"
                                                value="<?= capbac($user['level']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div>
                                            <label for="fname" class="form-label">Ngày đăng ký</label>
                                            <input type="text" class="form-control shadow-none"
                                                value="<?= $user['time']; ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" name="update" class="btn btn-primary">
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
        /* Styling cho các trường readonly - màu xám nhạt */
        input[readonly].form-control {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        /* Khi có thể edit thì background trắng */
        input:not([readonly]).form-control {
            background-color: #ffffff;
        }
    </style>
    
    <script>
        let editMode = false;
        const emailInput = document.querySelector('input[value="<?= $user['email']; ?>"]');
        const updateBtn = document.querySelector('button[name="update"]');
        
        // Thêm ID cho email input để dễ quản lý
        if (emailInput) {
            emailInput.id = 'email_input';
        }
        
        updateBtn.addEventListener('click', function(e) {
            if (!editMode) {
                // Chuyển sang chế độ chỉnh sửa
                e.preventDefault();
                editMode = true;
                
                // Mở khóa email để có thể chỉnh sửa
                emailInput.removeAttribute('readonly');
                emailInput.focus();
                
                // Đổi text button
                updateBtn.innerHTML = 'Lưu thay đổi';
                updateBtn.classList.remove('btn-primary');
                updateBtn.classList.add('btn-success');
            } else {
                // Cho phép submit form khi đã ở chế độ edit
                // Form sẽ submit bình thường
            }
        });
    </script>
    
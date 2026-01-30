<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->


<head>
    <?php require __DIR__ . '/../hethong/head2.php'; ?>
    <title>Thông Tin Tài Khoản | <?= $chungapi['ten_web']; ?></title>
    <?php require __DIR__ . '/../hethong/nav.php'; ?>
<main>
        <section class="py-110">
            <div class="container">
    <?php require __DIR__ . '/../hethong/settings_head.php'; ?>
<div class="row">
                    <div class="col-lg-6">
                        <div class="settings-card">
                            <div class="settings-card-head">
                                <h4>THÔNG TIN TÀI KHOẢN</h4>
                            </div>
                            <div class="settings-card-body">
                                <form method="POST" action="" enctype="multipart/form-data" class="row g-4">
                                    <div class="col-md-12">
                                        <div>
                                            <label for="profile_picture" class="form-label">Chọn ảnh đại
                                                diện mới</label>
                                            <input type="file" class="form-control shadow-none" id="profile_picture"
                                                name="profile_picture" accept="image/*">
                                            <i>Chỉ cho phép các định dạng như: jpeg,png,gif. Kích thước ảnh
                                                tối đa 2MB</i>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div>
                                            <label for="fname" class="form-label">iD Tài khoản</label>
                                            <input type="text" class="form-control shadow-none"
                                                value="<?= $user['id']; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div>
                                            <label for="fname" class="form-label">Tài khoản</label>
                                            <input type="text" class="form-control shadow-none" value="<?= $username; ?>"
                                                readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div>
                                            <label for="fname" class="form-label">Số dư</label>
                                            <input type="text" class="form-control shadow-none"
                                                value="<?= tien($user['money']); ?>đ" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div>
                                            <label for="fname" class="form-label">Tổng nạp</label>
                                            <input type="text" class="form-control shadow-none"
                                                value="<?= tien($user['tong_nap']); ?>đ" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div>
                                            <label for="fname" class="form-label">Email</label>
                                            <input type="text" class="form-control shadow-none"
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
                                    <div class="col-md-6">
                                        <div>
                                            <label for="fname" class="form-label">IP Truy cập</label>
                                            <input type="text" class="form-control shadow-none"
                                                value="<?= $user['ip']; ?>" readonly>
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
                    <div class="col-lg-6">
                        <div class="settings-card">
                            <div class="settings-card-head">
                                <h4>THÔNG TIN LIÊN HỆ</h4>
                            </div>
                            <div class="settings-card-body">
                                <form id="contact-links-form" action="" method="POST">
                                    <div id="link-container">
                                        <!-- Dòng liên kết mẫu -->
                                    </div>
                                    <button type="button" id="add-link" class="btn btn-success mt-3">Thêm dòng</button>
                                    <button type="submit" name="contact" class="btn btn-primary mt-3">Lưu liên
                                        kết</button>
                                </form>

                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </section>
    </main>
    
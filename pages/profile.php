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
                <div class="settings-page-lists">
                    <ul class="settings-head">
                        <li>
                            <a href="/profile" class="menu-item">Hồ sơ</a>
                        </li>
                        <li>
                            <a href="/password" class="menu-item">Đổi mật khẩu</a>
                        </li>
                        <li>
                            <a href="/history-code" class="menu-item">Lịch sử mua mã nguồn</a>
                        </li>
                        <li>
                            <a href="/history-tao-web" class="menu-item">Lịch sử tạo web</a>
                        </li>
                        <li>
                            <a href="/history-hosting" class="menu-item">Lịch sử mua hosting</a>
                        </li>
                        <li>
                            <a href="/history-logo" class="menu-item">Lịch sử tạo logo</a>
                        </li>
                        <li>
                            <a href="/history-mien" class="menu-item">Lịch sử mua miền</a>
                        </li>
                        <li>
                            <a href="/history-subdomain" class="menu-item">Lịch sử thuê subdomain</a>
                        </li>
                    </ul>
                </div>
                <script>
                    $(document).ready(function () {
                        var url = window.location.pathname;
                        var urlRegExp = new RegExp(url.replace(/\/$/, '') + "$");
                        $('.menu-item').each(function () {
                            if (urlRegExp.test(this.href.replace(/\/$/, ''))) {
                                $(this).addClass('active');
                            }
                        });
                    });
                </script>
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
    
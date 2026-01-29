<?php
require __DIR__ . '/../../hethong/config.php';

// Kiểm tra nếu người dùng đã đăng nhập
if (isset($_SESSION['session'])) {
    echo '<script>
        alert("Bạn đã đăng nhập rồi!");
        window.location.href = BASE_URL + "/";
    </script>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <base href="../../../" />
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Đăng Nhập Tài Khoản | <?= $chungapi['ten_web']; ?> </title>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <head>

        <main>
            <section class="py-5 bg-offWhite">
                <div class="container">
                    <div class="rounded-3">

                        <div class="row">
                            <div class="col-lg-6 p-3 p-lg-5 m-auto">
                                <div class="login-userset">
                                    <div class="login-card">
                                        <div class="login-heading">
                                            <h3>Đăng Nhập Tài Khoản</h3>
                                            <p>Điền vào các trường để vào tài khoản của bạn</p>
                                        </div>
                                        <div class="form-wrap form-focus">
                                            <span class="form-icon">
                                                <i class="feather-mail"></i>
                                            </span>
                                            <input type="text" id="username" class="form-control floating">
                                            <label class="focus-label">Tài khoản</label>
                                        </div>
                                        <div class="form-wrap form-focus pass-group">
                                            <span class="form-icon">
                                                <i class="toggle-password feather-eye-off"></i>
                                            </span>
                                            <input type="password" id="password"
                                                class="pass-input form-control  floating">
                                            <label class="focus-label">Mật khẩu</label>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="">
                                                <div class="form-wrap">
                                                    <label class="custom_check mb-0">Lưu phiên đăng nhập
                                                        <input type="checkbox" id="remember" name="remember">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="">
                                                <div class="form-wrap text-md-end">
                                                    <a href="/auth/password-reset" class="forgot-link">Quên mật
                                                        khẩu?</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-wrap mantadory-info d-none">
                                            <p><i class="feather-alert-triangle"></i>Fill all the fields to submit</p>
                                        </div>
                                        <button type="button" onclick="login()" class="btn btn-primary w-100">
                                            <span id="button1" class="indicator-label">Đăng Nhập</span>
                                            <span id="button2" class="indicator-progress" style="display: none;"> <i
                                                    class="fa fa-spinner fa-spin"></i> Đang xử lý.. </span>
                                        </button>
                                        <div class="login-or">
                                            <span class="span-or">or sign up with</span>
                                        </div>
                                        <ul class="login-social-link d-flex justify-content-center">
                                            <li>
                                                <a href="/">
                                                    <img src="<?=asset('assets/images/google-icon.svg')?>" alt="Google"> Google
                                                </a>
                                            </li>

                                        </ul>
                                    </div>
                                    <div class="acc-in">
                                        <p>Không có tài khoản ?
                                            <a href="<?= url('register') ?>"> Tạo tài khoản </a>
                                        </p>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </section>
        </main>

        <script>
            function login() {
                const button1 = document.getElementById("button1");
                const button2 = document.getElementById("button2");

                if (button2.disabled) return;

                button1.style.display = "none";
                button2.style.display = "inline-block";
                button2.disabled = true;

                const username = document.getElementById("username").value;
                const password = document.getElementById("password").value;

                const xhr = new XMLHttpRequest();
                xhr.open("POST", "/ajax/auth/xulylogin.php");
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                xhr.onload = function () {
                    button1.style.display = "inline-block";
                    button2.style.display = "none";
                    button2.disabled = false;

                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            showMessage("Đăng nhập thành công!", "success");
                            setTimeout(() => {
                                window.location.href = BASE_URL + "/";
                            }, 1000);
                        } else {
                            showMessage(response.message, "error");
                        }
                    } else {
                        showMessage("Lỗi: " + xhr.statusText, "error");
                    }
                };

                xhr.onerror = function () {
                    button1.style.display = "inline-block";
                    button2.style.display = "none";
                    button2.disabled = false;

                    showMessage("Lỗi kết nối đến máy chủ!", "error");
                };

                xhr.send(
                    "username=" + encodeURIComponent(username) +
                    "&password=" + encodeURIComponent(password)
                );
            }
        </script>
        <?php require __DIR__ . '/../../hethong/foot.php'; ?>
        </body>

</html>
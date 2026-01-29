<?php
require __DIR__ . '/../../hethong/config.php';
session_start();

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
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Đăng Ký Tài Khoản | <?= $chungapi['ten_web']; ?></title>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
</head>

<main>
    <section class="py-5 bg-offWhite">
        <div class="container">
            <div class="rounded-3">
                <div class="row">
                    <div class="col-lg-6 p-3 p-lg-5 m-auto">
                        <div class="login-userset">
                            <div class="login-card">
                                <div class="login-heading">
                                    <h3>Đăng Ký Tài Khoản</h3>

                                </div>
                                <div class="form-wrap form-focus">
                                    <span class="form-icon">
                                        <i class="feather-user"></i>
                                    </span>
                                    <input type="text" class="form-control floating" id="username">
                                    <label class="focus-label">Tài khoản *</label>
                                </div>
                                <div class="form-wrap form-focus">
                                    <span class="form-icon">
                                        <i class="feather-mail"></i>
                                    </span>
                                    <input type="email" class="form-control floating" id="email">
                                    <label class="focus-label">Email</label>
                                </div>
                                <div class="form-wrap form-focus pass-group">
                                    <span class="form-icon">
                                        <i class="toggle-password feather-eye-off"></i>
                                    </span>
                                    <input type="password" class="pass-input form-control  floating" id="password">
                                    <label class="focus-label">Mật khẩu</label>
                                </div>
                                <div class="d-flex justify-content-center mb-2">
                                    <div class="g-recaptcha" id="dailycode"
                                        data-sitekey="6LfJcyQpAAAAACA7VSW5YtjNhitPDqsEdjzsIol2"> </div>
                                </div>
                                <button type="button" onclick="dangky()" class="btn btn-primary w-100">
                                    <span id="button1" class="indicator-label">Đăng Ký</span>
                                    <span id="button2" class="indicator-progress" style="display: none;"> <i
                                            class="fa fa-spinner fa-spin"></i> Đang xử lý.. </span></span>
                                </button>
                                <div class="login-or">
                                    <span class="span-or">or sign up with</span>
                                </div>
                                <ul class="login-social-link d-flex justify-content-center">
                                    <li>
                                        <a href="/">
                                            <img src="<?= asset('assets/images/google-icon.svg') ?>" alt="google"> Google
                                        </a>
                                    </li>

                                </ul>
                            </div>
                            <div class="acc-in">
                                <p>Bạn đã có tài khoản? <a href="<?= url('login') ?>">Đăng Nhập</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<script>
    function dangky() {
        const button1 = document.getElementById("button1");
        const button2 = document.getElementById("button2");

        if (button2.disabled) return;

        button1.style.display = "none";
        button2.style.display = "inline-block";
        button2.disabled = true;

        const username = document.getElementById("username").value;
        const password = document.getElementById("password").value;
        const email = document.getElementById("email").value;
        const recaptchaResponse = grecaptcha.getResponse();

        const xhr = new XMLHttpRequest();
        xhr.open("POST", BASE_URL + "/ajax/auth/xulyregister.php");
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onload = function () {
            button1.style.display = "inline-block";
            button2.style.display = "none";
            button2.disabled = false;

            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showMessage("Đăng ký thành công!", "success");
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
            "&password=" + encodeURIComponent(password) +
            "&email=" + encodeURIComponent(email) +
            "&recaptchaResponse=" + encodeURIComponent(recaptchaResponse)
        );
    }  
</script>
<?php require __DIR__ . '/../../hethong/foot.php'; ?>
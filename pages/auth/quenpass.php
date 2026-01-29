<?php
require __DIR__.'/../../hethong/config.php';
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
<head>
    <base href="../../../" />
    <?php require __DIR__.'/../../hethong/head2.php';?>
    <title> Quên mật khẩu | <?=$chungapi['ten_web'];?> </title>
    <?php require __DIR__.'/../../hethong/nav.php';?>

</head>

<body>

    <main>
        <section class="py-5 bg-offWhite">
            <div class="container">
                <div class="rounded-3">
 
                    <div class="row">
                        <div class="col-lg-6 p-3 p-lg-5 m-auto">
                            <div class="login-userset">
                                <div class="login-card">
                                    <div class="login-heading">
                                        <h3>QUÊN MẬT KHẨU</h3>
                                        <p>Chúng tôi sẽ gửi liên kết để đặt lại mật khẩu của bạn</p>
                                    </div>
                                    <div class="form-wrap form-focus">
                                        <span class="form-icon">
                                            <i class="feather-mail"></i>
                                        </span>
                                        <input type="email" id="username" class="form-control floating">
                                        <label class="focus-label">Email</label>
                                    </div>
                                    <button type="button" onclick="register()" class="btn btn-primary w-100">
                        <span id="button1" class="indicator-label">Xác Nhận</span>
                        <span id="button2" class="indicator-progress" style="display: none;"> <i class="fa fa-spinner fa-spin"></i> Đang xử lý.. </span>
                    </button>
 
                                </div>
 
                            </div>
 
                        </div>
 
                    </div>
                </div>
            </div>
        </section>
    </main>

<script>
function register() {
    const button1 = document.getElementById("button1");
    const button2 = document.getElementById("button2");

    button1.style.display = "none";
    button2.style.display = "inline-block";
    button2.disabled = true;

    const username = document.getElementById("username").value;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "/ajax/auth/reset-pass.php");
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
        button1.style.display = "inline-block";
        button2.style.display = "none";
        button2.disabled = false;

        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showMessage("Hệ thống đã gửi liên kết đặt lại mật khẩu tới email của bạn!", "success");

                setTimeout(() => {
                    window.location.href = "";
                }, 3000);
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

    xhr.send("username=" + encodeURIComponent(username));
}
</script>



    <?php require __DIR__.'/../../hethong/foot.php';?>
</body>
</html>
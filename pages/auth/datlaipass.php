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
<!--begin::Head-->

<head>
    <base href="../../../" />
    <?php require __DIR__.'/../../hethong/head2.php';?>
    <title>Đặt Lại Mật Khẩu | <?=$chungapi['ten_web'];?> </title>
    <?php require __DIR__.'/../../hethong/nav.php';?>
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
                                        <h3>ĐẶT LẠI MẬT KHẨU</h3>
                                        <p>Khuyến khích đặt mật khẩu 6-12 chữ bao gồm ký tự đặc biệt</p>
                                    </div>
                                    <div class="form-wrap form-focus">
                                        <span class="form-icon">
                                            <i class="feather-mail"></i>
                                        </span>
                                        <input type="password" id="password" class="form-control floating">
                                        <label class="focus-label">Mật khẩu</label>
                                    </div>
                                    <button type="button" onclick="register()" class="btn btn-primary w-100">
                        <span id="button1" class="indicator-label">Cập Nhật</span>
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

    const otpcode = "<?=$_GET['id'];?>";
    const password = document.getElementById("password").value;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "/xu-ly-mat-khau");
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
        button1.style.display = "inline-block";
        button2.style.display = "none";
        button2.disabled = false;

        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showMessage("Đặt lại mật khẩu thành công!", "success");

                setTimeout(() => {
                    window.location.href = BASE_URL + "/";
                }, 2000);
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
        "otpcode=" + encodeURIComponent(otpcode) +
        "&password=" + encodeURIComponent(password)
    );
}
</script>
<?php require __DIR__.'/../../hethong/foot.php';?>
</body>
</html>
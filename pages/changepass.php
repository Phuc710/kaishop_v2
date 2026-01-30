<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <?php require __DIR__.'/../hethong/head2.php';?>
    <title>Đổi Mật Khẩu | <?=$chungapi['ten_web'];?></title>
    <?php require __DIR__.'/../hethong/nav.php';?>
</head>
<main>
        <section class="py-110">
            <div class="container">
    <?php require __DIR__ . '/../hethong/settings_head.php'; ?>
<div class="row">
    <div class="col-lg-6 mx-auto"> <!-- Thêm mx-auto để căn giữa -->
        <div class="settings-card">
            <div class="settings-card-head text-center">
                <h4>Thay đổi mật khẩu</h4>
            </div>
            <div class="settings-card-body">
                <div class="row g-4">
                    <div class="col-md-12">
                        <label for="password" class="form-label">Mật Khẩu Hiện Tại <span class="text-danger">*</span></label>
                        <input type="password" class="form-control shadow-none" id="password1" name="password1" required>
                    </div>
                    
                    <div class="col-md-12">
                        <label for="password" class="form-label">Mật Khẩu Mới <span class="text-danger">*</span></label>
                        <input type="password" class="form-control shadow-none" id="password2" name="password2" required>
                    </div>

                    <div class="col-md-12">
                        <label for="password" class="form-label">Xác Nhận Mật Khẩu <span class="text-danger">*</span></label>
                        <input type="password" class="form-control shadow-none" id="password3" name="password3" required>
                    </div>

                    <div class="col-12">
                        <button type="button" onclick="save()" class="btn btn-primary w-100">
                            <span id="button1" class="indicator-label">Cập Nhật</span>
                            <span id="button2" class="indicator-progress" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i> Đang xử lý..
                            </span>
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
function save() {
    const password1 = document.getElementById("password1").value;
    const password2 = document.getElementById("password2").value;
    const password3 = document.getElementById("password3").value;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "/ajax/changepass.php");
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showMessage("Thay đổi mật khẩu thành công", "success");
                setTimeout(() => {
                    window.location.href = BASE_URL + "/logout"; // hoặc trang login: "/login"
                }, 1000);
            } else {
                showMessage(response.message, "error");
            }
        } else {
            showMessage("Lỗi máy chủ: " + xhr.statusText, "error");
        }
    };
    xhr.onerror = function() {
        showMessage("Không thể kết nối đến máy chủ!", "error");
    };
    xhr.send(
        "password1=" + encodeURIComponent(password1) +
        "&password2=" + encodeURIComponent(password2) +
        "&password3=" + encodeURIComponent(password3)
    );
}
</script>
<?php require __DIR__.'/../hethong/foot.php';?>
</html>
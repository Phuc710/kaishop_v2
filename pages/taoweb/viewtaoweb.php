<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <?php require __DIR__.'/../../hethong/head2.php';?>
    <title>Quản lý web | <?=$chinhapi['ten_web'];?></title>
    <?php require __DIR__.'/../../hethong/nav.php';?>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
</head>
<?php
if(isset($_GET['id'])) {
$id = $_GET['id'];
$check_host = $ketnoi->query("SELECT * FROM `lich_su_tao_web` WHERE `id` = '$id' ");
if($check_host->num_rows == 1){
    $api_site = $ketnoi->query("SELECT * FROM `lich_su_tao_web` WHERE `id` = '$id' ")->fetch_array();
    $loai_site = $ketnoi->query("SELECT * FROM `list_mau_web` WHERE `id` = '".$api_site['loaiweb']."' ")->fetch_array();
    if($api_site['username']!=$username){
    echo '<script type="text/javascript">if(!alert("Website không tồn tại hay không phải của bạn!")){window.location.href = BASE_URL + "/";}</script>';
    }
}else{
    echo '<script type="text/javascript">if(!alert("Website không tồn tại hay không phải của bạn!")){window.location.href = BASE_URL + "/";}</script>';
}
}
?>
  
    <main>
        <section class="py-110">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card shadow-sm p-3">
                            <div class="pb-4 mb-4">
                                <div class="d-flex align-items-center mb-2">
                                    <h3 class="h5 fw-bold text-dark mb-0">Quản lý web</h3>
                                    <span class=""> <?=status($api_site['status']);?> </span>
                                </div>
                                <a href="https://<?=$api_site['domain'];?>" class="text-primary text-decoration-underline"> <?=$api_site['domain'];?> </a>
                            </div>
 
                            <div class="border-top pt-4 row row-cols-1 row-cols-md-2 gy-4 text-muted mb-6">
                                <div>
                                    <div class="text-secondary">Thanh toán lần đầu</div>
                                    <div class="fw-medium"><?=tien($loai_site['gia']);?> VND</div>
                                </div>
                                <div>
                                    <div class="text-secondary">Số tiền thanh toán định kỳ</div>
                                    <div class="fw-medium"><?=tien($loai_site['gia_han']);?> VND</div>
                                </div>
                                <div>
                                    <div class="text-secondary">Ngày đăng ký</div>
                                    <div class="fw-medium"> <?=ngay($api_site['ngay_mua']);?> </div>
                                </div>
                                <div>
                                    <div class="text-secondary">Ngày hết hạn</div>
                                    <div class="fw-medium"> <?=ngay($api_site['ngay_het']);?> </div>
                                </div>
                                <div>
                                    <div class="text-secondary">Hình thức thanh toán</div>
                                    <div class="fw-medium">Số dư tài khoản</div>
                                </div>
                            </div>
 
                            <div class="row gy-4">

                                <div class="col-md-6">
                                    <label for="username" class="form-label fw-medium">Tài Khoản</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" value="<?=$api_site['user_admin'];?>" readonly>
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="<?=$api_site['user_admin'];?>">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                    </div>
                                </div>
 
                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-medium">Mật khẩu</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" value="<?=$api_site['pass_admin'];?>" readonly>
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="<?=$api_site['pass_admin'];?>">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                    </div>
                                </div>
                               
 
 
                            </div>
                        </div>
                    </div>
<div class="col-md-6">
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h2 class="h5 card-title mb-4">Thao tác web</h2>
            <div class="row g-3 text-center">
                <div class="col-6 col-md-3">
                    <!-- Nút thao tác -->
                    <div id="button1" onclick="giahan()" class="text-center border rounded p-3 h-100 shadow-sm bg-white" style="cursor: pointer;">
                        <img src="<?=asset('assets/images/giahan.svg')?>" alt="Gia hạn" class="mb-2 img-fluid" style="max-height: 60px;">
                        <p class="mb-0 fw-bold text-dark">Gia hạn</p>
                    </div>
                    <!-- Đang xử lý -->
                    <div id="button2" class="text-center border rounded p-3 h-100 shadow-sm bg-white" style="display: none;">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <p class="mb-0 fw-bold text-muted">Đang xử lý...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
</main>
<script>
function giahan() {
    const button1 = document.getElementById("button1");
    const button2 = document.getElementById("button2");

    button1.style.display = "none";
    button2.style.display = "inline-block";
    button2.disabled = true;

    const username = "<?=$username;?>";
    const id_web = "<?=$_GET['id'];?>";
    const giahanValue = 1; // Mặc định gia hạn 1 tháng

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "/ajax/taoweb/giahan.php");
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function() {
        button1.style.display = "inline-block";
        button2.style.display = "none";
        button2.disabled = false;

        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showMessage("Gia hạn thành công, vui lòng đợi hệ thống kích hoạt trở lại", "success");
                setTimeout(() => location.reload(), 2000);
            } else {
                showMessage(response.message, "error");
            }
        } else {
            showMessage("Lỗi máy chủ: " + xhr.statusText, "error");
        }
    };
    xhr.onerror = function() {
        button1.style.display = "inline-block";
        button2.style.display = "none";
        button2.disabled = false;
        showMessage("Không thể kết nối máy chủ!", "error");
    };
    xhr.send(
        "username=" + encodeURIComponent(username) +
        "&id_web=" + encodeURIComponent(id_web) +
        "&giahan=" + encodeURIComponent(giahanValue)
    );
}
</script>
     <?php require __DIR__.'/../../hethong/foot.php';?>
                        <!--end::Content-->
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Page-->
            </div>
            <!--end::Root-->

</html>
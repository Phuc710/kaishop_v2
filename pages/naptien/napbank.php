<!DOCTYPE html>
<html lang="en"> 

<!--begin::Head-->

<head>
    <?php require __DIR__.'/../../hethong/head2.php';?>
    <title>Nạp Tiền Vào Tài Khoản | <?=$chungapi['ten_web'];?></title>
    <?php require __DIR__.'/../../hethong/nav.php';?>
    
    
    <main>
        <section class="py-110">
            <div class="container">
                <div class="row mb-5">
                    <div class="overflow-x-auto">
                        <div class="w-100">
                        <?php
$result = mysqli_query($connection, "SELECT * FROM `list_bank` WHERE `status`='ON'");
while ($row = mysqli_fetch_assoc($result)) { ?>
                    <div class="col-md-4">
                        <div class="bg-white shadow-sm rounded border">
                            <div class="border-b border-blue-500 ">
                                <div class="py-3 text-center">
                                    <img src="https://api.vietqr.io/<?php if ($username != "") { echo ($row['loai']); } else { echo 'Chưa Đăng Nhập '; } ?>/<?=$row['stk'];?>/0/<?php if ($username != "") { echo $chungapi['noi_dung_nap'].$user['id']; } else { echo 'Chưa Đăng Nhập'; } ?>/qronly2.jpg?accountName=<?=$row['name'];?>" class="w-100">
                                </div>
                                <div class="p-4 text-zinc-900">
                                <div class="d-flex justify-content-between mb-2">
                                        <span>Ngân Hàng:</span>
                                        <span class="copy cursor-pointer text-success" data-clipboard-text="<?php if ($username != "") { echo ($row['loai']); } else { echo 'Chưa Đăng Nhập '; } ?><i class="bx bx-copy"></i></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>STK:</span>
                                        <span class="copy cursor-pointer text-success" data-clipboard-text="<?php if ($username != "") { echo ($row['stk']); } else { echo 'Chưa Đăng Nhập '; } ?>"><?php if ($username != "") { echo ($row['stk']); } else { echo 'Chưa Đăng Nhập '; } ?> <i class="bx bx-copy"></i></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Chủ TK:</span>
                                        <span><?php if ($username != "") { echo ($row['ctk']); } else { echo 'Chưa Đăng Nhập '; } ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Nội Dung:</span>
                                        <span class="copy cursor-pointer text-danger" data-clipboard-text="<?php if($username!=""){echo $chungapi['noi_dung_nap'].$user['id'];}else{echo'Chưa Đăng Nhập';}?>"><?php if($username!=""){echo $chungapi['noi_dung_nap'].$user['id'];}else{echo'Chưa Đăng Nhập';}?> <i class="bx bx-copy"></i> </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php }?>
                </div>
             </div>
          </div>
                <div class="row">
                    <h3 class="text-24 fw-bold text-dark-300 mb-2">Lịch sử nạp bank</h3>
                    <form method="GET" action="" class="row">
                        <div class="col-lg col-md-4 col-6">
                            <input class="form-control shadow-none col-sm-2 mb-2" name="tid" type="text" value="" placeholder="Mã giao dịch">
                        </div>
                        <div class="col-lg col-md-4 col-6">
                            <input class="form-control shadow-none col-sm-2 mb-2" name="content" value="" type="text" placeholder="Nội dung">
                        </div>
 
                        <div class="col-lg col-md-4 col-6">
                            <input type="text" class="form-control shadow-none mb-2" name="purchase_date" id="purchase_date" type="text" value="" placeholder="Chọn khoảng thời gian">
                        </div>
                        <div class="col-lg col-md-4 col-6">
                            <button class="shop-widget-btn mb-2"><i class="fas fa-search"></i><span>Tìm kiếm</span></button>
                        </div>
                        <div class="col-lg col-md-4 col-6">
                            <a href="/bank" class="shop-widget-btn mb-2"><i class="far fa-trash-alt"></i><span>Bỏ
                                    lọc</span></a>
                        </div>
                    </form>
                    <div class="overflow-x-auto">
                        <div class="w-100">
                            <table class="w-100 dashboard-table table text-nowrap">
                                <thead class="pb-3">
                                    <tr>
                                        <th scope="col" class="py-2 px-4">STT</th>
                                        <th scope="col" class="py-2 px-4">LOẠI</th>
                                        <th scope="col" class="py-2 px-4">MÃ GIAO DỊCH</th>
                                        <th scope="col" class="py-2 px-4">THỰC NHẬN</th>
                                        <th scope="col" class="py-2 px-4">TRẠNG THÁI</th>
                                        <th scope="col" class="py-2 px-4">THỜI GIAN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
// Truy vấn toàn bộ lịch sử nạp tiền của người dùng mà không phân trang
$result = mysqli_query($connection, "SELECT * FROM `history_nap_bank` WHERE `username` = '$username' ORDER BY `id` DESC");

// Hiển thị dữ liệu
$stt = 1; // Số thứ tự bắt đầu từ 1
while ($dong = mysqli_fetch_assoc($result)) {
?>
                                    <tr>
                                        <td class="text-dark">
                                            <?= $stt++; ?> </td>
                                        <td class="text-dark">
                                            <?= htmlspecialchars($dong['type']); ?> </td>
 
                                        <td class="text-dark">
                                            <?= htmlspecialchars($dong['trans_id']); ?>
                                        </td>
                                        <td class="text-dark">
                                            <?= tien($dong['thucnhan']); ?> </td>
                                        <td>
                                            <span class="status-badge pending"> <?= napthe($dong['status']); ?> </span>
                                        </td>
                                        <td>
                                            <span class="status-badge pending"> <?= ngay($dong['time']); ?> </span>
                                        </td>
                                    </tr>
                                    <?php }?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end">
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
<?php require __DIR__.'/../../hethong/foot.php';?>
</body>
</html>
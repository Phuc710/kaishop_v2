<!DOCTYPE html>
<html lang="en">

<head>
    <?php require __DIR__.'/../../hethong/head2.php';?>
    <title>Trang Quản Lý Subdomain | <?=$chungapi['ten_web'];?></title>
</head>

<body>
    <?php require __DIR__.'/../../hethong/nav.php';?>

    <?php
    if(isset($_GET['id'])) {
        $id = antixss($_GET['id']);
        $check_host = $connection->query("SELECT * FROM `history_subdomain` WHERE `id` = '$id' ");
        if($check_host->num_rows == 1){
            $toz_mien = $check_host->fetch_array();
            $loai_mien = $connection->query("SELECT * FROM `khosubdomain` WHERE `duoimien` = '".$toz_mien['duoimien']."' ")->fetch_array();
            if($toz_mien['username']!=$username){
                echo '<script>alert("Miền không tồn tại hay không phải của bạn!"); window.location.href = BASE_URL + "/";</script>';
                exit;
            }
        } else {
            echo '<script>alert("Miền không tồn tại hay không phải của bạn!"); window.location.href = BASE_URL + "/";</script>';
            exit;
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
                                    <h3 class="h5 fw-bold text-dark mb-0"><?=$toz_mien['tenmien'].$toz_mien['duoimien'];?></h3>
                                    <span class=""><?=host($toz_mien['status']);?></span>
                                </div>
                                <a href="https://<?=$toz_host['domain'];?>" class="text-primary text-decoration-underline"><?=$toz_host['domain'];?></a>
                            </div>
 
                            <div class="border-top pt-4 row row-cols-1 row-cols-md-2 gy-4 text-muted mb-6">
                                <div>
                                    <div class="text-secondary">Thanh toán lần đầu</div>
                                    <div class="fw-medium"><?=tien($loai_mien['gia']);?> VND</div>
                                </div>
                                <div>
                                    <div class="text-secondary">Ngày đăng ký</div>
                                    <div class="fw-medium"><?=ngay($toz_mien['ngaymua']);?></div>
                                </div>
                                <div>
                                    <div class="text-secondary">Ngày hết hạn</div>
                                    <div class="fw-medium"><?=ngay($toz_mien['ngayhet']);?></div>
                                </div>
                                <div>
                                    <div class="text-secondary">Số tiền thanh toán định kỳ</div>
                                    <div class="fw-medium"><?=tien($loai_mien['gia']);?> VND</div>
                                </div>
                                <div>
                                    <div class="text-secondary">Hình thức thanh toán</div>
                                    <div class="fw-medium">Số dư tài khoản</div>
                                </div>
 
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4 shadow-sm">
                            <div class="card-body">

                                            <?php
// ... mã PHP trước đó không thay đổi ...

$limit = 10; // Số bản ghi mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Lấy tổng số bản ghi để tính toán tổng số trang
$totalRecordsQuery = mysqli_query($connection, "SELECT COUNT(*) as total FROM `list_record_domain` WHERE `id_domain` = '".$toz_mien['id']."'");
$totalRecords = mysqli_fetch_assoc($totalRecordsQuery);
$totalPages = ceil($totalRecords['total'] / $limit);

// Truy vấn bản ghi với phân trang
$result = mysqli_query($connection, "SELECT * FROM `list_record_domain` WHERE `id_domain` = '".$toz_mien['id']."' ORDER BY id LIMIT $limit OFFSET $offset");
?>

<div class="row">
    <div class="col-md-12">
        <h3 class="text-24 fw-bold text-dark-300 mb-2">Quản lý record</h3>
        <div class="overflow-x-auto">
            <div class="w-100">
                <table class="w-100 dashboard-table table text-nowrap">
                    <thead class="pb-3">
                        <tr>
                            <th class="py-2 px-4">STT</th>
                            <th class="py-2 px-4">Type</th>
                            <th class="py-2 px-4">Name</th>
                            <th class="py-2 px-4">Content</th>
                            <th class="py-2 px-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                     
    <?php
    $i = $offset + 1; // Đánh số bắt đầu từ offset
    while ($row1 = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td class="text-sm text-dark"><p class="text-nowrap"><?=$i++;?></p></td>
                            <td class="text-sm text-dark"><p class="text-nowrap"> <?=$row1['type'];?> </p></td>
                            <td class="text-sm text-dark"><p class="text-nowrap"> <?=$row1['name'];?> </p></td>
                            <td class="text-sm text-dark"><p class="text-nowrap"> <?=$row1['content'];?> </p></td>
                            <td class="text-sm text-dark">
                        <button onclick="location.href='/edit-record/<?=$row1['id'];?>';" class="btn btn-dark btn-sm">
                            <i class="bx bx-cog mr-1"></i>Quản lý
                        </button>
                    </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- PHÂN TRANG -->
            <div class="d-flex justify-content-center mt-3">
                <nav>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">«</a></li>
                        <?php endif; ?>

                        <?php
                        $maxPagesToShow = 5;
                        $startPage = max(1, $page - 2);
                        $endPage = min($total_pages, $startPage + $maxPagesToShow - 1);
                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                            if ($startPage > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        for ($p = $startPage; $p <= $endPage; $p++) {
                            $active = ($p == $page) ? 'active' : '';
                            echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $p . '">' . $p . '</a></li>';
                        }
                        if ($endPage < $total_pages) {
                            if ($endPage < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">»</a></li>
                        <?php endif; ?>

</section>
</main>

    <?php require __DIR__.'/../../hethong/foot.php';?>
</body>

</html>
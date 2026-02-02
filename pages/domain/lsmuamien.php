<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <?php require __DIR__.'/../../hethong/head2.php';?>
    <title>Lịch Sử Mua Domain | <?=$chungapi['ten_web'];?></title>
    <?php require __DIR__.'/../../hethong/nav.php';?>
</head>

    <main>
        <section class="py-110">
            <div class="container">
    <?php require __DIR__ . '/../../hethong/settings_head.php'; ?>
                <?php
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Đếm tổng bản ghi
$total_query = mysqli_query($connection, "SELECT COUNT(*) AS total FROM `history_domain` WHERE `username` = '$username'");
$total_row = mysqli_fetch_assoc($total_query);
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Lấy dữ liệu phân trang
$result = mysqli_query($connection, "SELECT * FROM `history_domain` WHERE `username` = '$username' ORDER BY `id` DESC LIMIT $start, $limit");
$i = $start + 1;
?>
<div class="row">
    <div class="col-md-12">
        <h3 class="text-24 fw-bold text-dark-300 mb-2">LỊCH SỬ MUA MIỀN</h3>
        <div class="overflow-x-auto">
            <div class="w-100">
                <table class="w-100 dashboard-table table text-nowrap">
                    <thead class="pb-3">
                        <tr>
                            <th class="py-2 px-4">STT</th>
                            <th class="py-2 px-4">Tên miền</th>
                            <th class="py-2 px-4">Chu kỳ</th>
                            <th class="py-2 px-4">Tổng tiền</th>
                            <th class="py-2 px-4">Namesever</th>
                            <th class="py-2 px-4">Trạng thái</th>
                            <th class="py-2 px-4">Ngày mua</th>
                            <th class="py-2 px-4">Ngày hết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $result = mysqli_query($connection,"SELECT * FROM `history_domain` WHERE `username` = '$username' ORDER BY `id` DESC");
                        while($row = mysqli_fetch_assoc($result)) {
                            $mien = $connection->query("SELECT * FROM `ds_domain` WHERE `duoimien` = '".$row['duoimien']."'")->fetch_array();
                        ?>
                        <tr>
                            <td class="text-sm text-dark"><p class="text-nowrap"><?=$i++;?></p></td>
                            <td class="text-sm text-dark"><p class="text-nowrap"> <?= htmlspecialchars($row['domain']); ?> </p></td>
                            <td class="text-sm text-dark"><p class="text-nowrap"> 1 năm / 1 lần </p></td>
                            <td class="text-sm text-dark"><p class="text-nowrap"> <?= tien($mien['gia']); ?>đ </p></td>
                            <td class="text-sm text-dark"><p class="text-nowrap"> <?=$row['nameserver'];?> </p></td>
                            <td class="text-sm text-dark"><p class="text-nowrap"> <?= status($row['status']); ?> </p></td>
                            <td class="text-sm text-dark"><p class="text-nowrap"> <?= ngay($row['ngay_mua']); ?> </p></td>
                            <td class="text-sm text-dark"><p class="text-nowrap"> <?= ngay($row['ngay_het']); ?> </p></td>
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
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>
        </section>
    </main>
    <?php require __DIR__.'/../../hethong/foot.php';?>
</body>
</html>
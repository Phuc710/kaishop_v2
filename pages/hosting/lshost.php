<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <?php require __DIR__.'/../../hethong/head2.php';?>
    <title>Lịch Sử Mua Hosting | <?=$chungapi['ten_web'];?></title>
    <?php require __DIR__.'/../../hethong/nav.php';?>
</head>
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
                    $(document).ready(function() {
                        var url = window.location.pathname;
                        var urlRegExp = new RegExp(url.replace(/\/$/, '') + "$");
                        $('.menu-item').each(function() {
                            if (urlRegExp.test(this.href.replace(/\/$/, ''))) {
                                $(this).addClass('active');
                            }
                        });
                    });
                </script>
                <div class="row">
                    <div class="col-md-12">
                        <form method="GET" action="" class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control shadow-none col-sm-2 mb-2" name="domain" value="" type="text" placeholder="Tên miền">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control shadow-none col-sm-2 mb-2" name="ip" value="" type="text" placeholder="IP">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <select class="custom-style-select nice-select select-dropdown" id="select_status_vps" name="select_status_vps">
                                    <option value="" selected="selected">Chọn trạng thái</option>
                                    <option value="on">Trạng thái đang bật</option>
                                    <option value="off">Trạng thái đã tắt</option>
                                    <option value="progressing">Trạng thái đang tạo</option>
                                    <option value="waiting">Trạng thái đang chờ tạo</option>
                                    <option value="rebuild">Trạng thái đang cài lại</option>
                                    <option value="expire">Trạng thái hết hạn</option>
                                    <option value="suspend">Trạng thái đã khóa</option>
                                    <option value="delete_vps">Trạng thái đã xóa</option>
                                    <option value="cancel">Trạng thái đã hủy</option>
                                </select>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input type="text" class="form-control shadow-none mb-2" name="purchase_date" id="purchase_date" type="text" value="" placeholder="Chọn khoảng thời gian">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <button class="shop-widget-btn mb-2"><i class="fas fa-search"></i><span>Tìm kiếm</span></button>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="/user/history/hosting" class="shop-widget-btn mb-2"><i class="far fa-trash-alt"></i><span>Bỏ lọc</span></a>
                            </div>
                        </form>
                        
<?php
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Đếm tổng số bản ghi
$total_query = mysqli_query($ketnoi, "SELECT COUNT(*) AS total FROM `lich_su_mua_host` WHERE `username` = '$username'");
$total_row = mysqli_fetch_assoc($total_query);
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Truy vấn dữ liệu theo phân trang
$result = mysqli_query($ketnoi, "SELECT * FROM `lich_su_mua_host` WHERE `username` = '$username' ORDER BY id DESC LIMIT $start, $limit");
$i = $start + 1;
?>

<style>
.pagination {
    display: flex;
    list-style: none;
    padding-left: 0;
}
.page-item {
    margin: 0 4px;
}
.page-link {
    padding: 6px 12px;
    border: 1px solid #dee2e6;
    color: #007bff;
    text-decoration: none;
    border-radius: 4px;
}
.page-item.active .page-link {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}
</style>

<div class="overflow-x-auto">
    <div class="w-100">
        <table class="w-100 dashboard-table text-nowrap table">
            <thead class="pb-3">
                <tr>
                    <th class="py-2 px-4">STT</th>
                    <th class="py-2 px-4">Tên miền</th>
                    <th class="py-2 px-4">Giá</th>
                    <th class="py-2 px-4">Gói</th>
                    <th class="py-2 px-4">Thời gian mua</th>
                    <th class="py-2 px-4">Thời gian hết</th>
                    <th class="py-2 px-4">Trạng thái</th>
                    <th class="py-2 px-4">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row1 = mysqli_fetch_assoc($result)) { ?>
                <tr onchange="updateForm('<?= $row1['id'] ?>')">
                    <td class="text-sm text-dark"><p class="text-nowrap"><?= $i++ ?></p></td>
                    <td class="text-sm text-dark"><p class="text-nowrap"><?= $row1['domain']; ?></p></td>
                    <td class="text-sm text-dark"><p class="text-nowrap"><?= tien($row1['gia_host']); ?>đ</p></td>
                    <td class="text-sm text-dark"><p class="text-nowrap"><?= $row1['goi_host']; ?></p></td>
                    <td class="text-sm text-dark"><p class="text-nowrap"><?= date('d-m-Y H:i', $row1['ngay_mua']); ?></p></td>
                    <td class="text-sm text-dark"><p class="text-nowrap"><?= date('d-m-Y H:i', $row1['ngay_mua']); ?></p></td>
                    <td class="text-sm text-dark"><p class="text-nowrap"><?= host($row1['status']); ?></p></td>
                    <td class="text-nowrap">
                        <button onclick="location.href='/quan-ly-host/<?= $row1['id']; ?>';" class="btn btn-dark btn-sm">
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
            </ul>
        </nav>
    </div>
</div>
        </section>
    </main>
<?php require __DIR__.'/../../hethong/foot.php';?>
</html>
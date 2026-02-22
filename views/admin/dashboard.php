<?php
/**
 * View: Dashboard
 * Route: GET /admin
 * Controller: DashboardController@index
 */
$pageTitle = 'Dashboard';
$breadcrumbs = [
    ['label' => 'Dashboard'],
];
require_once __DIR__ . '/layout/head.php';
require_once __DIR__ . '/layout/breadcrumb.php';
?>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>
                            <?= number_format($totalMoney ?? 0) ?>
                        </h3>
                        <p>Tổng Số Dư</p>
                    </div>
                    <div class="icon"><i class="ion ion-stats-bars"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>
                            <?= $totalUsers ?? 0 ?>
                        </h3>
                        <p>Tổng Thành Viên</p>
                    </div>
                    <div class="icon"><i class="ion ion-person-add"></i></div>
                    <a href="<?= url('admin/users') ?>" class="small-box-footer">
                        Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>
                            <?= $totalBanned ?? 0 ?>
                        </h3>
                        <p>Thành Viên Bị Khóa</p>
                    </div>
                    <div class="icon"><i class="ion ion-pie-graph"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>
                            <?= $totalProducts ?? 0 ?>
                        </h3>
                        <p>Tổng Sản Phẩm</p>
                    </div>
                    <div class="icon"><i class="ion ion-bag"></i></div>
                    <a href="<?= url('admin/products') ?>" class="small-box-footer">
                        Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/foot.php'; ?>
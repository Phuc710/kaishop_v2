<?php
/**
 * View: Thêm sản phẩm
 * Route: GET /admin/products/add
 * Controller: AdminProductController@add
 */
$pageTitle = 'Thêm sản phẩm';
$breadcrumbs = [
    ['label' => 'Sản phẩm', 'url' => url('admin/products')],
    ['label' => 'Thêm sản phẩm'],
];
$adminNeedsSummernote = true;
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<section class="content pb-4 mt-3">
    <div class="container-fluid">
        <div class="card custom-card">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-uppercase mb-0">THÊM SẢN PHẨM MỚI</h3>
            </div>

            <form action="<?= url('admin/products/add') ?>" method="POST" id="productForm">
                <?php require_once __DIR__ . '/_form.php'; ?>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
<?php include ROOT_PATH . '/admin/image-manager-modal.php'; ?>
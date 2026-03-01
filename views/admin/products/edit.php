<?php
/**
 * View: Sửa sản phẩm
 * Route: GET /admin/products/edit/{id}
 * Controller: AdminProductController@edit
 */
$pageTitle = 'Sửa sản phẩm';
$breadcrumbs = [
    ['label' => 'Sản phẩm', 'url' => url('admin/products')],
    ['label' => 'Sửa sản phẩm'],
];
$adminNeedsSummernote = true;
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';

$galleryArr = $product['gallery_arr'] ?? [];
$productType = $product['product_type'] ?? 'account';
?>

<section class="content pb-4 mt-3">
    <div class="container-fluid">
        <div class="card custom-card">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-uppercase mb-0">
                    CẬP NHẬT: <span class="text-primary"><?= htmlspecialchars($product['name']) ?></span>
                </h3>
            </div>

            <form action="<?= url('admin/products/edit/' . $product['id']) ?>" method="POST" id="productForm">
                <?php require_once __DIR__ . '/_form.php'; ?>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
<?php include ROOT_PATH . '/admin/image-manager-modal.php'; ?>
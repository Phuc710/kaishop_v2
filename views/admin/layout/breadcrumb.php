<?php
/**
 * Admin Breadcrumb Component
 * 
 * Sử dụng: truyền biến $breadcrumbs trước khi include
 * Format: $breadcrumbs = [
 *     ['label' => 'Sản phẩm', 'url' => url('admin/products')],
 *     ['label' => 'Thêm sản phẩm']   // item cuối không cần url
 * ];
 * Và $pageTitle = 'Thêm sản phẩm';
 */
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>
                    <?= htmlspecialchars($pageTitle ?? '') ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <?php if (!empty($breadcrumbs)): ?>
                        <?php foreach ($breadcrumbs as $i => $crumb): ?>
                            <?php if ($i < count($breadcrumbs) - 1): ?>
                                <li class="breadcrumb-item">
                                    <a href="<?= $crumb['url'] ?? '#' ?>">
                                        <?= htmlspecialchars($crumb['label']) ?>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="breadcrumb-item active">
                                    <?= htmlspecialchars($crumb['label']) ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ol>
            </div>
        </div>
    </div>
</section>
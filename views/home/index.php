<!DOCTYPE html>
<html lang="en">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title> Trang Chủ | <?= $chungapi['ten_web']; ?></title>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
</head>

<body>
    <main>
        <div class="breadcrumb-bar breadcrumb-bar-info">
            <div class="breadcrumb-img">
                <div class="breadcrumb-left">
                    <img src="<?= asset('assets/images/banner-bg-03.png') ?>" alt="img">
                </div>
            </div>
            <div class="container">
                <div class="row mt-3">
                    <div class="col-md-12 col-12">

                        <div class="slide-title-wrap">
                            <div class="row align-items-center">
                                <div class="col-md-12">
                                    <div class="slider-title">
                                        <h2>Các sản phẩm & dịch vụ</h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ds-product-container">
                            <!-- Dynamic Products Grouped by Category -->
                            <?php if (!empty($categories) && !empty($productsByCategory)): ?>
                                <?php foreach ($categories as $category): ?>
                                    <?php if (!empty($productsByCategory[$category['id']])): ?>
                                        <div class="slide-title-wrap mt-5">
                                            <div class="row align-items-center">
                                                <div class="col-md-12">
                                                    <div class="slider-title">
                                                        <h2><?= htmlspecialchars($category['name']) ?></h2>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="ds-product-grid">
                                            <?php foreach ($productsByCategory[$category['id']] as $product): ?>
                                                <?php
                                                $is_offline = $product['status'] !== 'ON';
                                                $discount = 0;
                                                if ($product['old_price'] > $product['price']) {
                                                    $discount = round((($product['old_price'] - $product['price']) / $product['old_price']) * 100);
                                                }
                                                $badge_class = '';
                                                if (stripos($product['name'], 'Premium') !== false)
                                                    $badge_class = 'premium';
                                                if (stripos($product['name'], 'Pro') !== false)
                                                    $badge_class = 'pro';
                                                ?>
                                                <div class="ds-card <?= $is_offline ? 'offline' : '' ?>">
                                                    <div class="ds-card-img-wrap">
                                                        <a href="<?= url('product/' . $product['id']) ?>">
                                                            <img src="<?= $product['image'] ?>" class="ds-card-img"
                                                                alt="<?= $product['name'] ?>">
                                                        </a>
                                                        <?php if ($badge_class): ?>
                                                            <div class="ds-badge <?= $badge_class ?>"><?= strtoupper($badge_class) ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($is_offline): ?>
                                                            <div class="ds-status-badge">Hết hàng</div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="ds-card-body">
                                                        <div class="ds-card-title">
                                                            <a
                                                                href="<?= url('product/' . $product['id']) ?>"><?= $product['name'] ?></a>
                                                        </div>
                                                        <div class="ds-price-row">
                                                            <div class="ds-price"><?= number_format($product['price']) ?>đ</div>
                                                            <?php if ($product['old_price'] > 0): ?>
                                                                <div class="ds-old-price"><?= number_format($product['old_price']) ?>đ</div>
                                                                <div class="ds-discount">-<?= $discount ?>%</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 mt-4">
                                    <p class="text-center">Chưa có sản phẩm nào.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>
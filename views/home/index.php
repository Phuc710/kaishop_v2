<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $GLOBALS['pageAssets'] = array_merge($GLOBALS['pageAssets'] ?? [], [
        'vendor_quill' => false,
        'vendor_isotope' => false,
        'vendor_glightbox' => false,
        'vendor_swiper' => false,
        'vendor_aos' => false,
    ]);
    ?>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title> Trang Chủ | <?= $chungapi['ten_web']; ?></title>
</head>

<body> <?php require __DIR__ . '/../../hethong/nav.php'; ?>
    <main class="pb-5">
        <div class="container py-4 home-main-content">
            <!-- Premium Hero Banner -->
            <div class="home-hero-banner mb-5">
                <div class="hero-content">
                    <h1>Khám phá Kho <span class="text-warning">Mã Nguồn</span> & Dịch Vụ Số</h1>
                    <p>Giải pháp công nghệ chuyên nghiệp cho doanh nghiệp và cá nhân. Cam kết chất lượng, bảo hành 24/7.
                    </p>
                </div>
            </div>

            <!-- Smart Category Navigation -->
            <div class="section-title-row mb-3 mt-5">
                <h5 class="fw-bold"><i class="fas fa-th-large me-2 text-primary"></i> Danh mục sản phẩm</h5>
            </div>
            <div class="category-nav-container">
                <div class="category-nav-scroll">
                    <a href="#" class="category-pill active">
                        <i class="fas fa-border-all"></i> Tất cả
                    </a>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <a href="#cat-<?= $cat['id'] ?>" class="category-pill">
                                <i class="fas fa-tags"></i> <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products List -->
            <div class="ds-product-container mt-4">
                <?php if (!empty($categories) && !empty($productsByCategory)): ?>
                    <?php foreach ($categories as $category): ?>
                        <?php if (!empty($productsByCategory[$category['id']])): ?>
                            <div id="cat-<?= $category['id'] ?>" class="ds-section-header mt-5 mb-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h3 class="ds-category-title mb-0"><?= htmlspecialchars($category['name']) ?></h3>
                                    <a href="<?= url('category/' . ($category['slug'] ?? xoadau($category['name']))) ?>"
                                        class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        Xem tất cả <i class="fas fa-chevron-right ms-1"></i>
                                    </a>
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

                                    // Visual Tags
                                    $badge = '';
                                    if (stripos($product['name'], 'Premium') !== false)
                                        $badge = 'premium';
                                    if (stripos($product['name'], 'Pro') !== false)
                                        $badge = 'pro';
                                    if (!empty($product['badge_text']))
                                        $badge_text = $product['badge_text'];
                                    else
                                        $badge_text = $badge ? ucfirst($badge) : '';
                                    ?>
                                    <a href="<?= url($product['public_path'] ?? ('product/' . $product['id'])) ?>"
                                        class="ds-card <?= $is_offline ? 'offline' : '' ?>">
                                        <div class="ds-card-img-wrap">
                                            <img src="<?= $product['image'] ?>" class="ds-card-img" alt="<?= $product['name'] ?>"
                                                loading="lazy">
                                            <?php if ($badge_text): ?>
                                                <div class="ds-badge <?= $badge ?>"><?= htmlspecialchars($badge_text) ?></div>
                                            <?php endif; ?>
                                            <?php if ($is_offline): ?>
                                                <div class="ds-status-badge">Tạm hết</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ds-card-body">
                                            <h4 class="ds-card-title"><?= htmlspecialchars($product['name']) ?></h4>
                                            <?php
                                            $stats = $stockStats[$product['id']] ?? ['available' => 0, 'sold' => 0];
                                            $stock_text = ($product['product_type'] === 'link') ? 'Vô hạn' : number_format($stats['available']);
                                            $sold_count = number_format($stats['sold']);
                                            ?>
                                            <div class="ds-stock-row">
                                                <span><i class="fas fa-box me-1"></i> Tồn kho: <strong
                                                        class="text-primary"><?= $stock_text ?></strong></span>
                                                <span><i class="fas fa-shopping-cart me-1"></i> Đã bán: <strong
                                                        class="text-success"><?= $sold_count ?></strong></span>
                                            </div>
                                            <div class="ds-price-row">
                                                <div class="ds-price"><?= number_format($product['price_vnd']) ?>đ</div>
                                                <?php if ($discount > 0): ?>
                                                    <div class="ds-old-price"><?= number_format($product['old_price']) ?>đ</div>
                                                    <div class="ds-discount">-<?= $discount ?>%</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="opacity-50 mb-3"><i class="fas fa-box-open fa-4x"></i></div>
                        <h5>Chưa có sản phẩm nào.</h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pills = document.querySelectorAll('.category-pill');

            // Handle Active State on Click
            pills.forEach(pill => {
                pill.addEventListener('click', function () {
                    pills.forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Smooth scroll offset adjustment if needed
            window.addEventListener('hashchange', function () {
                const targetId = window.location.hash;
                if (targetId) {
                    const el = document.querySelector(targetId);
                    if (el) {
                        window.scrollTo({
                            top: el.offsetTop - 100, // Offset for navbar
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    </script>
</body>

</html>

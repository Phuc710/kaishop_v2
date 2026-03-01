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
    <style>
        .ds-category-title {
            width: 100% !important;
            background-color: #0d6efd !important;
            color: #fff !important;
            padding: 10px 16px;
            border-radius: 6px;
            text-align: center;
        }

        .category-section-wrapper.d-none {
            display: none !important;
        }
        .category-title {
            text-transform: capitalize;
            position: relative;
            width: fit-content;
        }
        .category-title::before {
            content: "";
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 80%;
            height: 3px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }
        :root {
            --primary-color: #F97316;
        }
    </style>
    <!-- r3 -->
    <style>
        @media screen and (max-width: 549px) {
            .header-primary {
                position: absolute;
                top: 0;
            }
            .container.py-4.home-main-content {
                margin-top: 30px;
            }
        }
    </style>
</head>

<body> <?php require __DIR__ . '/../../hethong/nav.php'; ?>
    <main class="pb-5 pt-3" style="background: #f5f6f7">
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
                    <?php if (isset($is_category_page) && $is_category_page): ?>
                        <a href="<?= url('') ?>" class="kai-pill">
                            <i class="fas fa-border-all"></i> Tất cả
                        </a>
                    <?php else: ?>
                        <a href="#" class="kai-pill active" data-filter="all">
                            <i class="fas fa-border-all"></i> Tất cả
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <a href="#cat-<?= $cat['id'] ?>"
                                class="kai-pill <?= (isset($is_category_page) && $is_category_page) ? 'active' : '' ?>"
                                data-filter="cat-wrap-<?= $cat['id'] ?>">
                                <?= htmlspecialchars($cat['name']) ?>
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
                            <div class="category-section-wrapper" id="cat-wrap-<?= $category['id'] ?>">
                                <div id="cat-<?= $category['id'] ?>" class="ds-section-header  mb-4">
                                    <div class="category-title">
                                        <h3 class=" mb-0"><?= htmlspecialchars($category['name']) ?></h3>
                                    </div>
                                </div>

                                <div class="ds-product-grid">
                                    <?php foreach ($productsByCategory[$category['id']] as $product): ?>
                                        <?php
                                        $stats = $stockStats[$product['id']] ?? ['available' => 0, 'sold' => 0];
                                        $isStockManaged = !empty($product['stock_managed']);
                                        $availableCount = (int) ($stats['available'] ?? 0);
                                        $isOutOfStock = $isStockManaged && $availableCount <= 0;
                                        $is_offline = $product['status'] !== 'ON' || $isOutOfStock;
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
                                            class="kai-card <?= $is_offline ? 'offline' : '' ?>">
                                            <div class="kai-card-img-wrap">
                                                <img src="<?= $product['image'] ?>" class="kai-card-img" alt="<?= $product['name'] ?>"
                                                    loading="lazy" fetchpriority="low">
                                                <?php if ($badge_text): ?>
                                                    <div class="kai-badge <?= $badge ?>"><?= htmlspecialchars($badge_text) ?></div>
                                                <?php endif; ?>
                                                <?php if ($is_offline): ?>
                                                    <div class="kai-status-badge">Tạm hết</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="kai-card-body">
                                                <h4 class="kai-card-title"><?= htmlspecialchars($product['name']) ?></h4>
                                                <?php
                                                $delivery_mode = $product['delivery_mode'] ?? 'account_stock';
                                                $sold_count = number_format($stats['sold']);
                                                ?>
                                                <div class="kai-stock-row">
                                                    <?php if ($delivery_mode === 'source_link'): ?>
                                                        <span><i class="fas fa-box me-1"></i> Stock: <strong>∞</strong></span>
                                                    <?php else: ?>
                                                        <span><i class="fas fa-box me-1"></i> Stock: <strong><?= number_format($availableCount) ?></strong></span>
                                                    <?php endif; ?>
                                                    <span class="sold-info"><i class="fas fa-shopping-cart me-1"></i> Đã bán: <strong><?= $sold_count ?></strong></span>
                                                </div>
                                                <div class="kai-price-row mt-3">
                                                    <div class="kai-price"><?= number_format($product['price_vnd']) ?>đ</div>
                                                    <?php if ($discount > 0): ?>
                                                        <div class="kai-old-price"><?= number_format($product['old_price']) ?>đ</div>
                                                    <?php endif; ?>
                                                    <div class="kai-action-btn"><i class="fa-solid fa-arrow-right"></i></div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
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
            const pills = document.querySelectorAll('.kai-pill');
            const sections = document.querySelectorAll('.category-section-wrapper');

            // Handle Active State on Click
            pills.forEach(pill => {
                pill.addEventListener('click', function (e) {
                    const filter = this.getAttribute('data-filter');

                    if (!filter) return; // Allow normal navigation for non-filtering pills

                    e.preventDefault();
                    pills.forEach(p => p.classList.remove('active'));
                    this.classList.add('active');

                    if (filter === 'all') {
                        sections.forEach(sec => sec.classList.remove('d-none'));
                    } else {
                        sections.forEach(sec => {
                            if (sec.id === filter) {
                                sec.classList.remove('d-none');
                            } else {
                                sec.classList.add('d-none');
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>

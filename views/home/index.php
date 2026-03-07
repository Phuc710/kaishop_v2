<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $seoTitle = "KaiShop - Dịch vụ MMO, Source Code FREE, Nạp tiền tự động 24/7 Giá Rẻ";
    $seoDescription = "KaiShop chuyên cung cấp dịch vụ MMO, mua bán Source Code FREE và nạp tiền tự động 24/7 uy tín. Hệ thống giao dịch nhanh chóng, bảo mật và giá cạnh tranh nhất.";
    $seoKeywords = "mmo, source code free, nạp tiền tự động, nạp tiền 24/7, mua acc game, dịch vụ game giá rẻ, kaishop";

    $GLOBALS['pageAssets'] = array_merge($GLOBALS['pageAssets'] ?? [], [
        'vendor_quill' => false,
        'vendor_isotope' => false,
        'vendor_glightbox' => false,
        'vendor_swiper' => false,
        'vendor_aos' => false,
    ]);
    ?>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>

    <!-- Structured Data (JSON-LD) for SEO -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "KaiShop",
      "url": "<?= url('') ?>",
      "description": "<?= $seoDescription ?>",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?= url('') ?>?s={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "KaiShop",
      "url": "<?= url('') ?>",
      "logo": "<?= $chungapi['logo'] ?? asset('assets/images/logo.png') ?>",
      "contactPoint": {
        "@type": "ContactPoint",
        "contactType": "customer support",
        "url": "https://t.me/kaishop07"
      }
    }
    </script>
    <title> Trang Chủ | <?= $chungapi['ten_web']; ?></title>
    <style>
        .category-section-wrapper.d-none {
            display: none !important;
        }

        .ds-card.search-hidden {
            display: none !important;
        }


        .section-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .home-search-container {
            position: relative;
            max-width: 350px;
            width: 100%;
        }

        .home-search-input {
            width: 100%;
            padding: 11px 15px 11px 45px;
            border-radius: 50px !important;
            border: 1px solid #e2e8f0;
            background: #fff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .home-search-input:focus {
            background: #fff;
            border-color: var(--primary) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            outline: none;
        }

        .home-search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            transition: color 0.3s ease;
            z-index: 5;
        }

        .home-search-input:focus+.home-search-icon {
            color: var(--primary);
        }

        .search-result-info {
            display: none;
            padding: 15px;
            background: rgba(233, 236, 239, 0.4);
            border-radius: 12px;
            margin: 20px 0;
            border-left: 4px solid var(--primary);
        }

        .search-keyword-highlight {
            color: var(--primary);
            font-weight: bolder;
            text-transform: uppercase;
            word-break: break-all;
            overflow-wrap: break-word;
            max-width: 100%;
        }
    </style>
</head>

<body> <?php require __DIR__ . '/../../hethong/nav.php'; ?>
    <main>
        <div class="container py-4 home-main-content">
            <!-- Visually Hidden H1 for SEO -->
            <h1 class="visually-hidden">KaiShop - Dịch vụ MMO, Source Code FREE, Nạp tiền tự động 24/7 Giá Rẻ</h1>

            <!-- Premium Hero Banner -->
            <?php if (!empty($chungapi['home_hero_html'])): ?>
                <?= $chungapi['home_hero_html'] ?>
            <?php else: ?>
                <div class="home-hero-banner mb-5">
                    <div class="hero-content">
                        <div class="hero-header-flex">
                            <img src="https://media.giphy.com/media/0fnrt8FDzQBO8RSP9q/giphy.gif" alt="Thông báo"
                                class="hero-notice-gif ks-img-guard" loading="lazy" decoding="async" draggable="false">
                            <h1>Thông Báo Quan Trọng :</h1>
                        </div>
                        <div class="hero-disclaimer">
                            <p><strong>【Tuyên bố miễn trừ trách nhiệm】</strong></p>

                            <p>✅ Các sản phẩm được bán trên website này chỉ dùng cho mục đích hợp pháp như: giải trí game,
                                giao thương ngoại thương, marketing online.</p>

                            <p>⛔ <strong style="color: #ff4d4d;">Nghiêm cấm</strong> sử dụng cho
                                các mục đích bất hợp pháp, lừa đảo, gian lận hoặc
                                vi phạm pháp luật.</p>

                            <p>🔒 Nếu phát hiện người dùng sử dụng sản phẩm cho mục đích bất hợp pháp, hệ thống sẽ
                                <strong style="color: #ff4d4d; text-decoration: underline;">khóa tài khoản</strong> và
                                <strong style="color: #ff4d4d; text-decoration: underline;">từ chối hỗ trợ</strong>!
                            </p>

                            <?php
                            $heroBotName = '';
                            $heroBotUsername = '';
                            if (!empty($botInfo['ok'])) {
                                $heroBotName = $botInfo['result']['first_name'] ?? 'Bot';
                                $heroBotUsername = $botInfo['result']['username'] ?? '';
                            }
                            ?>

                            <!-- Quick Links Row -->
                            <div class="hero-links-row d-flex flex-wrap align-items-center mb-3 mt-4"
                                style="gap: 1.5rem; font-size: 1.1rem; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">
                                <?php if ($heroBotUsername !== ''): ?>
                                    <div class="hero-link-item text-white fw-bold" style="font-weight: 700;">
                                        🤖
                                        <a href="https://t.me/<?= htmlspecialchars($heroBotUsername) ?>" target="_blank"
                                            rel="noopener" style="color: #ffc107; text-decoration: none; font-weight: 800;">
                                            BOT Telegram Auto
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <div class="hero-link-item text-white fw-bold" style="font-weight: 700;">
                                    <a href="https://tmail.kaishop.id.vn" target="_blank" rel="noopener"
                                        style="color: #0dcaf0; text-decoration: none; font-weight: 800;">
                                        📩 KaiMail OTP
                                    </a>
                                </div>

                                <div class="hero-link-item">
                                    <a href="<?= htmlspecialchars($chungapi['tele_admin'] ?? '#') ?>" target="_blank"
                                        rel="noopener" style="color: #4facfe; text-decoration: none; font-weight: 800;">
                                        📢 Official Channel
                                    </a>
                                </div>
                            </div>

                            <div class="support-section text-white mt-3"
                                style="font-size: 1.1rem; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">
                                <p class="mb-1 fw-bold" style="color: #fff; font-weight: 700;">👑 Support:</p>
                                <?php
                                $supportTele = $chungapi['support_tele'] ?? '';
                                $supportTeleUser = '';
                                if (preg_match('/t\.me\/([^\/\s\?]+)/', $supportTele, $matches)) {
                                    $supportTeleUser = '@' . $matches[1];
                                } elseif (strpos($supportTele, '@') === 0) {
                                    $supportTeleUser = $supportTele;
                                    $supportTele = 'https://t.me/' . ltrim($supportTele, '@');
                                } else {
                                    $supportTeleUser = '@' . $supportTele;
                                    $supportTele = 'https://t.me/' . $supportTele;
                                }
                                ?>
                                <p class="mb-1 ml-3 fw-bold" style="font-weight: 700;">• Telegram:
                                    <a href="<?= htmlspecialchars($supportTele) ?>" target="_blank" rel="noopener"
                                        style="color: #0dcaf0; text-decoration: none; font-weight: 800;">
                                        <?= htmlspecialchars($supportTeleUser) ?>
                                    </a>
                                </p>
                                <p class="mb-0 ml-3 fw-bold" style="font-weight: 700;">• Discord:
                                    <span
                                        style="color: #0e9dff; font-weight: 800;">@<?= htmlspecialchars(ltrim($chungapi['discord_admin'] ?? 'thphuc37', '@')) ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Smart Category Navigation -->
            <div class="section-title-row mb-3 mt-5">
                <h5 class="fw-bold">🛒 Danh mục sản phẩm</h5>
                <div class="home-search-container">
                    <input type="text" id="homeSearchInput" class="home-search-input"
                        placeholder="Tìm kiếm sản phẩm...">
                    <i class="fas fa-search home-search-icon"></i>
                </div>
            </div>
            <div class="category-nav-container">
                <div class="category-nav-scroll">
                    <?php if (isset($is_category_page) && $is_category_page): ?>
                        <a href="<?= url('') ?>" class="category-pill">
                            <i class="fa-solid fa-cart-shopping"></i> <span>Tất cả</span>
                        </a>
                    <?php else: ?>
                        <a href="#" class="category-pill active" data-filter="all">
                            <i class="fa-solid fa-cart-shopping"></i> <span>Tất cả</span>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <a href="#cat-<?= $cat['id'] ?>"
                                class="category-pill <?= (isset($is_category_page) && $is_category_page) ? 'active' : '' ?>"
                                data-filter="cat-wrap-<?= $cat['id'] ?>">
                                <?php if (!empty($cat['icon'])): ?>
                                    <img src="<?= $cat['icon'] ?>" alt="" style="width: 18px; height: 18px; object-fit: contain;">
                                <?php else: ?>
                                    <i class="fas fa-folder"></i>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($cat['name']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($categories) && count($categories) >= 5): ?>
                    <button type="button" class="category-nav-see-all mt-2" id="catSeeAllBtn">
                        <i class="fas fa-th-large"></i>
                        <span id="catSeeAllLabel">Xem tất cả</span>
                        <i class="fas fa-chevron-down" id="catSeeAllChevron"
                            style="font-size:11px; transition: transform 0.3s;"></i>
                    </button>
                <?php endif; ?>
            </div>


            <div id="searchResultInfo" class="search-result-info mt-3">
                <span class="text-muted"><i class="fas fa-search me-2"></i> Sản phẩm liên quan đến từ khóa:</span>
                <span id="searchKeywordLabel" class="search-keyword-highlight ms-1"></span>
            </div>



            <!-- Products List -->
            <div class="ds-product-container mt-4">
                <?php if (!empty($categories) && !empty($productsByCategory)): ?>
                    <?php foreach ($categories as $category): ?>
                        <?php if (!empty($productsByCategory[$category['id']])): ?>
                            <div class="category-section-wrapper" id="cat-wrap-<?= $category['id'] ?>">
                                <div id="cat-<?= $category['id'] ?>" class="ds-section-header mt-5 mb-4">
                                    <div class="d-flex align-items-center">
                                        <h3 class="ds-category-title mb-0">
                                            <?php if (!empty($category['icon'])): ?>
                                                <img src="<?= $category['icon'] ?>" alt=""
                                                    style="width: 28px; height: 28px; object-fit: contain; margin-right: 10px;">
                                            <?php else: ?>
                                                <i class="fas fa-folder me-2"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </h3>
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
                                            class="ds-card <?= $is_offline ? 'offline' : '' ?>">
                                            <div class="ds-card-img-wrap">
                                                <img src="<?= $product['image'] ?>" class="ds-card-img" alt="<?= $product['name'] ?>"
                                                    loading="lazy" decoding="async" fetchpriority="low">
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
                                                $delivery_mode = $product['delivery_mode'] ?? 'account_stock';
                                                $delivery_label = $product['delivery_label'] ?? 'Tài Khoản';

                                                if ($delivery_mode === 'account_stock') {
                                                    $stock_display = '<i class="fas fa-box me-1"></i> Tồn kho: <strong class="text-primary">' . number_format($availableCount) . '</strong>';
                                                } elseif ($delivery_mode === 'manual_info') {
                                                    $stock_display = '<i class="fas fa-bolt me-1"></i> ' . (($availableCount > 0) ? '<strong class="text-warning">Sẵn hàng</strong>' : '<strong class="text-danger">Hết hàng</strong>');
                                                } else {
                                                    $stock_display = '<i class="fas fa-infinity me-1"></i> ' . $delivery_label . ': <strong class="text-info">Unlimited</strong>';
                                                }
                                                $sold_count = number_format($stats['sold']);
                                                ?>
                                                <div class="ds-stock-row">
                                                    <?php if ($delivery_mode === 'source_link'): ?>
                                                        <span><i class="fas fa-box me-1"></i> Stock: <strong
                                                                class="ds-stock-infinity text-primary">∞</strong></span>
                                                    <?php else: ?>
                                                        <span><i class="fas fa-box me-1"></i> Stock: <strong
                                                                class="text-primary"><?= number_format($availableCount) ?></strong></span>
                                                    <?php endif; ?>
                                                    <span><i class="fas fa-shopping-cart me-1"></i> Đã bán: <strong
                                                            class="text-success"><?= $sold_count ?></strong></span>
                                                </div>
                                                <div class="ds-price-row">
                                                    <div class="ds-price" data-price-vnd="<?= (int) $product['price_vnd'] ?>">
                                                        <?= number_format($product['price_vnd']) ?>đ
                                                    </div>
                                                    <?php if ($discount > 0): ?>
                                                        <div class="ds-old-price" data-price-vnd="<?= (int) $product['old_price'] ?>">
                                                            <?= number_format($product['old_price']) ?>đ
                                                        </div>
                                                        <div class="ds-discount">-<?= $discount ?>%</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div id="phpEmptyState" class="text-center py-5">
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
            // ── Category "Xem tất cả" toggle on mobile ──────────────
            const catScroll = document.querySelector('.category-nav-scroll');
            const catSeeAllBtn = document.getElementById('catSeeAllBtn');
            if (catSeeAllBtn && catScroll) {
                catSeeAllBtn.addEventListener('click', function () {
                    const isExpanded = catScroll.classList.toggle('is-expanded');
                    document.getElementById('catSeeAllLabel').textContent = isExpanded ? 'Thu gọn' : 'Xem tất cả';
                    const chevron = document.getElementById('catSeeAllChevron');
                    if (chevron) chevron.style.transform = isExpanded ? 'rotate(180deg)' : 'rotate(0deg)';
                });
            }


            const pills = document.querySelectorAll('.category-pill');
            const sections = document.querySelectorAll('.category-section-wrapper');
            const cards = document.querySelectorAll('.ds-card');
            const searchInput = document.getElementById('homeSearchInput');
            const resultInfo = document.getElementById('searchResultInfo');
            const keywordLabel = document.getElementById('searchKeywordLabel');

            let currentFilter = 'all';
            let currentKeyword = '';

            function performFilter() {
                let anyVisible = false;
                const normalizedKeyword = currentKeyword.toLowerCase().trim();

                // 1. Show/Hide Cards
                cards.forEach(card => {
                    card.classList.remove('animate-in'); // Reset animation
                    const title = card.querySelector('.ds-card-title').textContent.toLowerCase();
                    const matchesKeyword = title.includes(normalizedKeyword);

                    if (matchesKeyword) {
                        card.classList.remove('search-hidden');
                        // Trigger reflow to restart animation if needed
                        void card.offsetWidth;
                        card.classList.add('animate-in');
                    } else {
                        card.classList.add('search-hidden');
                    }
                });

                // 2. Show/Hide Sections (Categories)
                sections.forEach((sec, idx) => {
                    const isTargetCategory = (currentFilter === 'all' || sec.id === currentFilter);
                    const visibleCardsInSection = sec.querySelectorAll('.ds-card:not(.search-hidden)');

                    if (isTargetCategory && visibleCardsInSection.length > 0) {
                        sec.classList.remove('d-none');
                        anyVisible = true;
                    } else {
                        sec.classList.add('d-none');
                    }
                });

                // 3. Update Result Label
                if (currentKeyword.trim().length > 0) {
                    resultInfo.style.display = 'block';
                    keywordLabel.textContent = currentKeyword;
                } else {
                    resultInfo.style.display = 'none';
                }

                // 4. Update Empty State
                const container = document.querySelector('.ds-product-container');
                let existingEmpty = document.getElementById('emptySearchState');
                let phpEmpty = document.getElementById('phpEmptyState');

                if (!anyVisible) {
                    if (phpEmpty) phpEmpty.style.display = 'none'; // Hide PHP empty state if search empty state is needed
                    if (!existingEmpty) {
                        const emptyHTML = `
                            <div id="emptySearchState" class="text-center py-5">
                                <div class="opacity-50 mb-3"><i class="fas fa-search-minus fa-4x"></i></div>
                                <h5>Không tìm thấy sản phẩm phù hợp.</h5>
                                <p class="text-muted">Vui lòng thử từ khóa khác hoặc đổi danh mục.</p>
                            </div>
                        `;
                        container.insertAdjacentHTML('beforeend', emptyHTML);
                    }
                } else {
                    if (existingEmpty) existingEmpty.remove();
                    if (phpEmpty) phpEmpty.style.display = 'block'; // Show PHP empty state if it was original and we are not searching (though anyVisible would be false)
                }

                // If no search keyword, and anyVisible is false, and phpEmpty exists, show phpEmpty and remove searchEmpty
                if (currentKeyword.trim().length === 0 && !anyVisible && phpEmpty) {
                    if (existingEmpty) existingEmpty.remove();
                    phpEmpty.style.display = 'block';
                }

            }

            // Handle Category Click
            pills.forEach(pill => {
                pill.addEventListener('click', function (e) {
                    const filter = this.getAttribute('data-filter');
                    if (!filter) return;
                    e.preventDefault();
                    pills.forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = filter;
                    performFilter();
                });
            });

            // Handle Search typing
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    currentKeyword = this.value;
                    performFilter();
                });
            }

            // Light click animation for product cards.
            const clampPercent = (value) => Math.max(0, Math.min(100, value));

            cards.forEach(card => {
                const animateCardClick = (clientX, clientY) => {
                    const rect = card.getBoundingClientRect();
                    const x = rect.width > 0 ? ((clientX - rect.left) / rect.width) * 100 : 50;
                    const y = rect.height > 0 ? ((clientY - rect.top) / rect.height) * 100 : 50;

                    card.style.setProperty('--ripple-x', clampPercent(x) + '%');
                    card.style.setProperty('--ripple-y', clampPercent(y) + '%');

                    card.classList.remove('is-clicked');
                    requestAnimationFrame(() => card.classList.add('is-clicked'));

                    clearTimeout(card.__clickAnimTimer);
                    card.__clickAnimTimer = setTimeout(() => {
                        card.classList.remove('is-clicked');
                    }, 220);
                };

                card.addEventListener('pointerdown', function (e) {
                    if (e.pointerType === 'mouse' && e.button !== 0) return;
                    animateCardClick(e.clientX, e.clientY);
                }, { passive: true });

                card.addEventListener('keydown', function (e) {
                    if (e.key !== 'Enter') return;
                    const rect = card.getBoundingClientRect();
                    animateCardClick(rect.left + rect.width / 2, rect.top + rect.height / 2);
                });
            });
        });
    </script>
</body>

</html>
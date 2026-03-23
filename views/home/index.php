<!DOCTYPE html>
<html lang="<?= function_exists('app_is_english') && app_is_english() ? 'en' : 'vi' ?>">

<head>
    <?php
    $seoTitle = "KaiShop - Kho tài nguyên & Source Code Uy Tín";
    $seoDescription = "KaiShop chuyên cung cấp dịch vụ MMO, Source Code chất lượng. Hệ thống nạp tiền tự động 24/7 qua Ngân hàng, USDT. Giao dịch uy tín, bảo mật.";
    $seoKeywords = "nạp tiền 24/7, nạp tiền tự động, dịch vụ mmo, mua source code, kaishop, nạp tiền giá rẻ, nạp tiền game";

    $siteName = (string) ($chungapi['ten_web'] ?? 'KaiShop');
    // Default SEO optional variables to prevent notices after removal from controller
    $pageHeading = $pageHeading ?? '';
    $pageIntro = $pageIntro ?? '';
    $pageKicker = $pageKicker ?? '';
    $pageBodyTitle = $pageBodyTitle ?? '';
    $pageBodyText = $pageBodyText ?? '';
    $pageBottomTitle = $pageBottomTitle ?? '';
    $pageBottomText = $pageBottomText ?? '';
    $seoCanonical = $seoCanonical ?? '';

    if (!empty($pageHeading)) {
        $seoTitle = trim((string) $pageHeading);
        if (stripos($seoTitle, $siteName) === false) {
            $seoTitle .= ' | ' . $siteName;
        }
    }
    if (!empty($pageIntro)) {
        $seoDescription = trim((string) $pageIntro);
    }
    if (!empty($selectedCategory['name'])) {
        $seoKeywords .= ', ' . trim((string) $selectedCategory['name']);
    }

    $GLOBALS['pageAssets'] = array_merge($GLOBALS['pageAssets'] ?? [], [
        'vendor_quill' => false,
        'vendor_isotope' => false,
        'vendor_glightbox' => false,
        'vendor_swiper' => false,
        'vendor_aos' => false,
    ]);
    $supportTeleUrl = trim((string) ($chungapi['support_tele'] ?? ''));
    if ($supportTeleUrl === '') {
        $supportTeleUrl = url('lien-he');
    }
    $organizationSameAs = array_values(array_filter([
        (string) ($chungapi['fb_admin'] ?? ''),
        (string) ($chungapi['tele_admin'] ?? ''),
        (string) ($chungapi['support_tele'] ?? ''),
        (string) ($chungapi['tiktok_admin'] ?? ''),
        (string) ($chungapi['youtube_admin'] ?? ''),
    ]));
    $homeFaqItems = [
        [
            'question' => 'KaiShop nạp tiền tự động qua kênh nào? Mất bao lâu?',
            'answer' => 'KaiShop hỗ trợ nạp tiền tự động 24/7 qua chuyển khoản ngân hàng nội địa và Binance Pay (USDT). Hầu hết giao dịch được hệ thống xử lý trong vài giây sau khi ngân hàng/ví xác nhận — không cần chờ admin. Mỗi lệnh nạp có mã đối soát riêng, đảm bảo khớp chính xác và an toàn.',
        ],
        [
            'question' => 'KaiShop có uy tín không? Giao dịch có an toàn không?',
            'answer' => 'KaiShop vận hành minh bạch với lịch sử đơn hàng đầy đủ, hệ thống nạp tiền tự động qua cổng thanh toán xác thực, và kênh hỗ trợ trực tiếp qua Telegram. Thông tin thanh toán rõ ràng, không lưu thẻ ngân hàng. Bạn có thể kiểm tra lịch sử nạp và đơn hàng bất cứ lúc nào trong tài khoản.',
        ],
        [
            'question' => 'Mua hàng xong tôi nhận sản phẩm bằng cách nào?',
            'answer' => 'Với sản phẩm giao tự động (tài khoản số, source code, link tải), hệ thống trả nội dung ngay trong đơn hàng sau khi thanh toán thành công — không cần chờ. Với dịch vụ cần xử lý thủ công hoặc cần thông tin bổ sung từ bạn, admin sẽ tiếp nhận và xử lý theo quy trình đã cấu hình sẵn trên sản phẩm.',
        ],
        [
            'question' => 'KaiShop bán những loại sản phẩm và dịch vụ số nào?',
            'answer' => 'KaiShop tập trung vào nhóm sản phẩm số: tài khoản game và dịch vụ số, source code website/app, công cụ hỗ trợ MMO (marketing online), dịch vụ tự động hoá và các sản phẩm kỹ thuật số giao ngay. Danh mục được chia rõ ràng, hỗ trợ tìm kiếm và lọc theo nhóm để mua nhanh hơn.',
        ],
        [
            'question' => 'Sản phẩm có bảo hành không? Nếu lỗi tôi liên hệ ai?',
            'answer' => 'Chính sách bảo hành áp dụng theo mô tả trên từng sản phẩm cụ thể. Nếu gặp sự cố sau mua, bạn liên hệ hỗ trợ trực tiếp qua Telegram hoặc kênh liên hệ được hiển thị trên trang. Vui lòng cung cấp mã đơn hàng để admin xử lý nhanh nhất có thể.',
        ],
    ];
    $seoSchemaType = !empty($is_category_page) ? 'CollectionPage' : 'WebPage';
    $homeVisibleCategories = !empty($displayCategories) && is_array($displayCategories) ? $displayCategories : ($categories ?? []);
    $homeVisibleProductCount = 0;
    $homeItemListElements = [];
    $itemPosition = 1;
    foreach ((array) $homeVisibleCategories as $schemaCategory) {
        $homeVisibleProductCount += count($productsByCategory[$schemaCategory['id']] ?? []);
    }
    foreach ((array) $homeVisibleCategories as $schemaCategory) {
        $schemaProducts = $productsByCategory[$schemaCategory['id']] ?? [];
        foreach ($schemaProducts as $schemaProduct) {
            if ($itemPosition > 18) {
                break 2;
            }

            $schemaUrl = url($schemaProduct['public_path'] ?? ('product/' . $schemaProduct['id']));
            $homeItemListElements[] = [
                '@type' => 'ListItem',
                'position' => $itemPosition++,
                'url' => $schemaUrl,
                'item' => array_filter([
                    '@type' => 'Product',
                    'name' => (string) ($schemaProduct['name'] ?? ''),
                    'url' => $schemaUrl,
                    'image' => !empty($schemaProduct['image']) ? (string) $schemaProduct['image'] : null,
                ]),
            ];
        }
    }
    $homeFaqSchemaItems = [];
    foreach ($homeFaqItems as $faqItem) {
        $homeFaqSchemaItems[] = [
            '@type' => 'Question',
            'name' => (string) ($faqItem['question'] ?? ''),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => (string) ($faqItem['answer'] ?? ''),
            ],
        ];
    }
    $homeBreadcrumbItems = [];
    if (!empty($is_category_page) && !empty($selectedCategory['name'])) {
        $homeBreadcrumbItems = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => url('')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => (string) $selectedCategory['name'], 'item' => $seoCanonical ?? url('')],
        ];
    }
    ?>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>

    <?php if (false): ?>
    <!-- Structured Data (JSON-LD) for SEO -->
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $chungapi['ten_web'] ?? 'KaiShop',
        'url' => url(''),
        'description' => $seoDescription,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => url('') . '?s={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $chungapi['ten_web'] ?? 'KaiShop',
        'url' => url(''),
        'logo' => $chungapi['logo'] ?? asset('assets/images/logo.png'),
        'description' => $seoDescription,
        'email' => (string) ($chungapi['email_cf'] ?? ''),
        'telephone' => (string) ($chungapi['sdt_admin'] ?? ''),
        'sameAs' => $organizationSameAs,
        'areaServed' => [
            '@type' => 'Country',
            'name' => 'Vietnam',
        ],
        'contactPoint' => [
            array_filter([
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'url' => $supportTeleUrl,
                'email' => (string) ($chungapi['email_cf'] ?? ''),
                'telephone' => (string) ($chungapi['sdt_admin'] ?? ''),
                'availableLanguage' => ['Vietnamese', 'English'],
            ]),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => 'KaiShop nạp tiền tự động qua kênh nào? Mất bao lâu?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'KaiShop hỗ trợ nạp tiền tự động 24/7 qua chuyển khoản ngân hàng nội địa và Binance Pay (USDT). Hầu hết giao dịch được hệ thống xử lý trong vài giây sau khi ngân hàng/ví xác nhận — không cần chờ admin. Mỗi lệnh nạp có mã đối soát riêng, đảm bảo khớp chính xác và an toàn.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'KaiShop có uy tín không? Giao dịch có an toàn không?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'KaiShop vận hành minh bạch với lịch sử đơn hàng đầy đủ, hệ thống nạp tiền tự động qua cổng thanh toán xác thực, và kênh hỗ trợ trực tiếp qua Telegram. Thông tin thanh toán rõ ràng, không lưu thẻ ngân hàng. Bạn có thể kiểm tra lịch sử nạp và đơn hàng bất cứ lúc nào trong tài khoản.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Mua hàng xong tôi nhận sản phẩm bằng cách nào?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Với sản phẩm giao tự động (tài khoản số, source code, link tải), hệ thống trả nội dung ngay trong đơn hàng sau khi thanh toán thành công — không cần chờ. Với dịch vụ thủ công, admin tiếp nhận và xử lý theo quy trình đã cấu hình trên sản phẩm.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'KaiShop bán những loại sản phẩm và dịch vụ số nào?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'KaiShop cung cấp: tài khoản game và dịch vụ số, source code website/app, công cụ hỗ trợ MMO (marketing online), dịch vụ tự động hoá và các sản phẩm kỹ thuật số giao ngay.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Tôi cần tạo tài khoản trước khi mua hàng không?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Bạn cần đăng ký tài khoản để đảm bảo đơn hàng và nội dung sản phẩm được lưu lại đầy đủ. Đăng ký chỉ mất vài giây. Sau khi đăng nhập, bạn có thể nạp tiền, mua hàng và theo dõi toàn bộ lịch sử giao dịch tại một nơi.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Sản phẩm có bảo hành không? Nếu lỗi tôi liên hệ ai?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Chính sách bảo hành áp dụng theo mô tả trên từng sản phẩm. Nếu gặp sự cố, liên hệ hỗ trợ qua Telegram hoặc kênh liên hệ trên trang, kèm mã đơn hàng để được xử lý nhanh nhất.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'KaiShop có hỗ trợ hoàn tiền không?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Chính sách hoàn tiền áp dụng theo từng sản phẩm. Lỗi do hệ thống hoặc sản phẩm không đúng mô tả sẽ được xem xét hoàn tiền/đổi hàng. Trường hợp mua nhầm hoặc đổi ý sau khi nhận nội dung thường không thuộc diện hoàn tiền. Chi tiết xem tại trang Điều khoản.',
                ],
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <?php endif; ?>
    <?php if (!empty($homeBreadcrumbItems)): ?>
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $homeBreadcrumbItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <?php endif; ?>
    <?php if (!empty($homeItemListElements)): ?>
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => $seoTitle,
        'description' => $seoDescription,
        'numberOfItems' => $homeVisibleProductCount,
        'itemListElement' => $homeItemListElements,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <?php endif; ?>
    <?php if (!empty($homeFaqSchemaItems)): ?>
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $homeFaqSchemaItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <?php endif; ?>
    <title><?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <style>
        .category-section-wrapper.d-none {
            display: none !important;
        }

        .ds-card.search-hidden {
            display: none !important;
        }

        .home-seo-intro {
            margin-bottom: 28px;
            padding: 24px 26px;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(255, 247, 237, 0.98), rgba(255, 255, 255, 0.98));
            border: 1px solid rgba(255, 105, 0, 0.12);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.05);
        }

        .home-seo-breadcrumb {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
            color: #64748b;
            font-size: 0.93rem;
        }

        .home-seo-breadcrumb a {
            color: #ea580c;
            font-weight: 700;
        }

        .home-seo-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 105, 0, 0.1);
            color: #c2410c;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .home-seo-title {
            margin: 16px 0 12px;
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            line-height: 1.15;
            color: #0f172a;
            font-weight: 900;
        }

        .home-seo-lead {
            max-width: 880px;
            margin: 0;
            color: #475569;
            line-height: 1.8;
            font-size: 1rem;
        }

        .home-seo-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .home-seo-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid rgba(255, 105, 0, 0.12);
            color: #334155;
            font-weight: 700;
        }

        .home-copy-block {
            margin-top: 28px;
            padding: 24px 26px;
            border-radius: 22px;
            background: #fff;
            border: 1px solid #eef2f7;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
        }

        .home-copy-block h2 {
            margin: 0 0 12px;
            color: #0f172a;
            font-size: 1.35rem;
            font-weight: 800;
        }

        .home-copy-block p {
            margin: 0;
            color: #475569;
            line-height: 1.85;
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

        .home-faq-section {
            margin-top: 64px;
            padding: 56px 0;
            background: transparent;
            border: 0;
            box-shadow: none;
        }

        .home-faq-shell {
            max-width: 860px;
            margin: 0 auto;
            text-align: center;
        }

        .home-faq-kicker {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 105, 0, 0.1);
            color: #ea580c;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .home-faq-title {
            margin: 14px 0 0;
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
        }

        .home-faq-intro {
            max-width: 700px;
            margin: 14px auto 0;
            color: #475569;
            line-height: 1.75;
        }

        .home-faq-list {
            margin-top: 26px;
            display: grid;
            gap: 14px;
            text-align: left;
        }

        .home-faq-item {
            border-radius: 20px;
            background: #ffffff;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }



        .home-faq-trigger {
            width: 100%;
            border: 0;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 20px 22px;
            text-align: left;
            cursor: pointer;
            color: #0f172a;
        }

        .home-faq-trigger:focus-visible {
            outline: 2px solid rgba(255, 105, 0, 0.35);
            outline-offset: -2px;
        }

        .home-faq-question {
            margin: 0;
            font-size: 1.02rem;
            line-height: 1.55;
            font-weight: 700;
        }

        .home-faq-icon {
            flex: 0 0 auto;
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff7ed;
            color: #ea580c;
            transition: transform 0.28s ease, background-color 0.28s ease, color 0.28s ease;
        }

        .home-faq-item.is-open .home-faq-icon {
            transform: rotate(180deg);
            background: #ea580c;
            color: #fff;
        }

        .home-faq-panel {
            height: 0;
            overflow: hidden;
            transition: height 0.3s ease;
        }

        .home-faq-panel-inner {
            padding: 0 22px 22px;
            color: #475569;
            line-height: 1.8;
        }

        .home-faq-panel-inner p {
            margin: 0;
        }

        @media (max-width: 767.98px) {
            .home-faq-section {
                padding: 20px;
            }

            .home-faq-title {
                font-size: 1.45rem;
            }

            .home-faq-trigger {
                padding: 18px;
            }

            .home-faq-panel-inner {
                padding: 0 18px 18px;
            }
        }
    </style>
</head>

<body> <?php require __DIR__ . '/../../hethong/nav.php'; ?>
    <main>
        <div class="container py-4 home-main-content">
            <!-- Visually Hidden H1 for SEO -->
            <h1 class="visually-hidden">KaiShop - Hệ thống Nạp tiền Tự động 24/7, Dịch vụ MMO & Source Code</h1>

            <!-- Premium Hero Banner -->
            <?php if (!empty($chungapi['home_hero_html'])): ?>
                <?= $chungapi['home_hero_html'] ?>
            <?php else: ?>
                <div class="home-hero-banner mb-5">
                    <div class="hero-content">
                        <div class="hero-header-flex">
                            <img src="https://media.giphy.com/media/0fnrt8FDzQBO8RSP9q/giphy.gif" width="32" height="32" alt="Thông báo"
                                class="hero-notice-gif ks-img-guard" loading="lazy" decoding="async" draggable="false">
                            <h2>Thông Báo Quan Trọng :</h2>
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
                <h2 class="fw-bold h5 mb-0">🛒 Danh mục sản phẩm</h2>
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
                                    <img src="<?= $cat['icon'] ?>" width="18" height="18" alt="" style="width: 18px; height: 18px; object-fit: contain;">
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
                                                <img src="<?= $category['icon'] ?>" width="28" height="28" alt=""
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
                                        $oldPriceHome = isset($product['old_price']) ? (int) $product['old_price'] : 0;
                                        $priceVndHome = isset($product['price_vnd']) ? (int) $product['price_vnd'] : 0;
                                        if ($oldPriceHome > $priceVndHome) {
                                            $discount = round((($oldPriceHome - $priceVndHome) / $oldPriceHome) * 100);
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
                                                <img src="<?= $product['image'] ?>" width="400" height="400" class="ds-card-img" alt="<?= $product['name'] ?>"
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

            <?php if (!empty($pageBodyTitle) || !empty($pageBodyText)): ?>
                <section class="home-copy-block" aria-labelledby="homeBodyCopyHeading">
                    <?php if (!empty($pageBodyTitle)): ?>
                        <h2 id="homeBodyCopyHeading"><?= htmlspecialchars((string) $pageBodyTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($pageBodyText)): ?>
                        <p><?= htmlspecialchars((string) $pageBodyText, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="home-faq-section" aria-labelledby="homeFaqHeading">
                <div class="home-faq-shell">
                    <h2 id="homeFaqHeading" class="home-faq-title">Câu hỏi thường gặp về KaiShop</h2>
                    <div class="home-faq-list" data-faq-accordion>
                        <?php foreach ($homeFaqItems as $index => $faqItem): ?>
                            <?php $faqPanelId = 'home-faq-panel-' . (int) $index; ?>
                            <article class="home-faq-item">
                                <button type="button" class="home-faq-trigger" aria-expanded="false"
                                    aria-controls="<?= $faqPanelId ?>">
                                    <span class="home-faq-question"><?= htmlspecialchars($faqItem['question']) ?></span>
                                    <span class="home-faq-icon" aria-hidden="true"><i
                                            class="fas fa-chevron-down"></i></span>
                                </button>
                                <div id="<?= $faqPanelId ?>" class="home-faq-panel" hidden>
                                    <div class="home-faq-panel-inner">
                                        <p><?= htmlspecialchars($faqItem['answer']) ?></p>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <?php if (!empty($pageBottomTitle) || !empty($pageBottomText)): ?>
                <section class="home-copy-block" aria-labelledby="homeBottomCopyHeading">
                    <?php if (!empty($pageBottomTitle)): ?>
                        <h2 id="homeBottomCopyHeading"><?= htmlspecialchars((string) $pageBottomTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($pageBottomText)): ?>
                        <p><?= htmlspecialchars((string) $pageBottomText, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
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

            const faqAccordion = document.querySelector('[data-faq-accordion]');
            if (faqAccordion) {
                const faqItems = Array.from(faqAccordion.querySelectorAll('.home-faq-item'));

                const collapsePanel = (item) => {
                    const trigger = item.querySelector('.home-faq-trigger');
                    const panel = item.querySelector('.home-faq-panel');
                    if (!trigger || !panel || !item.classList.contains('is-open')) return;

                    panel.style.height = panel.scrollHeight + 'px';
                    requestAnimationFrame(() => {
                        panel.style.height = '0px';
                    });

                    trigger.setAttribute('aria-expanded', 'false');
                    item.classList.remove('is-open');

                    const onCollapseEnd = (event) => {
                        if (event.propertyName !== 'height') return;
                        panel.hidden = true;
                        panel.style.height = '';
                        panel.removeEventListener('transitionend', onCollapseEnd);
                    };
                    panel.addEventListener('transitionend', onCollapseEnd);
                };

                const expandPanel = (item) => {
                    const trigger = item.querySelector('.home-faq-trigger');
                    const panel = item.querySelector('.home-faq-panel');
                    if (!trigger || !panel) return;

                    panel.hidden = false;
                    const targetHeight = panel.scrollHeight;
                    panel.style.height = '0px';
                    requestAnimationFrame(() => {
                        item.classList.add('is-open');
                        trigger.setAttribute('aria-expanded', 'true');
                        panel.style.height = targetHeight + 'px';
                    });

                    const onExpandEnd = (event) => {
                        if (event.propertyName !== 'height') return;
                        panel.style.height = 'auto';
                        panel.removeEventListener('transitionend', onExpandEnd);
                    };
                    panel.addEventListener('transitionend', onExpandEnd);
                };

                faqItems.forEach((item, index) => {
                    const trigger = item.querySelector('.home-faq-trigger');
                    const panel = item.querySelector('.home-faq-panel');
                    if (!trigger || !panel) return;

                    if (item.classList.contains('is-open')) {
                        panel.hidden = false;
                        panel.style.height = 'auto';
                        trigger.setAttribute('aria-expanded', 'true');
                    } else {
                        panel.hidden = true;
                        panel.style.height = '';
                        trigger.setAttribute('aria-expanded', 'false');
                    }

                    trigger.addEventListener('click', function () {
                        const isOpen = item.classList.contains('is-open');
                        faqItems.forEach(otherItem => {
                            if (otherItem !== item) {
                                collapsePanel(otherItem);
                            }
                        });

                        if (isOpen) {
                            collapsePanel(item);
                        } else {
                            expandPanel(item);
                        }
                    });
                });
            }
        });
    </script>
</body>

</html>

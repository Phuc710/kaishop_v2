<?php
$productName = (string) ($product['name'] ?? 'Sản phẩm');
$productId = (int) ($product['id'] ?? 0);
$priceVnd = max(0, (int) ($product['price_vnd'] ?? 0));
$purchaseMinQty = max(1, (int) ($product['min_purchase_qty'] ?? 1));
$purchaseMaxQty = max(0, (int) ($product['max_purchase_qty'] ?? 0));
$requiresInfo = (int) ($product['requires_info'] ?? 0) === 1;
$productType = (string) ($product['product_type'] ?? 'account');
$deliveryMode = (string) ($product['delivery_mode'] ?? 'account_stock');
$stockManaged = !empty($product['stock_managed']);
$infoInstructions = trim((string) ($product['info_instructions'] ?? ''));
$availableStock = isset($product['available_stock']) ? (int) $product['available_stock'] : null;
$teleAdminRaw = trim((string) ($chungapi['tele_admin'] ?? ''));
$teleAdminUrl = '';
$teleAdminLabel = '';
if ($teleAdminRaw !== '') {
    if (preg_match('~^https?://~i', $teleAdminRaw) === 1) {
        $teleAdminUrl = $teleAdminRaw;
        $teleAdminLabel = $teleAdminRaw;
        if (preg_match('~t\\.me/([A-Za-z0-9_]+)~i', $teleAdminRaw, $m) === 1) {
            $teleAdminLabel = '@' . $m[1];
        }
    } else {
        $teleAdminHandle = ltrim($teleAdminRaw, "@/ \t\n\r\0\x0B");
        if ($teleAdminHandle !== '') {
            $teleAdminUrl = 'https://t.me/' . $teleAdminHandle;
            $teleAdminLabel = '@' . $teleAdminHandle;
        }
    }
}
$categoryName = trim((string) ($product['category_name'] ?? ''));
$categorySlug = trim((string) ($product['category_slug'] ?? ''));
$publicPath = (string) ($product['public_path'] ?? ('product/' . $productId));
$publicUrl = (string) ($product['public_url'] ?? url($publicPath));
$thumb = trim((string) ($product['image'] ?? ''));
$gallery = is_array($product['gallery_arr'] ?? null) ? $product['gallery_arr'] : [];

$galleryImages = [];
if ($thumb !== '') {
    $galleryImages[] = $thumb;
}
foreach ($gallery as $img) {
    $img = trim((string) $img);
    if ($img !== '' && !in_array($img, $galleryImages, true)) {
        $galleryImages[] = $img;
    }
}
if (empty($galleryImages)) {
    $galleryImages[] = asset('assets/images/banner-bg-03.png');
}

$displayMaxQty = $purchaseMaxQty;
if ($deliveryMode === 'source_link') {
    $displayMaxQty = 1;
} elseif ($stockManaged && is_int($availableStock)) {
    $displayMaxQty = $purchaseMaxQty > 0 ? min($purchaseMaxQty, $availableStock) : $availableStock;
}

$canPurchase = true;
$stockLabel = 'Unlimited';
$isOutOfStock = false;
if ($stockManaged && is_int($availableStock)) {
    $stockLabel = (string) max(0, $availableStock);
    if ($availableStock <= 0) {
        $canPurchase = false;
        $isOutOfStock = true;
    }
}
$stockColor = '';
if ($stockManaged && is_int($availableStock)) {
    $stockColor = $availableStock > 0 ? '#0f7a2f' : '#dc2626';
}

if ($deliveryMode === 'manual_info') {
    if ($availableStock > 0) {
        $stockLabel = 'Sẵn hàng';
        $stockColor = '#d97706';
    } else {
        $stockLabel = 'Tạm hết';
        $stockColor = '#dc2626';
    }
} elseif ($deliveryMode === 'source_link') {
    $stockLabel = 'Unlimited';
    $stockColor = '#0369a1';
}

if ($displayMaxQty > 0 && $displayMaxQty < $purchaseMinQty) {
    $canPurchase = false;
}

$seoTitle = $productName . ' | ' . (string) ($chungapi['ten_web'] ?? 'KaiShop');
$rawDescHtml = trim((string) ($product['description'] ?? ''));
$rawDesc = trim(strip_tags(html_entity_decode($rawDescHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
$seoDescription = trim((string) ($product['seo_description'] ?? ''));
if ($seoDescription === '') {
    $seoDescription = function_exists('mb_substr')
        ? mb_substr($rawDesc, 0, 160)
        : substr($rawDesc, 0, 160);
}
$seoCanonical = $publicUrl;
$seoImage = $galleryImages[0] ?? '';

$descriptionHtml = 'Chưa có mô tả cho sản phẩm này.';
if ($rawDescHtml !== '') {
    $descriptionDecoded = html_entity_decode($rawDescHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $descriptionAllowedTags = '<p><br><strong><b><em><i><u><span><ul><ol><li><a><blockquote><h1><h2><h3><h4><h5><h6><table><thead><tbody><tr><th><td><img><hr><pre><code><div>';
    $descriptionHtml = strip_tags($descriptionDecoded, $descriptionAllowedTags);
    $descriptionHtml = preg_replace('~<(script|style|iframe|object|embed|form|input|button|textarea|select|option)[^>]*>.*?</\1>~is', '', $descriptionHtml) ?? $descriptionHtml;
    $descriptionHtml = preg_replace('~\son[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)~i', '', $descriptionHtml) ?? $descriptionHtml;
    $descriptionHtml = preg_replace('~\s(?:href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\1~i', '', $descriptionHtml) ?? $descriptionHtml;
    $descriptionHtml = trim($descriptionHtml);
    if ($descriptionHtml === '') {
        $descriptionHtml = 'Chưa có mô tả cho sản phẩm này.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title><?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        .pd-wrap {
            padding: 80px 0 48px;
            background: #f7f8fc;
        }

        .pd-card {
            background: #fff;
            border: 1px solid #eceef6;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(17, 24, 39, .04);
        }

        .pd-gallery-shell {
            background: #fff;
            padding: 12px;
        }

        .pd-gallery-stage {
            position: relative;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #eceef6;
            min-height: 360px;
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .pd-gallery-main-btn {
            width: 100%;
            height: 100%;
            min-height: 360px;
            border: 0;
            background: transparent;
            padding: 14px;
            cursor: zoom-in;
            display: grid;
            place-items: center;
        }

        .pd-gallery-main {
            width: 100%;
            max-height: 520px;
            object-fit: contain;
            display: block;
            border-radius: 10px;
        }

        .pd-gallery-main.is-entering {
            animation: pdGalleryImageIn .28s ease;
        }

        @keyframes pdGalleryImageIn {
            from {
                opacity: .25;
                transform: translateY(8px) scale(.985);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .pd-gallery-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%) scale(.92);
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: 1px solid #d6deea;
            background: rgba(255, 255, 255, .8);
            color: #0f172a;
            font-size: 22px;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            opacity: 0;
            pointer-events: none;
            transition: opacity .22s ease, transform .22s ease, background-color .22s ease, border-color .22s ease;
        }

        .pd-gallery-nav.prev {
            left: 12px;
        }

        .pd-gallery-nav.next {
            right: 12px;
        }

        .pd-gallery-stage:hover .pd-gallery-nav,
        .pd-gallery-stage:focus-within .pd-gallery-nav {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(-50%) scale(1);
        }

        .pd-gallery-nav:hover {
            background: #fff;
            border-color: #c5d0e2;
        }

        .pd-gallery-counter {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 2;
            color: #0f172a;
            background: rgba(255, 255, 255, .92);
            border: 1px solid #d6deea;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 13px;
            font-weight: 700;
        }

        .pd-thumbs {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            overflow-x: auto;
            padding-bottom: 2px;
        }

        .pd-thumb-btn {
            width: 64px;
            height: 64px;
            flex: 0 0 64px;
            border: 1px solid #e7e8f0;
            border-radius: 10px;
            padding: 0;
            background: #fff;
            overflow: hidden;
            cursor: pointer;
        }

        .pd-thumb-btn.is-active {
            border: 1px solid #000000ff;
        }

        .pd-thumb-btn img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            background: #f8fafc;
        }

        .pd-title {
            font-size: 2rem;
            line-height: 1.25;
            margin: 0 0 15px;
            font-weight: 700;
            color: #151a2d;
        }

        .pd-meta-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #f7f8fc;
            border: 1px solid #eceef6;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .pd-price-row .user-label {
            font-size: 25px;
            font-weight: 800;
            color: #000;
        }

        .pd-price {
            color: #00ad5c;
            font-weight: 800;
            font-size: 25px;
        }

        #pdUnitPriceText {
            color: #000;
            font-size: 22px;
        }

        .pd-stock {
            color: #078631ff;
            font-weight: 700;
            font-size: .95rem;
            text-align: right;
        }

        .pd-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }

        .pd-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: .82rem;
            font-weight: 600;
            border: 1px solid #e7e8f0;
            background: #fff;
            color: #334155;
        }

        .pd-chip.info {
            color: #0369a1;
            background: #f0f9ff;
            border-color: #bae6fd;
        }

        .pd-chip.success {
            color: #166534;
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .pd-chip.warn {
            color: #92400e;
            background: #fffbeb;
            border-color: #fde68a;
        }

        .pd-label {
            font-weight: 700;
            font-size: .92rem;
            margin-bottom: 6px;
            color: #1f2937;
        }

        .pd-qty {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pd-qty-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 1px solid #dbe0ea;
            background: #fff;
            font-size: 20px;
            font-weight: 700;
            line-height: 1;
            color: #334155;
        }

        .pd-qty-input {
            width: 90px;
            text-align: center;
            font-weight: 700;
            border-radius: 10px;
            border: 1px solid #dbe0ea;
            height: 40px;
        }

        .pd-summary {
            border: 1px solid #eceef6;
            border-radius: 12px;
            background: #fbfcff;
            padding: 12px 14px;
        }

        .pd-summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 6px 0;
            font-size: .95rem;
        }

        .pd-summary-row+.pd-summary-row {
            border-top: 1px dashed #e5e7eb;
        }

        .pd-summary-row.total {
            font-weight: 800;
            color: #00ad5c;
            font-size: 25px;
        }

        .pd-summary-row.total span {
            color: #151a2d;
        }

        #sumTotal {
            color: #000;
            font-size: 22px;
        }

        .pd-summary-row.discount {
            color: #078631ff;
            font-weight: 700;
        }

        .pd-alert {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #fde68a;
            background: #fffbeb;
            color: #92400e;
            font-size: .92rem;
            font-weight: 600;
        }

        .pd-alert a {
            color: #0b63c8;
            font-weight: 700;
            text-decoration: underline;
        }

        .pd-desc {
            color: #334155;
            line-height: 1.65;
            white-space: normal;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .pd-desc p,
        .pd-desc ul,
        .pd-desc ol,
        .pd-desc h1,
        .pd-desc h2,
        .pd-desc h3,
        .pd-desc h4,
        .pd-desc h5,
        .pd-desc h6,
        .pd-desc blockquote {
            margin-bottom: .85rem;
        }

        .pd-desc img {
            max-width: 100%;
            height: auto;
            object-fit: contain;
            border-radius: 10px;
        }

        .pd-desc table {
            width: 100%;
            display: block;
            overflow-x: auto;
        }

        .pd-desc :first-child {
            margin-top: 0;
        }

        .pd-desc :last-child {
            margin-bottom: 0;
        }

        .pd-buy-btn {
            border-radius: 12px;
            height: 48px;
            font-weight: 700;
            font-size: 1rem;
        }

        .pd-note {
            font-size: .86rem;
            color: #64748b;
        }

        .pd-gift-feedback {
            display: none;
            margin-top: 8px;
            font-size: .86rem;
            font-weight: 600;
        }

        .pd-gift-feedback.is-success {
            display: block;
            color: #15803d;
        }

        .pd-gift-feedback.is-error {
            display: block;
            color: #dc2626;
        }

        .pd-gift-apply-btn {
            min-width: 110px;
            font-weight: 700;
            background-color: #198754;
            border-color: #198754;
            color: #fff;
        }

        .pd-gift-apply-btn:hover {
            background-color: #157347;
            border-color: #146c43;
            color: #fff;
        }

        @media (max-width: 767.98px) {

            .pd-gallery-stage,
            .pd-gallery-main-btn {
                min-height: 260px;
            }

            .pd-gallery-main {
                max-height: 360px;
            }

            .pd-gallery-nav {
                width: 38px;
                height: 38px;
                font-size: 20px;
            }

            .pd-title {
                font-size: 1.25rem;
            }

            .pd-price {
                font-size: 1.15rem;
            }

            .pd-meta-line {
                align-items: flex-start;
            }

            .pd-stock {
                text-align: left;
                width: 100%;
            }
        }

        @media (hover: none),
        (pointer: coarse) {
            .pd-gallery-nav {
                opacity: 1;
                pointer-events: auto;
                transform: translateY(-50%) scale(1);
            }
        }
    </style>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="pd-wrap">
        <div class="container">


            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="pd-card p-3">
                        <div class="pd-gallery-shell">
                            <div class="pd-gallery-stage">
                                <?php if (count($galleryImages) > 1): ?>
                                    <button type="button" id="pdGalleryPrev" class="pd-gallery-nav prev"
                                        aria-label="Ảnh trước">&lsaquo;</button>
                                    <button type="button" id="pdGalleryNext" class="pd-gallery-nav next"
                                        aria-label="Ảnh tiếp theo">&rsaquo;</button>
                                <?php endif; ?>

                                <button type="button" id="pdMainImageOpen" class="pd-gallery-main-btn"
                                    aria-label="Xem ảnh full">
                                    <img id="pdMainImage"
                                        src="<?= htmlspecialchars((string) ($galleryImages[0] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        alt="<?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?>"
                                        class="pd-gallery-main" loading="eager" decoding="async" fetchpriority="high">
                                </button>

                                <div class="pd-gallery-counter" id="pdGalleryCounter">
                                    1 / <?= (int) count($galleryImages) ?>
                                </div>
                            </div>

                            <?php if (count($galleryImages) > 1): ?>
                                <div class="pd-thumbs" id="pdThumbs">
                                    <?php foreach ($galleryImages as $idx => $img): ?>
                                        <button type="button" class="pd-thumb-btn <?= $idx === 0 ? 'is-active' : '' ?>"
                                            data-index="<?= (int) $idx ?>"
                                            data-img="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>"
                                            aria-label="Ảnh sản phẩm <?= $idx + 1 ?>">
                                            <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>"
                                                alt="<?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?>" loading="lazy"
                                                decoding="async" fetchpriority="low">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="pd-card p-3 p-md-4">
                        <h1 class="pd-title"><?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?></h1>

                        <div class="pd-meta-line">
                            <div>
                                <div class="pd-note">Giá bán</div>
                                <div class="pd-price" id="pdUnitPriceText"><?= number_format($priceVnd) ?>đ</div>
                            </div>
                            <?php if ($deliveryMode !== 'source_link'): ?>
                                <div class="pd-stock">
                                    <div class="pd-note">Stock</div>
                                    <div id="pdStockLabel" <?= $stockColor !== '' ? ' style="color: ' . $stockColor . ';"' : '' ?>>
                                        <?= htmlspecialchars($stockLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pd-chips">
                            <?php if ($deliveryMode === 'account_stock'): ?>
                                <span class="pd-chip success"><i class="fas fa-user-shield"></i> Tài Khoản</span>
                            <?php elseif ($deliveryMode === 'source_link' || $productType === 'link'): ?>
                                <span class="pd-chip info"><i class="fas fa-link"></i> Source</span>
                            <?php elseif ($deliveryMode === 'manual_info'): ?>
                                <span class="pd-chip warn"><i class="fas fa-keyboard"></i> Yêu cầu thông tin</span>
                            <?php endif; ?>
                            <?php if ($requiresInfo && $deliveryMode !== 'manual_info'): ?>
                                <span class="pd-chip warn"><i class="fas fa-keyboard"></i> Yêu cầu thông tin</span>
                            <?php endif; ?>
                            <?php if ($deliveryMode === 'source_link'): ?>
                                <span class="pd-chip info"><i class="fas fa-arrow-up-1-9"></i> Max 1</span>
                            <?php else: ?>
                                <?php if ($purchaseMinQty > 1): ?>
                                    <span class="pd-chip"><i class="fas fa-arrow-down-1-9"></i> Min
                                        <?= $purchaseMinQty ?></span>
                                <?php endif; ?>
                                <?php if ($displayMaxQty > 0): ?>
                                    <span class="pd-chip info"><i class="fas fa-arrow-up-1-9"></i> Max
                                        <?= $displayMaxQty ?></span>
                                <?php elseif ($deliveryMode !== 'source_link' && !$isOutOfStock): ?>
                                    <span class="pd-chip info"><i class="fas fa-infinity"></i> Max không giới hạn</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!$canPurchase): ?>
                            <div class="pd-alert mb-3">
                                <?php if ($isOutOfStock): ?>
                                    Sản phẩm hiện ĐANG HẾT. Vui lòng quay lại sau hoặc liên hệ
                                    <?php if ($teleAdminUrl !== ''): ?>
                                        <a href="<?= htmlspecialchars($teleAdminUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank"
                                            rel="noopener noreferrer">Quản trị viên</a>
                                    <?php else: ?>
                                        Quản trị viên
                                    <?php endif; ?>.
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <div class="pd-label">Mã giảm giá</div>
                            <div class="input-group">
                                <input type="text" class="form-control" id="giftcodeInput" maxlength="100"
                                    placeholder="Nhập mã giảm giá">
                                <button type="button" class="btn pd-gift-apply-btn" id="applyGiftcodeBtn">Áp
                                    dụng</button>
                            </div>
                            <div id="giftcodeFeedback" class="pd-gift-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <div class="pd-label">Số lượng:</div>
                            <div class="pd-qty">
                                <button type="button" class="pd-qty-btn" id="qtyMinusBtn" aria-label="Giảm">-</button>
                                <input type="number" id="purchaseQty" class="pd-qty-input"
                                    min="<?= (int) $purchaseMinQty ?>" value="<?= (int) $purchaseMinQty ?>" step="1"
                                    <?= $displayMaxQty > 0 ? 'max="' . (int) $displayMaxQty . '"' : '' ?>
                                    <?= $deliveryMode === 'source_link' ? 'readonly' : '' ?>>
                                <button type="button" class="pd-qty-btn" id="qtyPlusBtn" aria-label="Tăng">+</button>
                            </div>

                        </div>

                        <?php if ($requiresInfo): ?>
                            <div class="mb-3">
                                <div class="pd-label">
                                    <?= $infoInstructions !== '' ? nl2br(htmlspecialchars($infoInstructions, ENT_QUOTES, 'UTF-8')) : 'Thông tin khách cần nhập' ?>
                                </div>
                                <textarea id="customerInput" class="form-control" rows="4"
                                    placeholder="Nhập thông tin theo yêu cầu sản phẩm..."></textarea>
                            </div>
                        <?php endif; ?>

                        <div class="pd-summary mb-3">
                            <div class="pd-summary-row total">
                                <span>Tổng tiền: </span>
                                <strong id="sumTotal"><?= number_format($priceVnd * $purchaseMinQty) ?>đ</strong>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100 pd-buy-btn" id="buyNowBtn"
                            onclick="buyProduct(<?= $productId ?>)" <?= $canPurchase ? '' : 'disabled' ?>>
                            <i class="fas fa-shopping-cart me-1"></i> Mua hàng ngay
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="pd-card p-3 p-md-4">
                        <h3 class="mb-3" style="font-weight:700; color:#151a2d;">Chi tiết sản phẩm</h3>
                        <div class="pd-desc">
                            <?= $descriptionHtml ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>

    <script>
        const PRODUCT_DETAIL = {
            id: <?= $productId ?>,
            price: <?= $priceVnd ?>,
            minQty: <?= (int) $purchaseMinQty ?>,
            maxQty: <?= (int) $displayMaxQty ?>,
            maxQtyConfig: <?= (int) $purchaseMaxQty ?>,
            availableStock: <?= is_int($availableStock) ? (int) $availableStock : 'null' ?>,
            stockManaged: <?= ($stockManaged && is_int($availableStock)) ? 'true' : 'false' ?>,
            requiresInfo: <?= $requiresInfo ? 'true' : 'false' ?>,
            deliveryMode: <?= json_encode($deliveryMode, JSON_UNESCAPED_UNICODE) ?>,
            productType: <?= json_encode($productType, JSON_UNESCAPED_UNICODE) ?>,
            canPurchase: <?= $canPurchase ? 'true' : 'false' ?>,
            quoteUrl: <?= json_encode(url('product/' . $productId . '/quote'), JSON_UNESCAPED_UNICODE) ?>,
            purchaseUrl: <?= json_encode(url('product/' . $productId . '/purchase'), JSON_UNESCAPED_UNICODE) ?>,
            loginUrl: <?= json_encode(url('login'), JSON_UNESCAPED_UNICODE) ?>,
            csrfToken: <?= json_encode(function_exists('csrf_token') ? csrf_token() : '', JSON_UNESCAPED_UNICODE) ?>
        };
        const PRODUCT_GALLERY_IMAGES = <?= json_encode(array_values($galleryImages), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const PRODUCT_GALLERY_FALLBACK = <?= json_encode(asset('assets/images/banner-bg-03.png'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        let APPLIED_GIFTCODE_PREVIEW = null;
        let APPLY_GIFTCODE_LOADING = false;

        function fmtMoney(value) {
            return new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';
        }

        function getQtyInput() {
            return document.getElementById('purchaseQty');
        }

        function getStockLabelEl() {
            return document.getElementById('pdStockLabel');
        }

        function computeDisplayMaxFromState() {
            if (PRODUCT_DETAIL.deliveryMode === 'source_link') {
                return 1;
            }

            if (PRODUCT_DETAIL.stockManaged) {
                const available = Math.max(0, Number(PRODUCT_DETAIL.availableStock || 0));
                const maxCfg = Math.max(0, Number(PRODUCT_DETAIL.maxQtyConfig || 0));
                return maxCfg > 0 ? Math.min(maxCfg, available) : available;
            }

            return Math.max(0, Number(PRODUCT_DETAIL.maxQty || 0));
        }

        function renderStockRealtimeState() {
            if (!PRODUCT_DETAIL.stockManaged) return;

            const stock = Math.max(0, Number(PRODUCT_DETAIL.availableStock || 0));
            const stockEl = getStockLabelEl();
            if (stockEl) {
                stockEl.textContent = String(stock);
                stockEl.style.color = stock > 0 ? '#0f7a2f' : '#dc2626';
                stockEl.style.fontWeight = '700';
            }

            PRODUCT_DETAIL.maxQty = computeDisplayMaxFromState();

            const qtyInput = getQtyInput();
            if (qtyInput) {
                if (PRODUCT_DETAIL.maxQty > 0) {
                    qtyInput.setAttribute('max', String(PRODUCT_DETAIL.maxQty));
                } else {
                    qtyInput.removeAttribute('max');
                }
            }

            PRODUCT_DETAIL.canPurchase = PRODUCT_DETAIL.maxQty > 0;
            const buyBtn = document.getElementById('buyNowBtn');
            if (buyBtn && !PRODUCT_DETAIL.canPurchase) {
                buyBtn.innerHTML = '<i class="fas fa-box-open me-1"></i> Hết hàng';
            }

            setPurchaseControlsAvailability();
        }

        function setPurchaseControlsAvailability() {
            const canBuy = !!PRODUCT_DETAIL.canPurchase;
            const qtyInput = getQtyInput();
            const minusBtn = document.getElementById('qtyMinusBtn');
            const plusBtn = document.getElementById('qtyPlusBtn');
            const giftInput = document.getElementById('giftcodeInput');
            const giftBtn = document.getElementById('applyGiftcodeBtn');

            if (qtyInput) {
                qtyInput.disabled = !canBuy;
                if (!canBuy) {
                    qtyInput.value = String(Math.max(1, Number(PRODUCT_DETAIL.minQty || 1)));
                }
            }
            if (minusBtn) minusBtn.disabled = !canBuy;
            if (plusBtn) plusBtn.disabled = !canBuy;
            if (giftInput) giftInput.disabled = false;
            if (giftBtn && !APPLY_GIFTCODE_LOADING) giftBtn.disabled = false;
        }

        function applyPurchaseSuccessRealtimeState(data, requestedQty) {
            if (!PRODUCT_DETAIL.stockManaged) return;

            const order = (data && data.order) ? data.order : {};
            const qtyBought = Math.max(1, Number(order.quantity || requestedQty || 1));

            PRODUCT_DETAIL.availableStock = Math.max(0, Number(PRODUCT_DETAIL.availableStock || 0) - qtyBought);
            renderStockRealtimeState();

            const qtyInput = getQtyInput();
            if (qtyInput) {
                const nextMax = Number(PRODUCT_DETAIL.maxQty || 0);
                if (nextMax <= 0) {
                    qtyInput.value = String(PRODUCT_DETAIL.minQty);
                    clearAppliedGiftcodePreview({ silent: true });
                } else if (Number(qtyInput.value || 0) > nextMax) {
                    qtyInput.value = String(nextMax);
                    clearAppliedGiftcodePreview({ silent: true });
                }
            }

            updateSummaryPreview();

            const giftcode = getGiftCode();
            if (giftcode && PRODUCT_DETAIL.canPurchase) {
                applyGiftcode();
            }
        }

        function normalizeQty(nextQty) {
            if (!PRODUCT_DETAIL.canPurchase) {
                return Math.max(1, Number(PRODUCT_DETAIL.minQty || 1));
            }

            let qty = Number(nextQty);
            if (!Number.isFinite(qty)) qty = PRODUCT_DETAIL.minQty;
            qty = Math.floor(qty);

            if (PRODUCT_DETAIL.deliveryMode === 'source_link') {
                qty = 1;
            }

            if (qty < PRODUCT_DETAIL.minQty) qty = PRODUCT_DETAIL.minQty;
            if (PRODUCT_DETAIL.maxQty > 0 && qty > PRODUCT_DETAIL.maxQty) qty = PRODUCT_DETAIL.maxQty;

            if (qty < 1) qty = 1;
            return qty;
        }

        function getRequestedQuantity() {
            const input = getQtyInput();
            const qty = normalizeQty(input ? input.value : PRODUCT_DETAIL.minQty);
            if (input) input.value = qty;
            return qty;
        }

        function getCustomerInput() {
            const el = document.getElementById('customerInput');
            return el ? String(el.value || '').trim() : '';
        }

        function getGiftCode() {
            const el = document.getElementById('giftcodeInput');
            if (!el) return '';
            el.value = String(el.value || '').trim().toUpperCase();
            return el.value;
        }

        function setGiftcodeFeedback(type, message) {
            const el = document.getElementById('giftcodeFeedback');
            if (!el) return;
            el.className = 'pd-gift-feedback';
            if (!message) {
                el.textContent = '';
                return;
            }
            el.classList.add(type === 'success' ? 'is-success' : 'is-error');
            el.textContent = String(message);
        }

        function setApplyGiftcodeLoading(isLoading) {
            APPLY_GIFTCODE_LOADING = !!isLoading;
            const btn = document.getElementById('applyGiftcodeBtn');
            if (!btn) return;
            btn.disabled = APPLY_GIFTCODE_LOADING;
            btn.innerHTML = APPLY_GIFTCODE_LOADING
                ? '<i class="fas fa-spinner fa-spin me-1"></i> Đang áp dụng'
                : 'Áp dụng';
        }

        function clearAppliedGiftcodePreview(options = {}) {
            APPLIED_GIFTCODE_PREVIEW = null;
            if (!options.silent) {
                setGiftcodeFeedback('', '');
            }
        }

        function updateSummaryPreview() {
            const qty = getRequestedQuantity();
            const giftcode = getGiftCode();
            const subtotal = qty * PRODUCT_DETAIL.price;
            let discount = 0;
            let total = subtotal;

            if (
                APPLIED_GIFTCODE_PREVIEW &&
                String(APPLIED_GIFTCODE_PREVIEW.giftcode || '') === giftcode &&
                Number(APPLIED_GIFTCODE_PREVIEW.quantity || 0) === qty
            ) {
                discount = Number(APPLIED_GIFTCODE_PREVIEW.discount_amount || 0);
                total = Number(APPLIED_GIFTCODE_PREVIEW.total_price || (subtotal - discount));
            }

            const unitEl = document.getElementById('sumUnitPrice');
            const qtyEl = document.getElementById('sumQty');
            const subtotalEl = document.getElementById('sumSubtotal');
            const totalEl = document.getElementById('sumTotal');
            const discountEl = document.getElementById('sumDiscount');

            if (unitEl) unitEl.textContent = fmtMoney(PRODUCT_DETAIL.price);
            if (qtyEl) qtyEl.textContent = String(qty);
            if (subtotalEl) subtotalEl.textContent = fmtMoney(subtotal);
            if (discountEl) discountEl.textContent = fmtMoney(discount);
            if (totalEl) totalEl.textContent = fmtMoney(total);
        }

        function applyGiftcode() {
            if (!PRODUCT_DETAIL.canPurchase) return;

            const giftcode = getGiftCode();
            const qty = getRequestedQuantity();

            if (!giftcode) {
                clearAppliedGiftcodePreview();
                setGiftcodeFeedback('error', 'Vui lòng nhập mã giảm giá trước khi áp dụng.');
                updateSummaryPreview();
                return;
            }

            if (APPLY_GIFTCODE_LOADING) return;

            const formData = new FormData();
            formData.append('quantity', String(qty));
            formData.append('giftcode', giftcode);
            formData.append('csrf_token', PRODUCT_DETAIL.csrfToken);

            setApplyGiftcodeLoading(true);
            setGiftcodeFeedback('', '');

            fetch(PRODUCT_DETAIL.quoteUrl, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'X-CSRF-Token': PRODUCT_DETAIL.csrfToken },
                body: formData
            })
                .then(async (res) => {
                    let data = {};
                    try { data = await res.json(); } catch (e) { }
                    if (!res.ok || !data.success) {
                        throw new Error((data && data.message) ? data.message : 'Không thể áp dụng mã giảm giá.');
                    }
                    return data;
                })
                .then((data) => {
                    const pricing = data.pricing || {};
                    APPLIED_GIFTCODE_PREVIEW = {
                        giftcode: pricing.giftcode || giftcode,
                        quantity: Number(pricing.quantity || qty),
                        discount_amount: Number(pricing.discount_amount || 0),
                        total_price: Number(pricing.total_price || (qty * PRODUCT_DETAIL.price))
                    };
                    updateSummaryPreview();
                    if (pricing.giftcode) {
                        const pct = Number(pricing.giftcode_percent || 0);
                        setGiftcodeFeedback('success', pct > 0
                            ? ('Áp dụng mã thành công: giảm ' + pct + '%.')
                            : 'Mã hợp lệ nhưng không có giảm giá.');
                    } else {
                        setGiftcodeFeedback('success', data.message || 'Đã cập nhật thành tiền.');
                    }
                })
                .catch((err) => {
                    clearAppliedGiftcodePreview({ silent: true });
                    updateSummaryPreview();
                    setGiftcodeFeedback('error', (err && err.message) ? err.message : 'Không thể áp dụng mã giảm giá.');
                })
                .finally(() => {
                    setApplyGiftcodeLoading(false);
                });
        }

        function setBuyButtonLoading(isLoading) {
            const btn = document.getElementById('buyNowBtn');
            if (!btn) return;
            btn.disabled = !!isLoading || !PRODUCT_DETAIL.canPurchase;
            if (isLoading) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang xử lý...';
                return;
            }
            btn.innerHTML = PRODUCT_DETAIL.canPurchase
                ? '<i class="fas fa-shopping-cart me-1"></i> Mua hàng ngay'
                : '<i class="fas fa-box-open me-1"></i> Hết hàng';
            setPurchaseControlsAvailability();
        }


        function showPurchaseSuccess(data) {
            const payload = data || {};
            const order = payload.order || {};
            const isPending = !!payload.pending || String(order.status || '').toLowerCase() === 'pending';
            const orderId = Number(order.id || 0);
            const historyUrl = BASE_URL + '/history-orders';
            const detailUrl = historyUrl + (orderId > 0 ? ('?order_id=' + encodeURIComponent(String(orderId))) : '');

            if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.purchaseResult === 'function') {
                SwalHelper.purchaseResult(payload, {
                    historyUrl: historyUrl,
                    detailUrl: detailUrl,
                    onCompletedOpen: function () {
                        if (window.KaiConfetti) {
                            window.KaiConfetti.ensureReady().then(function () {
                                window.KaiConfetti.fire();
                            });
                        }
                    }
                });
                return;
            }

            alert(isPending
                ? 'Đặt hàng thành công. Đơn đang chờ xử lý.'
                : 'Thanh toán thành công.');
        }
        function buyProduct(id) {
            if (!PRODUCT_DETAIL.canPurchase) {
                return;
            }

            const qty = getRequestedQuantity();
            const customerInput = getCustomerInput();
            const giftcode = getGiftCode();

            if (PRODUCT_DETAIL.requiresInfo && !customerInput) {
                if (window.Swal && Swal.fire) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Thiếu thông tin',
                        text: 'Vui lòng nhập thông tin theo yêu cầu sản phẩm trước khi mua.'
                    });
                } else {
                    alert('Vui lòng nhập thông tin theo yêu cầu sản phẩm trước khi mua.');
                }
                return;
            }

            // Confirmation before purchase
            let totalToPay = qty * PRODUCT_DETAIL.price;
            if (APPLIED_GIFTCODE_PREVIEW && Number(APPLIED_GIFTCODE_PREVIEW.quantity) === qty) {
                totalToPay = Number(APPLIED_GIFTCODE_PREVIEW.total_price);
            }

            const productNameText = document.querySelector('.pd-title') ? document.querySelector('.pd-title').textContent.trim() : 'sản phẩm';
            const confirmMsg = `
                <div class="text-start">
                    <div class="p-2 border rounded bg-light mb-2">
                        <div class="d-flex justify-content-between">
                            <span>Sản phẩm:</span>
                            <span class="fw-bold text-end">${productNameText}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Số lượng:</span>
                            <span class="fw-bold">x${qty}</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Tổng thanh toán:</span>
                        <span class="fs-5 fw-bold text-success">${fmtMoney(totalToPay)}</span>
                    </div>
                </div>
            `;

            if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.confirm === 'function') {
                SwalHelper.confirm('Xác nhận mua hàng', confirmMsg, () => {
                    executePurchase(qty, customerInput, giftcode);
                });
            } else {
                if (confirm(confirmMsg.replace(/<[^>]*>/g, ''))) {
                    executePurchase(qty, customerInput, giftcode);
                }
            }
        }

        function executePurchase(qty, customerInput, giftcode) {
            const formData = new FormData();
            formData.append('quantity', String(qty));
            formData.append('customer_input', customerInput);
            formData.append('giftcode', giftcode);
            formData.append('csrf_token', PRODUCT_DETAIL.csrfToken);

            setBuyButtonLoading(true);

            fetch(PRODUCT_DETAIL.purchaseUrl, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'X-CSRF-Token': PRODUCT_DETAIL.csrfToken },
                body: formData
            })
                .then(async (res) => {
                    let data = {};
                    try {
                        data = await res.json();
                    } catch (e) { }
                    if (!res.ok || !data.success) {
                        throw new Error((data && data.message) ? data.message : 'Không thể mua sản phẩm lúc này.');
                    }
                    return data;
                })
                .then((data) => {
                    const order = data.order || {};
                    const qtyEl = document.getElementById('sumQty');
                    const subEl = document.getElementById('sumSubtotal');
                    const disEl = document.getElementById('sumDiscount');
                    const totalEl = document.getElementById('sumTotal');
                    if (qtyEl) qtyEl.textContent = String(order.quantity || qty);
                    if (subEl) subEl.textContent = fmtMoney(order.subtotal_price || (qty * PRODUCT_DETAIL.price));
                    if (disEl) disEl.textContent = fmtMoney(order.discount_amount || 0);
                    if (totalEl) totalEl.textContent = fmtMoney(order.price || (qty * PRODUCT_DETAIL.price));
                    applyPurchaseSuccessRealtimeState(data, qty);
                    showPurchaseSuccess(data);
                })
                .catch((err) => {
                    const msg = (err && err.message) ? err.message : 'Không thể mua sản phẩm lúc này.';
                    if (/dang nhap|đăng nhập/i.test(msg)) {
                        window.location.href = PRODUCT_DETAIL.loginUrl;
                        return;
                    }
                    if (window.Swal && Swal.fire) {
                        Swal.fire({ icon: 'error', title: 'Mua hàng thất bại', text: msg });
                    } else {
                        alert(msg);
                    }
                })
                .finally(() => {
                    setBuyButtonLoading(false);
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const mainImg = document.getElementById('pdMainImage');
            const thumbsWrap = document.getElementById('pdThumbs');
            const prevBtn = document.getElementById('pdGalleryPrev');
            const nextBtn = document.getElementById('pdGalleryNext');
            const counterEl = document.getElementById('pdGalleryCounter');
            const openBtn = document.getElementById('pdMainImageOpen');
            const galleryImages = Array.isArray(PRODUCT_GALLERY_IMAGES) ? PRODUCT_GALLERY_IMAGES.slice() : [];
            let galleryIndex = 0;
            let galleryLightbox = null;

            function normalizeGalleryIndex(nextIndex) {
                const total = galleryImages.length;
                if (total <= 0) return 0;
                let idx = Number(nextIndex || 0);
                if (!Number.isFinite(idx)) idx = 0;
                idx = Math.floor(idx);
                if (idx < 0) idx = total - 1;
                if (idx >= total) idx = 0;
                return idx;
            }

            function renderGallery(nextIndex) {
                if (!mainImg || galleryImages.length <= 0) return;
                galleryIndex = normalizeGalleryIndex(nextIndex);
                const nextSrc = String(galleryImages[galleryIndex] || '').trim() || PRODUCT_GALLERY_FALLBACK;
                mainImg.classList.remove('is-entering');
                void mainImg.offsetWidth;
                mainImg.src = nextSrc;
                mainImg.classList.add('is-entering');

                if (counterEl) {
                    counterEl.textContent = (galleryIndex + 1) + ' / ' + galleryImages.length;
                }

                if (thumbsWrap) {
                    thumbsWrap.querySelectorAll('.pd-thumb-btn').forEach((el) => {
                        const idx = Number(el.getAttribute('data-index') || 0);
                        el.classList.toggle('is-active', idx === galleryIndex);
                    });
                }
            }

            if (mainImg) {
                mainImg.addEventListener('error', function () {
                    if (this.src !== PRODUCT_GALLERY_FALLBACK) {
                        this.src = PRODUCT_GALLERY_FALLBACK;
                    }
                });
            }

            if (thumbsWrap && mainImg) {
                thumbsWrap.addEventListener('click', function (e) {
                    const btn = e.target.closest('.pd-thumb-btn');
                    if (!btn) return;
                    const idx = Number(btn.getAttribute('data-index') || 0);
                    renderGallery(idx);
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    renderGallery(galleryIndex - 1);
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    renderGallery(galleryIndex + 1);
                });
            }

            if (openBtn) {
                openBtn.addEventListener('click', function () {
                    if (galleryImages.length <= 0) return;

                    if (!galleryLightbox && window.GLightbox) {
                        galleryLightbox = GLightbox({
                            elements: galleryImages.map(function (src) {
                                return { href: src, type: 'image' };
                            }),
                            loop: true,
                            touchNavigation: true,
                            draggable: true
                        });
                    }

                    if (galleryLightbox && typeof galleryLightbox.openAt === 'function') {
                        galleryLightbox.openAt(galleryIndex);
                        return;
                    }

                    window.open(String(galleryImages[galleryIndex] || PRODUCT_GALLERY_FALLBACK), '_blank', 'noopener');
                });
            }

            document.addEventListener('keydown', function (e) {
                if (galleryImages.length <= 1) return;
                const tag = String((e.target && e.target.tagName) || '').toLowerCase();
                if (tag === 'input' || tag === 'textarea') return;
                if (e.key === 'ArrowLeft') {
                    renderGallery(galleryIndex - 1);
                } else if (e.key === 'ArrowRight') {
                    renderGallery(galleryIndex + 1);
                }
            });

            renderGallery(0);

            const qtyInput = getQtyInput();
            const minusBtn = document.getElementById('qtyMinusBtn');
            const plusBtn = document.getElementById('qtyPlusBtn');
            const applyGiftcodeBtn = document.getElementById('applyGiftcodeBtn');

            if (qtyInput) {
                qtyInput.addEventListener('input', function () {
                    clearAppliedGiftcodePreview();
                    updateSummaryPreview();
                });
                qtyInput.addEventListener('change', function () {
                    clearAppliedGiftcodePreview();
                    updateSummaryPreview();
                });
            }

            if (minusBtn) {
                minusBtn.addEventListener('click', function () {
                    const next = getRequestedQuantity() - 1;
                    if (qtyInput) qtyInput.value = normalizeQty(next);
                    clearAppliedGiftcodePreview();
                    updateSummaryPreview();
                });
            }

            if (plusBtn) {
                plusBtn.addEventListener('click', function () {
                    const next = getRequestedQuantity() + 1;
                    if (qtyInput) qtyInput.value = normalizeQty(next);
                    clearAppliedGiftcodePreview();
                    updateSummaryPreview();
                });
            }

            const giftcodeInput = document.getElementById('giftcodeInput');
            if (giftcodeInput) {
                giftcodeInput.addEventListener('input', function () {
                    this.value = this.value.toUpperCase();
                    clearAppliedGiftcodePreview();
                    updateSummaryPreview();
                });
                giftcodeInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        applyGiftcode();
                    }
                });
            }

            if (applyGiftcodeBtn) {
                applyGiftcodeBtn.addEventListener('click', applyGiftcode);
            }

            updateSummaryPreview();
            renderStockRealtimeState();
            setBuyButtonLoading(false);
        });
    </script>
</body>

</html>
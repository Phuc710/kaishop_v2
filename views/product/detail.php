<?php
$productName = (string) ($product['name'] ?? 'Sản phẩm');
$productId = (int) ($product['id'] ?? 0);
$priceVnd = max(0, (int) ($product['price_vnd'] ?? 0));
$purchaseMinQty = max(1, (int) ($product['min_purchase_qty'] ?? 1));
$purchaseMaxQty = max(0, (int) ($product['max_purchase_qty'] ?? 0));
$requiresInfo = (int) ($product['requires_info'] ?? 0) === 1;
$productType = (string) ($product['product_type'] ?? 'account');
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
if ($productType === 'link') {
    $displayMaxQty = 1;
} elseif ($productType === 'account' && !$requiresInfo && is_int($availableStock)) {
    $displayMaxQty = $purchaseMaxQty > 0 ? min($purchaseMaxQty, $availableStock) : $availableStock;
}

$canPurchase = true;
$stockLabel = 'Unlimited';
$isOutOfStock = false;
if ($requiresInfo) {
    $stockLabel = 'Theo yêu cầu';
} elseif ($productType === 'link') {
    $stockLabel = 'Unlimited';
} elseif (is_int($availableStock)) {
    $stockLabel = max(0, $availableStock) . ' Stock';
    if ($availableStock <= 0) {
        $canPurchase = false;
        $isOutOfStock = true;
    }
}

if ($displayMaxQty > 0 && $displayMaxQty < $purchaseMinQty) {
    $canPurchase = false;
}

$seoTitle = $productName . ' | ' . (string) ($chungapi['ten_web'] ?? 'KaiShop');
$rawDesc = trim((string) ($product['description'] ?? ''));
$seoDescription = trim((string) ($product['seo_description'] ?? ''));
if ($seoDescription === '') {
    $seoDescription = function_exists('mb_substr')
        ? mb_substr($rawDesc, 0, 160)
        : substr($rawDesc, 0, 160);
}
$seoCanonical = $publicUrl;
$seoImage = $galleryImages[0] ?? '';
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

        .pd-gallery-main {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
            border-radius: 14px;
            background: #f3f4f6;
            border: 1px solid #edf0f5;
        }

        .pd-thumbs {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .pd-thumb-btn {
            border: 1px solid #e7e8f0;
            border-radius: 10px;
            padding: 0;
            background: #fff;
            overflow: hidden;
            cursor: pointer;
        }

        .pd-thumb-btn.is-active {
            border-color: #845adf;
            box-shadow: 0 0 0 2px rgba(132, 90, 223, .12);
        }

        .pd-thumb-btn img {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
            display: block;
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
            white-space: pre-line;
            overflow-wrap: break-word;
            word-break: break-word;
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
    </style>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="pd-wrap">
        <div class="container">


            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="pd-card p-3">
                        <img id="pdMainImage"
                            src="<?= htmlspecialchars((string) ($galleryImages[0] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?>" class="pd-gallery-main">

                        <?php if (count($galleryImages) > 1): ?>
                            <div class="pd-thumbs" id="pdThumbs">
                                <?php foreach ($galleryImages as $idx => $img): ?>
                                    <button type="button" class="pd-thumb-btn <?= $idx === 0 ? 'is-active' : '' ?>"
                                        data-img="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>"
                                        aria-label="Ảnh sản phẩm <?= $idx + 1 ?>">
                                        <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>"
                                            alt="<?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?>">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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
                            <div class="pd-stock">
                                <div class="pd-note">Stock</div>
                                <div id="pdStockLabel">
                                    <?= htmlspecialchars($stockLabel, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>

                        <div class="pd-chips">
                            <?php if ($productType === 'account'): ?>
                                <span class="pd-chip success"><i class="fas fa-user-shield"></i> Tài khoản từ
                                    kho</span>
                            <?php else: ?>
                                <span class="pd-chip info"><i class="fas fa-link"></i> Source / Link</span>
                            <?php endif; ?>
                            <?php if ($requiresInfo): ?>
                                <span class="pd-chip warn"><i class="fas fa-keyboard"></i> Yêu cầu thông tin từ
                                    user</span>
                            <?php endif; ?>
                            <?php if ($purchaseMinQty > 1): ?>
                                <span class="pd-chip"><i class="fas fa-arrow-down-1-9"></i> Min
                                    <?= $purchaseMinQty ?></span>
                            <?php endif; ?>
                            <?php if ($displayMaxQty > 0): ?>
                                <span class="pd-chip info"><i class="fas fa-arrow-up-1-9"></i> Max
                                    <?= $displayMaxQty ?></span>
                            <?php elseif ($productType !== 'link'): ?>
                                <span class="pd-chip info"><i class="fas fa-infinity"></i> Max không giới hạn</span>
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
                                    <?= $productType === 'link' ? 'readonly' : '' ?>>
                                <button type="button" class="pd-qty-btn" id="qtyPlusBtn" aria-label="Tăng">+</button>
                            </div>
                            <div class="pd-note mt-1">
                                <?php if ($productType === 'link'): ?>
                                    Tối đa 1 sản phẩm mỗi đơn.
                                <?php else: ?>
                                    Min: <?= $purchaseMinQty ?>
                                    <?php if ($displayMaxQty > 0): ?>
                                        | Max: <?= $displayMaxQty ?>
                                    <?php else: ?>
                                        | Max:
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($requiresInfo): ?>
                            <div class="mb-3">
                                <div class="pd-label">Thông tin khách cần nhập</div>
                                <textarea id="customerInput" class="form-control" rows="4"
                                    placeholder="Nhập thông tin theo yêu cầu sản phẩm..."></textarea>
                                <?php if ($infoInstructions !== ''): ?>
                                    <div class="pd-note mt-1">
                                        <?= nl2br(htmlspecialchars($infoInstructions, ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                <?php endif; ?>
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
                            <?= nl2br(htmlspecialchars((string) ($product['description'] ?? 'Chưa có mô tả cho sản phẩm này.'), ENT_QUOTES, 'UTF-8')) ?>
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
            stockManaged: <?= ($productType === 'account' && !$requiresInfo && is_int($availableStock)) ? 'true' : 'false' ?>,
            requiresInfo: <?= $requiresInfo ? 'true' : 'false' ?>,
            productType: <?= json_encode($productType, JSON_UNESCAPED_UNICODE) ?>,
            canPurchase: <?= $canPurchase ? 'true' : 'false' ?>,
            quoteUrl: <?= json_encode(url('product/' . $productId . '/quote'), JSON_UNESCAPED_UNICODE) ?>,
            purchaseUrl: <?= json_encode(url('product/' . $productId . '/purchase'), JSON_UNESCAPED_UNICODE) ?>,
            loginUrl: <?= json_encode(url('login'), JSON_UNESCAPED_UNICODE) ?>,
            csrfToken: <?= json_encode(function_exists('csrf_token') ? csrf_token() : '', JSON_UNESCAPED_UNICODE) ?>
        };
        let APPLIED_GIFTCODE_PREVIEW = null;
        let APPLY_GIFTCODE_LOADING = false;

        function fmtMoney(value) {
            return new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getQtyInput() {
            return document.getElementById('purchaseQty');
        }

        function getStockLabelEl() {
            return document.getElementById('pdStockLabel');
        }

        function computeDisplayMaxFromState() {
            if (PRODUCT_DETAIL.productType === 'link') {
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
                stockEl.textContent = stock + ' Stock';
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
            if (data && data.pending) return;

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

            if (PRODUCT_DETAIL.productType === 'link') {
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

        let __ksConfettiLoader = null;

        function ensureConfettiReady() {
            if (typeof confetti === 'function') return Promise.resolve();
            if (__ksConfettiLoader) return __ksConfettiLoader;

            __ksConfettiLoader = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js';
                script.async = true;
                script.onload = () => resolve();
                script.onerror = () => reject(new Error('confetti_load_failed'));
                document.head.appendChild(script);
            }).catch(() => {
                __ksConfettiLoader = null;
            });

            return __ksConfettiLoader || Promise.resolve();
        }

        function fireDoubleSideConfetti() {
            if (typeof confetti !== 'function') return;

            const count = 250;
            const defaults = {
                origin: { y: 0.7 },
                spread: 90,
                ticks: 300,
                gravity: 1.2,
                decay: 0.94,
                startVelocity: 45,
                zIndex: 1000
            };

            confetti({
                ...defaults,
                particleCount: count,
                angle: 60,
                origin: { x: 0, y: 0.7 }
            });

            confetti({
                ...defaults,
                particleCount: count,
                angle: 120,
                origin: { x: 1, y: 0.7 }
            });
        }

        function buildOrderDownloadUrl(order) {
            const id = Number(order && order.id ? order.id : 0);
            if (!id) return '';
            return BASE_URL + '/history-orders/download/' + encodeURIComponent(String(id));
        }

        function attachSuccessModalActions(data) {
            const order = data.order || {};

            const historyBtn = document.querySelector('.js-order-modal-history');
            if (historyBtn) {
                historyBtn.addEventListener('click', function () {
                    window.location.href = BASE_URL + '/history-orders';
                });
            }

            const buyMoreBtn = document.querySelector('.js-order-modal-buy-more');
            if (buyMoreBtn) {
                buyMoreBtn.addEventListener('click', function () {
                    if (window.Swal && Swal.close) Swal.close();
                });
            }

            const downBtn = document.querySelector('.js-order-modal-download');
            if (downBtn) {
                downBtn.addEventListener('click', function () {
                    const url = buildOrderDownloadUrl(order);
                    if (!url) {
                        if (window.SwalHelper && SwalHelper.toast) {
                            SwalHelper.toast('Không thể tải đơn hàng này.', 'error');
                        }
                        return;
                    }
                    window.open(url, '_blank');
                });
            }
        }

        function buildSuccessHtml(data) {
            const order = data.order || {};
            const isPending = !!data.pending;
            const orderCodeDisplay = order.order_code_short || order.order_code || '-';
            const subtotal = Number(order.subtotal_price || order.price || 0);
            const discount = Number(order.discount_amount || 0);
            const total = Number(order.price || 0);

            let html = '';
            html += '<div style="text-align:left">';
            html += '<div style="display:flex;justify-content:center;margin-bottom:10px;">';
            html += '<span style="display:inline-flex;align-items:center;gap:8px;border:1px solid #bbf7d0;background:#f0fdf4;color:#065f46;padding:7px 12px;border-radius:999px;font-weight:700;font-size:14px;cursor:pointer;" title="Bấm để sao chép mã đơn" class="js-copy-success-order" data-copy="' + escapeHtml(orderCodeDisplay) + '">';
            html += 'Mã đơn #' + escapeHtml(orderCodeDisplay) + '</span></div>';
            html += '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:10px 12px;margin-bottom:10px;">';
            html += '<div style="display:flex;justify-content:space-between;gap:10px;padding:3px 0;"><span>Sản phẩm</span><strong>' + escapeHtml(order.product_name || '-') + '</strong></div>';
            html += '<div style="display:flex;justify-content:space-between;gap:10px;padding:3px 0;"><span>Số lượng</span><strong>' + escapeHtml(order.quantity || 1) + '</strong></div>';
            html += '<div style="display:flex;justify-content:space-between;gap:10px;padding:3px 0;"><span>Thành tiền</span><strong>' + fmtMoney(subtotal) + '</strong></div>';
            html += '<div style="display:flex;justify-content:space-between;gap:10px;padding:3px 0;color:#16a34a;"><span>Giảm giá</span><strong>-' + fmtMoney(discount) + '</strong></div>';
            html += '<div style="display:flex;justify-content:space-between;gap:10px;padding:5px 0;border-top:1px dashed #cbd5e1;margin-top:4px;"><span><b>Tổng thanh toán</b></span><strong>' + fmtMoney(total) + '</strong></div>';
            if (order.giftcode) {
                html += '<div style="margin-top:4px;font-size:12px;color:#0f766e;">Đã áp dụng mã: <b>' + escapeHtml(order.giftcode) + '</b></div>';
            }
            html += '</div>';

            if (isPending) {
                html += '<p style="margin:0 0 6px;font-size:14px;"><b>Đơn hàng đang ở trạng thái chờ xử lý.</b> Admin sẽ xử lý và trả kết quả sau.</p>';
                if (order.customer_input) {
                    html += '<p style="margin:0 0 5px;font-size:14px;"><b>Thông tin bạn đã gửi:</b></p>';
                    html += '<textarea readonly style="width:100%;min-height:90px;border:1px solid #ddd;border-radius:10px;padding:10px;font-size:13px;">' + escapeHtml(order.customer_input) + '</textarea>';
                }
            } else {
                html += '<p style="margin:0 0 5px;font-size:14px;"><b>Dữ liệu bàn giao:</b></p>';
                html += '<textarea readonly style="width:100%;min-height:110px;border:1px solid #ddd;border-radius:10px;padding:10px;font-size:13px;">' + escapeHtml(order.content || '') + '</textarea>';
            }

            html += '<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:12px;">';
            html += '<button type="button" class="js-order-modal-history" style="border:1px solid #0ea5e9;background:#f0f9ff;color:#0369a1;border-radius:10px;padding:10px 8px;font-weight:700;">Xem đơn hàng</button>';
            html += '<button type="button" class="js-order-modal-buy-more" style="border:1px solid #d1d5db;background:#f9fafb;color:#111827;border-radius:10px;padding:10px 8px;font-weight:700;">Mua thêm</button>';
            html += '<button type="button" class="js-order-modal-download" style="border:1px solid #16a34a;background:#f0fdf4;color:#166534;border-radius:10px;padding:10px 8px;font-weight:700;">Down</button>';
            html += '</div>';
            html += '</div>';
            return html;
        }

        function showPurchaseSuccess(data) {
            const isPending = !!data.pending;
            const title = isPending ? 'Tạo đơn hàng thành công' : 'Thanh toán thành công!';

            if (window.Swal && Swal.fire) {
                Swal.fire({
                    icon: 'success',
                    title: title,
                    html: buildSuccessHtml(data),
                    width: 680,
                    confirmButtonText: 'Đóng',
                    didOpen: function () {
                        fireDoubleSideConfetti();
                        attachSuccessModalActions(data);

                        const copyBtn = document.querySelector('.js-copy-success-order');
                        if (copyBtn) {
                            copyBtn.addEventListener('click', async function () {
                                const text = String(copyBtn.getAttribute('data-copy') || '').trim();
                                if (!text) return;
                                try {
                                    if (navigator.clipboard && navigator.clipboard.writeText) {
                                        await navigator.clipboard.writeText(text);
                                    } else {
                                        const ta = document.createElement('textarea');
                                        ta.value = text;
                                        document.body.appendChild(ta);
                                        ta.select();
                                        document.execCommand('copy');
                                        ta.remove();
                                    }
                                    if (window.SwalHelper && SwalHelper.toast) {
                                        SwalHelper.toast('Đã sao chép mã đơn', 'success');
                                    }
                                } catch (e) { }
                            });
                        }
                    }
                });
                return;
            }

            alert(data.message || 'Thành công');
        }

        // Override popup builder to keep labels/buttons aligned with latest UX requirements.
        function buildSuccessHtml(data) {
            const order = data.order || {};
            const isPending = !!data.pending;
            const orderCodeDisplay = order.order_code_short || order.order_code || '-';
            const quantity = Math.max(1, Number(order.quantity || 1));
            const subtotal = Number(order.subtotal_price || order.price || 0);
            const discount = Number(order.discount_amount || 0);
            const total = Number(order.price || 0);
            const unitPrice = quantity > 0 ? Math.round(subtotal / quantity) : subtotal;

            let html = '';
            html += '<div style="text-align:left">';
            html += '<div style="display:flex;justify-content:center;margin-bottom:10px;">';
            html += '<span style="display:inline-flex;align-items:center;gap:8px;border:1px solid #bbf7d0;background:#f0fdf4;color:#065f46;padding:7px 12px;border-radius:999px;font-weight:700;font-size:14px;cursor:pointer;" title="Bấm để sao chép mã đơn" class="js-copy-success-order" data-copy="' + escapeHtml(orderCodeDisplay) + '">';
            html += 'Mã đơn #' + escapeHtml(orderCodeDisplay) + '</span></div>';

            html += '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:10px 12px;margin-bottom:10px;">';
            html += '<div style="display:flex;justify-content:space-between;gap:10px;padding:3px 0;"><span>Sản phẩm</span><strong>' + escapeHtml(order.product_name || '-') + '</strong></div>';
            html += '<div style="display:flex;justify-content:space-between;gap:10px;padding:3px 0;"><span>Giá</span><strong>' + fmtMoney(unitPrice) + '</strong></div>';
            html += '<div style="display:flex;justify-content:space-between;gap:10px;padding:3px 0;"><span>Số lượng</span><strong>' + escapeHtml(quantity) + '</strong></div>';
            html += '<div style="display:flex;justify-content:space-between;gap:10px;padding:3px 0;color:#16a34a;"><span>Giảm giá</span><strong>-' + fmtMoney(discount) + '</strong></div>';
            html += '<div style="display:flex;justify-content:space-between;gap:10px;padding:5px 0;border-top:1px dashed #cbd5e1;margin-top:4px;"><span><b>Tổng thanh toán</b></span><strong>' + fmtMoney(total) + '</strong></div>';
            if (order.giftcode) {
                html += '<div style="margin-top:4px;font-size:12px;color:#0f766e;">Đã áp dụng mã: <b>' + escapeHtml(order.giftcode) + '</b></div>';
            }
            html += '</div>';

            if (isPending) {
                html += '<p style="margin:0 0 6px;font-size:14px;"><b>Đơn hàng đang ở trạng thái chờ xử lý.</b> Admin sẽ xử lý và trả kết quả sau.</p>';
                if (order.customer_input) {
                    html += '<p style="margin:0 0 5px;font-size:14px;"><b>Thông tin bạn đã gửi:</b></p>';
                    html += '<textarea readonly style="width:100%;min-height:80px;max-height:140px;border:1px solid #ddd;border-radius:10px;padding:10px;font-size:13px;">' + escapeHtml(order.customer_input) + '</textarea>';
                }
            } else {
                html += '<p style="margin:0 0 5px;font-size:14px;"><b>Đơn hàng:</b></p>';
                html += '<textarea readonly style="width:100%;min-height:80px;max-height:150px;border:1px solid #ddd;border-radius:10px;padding:10px;font-size:13px;">' + escapeHtml(order.content || '') + '</textarea>';
            }

            html += '<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:12px;">';
            html += '<button type="button" class="js-order-modal-history" style="border:1px solid #0ea5e9;background:#f0f9ff;color:#0369a1;border-radius:10px;padding:10px 8px;font-weight:700;">Chi tiết</button>';
            html += '<button type="button" class="js-order-modal-download" style="border:1px solid #16a34a;background:#f0fdf4;color:#166534;border-radius:10px;padding:10px 8px;font-weight:700;">DOWNLOAD</button>';
            html += '<button type="button" class="js-order-modal-buy-more" style="border:1px solid #d1d5db;background:#f9fafb;color:#111827;border-radius:10px;padding:10px 8px;font-weight:700;">Mua thêm</button>';
            html += '</div>';
            html += '</div>';
            return html;
        }

        function showPurchaseSuccess(data) {
            const isPending = !!data.pending;
            const title = isPending ? 'Tạo đơn hàng thành công' : 'Thanh toán thành công!';

            if (window.Swal && Swal.fire) {
                Swal.fire({
                    icon: 'success',
                    title: title,
                    html: buildSuccessHtml(data),
                    width: 680,
                    showConfirmButton: false,
                    showCloseButton: false,
                    didOpen: function () {
                        ensureConfettiReady().then(function () {
                            fireDoubleSideConfetti();
                        }).catch(function () { });
                        attachSuccessModalActions(data);

                        const copyBtn = document.querySelector('.js-copy-success-order');
                        if (copyBtn) {
                            copyBtn.addEventListener('click', async function () {
                                const text = String(copyBtn.getAttribute('data-copy') || '').trim();
                                if (!text) return;
                                try {
                                    if (navigator.clipboard && navigator.clipboard.writeText) {
                                        await navigator.clipboard.writeText(text);
                                    } else {
                                        const ta = document.createElement('textarea');
                                        ta.value = text;
                                        document.body.appendChild(ta);
                                        ta.select();
                                        document.execCommand('copy');
                                        ta.remove();
                                    }
                                    if (window.SwalHelper && SwalHelper.toast) {
                                        SwalHelper.toast('Đã sao chép mã đơn', 'success');
                                    }
                                } catch (e) { }
                            });
                        }
                    }
                });
                return;
            }

            alert(data.message || 'Thành công');
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
            if (thumbsWrap && mainImg) {
                thumbsWrap.addEventListener('click', function (e) {
                    const btn = e.target.closest('.pd-thumb-btn');
                    if (!btn) return;
                    const next = btn.getAttribute('data-img');
                    if (!next) return;
                    mainImg.src = next;
                    thumbsWrap.querySelectorAll('.pd-thumb-btn').forEach((el) => el.classList.remove('is-active'));
                    btn.classList.add('is-active');
                });
            }

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
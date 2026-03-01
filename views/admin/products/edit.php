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
                <?php if (!empty($product['stock_managed'])): ?>
                    <a href="<?= url('admin/products/stock/' . $product['id']) ?>" class="btn btn-info btn-sm shadow-sm">
                        <i class="fas fa-warehouse mr-1"></i>QUẢN LÝ KHO
                    </a>
                <?php endif; ?>
            </div>

            <form action="<?= url('admin/products/edit/' . $product['id']) ?>" method="POST" id="productForm">
                <div class="card-body pt-3">
                    <style>
                        .mode-card-group {
                            display: grid;
                            grid-template-columns: repeat(3, minmax(0, 1fr));
                            gap: 12px;
                        }

                        .mode-card {
                            border: 1px solid #dbe4f0;
                            border-radius: 10px;
                            padding: 14px 12px;
                            cursor: pointer;
                            background: #fff;
                            transition: all .15s ease;
                            user-select: none;
                            position: relative;
                            margin-bottom: 0 !important;
                        }

                        .mode-card:hover {
                            border-color: #7aa7ff;
                            box-shadow: 0 4px 14px rgba(27, 84, 255, .08);
                        }

                        .mode-card.active {
                            border-color: #4f7cff;
                            background: #f0f7ff;
                            box-shadow: 0 0 0 1px #4f7cff;
                            padding-right: 40px;
                        }

                        .mode-card::after {
                            content: '';
                            position: absolute;
                            top: 50%;
                            right: 15px;
                            transform: translateY(-50%);
                            width: 20px;
                            height: 20px;
                            border: 2px solid #dbe4f0;
                            border-radius: 50%;
                            background: #fff;
                            transition: all .2s;
                        }

                        .mode-card.active::after {
                            border-color: #4f7cff;
                            background: #4f7cff;
                        }

                        .mode-card.active::before {
                            content: '\f00c';
                            font-family: 'Font Awesome 5 Free';
                            font-weight: 900;
                            position: absolute;
                            top: 50%;
                            right: 18px;
                            transform: translateY(-50%);
                            font-size: 10px;
                            color: #fff;
                            z-index: 1;
                        }

                        .mode-card input[type="radio"] {
                            display: none;
                        }

                        .mode-card-title {
                            font-weight: 700;
                            font-size: 14px;
                            color: #1f2937;
                            margin-bottom: 4px;
                        }

                        .thumb-preview-box,
                        .gallery-line-preview {
                            height: 42px;
                            border: 1px dashed #d1d5db;
                            border-radius: 8px;
                            background: #f8fafc;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            overflow: hidden;
                            padding: 4px;
                        }

                        .thumb-preview-box img,
                        .gallery-line-preview img {
                            max-height: 34px;
                            max-width: 100%;
                            object-fit: contain;
                        }

                        .thumb-preview-box span,
                        .gallery-line-preview span {
                            font-size: 11px;
                            color: #9ca3af;
                        }

                        .gallery-line {
                            margin-bottom: 12px;
                        }

                        .btn-add-gallery {
                            color: #000000ff;
                            border-color: #000000ff;
                            background: transparent;
                            transition: all 0.2s;
                            font-weight: 500;
                        }

                        .btn-add-gallery:hover {
                            background: #000000ff;
                            color: #fff;
                        }

                        @media (max-width: 991.98px) {
                            .mode-card-group {
                                grid-template-columns: 1fr;
                            }
                        }
                    </style>

                    <!-- Row 1: Tên, Slug -->
                    <div class="form-section mb-4">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold form-label-req">Tên sản phẩm</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Đường dẫn (Slug)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"
                                                id="slugPrefix">/danh-muc/</span></div>
                                        <input type="text" class="form-control" name="slug" id="slug"
                                            value="<?= htmlspecialchars($product['slug'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Loại sản phẩm, Config & SEO -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="form-section h-100 mb-0">
                                <label class="font-weight-bold d-block mb-3 text-primary"><i
                                        class="fas fa-shipping-fast mr-1"></i>LOẠI SẢN PHẨM / CÁCH GIAO HÀNG</label>
                                <?php
                                $currentMode = (string) ($product['delivery_mode'] ?? 'account_stock');
                                ?>
                                <div class="mode-card-group">
                                    <label class="mode-card <?= $currentMode === 'account_stock' ? 'active' : '' ?>"
                                        data-mode="account_stock">
                                        <input type="radio" name="sale_mode_ui" value="account_stock"
                                            <?= $currentMode === 'account_stock' ? 'checked' : '' ?>>
                                        <div class="mode-card-title"><i class="fas fa-user-lock mr-1 text-primary"></i>
                                            Tài khoản</div>
                                        <div class="mode-card-desc">Bán từ kho, giao ngay.</div>
                                    </label>
                                    <label class="mode-card <?= $currentMode === 'source_link' ? 'active' : '' ?>"
                                        data-mode="source_link">
                                        <input type="radio" name="sale_mode_ui" value="source_link"
                                            <?= $currentMode === 'source_link' ? 'checked' : '' ?>>
                                        <div class="mode-card-title"><i class="fas fa-link mr-1 text-info"></i>
                                            Source / Link</div>
                                        <div class="mode-card-desc">Bán từ kho source.</div>
                                    </label>
                                    <label class="mode-card <?= $currentMode === 'manual_info' ? 'active' : '' ?>"
                                        data-mode="manual_info">
                                        <input type="radio" name="sale_mode_ui" value="manual_info"
                                            <?= $currentMode === 'manual_info' ? 'checked' : '' ?>>
                                        <div class="mode-card-title"><i class="fas fa-keyboard mr-1 text-warning"></i>
                                            Yêu cầu info</div>
                                        <div class="mode-card-desc">Khách nhập form.</div>
                                    </label>
                                </div>

                                <!-- KHUNG CẤU HÌNH GIAO HÀNG -->
                                <div id="delivery-config-box" class="mt-3">
                                    <div class="p-3 border rounded shadow-sm"
                                        style="background: #f8fafc; border: 2px dashed #cbd5e1 !important;">
                                        <!-- Section Kho -->
                                        <div id="section-stock-info">
                                            <h6 class="font-weight-bold mb-2 text-primary small"><i
                                                    class="fas fa-box-open mr-1"></i> THÔNG TIN KHO</h6>
                                            <div class="alert alert-info py-2 mb-0" style="font-size: 12px;">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Quản lý tại trang riêng.
                                                <a href="<?= url('admin/products/stock/' . $product['id']) ?>"
                                                    class="btn btn-xs btn-primary ml-1">
                                                    Vào Kho
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Section Link -->
                                        <div id="section-link"
                                            style="<?= $currentMode !== 'source_link' ? 'display: none;' : '' ?>">
                                            <h6 class="font-weight-bold mb-2 text-info small"><i
                                                    class="fas fa-link mr-1"></i> CẤU HÌNH LINK</h6>
                                            <input type="text" class="form-control form-control-sm" name="source_link"
                                                id="source_link"
                                                value="<?= htmlspecialchars((string) ($product['source_link'] ?? '')) ?>"
                                                placeholder="https://..."
                                                <?= $currentMode === 'source_link' ? '' : 'disabled' ?>>
                                        </div>

                                        <!-- Section Manual Info -->
                                        <div id="section-info"
                                            style="<?= $currentMode !== 'manual_info' ? 'display: none;' : '' ?>">
                                            <h6 class="font-weight-bold mb-2 text-warning small"><i
                                                    class="fas fa-user-edit mr-1"></i> YÊU CẦU INFO</h6>
                                            <textarea class="form-control" name="info_instructions"
                                                id="info_instructions" rows="3" <?= $currentMode === 'manual_info' ? '' : 'disabled' ?>
                                                placeholder="Ví dụ: Nhập UID game..."><?= htmlspecialchars($product['info_instructions'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="product_type" id="productType"
                                    value="<?= htmlspecialchars($productType) ?>">
                                <input type="hidden" name="requires_info" id="requires_info"
                                    value="<?= (int) $product['requires_info'] ?>">

                                <!-- QUY ĐỊNH SỐ LƯỢNG MUA -->
                                <div class="row mt-3">
                                    <div class="col-md-4 col-12 mb-2 mb-md-0">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold small">Mua tối thiểu</label>
                                            <input type="number" class="form-control form-control-sm"
                                                name="min_purchase_qty"
                                                value="<?= (int) ($product['min_purchase_qty'] ?? 1) ?>" min="1"
                                                step="1">
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-12 mb-2 mb-md-0">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold small">Mua tối đa</label>
                                            <input type="number" class="form-control form-control-sm"
                                                name="max_purchase_qty"
                                                value="<?= (int) ($product['max_purchase_qty'] ?? 0) ?>" min="0"
                                                step="1">
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold small" id="stockLabel">Stock</label>
                                            <input type="number" class="form-control form-control-sm"
                                                name="manual_stock" id="stockPreviewInput"
                                                data-account-stock="<?= (int) ($accountStockCount ?? 0) ?>"
                                                value="<?= $currentMode === 'manual_info' ? (int) ($product['manual_stock'] ?? 0) : ($currentMode === 'source_link' ? 'Unlimited' : (int) ($accountStockCount ?? 0)) ?>"
                                                <?= $currentMode === 'manual_info' ? '' : 'readonly' ?>>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: Giá, Trạng thái, Danh mục, Thứ tự -->
                        <div class="form-section mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold form-label-req">Giá bán (VNĐ)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control text-success font-weight-bold"
                                                name="price_vnd" value="<?= (int) ($product['price_vnd'] ?? 0) ?>"
                                                min="0" required>
                                            <div class="input-group-append"><span class="input-group-text">đ</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Trạng thái hiển thị</label>
                                        <select class="form-control font-weight-bold" name="status">
                                            <option value="ON" <?= ($product['status'] ?? 'ON') === 'ON' ? 'selected' : '' ?>>
                                                HIỂN THỊ (ON)</option>
                                            <option value="OFF" <?= ($product['status'] ?? '') === 'OFF' ? 'selected' : '' ?>>
                                                ẨN (OFF)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Danh mục</label>
                                        <select class="form-control" name="category_id" id="category_id" required>
                                            <option value="0">— Chọn danh mục —</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= (int) $cat['id'] ?>"
                                                    data-slug="<?= htmlspecialchars((string) ($cat['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= ((int) ($product['category_id'] ?? 0) === (int) $cat['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Thứ tự</label>
                                        <input type="number" class="form-control" name="display_order"
                                            value="<?= (int) ($product['display_order'] ?? 0) ?>" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Row 5: Ảnh (Thumbnail & Gallery) -->
                        <div class="form-section mb-4">
                            <div class="row align-items-center mb-4">
                                <div class="col-md-9">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold">Ảnh sản phẩm (Thumbnail)</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="image" name="image"
                                                value="<?= htmlspecialchars($product['image'] ?? '') ?>">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-primary"
                                                    style="background-color: #6f42c1; border-color: #6f42c1;"
                                                    onclick="openImageManager && openImageManager('image')">Chọn
                                                    ảnh</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-2 mt-md-0">
                                    <div class="thumb-preview-box">
                                        <img id="imagePreview" src="<?= htmlspecialchars($product['image'] ?? '') ?>"
                                            alt=""
                                            style="display: <?= empty($product['image']) ? 'none' : 'inline-block' ?>;">
                                        <span id="noImage"
                                            style="display: <?= empty($product['image']) ? 'inline-block' : 'none' ?>;">Xem
                                            trước</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="font-weight-bold mb-0">Ảnh Gallery</label>
                                    <button type="button" class="btn btn-add-gallery btn-sm" onclick="addGalleryItem()">
                                        <i class="fas fa-plus mr-1"></i>Thêm ảnh
                                    </button>
                                </div>
                                <div id="gallery-container">
                                    <?php foreach ($galleryArr as $i => $gUrl): ?>
                                        <div class="gallery-line mb-3" id="gallery-row-<?= (int) $i ?>">
                                            <div class="row align-items-center">
                                                <div class="col-md-9">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" class="form-control" name="gallery[]"
                                                            id="gallery-input-<?= (int) $i ?>"
                                                            value="<?= htmlspecialchars($gUrl) ?>">
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-primary"
                                                                style="background-color: #6f42c1; border-color: #6f42c1;"
                                                                onclick="openImageManager('gallery-input-<?= (int) $i ?>')">
                                                                <i class="fas fa-images"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-danger"
                                                                onclick="removeGalleryItem(<?= (int) $i ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 mt-2 mt-md-0">
                                                    <div class="gallery-line-preview">
                                                        <img class="gallery-preview-img" alt="preview"
                                                            src="<?= htmlspecialchars($gUrl) ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Row 7: Mô tả -->
                        <div class="form-section mt-4">
                            <div class="form-section-title">📝 Mô tả sản phẩm</div>
                            <div class="form-group mb-0">
                                <textarea class="form-control" id="description" name="description"
                                    rows="12"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer text-right bg-transparent border-top-0 pt-0 pb-4">
                        <a href="<?= url('admin/products') ?>" class="btn btn-light border mr-2 px-4">Hủy thay đổi</a>
                        <button type="submit" class="btn btn-primary px-4 shadow">CẬP NHẬT SẢN PHẨM</button>
                    </div>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
<?php include ROOT_PATH . '/admin/image-manager-modal.php'; ?>

<script>
    // Wait for jQuery
    document.addEventListener("DOMContentLoaded", function () {
        let checkJquery = setInterval(function () {
            if (window.jQuery) {
                clearInterval(checkJquery);
                initEditScripts();
            }
        }, 100);
    });

    function initEditScripts() {
        if ($.fn.summernote) $('#description').summernote({ height: 300 });

        $('#image').on('change keyup paste', function () {
            var url = $(this).val();
            if (url) {
                $('#imagePreview').attr('src', url).show();
                $('#noImage').hide();
            } else {
                $('#imagePreview').hide();
                $('#noImage').show();
            }
        });

        $('#slug').on('input', function () { $(this).data('manual', true); });
        bindCategorySlugPrefix();

        function bindCategorySlugPrefix() {
            $('#category_id').on('change', updateSlugPrefixFromCategory);
            updateSlugPrefixFromCategory();
        }

        function updateSlugPrefixFromCategory() {
            var opt = $('#category_id option:selected');
            var catSlug = String(opt.data('slug') || '').trim();
            if (!catSlug) catSlug = 'danh-muc';
            $('#slugPrefix').text('/' + catSlug + '/');
        }

        function updateStockPreview(mode) {
            var currentMode = mode || $('input[name="sale_mode_ui"]:checked').val() || 'account_stock';
            var stockInput = $('#stockPreviewInput');
            var accountStock = Number(stockInput.data('accountStock') || 0);

            if (currentMode === 'source_link') {
                stockInput.val('Unlimited');
                return;
            }

            if (currentMode === 'manual_info') {
                return;
            }

            stockInput.val(String(accountStock));
        }

        function updateDeliveryModeUI() {
            var mode = $('input[name="sale_mode_ui"]:checked').val() || 'account_stock';
            $('.mode-card').removeClass('active');
            $('.mode-card[data-mode="' + mode + '"]').addClass('active');

            var type = 'account';
            var requiresInfo = 0;

            if (mode === 'source_link') {
                type = 'link';
                requiresInfo = 0;
            } else if (mode === 'manual_info') {
                type = 'account';
                requiresInfo = 1;
            }

            $('#productType').val(type);
            $('#requires_info').val(requiresInfo);

            // Chỉ hiện section kho khi là Tài Khoản
            var showStock = (mode === 'account_stock');
            if (showStock) {
                $('#section-stock-info').slideDown();
            } else {
                $('#section-stock-info').slideUp();
            }

            $('#section-link').toggle(mode === 'source_link');
            $('#source_link').prop('required', mode === 'source_link').prop('disabled', mode !== 'source_link');

            if (mode === 'manual_info') {
                $('#section-info').slideDown();
                $('#info_instructions').prop('disabled', false);
            } else {
                $('#section-info').slideUp();
                $('#info_instructions').prop('disabled', true);
            }

            // Cột Stock preview
            if (mode === 'source_link') {
                $('input[name="max_purchase_qty"]').val(1).prop('readonly', true).css('background-color', '#e9ecef');
                $('#stockPreviewInput').val('Unlimited').prop('readonly', true).css('background-color', '#e9ecef');
                $('#stockLabel').text('Stock (Unlimited)');
            } else if (mode === 'manual_info') {
                $('input[name="max_purchase_qty"]').prop('readonly', false).css('background-color', '');
                $('#stockPreviewInput').prop('readonly', false).css('background-color', '');
                $('#stockLabel').text('Số lượng kho');
            } else {
                var accountStock = Number($('#stockPreviewInput').data('accountStock') || 0);
                $('input[name="max_purchase_qty"]').prop('readonly', false).css('background-color', '');
                $('#stockPreviewInput').val(String(accountStock)).prop('readonly', true).css('background-color', '#e9ecef');
                $('#stockLabel').text('Tồn kho');
            }

            updateStockPreview(mode);
        }

        $('input[name="sale_mode_ui"]').on('change', updateDeliveryModeUI);
        $('.mode-card').on('click', function () {
            $(this).find('input').prop('checked', true).trigger('change');
        });
        updateDeliveryModeUI();

        // Validation: max_purchase_qty <= stock
        $('#productForm').on('submit', function (e) {
            var mode = $('input[name="sale_mode_ui"]:checked').val() || 'account_stock';
            if (mode !== 'account_stock' && mode !== 'manual_info') return true;

            var maxQty = parseInt($('input[name="max_purchase_qty"]').val(), 10) || 0;
            var stock = parseInt($('#stockPreviewInput').val(), 10) || 0;

            if (maxQty > 0 && stock > 0 && maxQty > stock) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Không hợp lệ',
                    html: '<b>Mua tối đa (' + maxQty + ')</b> không được lớn hơn <b>Tồn kho (' + stock + ')</b>.',
                    confirmButtonText: 'OK'
                });
                return false;
            }
        });
    }

    let galleryIndex = <?= count($galleryArr) ?>;
    function addGalleryItem(url) {
        url = url || '';
        const id = `gallery-input-${galleryIndex}`;
        const previewImgId = `gallery-preview-img-${galleryIndex}`;
        const previewEmptyId = `gallery-preview-empty-${galleryIndex}`;
        const rowId = `gallery-row-${galleryIndex}`;

        const html = `
            <div class="gallery-line mb-3" id="${rowId}">
                <div class="row align-items-center">
                    <div class="col-md-9">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" name="gallery[]" id="${id}" value="${escHtml(url)}" placeholder="Link ảnh...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" style="background-color: #6f42c1; border-color: #6f42c1;" onclick="openImageManager('${id}')">
                                    <i class="fas fa-images"></i>
                                </button>
                                <button type="button" class="btn btn-danger" onclick="removeGalleryItem(${galleryIndex})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mt-2 mt-md-0">
                        <div class="gallery-line-preview">
                            <img id="${previewImgId}" class="gallery-preview-img" alt="preview" style="${url ? '' : 'display: none;'} " src="${escHtml(url)}">
                            <span id="${previewEmptyId}" class="gallery-preview-empty" style="${url ? 'display: none;' : ''}">Xem trước</span>
                        </div>
                    </div>
                </div>
            </div>`;
        $('#gallery-container').append(html);
        galleryIndex++;
    }

    // Add change event for manual input in gallery
    $(document).on('change keyup paste', '.gallery-line input', function () {
        const url = $(this).val();
        const row = $(this).closest('.gallery-line');
        const img = row.find('.gallery-preview-img');
        const empty = row.find('.gallery-preview-empty');
        if (url) {
            img.attr('src', url).show();
            empty.hide();
        } else {
            img.hide();
            empty.show();
        }
    });

    function removeGalleryItem(idx) { $('#gallery-row-' + idx).remove(); }
    function escHtml(s) { return s ? String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') : ''; }
</script>

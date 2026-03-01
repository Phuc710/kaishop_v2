<?php
/**
 * Shared Product Form Partial
 * Used by add.php and edit.php
 * 
 * Variables:
 * @var array|null $product (null for add, array for edit)
 * @var array $categories
 */
$isEdit = !empty($product);
$productType = $product['product_type'] ?? 'account';
$requiresInfo = (int) ($product['requires_info'] ?? 0);
$deliveryMode = (string) ($product['delivery_mode'] ?? 'account_stock');
$galleryArr = $product['gallery_arr'] ?? [];
?>

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
        margin-bottom: 0 !important;
        position: relative;
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

    .mode-card-desc {
        font-size: 12px;
        color: #6b7280;
        line-height: 1.35;
    }

    #delivery-config-box {
        transition: all 0.3s ease;
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

    @media (max-width: 991.98px) {
        .mode-card-group {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="card-body pt-3">
    <!-- Row 1: Tên, Slug -->
    <div class="form-section mb-4">
        <div class="row">
            <div class="col-md-7">
                <div class="form-group mb-3">
                    <label class="font-weight-bold form-label-req">Tên sản phẩm</label>
                    <input type="text" class="form-control" id="name" name="name"
                        value="<?= htmlspecialchars($product['name'] ?? '') ?>" placeholder="Nhập tên sản phẩm..."
                        required>
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group mb-3">
                    <label class="font-weight-bold">Đường dẫn (Slug)</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"
                                id="slugPrefix">/danh-muc/</span></div>
                        <input type="text" class="form-control" name="slug" id="slug"
                            value="<?= htmlspecialchars($product['slug'] ?? '') ?>" placeholder="Tự động theo tên">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Giá, Thứ tự, Trạng thái, Danh mục -->
    <div class="form-section mb-4">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group mb-3">
                    <label class="font-weight-bold form-label-req">Giá bán (VNĐ)</label>
                    <div class="input-group">
                        <input type="number" class="form-control text-success font-weight-bold" name="price_vnd"
                            value="<?= (int) ($product['price_vnd'] ?? 0) ?>" placeholder="0" min="0" required>
                        <div class="input-group-append"><span class="input-group-text">đ</span></div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-3">
                    <label class="font-weight-bold">Thứ tự</label>
                    <input type="number" class="form-control" name="display_order"
                        value="<?= (int) ($product['display_order'] ?? 0) ?>" min="0">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-3">
                    <label class="font-weight-bold">Trạng thái hiển thị</label>
                    <select class="form-control font-weight-bold" name="status">
                        <option value="ON" <?= ($product['status'] ?? 'ON') === 'ON' ? 'selected' : '' ?>>HIỂN THỊ
                        </option>
                        <option value="OFF" <?= ($product['status'] ?? '') === 'OFF' ? 'selected' : '' ?>>ẨN</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label class="font-weight-bold">Danh mục</label>
                    <select class="form-control" name="category_id" id="category_id" required>
                        <option value="0" selected disabled>— Chọn danh mục —</option>
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
        </div>
    </div>

    <!-- Row 3: Loại sản phẩm, Config & SEO -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="form-section h-100 mb-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="font-weight-bold text-primary mb-0"><i class="fas fa-shipping-fast mr-1"></i>LOẠI SẢN
                        PHẨM</label>
                    <?php if ($isEdit && !empty($product['stock_managed'])): ?>
                        <a href="<?= url('admin/products/stock/' . $product['id']) ?>"
                            class="btn btn-info btn-sm shadow-sm rounded-pill px-3">
                            <i class="fas fa-warehouse mr-1"></i>QUẢN LÝ KHO
                        </a>
                    <?php endif; ?>
                </div>
                <div class="mode-card-group">
                    <label class="mode-card <?= $deliveryMode === 'account_stock' ? 'active' : '' ?>"
                        data-mode="account_stock">
                        <input type="radio" name="sale_mode_ui" value="account_stock" <?= $deliveryMode === 'account_stock' ? 'checked' : '' ?>>
                        <div class="mode-card-title"><i class="fas fa-user-lock mr-1 text-primary"></i> Tài Khoản</div>
                        <div class="mode-card-desc">Bán từ kho, giao ngay.</div>
                    </label>
                    <label class="mode-card <?= $deliveryMode === 'source_link' ? 'active' : '' ?>"
                        data-mode="source_link">
                        <input type="radio" name="sale_mode_ui" value="source_link" <?= $deliveryMode === 'source_link' ? 'checked' : '' ?>>
                        <div class="mode-card-title"><i class="fas fa-link mr-1 text-info"></i> Source / Link</div>
                        <div class="mode-card-desc">Bán từ link download.</div>
                    </label>
                    <label class="mode-card <?= $deliveryMode === 'manual_info' ? 'active' : '' ?>"
                        data-mode="manual_info">
                        <input type="radio" name="sale_mode_ui" value="manual_info" <?= $deliveryMode === 'manual_info' ? 'checked' : '' ?>>
                        <div class="mode-card-title"><i class="fas fa-keyboard mr-1 text-warning"></i> Yêu cầu info
                        </div>
                        <div class="mode-card-desc">Khách nhập form thủ công.</div>
                    </label>
                </div>

                <input type="hidden" name="product_type" id="productType" value="<?= htmlspecialchars($productType) ?>">
                <input type="hidden" name="requires_info" id="requires_info" value="<?= $requiresInfo ?>">

                <!-- KHUNG CẤU HÌNH GIAO HÀNG -->
                <div id="delivery-config-box" class="mt-3">
                    <div class="p-3 border rounded shadow-sm"
                        style="background: #f8fafc; border: 2px dashed #cbd5e1 !important;">

                        <!-- Section Kho (Add mode) -->
                        <?php if (!$isEdit): ?>
                            <div id="section-stock">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="font-weight-bold mb-0 text-primary small"><i
                                            class="fas fa-box-open mr-1"></i> NHẬP KHO SẢN PHẨM</h6>
                                    <button type="button" class="btn btn-xs btn-outline-primary"
                                        onclick="$('#stockFile').click()">
                                        <i class="fas fa-file-import mr-1"></i>File .txt
                                    </button>
                                    <input type="file" id="stockFile" style="display:none;" accept=".txt"
                                        onchange="handleStockFile(this)">
                                </div>
                                <textarea class="form-control" id="initial_stock" name="initial_stock" rows="5"
                                    style="font-family:Consolas,monospace;font-size:12px;"
                                    placeholder="Nội dung giao 1&#10;Nội dung giao 2&#10;..."></textarea>
                            </div>
                        <?php else: ?>
                            <!-- Section Kho (Edit mode) -->
                            <div id="section-stock-info"
                                style="<?= $deliveryMode !== 'account_stock' ? 'display: none;' : '' ?>">
                                <h6 class="font-weight-bold mb-2 text-primary small"><i class="fas fa-box-open mr-1"></i>
                                    THÔNG TIN KHO</h6>
                                <div class="alert alert-info py-2 mb-0 d-flex align-items-center shadow-sm"
                                    style="font-size: 13px; background: #17a2b8; color: #fff; border: none; border-radius: 8px;">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <span>Quản lý kho tại trang riêng.</span>
                                    <a href="<?= url('admin/products/stock/' . $product['id']) ?>"
                                        class="btn btn-xs btn-light ml-auto px-3 rounded-pill text-info"
                                        style="font-weight: 800; font-size: 10px;">
                                        VÀO KHO <i class="fas fa-chevron-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Section Link -->
                        <div id="section-link" style="<?= $deliveryMode !== 'source_link' ? 'display: none;' : '' ?>">
                            <h6 class="font-weight-bold mb-2 text-info small"><i class="fas fa-link mr-1"></i> CẤU HÌNH
                                LINK</h6>
                            <input type="text" class="form-control form-control-sm" name="source_link" id="source_link"
                                value="<?= htmlspecialchars((string) ($product['source_link'] ?? '')) ?>"
                                placeholder="https://..." <?= $deliveryMode === 'source_link' ? '' : 'disabled' ?>>
                        </div>

                        <!-- Section Manual Info -->
                        <div id="section-info" style="<?= $deliveryMode !== 'manual_info' ? 'display: none;' : '' ?>">
                            <h6 class="font-weight-bold mb-2 text-warning small"><i class="fas fa-user-edit mr-1"></i>
                                YÊU CẦU INFO</h6>
                            <textarea class="form-control" name="info_instructions" id="info_instructions" rows="3"
                                <?= $deliveryMode === 'manual_info' ? '' : 'disabled' ?>
                                placeholder="Ví dụ: Nhập UID game..."><?= htmlspecialchars($product['info_instructions'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- QUY ĐỊNH SỐ LƯỢNG MUA -->
                <div class="row mt-3">
                    <div class="col-md-4 col-12 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold small">Mua tối thiểu</label>
                            <input type="number" class="form-control form-control-sm" name="min_purchase_qty"
                                value="<?= (int) ($product['min_purchase_qty'] ?? 1) ?>" min="1" step="1">
                        </div>
                    </div>
                    <div class="col-md-4 col-12 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold small">Mua tối đa</label>
                            <input type="number" class="form-control form-control-sm" name="max_purchase_qty"
                                value="<?= (int) ($product['max_purchase_qty'] ?? 0) ?>" min="0" step="1">
                        </div>
                    </div>
                    <div class="col-md-4 col-12">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold small" id="stockLabel">Tồn kho</label>
                            <input type="number" class="form-control form-control-sm" name="manual_stock"
                                id="stockPreviewInput" data-account-stock="<?= (int) ($accountStockCount ?? 0) ?>"
                                value="<?= $deliveryMode === 'manual_info' ? (int) ($product['manual_stock'] ?? 0) : ($deliveryMode === 'source_link' ? 'Unlimited' : (int) ($accountStockCount ?? 0)) ?>"
                                <?= $deliveryMode === 'manual_info' ? '' : 'readonly' ?>>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="form-section h-100 mb-0">
                <div class="form-section-title">👉 Thông tin SEO / Thẻ</div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Mô tả SEO</label>
                    <textarea class="form-control" name="seo_description" rows="9"
                        placeholder="Đoạn mô tả ngắn hiển thị trên Google..."><?= htmlspecialchars($product['seo_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 5: Ảnh (Thumbnail & Gallery) -->
    <div class="form-section mb-4">
        <div class="row mb-4">
            <div class="col-12">
                <label class="font-weight-bold d-block">Ảnh sản phẩm (Thumbnail)</label>
                <div class="row align-items-center">
                    <div class="col-md-9">
                        <div class="input-group">
                            <input type="text" class="form-control" id="image" name="image"
                                value="<?= htmlspecialchars($product['image'] ?? '') ?>"
                                placeholder="Link ảnh hoặc chọn từ máy">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary px-4"
                                    style="background-color: #6f42c1; border-color: #6f42c1;"
                                    onclick="openImageManager && openImageManager('image')">Chọn ảnh</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mt-2 mt-md-0">
                        <div class="thumb-preview-box">
                            <img id="imagePreview" src="<?= htmlspecialchars($product['image'] ?? '') ?>" alt=""
                                style="<?= empty($product['image']) ? 'display:none;' : '' ?>">
                            <span id="noImage" style="<?= !empty($product['image']) ? 'display:none;' : '' ?>">Xem trước
                                ảnh đại diện</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                <label class="font-weight-bold mb-0">Ảnh trong sản phẩm (Gallery)</label>
                <button type="button" class="btn btn-add-gallery btn-sm" onclick="addGalleryItem()">
                    <i class="fas fa-plus mr-1"></i>Thêm dòng ảnh
                </button>
            </div>
            <small class="text-muted d-block mb-3">Hình ảnh chi tiết sản phẩm hiển thị dạng trượt.</small>
            <div id="gallery-container">
                <?php foreach ($galleryArr as $i => $gUrl): ?>
                    <div class="gallery-line mb-3" id="gallery-row-<?= (int) $i ?>">
                        <div class="row align-items-center">
                            <div class="col-md-9">
                                <div class="input-group">
                                    <input type="text" class="form-control gallery-url-input" name="gallery[]"
                                        id="gallery-input-<?= (int) $i ?>" value="<?= htmlspecialchars($gUrl) ?>"
                                        placeholder="Link ảnh hoặc chọn từ máy">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary"
                                            style="background-color: #6f42c1; border-color: #6f42c1;"
                                            onclick="openImageManager('gallery-input-<?= (int) $i ?>')">Chọn ảnh</button>
                                        <button type="button" class="btn btn-danger"
                                            onclick="removeGalleryItem('gallery-row-<?= (int) $i ?>')"><i
                                                class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mt-2 mt-md-0">
                                <div class="gallery-line-preview">
                                    <img id="gallery-preview-img-<?= (int) $i ?>" class="gallery-preview-img" alt="preview"
                                        src="<?= htmlspecialchars($gUrl) ?>">
                                    <span id="gallery-preview-empty-<?= (int) $i ?>" class="gallery-preview-empty"
                                        style="display: none;">Xem trước</span>
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
        <div class="form-section-title">👉 Mô tả sản phẩm</div>
        <div class="form-group mb-0">
            <textarea class="form-control" id="description" name="description"
                rows="12"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
        </div>
    </div>
</div>

<div class="card-footer text-right bg-transparent border-top-0 pt-0 pb-4">
    <a href="<?= url('admin/products') ?>" class="btn btn-light border mr-2 px-4">Hủy</a>
    <button type="submit" class="btn btn-primary px-4 shadow">
        <?= $isEdit ? 'Cập nhật' : 'Lưu' ?> sản phẩm
    </button>
</div>

<script>
    let galleryIndex = <?= count($galleryArr) ?>;

    // Wait for jQuery and DOM to be ready
    document.addEventListener("DOMContentLoaded", function () {
        let checkJquery = setInterval(function () {
            if (window.jQuery) {
                clearInterval(checkJquery);
                initFormScripts();
            }
        }, 100);
    });

    function initFormScripts() {
        if ($.fn.summernote) {
            $('#description').summernote({ height: 300 });
        }

        bindThumbPreview();
        bindSlugAutoGen();
        bindCategorySlugPrefix();
        bindSaleModeUI();
        bindGalleryLivePreview();

        // Initial setup
        var mode = getSelectedSaleMode();
        applySaleMode(mode);

        if ($('#gallery-container .gallery-line').length === 0) {
            addGalleryItem();
        }

        // Validation: max_purchase_qty <= stock
        $('#productForm').on('submit', function (e) {
            var mode = getSelectedSaleMode();
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

    function handleStockFile(input) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            const content = e.target.result;
            $('#initial_stock').val(content);
            updateStockPreview('account_stock');
            if (window.Toast) {
                Toast.fire({ icon: 'success', title: 'Đã tải nội dung từ file' });
            } else {
                Swal.fire({ icon: 'success', title: 'Thành công', text: 'Đã tải nội dung từ file', timer: 1500 });
            }
        };
        reader.readAsText(file);
        input.value = '';
    }

    function bindThumbPreview() {
        $('#initial_stock').on('input', function () {
            updateStockPreview();
        });

        $('#image').on('change keyup paste', function () {
            updateImagePreview($(this).val(), '#imagePreview', '#noImage', 'Xem trước ảnh đại diện');
        });
    }

    function bindSlugAutoGen() {
        $('#name').on('keyup change', function () {
            if (!$('#slug').data('manual')) {
                $('#slug').val(toSlug($(this).val()));
            }
        });
        $('#slug').on('input', function () {
            $(this).data('manual', true);
        });
    }

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

    function bindSaleModeUI() {
        $(document).on('click', '.mode-card', function () {
            var mode = $(this).data('mode');
            $(this).find('input[type="radio"]').prop('checked', true);
            applySaleMode(mode);
        });

        $(document).on('change', 'input[name="sale_mode_ui"]', function () {
            applySaleMode($(this).val());
        });
    }

    function getSelectedSaleMode() {
        return $('input[name="sale_mode_ui"]:checked').val() || 'account_stock';
    }

    function countStockPreviewItems(rawText) {
        var seen = {};
        var count = 0;
        String(rawText || '').split(/\r?\n/).forEach(function (line) {
            var normalized = String(line || '').trim();
            if (!normalized || seen[normalized]) return;
            seen[normalized] = true;
            count++;
        });
        return count;
    }

    function updateStockPreview(mode) {
        var currentMode = mode || getSelectedSaleMode();
        var stockInput = $('#stockPreviewInput');

        if (currentMode === 'source_link') {
            stockInput.val('Unlimited');
            return;
        }

        if (currentMode === 'manual_info') {
            return;
        }

        // For add mode, count from textarea. For edit mode, it's already set from PHP but we might need to handle updates if it's dynamic.
        // Actually for edit mode, 'account_stock' is readonly and shows existing count.
        if ($('#initial_stock').length) {
            stockInput.val(String(countStockPreviewItems($('#initial_stock').val())));
        } else {
            var accountStock = Number(stockInput.data('accountStock') || 0);
            stockInput.val(String(accountStock));
        }
    }

    function applySaleMode(mode) {
        mode = mode || 'account_stock';

        $('.mode-card').removeClass('active');
        $('.mode-card[data-mode="' + mode + '"]').addClass('active');

        var productType = 'account';
        var requiresInfo = '0';

        if (mode === 'source_link') {
            productType = 'link';
            requiresInfo = '0';
        } else if (mode === 'manual_info') {
            productType = 'account';
            requiresInfo = '1';
        }

        $('#productType').val(productType);
        $('#requires_info').val(requiresInfo);

        // Visibility
        $('#section-stock').toggle(mode === 'account_stock');
        $('#section-stock-info').toggle(mode === 'account_stock');
        $('#initial_stock').prop('disabled', mode !== 'account_stock');

        $('#section-link').toggle(mode === 'source_link');
        $('#source_link').prop('required', mode === 'source_link').prop('disabled', mode !== 'source_link');

        $('#section-info').toggle(mode === 'manual_info');
        $('#info_instructions').prop('disabled', mode !== 'manual_info');

        // Stock preview behaviors
        if (mode === 'source_link') {
            $('input[name="max_purchase_qty"]').val(1).prop('readonly', true).css('background-color', '#e9ecef');
            $('#stockPreviewInput').val('Unlimited').prop('readonly', true).css('background-color', '#e9ecef');
            $('#stockLabel').text('Stock (Unlimited)');
        } else if (mode === 'manual_info') {
            $('input[name="max_purchase_qty"]').prop('readonly', false).css('background-color', '');
            $('#stockPreviewInput').prop('readonly', false).css('background-color', '');
            $('#stockLabel').text('Số lượng Stock');
        } else {
            $('input[name="max_purchase_qty"]').prop('readonly', false).css('background-color', '');
            $('#stockPreviewInput').prop('readonly', true).css('background-color', '#e9ecef');
            $('#stockLabel').text('Tồn kho');
            updateStockPreview(mode);
        }
    }

    function bindGalleryLivePreview() {
        $(document).on('change keyup paste', '.gallery-url-input', function () {
            var row = $(this).closest('.gallery-line');
            var img = row.find('.gallery-preview-img');
            var empty = row.find('.gallery-preview-empty');
            updateImagePreview($(this).val(), img, empty, 'Xem trước ảnh');
        });
    }

    function updateImagePreview(url, imgSelector, emptySelector, emptyText) {
        var img = (imgSelector instanceof jQuery) ? imgSelector : $(imgSelector);
        var empty = (emptySelector instanceof jQuery) ? emptySelector : $(emptySelector);
        url = String(url || '').trim();

        if (url) {
            img.attr('src', url).show();
            empty.hide();
        } else {
            img.attr('src', '').hide();
            empty.text(emptyText || 'Xem trước').show();
        }
    }

    function addGalleryItem(url) {
        url = url || '';
        var currentIdx = galleryIndex++;
        var inputId = 'gallery-input-' + currentIdx;
        var rowId = 'gallery-row-' + currentIdx;
        var previewImgId = 'gallery-preview-img-' + currentIdx;
        var previewEmptyId = 'gallery-preview-empty-' + currentIdx;

        var html = ''
            + '<div class="gallery-line mb-3" id="' + rowId + '">'
            + '  <div class="row align-items-center">'
            + '    <div class="col-md-9">'
            + '      <div class="input-group">'
            + '        <input type="text" class="form-control gallery-url-input" name="gallery[]" id="' + inputId + '" value="' + escHtml(url) + '" placeholder="Link ảnh hoặc chọn từ máy">'
            + '        <div class="input-group-append">'
            + '          <button type="button" class="btn btn-primary" style="background-color: #6f42c1; border-color: #6f42c1;" onclick="openImageManager(\'' + inputId + '\')">Chọn ảnh</button>'
            + '          <button type="button" class="btn btn-danger" onclick="removeGalleryItem(\'' + rowId + '\')"><i class="fas fa-trash"></i></button>'
            + '        </div>'
            + '      </div>'
            + '    </div>'
            + '    <div class="col-md-3 mt-2 mt-md-0">'
            + '      <div class="gallery-line-preview">'
            + '        <img id="' + previewImgId + '" class="gallery-preview-img" alt="preview" style="' + (url ? '' : 'display:none;') + '" src="' + escHtml(url) + '">'
            + '        <span id="' + previewEmptyId + '" class="gallery-preview-empty" style="' + (url ? 'display:none;' : '') + '">Xem trước</span>'
            + '      </div>'
            + '    </div>'
            + '  </div>'
            + '</div>';

        $('#gallery-container').append(html);
    }

    function removeGalleryItem(rowId) {
        $('#' + rowId).remove();
        if ($('#gallery-container .gallery-line').length === 0) {
            addGalleryItem();
        }
    }

    function escHtml(value) {
        return value ? String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') : '';
    }

    function toSlug(str) {
        str = String(str || '').toLowerCase();
        if (typeof str.normalize === 'function') {
            str = str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        str = str.replace(/đ/g, 'd');
        return str
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }
</script>
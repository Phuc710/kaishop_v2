<?php
/**
 * View: Dedicated ChatGPT Product Edit
 * Optimized for ChatGPT Business Module
 */
$isEdit = !empty($product);
$pageTitle = $isEdit ? 'Sửa sản phẩm GPT' : 'Tạo sản phẩm GPT Business';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/gpt-business/farms')],
    ['label' => $isEdit ? 'Sửa sản phẩm' : 'Thêm mới'],
];
$adminNeedsSummernote = true;
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';

$product = $product ?? [];
$categories = $categories ?? [];
$galleryArr = !empty($product['gallery']) ? json_decode((string) $product['gallery'], true) : [];
if (!is_array($galleryArr))
    $galleryArr = [];

// Prepare data for JS
$visibilityMode = (string) ($product['visibility_mode'] ?? 'both');
?>

<style>
    .gptb-edit-container {
        margin-top: 20px;
        padding-bottom: 40px;
    }

    .mode-card-static {
        border: 2px solid #4f7cff;
        background: #f0f7ff;
        border-radius: 10px;
        padding: 14px 12px;
        position: relative;
        box-shadow: 0 0 0 1px #4f7cff;
    }

    .mode-card-static::after {
        content: '\f00c';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        top: 50%;
        right: 18px;
        transform: translateY(-50%);
        font-size: 10px;
        color: #fff;
        background: #4f7cff;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
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

    .admin-chatgpt-page .content-header {
        display: none;
    }

    .gptb-card-header-main {
        background: #fff !important;
        color: #212529 !important;
        border-bottom: 1px solid #ebedf2 !important;
        padding: 15px 20px !important;
    }

    .gptb-title-with-bar {
        border-left: 4px solid #6610f2;
        padding-left: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .btn-gptb-save {
        background-color: #ffc107 !important;
        color: #000 !important;
        border: none !important;
        font-weight: 800 !important;
        border-radius: 8px !important;
        transition: all 0.3s ease;
    }
</style>

<div class="container-fluid gptb-edit-container">
    <form action="<?= url('admin/gpt-business/product') ?>" method="POST" id="productForm">
        <input type="hidden" name="csrf_token" value="<?= function_exists('csrf_token') ? csrf_token() : '' ?>">
        <input type="hidden" name="id" value="<?= (int) ($product['id'] ?? 0) ?>">

        <div class="row">
            <div class="col-lg-12">
                <div class="card custom-card shadow-sm">
                    <div
                        class="card-header gptb-card-header-main border-0 d-flex justify-content-between align-items-center">
                        <span class="gptb-title-with-bar">
                            <?= $isEdit ? 'CẬP NHẬT: <span class="text-warning">' . htmlspecialchars($product['name']) . '</span>' : 'TẠO SẢN PHẨM GPT MỚI' ?>
                        </span>
                    </div>

                    <div class="card-body pt-4">
                        <!-- Tên & Slug -->
                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Tên sản phẩm <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="name" name="name"
                                        value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Đường dẫn (Slug)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">/</span></div>
                                        <input type="text" class="form-control" name="slug" id="slug"
                                            value="<?= htmlspecialchars($product['slug'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Giá & Logic % -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Giá bán (VNĐ)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-success font-weight-bold"
                                            name="price_vnd" id="price_vnd"
                                            value="<?= (int) ($product['price_vnd'] ?? 0) ?>" required>
                                        <div class="input-group-append"><span class="input-group-text">đ</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Giá gốc ảo (VNĐ)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-muted" name="old_price"
                                            id="old_price" value="<?= (int) ($product['old_price'] ?? 0) ?>">
                                        <div class="input-group-append"><span class="input-group-text">đ</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold text-danger">Giảm (%)</label>
                                    <input type="number" class="form-control text-danger font-weight-bold"
                                        id="discount_percent" readonly>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Thứ tự</label>
                                    <input type="number" class="form-control" name="display_order"
                                        value="<?= (int) ($product['display_order'] ?? 0) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Hiển thị</label>
                                    <select class="form-control" name="visibility_mode">
                                        <option value="both" <?= $visibilityMode === 'both' ? 'selected' : '' ?>>Web +
                                            Telegram</option>
                                        <option value="web" <?= $visibilityMode === 'web' ? 'selected' : '' ?>>Chỉ Web
                                        </option>
                                        <option value="telegram" <?= $visibilityMode === 'telegram' ? 'selected' : '' ?>>
                                            Chỉ Telegram</option>
                                        <option value="hidden" <?= $visibilityMode === 'hidden' ? 'selected' : '' ?>>Ẩn cả
                                            2</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <!-- Left Col: Type & Info -->
                            <div class="col-md-6">
                                <label class="font-weight-bold text-primary mb-3"><i
                                        class="fas fa-shipping-fast mr-1"></i>CHẾ ĐỘ GIAO HÀNG</label>

                                <div class="form-group mb-3">
                                    <?php $currentMode = 'business_invite_auto'; ?>
                                    <input type="hidden" name="delivery_mode" value="business_invite_auto">
                                    <input type="hidden" name="auto_invite" value="1">
                                    <div class="mode-card-static">
                                        <div class="font-weight-bold text-primary mb-1">⚡ GPT Business (Auto Invite)
                                        </div>
                                        <div class="text-muted small mb-0">Trang này luôn dùng auto invite vào farm. Hệ
                                            thống tự động điều phối để tối ưu tài khoản.</div>
                                    </div>
                                </div>

                                <!-- Automated Invitation Config (Auto-coordinated) -->
                                <div id="auto_invite_config" class="p-3 border rounded bg-light mb-3">
                                    <h6 class="font-weight-bold text-success mb-2"><i class="fas fa-magic mr-1"></i> Cấu
                                        hình Farm</h6>
                                    <p class="text-muted small mb-3">Sản phẩm này sử dụng chế độ <strong>Điều phối tự
                                            động</strong>. Hệ thống sẽ tự tìm Farm còn chỗ để gửi lời mời.</p>
                                    <input type="hidden" name="farm_id" value="0">

                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group mb-0">
                                                <label class="small font-weight-bold">Thời hạn member (Ngày)</label>
                                                <input type="number" class="form-control form-control-sm"
                                                    name="duration_days"
                                                    value="<?= (int) ($product['duration_days'] ?? 30) ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="font-weight-bold small">Hướng dẫn nhập thông tin</label>
                                    <textarea name="info_instructions" class="form-control" rows="3"
                                        placeholder="VD: Nhập email ChatGPT của bạn để nhận lời mời vào Farm..."><?= htmlspecialchars($product['info_instructions'] ?? '') ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-group"><label class="small">Mua tối thiểu</label>
                                            <input type="number" class="form-control form-control-sm"
                                                name="min_purchase_qty"
                                                value="<?= (int) ($product['min_purchase_qty'] ?? 1) ?>">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group"><label class="small">Mua tối đa</label>
                                            <input type="number" class="form-control form-control-sm"
                                                name="max_purchase_qty"
                                                value="<?= (int) ($product['max_purchase_qty'] ?? 0) ?>">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group"><label class="small">Stock</label>
                                            <input type="hidden" name="manual_stock" value="0">
                                            <input type="text" class="form-control form-control-sm" value="Kho Auto"
                                                readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Col: Category & SEO -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Danh mục</label>
                                    <select class="form-control" name="category_id">
                                        <?php foreach ($categories as $cat):
                                            $isSelected = false;
                                            if ($isEdit) {
                                                $isSelected = ((int) $product['category_id'] === (int) $cat['id']);
                                            } else {
                                                $isSelected = (trim($cat['name']) === 'ChatGPT Business');
                                            }
                                            ?>
                                            <option value="<?= (int) $cat['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold">Mô tả SEO</label>
                                    <textarea class="form-control" name="seo_description" rows="6"
                                        placeholder="Hiển thị trên kết quả tìm kiếm Google..."><?= htmlspecialchars($product['seo_description'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Ảnh Thumbnail -->
                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Ảnh sản phẩm (Thumbnail)</label>
                            <div class="row align-items-center">
                                <div class="col-md-9">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="image" name="image"
                                            value="<?= htmlspecialchars($product['image'] ?? '') ?>"
                                            placeholder="Link ảnh hoặc chọn từ máy">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary"
                                                onclick="openImageManager && openImageManager('image')">Chọn
                                                ảnh</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-2 mt-md-0">
                                    <div class="thumb-preview-box">
                                        <img id="imagePreview" src="<?= htmlspecialchars($product['image'] ?? '') ?>"
                                            style="<?= empty($product['image']) ? 'display:none;' : '' ?>">
                                        <span id="noImage"
                                            style="<?= !empty($product['image']) ? 'display:none;' : '' ?>">Xem
                                            trước</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gallery -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="font-weight-bold mb-0">Ảnh chi tiết (Gallery)</label>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3"
                                    onclick="addGalleryItem()">
                                    <i class="fas fa-plus mr-1"></i> Thêm ảnh
                                </button>
                            </div>
                            <div id="gallery-container">
                                <?php foreach ($galleryArr as $i => $gUrl): ?>
                                    <div class="gallery-line mb-3" id="gallery-row-<?= $i ?>">
                                        <div class="row align-items-center">
                                            <div class="col-md-9">
                                                <div class="input-group">
                                                    <input type="text" class="form-control gallery-url-input"
                                                        name="gallery[]" id="gallery-input-<?= $i ?>"
                                                        value="<?= htmlspecialchars($gUrl) ?>">
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-primary"
                                                            onclick="openImageManager('gallery-input-<?= $i ?>')">Chọn</button>
                                                        <button type="button" class="btn btn-danger"
                                                            onclick="removeGalleryItem('gallery-row-<?= $i ?>')"><i
                                                                class="fas fa-trash"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mt-2 mt-md-0">
                                                <div class="gallery-line-preview">
                                                    <img id="gallery-preview-img-<?= $i ?>"
                                                        src="<?= htmlspecialchars($gUrl) ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Mô tả Summernote -->
                        <div class="form-group mb-0">
                            <label class="font-weight-bold">👉 Mô tả sản phẩm</label>
                            <textarea name="description" id="description"
                                class="form-control summernote"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-0 text-right py-4">
                        <button type="submit" class="btn btn-gptb-save px-5 shadow-lg">
                            <i class="fas fa-save mr-1"></i> <?= $isEdit ? 'LƯU THAY ĐỔI' : 'TẠO NGAY' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    let galleryIndex = <?= count($galleryArr) ?>;

    $(document).ready(function () {
        if ($.fn.summernote) {
            $('.summernote').summernote({ height: 400 });
        }

        // Pricing % logic
        function calcDiscount() {
            let p = parseInt($('#price_vnd').val()) || 0;
            let o = parseInt($('#old_price').val()) || 0;
            if (o > 0 && p < o) {
                $('#discount_percent').val(Math.round(((o - p) / o) * 100));
            } else {
                $('#discount_percent').val(0);
            }
        }
        $('#price_vnd, #old_price').on('input', calcDiscount);
        calcDiscount();

        // Image logic
        $('#image').on('input change', function () {
            let val = $(this).val().trim();
            if (val) { $('#imagePreview').attr('src', val).show(); $('#noImage').hide(); }
            else { $('#imagePreview').hide(); $('#noImage').show(); }
        });

        $(document).on('input change', '.gallery-url-input', function () {
            let val = $(this).val().trim();
            $(this).closest('.gallery-line').find('img').attr('src', val).show();
        });

        // Slug auto gen
        $('#name').on('input', function () {
            if (!$('#slug').data('touched')) {
                $('#slug').val(toSlug($(this).val()));
            }
        });
        $('#slug').on('input', function () { $(this).data('touched', true); });

    });

    function addGalleryItem() {
        let i = galleryIndex++;
        let html = `
    <div class="gallery-line mb-3" id="gallery-row-${i}">
        <div class="row align-items-center">
            <div class="col-md-9">
                <div class="input-group">
                    <input type="text" class="form-control gallery-url-input" name="gallery[]" id="gallery-input-${i}" placeholder="ChÃƒÆ’Ã‚Â¨n link ÃƒÂ¡Ã‚ÂºÃ‚Â£nh...">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-primary" onclick="openImageManager('gallery-input-${i}')">ChÃƒÂ¡Ã‚Â»Ã‚Ân</button>
                        <button type="button" class="btn btn-danger" onclick="removeGalleryItem('gallery-row-${i}')"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mt-2 mt-md-0">
                <div class="gallery-line-preview"><img style="display:none;"></div>
            </div>
        </div>
    </div>`;
        $('#gallery-container').append(html);
    }

    function removeGalleryItem(id) { $('#' + id).remove(); }

    function toSlug(s) {
        s = s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, "").replace(/Ãƒâ€žÃ¢â‚¬Ëœ/g, "d");
        return s.replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').replace(/-+/g, '-');
    }
    function addGalleryItem(url) {
        url = url || '';
        let i = galleryIndex++;
        let html = `
    <div class="gallery-line mb-3" id="gallery-row-${i}">
        <div class="row align-items-center">
            <div class="col-md-9">
                <div class="input-group">
                    <input type="text" class="form-control gallery-url-input" name="gallery[]" id="gallery-input-${i}" value="${escHtml(url)}" placeholder="Chen link anh...">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-primary" onclick="openImageManager('gallery-input-${i}')">Chon</button>
                        <button type="button" class="btn btn-danger" onclick="removeGalleryItem('gallery-row-${i}')"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mt-2 mt-md-0">
                <div class="gallery-line-preview"><img src="${escHtml(url)}" style="${url ? '' : 'display:none;'}"></div>
            </div>
        </div>
    </div>`;
        $('#gallery-container').append(html);
    }

    function escHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
</script>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
<?php if (file_exists(ROOT_PATH . '/admin/image-manager-modal.php'))
    include ROOT_PATH . '/admin/image-manager-modal.php'; ?>
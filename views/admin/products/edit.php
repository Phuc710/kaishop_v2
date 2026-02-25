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
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';

$galleryArr = $product['gallery_arr'] ?? [];
$productType = $product['product_type'] ?? 'account';
?>

<section class="content pb-4 mt-3">
    <div class="row justify-content-center">
        <div class="col-md-11">
            <div class="card custom-card">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold text-uppercase mb-0">
                        CẬP NHẬT: <span class="text-primary"><?= htmlspecialchars($product['name']) ?></span>
                    </h3>
                    <?php if ($productType === 'account'): ?>
                        <a href="<?= url('admin/products/stock/' . $product['id']) ?>"
                            class="btn btn-info btn-sm shadow-sm">
                            <i class="fas fa-warehouse mr-1"></i>QUẢN LÝ KHO
                        </a>
                    <?php endif; ?>
                </div>

                <form action="<?= url('admin/products/edit/' . $product['id']) ?>" method="POST" id="productForm">
                    <div class="card-body pt-3">

                        <!-- ===== HÀNG 1: Tên, Slug, Giá ===== -->
                        <div class="form-section mb-4">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold form-label-req">Tên sản phẩm</label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Đường dẫn (Slug)</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">/p/</span>
                                            </div>
                                            <input type="text" class="form-control" name="slug" id="slug"
                                                value="<?= htmlspecialchars($product['slug'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
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
                            </div>
                        </div>

                        <!-- ===== HÀNG 2: Trạng thái, Loại, Danh mục, Thứ tự ===== -->
                        <div class="form-section mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Trạng thái hiển thị</label>
                                        <select class="form-control font-weight-bold" name="status">
                                            <option value="ON" <?= ($product['status'] ?? 'ON') === 'ON' ? 'selected' : '' ?>>HIỂN THỊ (ON)</option>
                                            <option value="OFF" <?= ($product['status'] ?? '') === 'OFF' ? 'selected' : '' ?>>ẨN (OFF)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Loại sản phẩm</label>
                                        <select class="form-control font-weight-bold btn-outline-primary"
                                            name="product_type" id="productType">
                                            <option value="account" <?= $productType === 'account' ? 'selected' : '' ?>>🔑 Tài khoản</option>
                                            <option value="link" <?= $productType === 'link' ? 'selected' : '' ?>>🔗 Source Link</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Danh mục</label>
                                        <select class="form-control" name="category_id">
                                            <option value="0">— Chọn danh mục —</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= (int) $cat['id'] ?>" <?= ((int) ($product['category_id'] ?? 0) === (int) $cat['id']) ? 'selected' : '' ?>>
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

                        <!-- ===== HÀNG 3: Ảnh Thumbnail ===== -->
                        <div class="form-section mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold">Ảnh sản phẩm (Thumbnail)</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="image" name="image"
                                                value="<?= htmlspecialchars($product['image'] ?? '') ?>">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-primary"
                                                    onclick="openImageManager && openImageManager('image')">Chọn ảnh</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-1 bg-light text-center"
                                        style="height: 50px; display: flex; align-items: center; justify-content: center; margin-top: 30px;">
                                        <img id="imagePreview"
                                            src="<?= htmlspecialchars($product['image'] ?? '') ?>" alt=""
                                            style="max-height: 40px; max-width: 100%; display: <?= empty($product['image']) ? 'none' : 'inline-block' ?>;">
                                        <span id="noImage" class="text-muted small"
                                            style="display: <?= empty($product['image']) ? 'inline-block' : 'none' ?>;">Xem trước</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ===== HÀNG 4: Gallery Ảnh phụ ===== -->
                        <div class="form-section mb-4">
                            <label class="font-weight-bold mb-2">Ảnh Gallery</label>
                            <div id="gallery-container" class="row no-gutters">
                                <?php foreach ($galleryArr as $i => $gUrl): ?>
                                    <div class="col-md-4 p-1 gallery-item" id="gallery-row-<?= (int) $i ?>">
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" name="gallery[]" id="gallery-input-<?= (int) $i ?>"
                                                value="<?= htmlspecialchars($gUrl) ?>">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-primary" onclick="openImageManager('gallery-input-<?= (int) $i ?>')">
                                                    <i class="fas fa-images"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger"
                                                    onclick="removeGalleryItem(<?= (int) $i ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-outline-info btn-sm mt-2"
                                onclick="addGalleryItem()">
                                <i class="fas fa-plus mr-1"></i>Thêm ảnh phụ
                            </button>
                        </div>

                        <!-- ===== REQUIRES INFO & STOCK CONFIG ===== -->
                        <div class="row">
                            <div class="col-md-6">
                                <?php if ($productType === 'account'): ?>
                                    <div class="form-section" id="section-stock-info" style="background: #f0f7ff; border: 1px dashed #cfe2ff; height: 100%;">
                                        <div class="form-section-title">📦 Kho tài khoản</div>
                                        <div class="alert alert-info py-2 m-2">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Quản lý nội dung tại
                                            <a href="<?= url('admin/products/stock/' . $product['id']) ?>"
                                                class="font-weight-bold text-dark">Trang kho</a>.
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="form-section" id="section-link"
                                    style="<?= $productType !== 'link' ? 'display:none;' : '' ?> background: #f8f9fa; border: 1px dashed #dee2e6; height: 100%;">
                                    <div class="form-section-title">🔗 Link Download</div>
                                    <div class="form-group mb-0 p-2">
                                        <input type="text" class="form-control" name="source_link"
                                            id="source_link" value="<?= htmlspecialchars($product['source_link'] ?? '') ?>"
                                            placeholder="https://mega.nz/file/..." <?= $productType === 'link' ? 'required' : '' ?>>
                                        <small class="text-muted">Link giao tự động khi khách mua.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-section" style="background: #fff9f0; border: 1px dashed #ffeeba; height: 100%;">
                                    <div class="form-section-title text-warning">🛠️ Yêu cầu thông tin khách</div>
                                    <div class="p-2">
                                        <div class="custom-control custom-switch mb-2">
                                            <input type="checkbox" class="custom-control-input" id="requires_info" name="requires_info" value="1" <?= ($product['requires_info'] ?? 0) == 1 ? 'checked' : '' ?>>
                                            <label class="custom-control-label font-weight-bold" for="requires_info">Yêu cầu thông tin khi mua</label>
                                        </div>
                                        <p class="small text-muted mb-2">Nếu bật, đơn hàng sẽ ở trạng thái <b>Pending</b> chờ bạn giao thủ công.</p>
                                        <textarea class="form-control" name="info_instructions" rows="3" 
                                            placeholder="VD: Nhập tên miền cần đăng ký, User ID game..."><?= htmlspecialchars($product['info_instructions'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ===== MÔ TẢ & SEO ===== -->
                        <div class="row mt-4">
                            <div class="col-md-8">
                                <div class="form-section">
                                    <div class="form-section-title">📝 Mô tả sản phẩm</div>
                                    <div class="form-group mb-0">
                                        <textarea class="form-control" id="description" name="description"
                                            rows="12"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-section">
                                    <div class="form-section-title">🔍 Mô tả SEO (Meta Description)</div>
                                    <div class="form-group mb-3">
                                        <textarea class="form-control" name="seo_description"
                                            rows="10"><?= htmlspecialchars($product['seo_description'] ?? '') ?></textarea>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold">Badge Text</label>
                                        <input type="text" class="form-control" name="badge_text"
                                            value="<?= htmlspecialchars($product['badge_text'] ?? '') ?>"
                                            placeholder="NEW / HOT">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer text-right bg-transparent border-top-0 pt-0 pb-4">
                        <a href="<?= url('admin/products') ?>" class="btn btn-light border mr-2 px-4">
                            Hủy
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            LƯU THAY ĐỔI
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
<?php include ROOT_PATH . '/admin/image-manager-modal.php'; ?>

<script>
    $(function () {
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

        $('#productType').on('change', function () {
            var type = $(this).val();
            if (type === 'link') {
                $('#section-link').slideDown();
                $('#section-stock-info').slideUp();
                $('#source_link').prop('required', true);
            } else {
                $('#section-link').slideUp();
                $('#section-stock-info').slideDown();
                $('#source_link').prop('required', false);
            }
        });
    });

    let galleryIndex = <?= count($galleryArr) ?>;
    function addGalleryItem(url) {
        url = url || '';
        const id = `gallery-input-${galleryIndex}`;
        const html = `
            <div class="col-md-4 p-1 gallery-item" id="gallery-row-${galleryIndex}">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" name="gallery[]" id="${id}" value="${escHtml(url)}" placeholder="Link ảnh...">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-primary" onclick="openImageManager('${id}')">
                            <i class="fas fa-images"></i>
                        </button>
                        <button type="button" class="btn btn-danger" onclick="removeGalleryItem(${galleryIndex})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>`;
        $('#gallery-container').append(html);
        galleryIndex++;
    }
    function removeGalleryItem(idx) { $('#gallery-row-' + idx).remove(); }
    function escHtml(s) { return $('<div/>').text(s).html(); }
</script>
<?php
/**
 * View: Thêm sản phẩm
 * Route: GET /admin/products/add
 * Controller: AdminProductController@add
 */
$pageTitle = 'Thêm sản phẩm';
$breadcrumbs = [
    ['label' => 'Sản phẩm', 'url' => url('admin/products')],
    ['label' => 'Thêm sản phẩm'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<section class="content pb-4 mt-3">
    <div class="container-fluid">
        <div class="card custom-card">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-uppercase mb-0">THÊM SẢN PHẨM MỚI</h3>
            </div>

            <form action="<?= url('admin/products/add') ?>" method="POST" id="productForm">
                <div class="card-body pt-3">

                    <!-- ===== HÀNG 1: Tên, Slug, Giá ===== -->
                    <div class="form-section mb-4">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold form-label-req">Tên sản phẩm</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        placeholder="Nhập tên sản phẩm..." required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Đường dẫn (Slug)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">/p/</span>
                                        </div>
                                        <input type="text" class="form-control" name="slug" id="slug"
                                            placeholder="Tự động theo tên">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold form-label-req">Giá bán (VNĐ)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-success font-weight-bold"
                                            name="price_vnd" placeholder="0" min="0" required>
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
                                        <option value="ON" class="text-success">HIỂN THỊ (ON)</option>
                                        <option value="OFF" class="text-danger">ẨN (OFF)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Loại sản phẩm</label>
                                    <select class="form-control font-weight-bold btn-outline-primary"
                                        name="product_type" id="productType">
                                        <option value="account">🔑 Tài khoản (Bán từ kho)</option>
                                        <option value="link">🔗 Source Link (Link download)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Danh mục</label>
                                    <select class="form-control" name="category_id">
                                        <option value="0">— Chọn danh mục —</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= (int) $cat['id'] ?>">
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Thứ tự</label>
                                    <input type="number" class="form-control" name="display_order" value="0" min="0">
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
                                            placeholder="Link ảnh hoặc chọn từ máy">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary"
                                                onclick="openImageManager && openImageManager('image')">
                                                Chọn ảnh
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-1 bg-light text-center"
                                    style="height: 50px; display: flex; align-items: center; justify-content: center; margin-top: 30px;">
                                    <img id="imagePreview" src="" alt=""
                                        style="max-height: 40px; max-width: 100%; display: none;">
                                    <span id="noImage" class="text-muted small">Xem trước</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== HÀNG 4: Gallery Ảnh phụ ===== -->
                    <div class="form-section mb-4">
                        <label class="font-weight-bold mb-2">Ảnh Gallery (Nhiều ảnh)</label>
                        <div id="gallery-container" class="row no-gutters"></div>
                        <button type="button" class="btn btn-outline-info btn-sm mt-2" onclick="addGalleryItem()">
                            <i class="fas fa-plus mr-1"></i>Thêm ảnh phụ
                        </button>
                    </div>

                    <!-- ===== REQUIRES INFO & STOCK CONFIG ===== -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-section" id="section-stock"
                                style="background: #f0f7ff; border: 1px dashed #cfe2ff; height: 100%;">
                                <div class="form-section-title">📦 Nhập kho tài khoản</div>
                                <div class="form-group mb-0 p-2">
                                    <textarea class="form-control" name="initial_stock" rows="6"
                                        style="font-family:Consolas,monospace;font-size:13px;"
                                        placeholder="user1:pass1&#10;user2:pass2&#10;..."></textarea>
                                    <small class="text-muted"><i class="fas fa-info-circle mr-1"></i> Mỗi dòng 1
                                        account.</small>
                                </div>
                            </div>
                            <div class="form-section" id="section-link"
                                style="display:none; background: #f8f9fa; border: 1px dashed #dee2e6; height: 100%;">
                                <div class="form-section-title">🔗 Link Download</div>
                                <div class="form-group mb-0 p-2">
                                    <input type="text" class="form-control" name="source_link" id="source_link"
                                        placeholder="https://mega.nz/file/...">
                                    <small class="text-muted">Link giao tự động khi khách mua.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-section"
                                style="background: #fff9f0; border: 1px dashed #ffeeba; height: 100%;">
                                <div class="form-section-title text-warning">🛠️ Yêu cầu thông tin khách</div>
                                <div class="p-2">
                                    <div class="custom-control custom-switch mb-2">
                                        <input type="checkbox" class="custom-control-input" id="requires_info"
                                            name="requires_info" value="1">
                                        <label class="custom-control-label font-weight-bold" for="requires_info">Yêu cầu
                                            thông tin khi mua</label>
                                    </div>
                                    <p class="small text-muted mb-2">Nếu bật, đơn hàng sẽ ở trạng thái <b>Pending</b>
                                        chờ bạn giao thủ công.</p>
                                    <textarea class="form-control" name="info_instructions" rows="3"
                                        placeholder="VD: Nhập tên miền cần đăng ký, User ID game..."></textarea>
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
                                        rows="12"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-section">
                                <div class="form-section-title">🔍 Mô tả SEO (Meta Description)</div>
                                <div class="form-group mb-3">
                                    <textarea class="form-control" name="seo_description" rows="10"
                                        placeholder="Đoạn văn ngắn hiện trên Google..."></textarea>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold">Badge Text</label>
                                    <input type="text" class="form-control" name="badge_text" placeholder="NEW / HOT">
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
                        LƯU SẢN PHẨM
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

        $('#name').on('keyup change', function () {
            if (!$('#slug').data('manual')) $('#slug').val(toSlug($(this).val()));
        });
        $('#slug').on('input', function () { $(this).data('manual', true); });

        $('#productType').on('change', function () {
            var type = $(this).val();
            if (type === 'link') {
                $('#section-link').slideDown();
                $('#section-stock').slideUp();
                $('#source_link').prop('required', true);
            } else {
                $('#section-link').slideUp();
                $('#section-stock').slideDown();
                $('#source_link').prop('required', false);
            }
        }).trigger('change');
    });

    let galleryIndex = 0;
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

    function toSlug(str) {
        const map = { 'à': 'a', 'á': 'a', 'ạ': 'a', 'ả': 'a', 'ã': 'a', 'â': 'a', 'ầ': 'a', 'ấ': 'a', 'ậ': 'a', 'ẩ': 'a', 'ẫ': 'a', 'ă': 'a', 'ằ': 'a', 'ắ': 'a', 'ặ': 'a', 'ẳ': 'a', 'ẵ': 'a', 'è': 'e', 'é': 'e', 'ẹ': 'e', 'ẻ': 'e', 'ẽ': 'e', 'ê': 'e', 'ề': 'e', 'ế': 'e', 'ệ': 'e', 'ể': 'e', 'ễ': 'e', 'ì': 'i', 'í': 'i', 'ị': 'i', 'ỉ': 'i', 'ĩ': 'i', 'ò': 'o', 'ó': 'o', 'ọ': 'o', 'ỏ': 'o', 'õ': 'o', 'ô': 'o', 'ồ': 'o', 'ố': 'o', 'ộ': 'o', 'ổ': 'o', 'ỗ': 'o', 'ơ': 'o', 'ờ': 'o', 'ớ': 'o', 'ợ': 'o', 'ở': 'o', 'ỡ': 'o', 'ù': 'u', 'ú': 'u', 'ụ': 'u', 'ủ': 'u', 'ũ': 'u', 'ư': 'u', 'ừ': 'u', 'ứ': 'u', 'ự': 'u', 'ử': 'u', 'ữ': 'u', 'ỳ': 'y', 'ý': 'y', 'ỵ': 'y', 'ỷ': 'y', 'ỹ': 'y', 'đ': 'd' };
        for (const [k, v] of Object.entries(map)) str = str.split(k).join(v);
        return str.toLowerCase().replace(/[^a-z0-9\s-]/g, '').trim().replace(/[\s]+/g, '-');
    }
</script>
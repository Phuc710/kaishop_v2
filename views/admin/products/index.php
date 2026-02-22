<?php
/**
 * View: Danh sách sản phẩm
 * Route: GET /admin/products
 * Controller: AdminProductController@index
 */
$pageTitle = 'Danh sách sản phẩm';
$breadcrumbs = [
    ['label' => 'Sản phẩm', 'url' => url('admin/products')],
    ['label' => 'Danh sách'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<section class="content pb-4 mt-3">
    <div class="container-fluid">

        <!-- Stats -->
        <div class="row mb-3">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info elevation-2">
                    <div class="inner">
                        <h3><?= $stats['total'] ?? 0 ?></h3>
                        <p>TỔNG SẢN PHẨM</p>
                    </div>
                    <div class="icon"><i class="fas fa-boxes"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success elevation-2">
                    <div class="inner">
                        <h3><?= $stats['active'] ?? 0 ?></h3>
                        <p>ĐANG HOẠT ĐỘNG</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning elevation-2">
                    <div class="inner">
                        <h3><?= $stats['pinned'] ?? 0 ?></h3>
                        <p>ĐANG GHIM</p>
                    </div>
                    <div class="icon"><i class="fas fa-thumbtack"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary elevation-2">
                    <div class="inner">
                        <h3><?= $stats['hidden'] ?? 0 ?></h3>
                        <p>ĐANG ẨN</p>
                    </div>
                    <div class="icon"><i class="fas fa-eye-slash"></i></div>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card custom-card">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title text-uppercase font-weight-bold">QUẢN LÝ KHO SẢN PHẨM</h3>
            </div>

            <!-- Filter Bar -->
            <div class="dt-filters">
                <div class="row g-2 mb-3">
                    <div class="col-md-3 mb-2">
                        <input id="f-search" class="form-control form-control-sm" placeholder="Tìm tên hoặc slug...">
                    </div>
                    <div class="col-md-3 mb-2">
                        <select id="f-cat" class="form-control form-control-sm">
                            <option value="">Tất cả danh mục</option>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="f-status" class="form-control form-control-sm">
                            <option value="">Trạng thái (ẩn/hiện)</option>
                            <option value="1">Đang ẩn</option>
                            <option value="0">Đang hiện</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 text-center">
                        <button type="button" id="btn-clear" class="btn btn-danger btn-sm shadow-sm w-100">
                            <i class="fas fa-trash"></i> Xóa Lọc
                        </button>
                    </div>
                    <div class="col-md-2 mb-2 text-right">
                        <a href="<?= url('admin/products/add') ?>" class="btn btn-primary btn-sm shadow-sm w-100">
                            <i class="fas fa-plus mr-1"></i> Thêm mới
                        </a>
                    </div>
                </div>

                <!-- Dropdown Line -->
                <div class="top-filter mb-2">
                    <div class="filter-show">
                        <span class="filter-label">SHOW :</span>
                        <select id="f-length" class="filter-select flex-grow-1">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="loading-indicator" class="dt-loading">Đang lọc...</div>

            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-3">
                    <table id="product-table" class="table text-nowrap table-hover table-bordered admin-table">
                        <thead>
                            <tr>
                                <th class="text-center font-weight-bold align-middle" style="width:60px">ƯU TIÊN</th>
                                <th class="text-center font-weight-bold align-middle" style="width:52px">ẢNH</th>
                                <th class="text-center font-weight-bold align-middle">TÊN SẢN PHẨM</th>
                                <th class="text-center font-weight-bold align-middle">SLUG</th>
                                <th class="text-center font-weight-bold align-middle">DANH MỤC</th>
                                <th class="text-center font-weight-bold align-middle">GIÁ BÁN</th>
                                <th class="text-center font-weight-bold align-middle" style="width:60px"
                                    title="Ghim lên đầu">📌 GHIM</th>
                                <th class="text-center font-weight-bold align-middle" style="width:100px">TRẠNG THÁI
                                </th>
                                <th class="text-center font-weight-bold align-middle" style="width:110px">NGÀY TẠO</th>
                                <th class="text-center font-weight-bold align-middle" style="width:80px">THAO TÁC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $row): ?>
                                <tr id="row-<?= $row['id'] ?>" data-hidden="<?= $row['is_hidden'] ?>">
                                    <td class="text-center align-middle">
                                        <span class="badge badge-primary px-2 py-1"><?= $row['display_order'] ?? 0 ?></span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <?php if (!empty($row['image'])): ?>
                                            <img src="<?= htmlspecialchars($row['image']) ?>" width="38" height="38"
                                                style="object-fit:cover;border-radius:5px;border:1px solid #ddd" alt="">
                                        <?php else: ?>
                                            <div class="bg-light text-muted d-flex align-items-center justify-content-center mx-auto"
                                                style="width:38px;height:38px;border-radius:5px;border:1px solid #ddd">
                                                <i class="fas fa-image" style="font-size:14px"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <strong><?= htmlspecialchars($row['name']) ?></strong>
                                    </td>
                                    <td class="text-center align-middle">
                                        <code class="text-muted"
                                            style="font-size:12px"><?= htmlspecialchars($row['slug'] ?? '—') ?></code>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span
                                            class="cat-badge"><?= htmlspecialchars($row['category_name'] ?? $row['category'] ?? '—') ?></span>
                                    </td>
                                    <td class="text-center align-middle font-weight-bold text-danger">
                                        <?= number_format($row['price_vnd'] ?? $row['price'] ?? 0) ?>đ
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button"
                                            class="btn btn-xs toggle-btn <?= $row['is_pinned'] ? 'btn-warning' : 'btn-outline-secondary' ?>"
                                            onclick="toggleField(<?= $row['id'] ?>, 'pin', this)"
                                            title="<?= $row['is_pinned'] ? 'Bỏ ghim' : 'Ghim lên đầu' ?>">
                                            <i class="fas fa-thumbtack"></i>
                                        </button>
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button"
                                            class="btn btn-xs toggle-btn <?= $row['is_hidden'] ? 'btn-secondary' : 'btn-outline-info' ?>"
                                            onclick="toggleField(<?= $row['id'] ?>, 'hide', this)"
                                            title="<?= $row['is_hidden'] ? 'Đang ẩn — click để hiện' : 'Click để ẩn' ?>">
                                            <i class="fas <?= $row['is_hidden'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                            <span class="ml-1"><?= $row['is_hidden'] ? 'Ẩn' : 'Hiện' ?></span>
                                        </button>
                                    </td>
                                    <td class="text-center align-middle date-col"
                                        data-order="<?= $row['created_at'] ?? '' ?>">
                                        <?php $ts = !empty($row['created_at']) ? strtotime($row['created_at']) : null; ?>
                                        <?php if ($ts): ?>
                                            <span class="badge date-badge text-monospace" data-toggle="tooltip"
                                                data-placement="top" title="<?= timeAgo(date('Y-m-d H:i:s', $ts)) ?>">
                                                <?= date('Y-m-d H:i:s', $ts) ?>
                                            </span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="btn-group">
                                            <a href="<?= url('admin/products/edit/' . $row['id']) ?>"
                                                class="btn btn-search-dt btn-sm" title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm ml-1" title="Xóa"
                                                onclick="confirmDelete(<?= $row['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
    let dt;

    function showLoading(on) {
        var el = document.getElementById('loading-indicator');
        if (!el) return;
        if (on) { el.classList.add('show'); } else { el.classList.remove('show'); }
    }

    // Custom row filter — Ẩn/Hiện
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable.id !== 'product-table') return true;
        var fStatus = $('#f-status').val();
        if (!fStatus) return true;
        var tr = settings.aoData[dataIndex].nTr;
        var isHidden = $(tr).attr('data-hidden');
        return isHidden === fStatus;
    });

    $(document).ready(function () {
        dt = $('#product-table').DataTable({
            dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center"p>>',
            scrollX: true,
            autoWidth: false,
            order: [[0, 'asc']],
            pageLength: 20,
            columnDefs: [
                { orderable: false, targets: [1, 6, 9] },
                { searchable: false, targets: [0, 1, 5, 6, 7, 9] }
            ],
            language: {
                sLengthMenu: 'Hiển thị _MENU_ mục',
                sZeroRecords: 'Không tìm thấy dữ liệu',
                sInfo: 'Xem _START_–_END_ / _TOTAL_ mục',
                sInfoEmpty: 'Xem 0-0 / 0 mục',
                sInfoFiltered: '(lọc từ _MAX_ mục)',
                sSearch: 'Tìm nhanh:',
                oPaginate: { sPrevious: '‹', sNext: '›' }
            }
        });

        function applyFilters() {
            showLoading(true);
            dt.search($('#f-search').val().trim());
            dt.column(4).search($('#f-cat').val().trim());
            dt.draw();
            setTimeout(() => showLoading(false), 200);
        }

        $('#f-search').on('keyup', function () {
            clearTimeout(window.searchTimer);
            window.searchTimer = setTimeout(applyFilters, 400);
        });

        $('#f-cat, #f-status').on('change', applyFilters);

        // Page Length
        $('#f-length').change(function () {
            dt.page.len($(this).val()).draw();
        });

        // Clear
        $('#btn-clear').click(function () {
            $('#f-search, #f-cat, #f-status').val('');
            $('#f-length').val('20');
            dt.search('').columns().search('');
            dt.page.len(20).order([0, 'asc']).draw();
        });
    });

    /* ── Toggle AJAX ── */
    function toggleField(id, action, btn) {
        const urlMap = {
            'hide': '<?= url("admin/products/toggle-hide") ?>',
            'pin': '<?= url("admin/products/toggle-pin") ?>',
            'active': '<?= url("admin/products/toggle-active") ?>'
        };

        $.post(urlMap[action], { id: id }, function (res) {
            if (res.success) {
                SwalHelper.toast('Cập nhật thành công', 'success');
                if (action === 'pin') {
                    const $b = $(btn);
                    if (res.new_value == 1) {
                        $b.removeClass('btn-outline-secondary').addClass('btn-warning').attr('title', 'Bỏ ghim');
                    } else {
                        $b.removeClass('btn-warning').addClass('btn-outline-secondary').attr('title', 'Ghim lên đầu');
                    }
                } else if (action === 'hide') {
                    const $b = $(btn);
                    $(btn).closest('tr').attr('data-hidden', res.new_value);
                    if (res.new_value == 1) {
                        $b.removeClass('btn-outline-info').addClass('btn-secondary').attr('title', 'Đang ẩn — click để hiện');
                        $b.html('<i class="fas fa-eye-slash"></i><span class="ml-1">Ẩn</span>');
                    } else {
                        $b.removeClass('btn-secondary').addClass('btn-outline-info').attr('title', 'Click để ẩn');
                        $b.html('<i class="fas fa-eye"></i><span class="ml-1">Hiện</span>');
                    }
                    dt.draw(false);
                }
            } else {
                SwalHelper.toast(res.message || 'Lỗi server', 'error');
            }
        }).fail(function () {
            SwalHelper.toast('Lỗi kết nối', 'error');
        });
    }

    /* ── Delete AJAX ── */
    function confirmDelete(id) {
        Swal.fire({
            title: 'Xóa sản phẩm này?',
            text: 'Dữ liệu không thể khôi phục!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-trash"></i> Xóa ngay',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= url("admin/products/delete") ?>', { id: id }, function (res) {
                    if (res.success) {
                        $('#row-' + id).fadeOut(() => { dt.row('#row-' + id).remove().draw(false); });
                        SwalHelper.toast('Đã xóa sản phẩm!', 'success');
                    } else {
                        SwalHelper.toast(res.message || 'Lỗi', 'error');
                    }
                }).fail(() => SwalHelper.toast('Lỗi kết nối', 'error'));
            }
        });
    }
</script>
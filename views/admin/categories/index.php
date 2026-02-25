<?php
/**
 * View: Danh sách danh mục
 * Route: GET /admin/categories
 * Controller: CategoryController@index
 */
$pageTitle = 'Quản lý danh mục';
$breadcrumbs = [
    ['label' => 'Danh mục', 'url' => url('admin/categories')],
    ['label' => 'Danh sách'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<section class="content pb-4 mt-3">
    <div class="container-fluid">
        <div class="card custom-card">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title text-uppercase font-weight-bold">QUẢN LÝ DANH MỤC</h3>
            </div>

            <!-- Filter Bar -->
            <div class="dt-filters">
                <div class="row g-2 mb-3">
                    <div class="col-md-5 mb-2">
                        <input id="f-search" class="form-control form-control-sm" placeholder="Tìm tên danh mục...">
                    </div>
                    <div class="col-md-3 mb-2">
                        <select id="f-status" class="form-control form-control-sm">
                            <option value="">Tất cả trạng thái</option>
                            <option value="ON">Đang bật</option>
                            <option value="OFF">Đang tắt</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 text-center">
                        <button type="button" id="btn-clear" class="btn btn-danger btn-sm shadow-sm w-100">
                            <i class="fas fa-trash"></i> Xóa Lọc
                        </button>
                    </div>
                    <div class="col-md-2 mb-2 text-right">
                        <a href="<?= url('admin/categories/add') ?>" class="btn btn-primary btn-sm shadow-sm w-100">
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

            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-3">
                    <table id="categoryTable" class="table text-nowrap table-hover table-bordered admin-table">
                        <thead>
                            <tr>
                                <th class="text-center font-weight-bold align-middle" style="width:60px">THỨ TỰ</th>
                                <th class="text-center font-weight-bold align-middle" style="width:60px">ICON</th>
                                <th class="text-center font-weight-bold align-middle">TÊN DANH MỤC</th>
                                <th class="text-center font-weight-bold align-middle">SLUG</th>
                                <th class="text-center font-weight-bold align-middle">SỐ SẢN PHẨM</th>
                                <th class="text-center font-weight-bold align-middle">TRẠNG THÁI</th>
                                <th class="text-center font-weight-bold align-middle">NGÀY TẠO</th>
                                <th class="text-center font-weight-bold align-middle" style="width:120px">THAO TÁC
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $row): ?>
                                    <tr>
                                        <td class="text-center align-middle font-weight-bold">
                                            <span class="badge badge-primary px-2 py-1"><?= $row['display_order'] ?></span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if (!empty($row['icon'])): ?>
                                                <div
                                                    style="width:40px; height:40px; margin: 0 auto; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border: 1px solid #eee; border-radius: 6px;">
                                                    <img src="<?= htmlspecialchars($row['icon']) ?>" alt="icon"
                                                        style="max-width:100%; max-height:100%; object-fit:contain;">
                                                </div>
                                            <?php else: ?>
                                                <i class="fas fa-folder text-muted" style="font-size:24px;"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle font-weight-bold">
                                            <?= htmlspecialchars($row['name']) ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="text-muted" style="font-size: 0.9em;">
                                                <?= htmlspecialchars($row['slug'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-info px-2 py-1"><?= $row['product_count'] ?? 0 ?></span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($row['status'] == 'ON'): ?>
                                                <span class="badge badge-success">ON</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">OFF</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php
                                            $createdRaw = (string) ($row['created_at'] ?? '');
                                            $createdTooltip = function_exists('timeAgo') ? (string) timeAgo($createdRaw) : $createdRaw;
                                            ?>
                                            <span class="badge date-badge" data-toggle="tooltip" data-placement="top"
                                                title="<?= htmlspecialchars($createdTooltip, ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($row['created_at']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="btn-group">
                                                <a href="<?= url('admin/categories/edit/' . $row['id']) ?>"
                                                    class="btn btn-search-dt btn-sm" title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm ml-1" title="Xóa"
                                                    onclick="deleteCategory(<?= $row['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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

    document.addEventListener("DOMContentLoaded", function () {
        let checkExist = setInterval(function () {
            if (window.jQuery && $.fn.DataTable) {
                clearInterval(checkExist);
                initCategoryTable();
            }
        }, 100);
    });

    function initCategoryTable() {
        dt = $('#categoryTable').DataTable({
            dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center"p>>',
            responsive: true,
            autoWidth: false,
            order: [[0, "asc"]],
            pageLength: 20,
            columnDefs: [
                { orderable: false, targets: [1, 7] }
            ],
            language: {
                sLengthMenu: 'Hiển thị _MENU_ mục',
                sZeroRecords: 'Không tìm thấy dữ liệu',
                sInfo: 'Xem _START_–_END_ / _TOTAL_ mục',
                sInfoEmpty: 'Xem 0-0 / 0 mục',
                sInfoFiltered: '(lọc từ _MAX_)',
                sSearch: 'Tìm nhanh:',
                oPaginate: { sPrevious: '&lsaquo;', sNext: '&rsaquo;' }
            }
        });

        // Search
        $('#f-search').on('input keyup', function () {
            dt.column(2).search(this.value.trim()).draw();
        });

        // Status filter
        $('#f-status').change(function () {
            dt.column(5).search(this.value).draw();
        });

        // Page length
        $('#f-length').change(function () {
            dt.page.len($(this).val()).draw();
        });

        // Clear
        $('#btn-clear').click(function () {
            $('#f-search').val('');
            $('#f-status').val('');
            $('#f-length').val('20');
            dt.search('').columns().search('');
            dt.page.len(20).order([0, 'asc']).draw();
        });
    }

    /* ── AJAX Delete ── */
    function deleteCategory(id) {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: 'Danh mục sẽ bị xóa vĩnh viễn!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-trash"></i> Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= url("admin/categories/delete") ?>', { id: id }, function (res) {
                    if (res.success) {
                        SwalHelper.toast('Đã xóa danh mục!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        SwalHelper.toast(res.message || 'Lỗi hệ thống', 'error');
                    }
                }).fail(function () {
                    SwalHelper.toast('Lỗi kết nối máy chủ', 'error');
                });
            }
        });
    }
</script>

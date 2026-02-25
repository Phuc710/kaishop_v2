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
<!-- Daterange picker -->
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/daterangepicker/daterangepicker.css">

<section class="content pb-4 mt-3">
    <div class="container-fluid">

        <!-- STATS -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-info elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-box-open"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">TỔNG SẢN PHẨM</span>
                        <span class="info-box-number h4 mb-0"><?= (int) ($stats['total'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-success elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">ĐANG HIỂN THỊ (ON)</span>
                        <span class="info-box-number h4 mb-0"><?= (int) ($stats['active'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-secondary elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-eye-slash"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">ĐANG ẨN (OFF)</span>
                        <span class="info-box-number h4 mb-0"><?= (int) ($stats['inactive'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLE CARD -->
        <div class="card custom-card">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title text-uppercase font-weight-bold">QUẢN LÝ SẢN PHẨM</h3>
            </div>

            <!-- FILTER BAR -->
            <div class="dt-filters">
                <!-- Search Line -->
                <div class="row g-2 justify-content-center align-items-center mb-3">
                    <div class="col-md-2 mb-2">
                        <input id="f-name" class="form-control form-control-sm" placeholder="Tìm tên sản phẩm...">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="f-category" class="form-control form-control-sm">
                            <option value="">-- Tất cả danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="f-status" class="form-control form-control-sm">
                            <option value="">-- Trạng thái --</option>
                            <option value="ON">ON</option>
                            <option value="OFF">OFF</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="f-type" class="form-control form-control-sm">
                            <option value="">-- Loại sản phẩm --</option>
                            <option value="Tài khoản">Tài khoản</option>
                            <option value="Source Link">Source Link</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 text-center">
                        <button type="button" id="btn-clear" class="btn btn-danger btn-sm shadow-sm w-100">
                            <i class="fas fa-trash"></i> Xóa Lọc
                        </button>
                    </div>
                    <div class="col-md-2 mb-2 text-right">
                        <a href="<?= url('admin/products/add') ?>" class="btn btn-primary btn-sm shadow-sm w-100">
                            <i class="fas fa-plus mr-1"></i> Thêm sản phẩm
                        </a>
                    </div>
                </div>

                <!-- Dropdown Line -->
                <div class="top-filter mb-2">
                    <div class="filter-show">
                        <span class="filter-label">SHOW :</span>
                        <select id="f-length" class="filter-select flex-grow-1">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="filter-short justify-content-end">
                        <span class="filter-label">SHORT BY DATE:</span>
                        <select id="f-sort" class="filter-select flex-grow-1">
                            <option value="all">Tất cả</option>
                            <option value="7">7 days</option>
                            <option value="15">15 days</option>
                            <option value="30">30 days</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-3">
                    <table id="productTable" class="table text-nowrap table-hover table-bordered w-100">
                        <thead>
                            <tr>
                                <th class="text-center font-weight-bold align-middle" style="width:60px;">ẢNH</th>
                                <th class="text-center font-weight-bold align-middle">TÊN SẢN PHẨM</th>
                                <th class="text-center font-weight-bold align-middle">LOẠI</th>
                                <th class="text-center font-weight-bold align-middle">DANH MỤC</th>
                                <th class="text-center font-weight-bold align-middle">GIÁ BÁN</th>
                                <th class="text-center font-weight-bold align-middle">KHO / ĐÃ BÁN</th>
                                <th class="text-center font-weight-bold align-middle">TRẠNG THÁI</th>
                                <th class="text-center font-weight-bold align-middle">NGÀY TẠO</th>
                                <th class="text-center font-weight-bold align-middle" style="width:120px;">HÀNH ĐỘNG
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $p):
                                    $isAccount = ($p['product_type'] ?? 'account') === 'account';
                                    $pid = (int) $p['id'];
                                    $st = $stockStats[$pid] ?? ['available' => 0, 'sold' => 0];
                                    ?>
                                    <tr id="row-<?= $pid ?>">
                                        <td class="text-center align-middle">
                                            <?php if (!empty($p['image'])): ?>
                                                <img src="<?= htmlspecialchars($p['image']) ?>"
                                                    style="width:46px;height:46px;object-fit:cover;border-radius:6px;" alt="">
                                            <?php else: ?>
                                                <div
                                                    style="width:46px;height:46px;background:#f0f0f0;border-radius:6px;display:flex;align-items:center;justify-content:center;margin: 0 auto;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <div class="font-weight-bold"><?= htmlspecialchars($p['name']) ?></div>
                                            <?php if (!empty($p['badge_text'])): ?>
                                                <span class="badge badge-warning"><?= htmlspecialchars($p['badge_text']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($isAccount): ?>
                                                <span class="badge badge-info"><i class="fas fa-user mr-1"></i>Tài khoản</span>
                                            <?php else: ?>
                                                <span class="badge badge-purple" style="background:#8b5cf6;color:#fff;"><i
                                                        class="fas fa-link mr-1"></i>Source Link</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle text-muted small">
                                            <?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                                        <td class="text-center align-middle font-weight-bold text-success">
                                            <?= number_format((int) $p['price_vnd']) ?>đ
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($isAccount): ?>
                                                <span class="text-success font-weight-bold"><?= $st['available'] ?></span>
                                                <span class="text-muted"> / đã bán </span>
                                                <span class="text-danger font-weight-bold"><?= $st['sold'] ?></span>
                                                <a href="<?= url('admin/products/stock/' . $pid) ?>"
                                                    class="btn btn-xs btn-outline-secondary ml-1" title="Quản lý kho">
                                                    <i class="fas fa-warehouse"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="badge badge-light text-muted"><i class="fas fa-infinity mr-1"></i>Vô
                                                    hạn</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button
                                                class="btn btn-xs toggle-status-btn <?= $p['status'] === 'ON' ? 'btn-success' : 'btn-secondary' ?>"
                                                data-id="<?= $pid ?>"
                                                title="<?= $p['status'] === 'ON' ? 'Đang hiển thị – click để ẩn' : 'Đang ẩn – click để hiển thị' ?>">
                                                <span style="display:none;"><?= $p['status'] === 'ON' ? 'ON' : 'OFF' ?></span>
                                                <?= $p['status'] === 'ON' ? 'ON' : 'OFF' ?>
                                            </button>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge date-badge">
                                                <?= $p['created_at'] ? date('Y-m-d H:i:s', strtotime($p['created_at'])) : '—' ?>
                                            </span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="btn-group">
                                                <a href="<?= url('admin/products/edit/' . $pid) ?>"
                                                    class="btn btn-search-dt btn-sm mr-1" title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $pid ?>"
                                                    data-name="<?= htmlspecialchars($p['name']) ?>" title="Xóa">
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
    let dtProduct;
    $(function () {
        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });

        dtProduct = $('#productTable').DataTable({
            dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center"p>>',
            responsive: true,
            autoWidth: false,
            order: [[7, "desc"]], // Sort by date desc
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [0, 8] }, // image and action
                { searchable: false, targets: [0, 4, 5, 8] } // dont search in these cols
            ],
            language: {
                sLengthMenu: 'Hiển thị _MENU_ mục',
                sZeroRecords: '<div class="text-center w-100 font-weight-bold py-3">Không tìm thấy dữ liệu</div>',
                sInfo: 'Xem _START_–_END_ / _TOTAL_ mục',
                sInfoEmpty: 'Không có dữ liệu',
                sInfoFiltered: '(lọc từ _MAX_)',
                sSearch: 'Tìm kiếm:',
                oPaginate: { sPrevious: '‹', sNext: '›' },
            },
        });

        // Dropdown Page Length
        $('#f-length').change(function () {
            dtProduct.page.len($(this).val()).draw();
        });

        // Custom Filters Action
        $('#f-name').on('input keyup', function () {
            dtProduct.column(1).search(this.value.trim()).draw();
        });

        $('#f-category').change(function () {
            dtProduct.column(3).search(this.value).draw();
        });

        $('#f-status').change(function () {
            dtProduct.column(6).search(this.value).draw();
        });

        $('#f-type').change(function () {
            dtProduct.column(2).search(this.value).draw();
        });

        // Dropdown Sort/Date Logic Ext
        $('#f-sort').change(function () {
            dtProduct.draw();
        });

        // Date Filter Logic Ext (for '7 days', '15 days' etc.)
        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'productTable') return true;

                // Sort theo dropdown (7, 15, 30 ngày)
                var sortVal = $('#f-sort').val();
                if (sortVal !== 'all') {
                    var days = parseInt(sortVal);
                    if (!isNaN(days)) {
                        var rowTime = new Date(data[7]).getTime();
                        var pastTime = new Date().getTime() - (days * 24 * 60 * 60 * 1000);
                        if (rowTime < pastTime) return false;
                    }
                }
                return true;
            }
        );

        // Clear Filters
        $('#btn-clear').click(function () {
            $('#f-name').val('');
            $('#f-category').val('');
            $('#f-status').val('');
            $('#f-type').val('');
            $('#f-length').val('10');
            $('#f-sort').val('all');
            dtProduct.search('').columns().search('');
            dtProduct.page.len(10).order([7, 'desc']).draw();
        });


        // Toggle status (update button logic for Datatable)
        $('#productTable tbody').on('click', '.toggle-status-btn', function () {
            const btn = $(this);
            const id = btn.data('id');
            $.post('<?= url("admin/products/toggle-status") ?>', { id: id }, function (res) {
                if (res.success) {
                    const isOn = res.new_status === 'ON';
                    btn.html(`<span style="display:none;">${isOn ? 'ON' : 'OFF'}</span>${isOn ? 'ON' : 'OFF'}`)
                        .removeClass('btn-success btn-secondary')
                        .addClass(isOn ? 'btn-success' : 'btn-secondary')
                        .attr('title', isOn ? 'Đang hiển thị – click để ẩn' : 'Đang ẩn – click để hiển thị');

                    // Update table cell data for searching if needed
                    dtProduct.cell(btn.closest('td')).data(btn.parent().html()).invalidate();

                    Toast.fire({ icon: 'success', title: isOn ? 'Đã bật hiển thị' : 'Đã ẩn sản phẩm' });

                    // Redraw to apply status filters if active
                    if ($('#f-status').val() !== '') {
                        dtProduct.draw();
                    }
                } else {
                    Toast.fire({ icon: 'error', title: res.message || 'Lỗi!' });
                }
            }, 'json').fail(() => Toast.fire({ icon: 'error', title: 'Lỗi máy chủ!' }));
        });

        // Delete
        $('#productTable tbody').on('click', '.delete-btn', function () {
            const btn = $(this);
            const id = btn.data('id');
            const name = btn.data('name');
            Swal.fire({
                title: 'Xóa sản phẩm?', text: name, icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', cancelButtonText: 'Hủy', confirmButtonText: 'Xóa'
            }).then(r => {
                if (!r.isConfirmed) return;
                $.post('<?= url("admin/products/delete") ?>', { id: id }, function (res) {
                    if (res.success) {
                        dtProduct.row(btn.closest('tr')).remove().draw();
                        Toast.fire({ icon: 'success', title: 'Đã xóa sản phẩm' });
                    } else {
                        Toast.fire({ icon: 'error', title: res.message || 'Lỗi!' });
                    }
                }, 'json');
            });
        });
    });
</script>
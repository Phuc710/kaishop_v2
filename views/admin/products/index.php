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

<section class="content pb-4 mt-1">
    <div class="container-fluid">

        <!-- THỐNG KÊ NHANH -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-info elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-box-open"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">Tổng sản phẩm</span>
                        <span class="info-box-number h4 mb-0"><?= number_format($stats['total'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-success elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">Đang hiển thị</span>
                        <span class="info-box-number h4 mb-0"><?= number_format($stats['active'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-secondary elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-eye-slash"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">Đang ẩn</span>
                        <span class="info-box-number h4 mb-0"><?= number_format($stats['inactive'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title text-uppercase font-weight-bold">QUẢN LÝ SẢN PHẨM</h3>
            </div>

            <!-- BỘ LỌC -->
            <div class="dt-filters">
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
                            <option value="ON">Đang hiển thị (ON)</option>
                            <option value="OFF">Đang ẩn (OFF)</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="f-type" class="form-control form-control-sm">
                            <option value="">-- Loại sản phẩm --</option>
                            <option value="Tài Khoản">Tài Khoản</option>
                            <option value="Yêu cầu thông tin">Yêu cầu thông tin</option>
                            <option value="Source">Source</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 text-center">
                        <button type="button" id="btn-clear" class="btn btn-danger btn-sm shadow-sm w-100">
                            Xóa Lọc
                        </button>
                    </div>
                    <div class="col-md-2 mb-2 text-right">
                        <a href="<?= url('admin/products/add') ?>" class="btn btn-primary btn-sm shadow-sm w-100">
                            <i class="fas fa-plus mr-1"></i> Thêm sản phẩm
                        </a>
                    </div>
                </div>

                <div class="top-filter mb-2">
                    <div class="filter-show">
                        <span class="filter-label">Hiển thị :</span>
                        <select id="f-length" class="filter-select flex-grow-1">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="filter-short justify-content-end">
                        <span class="filter-label">Lọc theo ngày:</span>
                        <select id="f-sort" class="filter-select flex-grow-1">
                            <option value="all">Tất cả</option>
                            <option value="7">7 ngày qua</option>
                            <option value="15">15 ngày qua</option>
                            <option value="30">30 ngày qua</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-3">
                    <table id="productTable" class="table table-hover table-bordered admin-table w-100">
                        <thead>
                            <tr>
                                <th class="text-center font-weight-bold align-middle" style="width:60px;">ẢNH</th>
                                <th class="text-center font-weight-bold align-middle">TÊN SẢN PHẨM</th>
                                <th class="text-center font-weight-bold align-middle">LOẠI</th>
                                <th class="text-center font-weight-bold align-middle">DANH MỤC</th>
                                <th class="text-center font-weight-bold align-middle">GIÁ BÁN</th>
                                <th class="text-center font-weight-bold align-middle">KHO / BÁN</th>
                                <th class="text-center font-weight-bold align-middle">TRẠNG THÁI</th>
                                <th class="text-center font-weight-bold align-middle">NGÀY TẠO</th>
                                <th class="text-center font-weight-bold align-middle" style="width:140px;">THAO TÁC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $p):
                                    $isAccount = ($p['product_type'] ?? 'account') === 'account';
                                    $isManualRequest = (($p['delivery_mode'] ?? '') === 'manual_info');
                                    $isSourceProduct = (($p['delivery_mode'] ?? '') === 'source_link');
                                    $isStockManaged = !empty($p['stock_managed']);
                                    $pid = (int) $p['id'];
                                    $st = $stockStats[$pid] ?? ['available' => 0, 'sold' => 0, 'unlimited' => false];
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
                                            <?php if ($isManualRequest): ?>
                                                <span class="badge badge-warning"><i class="fas fa-keyboard mr-1"></i>Yêu cầu thông
                                                    tin</span>
                                            <?php elseif ($isAccount): ?>
                                                <span class="badge badge-info"><i class="fas fa-user mr-1"></i>Tài Khoản</span>
                                            <?php else: ?>
                                                <span class="badge badge-purple" style="background:#8b5cf6;color:#fff;"><i
                                                        class="fas fa-link mr-1"></i>Source</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle text-muted small">
                                            <?= htmlspecialchars($p['category_name'] ?? '—') ?>
                                        </td>
                                        <td class="text-center align-middle font-weight-bold text-success">
                                            <?= number_format((int) $p['price_vnd']) ?>đ
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($isSourceProduct || !empty($st['unlimited'])): ?>
                                                <span class="badge badge-light text-muted"><i
                                                        class="fas fa-infinity mr-1"></i>Unlimited</span>
                                                <span class="text-muted"> / </span>
                                                <span class="text-danger font-weight-bold"><?= (int) ($st['sold'] ?? 0) ?></span>
                                            <?php elseif ($isStockManaged): ?>
                                                <span class="text-success font-weight-bold"><?= $st['available'] ?></span>
                                                <span class="text-muted"> / </span>
                                                <span class="text-danger font-weight-bold"><?= $st['sold'] ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-light text-muted"><i
                                                        class="fas fa-infinity mr-1"></i>Unlimited</span>
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
                                        <td class="text-center align-middle"
                                            data-time-ts="<?= (int) ($p['created_at_ts'] ?? 0) ?>"
                                            data-time-iso="<?= htmlspecialchars((string) ($p['created_at_iso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-order="<?= (int) ($p['created_at_ts'] ?? 0) ?>">
                                            <?= FormatHelper::eventTime($p['created_at_display'] ?? ($p['created_at'] ?? ''), $p['created_at'] ?? ($p['created_at_display'] ?? '')) ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="d-flex justify-content-center align-items-center"
                                                style="gap:5px; width:100%;">
                                                <a href="<?= url('admin/products/edit/' . $pid) ?>" class="btn btn-xs px-2"
                                                    style="background-color: #8b5cf6; color: white;" title="Sửa sản phẩm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?= url('admin/products/stock/' . $pid) ?>" class="btn btn-xs px-2"
                                                    style="background-color: #0ea5e9; color: white;" title="Quản lý kho">
                                                    <i class="fas fa-warehouse"></i>
                                                </a>
                                                <button class="btn btn-danger btn-xs px-2 delete-btn" data-id="<?= $pid ?>"
                                                    data-name="<?= htmlspecialchars($p['name']) ?>" title="Xóa sản phẩm">
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

    function stripHtmlToText(html) {
        return String(html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function getProductRowTimestamp(settings, dataIndex, cellHtml) {
        try {
            var rowMeta = settings && settings.aoData ? settings.aoData[dataIndex] : null;
            var rowNode = rowMeta ? rowMeta.nTr : null;
            var timeCell = rowNode && rowNode.cells ? rowNode.cells[7] : null;
            if (timeCell) {
                var tsAttr = Number(timeCell.getAttribute('data-time-ts') || '');
                if (!isNaN(tsAttr) && tsAttr > 0) return tsAttr * 1000;
                var iso = timeCell.getAttribute('data-time-iso') || '';
                if (iso) {
                    if (window.KaiTime && typeof window.KaiTime.toTimestamp === 'function') {
                        var kaiTs = window.KaiTime.toTimestamp(iso);
                        if (!isNaN(kaiTs) && kaiTs > 0) return kaiTs * 1000;
                    }
                    var nativeTs = Date.parse(iso);
                    if (!isNaN(nativeTs)) return nativeTs;
                }
            }
        } catch (e) { }
        var raw = stripHtmlToText(cellHtml);
        if (window.KaiTime && typeof window.KaiTime.toTimestamp === 'function') {
            var fallbackTs = window.KaiTime.toTimestamp(raw);
            if (!isNaN(fallbackTs) && fallbackTs > 0) return fallbackTs * 1000;
        }
        var ts = Date.parse(raw);
        return isNaN(ts) ? null : ts;
    }

    $(function () {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true
        });

        dtProduct = $('#productTable').DataTable({
            dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center"p>>',
            responsive: true,
            autoWidth: false,
            order: [
                [7, "desc"]
            ],
            pageLength: 10,
            columnDefs: [{
                orderable: false,
                targets: [0, 8]
            },
            {
                searchable: false,
                targets: [0, 4, 5, 8]
            }
            ],
            language: {
                sLengthMenu: 'Hiển thị _MENU_ sản phẩm',
                sZeroRecords: '<div class="text-center w-100 font-weight-bold py-3">Không tìm thấy sản phẩm nào</div>',
                sInfo: 'Xem _START_–_END_ / _TOTAL_ sản phẩm',
                sInfoEmpty: 'Không có sản phẩm nào',
                sInfoFiltered: '(lọc từ _MAX_ sản phẩm)',
                sSearch: 'Tìm kiếm:',
                oPaginate: {
                    sPrevious: '&lsaquo; Trước',
                    sNext: 'Tiếp &rsaquo;'
                },
            },
        });

        $('#f-length').change(function () {
            dtProduct.page.len($(this).val()).draw();
        });

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

        $('#f-sort').change(function () {
            dtProduct.draw();
        });

        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'productTable') return true;
                var sortVal = $('#f-sort').val();
                if (sortVal !== 'all') {
                    var days = parseInt(sortVal);
                    if (!isNaN(days)) {
                        var rowTime = getProductRowTimestamp(settings, dataIndex, data[7]);
                        var pastTime = new Date().getTime() - (days * 24 * 60 * 60 * 1000);
                        if (rowTime !== null && rowTime < pastTime) return false;
                    }
                }
                return true;
            }
        );

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

        $('#productTable tbody').on('click', '.toggle-status-btn', function () {
            const btn = $(this);
            const id = btn.data('id');
            const row = btn.closest('tr');

            $.post('<?= url("admin/products/toggle-status") ?>', {
                id: id
            }, function (res) {
                if (res.success) {
                    const nextStatus = res.new_status || res.new_value || '';
                    const isOn = nextStatus === 'ON';

                    // Update main toggle button in status column
                    const statusCell = row.find('td:eq(6)');
                    const statusBtn = statusCell.find('.toggle-status-btn');
                    statusBtn.html(`<span style="display:none;">${isOn ? 'ON' : 'OFF'}</span>${isOn ? 'ON' : 'OFF'}`)
                        .removeClass('btn-success btn-secondary')
                        .addClass(isOn ? 'btn-success' : 'btn-secondary')
                        .attr('title', isOn ? 'Đang hiển thị – click để ẩn' : 'Đang ẩn – click để hiển thị');

                    // Update the ban icon button in actions column
                    const actionCell = row.find('td:eq(8)');
                    const banBtn = actionCell.find('.toggle-status-btn');
                    banBtn.removeClass('btn-warning btn-secondary')
                        .addClass(isOn ? 'btn-warning' : 'btn-secondary')
                        .css('color', isOn ? '#000' : '#fff')
                        .attr('title', isOn ? 'Ẩn sản phẩm' : 'Hiện sản phẩm');

                    // Invalidate caches for both cells to reflect changes in DataTables
                    dtProduct.cell(statusCell).data(statusCell.html()).invalidate();
                    dtProduct.cell(actionCell).data(actionCell.html()).invalidate();

                    Toast.fire({
                        icon: 'success',
                        title: isOn ? 'Đã bật hiển thị' : 'Đã ẩn sản phẩm'
                    });
                    if ($('#f-status').val() !== '') {
                        dtProduct.draw();
                    }
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: res.message || 'Lỗi!'
                    });
                }
            }, 'json').fail(() => Toast.fire({
                icon: 'error',
                title: 'Lỗi máy chủ!'
            }));
        });

        $('#productTable tbody').on('click', '.delete-btn', function () {
            const btn = $(this);
            const id = btn.data('id');
            const name = btn.data('name');
            Swal.fire({
                title: 'Xóa sản phẩm?',
                text: name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonText: 'Hủy',
                confirmButtonText: 'Xóa'
            }).then(r => {
                if (!r.isConfirmed) return;
                $.post('<?= url("admin/products/delete") ?>', {
                    id: id
                }, function (res) {
                    if (res.success) {
                        dtProduct.row(btn.closest('tr')).remove().draw();
                        Toast.fire({
                            icon: 'success',
                            title: 'Đã xóa sản phẩm'
                        });
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: res.message || 'Lỗi!'
                        });
                    }
                }, 'json');
            });
        });
    });
</script>

<?php
/**
 * View: Danh sách mã giảm giá
 */
$pageTitle = 'Mã giảm giá';
$breadcrumbs = [
    ['label' => 'Mã giảm giá', 'url' => url('admin/finance/giftcodes')],
    ['label' => 'Danh sách'],
];
$adminNeedsFlatpickr = true;
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';

$summary = $summary ?? [
    'total_codes' => 0,
    'total_quantity' => 0,
    'total_used' => 0,
    'total_remaining' => 0,
];
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<section class="content pb-4 mt-1">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="info-box mb-3 border-0 shadow-sm" style="border-radius: 8px;">
                    <span class="info-box-icon bg-primary elevation-1" style="border-radius: 8px;">
                        <i class="fas fa-tags"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase small">TỔNG MÃ</span>
                        <span class="info-box-number h4 mb-0"><?= (int) ($summary['total_codes'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="info-box mb-3 border-0 shadow-sm" style="border-radius: 8px;">
                    <span class="info-box-icon bg-info elevation-1" style="border-radius: 8px;">
                        <i class="fas fa-layer-group"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase small">TỔNG SỐ LƯỢNG</span>
                        <span class="info-box-number h4 mb-0"><?= (int) ($summary['total_quantity'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="info-box mb-3 border-0 shadow-sm" style="border-radius: 8px;">
                    <span class="info-box-icon bg-danger elevation-1" style="border-radius: 8px;">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase small">ĐÃ SỬ DỤNG</span>
                        <span class="info-box-number h4 mb-0"><?= (int) ($summary['total_used'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="info-box mb-3 border-0 shadow-sm" style="border-radius: 8px;">
                    <span class="info-box-icon bg-success elevation-1" style="border-radius: 8px;">
                        <i class="fas fa-hourglass-half"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase small">CÒN LẠI</span>
                        <span
                            class="info-box-number h4 mb-0 text-success"><?= (int) ($summary['total_remaining'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card custom-card">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title text-uppercase font-weight-bold">QUẢN LÝ MÃ GIẢM GIÁ</h3>
            </div>



            <div class="dt-filters">
                <div class="row g-2 mb-3 align-items-center">
                    <div class="col-md-3 mb-2">
                        <input id="f-code" class="form-control form-control-sm" placeholder="Tìm mã giảm giá...">
                    </div>
                    <div class="col-md-3 mb-2">
                        <input id="f-product" class="form-control form-control-sm"
                            placeholder="Tìm sản phẩm áp dụng...">
                    </div>
                    <div class="col-md-2 mb-2">
                        <input id="f-date" class="form-control form-control-sm" placeholder="Chọn thời gian...">
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="button" id="btn-clear"
                            class="btn btn-danger btn-sm shadow-sm w-100 font-weight-bold">
                            <i class="fas fa-trash-alt mr-1"></i> XÓA LỌC
                        </button>
                    </div>
                    <div class="col-md-2 mb-2">
                        <a href="<?= url('admin/finance/giftcodes/add') ?>"
                            class="btn btn-primary btn-sm shadow-sm w-100 font-weight-bold"
                            style="display: flex; align-items: center; justify-content: center; height: 31px;">
                            <i class="fas fa-plus-circle mr-1"></i> TẠO MÃ MỚI
                        </a>
                    </div>
                </div>

                <div class="top-filter mb-2">
                    <div class="filter-show">
                        <span class="filter-label">HIỂN THỊ:</span>
                        <select id="f-length" class="filter-select">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="filter-short justify-content-end">
                        <span class="filter-label">LỌC THEO NGÀY:</span>
                        <select id="f-sort" class="filter-select">
                            <option value="all">Tất cả</option>
                            <option value="7">7 ngày</option>
                            <option value="15">15 ngày</option>
                            <option value="30">30 ngày</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-3">
                    <table id="giftTable" class="table text-nowrap table-hover table-bordered w-100">
                        <thead>
                            <tr>
                                <th class="text-center font-weight-bold align-middle">MÃ GIẢM GIÁ</th>
                                <th class="text-center font-weight-bold align-middle">SẢN PHẨM ÁP DỤNG</th>
                                <th class="text-center font-weight-bold align-middle">SỐ LƯỢNG</th>
                                <th class="text-center font-weight-bold align-middle">ĐÃ SỬ DỤNG</th>
                                <th class="text-center font-weight-bold align-middle">GIẢM</th>
                                <th class="text-center font-weight-bold align-middle">THỜI GIAN</th>
                                <th class="text-center font-weight-bold align-middle" style="width: 120px;">THAO TÁC
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($giftcodes)): ?>
                                <?php foreach ($giftcodes as $row): ?>
                                    <tr>
                                        <td class="text-center font-weight-bold align-middle">
                                            <span class="text-primary"><?= htmlspecialchars($row['giftcode']) ?></span><br>
                                            <small
                                                class="font-weight-bold <?= $row['remaining'] > 0 && $row['status'] == 'ON' ? 'text-success' : 'text-danger' ?>">
                                                (Còn <?= (int) $row['remaining'] ?> lượt)
                                            </small>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($row['type'] == 'all' || empty($row['product_names'])): ?>
                                                <span class="cat-badge">Toàn bộ sản phẩm</span>
                                            <?php else: ?>
                                                <div class="d-flex flex-wrap justify-content-center gap-1">
                                                    <?php foreach ($row['product_names'] as $p): ?>
                                                        <span class="cat-badge"
                                                            style="background: #f1f5f9;"><?= htmlspecialchars($p['name']) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle font-weight-bold"><?= (int) $row['soluong'] ?></td>
                                        <td class="text-center align-middle font-weight-bold text-danger">
                                            <?= (int) $row['dadung'] ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-success px-2 py-1"
                                                style="background-color: #8b5cf6;"><?= (int) $row['giamgia'] ?>%</span>
                                        </td>
                                        <td class="text-center align-middle"
                                            data-raw-datetime="<?= htmlspecialchars($row['time'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                            <span class="date-badge">
                                                <?= FormatHelper::eventTime($row['time'], $row['time']) ?>
                                            </span>
                                            <!-- Hidden raw time for sorting -->
                                            <span style="display:none;"><?= (string) ($row['time'] ?? '') ?></span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="btn-group">
                                                <a href="<?= url('admin/finance/giftcodes/log/' . $row['id']) ?>"
                                                    class="btn btn-info btn-sm" title="Nhật ký sử dụng"
                                                    style="background: #38bdf8; border:none;">
                                                    <i class="fas fa-history"></i>
                                                </a>
                                                <a href="<?= url('admin/finance/giftcodes/edit/' . $row['id']) ?>"
                                                    class="btn btn-search-dt btn-sm mx-1" title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm" title="Xóa"
                                                    onclick="deleteGiftcode(<?= (int) $row['id'] ?>)">
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

    document.addEventListener('DOMContentLoaded', function () {
        const checkExist = setInterval(function () {
            if (window.jQuery && $.fn.DataTable) {
                clearInterval(checkExist);
                initGiftcodeTable();
            }
        }, 100);
    });

    function initGiftcodeTable() {
        dt = $('#giftTable').DataTable({
            dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center"p>>',
            responsive: true,
            autoWidth: false,
            order: [[5, 'desc']],
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [1, 6] }
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

        if (typeof flatpickr !== 'undefined') {
            flatpickr('#f-date', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        dt.draw();
                    }
                },
                onReady: function (selectedDates, dateStr, instance) {
                    const clearBtn = document.createElement('div');
                    clearBtn.className = 'flatpickr-clear-btn mt-2 text-center text-danger';
                    clearBtn.innerHTML = '<span style="cursor:pointer;font-weight:bold;">Xóa lựa chọn</span>';
                    clearBtn.onclick = function () {
                        instance.clear();
                        dt.draw();
                    };
                    instance.calendarContainer.appendChild(clearBtn);
                }
            });
        }

        $('#f-length').change(function () {
            dt.page.len($(this).val()).draw();
        });

        $('#f-code, #f-product').on('input keyup', function () {
            dt.column(0).search($('#f-code').val().trim());
            dt.column(1).search($('#f-product').val().trim());
            dt.draw();
        });

        $('#btn-clear').click(function () {
            $('#f-code, #f-product, #f-date').val('');
            $('#f-length').val('10');
            $('#f-sort').val('all');
            dt.search('').columns().search('');
            dt.page.len(10).order([5, 'desc']).draw();
        });

        $('#f-sort').change(function () {
            dt.draw();
        });

        function getGiftRowTimestamp(settings, dataIndex) {
            const rowMeta = settings.aoData && settings.aoData[dataIndex] ? settings.aoData[dataIndex] : null;
            const rowNode = rowMeta ? rowMeta.nTr : null;
            if (!rowNode || !rowNode.cells || !rowNode.cells[5]) return NaN;

            const rawTime = rowNode.cells[5].getAttribute('data-raw-datetime');
            if (!rawTime) return NaN;

            return new Date(rawTime.replace(' ', 'T')).getTime();
        }

        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'giftTable') return true;

            const sortVal = $('#f-sort').val();
            if (sortVal !== 'all') {
                const days = parseInt(sortVal, 10);
                if (!isNaN(days)) {
                    const rowTime = getGiftRowTimestamp(settings, dataIndex);
                    const pastTime = Date.now() - (days * 24 * 60 * 60 * 1000);
                    if (!isNaN(rowTime) && rowTime < pastTime) return false;
                }
            }

            const dr = $('#f-date').val();
            if (!dr) return true;

            const separator = dr.includes(' to ') ? ' to ' : ' - ';
            const range = dr.split(separator);
            if (range.length !== 2) return true;

            const min = new Date(range[0] + ' 00:00:00').getTime();
            const max = new Date(range[1] + ' 23:59:59').getTime();
            const timeCol = getGiftRowTimestamp(settings, dataIndex);

            if (isNaN(min) || isNaN(max) || isNaN(timeCol)) return true;
            return timeCol >= min && timeCol <= max;
        });
    }

    function deleteGiftcode(id) {
        Swal.fire({
            title: 'Xác nhận xóa mã?',
            text: 'Mã giảm giá và dữ liệu liên quan sẽ không thể khôi phục!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-trash"></i> Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= url("admin/finance/giftcodes/delete/") ?>' + id, function (res) {
                    if (res.success) {
                        SwalHelper.toast('Đã xóa mã giảm giá!', 'success');
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
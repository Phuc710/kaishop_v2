<?php
/**
 * View: Nhật ký sử dụng mã giảm giá
 */
$pageTitle = 'Nhật ký mã ' . htmlspecialchars($giftcode['giftcode']);
$breadcrumbs = [
    ['label' => 'Mã giảm giá', 'url' => url('admin/finance/giftcodes')],
    ['label' => 'Nhật ký sử dụng'],
];
$adminNeedsFlatpickr = true;
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<?php
// Cấu hình OOP Helper phân tích Log
function parseActivityLog($text)
{
    $result = [
        'order_id' => '',
        'product_name' => '',
        'raw_text' => $text
    ];

    // Tìm mã đơn hàng Hex (Ví dụ: #A1B2C3, #123456)
    if (preg_match('/#([a-zA-Z0-9]{6})\b/', $text, $matches)) {
        $result['order_id'] = $matches[1];
        $text = str_replace($matches[0], '', $text);
    }

    // Các logic trích xuất Tên sản phẩm (Thông thường tên nằm sau chữ "Mua" hoặc "Đơn hàng")
    if (preg_match('/(Mua|Đơn hàng|Sản phẩm) (.+?) -/i', $text, $matches)) {
        $result['product_name'] = trim($matches[2]);
    } else {
        // Chỉ dọn dẹp raw text
        $result['product_name'] = trim(str_replace(['Sử dụng mã giảm giá', 'áp dụng mã'], '', $text));
    }

    return $result;
}
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<!-- Daterange picker -->

<section class="content pb-4 mt-3">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card custom-card">
                    <div class="card-header border-0 pb-0">
                        <h3 class="card-title text-uppercase font-weight-bold">
                            NHẬT KÝ MÃ: <span class="text-primary"><?= htmlspecialchars($giftcode['giftcode']) ?></span>
                        </h3>
                    </div>

                    <!-- Filter Bar -->
                    <div class="dt-filters">
                        <!-- Search Line -->
                        <div class="row g-2 justify-content-center align-items-center mb-3">
                            <div class="col-md-5 mb-2">
                                <input id="f-user" class="form-control form-control-sm"
                                    placeholder="Tìm kiếm Username...">
                            </div>
                            <div class="col-md-4 mb-2">
                                <input id="f-date" class="form-control form-control-sm" placeholder="Chọn thời gian...">
                            </div>
                            <div class="col-md-3 mb-2 text-center">
                                <button type="button" id="btn-clear" class="btn btn-danger btn-sm shadow-sm w-100">
                                    <i class="fas fa-trash"></i> Xóa Lọc
                                </button>
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

                    <div class="card-body pt-3">
                        <div class="table-responsive table-wrapper mb-3">
                            <table id="logTable" class="table text-nowrap table-hover table-bordered w-100">
                                <thead>
                                    <tr>
                                        <th class="text-center font-weight-bold align-middle" style="width:50px">STT
                                        </th>
                                        <th class="text-center font-weight-bold align-middle">USERNAME</th>
                                        <th class="text-center font-weight-bold align-middle">THÔNG TIN ĐƠN HÀNG</th>
                                        <th class="text-center font-weight-bold align-middle" style="width:160px">THỜI
                                            GIAN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($logs)): ?>
                                        <?php foreach ($logs as $i => $row):
                                            $parsed = parseActivityLog($row['hoatdong']);
                                            ?>
                                            <tr>
                                                <td class="text-center align-middle font-weight-bold">
                                                    <?= $i + 1 ?>
                                                </td>
                                                <td class="text-center align-middle font-weight-bold text-primary">
                                                    <?= htmlspecialchars($row['username']) ?>
                                                </td>
                                                <td class="text-center align-middle text-wrap" style="max-width: 300px;">
                                                    <?php if ($parsed['order_id']): ?>
                                                        <span class="badge badge-info mb-1">Đơn
                                                            #<?= htmlspecialchars($parsed['order_id']) ?></span><br>
                                                    <?php endif; ?>
                                                    <span
                                                        class="text-muted text-sm"><?= htmlspecialchars($parsed['product_name']) ?></span>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <?= FormatHelper::eventTime($row['time'], $row['time']) ?>
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
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
    let dtLog;
    document.addEventListener("DOMContentLoaded", function () {
        let checkExist = setInterval(function () {
            if (window.jQuery && $.fn.DataTable) {
                clearInterval(checkExist);
                initLogTable();
            }
        }, 100);
    });

    function initLogTable() {
        dtLog = $('#logTable').DataTable({
            dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center"p>>',
            responsive: true,
            autoWidth: false,
            order: [[3, "desc"]], // Sort by time desc
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [0, 2] }
            ],
            language: {
                sLengthMenu: 'Hiển thị _MENU_ mục',
                sZeroRecords: 'Chưa có lịch sử sử dụng cho mã này',
                sInfo: 'Xem _START_–_END_ / _TOTAL_ nhật ký',
                sInfoEmpty: '0 nhật ký',
                sSearch: 'Tìm nhanh:',
                oPaginate: { sPrevious: '←', sNext: '→' }
            }
        });

        // Date Picker initialization (Flatpickr)
        if (typeof flatpickr !== 'undefined') {
            flatpickr('#f-date', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        dtLog.draw();
                    }
                },
                onReady: function (selectedDates, dateStr, instance) {
                    const clearBtn = document.createElement('div');
                    clearBtn.className = 'flatpickr-clear-btn mt-2 text-center text-danger';
                    clearBtn.innerHTML = '<span style="cursor:pointer;font-weight:bold;">Clear Selection</span>';
                    clearBtn.onclick = function () {
                        instance.clear();
                        dtLog.draw();
                    };
                    instance.calendarContainer.appendChild(clearBtn);
                }
            });
        }

        // Live search Users
        $('#f-user').on('input keyup', function () {
            dtLog.column(1).search(this.value.trim()).draw();
        });

        // Clear Filters
        $('#btn-clear').click(function () {
            $('#f-user, #f-date').val('');
            $('#f-length').val('10');
            $('#f-sort').val('all');
            dtLog.search('').columns().search('');
            dtLog.page.len(10).order([3, 'desc']).draw();
        });

        // Dropdown Length & Sort
        $('#f-length').change(function () {
            dtLog.page.len($(this).val()).draw();
        });

        // Dropdown Sort/Date Logic Ext
        $('#f-sort').change(function () {
            dtLog.draw();
        });

        // Date Filter Logic Ext
        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'logTable') return true;

                // Trích xuất filter dropdown date (7, 15, 30 days)
                var sortVal = $('#f-sort').val();
                if (sortVal !== 'all') {
                    var days = parseInt(sortVal);
                    if (!isNaN(days)) {
                        var rowTime = new Date(data[3]).getTime();
                        var pastTime = new Date().getTime() - (days * 24 * 60 * 60 * 1000);
                        if (rowTime < pastTime) return false;
                    }
                }

                // Trích xuất filter date range flatpickr
                var dr = $('#f-date').val();
                if (!dr) return true;

                var separator = dr.includes(' to ') ? ' to ' : ' - ';
                var range = dr.split(separator);
                if (range.length !== 2) return true;

                var min = new Date(range[0] + ' 00:00:00').getTime();
                var max = new Date(range[1] + ' 23:59:59').getTime();
                var timeCol = new Date(data[3]).getTime(); // using time column string representation

                if (isNaN(min) || isNaN(max) || isNaN(timeCol)) return true;
                return timeCol >= min && timeCol <= max;
            }
        );
    }
</script>

<?php
/**
 * View: Nhật ký hệ thống (DataTables — unified, smart AJAX search)
 * Route: GET /admin/logs/activities | /admin/logs/balance-changes
 * Controller: JournalController
 *
 * Follows same DataTable pattern as giftcodes.php — No search button, auto-filter on keyup.
 * Supports ?user= param to auto-filter by username from User Edit page.
 */
$breadcrumbs = [
    ['label' => 'Nhật ký', 'url' => url('admin/logs/activities')],
    ['label' => $pageTitle ?? 'Nhật ký'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';

// Read ?user= param (passed from User Edit Page buttons)
$prefilterUser = trim((string) ($_GET['user'] ?? ''));
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/daterangepicker/daterangepicker.css">

<section class="content pb-4 mt-3">
    <div class="container-fluid">
        <div class="card custom-card">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title text-uppercase font-weight-bold">
                    <?= htmlspecialchars($cardTitle ?? 'DANH SÁCH'); ?>
                </h3>
            </div>

            <!-- Filter Bar -->
            <div class="dt-filters">
                <!-- Search Line -->
                <div class="row g-2 mb-3">
                    <div class="col-md-5 mb-2">
                        <input id="f-search" class="form-control form-control-sm" placeholder="Tìm kiếm tất cả..."
                            value="<?= htmlspecialchars($prefilterUser) ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <input id="f-date" class="form-control form-control-sm" placeholder="Thời gian...">
                    </div>
                    <div class="col-md-2 mb-2 text-center">
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
                            <option value="20" selected>20</option>
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
                    <table id="<?= htmlspecialchars($tableId ?? 'journalTable') ?>"
                        class="table text-nowrap table-hover table-bordered w-100">
                        <thead>
                            <tr>
                                <?php foreach (($columns ?? []) as $column): ?>
                                    <th
                                        class="<?= ($column['align'] ?? '') === 'center' ? 'text-center' : 'text-left'; ?> font-weight-bold align-middle">
                                        <?= htmlspecialchars(mb_strtoupper((string) ($column['label'] ?? ''), 'UTF-8')); ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($rows)): ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <?php foreach (($columns ?? []) as $column): ?>
                                            <?php
                                            $key = (string) ($column['key'] ?? '');
                                            $cell = $row[$key] ?? '--';
                                            $alignClass = ($column['align'] ?? '') === 'center' ? 'text-center' : 'text-left';
                                            ?>
                                            <td class="<?= $alignClass; ?> align-middle"><?= (string) $cell; ?></td>
                                        <?php endforeach; ?>
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
    const TABLE_ID = '<?= htmlspecialchars($tableId ?? 'journalTable') ?>';
    // Detect which column index has time data (look for column key = 'time')
    const TIME_COL_INDEX = <?php
    $timeIdx = 0;
    foreach (($columns ?? []) as $i => $col) {
        if (($col['key'] ?? '') === 'time') {
            $timeIdx = $i;
            break;
        }
    }
    echo $timeIdx;
    ?>;

    document.addEventListener("DOMContentLoaded", function () {
        let checkExist = setInterval(function () {
            if (window.jQuery && $.fn.DataTable) {
                clearInterval(checkExist);
                initJournalTable();
            }
        }, 100);
    });

    function initJournalTable() {
        dt = $('#' + TABLE_ID).DataTable({
            dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center"p>>',
            responsive: true,
            autoWidth: false,
            order: [[TIME_COL_INDEX, "desc"]],
            pageLength: 20,
            language: {
                sLengthMenu: 'Hiển thị _MENU_ mục',
                sZeroRecords: '<div class="text-center w-100 font-weight-bold py-3">Không tìm thấy dữ liệu</div>',
                sInfo: 'Xem _START_–_END_ / _TOTAL_ mục',
                sInfoEmpty: 'Xem 0-0 / 0 mục',
                sInfoFiltered: '(lọc từ _MAX_)',
                sSearch: 'Tìm nhanh:',
                oPaginate: { sPrevious: '‹', sNext: '›' }
            }
        });

        // Pre-filter by ?user= param if present
        var prefilter = '<?= addslashes($prefilterUser) ?>';
        if (prefilter) {
            dt.search(prefilter).draw();
        }

        // DatePicker
        if (typeof $.fn.daterangepicker === 'function') {
            $('#f-date').daterangepicker({
                autoUpdateInput: false,
                locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' }
            });

            $('#f-date').on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                dt.draw();
            });

            $('#f-date').on('cancel.daterangepicker', function () {
                $(this).val('');
                dt.draw();
            });
        }

        // Smart Search — no button, auto-filter on keyup
        $('#f-search').on('input keyup', function () {
            dt.search($(this).val().trim()).draw();
        });

        // Dropdown Page Length
        $('#f-length').change(function () {
            dt.page.len($(this).val()).draw();
        });

        // Sort by date dropdown
        $('#f-sort').change(function () {
            dt.draw();
        });

        // Clear All Filters
        $('#btn-clear').click(function () {
            $('#f-search, #f-date').val('');
            $('#f-length').val('20');
            $('#f-sort').val('all');
            dt.search('').columns().search('');
            dt.page.len(20).order([TIME_COL_INDEX, 'desc']).draw();
        });

        // Date Range Custom Filter (7/15/30 days + daterangepicker)
        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {
                if (settings.nTable.id !== TABLE_ID) return true;

                // Sort by dropdown (7, 15, 30 days)
                var sortVal = $('#f-sort').val();
                if (sortVal !== 'all') {
                    var days = parseInt(sortVal);
                    if (!isNaN(days)) {
                        var rowTime = new Date(data[TIME_COL_INDEX]).getTime();
                        var pastTime = new Date().getTime() - (days * 24 * 60 * 60 * 1000);
                        if (rowTime < pastTime) return false;
                    }
                }

                // DateRangePicker
                var dr = $('#f-date').val();
                if (!dr) return true;

                var range = dr.split(' - ');
                if (range.length !== 2) return true;

                var min = new Date(range[0] + ' 00:00:00').getTime();
                var max = new Date(range[1] + ' 23:59:59').getTime();
                var timeCol = new Date(data[TIME_COL_INDEX]).getTime();

                if (isNaN(min) || isNaN(max) || isNaN(timeCol)) return true;
                return timeCol >= min && timeCol <= max;
            }
        );

        // Tooltips
        if (typeof $.fn.tooltip === 'function') {
            $('[data-toggle="tooltip"]').tooltip();
        }
    }
</script>
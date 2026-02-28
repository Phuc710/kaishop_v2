<?php
$userPageTitle = 'Biến động số dư';
$userPageAssetFlags = [
    'datatables' => true,
    'flatpickr' => true,
    'interactive_bundle' => false,
];
$activePage = 'history';
require __DIR__ . '/layout/header.php';
?>

<div class="profile-card">
    <div class="profile-card-header profile-card-header--with-actions">
        <div>
            <h5 class="text-dark mb-1">BIẾN ĐỘNG SỐ DƯ</h5>
        </div>
        <a href="<?= url('balance/bank') ?>" class="btn btn-edit-profile">
            <i class="fas fa-university me-1"></i> Nạp tiền
        </a>
    </div>
    <div class="profile-card-body p-4">
        <div class="user-history-filters mb-4">
            <div class="row g-2 mb-3 align-items-center">
                <div class="col-md-6 mb-2">
                    <div class="input-group user-filter-input">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i
                                class="fas fa-search"></i></span>
                        <input type="text" id="filter-reason" class="form-control border-start-0 ps-0"
                            placeholder="Tìm theo nội dung biến động...">
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="input-group user-filter-input">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i
                                class="far fa-calendar-alt"></i></span>
                        <input type="text" id="filter-date" class="form-control border-start-0 ps-0 bg-white"
                            placeholder="Từ ngày - Đến ngày" readonly>
                    </div>
                </div>
                <div class="col-md-2 mb-2">
                    <button id="btn-clear" class="btn w-100 py-2" title="Xóa bộ lọc">
                        <i class="fas fa-trash me-1"></i> Xóa lọc
                    </button>
                </div>
            </div>

            <div class="user-history-toolbar d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <span class="user-toolbar-label">Show :</span>
                    <select id="f-length"
                        class="form-select form-select-sm shadow-none user-toolbar-select user-toolbar-select--narrow">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="d-flex align-items-center">
                    <span class="user-toolbar-label">Sort by date:</span>
                    <select id="f-sort" class="form-select form-select-sm shadow-none user-toolbar-select">
                        <option value="all">Tất cả</option>
                        <option value="today">Hôm nay</option>
                        <option value="7">Tuần</option>
                        <option value="30">Tháng</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive user-history-table-wrap">
            <table id="history-table" class="table table-hover align-middle w-100 mb-0 user-history-table">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 text-nowrap text-center">THỜI GIAN</th>
                        <th class="py-3 text-nowrap text-center">SỐ DƯ TRƯỚC</th>
                        <th class="py-3 text-nowrap text-center">BIẾN ĐỘNG</th>
                        <th class="py-3 text-nowrap text-center">SỐ DƯ HIỆN TẠI</th>
                        <th class="py-3 text-nowrap text-center">NỘI DUNG BIẾN ĐỘNG</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        function renderSharedTimeCell(row) {
            if (window.KaiTime && typeof window.KaiTime.renderUserTimeCell === 'function') {
                return window.KaiTime.renderUserTimeCell({
                    timeTs: row.time_ts,
                    rawValue: row.time_raw,
                    fallbackText: row.time,
                    timeAgo: row.time_ago,
                    className: 'user-time-plain'
                });
            }
            return String(row.time || '--');
        }

        function fmtMoneyVnd(value) {
            const n = Number(value || 0);
            return new Intl.NumberFormat('vi-VN').format(Math.abs(n)) + 'đ';
        }

        function debounce(fn, wait) {
            let t = null;
            return function () {
                const ctx = this;
                const args = arguments;
                clearTimeout(t);
                t = setTimeout(function () { fn.apply(ctx, args); }, wait || 250);
            };
        }

        function renderBalanceMoney(value, kind) {
            const n = Number(value || 0);
            if (!Number.isFinite(n)) return '--';

            if (kind === 'change') {
                if (n > 0) {
                    return '<span class="user-money-change user-money-change--plus">+' + fmtMoneyVnd(n) + '</span>';
                }
                if (n < 0) {
                    return '<span class="user-money-change user-money-change--minus">-' + fmtMoneyVnd(n) + '</span>';
                }
                return '<span class="user-money-before">0đ</span>';
            }

            if (kind === 'after') {
                const sign = n < 0 ? '-' : '';
                return '<span class="user-money-after">' + sign + fmtMoneyVnd(n) + '</span>';
            }

            const sign = n < 0 ? '-' : '';
            return '<span class="user-money-before">' + sign + fmtMoneyVnd(n) + '</span>';
        }

        let datePicker = {
            clear: function () { $('#filter-date').val(''); }
        };

        const table = $('#history-table').DataTable({
            serverSide: true,
            ajax: {
                url: BASE_URL + '/api/history-balance',
                type: 'POST',
                data: function (d) {
                    d.reason = $('#filter-reason').val();
                    d.time_range = $('#filter-date').val();
                    d.sort_date = $('#f-sort').val();
                }
            },
            columns: [
                {
                    data: null,
                    width: '15%',
                    className: 'text-center',
                    render: function (row) {
                        return renderSharedTimeCell(row);
                    }
                },
                {
                    data: null,
                    width: '15%',
                    className: 'text-center text-nowrap',
                    render: function (row) { return renderBalanceMoney(row.before_amount, 'before'); }
                },
                {
                    data: null,
                    width: '15%',
                    className: 'text-center text-nowrap',
                    render: function (row) { return renderBalanceMoney(row.change_amount, 'change'); }
                },
                {
                    data: null,
                    width: '15%',
                    className: 'text-center text-nowrap',
                    render: function (row) { return renderBalanceMoney(row.after_amount, 'after'); }
                },
                { data: 'reason', width: '40%', className: 'text-start text-wrap' }
            ],
            order: [],
            ordering: false,
            pageLength: 10,
            dom: 't<"d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 gap-3"<"text-muted small"i><"d-flex align-items-center gap-3"p>>',
            language: {
                info: "Hiển thị _START_ - _END_ trong tổng số _TOTAL_ biến động",
                infoEmpty: "Chưa có biến động nào",
                emptyTable: "Không có dữ liệu biến động số dư",
                paginate: {
                    first: "Đầu",
                    last: "Cuối",
                    next: "&rsaquo;",
                    previous: "&lsaquo;"
                }
            }
        });

        if (window.flatpickr) {
            const fpLocale = (flatpickr.l10ns && (flatpickr.l10ns.vn || flatpickr.l10ns.VN))
                ? (flatpickr.l10ns.vn || flatpickr.l10ns.VN)
                : undefined;

            datePicker = flatpickr('#filter-date', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                locale: fpLocale,
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2 || selectedDates.length === 0) {
                        table.draw();
                    }
                }
            });
        }

        const debouncedSearchDraw = debounce(function () { table.draw(); }, 280);

        $('#f-length').on('change', function () { table.page.len($(this).val()).draw(); });
        $('#f-sort').on('change', function () { table.draw(); });
        $('#filter-reason').on('input', debouncedSearchDraw);
        $('#filter-reason').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                table.draw();
            }
        });
        $('#btn-clear').on('click', function () {
            $('#filter-reason').val('');
            $('#f-sort').val('all');
            datePicker.clear();
            table.draw();
        });
    });
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <?php
    $GLOBALS['pageAssets'] = array_merge($GLOBALS['pageAssets'] ?? [], [
        'datatables' => true,
        'flatpickr' => true,
        'interactive_bundle' => false,
    ]);
    ?>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Lịch sử nạp tiền |
        <?= htmlspecialchars((string) ($chungapi['ten_web'] ?? 'KaiShop'), ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="robots" content="noindex, nofollow">
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="bg-light">
        <section class="py-5" style="padding-top: 80px !important;">
            <div class="container user-page-container">
                <div class="row">
                    <!-- Sidebar (DRY component) -->
                    <div class="col-lg-3 col-md-4 mb-4">
                        <?php $activePage = 'history';
                        require __DIR__ . '/../../hethong/user_sidebar.php'; ?>
                    </div>

                    <!-- Main Content -->
                    <div class="col-lg-9 col-md-8">
                        <div class="profile-card">
                            <div class="profile-card-header">
                                <h5 class="text-dark">Biến động số dư</h5>
                            </div>
                            <div class="profile-card-body p-4">
                                <!-- Filter Section -->
                                <div class="mb-4">
                                    <div class="row g-2 mb-3 align-items-center">
                                        <div class="col-md-6 mb-2">
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i
                                                        class="fas fa-search"></i></span>
                                                <input type="text" id="filter-reason"
                                                    class="form-control border-start-0 ps-0"
                                                    placeholder="Tìm nhanh theo lý do...">
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i
                                                        class="far fa-calendar-alt"></i></span>
                                                <input type="text" id="filter-date"
                                                    class="form-control border-start-0 ps-0 bg-white"
                                                    placeholder="Từ ngày - Đến ngày" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <button id="btn-clear" class="btn btn-outline-danger w-100 shadow-sm py-2"
                                                title="Xóa bộ lọc">
                                                <i class="fas fa-trash me-1"></i> Xóa lọc
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Dropdown Line -->
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="text-secondary fw-bold small me-2 text-uppercase">Show :</span>
                                            <select id="f-length" class="form-select form-select-sm shadow-none"
                                                style="width: 70px;">
                                                <option value="10">10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                                <option value="100">100</option>
                                            </select>
                                        </div>

                                        <div class="d-flex align-items-center">
                                            <span class="text-secondary fw-bold small me-2 text-uppercase">Short by
                                                date:</span>
                                            <select id="f-sort" class="form-select form-select-sm shadow-none"
                                                style="width: auto;">
                                                <option value="all">Tất cả</option>
                                                <option value="today">Hôm nay</option>
                                                <option value="7">Tuần</option>
                                                <option value="30">Tháng</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Table Section -->
                                <div class="table-responsive">
                                    <table id="history-table" class="table table-hover align-middle w-100 mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="py-3 text-nowrap text-center">THỜI GIAN</th>
                                                <th class="py-3 text-nowrap text-center">SỐ DƯ TRƯỚC</th>
                                                <th class="py-3 text-nowrap text-center">BIẾN ĐỘNG</th>
                                                <th class="py-3 text-nowrap text-center">SỐ DƯ HIỆN TẠI</th>
                                                <th class="py-3 text-nowrap text-center">LÝ DO</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data loaded via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>

    <style>
        .date-badge {
            color: black !important;
            background-color: transparent !important;
            font-size: 14px;
            font-weight: 500;
        }
    </style>
    <script>
        $(document).ready(function () {
            // Init Flatpickr for date range selection
            const datePicker = flatpickr("#filter-date", {
                mode: "range",
                dateFormat: "Y-m-d",
                locale: "vn",
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2 || selectedDates.length === 0) {
                        table.draw();
                    }
                }
            });

            // Init DataTable
            const table = $('#history-table').DataTable({
                serverSide: true,
                ajax: {
                    url: BASE_URL + '/api/history-code',
                    type: 'POST',
                    data: function (d) {
                        d.reason = $('#filter-reason').val();
                        d.time_range = $('#filter-date').val();
                        d.sort_date = $('#f-sort').val(); // New parameter for time sorting
                    }
                },
                columns: [
                    {
                        data: 'time',
                        width: '15%',
                        className: 'text-nowrap small text-center',
                        render: function (data) {
                            return '<span style="color: #1D1D1D;">' + data + '</span>';
                        }
                    },
                    { data: 'before', width: '15%', className: 'text-center text-nowrap' },
                    { data: 'change', width: '15%', className: 'text-center text-nowrap' },
                    { data: 'after', width: '15%', className: 'text-center text-nowrap' },
                    { data: 'reason', width: '40%', className: 'text-start text-wrap' }
                ],
                order: [], // Disable initial order since server handles it (DESC by id)
                ordering: false, // Disable ordering on frontend
                pageLength: 10,
                dom: 't<"d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 gap-3"<"text-muted small"i><"d-flex align-items-center gap-3"p>>',
                language: {
                    info: "Hiển thị _START_ - _END_ trong tổng số _TOTAL_ giao dịch",
                    infoEmpty: "Chưa có giao dịch nào",
                    emptyTable: "Không có dữ liệu biến động số dư",
                    paginate: {
                        first: "Đầu",
                        last: "Cuối",
                        next: "&rsaquo;",
                        previous: "&lsaquo;"
                    }
                }
            });

            // Handle Show Entries dropdown change
            $('#f-length').on('change', function () {
                table.page.len($(this).val()).draw();
            });

            // Handle Sort by Date dropdown change
            $('#f-sort').on('change', function () {
                table.draw();
            });

            // Trigger search on input for reason
            $('#filter-reason').on('input', function () {
                table.draw();
            });

            // Clear Filter
            $('#btn-clear').on('click', function () {
                $('#filter-reason').val('');
                datePicker.clear();
                table.draw();
            });
        });
    </script>
</body>

</html>
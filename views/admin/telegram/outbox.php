<?php
/**
 * View: Telegram Bot — Outbox & Worker (Smart Refactor)
 * Route: GET /admin/telegram/outbox
 */
$pageTitle = 'Hàng đợi Outbox';
$breadcrumbs = [
    ['label' => 'Telegram Bot', 'url' => url('admin/telegram/settings')],
    ['label' => 'Outbox & Worker'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';

$pending = (int) ($stats['pending'] ?? 0);
$sent = (int) ($stats['sent'] ?? 0);
$failed = (int) ($stats['failed'] ?? 0);

// Worker Health Logic
$workerStatus = 'OFF_LINE';
$workerClass = 'badge-secondary';
$pulseClass = 'status-offline';

if (!empty($lastCronRun)) {
    $lastTs = strtotime($lastCronRun);
    $diff = time() - $lastTs;
    if ($diff < 120) {
        $workerStatus = 'ACTIVE';
        $workerClass = 'badge-success';
        $pulseClass = 'status-active';
    } elseif ($diff < 600) {
        $workerStatus = 'STALLED';
        $workerClass = 'badge-warning';
        $pulseClass = 'status-stalled';
    }
}
$statusFilter = $filters['status'] ?? 'all';
$period = $filters['period'] ?? 'all';
$searchQuery = $filters['search'] ?? '';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<section class="content pb-4 mt-1 text-dark">
    <div class="container-fluid">

        <!-- THỐNG KÊ NHANH -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-warning elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">Đang chờ (Queue)</span>
                        <span id="stat-pending" class="info-box-number h4 mb-0"><?= number_format($pending) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-success elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">Đã hoàn thành</span>
                        <span id="stat-sent" class="info-box-number h4 mb-0"><?= number_format($sent) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-danger elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">Gửi thất bại</span>
                        <span id="stat-failed" class="info-box-number h4 mb-0"><?= number_format($failed) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title text-uppercase font-weight-bold">HÀNG ĐỢI TIN NHẮN</h3>
            </div>

            <!-- BỘ LỌC -->
            <div class="dt-filters">
                <div class="row g-2 justify-content-center align-items-center mb-3">
                    <div class="col-md-3 mb-2">
                        <input id="f-search" class="form-control form-control-sm" placeholder="Tìm ID, nội dung..."
                            value="<?= htmlspecialchars($searchQuery) ?>">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="f-status" class="form-control form-control-sm">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>Đã gửi</option>
                            <option value="fail" <?= $statusFilter === 'fail' ? 'selected' : '' ?>>Thất bại</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Đang chờ</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="f-period" class="form-control form-control-sm">
                            <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>Mọi lúc</option>
                            <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Hôm nay</option>
                            <option value="yesterday" <?= $period === 'yesterday' ? 'selected' : '' ?>>Hôm qua</option>
                            <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>7 ngày qua</option>
                            <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>30 ngày qua</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 text-center">
                        <button type="button" id="btn-retry-failed" class="btn btn-warning btn-sm shadow-sm w-100"
                            title="Gửi lại lỗi">
                            <i class="fas fa-sync-alt mr-1"></i> Gửi lại lỗi
                        </button>
                    </div>
                    <div class="col-md-2 mb-2 text-center">
                        <button type="button" id="btn-delete-all" class="btn btn-danger btn-sm shadow-sm w-100"
                            title="Dọn dẹp rác">
                            <i class="fas fa-trash-alt mr-1"></i> Dọn dẹp
                        </button>
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
                </div>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-3">
                    <table id="outboxTable" class="table table-hover table-bordered admin-table w-100">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="text-center font-weight-bold align-middle" style="width: 80px;">ID</th>
                                <th class="text-center font-weight-bold align-middle">NGƯỜI NHẬN</th>
                                <th class="text-center font-weight-bold align-middle">WEB USER</th>
                                <th class="text-center font-weight-bold align-middle">NỘI DUNG</th>
                                <th class="text-center font-weight-bold align-middle">TRẠNG THÁI</th>
                                <th class="text-center font-weight-bold align-middle">LƯỢT GỬI</th>
                                <th class="text-center font-weight-bold align-middle">THỜI GIAN</th>
                                <th class="text-center font-weight-bold align-middle" style="width: 80px;">THAO TÁC</th>
                            </tr>
                        </thead>
                        <tbody id="outbox-tbody">
                            <?php if (!empty($messages)):
                                foreach ($messages as $msg): ?>
                                    <tr id="row-<?= $msg['id'] ?>">
                                        <td class="text-center align-middle font-weight-bold">#<?= $msg['id'] ?></td>
                                        <td class="align-middle">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle mr-2 d-flex align-items-center justify-content-center"
                                                    style="width: 28px; height: 28px; font-size: 10px;">
                                                    <i class="fab fa-telegram-plane"></i>
                                                </div>
                                                <code class="text-primary"><?= htmlspecialchars($msg['telegram_id']) ?></code>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if (!empty($msg['web_username'])): ?>
                                                <span class="badge badge-light border"><i class="fas fa-user mr-1 text-primary"></i>
                                                    <?= htmlspecialchars($msg['web_username']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">Guest / Bot</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <div class="text-truncate" style="max-width: 250px; font-size: 0.9rem;"
                                                title="<?= htmlspecialchars($msg['message']) ?>">
                                                <?= htmlspecialchars($msg['message']) ?>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($msg['status'] === 'sent'): ?>
                                                <span class="badge badge-success-soft text-success"><i
                                                        class="fas fa-check-circle mr-1"></i>ĐÃ GỬI</span>
                                            <?php elseif ($msg['status'] === 'fail'): ?>
                                                <span class="badge badge-danger-soft text-danger"><i
                                                        class="fas fa-exclamation-triangle mr-1"></i>LỖI</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning-soft text-warning"><i
                                                        class="fas fa-clock mr-1"></i>CHỜ GỬI</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-light font-weight-bold"><?= $msg['try_count'] ?></span>
                                        </td>
                                        <td class="text-center align-middle text-muted small"
                                            data-order="<?= strtotime($msg['created_at']) ?>">
                                            <?= date('H:i:s', strtotime($msg['created_at'])) ?><br>
                                            <span
                                                style="font-size: 10px;"><?= date('d/m/Y', strtotime($msg['created_at'])) ?></span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button class="btn btn-xs btn-light text-primary border shadow-sm px-2"
                                                data-msg="<?= htmlspecialchars(rawurlencode(json_encode($msg, JSON_UNESCAPED_UNICODE)), ENT_QUOTES, 'UTF-8') ?>"
                                                onclick="showDetailFromBtn(this)" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Detail Modal -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title font-weight-bold">Chi tiết bản ghi #<span id="det-id"></span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="small text-muted text-uppercase font-weight-bold mb-1">Người nhận (Telegram
                            ID)</label>
                        <div class="h5 mb-0 text-primary font-weight-bold" id="det-tid-display"></div>
                    </div>
                    <div class="col-md-6 text-md-right">
                        <label class="small text-muted text-uppercase font-weight-bold mb-1">Trạng thái gửi</label>
                        <div id="det-status"></div>
                    </div>
                </div>
                <div class="form-group mb-4">
                    <label class="small text-muted text-uppercase font-weight-bold mb-2">Nội dung tin nhắn</label>
                    <div class="p-3 bg-light rounded text-monospace border" id="det-message"
                        style="white-space: pre-wrap; font-size: 0.9rem; max-height: 350px; overflow-y: auto; color: #334155;">
                    </div>
                </div>
                <div id="det-error-box" class="form-group d-none mb-4">
                    <label class="small text-danger text-uppercase font-weight-bold mb-2">Lỗi từ API / Hệ thống</label>
                    <div class="p-3 border border-danger rounded text-danger bg-danger-soft small font-weight-bold"
                        id="det-error"></div>
                </div>
                <div class="pt-3 border-top">
                    <div class="row small text-muted">
                        <div class="col-6">Tạo lúc: <span id="det-created" class="text-dark"></span></div>
                        <div class="col-6 text-right">Cập nhật: <span id="det-updated" class="text-dark"></span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary px-5 font-weight-bold" data-dismiss="modal">ĐÓNG</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<style>
    .badge-success-soft {
        background: #dcfce7;
        color: #166534;
    }

    .badge-danger-soft {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-warning-soft {
        background: #fef9c3;
        color: #854d0e;
    }

    .bg-danger-soft {
        background: #fff5f5;
    }

    .custom-card {
        border-radius: 12px;
    }

    .table td {
        vertical-align: middle;
    }

    .text-monospace {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
    }

    .is-loading {
        opacity: 0.65;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    /* Bot Status Style */
    .telegram-settings-page .tg-status-pill--bot-off {
        background: rgba(239, 68, 68, 0.18);
        color: #000000;
        border-color: rgba(254, 202, 202, 0.35);
    }
</style>

<script>
    let dtOutbox;
    const outboxEndpoint = '<?= url('admin/telegram/outbox') ?>';
    const outboxRetryEndpoint = '<?= url('admin/telegram/outbox/retry') ?>';
    const outboxDeleteEndpoint = '<?= url('admin/telegram/outbox/delete') ?>';
    const numberFormatter = new Intl.NumberFormat('vi-VN');

    $(function () {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true
        });

        dtOutbox = $('#outboxTable').DataTable({
            dom: 't<"row align-items-center mt-3"<"col-12 d-flex justify-content-md-end justify-content-center"p>>',
            responsive: true,
            autoWidth: false,
            order: [[0, "desc"]],
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [7] },
                { searchable: false, targets: [4, 5, 7] }
            ],
            language: {
                sZeroRecords: '<div class="text-center w-100 font-weight-bold py-3">Không tìm thấy tin nhắn nào khớp với bộ lọc</div>',
                oPaginate: {
                    sPrevious: '<i class="fas fa-chevron-left"></i>',
                    sNext: '<i class="fas fa-chevron-right"></i>'
                },
            },
        });

        $('#f-length').change(function () {
            dtOutbox.page.len($(this).val()).draw();
        });

        $('#f-search').on('input keyup', function () {
            dtOutbox.search(this.value.trim()).draw();
        });

        $('#f-status').change(function () {
            dtOutbox.column(4).search(this.value).draw();
        });

        $('#f-period').change(function () {
            applyRemoteFilters();
        });

        async function applyRemoteFilters() {
            setLoading(true);
            const period = $('#f-period').val();
            const url = new URL(outboxEndpoint, window.location.origin);
            url.searchParams.set('period', period);
            url.searchParams.set('ajax', '1');

            try {
                const response = await fetch(url.toString(), {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'Error');

                renderRows(data.rows || []);
                updateStats(data.stats || {});

                // Sync URL state
                const nextUrl = new URL(window.location.href);
                nextUrl.searchParams.set('period', period);
                window.history.replaceState({}, '', nextUrl.toString());

            } catch (error) {
                Toast.fire({ icon: 'error', title: 'Không thể tải lại dữ liệu' });
            } finally {
                setLoading(false);
            }
        }

        function setLoading(state) {
            $('.table-wrapper').toggleClass('is-loading', state);
            $('#f-search, #f-status, #f-period, #f-length').disabled = state;
        }

        function updateStats(stats) {
            $('#stat-pending').text(numberFormatter.format(Number(stats.pending || 0)));
            $('#stat-sent').text(numberFormatter.format(Number(stats.sent || 0)));
            $('#stat-failed').text(numberFormatter.format(Number(stats.failed || 0)));
        }

        function renderRows(rows) {
            dtOutbox.clear();
            if (rows && rows.length > 0) {
                rows.forEach(row => {
                    const statusMeta = getStatusMeta(row.status, row.try_count);
                    const webUser = row.web_username
                        ? `<span class="badge badge-light border"><i class="fas fa-user mr-1 text-primary"></i> ${escapeHtml(row.web_username)}</span>`
                        : '<span class="text-muted small">Guest / Bot</span>';

                    const timeOrder = new Date(row.created_at).getTime();
                    const timeDisplay = formatDateDisplay(row.created_at);

                    const msgEncoded = encodeURIComponent(JSON.stringify(row));

                    dtOutbox.row.add([
                        `<div class="text-center font-weight-bold">#${row.id}</div>`,
                        `<div class="d-flex align-items-center"><div class="bg-primary text-white rounded-circle mr-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 10px;"><i class="fab fa-telegram-plane"></i></div><code class="text-primary">${escapeHtml(row.telegram_id)}</code></div>`,
                        `<div class="text-center">${webUser}</div>`,
                        `<div class="text-truncate" style="max-width: 250px; font-size: 0.9rem;" title="${escapeHtml(row.message)}">${escapeHtml(row.message)}</div>`,
                        `<div class="text-center"><span class="badge ${statusMeta.badgeClass}"><i class="${statusMeta.icon} mr-1"></i>${statusMeta.label}</span></div>`,
                        `<div class="text-center"><span class="badge badge-light font-weight-bold">${row.try_count}</span></div>`,
                        `<div class="text-center text-muted small" data-order="${timeOrder}">${timeDisplay}</div>`,
                        `<div class="text-center"><button class="btn btn-xs btn-light text-primary border shadow-sm px-2" data-msg="${msgEncoded}" onclick="showDetailFromBtn(this)" title="Xem chi tiết"><i class="fas fa-eye"></i></button></div>`
                    ]);
                });
            }
            dtOutbox.draw();
        }

        function getStatusMeta(status, tryCount) {
            if (status === 'sent') return { badgeClass: 'badge-success-soft text-success', icon: 'fas fa-check-circle', label: 'ĐÃ GỬI' };
            if (status === 'fail') return { badgeClass: 'badge-danger-soft text-danger', icon: 'fas fa-exclamation-triangle', label: 'LỖI' };
            return { badgeClass: 'badge-warning-soft text-warning', icon: 'fas fa-clock', label: 'CHỜ GỬI' };
        }

        function formatDateDisplay(dateStr) {
            const d = new Date(dateStr);
            const time = [pad(d.getHours()), pad(d.getMinutes()), pad(d.getSeconds())].join(':');
            const date = [pad(d.getDate()), pad(d.getMonth() + 1), d.getFullYear()].join('/');
            return `${time}<br><span style="font-size: 10px;">${date}</span>`;
        }

        function pad(n) { return n < 10 ? '0' + n : n; }
        function escapeHtml(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

        $('#btn-retry-failed').click(function () {
            SwalHelper.confirm('Thử lại tất cả lỗi?', 'Hệ thống sẽ đặt lại trạng thái CHỜ GỬI cho các tin nhắn bị lỗi.', async () => {
                const formData = new FormData();
                formData.append('ids', 'all_fails');
                try {
                    const res = await fetch(outboxRetryEndpoint, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json());
                    if (res.success) {
                        Toast.fire({ icon: 'success', title: res.message });
                        applyRemoteFilters();
                    } else Toast.fire({ icon: 'error', title: res.message });
                } catch (e) { Toast.fire({ icon: 'error', title: 'Lỗi hệ thống' }); }
            });
        });

        $('#btn-delete-all').click(function () {
            SwalHelper.confirm('Dọn dẹp hàng đợi?', 'Hành động này sẽ xóa các tin nhắn đã gửi hoặc lỗi.', async () => {
                const formData = new FormData();
                formData.append('ids', 'all');
                try {
                    const res = await fetch(outboxDeleteEndpoint, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json());
                    if (res.success) {
                        Toast.fire({ icon: 'success', title: res.message });
                        applyRemoteFilters();
                    } else Toast.fire({ icon: 'error', title: res.message });
                } catch (e) { Toast.fire({ icon: 'error', title: 'Lỗi khi xóa' }); }
            });
        });
    });

    function showDetailFromBtn(btn) {
        try {
            const msg = JSON.parse(decodeURIComponent($(btn).data('msg')));
            $('#det-id').text(msg.id);
            $('#det-tid-display').text(msg.telegram_id + (msg.web_username ? ' (' + msg.web_username + ')' : ''));
            $('#det-message').text(msg.message);
            $('#det-created').text(msg.created_at);
            $('#det-updated').text(msg.updated_at);

            let statusHtml = '<span class="badge badge-warning px-3 py-2 text-dark">ĐANG CHỜ</span>';
            if (msg.status === 'sent') statusHtml = '<span class="badge badge-success px-3 py-2">ĐÃ GỬI</span>';
            else if (msg.status === 'fail') statusHtml = `<span class="badge badge-danger px-3 py-2">LỖI (${msg.try_count || 0}/3)</span>`;
            $('#det-status').html(statusHtml);

            if (msg.last_error) {
                $('#det-error-box').removeClass('d-none');
                $('#det-error').text(msg.last_error);
            } else $('#det-error-box').addClass('d-none');

            $('#modalDetail').modal('show');
        } catch (e) { console.error('Parse error', e); }
    }
</script>
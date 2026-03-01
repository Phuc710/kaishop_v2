<?php
/**
 * View: Quản lý Blacklist (Refactored)
 */
$pageTitle = 'Quản lý Blacklist';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('admin')],
    ['label' => 'Blacklist'],
];
$adminNeedsFlatpickr = true;
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';

$tab = $tab ?? 'ip';
$search = $search ?? '';
$summary = $summary ?? [
    'active_ip' => 0,
    'total_ip' => 0,
    'active_device' => 0,
    'total_device' => 0,
    'admin_active' => 0
];

$successMessage = '';
$errorMessage = '';
$unbanStatus = (string) ($_GET['unban'] ?? '');

if ($unbanStatus === 'ok') {
    $successMessage = 'Đã mở khóa thành công!';
} elseif ($unbanStatus === 'error') {
    $errorMessage = 'Mục tiêu không tồn tại hoặc đã được mở khóa trước đó!';
} elseif (!empty($_GET['cleared'])) {
    $successMessage = 'Đã dọn dẹp các lệnh chặn hết hạn!';
}

$statusLabel = static function (bool $active, bool $expired = false): string {
    if ($active) {
        return '<span class="badge badge-danger px-2 py-1">Đang chặn</span>';
    }
    if ($expired) {
        return '<span class="badge badge-secondary px-2 py-1">Hết hạn</span>';
    }
    return '<span class="badge badge-success px-2 py-1">Đã gỡ</span>';
};
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<section class="content pb-4 mt-1">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="info-box mb-3 border-0 shadow-sm" style="border-radius: 8px;">
                    <span class="info-box-icon bg-danger elevation-1" style="border-radius: 8px;">
                        <i class="fas fa-network-wired"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase small">IP ĐANG CHẶN</span>
                        <span class="info-box-number h4 mb-0"><?= number_format((int) $summary['active_ip']) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="info-box mb-3 border-0 shadow-sm" style="border-radius: 8px;">
                    <span class="info-box-icon bg-warning elevation-1"
                        style="border-radius: 8px; color: #fff !important;">
                        <i class="fas fa-mobile-alt"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase small">DEVICE ĐANG CHẶN</span>
                        <span
                            class="info-box-number h4 mb-0"><?= number_format((int) $summary['active_device']) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="info-box mb-3 border-0 shadow-sm" style="border-radius: 8px;">
                    <span class="info-box-icon bg-primary elevation-1" style="border-radius: 8px;">
                        <i class="fas fa-user-shield"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase small">ADMIN BAN ĐANG HỆU LỰC</span>
                        <span
                            class="info-box-number h4 mb-0"><?= number_format((int) $summary['admin_active']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Utility Bar -->
        <div class="card custom-card mb-3">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <form action="<?= url('admin/blacklist/unban') ?>" method="POST"
                            class="d-flex align-items-center">
                            <?php if (function_exists('csrf_token')): ?>
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <?php endif; ?>
                            <input type="hidden" name="type" value="ref">
                            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                            <label class="font-weight-bold small text-uppercase mr-3 mb-0"
                                style="white-space: nowrap;">MỞ KHÓA BẰNG REF:</label>
                            <input type="text" name="ref" class="form-control form-control-sm mr-2"
                                style="max-width: 250px;" placeholder="Nhập mã REF (Hex)..." required>
                            <button type="submit" class="btn btn-primary btn-sm shadow-sm font-weight-bold px-3">
                                <i class="fas fa-unlock mr-1"></i> MỞ KHÓA
                            </button>
                        </form>
                    </div>
                    <div class="col-md-5 text-md-right mt-2 mt-md-0">
                        <form action="<?= url('admin/blacklist/clear-expired') ?>" method="POST" class="d-inline"
                            onsubmit="confirmAndSubmit(event, 'Xác nhận dọn dẹp toàn bộ lệnh chặn đã hết hạn?');">
                            <?php if (function_exists('csrf_token')): ?>
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-outline-danger btn-sm font-weight-bold">
                                <i class="fas fa-broom mr-1"></i> DỌN DẸP LỆNH HẾT HẠN
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card custom-card">
            <div class="card-header border-0 pb-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h3 class="card-title text-uppercase font-weight-bold mb-2">QUẢN LÝ BLACKLIST</h3>
                    <ul class="nav nav-pills mb-2" style="background: #f1f5f9; border-radius: 30px; padding: 4px;">
                        <li class="nav-item">
                            <a class="nav-link border-0 small font-weight-bold <?= $tab === 'ip' ? 'active' : '' ?>"
                                style="border-radius: 25px; padding: 6px 16px;"
                                href="<?= url('admin/blacklist?tab=ip') ?>">IP (<?= count($ipBans) ?>)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link border-0 small font-weight-bold <?= $tab === 'device' ? 'active' : '' ?>"
                                style="border-radius: 25px; padding: 6px 16px;"
                                href="<?= url('admin/blacklist?tab=device') ?>">DEVICE (<?= count($deviceBans) ?>)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link border-0 small font-weight-bold <?= $tab === 'admin' ? 'active' : '' ?>"
                                style="border-radius: 25px; padding: 6px 16px;"
                                href="<?= url('admin/blacklist?tab=admin') ?>">HISTORY (<?= count($adminBans) ?>)</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="dt-filters">
                <div class="row g-2 mb-3 align-items-center">
                    <div class="col-md-3 mb-2">
                        <input id="f-target" class="form-control form-control-sm" placeholder="Tìm IP hoặc Username...">
                    </div>
                    <div class="col-md-3 mb-2">
                        <input id="f-reason" class="form-control form-control-sm" placeholder="Tìm Lý do hoặc Admin...">
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
                        <?php if ($tab === 'admin'): ?>
                            <a href="<?= url('admin/blacklist/clear-expired-history') ?>"
                                class="btn btn-warning btn-sm shadow-sm w-100 font-weight-bold d-flex align-items-center justify-content-center"
                                style="height:31px;" onclick="return confirm('Xóa toàn bộ lịch sử admin cũ?');">
                                <i class="fas fa-broom mr-1"></i> DỌN LỊCH SỬ
                            </a>
                        <?php else: ?>
                            <form action="<?= url('admin/blacklist/clear-expired') ?>" method="POST"
                                onsubmit="confirmAndSubmit(event, 'Xác nhận dọn dẹp toàn bộ lệnh chặn đã hết hạn?');">
                                <?php if (function_exists('csrf_token')): ?>
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <?php endif; ?>
                                <button type="submit" class="btn btn-warning btn-sm shadow-sm w-100 font-weight-bold">
                                    <i class="fas fa-broom mr-1"></i> DỌN HẾT HẠN
                                </button>
                            </form>
                        <?php endif; ?>
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
                    <?php if ($tab === 'ip'): ?>
                        <table id="banTable" class="table table-hover table-bordered w-100">
                            <thead>
                                <tr>
                                    <th class="text-center font-weight-bold align-middle">IP ADDRESS</th>
                                    <th class="text-center font-weight-bold align-middle">MÃ REF</th>
                                    <th class="text-center font-weight-bold align-middle">LÝ DO / NGUỒN</th>
                                    <th class="text-center font-weight-bold align-middle">BẮT ĐẦU</th>
                                    <th class="text-center font-weight-bold align-middle">HẾT HẠN</th>
                                    <th class="text-center font-weight-bold align-middle">TRẠNG THÁI</th>
                                    <th class="text-center font-weight-bold align-middle" style="width: 80px;">THAO TÁC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ipBans as $ban):
                                    $active = empty($ban['expires_at']) || $ban['expires_at_ts'] > TimeService::instance()->nowTs();
                                    ?>
                                    <tr>
                                        <td class="text-center align-middle">
                                            <code
                                                class="text-primary font-weight-bold"><?= htmlspecialchars((string) ($ban['ip_address'] ?? '')) ?></code>
                                        </td>
                                        <td class="text-center align-middle">
                                            <code
                                                class="text-muted"><?= htmlspecialchars((string) ($ban['ref_hash'] ?? '—')) ?></code>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span
                                                class="font-weight-bold text-dark"><?= htmlspecialchars((string) ($ban['reason'] ?? 'System')) ?></span><br>
                                            <small
                                                class="text-muted text-uppercase"><?= htmlspecialchars((string) ($ban['source'] ?? 'antiflood')) ?></small>
                                        </td>
                                        <td class="text-center align-middle"
                                            data-time-ts="<?= (int) ($ban['banned_at_ts'] ?? 0) ?>"
                                            data-time-iso="<?= htmlspecialchars((string) ($ban['banned_at_iso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= FormatHelper::eventTime($ban['banned_at_display'] ?? '', '') ?>
                                        </td>
                                        <td class="text-center align-middle"
                                            data-time-ts="<?= (int) ($ban['expires_at_ts'] ?? 0) ?>"
                                            data-time-iso="<?= htmlspecialchars((string) ($ban['expires_at_iso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= !empty($ban['expires_at']) ? FormatHelper::eventTime($ban['expires_at_display'], '') : '<span class="badge badge-pill badge-light border text-danger font-weight-bold px-2 py-1">Vĩnh viễn</span>' ?>
                                        </td>
                                        <td class="text-center align-middle"><?= $statusLabel($active, !$active) ?></td>
                                        <td class="text-center align-middle">
                                            <form action="<?= url('admin/blacklist/unban') ?>" method="POST"
                                                onsubmit="confirmAndSubmit(event, 'Mở khóa IP này?');">
                                                <?php if (function_exists('csrf_token')): ?>
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <?php endif; ?>
                                                <input type="hidden" name="tab" value="ip">
                                                <input type="hidden" name="type" value="ip">
                                                <input type="hidden" name="id" value="<?= (int) ($ban['id'] ?? 0) ?>">
                                                <button type="submit" class="btn btn-sm btn-success shadow-sm"
                                                    title="Mở khóa IP">
                                                    <i class="fas fa-unlock"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($tab === 'device'): ?>
                        <table id="banTable" class="table table-hover table-bordered w-100">
                            <thead>
                                <tr>
                                    <th class="text-center font-weight-bold align-middle">FINGERPRINT</th>
                                    <th class="text-center font-weight-bold align-middle">USER / NGUỒN</th>
                                    <th class="text-center font-weight-bold align-middle">LÝ DO</th>
                                    <th class="text-center font-weight-bold align-middle">BẮT ĐẦU</th>
                                    <th class="text-center font-weight-bold align-middle">HẾT HẠN</th>
                                    <th class="text-center font-weight-bold align-middle">TRẠNG THÁI</th>
                                    <th class="text-center font-weight-bold align-middle" style="width: 80px;">THAO TÁC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deviceBans as $ban):
                                    $active = empty($ban['expires_at']) || $ban['expires_at_ts'] > TimeService::instance()->nowTs();
                                    ?>
                                    <tr>
                                        <td class="text-center align-middle">
                                            <code
                                                class="text-primary"><?= htmlspecialchars(substr((string) ($ban['fingerprint_hash'] ?? ''), 0, 16)) ?>...</code>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span
                                                class="font-weight-bold text-primary"><?= htmlspecialchars((string) ($ban['target_username'] ?? 'Khách')) ?></span><br>
                                            <small
                                                class="text-muted text-uppercase"><?= htmlspecialchars((string) ($ban['source'] ?? 'system')) ?></small>
                                        </td>
                                        <td class="text-center align-middle text-dark font-weight-bold">
                                            <?= htmlspecialchars((string) ($ban['reason'] ?? 'Unknown')) ?>
                                        </td>
                                        <td class="text-center align-middle"
                                            data-time-ts="<?= (int) ($ban['created_at_ts'] ?? 0) ?>"
                                            data-time-iso="<?= htmlspecialchars((string) ($ban['created_at_iso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= FormatHelper::eventTime($ban['created_at_display'] ?? '', '') ?>
                                        </td>
                                        <td class="text-center align-middle"
                                            data-time-ts="<?= (int) ($ban['expires_at_ts'] ?? 0) ?>"
                                            data-time-iso="<?= htmlspecialchars((string) ($ban['expires_at_iso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= !empty($ban['expires_at']) ? FormatHelper::eventTime($ban['expires_at_display'], '') : '<span class="badge badge-pill badge-light border text-danger font-weight-bold px-2 py-1">Vĩnh viễn</span>' ?>
                                        </td>
                                        <td class="text-center align-middle"><?= $statusLabel($active, !$active) ?></td>
                                        <td class="text-center align-middle">
                                            <form action="<?= url('admin/blacklist/unban') ?>" method="POST"
                                                onsubmit="confirmAndSubmit(event, 'Mở khóa Device này?');">
                                                <?php if (function_exists('csrf_token')): ?>
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <?php endif; ?>
                                                <input type="hidden" name="tab" value="device">
                                                <input type="hidden" name="type" value="device">
                                                <input type="hidden" name="fingerprint"
                                                    value="<?= htmlspecialchars((string) ($ban['fingerprint_hash'] ?? '')) ?>">
                                                <button type="submit" class="btn btn-sm btn-success shadow-sm"
                                                    title="Mở khóa Device">
                                                    <i class="fas fa-unlock"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <table id="banTable" class="table table-hover table-bordered w-100">
                            <thead>
                                <tr>
                                    <th class="text-center font-weight-bold align-middle">PHẠM VI</th>
                                    <th class="text-center font-weight-bold align-middle">MỤC TIÊU</th>
                                    <th class="text-center font-weight-bold align-middle">LÝ DO / ADMIN</th>
                                    <th class="text-center font-weight-bold align-middle">BẮT ĐẦU</th>
                                    <th class="text-center font-weight-bold align-middle">HẾT HẠN</th>
                                    <th class="text-center font-weight-bold align-middle">TRẠNG THÁI</th>
                                    <th class="text-center font-weight-bold align-middle" style="width: 80px;">THAO TÁC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($adminBans as $ban):
                                    $isActive = (string) ($ban['status'] ?? '') === 'active' && (empty($ban['expires_at']) || $ban['expires_at_ts'] > TimeService::instance()->nowTs());
                                    $isExpired = (string) ($ban['status'] ?? '') === 'expired' || (!empty($ban['expires_at']) && $ban['expires_at_ts'] <= TimeService::instance()->nowTs());
                                    $target = (string) ($ban['target_username'] ?? '');
                                    if ($target === '' && !empty($ban['target_ip']))
                                        $target = (string) $ban['target_ip'];
                                    if ($target === '' && !empty($ban['target_fingerprint']))
                                        $target = substr((string) $ban['target_fingerprint'], 0, 16) . '...';
                                    ?>
                                    <tr>
                                        <td class="text-center align-middle font-weight-bold text-uppercase">
                                            <span
                                                class="badge badge-dark px-2"><?= htmlspecialchars((string) ($ban['scope'] ?? 'ALL')) ?></span>
                                        </td>
                                        <td class="text-center align-middle font-weight-bold text-primary">
                                            <?= htmlspecialchars($target) ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span
                                                class="text-dark font-weight-bold"><?= htmlspecialchars((string) ($ban['reason'] ?? '—')) ?></span><br>
                                            <small class="text-muted">Bởi:
                                                <?= htmlspecialchars((string) ($ban['banned_by'] ?? 'Admin')) ?></small>
                                        </td>
                                        <td class="text-center align-middle"
                                            data-time-ts="<?= (int) ($ban['started_at_ts'] ?? 0) ?>"
                                            data-time-iso="<?= htmlspecialchars((string) ($ban['started_at_iso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= FormatHelper::eventTime($ban['started_at_display'] ?? '', '') ?>
                                        </td>
                                        <td class="text-center align-middle"
                                            data-time-ts="<?= (int) ($ban['expires_at_ts'] ?? 0) ?>"
                                            data-time-iso="<?= htmlspecialchars((string) ($ban['expires_at_iso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= !empty($ban['expires_at']) ? FormatHelper::eventTime($ban['expires_at_display'], '') : '<span class="badge badge-pill badge-light border text-danger font-weight-bold px-2 py-1">Vĩnh viễn</span>' ?>
                                        </td>
                                        <td class="text-center align-middle"><?= $statusLabel($isActive, $isExpired) ?></td>
                                        <td class="text-center align-middle">
                                            <?php if ($isActive): ?>
                                                <form action="<?= url('admin/blacklist/unban') ?>" method="POST"
                                                    onsubmit="confirmAndSubmit(event, 'Mở khóa mục tiêu này?');">
                                                    <?php if (function_exists('csrf_token')): ?>
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <?php endif; ?>
                                                    <input type="hidden" name="tab" value="admin">
                                                    <?php if ((string) $ban['scope'] === 'account'): ?>
                                                        <input type="hidden" name="type" value="account">
                                                        <input type="hidden" name="username"
                                                            value="<?= htmlspecialchars((string) $ban['target_username']) ?>">
                                                    <?php elseif ((string) $ban['scope'] === 'device'): ?>
                                                        <input type="hidden" name="type" value="device">
                                                        <input type="hidden" name="fingerprint"
                                                            value="<?= htmlspecialchars((string) $ban['target_fingerprint']) ?>">
                                                    <?php endif; ?>
                                                    <button type="submit" class="btn btn-sm btn-success shadow-sm" title="Mở khóa">
                                                        <i class="fas fa-unlock"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
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
                initBanTable();

                <?php if ($successMessage !== ''): ?>
                    if (typeof SwalHelper !== 'undefined') {
                        SwalHelper.toast(<?= json_encode($successMessage, JSON_UNESCAPED_UNICODE) ?>, 'success');
                    }
                <?php endif; ?>

                <?php if ($errorMessage !== ''): ?>
                    if (typeof SwalHelper !== 'undefined') {
                        SwalHelper.toast(<?= json_encode($errorMessage, JSON_UNESCAPED_UNICODE) ?>, 'error');
                    }
                <?php endif; ?>
            }
        }, 100);
    });

    function confirmAndSubmit(e, message) {
        e.preventDefault();
        const form = e.target.closest('form');
        if (typeof SwalHelper !== 'undefined') {
            SwalHelper.confirm('Xác nhận', message, () => {
                form.submit();
            });
        } else {
            if (confirm(message)) form.submit();
        }
    }

    function initBanTable() {
        dt = $('#banTable').DataTable({
            dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center"p>>',
            responsive: true,
            autoWidth: false,
            order: [[3, 'desc']], // Mặc định sort theo cột Bắt đầu
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [6] }
            ],
            language: {
                sLengthMenu: 'Hiển thị _MENU_ mục',
                sZeroRecords: 'Không tìm thấy dữ liệu chặn',
                sInfo: 'Xem _START_–_END_ / _TOTAL_ lệnh',
                sInfoEmpty: 'Xem 0-0 / 0 lệnh',
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
                    if (selectedDates.length === 2) dt.draw();
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

        $('#f-target, #f-reason').on('input keyup', function () {
            const isHistory = window.location.search.includes('tab=admin');
            const targetCol = 0;
            const reasonCol = isHistory ? 2 : 1;

            dt.column(targetCol).search($('#f-target').val().trim());
            dt.column(reasonCol).search($('#f-reason').val().trim());
            dt.draw();
        });

        $('#btn-clear').click(function () {
            $('#f-target, #f-reason, #f-date').val('');
            $('#f-length').val('10');
            $('#f-sort').val('all');
            dt.search('').columns().search('');
            dt.page.len(10).order([3, 'desc']).draw();
        });

        $('#f-sort').change(function () {
            dt.draw();
        });

        function getBanRowTimestamp(settings, dataIndex) {
            const rowMeta = settings.aoData && settings.aoData[dataIndex] ? settings.aoData[dataIndex] : null;
            const rowNode = rowMeta ? rowMeta.nTr : null;
            if (!rowNode || !rowNode.cells || !rowNode.cells[3]) return NaN;

            const cell = rowNode.cells[3]; // Cột thời gian bắt đầu
            const tsAttr = Number(cell.getAttribute('data-time-ts') || '');
            if (!isNaN(tsAttr) && tsAttr > 0) return tsAttr * 1000;

            const iso = cell.getAttribute('data-time-iso') || '';
            if (iso) return Date.parse(iso);

            return NaN;
        }

        // Custom filter for data range and quick sort
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'banTable') return true;

            const sortVal = $('#f-sort').val();
            if (sortVal !== 'all') {
                const days = parseInt(sortVal, 10);
                if (!isNaN(days)) {
                    const rowTime = getBanRowTimestamp(settings, dataIndex);
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
            const timeCol = getBanRowTimestamp(settings, dataIndex);

            if (isNaN(min) || isNaN(max) || isNaN(timeCol)) return true;
            return timeCol >= min && timeCol <= max;
        });
    }
</script>
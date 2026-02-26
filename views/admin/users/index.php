<?php
/**
 * View: Danh sách thành viên
 * Route: GET /admin/users
 * Controller: UserController@index
 */
$pageTitle = 'Danh sách thành viên';
$breadcrumbs = [
    ['label' => 'Thành viên', 'url' => url('admin/users')],
    ['label' => 'Danh sách'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<section class="content pb-4 mt-1">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-primary elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">TỔNG THÀNH VIÊN</span>
                        <span class="info-box-number h4 mb-0"><?= number_format($totalUsers ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-danger elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-user-slash"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">TÀI KHOẢN BỊ KHÓA</span>
                        <span class="info-box-number h4 mb-0"><?= number_format($bannedUsers ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-success elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-user-shield"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">QUẢN TRỊ VIÊN</span>
                        <span class="info-box-number h4 mb-0"><?= number_format($adminUsers ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title text-uppercase font-weight-bold">DANH SÁCH THÀNH VIÊN</h3>
            </div>

            <!-- Filter Bar -->
            <div class="dt-filters">
                <!-- Search Line -->
                <div class="row g-2 mb-3">
                    <div class="col-md-3 mb-2">
                        <input id="f-keyword" class="form-control form-control-sm"
                            placeholder="Tìm Username hoặc Email...">
                    </div>
                    <div class="col-md-3 mb-2">
                        <input id="f-fingerprint" class="form-control form-control-sm"
                            placeholder="Tìm Fingerprint hash...">
                    </div>
                    <div class="col-md-2 mb-2 text-center">
                        <select id="f-status" class="form-control form-control-sm">
                            <option value="">-- TRẠNG THÁI --</option>
                            <option value="Active">ACTIVE</option>
                            <option value="Banned">BANNED</option>
                        </select>
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
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="filter-short justify-content-end">
                        <span class="filter-label">SORT BY DATE:</span>
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
                    <table id="datatable1" class="table text-nowrap table-hover table-bordered w-100">
                        <thead>
                            <tr>
                                <th class="text-center font-weight-bold align-middle">USERNAME</th>
                                <th class="text-center font-weight-bold align-middle">EMAIL</th>
                                <th class="text-center font-weight-bold align-middle">SỐ DƯ</th>
                                <th class="text-center font-weight-bold align-middle">TỔNG NẠP</th>
                                <th class="text-center font-weight-bold align-middle">TRẠNG THÁI</th>
                                <th class="text-center font-weight-bold align-middle">FINGERPRINT</th>
                                <th class="text-center font-weight-bold align-middle">NGÀY TẠO</th>
                                <th class="text-center font-weight-bold align-middle" style="width:120px">THAO TÁC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $row): ?>
                                    <tr>
                                        <td class="text-center align-middle font-weight-bold">
                                            <?= htmlspecialchars($row['username']) ?>
                                        </td>
                                        <td class="text-center align-middle"><?= htmlspecialchars($row['email']) ?></td>
                                        <td class="text-center align-middle font-weight-bold text-danger">
                                            <?= number_format($row['money']) ?>đ
                                        </td>
                                        <td class="text-center align-middle font-weight-bold text-success">
                                            <?= number_format($row['tong_nap'] ?? 0) ?>đ
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($row['bannd'] == 1): ?>
                                                <span class="badge badge-danger px-2 py-1">Banned</span>
                                            <?php else: ?>
                                                <span class="badge badge-success px-2 py-1">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php
                                            $fp = (string) ($row['fingerprint'] ?? '');
                                            if ($fp !== ''):
                                                $fpShort = substr($fp, 0, 8) . '...';
                                                ?>
                                                <span class="badge badge-secondary px-2 py-1" data-toggle="tooltip"
                                                    title="<?= htmlspecialchars($fp) ?>"
                                                    style="font-family:monospace;cursor:default;">
                                                    <?= htmlspecialchars($fpShort) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size:12px;">—</span>
                                            <?php endif; ?>
                                            <!-- Hidden full hash for DataTable search -->
                                            <span style="display:none;"><?= htmlspecialchars($fp) ?></span>
                                        </td>
                                        <td class="text-center align-middle"
                                            data-time-ts="<?= (int) ($row['list_time_ts'] ?? 0) ?>"
                                            data-time-iso="<?= htmlspecialchars((string) ($row['list_time_iso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-order="<?= (int) ($row['list_time_ts'] ?? 0) ?>">
                                            <?= FormatHelper::eventTime(
                                                $row['list_time_display'] ?? ($row['created_at'] ?? ''),
                                                $row['time'] ?? ($row['created_at'] ?? '')
                                            ) ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="btn-group">
                                                <a href="<?= url('admin/users/edit/' . $row['username']) ?>"
                                                    class="btn btn-search-dt btn-sm" title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($row['bannd'] == 1): ?>
                                                    <button type="button" class="btn btn-success btn-sm ml-1" title="Mở khóa"
                                                        onclick="unbanUser('<?= htmlspecialchars($row['username']) ?>')">
                                                        <i class="fas fa-unlock"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-warning btn-sm ml-1" title="Khóa"
                                                        onclick="banUser('<?= htmlspecialchars($row['username']) ?>', '<?= htmlspecialchars($fp) ?>')">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-danger btn-sm ml-1" title="Xóa"
                                                    onclick="deleteUser(<?= $row['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Không có dữ liệu</td>
                                </tr>
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
    let dtUser;
    function getUserRowTimestamp(settings, dataIndex, cellHtml) {
        try {
            var rowMeta = settings && settings.aoData ? settings.aoData[dataIndex] : null;
            var rowNode = rowMeta ? rowMeta.nTr : null;
            var timeCell = rowNode && rowNode.cells ? rowNode.cells[6] : null;
            if (timeCell) {
                var tsAttr = Number(timeCell.getAttribute('data-time-ts') || '');
                if (!isNaN(tsAttr) && tsAttr > 0) return tsAttr * 1000;
                var iso = timeCell.getAttribute('data-time-iso') || '';
                if (iso) {
                    if (window.KaiTime && typeof window.KaiTime.toTimestamp === 'function') {
                        var ts = window.KaiTime.toTimestamp(iso);
                        if (!isNaN(ts) && ts > 0) return ts * 1000;
                    }
                    var nativeTs = Date.parse(iso);
                    if (!isNaN(nativeTs)) return nativeTs;
                }
            }
        } catch (e) {}

        var text = String(cellHtml || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        if (window.KaiTime && typeof window.KaiTime.toTimestamp === 'function') {
            var fallbackTs = window.KaiTime.toTimestamp(text);
            if (!isNaN(fallbackTs) && fallbackTs > 0) return fallbackTs * 1000;
        }
        var nativeFallback = Date.parse(text);
        return isNaN(nativeFallback) ? NaN : nativeFallback;
    }

    $(document).ready(function () {
        dtUser = $('#datatable1').DataTable({
            dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-md-flex justify-content-end"p>>',
            responsive: true,
            autoWidth: false,
            order: [[0, "desc"]],
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [5, 7] }  // Fingerprint + action
            ],
            language: {
                sLengthMenu: 'Hiển thị _MENU_ mục',
                sZeroRecords: '<div class="text-center w-100 font-weight-bold py-3">Không tìm thấy dữ liệu</div>',
                sInfo: 'Xem _START_–_END_ / _TOTAL_ mục',
                sInfoEmpty: 'Không có dữ liệu',
                sInfoFiltered: '(lọc từ _MAX_)',
                sSearch: 'Tìm kiếm:',
                oPaginate: { sPrevious: '&lsaquo;', sNext: '&rsaquo;' },
            },
        });

        // Page Length
        $('#f-length').change(function () { dtUser.page.len($(this).val()).draw(); });

        // Keyword search (username col 0 + email col 1)
        $('#f-keyword').on('input keyup', function () {
            dtUser.search(this.value.trim()).draw();
        });

        // Fingerprint search — column 5 (full hash hidden span)
        $('#f-fingerprint').on('input keyup', function () {
            dtUser.column(5).search(this.value.trim()).draw();
        });

        // Status filter — column 4
        $('#f-status').change(function () {
            dtUser.column(4).search($(this).val()).draw();
        });

        // Date sort
        $('#f-sort').change(function () { dtUser.draw(); });

        // Date filter
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'datatable1') return true;
            var sortVal = $('#f-sort').val();
            if (sortVal !== 'all') {
                var days = parseInt(sortVal);
                if (!isNaN(days)) {
                    var rowTime = getUserRowTimestamp(settings, dataIndex, data[6]);
                    var pastTime = new Date().getTime() - (days * 24 * 60 * 60 * 1000);
                    if (!isNaN(rowTime) && rowTime < pastTime) return false;
                }
            }
            return true;
        });

        // Clear
        $('#btn-clear').click(function () {
            $('#f-keyword, #f-fingerprint').val('');
            $('#f-status').val('');
            $('#f-length').val('10');
            $('#f-sort').val('all');
            dtUser.search('').columns().search('');
            dtUser.page.len(10).order([0, 'desc']).draw();
        });
    });

    function deleteUser(id) {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: 'Hành động này không thể hoàn tác!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?= url('admin/users') ?>?delete=' + id;
            }
        });
    }

    /**
     * banUser(username, fingerprint)
     * fingerprint: trực tiếp từ data row - nếu rỗng thì ẩn option khóa thiết bị.
     */
    function banUser(username, fp) {
        const hasFp = fp && fp.trim() !== '';
        Swal.fire({
            title: 'Khóa Quyền Truy Cập',
            html: `
                <p class="mb-2">Bạn đang thao tác với tài khoản <b>${username}</b></p>
                <div class="form-group mb-2 text-left" style="margin-top:10px;">
                    <label class="font-weight-bold text-danger" style="font-size:13px;">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Hình thức
                    </label>
                    <select id="swal-ban-type" class="form-control" style="border:1px solid #ddd;border-radius:8px;font-size:14px;">
                        <option value="account">Khóa tài khoản</option>
                        ${hasFp ? '<option value="device">Khóa thiết bị (Fingerprint)</option>' : ''}
                    </select>
                </div>
                <textarea id="swal-ban-reason" class="form-control" rows="3"
                    placeholder="Nhập lý do (bắt buộc)..."
                    style="border:1px solid #ddd;border-radius:8px;font-size:14px;"></textarea>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e53e3e',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Hủy',
            focusConfirm: false,
            preConfirm: () => {
                const reason = document.getElementById('swal-ban-reason').value.trim();
                const type = document.getElementById('swal-ban-type').value;
                if (!reason) {
                    Swal.showValidationMessage('Vui lòng nhập lý do!');
                    return false;
                }
                return { reason, type };
            }
        }).then((result) => {
            if (!result.isConfirmed) return;
            const { reason, type } = result.value;
            const endpoint = type === 'device'
                ? '<?= url('admin/users/ban-device') ?>/'
                : '<?= url('admin/users/ban') ?>/';

            $.post(endpoint + encodeURIComponent(username), { reason },
                function (res) {
                    if (res.success) {
                        SwalHelper.toast(res.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        SwalHelper.toast(res.message || 'Lỗi', 'error');
                    }
                }, 'json'
            ).fail(() => SwalHelper.toast('Lỗi kết nối server', 'error'));
        });
    }

    function unbanUser(username) {
        Swal.fire({
            title: '<i class="fas fa-unlock text-success"></i> Mở khóa',
            html: `<p>Bạn có chắc muốn mở khóa tài khoản <b>${username}</b>?</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-unlock"></i> Mở khóa',
            cancelButtonText: 'Hủy',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= url('admin/users/unban') ?>/' + encodeURIComponent(username), {},
                    function (res) {
                        if (res.success) {
                            SwalHelper.toast(res.message, 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            SwalHelper.toast(res.message || 'Lỗi', 'error');
                        }
                    }, 'json'
                ).fail(() => SwalHelper.toast('Lỗi kết nối server', 'error'));
            }
        });
    }
</script>

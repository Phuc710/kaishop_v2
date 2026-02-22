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

<section class="content pb-4 mt-3">
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
                    <div class="col-md-4 mb-2">
                        <input id="f-keyword" class="form-control form-control-sm"
                            placeholder="Tìm Username hoặc Email...">
                    </div>
                    <div class="col-md-3 mb-2 text-center">
                        <select id="f-status" class="form-control form-control-sm">
                            <option value="">-- TRẠNG THÁI --</option>
                            <option value="Active">ACTIVE</option>
                            <option value="Banned">BANNED</option>
                        </select>
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
                    <table id="datatable1" class="table text-nowrap table-hover table-bordered w-100">
                        <thead>
                            <tr>
                                <th class="text-center font-weight-bold align-middle">USERNAME</th>
                                <th class="text-center font-weight-bold align-middle">EMAIL</th>
                                <th class="text-center font-weight-bold align-middle">SỐ DƯ</th>
                                <th class="text-center font-weight-bold align-middle">TỔNG NẠP</th>
                                <th class="text-center font-weight-bold align-middle">TRẠNG THÁI</th>
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
                                            <span class="badge date-badge" data-toggle="tooltip" data-placement="top"
                                                title="<?= timeAgo($row['time']) ?>">
                                                <?= htmlspecialchars((string) ($row['time'] ?? '--')) ?>
                                            </span>
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
                                                    <button type="button" class="btn btn-warning btn-sm ml-1" title="Khóa tài khoản"
                                                        onclick="banUser('<?= htmlspecialchars($row['username']) ?>')">
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
    $(document).ready(function () {
        dtUser = $('#datatable1').DataTable({
            dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-md-flex justify-content-end"p>>',
            responsive: true,
            autoWidth: false,
            order: [[0, "desc"]],
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [6] }
            ],
            language: {
                sLengthMenu: 'Hiển thị _MENU_ mục',
                sZeroRecords: '<div class="text-center w-100 font-weight-bold py-3">Không tìm thấy dữ liệu</div>',
                sInfo: 'Xem _START_–_END_ / _TOTAL_ mục',
                sInfoEmpty: 'Không có dữ liệu',
                sInfoFiltered: '(lọc từ _MAX_)',
                sSearch: 'Tìm kiếm:',
                oPaginate: {
                    sPrevious: '‹',
                    sNext: '›',
                },
            },
        });

        // Dropdown Page Length
        $('#f-length').change(function () {
            dtUser.page.len($(this).val()).draw();
        });

        // Custom Filters Action
        $('#f-keyword').on('input keyup', function () {
            // Search in Username (col 1) and Email (col 2)
            dtUser.search(this.value.trim()).draw();
        });

        $('#f-status').change(function () {
            // Search in Status (col 4)
            dtUser.column(4).search($(this).val()).draw();
        });

        // Dropdown Sort/Date Logic Ext
        $('#f-sort').change(function () {
            dtUser.draw();
        });

        // Date Filter Logic Ext
        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'datatable1') return true;

                // Sort theo dropdown (7, 15, 30 ngày)
                var sortVal = $('#f-sort').val();
                if (sortVal !== 'all') {
                    var days = parseInt(sortVal);
                    if (!isNaN(days)) {
                        // Custom parse "HH:MM DD-MM-YYYY" hoac "YYYY-MM-DD HH:MM:SS"
                        var dateStr = data[5].trim();
                        var rowTime;
                        var parts = dateStr.split(' ');
                        if (parts.length === 2 && parts[1].includes('-')) {
                            var dateParts = parts[1].split('-');
                            if (dateParts.length === 3 && dateParts[2].length === 4) {
                                // "HH:MM DD-MM-YYYY" -> YYYY-MM-DDTHH:MM:00
                                rowTime = new Date(dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0] + 'T' + parts[0] + ':00').getTime();
                            } else {
                                rowTime = new Date(dateStr).getTime();
                            }
                        } else {
                            rowTime = new Date(dateStr).getTime();
                        }

                        var pastTime = new Date().getTime() - (days * 24 * 60 * 60 * 1000);
                        if (rowTime < pastTime) return false;
                    }
                }
                return true;
            }
        );

        // Clear Filters
        $('#btn-clear').click(function () {
            $('#f-keyword').val('');
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
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?= url('admin/users') ?>?delete=' + id;
            }
        });
    }

    function banUser(username) {
        Swal.fire({
            title: 'Khóa tài khoản',
            html: `
                <p class="mb-2">Bạn đang khóa tài khoản <b>${username}</b></p>
                <textarea id="swal-ban-reason" class="form-control" rows="3" 
                    placeholder="Nhập lý do ban (bắt buộc)..."
                    style="border: 1px solid #ddd; border-radius: 8px; font-size: 14px;"></textarea>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Hủy',
            focusConfirm: false,
            preConfirm: () => {
                const reason = document.getElementById('swal-ban-reason').value.trim();
                if (!reason) {
                    Swal.showValidationMessage('Vui lòng nhập lý do ban!');
                    return false;
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= url('admin/users/ban') ?>/' + encodeURIComponent(username),
                    { reason: result.value },
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

    function unbanUser(username) {
        Swal.fire({
            title: '<i class="fas fa-unlock text-success"></i> Mở khóa tài khoản',
            html: `<p>Bạn có chắc muốn mở khóa tài khoản <b>${username}</b>?</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-unlock"></i> Mở khóa',
            cancelButtonText: 'Hủy',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= url('admin/users/unban') ?>/' + encodeURIComponent(username),
                    {},
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
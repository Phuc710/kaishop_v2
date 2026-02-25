<?php
/**
 * View: Chỉnh sửa thành viên
 * Route: GET /admin/users/edit/{username}
 * Controller: UserController@edit
 */
$pageTitle = 'Chỉnh sửa thành viên';
$breadcrumbs = [
    ['label' => 'Thành viên', 'url' => url('admin/users')],
    ['label' => 'Chỉnh sửa'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<section class="content pb-4 mt-3">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header border-0 pb-0">
                        <h3 class="card-title text-uppercase font-weight-bold">
                            HỒ SƠ THÀNH VIÊN: <span
                                class="text-primary"><?= htmlspecialchars($toz_user['username']) ?></span>
                        </h3>
                    </div>

                    <div class="card-body pt-3">
                        <div class="row">
                            <!-- Cột Trái: Thông tin cơ bản -->
                            <div class="col-xl-7 col-lg-6 mb-4 mb-lg-0">
                                <form id="userEditForm" action="<?= url('admin/users/edit/' . $toz_user['username']) ?>"
                                    method="post">
                                    <div class="form-section h-100 mb-0 d-flex flex-column">
                                        <div class="form-section-title">Thông tin tài khoản</div>

                                        <div class="row">
                                            <div
                                                class="col-md-6 text-center mb-3 d-flex flex-column align-items-center justify-content-center">
                                                <img src="<?= asset('assets/images/avt.png') ?>"
                                                    class="rounded-circle shadow-sm border mb-2"
                                                    style="width: 80px; height: 80px; object-fit: cover;" alt="Avatar">
                                                <div
                                                    class="badge badge-light-primary text-primary px-3 py-1 font-weight-bold">
                                                    <?= htmlspecialchars($toz_user['username']) ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold form-label-req">Email</label>
                                                    <input type="email" class="form-control" name="email"
                                                        value="<?= htmlspecialchars($toz_user['email']) ?>" required>
                                                </div>
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold">Trạng thái tài khoản</label>
                                                    <select class="form-control" name="bannd">
                                                        <option value="0" <?= $toz_user['bannd'] == 0 ? 'selected' : '' ?>>
                                                            ✅ Đang hoạt động (Active)</option>
                                                        <option value="1" <?= $toz_user['bannd'] == 1 ? 'selected' : '' ?>>
                                                            ❌ Đã bị khóa (Banned)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold">Thay đổi Username</label>
                                                    <input type="text" class="form-control" name="username"
                                                        value="<?= htmlspecialchars($toz_user['username']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold">Cấp bậc (Level)</label>
                                                    <select class="form-control" name="level">
                                                        <option value="0" <?= $toz_user['level'] == 0 ? 'selected' : '' ?>>
                                                            Member (Thành Viên)</option>
                                                        <option value="9" <?= $toz_user['level'] == 9 ? 'selected' : '' ?>>
                                                            Administrator (Quản Trị Viên)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </form>
                            </div>

                            <!-- Cột Phải: Tài chính -->
                            <div class="col-xl-5 col-lg-6">
                                <div class="form-section h-100 mb-0">
                                    <div class="form-section-title">Quản lý tài chính</div>

                                    <div class="form-group mb-4">
                                        <label class="font-weight-bold text-muted mb-1">Số dư hiện tại</label>
                                        <h3 class="font-weight-bold text-success mb-0">
                                            <?= number_format($toz_user['money']) ?> <small>VND</small>
                                        </h3>
                                    </div>

                                    <!-- 4 Action Buttons -->
                                    <div class="d-flex flex-wrap" style="gap: 10px;">
                                        <button type="button" class="btn btn-sm px-3 py-2 text-white font-weight-bold"
                                            onclick="openAddMoney()"
                                            style="background: linear-gradient(135deg, #22c55e, #16a34a); border: none; border-radius: 8px;">
                                            <i class="fas fa-plus mr-1"></i> Cộng số dư
                                        </button>
                                        <button type="button" class="btn btn-sm px-3 py-2 text-white font-weight-bold"
                                            onclick="openSubMoney()"
                                            style="background: linear-gradient(135deg, #ef4444, #dc2626); border: none; border-radius: 8px;">
                                            <i class="fas fa-minus mr-1"></i> Trừ số dư
                                        </button>
                                        <a href="<?= url('admin/logs/activities') ?>?user=<?= urlencode($toz_user['username']) ?>"
                                            target="_blank" class="btn btn-sm px-3 py-2 text-white font-weight-bold"
                                            style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none; border-radius: 8px;">
                                            <i class="fas fa-shopping-bag mr-1"></i> Lịch sử mua hàng
                                        </a>
                                        <a href="<?= url('admin/logs/balance-changes') ?>?user=<?= urlencode($toz_user['username']) ?>"
                                            target="_blank" class="btn btn-sm px-3 py-2 text-white font-weight-bold"
                                            style="background: linear-gradient(135deg, #38bdf8, #0ea5e9); border: none; border-radius: 8px;">
                                            <i class="fas fa-history mr-1"></i> Biến động số dư
                                        </a>

                                        <a href="<?= url('admin/logs/system') ?>" target="_blank"
                                            class="btn btn-sm px-3 py-2 text-white font-weight-bold"
                                            style="background: linear-gradient(135deg, #383bf8ff, #383bf8ff); border: none; border-radius: 8px;">
                                            <i class="fas fa-history mr-1"></i> Nhật ký hệ thống
                                        </a>

                                    </div>

                                </div>
                            </div>
                        </div>



                        <?php
                        // Financial Summary Section
                        $money = (int) ($toz_user['money'] ?? 0);
                        $tongNap = (int) ($toz_user['tong_nap'] ?? 0);
                        $tongSuDung = $tongNap - $money;
                        if ($tongSuDung < 0) {
                            $tongSuDung = 0;
                        }
                        ?>
                        <style>
                            .form-section-title {
                                font-size: 14px !important;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            }
                        </style>
                        <div class="form-section mt-4 bg-light-soft border-0 shadow-none"
                            style="background: #fbfbfd; border-radius: 12px; padding: 25px;">
                            <div class="form-section-title">Thống kê tài chính nâng cao</div>
                            <div class="row mt-3">
                                <div class="col-md-4 mb-3">
                                    <div class="finance-stat-card p-3 h-100 bg-white shadow-sm"
                                        style="border-radius: 10px; border-left: 4px solid #28a745;">
                                        <label class="font-weight-bold text-muted small text-uppercase mb-2 d-block">Số
                                            dư khả dụng</label>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span
                                                class="h4 font-weight-bold text-success mb-0"><?= number_format($money) ?>đ</span>
                                            <div class="icon-circle bg-light-success text-success"
                                                style="width: 36px; height: 36px; background: #e8f5e9; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-wallet" style="font-size: 18px;"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="finance-stat-card p-3 h-100 bg-white shadow-sm"
                                        style="border-radius: 10px; border-left: 4px solid #ffc107;">
                                        <label
                                            class="font-weight-bold text-muted small text-uppercase mb-2 d-block">Tổng
                                            tiền đã nạp</label>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="h4 font-weight-bold mb-0"
                                                style="color: #444;"><?= number_format($tongNap) ?>đ</span>
                                            <div class="icon-circle bg-light-warning text-warning"
                                                style="width: 36px; height: 36px; background: #fff8e1; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-coins" style="font-size: 18px;"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="finance-stat-card p-3 h-100 bg-white shadow-sm"
                                        style="border-radius: 10px; border-left: 4px solid #17a2b8;">
                                        <label
                                            class="font-weight-bold text-muted small text-uppercase mb-2 d-block">Tổng
                                            chi tiêu</label>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span
                                                class="h4 font-weight-bold text-info mb-0"><?= number_format($tongSuDung) ?>đ</span>
                                            <div class="icon-circle bg-light-info text-info"
                                                style="width: 36px; height: 36px; background: #e0f7fa; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-receipt" style="font-size: 18px;"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Login Info Section -->
                        <div class="form-section mt-4">
                            <div class="form-section-title">Thông tin Device User</div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold text-muted mb-1">Địa chỉ IP</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-wifi"></i></span>
                                        </div>
                                        <input type="text" class="form-control"
                                            value="<?= htmlspecialchars($toz_user['ip_address'] ?? 'Chưa có') ?>"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold text-muted mb-1">Thiết bị</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-desktop"></i></span>
                                        </div>
                                        <input type="text" class="form-control"
                                            value="<?= htmlspecialchars($toz_user['user_agent'] ?? 'Chưa có') ?>"
                                            readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold text-muted mb-1">Đăng ký tài khoản vào lúc</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="text" class="form-control"
                                            value="<?= htmlspecialchars($toz_user['time'] ?? '--') ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold text-muted mb-1">Truy cập gần nhất vào lúc</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="text" class="form-control"
                                            value="<?= htmlspecialchars($toz_user['last_login'] ?? 'Chưa có') ?>"
                                            readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="font-weight-bold text-muted mb-1"><i
                                            class="fas fa-fingerprint text-primary mr-1"></i>Fingerprint Hash</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-fingerprint"></i></span>
                                        </div>
                                        <input type="text" class="form-control font-weight-bold"
                                            style="font-family: monospace; letter-spacing: 1px;"
                                            value="<?= htmlspecialchars($toz_user['fingerprint'] ?? 'Chưa thu thập') ?>"
                                            readonly>
                                        <?php if (!empty($fingerprints) && !empty($fingerprints[0]['components'])): ?>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-primary"
                                                    onclick="showFpDetail(<?= htmlspecialchars(json_encode($fingerprints[0]['components'])) ?>)"
                                                    title="Xem chi tiết Fingerprint">
                                                    <i class="fas fa-eye"></i> Chi tiết
                                                </button>
                                                <?php if ($toz_user['bannd'] == 0): ?>
                                                    <button type="button" class="btn btn-outline-danger"
                                                        onclick="banDeviceFromEdit('<?= htmlspecialchars($toz_user['username']) ?>')"
                                                        title="Khóa vĩnh viễn thiết bị này">
                                                        <i class="fas fa-user-slash"></i> Khóa Thiết Bị
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($toz_user['bannd'] == 1 && !empty($toz_user['ban_reason'])): ?>
                                <div class="alert alert-danger d-flex align-items-center mt-2" role="alert"
                                    style="border-radius: 8px;">
                                    <i class="fas fa-exclamation-triangle mr-2" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Tài khoản bị khóa</strong><br>
                                        <span>Lý do: <?= htmlspecialchars($toz_user['ban_reason']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                    <div class="card-footer text-right bg-transparent border-top-0 pt-0 pb-4">
                        <a href="<?= url('admin/users') ?>" class="btn btn-light border mr-2 px-4">
                            <i class="fas fa-times mr-1"></i> Hủy
                        </a>
                        <button type="button" onclick="document.getElementById('userEditForm').submit()"
                            class="btn btn-primary px-4">
                            <i class="fas fa-save mr-1"></i> LƯU THÔNG TIN
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<!-- Money Popup (Uses native Bootstrap Modal loaded in foot.php -> money_popup.js) -->
<script>
    function openAddMoney() {
        adminMoneyPopup.show({
            title: '+ CỘNG TIỀN',
            actionUrl: '<?= url('admin/users/add-money/' . urlencode($toz_user['username'])) ?>',
            amountName: 'tien_cong',
            reasonName: 'rs_cong',
            confirmColor: '#20c997', // Teal from screenshot
            verb: 'cộng'
        });
    }

    function openSubMoney() {
        adminMoneyPopup.show({
            title: '- TRỪ TIỀN',
            actionUrl: '<?= url('admin/users/sub-money/' . urlencode($toz_user['username'])) ?>',
            amountName: 'tien_tru',
            reasonName: 'rs_tru',
            confirmColor: '#20c997', // Red
            verb: 'trừ'
        });
    }

    function showFpDetail(data) {
        try {
            let parsed = typeof data === 'string' ? JSON.parse(data) : data;
            Swal.fire({
                title: '<i class="fas fa-fingerprint text-primary"></i> Chi tiết Fingerprint',
                html: '<pre style="text-align:left; max-height:400px; overflow:auto; font-size:12px; background:#f8f9fa; padding:12px; border-radius:8px;">' + JSON.stringify(parsed, null, 2) + '</pre>',
                width: '700px',
                confirmButtonText: 'Đóng',
                confirmButtonColor: '#3085d6'
            });
        } catch (e) {
            Swal.fire('Lỗi', 'Không thể parse fingerprint data', 'error');
        }
    }

    function banDeviceFromEdit(username) {
        Swal.fire({
            title: 'Khóa Thiết Bị (Fingerprint)',
            html: `
                <p class="mb-2 text-danger"><i class="fas fa-exclamation-triangle"></i> Bạn đang khóa toàn bộ thiết bị của <b>${username}</b></p>
                <p class="text-muted" style="font-size: 13px;">Hành động này sẽ cấm thiết bị hiện tại truy cập vào hệ thống, bất kể họ dùng tài khoản nào.</p>
                <textarea id="swal-bandevice-reason" class="form-control" rows="3" 
                    placeholder="Nhập lý do ban thiết bị (bắt buộc)..."
                    style="border: 1px solid #ddd; border-radius: 8px; font-size: 14px;"></textarea>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Khóa ngay',
            cancelButtonText: 'Hủy',
            focusConfirm: false,
            preConfirm: () => {
                const reason = document.getElementById('swal-bandevice-reason').value.trim();
                if (!reason) {
                    Swal.showValidationMessage('Vui lòng nhập lý do ban thiết bị!');
                    return false;
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= url("admin/users/ban-device") ?>/' + encodeURIComponent(username),
                    { reason: result.value },
                    function (res) {
                        if (res.success) {
                            SwalHelper.toast(res.message, 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            SwalHelper.toast(res.message || 'Lỗi', 'error');
                        }
                    }, 'json'
                ).fail(() => SwalHelper.toast('Lỗi kết nối server', 'error'));
            }
        });
    }
</script>
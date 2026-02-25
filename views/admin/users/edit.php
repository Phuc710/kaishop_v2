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
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h3 class="card-title text-uppercase font-weight-bold mb-0">
                            HỒ SƠ THÀNH VIÊN: <span
                                class="text-primary"><?= htmlspecialchars($toz_user['username']) ?></span>
                        </h3>
                    </div>

                    <div class="card-body pt-3">
                        <div class="row">
                            <!-- Cột Trái: Thông tin cơ bản -->
                            <div class="col-xl-7 col-lg-6 mb-4 mb-lg-0">
                                <form action="<?= url('admin/users/edit/' . $toz_user['username']) ?>" method="post">
                                    <div class="form-section h-100 mb-0">
                                        <div class="form-section-title">Thông tin tài khoản</div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold form-label-req">Username</label>
                                                    <input type="text" class="form-control" name="username"
                                                        value="<?= htmlspecialchars($toz_user['username']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold form-label-req">Email</label>
                                                    <input type="email" class="form-control" name="email"
                                                        value="<?= htmlspecialchars($toz_user['email']) ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold">Trạng thái (Bannd)</label>
                                                    <select class="form-control" name="bannd">
                                                        <option value="0" <?= $toz_user['bannd'] == 0 ? 'selected' : '' ?>>
                                                            Active (Hoạt động)</option>
                                                        <option value="1" <?= $toz_user['bannd'] == 1 ? 'selected' : '' ?>>
                                                            Banned (Bị khóa)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold">Cấp bậc (Level)</label>
                                                    <select class="form-control" name="level">
                                                        <option value="0" <?= $toz_user['level'] == 0 ? 'selected' : '' ?>>
                                                            Thành Viên</option>
                                                        <option value="9" <?= $toz_user['level'] == 9 ? 'selected' : '' ?>>
                                                            Quản Trị Viên (Admin)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mt-4 mb-0 text-right">
                                            <button type="submit" class="btn btn-primary px-4">
                                                <i class="fas fa-save mr-1"></i>Lưu thông tin
                                            </button>
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
                                            style="background: linear-gradient(135deg, #845adf, #6f42c1); border: none; border-radius: 8px;">
                                            <i class="fas fa-history mr-1"></i> Lịch sử giao dịch
                                        </a>
                                        <a href="<?= url('admin/logs/balance-changes') ?>?user=<?= urlencode($toz_user['username']) ?>"
                                            target="_blank" class="btn btn-sm px-3 py-2 text-white font-weight-bold"
                                            style="background: linear-gradient(135deg, #38bdf8, #0ea5e9); border: none; border-radius: 8px;">
                                            <i class="fas fa-history mr-1"></i> Biến động số dư
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
                        <div class="form-section mt-4">
                            <div class="form-section-title">Tổng quan tài chính</div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold text-muted mb-1">Số dư hiện tại</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-wallet"
                                                    style="color:#28a745;"></i></span>
                                        </div>
                                        <input type="text" class="form-control font-weight-bold text-success"
                                            value="<?= number_format($money) ?>đ" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold text-muted mb-1">Tổng tiền nạp</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i
                                                    class="fas fa-coins text-warning"></i></span>
                                        </div>
                                        <input type="text" class="form-control font-weight-bold"
                                            value="<?= number_format($tongNap) ?>đ" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold text-muted mb-1">Tổng tiền đã sử dụng</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i
                                                    class="fas fa-receipt text-info"></i></span>
                                        </div>
                                        <input type="text" class="form-control font-weight-bold"
                                            value="<?= number_format($tongSuDung) ?>đ" readonly>
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
<?php
/**
 * View: Cấu hình Website
 * Route: GET /admin/setting
 * Controller: SettingController@index
 */
$pageTitle = 'Cài đặt Website';
$breadcrumbs = [
    ['label' => 'Hệ thống', 'url' => url('admin/setting')],
    ['label' => 'Cài đặt'],
];
require_once __DIR__ . '/layout/head.php';
require_once __DIR__ . '/layout/breadcrumb.php';
?>

<style>
    .card-title i {
        display: none !important;
    }

    .card-body h5,
    .card-body h6 {
        font-size: 17px !important;
    }

    .card-body h5 i,
    .card-body h6 i {
        display: none !important;
    }

    .btn-primary {
        color: #fff !important;
        background-color: #17a2b8 !important;
        border-color: #148ea1 !important;
    }

    .btn-primary:hover {
        background-color: #138496 !important;
        border-color: #117a8b !important;
    }

    .alert a {
        color: #fff !important;
        text-decoration: underline !important;
    }

    .alert a:hover {
        text-decoration: underline !important;
        opacity: 0.9;
    }
</style>

<section class="content pb-4 mt-3">
    <div class="container-fluid">
        <!-- GENERAL SETTINGS -->
        <div class="row">
            <div class="col-md-12">
                <div class="card custom-card">
                    <form id="form-general" class="form-horizontal">
                        <div class="card-header border-0">
                            <h3 class="card-title text-uppercase font-weight-bold">
                                THÔNG TIN CƠ BẢN
                            </h3>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group text-center mt-2 mb-3">
                                        <h5 class="font-weight-bold text-muted text-uppercase">
                                            CẤU HÌNH HÌNH ẢNH
                                        </h5>
                                        <hr style="width: 50px; border-top: 2px solid var(--primary);">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">LOGO (Header)</label>
                                        <input type="text" class="form-control" name="logo" placeholder="https://..."
                                            value="<?= htmlspecialchars($chungapi['logo'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">LOGO (Footer)</label>
                                        <input type="text" class="form-control" name="logo_footer"
                                            placeholder="https://..."
                                            value="<?= htmlspecialchars($chungapi['logo_footer'] ?? $chungapi['logo'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">FAVICON</label>
                                        <input type="text" class="form-control" name="favicon" placeholder="https://..."
                                            value="<?= htmlspecialchars($chungapi['favicon'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="col-md-12 mt-4">
                                    <div class="form-group text-center mb-3">
                                        <h5 class="font-weight-bold text-muted text-uppercase">
                                            LIÊN KẾT MẠNG XÃ HỘI
                                        </h5>
                                        <hr style="width: 50px; border-top: 2px solid #28a745;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">LINK FACEBOOK</label>
                                        <input type="url" class="form-control" name="fb_admin"
                                            placeholder="https://facebook.com/..."
                                            value="<?= htmlspecialchars($chungapi['fb_admin'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">LINK TELEGRAM</label>
                                        <input type="text" class="form-control" name="tele_admin"
                                            placeholder="https://t.me/yourtelegram"
                                            value="<?= htmlspecialchars($chungapi['tele_admin'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">LINK TIKTOK</label>
                                        <input type="url" class="form-control" name="tiktok_admin"
                                            placeholder="https://tiktok.com/@..."
                                            value="<?= htmlspecialchars($chungapi['tiktok_admin'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">LINK YOUTUBE</label>
                                        <input type="url" class="form-control" name="youtube_admin"
                                            placeholder="https://youtube.com/@..."
                                            value="<?= htmlspecialchars($chungapi['youtube_admin'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="col-md-12 mt-4">
                                    <div class="form-group text-center mb-3">
                                        <h5 class="font-weight-bold text-muted text-uppercase">
                                            THÔNG TIN WEB & SEO
                                        </h5>
                                        <hr style="width: 50px; border-top: 2px solid #17a2b8;">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">TÊN WEBSITE</label>
                                        <input type="text" class="form-control" name="ten_web"
                                            placeholder="DAILYCODE.VN"
                                            value="<?= htmlspecialchars($chungapi['ten_web'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">SĐT ZALO</label>
                                        <input type="text" class="form-control" name="sdt_admin"
                                            placeholder="0812420710"
                                            value="<?= htmlspecialchars($chungapi['sdt_admin'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">EMAIL LIÊN HỆ</label>
                                        <input type="text" class="form-control" name="email_cf"
                                            placeholder="hotro@kaishop.vn"
                                            value="<?= htmlspecialchars($chungapi['email_cf'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">MÔ TẢ WEBSITE (SEO)</label>
                                        <input type="text" class="form-control" name="mo_ta" placeholder="Mô tả website"
                                            value="<?= htmlspecialchars($chungapi['mo_ta'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-right">
                            <button type="submit" class="btn btn-primary shadow-sm px-4 font-weight-bold"
                                style="border-radius: 8px;">
                                <i class="fas fa-save mr-1"></i> LƯU THAY ĐỔI
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- SMTP SETTINGS -->
            <div class="col-lg-6">
                <form id="form-smtp">
                    <div class="card custom-card">
                        <div class="card-header border-0">
                            <h3 class="card-title text-uppercase font-weight-bold">
                                CẤU HÌNH SMTP GMAIL
                            </h3>
                        </div>
                        <div class="card-body pt-0">
                            <div class="alert alert-info py-2" style="border-radius: 8px;">
                                <i class="fas fa-lightbulb mr-1"></i> <a
                                    href="https://myaccount.google.com/apppasswords" target="_blank"
                                    class="font-weight-bold">Lấy mật khẩu ứng dụng Google tại đây</a>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold small text-uppercase">Tên người gửi</label>
                                <input type="text" name="ten_nguoi_gui"
                                    value="<?= htmlspecialchars($chungapi['ten_nguoi_gui'] ?? ''); ?>"
                                    class="form-control" placeholder="KaiShop">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold small text-uppercase">Email gửi tin</label>
                                <input type="email" name="email_auto"
                                    value="<?= htmlspecialchars($chungapi['email_auto'] ?? ''); ?>" class="form-control"
                                    placeholder="example@gmail.com">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold small text-uppercase">Mật khẩu ứng dụng</label>
                                <input type="password" name="pass_mail_auto"
                                    value="<?= htmlspecialchars($chungapi['pass_mail_auto'] ?? ''); ?>"
                                    class="form-control" placeholder="xxxx xxxx xxxx xxxx">
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-right">
                            <button type="submit" class="btn btn-primary shadow-sm px-4 font-weight-bold"
                                style="border-radius: 8px;">
                                <i class="fas fa-save mr-1"></i> LƯU SMTP
                            </button>
                        </div>
                    </div>
                </form>

                <!-- NOTIFICATION SETTINGS -->
                <form id="form-notification">
                    <div class="card custom-card mt-3">
                        <div class="card-header border-0">
                            <h3 class="card-title text-uppercase font-weight-bold">
                                THÔNG BÁO WEBSITE
                            </h3>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">KIỂU POPUP HIỂN THỊ</label>
                                        <select class="form-control" name="popup_template">
                                            <option value="0" <?= ($chungapi['popup_template'] ?? '1') === '0' ? 'selected' : '' ?>>Tắt Popup</option>
                                            <option value="1" <?= ($chungapi['popup_template'] ?? '1') === '1' ? 'selected' : '' ?>>Mặc định (Khuyến mãi)</option>
                                            <option value="2" <?= ($chungapi['popup_template'] ?? '1') === '2' ? 'selected' : '' ?>>Thông báo (Nội dung bên dưới)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold small text-uppercase">NỘI DUNG THÔNG BÁO</label>
                                        <textarea class="form-control" name="thongbao" rows="5"
                                            placeholder="Nhập nội dung HTML thông báo..."><?= htmlspecialchars($chungapi['thongbao'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-right">
                            <button type="submit" class="btn btn-primary shadow-sm px-4 font-weight-bold"
                                style="border-radius: 8px;">
                                <i class="fas fa-save mr-1"></i> LƯU THÔNG BÁO
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- BANK & PROMO SETTINGS -->
            <div class="col-lg-6">
                <form id="form-bank">
                    <div class="card custom-card">
                        <div class="card-header border-0">
                            <h3 class="card-title text-uppercase font-weight-bold">
                                NGÂN HÀNG & KHUYẾN MÃI
                            </h3>
                        </div>
                        <div class="card-body pt-0">
                            <div class="form-group border-bottom pb-3 mb-3">
                                <label class="font-weight-bold small text-uppercase text-muted">WEBHOOK URL
                                    (SEPAY)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light font-weight-bold text-primary"
                                        value="<?= url('api/sepay/webhook') ?>" id="sepay_webhook_url" readonly>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-primary" onclick="copyWebhook()">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">Ngân hàng</label>
                                        <select name="bank_name" class="form-control">
                                            <?php
                                            $banks = ['MB Bank', 'Vietcombank', 'Techcombank', 'VietinBank', 'BIDV', 'Agribank', 'VPBank', 'ACB', 'Sacombank', 'TPBank', 'MSB', 'OCB', 'VIB', 'Momo'];
                                            $currentBank = $chungapi['bank_name'] ?? 'MB Bank';
                                            foreach ($banks as $b) {
                                                $sel = ($currentBank === $b) ? 'selected' : '';
                                                echo "<option value=\"{$b}\" {$sel}>{$b}</option>";
                                            }
                                            if (!in_array($currentBank, $banks)) {
                                                echo "<option value=\"{$currentBank}\" selected>{$currentBank}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">Số tài khoản</label>
                                        <input type="text" name="bank_account" class="form-control"
                                            value="<?= htmlspecialchars($chungapi['bank_account'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">Chủ tài khoản</label>
                                        <input type="text" name="bank_owner" class="form-control"
                                            value="<?= htmlspecialchars($chungapi['bank_owner'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group border-bottom pb-3">
                                        <label class="font-weight-bold small text-uppercase">SePay API Key</label>
                                        <input type="password" name="sepay_api_key" class="form-control"
                                            value="<?= htmlspecialchars($chungapi['sepay_api_key'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <h6 class="mt-3 font-weight-bold text-primary"><i class="fas fa-gift mr-1"></i> CÁC MỐC
                                KHUYẾN MÃI NẠP</h6>
                            <div class="row">
                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <div class="col-6">
                                        <div class="form-group mb-2">
                                            <label class="small text-muted font-weight-bold">Mốc <?= $i ?> (Số tiền)</label>
                                            <input type="number" name="bonus_<?= $i ?>_amount"
                                                class="form-control form-control-sm"
                                                value="<?= $chungapi["bonus_{$i}_amount"] ?? (100000 * $i) ?>">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group mb-2">
                                            <label class="small text-muted font-weight-bold">Khuyến mãi %</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="bonus_<?= $i ?>_percent" class="form-control"
                                                    value="<?= $chungapi["bonus_{$i}_percent"] ?? ($i * 5 + 5) ?>">
                                                <div class="input-group-append"><span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-right">
                            <button type="submit" class="btn btn-primary shadow-sm px-4 font-weight-bold"
                                style="border-radius: 8px;">
                                <i class="fas fa-save mr-1"></i> LƯU NGÂN HÀNG
                            </button>
                        </div>
                    </div>
                </form>

                <!-- MAINTENANCE SETTINGS -->
                <form id="form-maintenance">
                    <div class="card custom-card mt-3">
                        <div class="card-header border-0">
                            <h3 class="card-title text-uppercase font-weight-bold">
                                BẢO TRÌ HỆ THỐNG
                            </h3>
                        </div>
                        <div class="card-body pt-0">
                            <div class="form-group mb-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="maintenance_enabled"
                                        name="maintenance_enabled" value="1" <?= !empty($maintenanceConfig['enabled']) ? 'checked' : '' ?>>
                                    <label class="custom-control-label font-weight-bold text-uppercase small"
                                        for="maintenance_enabled">BẬT CHẾ ĐỘ BẢO TRÌ</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">Bắt đầu</label>
                                        <input type="datetime-local" class="form-control" name="maintenance_start_at"
                                            value="<?= htmlspecialchars(str_replace(' ', 'T', $maintenanceConfig['start_at'] ?? '')) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">Thời lượng (phút)</label>
                                        <input type="number" class="form-control" name="maintenance_duration_minutes"
                                            value="<?= (int) ($maintenanceConfig['duration_minutes'] ?? 60) ?>">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold small text-uppercase">Lời nhắn bảo trì</label>
                                        <textarea class="form-control" name="maintenance_message" rows="3"
                                            placeholder="Hệ thống đang bảo trì, vui lòng quay lại sau..."><?= htmlspecialchars((string) ($maintenanceConfig['message'] ?? '')) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-right">
                            <button type="button" id="btn-clear-maintenance"
                                class="btn btn-outline-danger shadow-sm mr-2 font-weight-bold"
                                style="border-radius: 8px;">
                                <i class="fas fa-times mr-1"></i> TẮT NGAY
                            </button>
                            <button type="submit" class="btn btn-primary shadow-sm px-4 font-weight-bold"
                                style="border-radius: 8px;">
                                <i class="fas fa-save mr-1"></i> LƯU LỊCH
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/foot.php'; ?>

<script>
    $(document).ready(function () {
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        function handleFormSubmit(formId, actionName, onSuccess) {
            $('#' + formId).on('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('action', actionName);

                var btn = $(this).find('button[type="submit"]');
                var originalText = btn.html();
                btn.html('<i class="fas fa-circle-notch fa-spin mr-1"></i>').prop('disabled', true);

                $.ajax({
                    url: '<?= url('admin/setting/update') ?>',
                    type: 'POST',
                    data: formData,
                    contentType: false, processData: false,
                    success: function (res) {
                        btn.html(originalText).prop('disabled', false);
                        if (res.status === 'success') {
                            Toast.fire({ icon: 'success', title: res.message });
                            if (onSuccess) onSuccess(res);
                        } else {
                            Toast.fire({ icon: 'error', title: res.message || 'Lỗi!' });
                        }
                    },
                    error: function () {
                        btn.html(originalText).prop('disabled', false);
                        Toast.fire({ icon: 'error', title: 'Lỗi máy chủ!' });
                    }
                });
            });
        }

        handleFormSubmit('form-general', 'update_general');
        handleFormSubmit('form-smtp', 'update_smtp');
        handleFormSubmit('form-notification', 'update_notification');
        handleFormSubmit('form-bank', 'update_bank');
        handleFormSubmit('form-maintenance', 'update_maintenance', () => setTimeout(() => location.reload(), 700));

        $('#btn-clear-maintenance').on('click', function () {
            $.post('<?= url('admin/setting/update') ?>', { action: 'clear_maintenance' }, function (res) {
                if (res.status === 'success') {
                    Toast.fire({ icon: 'success', title: res.message });
                    setTimeout(() => location.reload(), 700);
                } else {
                    Toast.fire({ icon: 'error', title: res.message });
                }
            }, 'json');
        });
    });

    function copyWebhook() {
        var copyText = document.getElementById("sepay_webhook_url");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        SwalHelper.toast('Đã sao chép Webhook URL', 'success');
    }
</script>
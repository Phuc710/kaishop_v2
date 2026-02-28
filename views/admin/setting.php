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

$telegramTokenStored = trim((string) ($chungapi['telegram_bot_token'] ?? ''));
$telegramChatIdStored = trim((string) ($chungapi['telegram_chat_id'] ?? ''));
$telegramConfigured = $telegramTokenStored !== '' && $telegramChatIdStored !== '';
$maskedTelegramToken = '';
if ($telegramTokenStored !== '') {
    $head = substr($telegramTokenStored, 0, 6);
    $tail = substr($telegramTokenStored, -4);
    $maskedTelegramToken = $head . '***' . $tail;
}
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

                <form id="form-telegram">
                    <div class="card custom-card mt-3">
                        <div class="card-header border-0">
                            <h3 class="card-title text-uppercase font-weight-bold">
                                TELEGRAM BOT ALERT
                            </h3>
                        </div>
                        <div class="card-body pt-0">
                            <div class="alert alert-warning py-2" style="border-radius: 8px;">
                                <i class="fas fa-shield-alt mr-1"></i>
                                Bảo mật: Bot token sẽ không hiển thị lại trên giao diện.
                            </div>

                            <div class="mb-3">
                                <span
                                    class="badge <?= $telegramConfigured ? 'badge-success' : 'badge-secondary' ?> mr-1">
                                    <?= $telegramConfigured ? 'ĐÃ CẤU HÌNH' : 'CHƯA CẤU HÌNH' ?>
                                </span>
                                <?php if ($maskedTelegramToken !== ''): ?>
                                    <span class="small text-muted">Token hiện tại:
                                        <?= htmlspecialchars($maskedTelegramToken) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold small text-uppercase">Telegram Bot Token</label>
                                <input type="password" class="form-control" name="telegram_bot_token"
                                    placeholder="Để trống để giữ token hiện tại" autocomplete="new-password">
                                <small class="text-muted">Định dạng thường: `123456789:AA...`</small>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold small text-uppercase">Telegram Chat ID / Channel</label>
                                <input type="text" class="form-control" name="telegram_chat_id"
                                    placeholder="-1001234567890 hoặc @channel_name"
                                    value="<?= htmlspecialchars($telegramChatIdStored) ?>">
                            </div>

                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" value="1"
                                    id="clear_telegram_bot_token" name="clear_telegram_bot_token">
                                <label for="clear_telegram_bot_token" class="custom-control-label">
                                    Xóa token hiện tại (chỉ giữ Chat ID)
                                </label>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-right">
                            <button type="submit" class="btn btn-primary shadow-sm px-4 font-weight-bold"
                                style="border-radius: 8px;">
                                <i class="fas fa-save mr-1"></i> LƯU TELEGRAM
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
                            <h3 class="card-title text-uppercase font-weight-bold">BẢO TRÌ HỆ THỐNG</h3>
                        </div>
                        <div class="card-body pt-0">
                            <div class="form-group mb-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="maintenance_enabled"
                                        name="maintenance_enabled" value="1" <?= !empty($maintenanceConfig['enabled']) ? 'checked' : '' ?>>
                                    <label class="custom-control-label font-weight-bold text-uppercase small"
                                        for="maintenance_enabled">Bật bảo trì thủ công</label>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    OFF sẽ chạy theo lịch đã setup. ON sẽ ép website vào bảo trì ngay và giữ nguyên
                                    cho tới khi bạn tắt lại, kể cả khi giờ kết thúc trong lịch đã qua.
                                </small>
                            </div>

                            <input type="hidden" name="maintenance_notice_minutes"
                                value="<?= (int) ($maintenanceConfig['notice_minutes'] ?? 5) ?>">
                            <div id="maintenanceRuntimeCard" class="border rounded p-3 mb-3 bg-light">
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                    <div class="font-weight-bold text-uppercase small">Trạng thái bảo trì
                                    </div>
                                    <span id="maintenanceStatusBadge" class="badge badge-secondary px-2 py-1">Đang
                                        tải...</span>
                                </div>
                                <div id="maintenanceStatusText" class="small text-muted mb-3">Đang đồng bộ trạng thái...</div>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <div class="small text-muted text-uppercase font-weight-bold">Đếm ngược tới bắt
                                            đầu</div>
                                        <div id="maintenanceCountdownStart" class="font-weight-bold">--:--:--</div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="small text-muted text-uppercase font-weight-bold">Countdown cảnh
                                            báo</div>
                                        <div id="maintenanceCountdownNotice" class="font-weight-bold">--:--:--</div>
                                    </div>

                                    <div class="col-md-6 mb-2">
                                        <div class="small text-muted text-uppercase font-weight-bold">Thời gian còn lại
                                        </div>
                                        <div id="maintenanceCountdownEnd" class="font-weight-bold">--:--:--</div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="small text-muted text-uppercase font-weight-bold">Thời gian hoạt
                                            động</div>
                                        <div id="maintenanceElapsed" class="font-weight-bold">--:--:--</div>
                                    </div>
                                </div>
                                <div id="maintenanceSyncMeta" class="small text-muted mt-2">Đang lấy dữ liệu từ máy chủ...</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">Bắt đầu</label>
                                        <input type="datetime-local" class="form-control" name="maintenance_start_at"
                                            value="<?= htmlspecialchars((string) ($maintenanceStartInput ?? '')) ?>">
                                        <small class="text-muted">Lưu lịch không phụ thuộc công tắc thủ công ở trên.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold small text-uppercase">Kết thúc</label>
                                        <input type="datetime-local" class="form-control" name="maintenance_end_at"
                                            value="<?= htmlspecialchars((string) ($maintenanceEndInput ?? '')) ?>">
                                        <small class="text-muted">Nếu bỏ trống, mặc định kết thúc sau 1 giờ kể từ bắt
                                            đầu.</small>
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
                                <i class="fas fa-times mr-1"></i> Tắt ngay
                            </button>
                            <button type="submit" class="btn btn-primary shadow-sm px-4 font-weight-bold"
                                style="border-radius: 8px;">
                                <i class="fas fa-save mr-1"></i> Lưu lịch
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/foot.php'; ?>
<script src="<?= asset('assets/js/maintenance-runtime.js') ?>"></script>

<script>
    $(document).ready(function () {
        const CSRF_TOKEN = '<?= function_exists('csrf_token') ? csrf_token() : '' ?>';
        const MAINTENANCE_STATUS_URL = '<?= url('api/system/maintenance-status') ?>';
        let maintenanceStatusPollOnce = null;

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
                if (CSRF_TOKEN) formData.append('csrf_token', CSRF_TOKEN);

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
        handleFormSubmit('form-telegram', 'update_telegram');
        handleFormSubmit('form-bank', 'update_bank');
        handleFormSubmit('form-maintenance', 'update_maintenance', () => setTimeout(() => location.reload(), 700));

        (function initMaintenanceStatusRealtime() {
            const $badge = $('#maintenanceStatusBadge');
            const $statusText = $('#maintenanceStatusText');
            const $start = $('#maintenanceCountdownStart');
            const $noticeCd = $('#maintenanceCountdownNotice');
            const $end = $('#maintenanceCountdownEnd');
            const $elapsed = $('#maintenanceElapsed');
            const $meta = $('#maintenanceSyncMeta');

            function fmt(sec) {
                const n = Math.max(0, Math.floor(Number(sec || 0)));
                const h = String(Math.floor(n / 3600)).padStart(2, '0');
                const m = String(Math.floor((n % 3600) / 60)).padStart(2, '0');
                const s = String(n % 60).padStart(2, '0');
                return h + ':' + m + ':' + s;
            }

            function setCountdown($el, seconds, fallback) {
                if (!Number.isFinite(Number(seconds))) {
                    $el.text(fallback || '--:--:--');
                    return;
                }
                $el.text(fmt(seconds));
            }

            function setBadge(phase) {
                const map = {
                    off: { cls: 'badge-secondary', text: 'TẮT' },
                    scheduled: { cls: 'badge-info', text: 'ĐÃ LÊN LỊCH' },
                    countdown: { cls: 'badge-warning', text: 'COUNTDOWN 5 PHÚT' },
                    active: { cls: 'badge-danger', text: 'ĐANG BẢO TRÌ' },
                    finished: { cls: 'badge-success', text: 'ĐÃ KẾT THÚC' }
                };
                const chosen = map[phase] || map.off;
                $badge.removeClass('badge-secondary badge-danger badge-warning badge-info badge-success').addClass(chosen.cls).text(chosen.text);
            }

            function inferPhase(state) {
                if (!state) return 'off';
                if (state.phase) return String(state.phase);
                if (state.active) return 'active';
                if (state.notice_active) return 'countdown';
                if (state.scheduled) return 'scheduled';
                return 'off';
            }

            function render(snapshot) {
                const state = snapshot && snapshot.state ? snapshot.state : null;
                if (!state) {
                    setBadge('off');
                    $statusText.text('Không có dữ liệu trạng thái bảo trì.');
                    setCountdown($start, null, 'Chưa có lịch');
                    setCountdown($noticeCd, null, 'Chưa có countdown');
                    setCountdown($end, null, 'Chưa chạy');
                    setCountdown($elapsed, null, 'Chưa chạy');
                    return;
                }

                const phase = inferPhase(state);
                const secondsUntilStart = Number.isFinite(Number(snapshot.secondsUntilStart))
                    ? Number(snapshot.secondsUntilStart)
                    : (Number.isFinite(Number(state.seconds_until_start)) ? Number(state.seconds_until_start) : null);
                const noticeLeft = Number.isFinite(Number(snapshot.noticeSecondsLeft))
                    ? Number(snapshot.noticeSecondsLeft)
                    : (Number.isFinite(Number(state.notice_seconds_left)) ? Number(state.notice_seconds_left) : null);
                const secondsUntilEnd = Number.isFinite(Number(snapshot.secondsUntilEnd))
                    ? Number(snapshot.secondsUntilEnd)
                    : (Number.isFinite(Number(state.seconds_until_end)) ? Number(state.seconds_until_end) : null);

                const nowTs = Number.isFinite(Number(snapshot.serverNowTs))
                    ? Number(snapshot.serverNowTs)
                    : Math.floor(Date.now() / 1000);
                const startTs = Number.isFinite(Number(state.start_at_ts)) ? Number(state.start_at_ts) : null;
                const elapsed = (phase === 'active' && Number.isFinite(startTs)) ? Math.max(0, nowTs - startTs) : null;

                setBadge(phase);
                $statusText.text(String(state.status_text || 'Không có trạng thái'));

                const manualActive = !!state.active_by_manual;
                setCountdown($start, secondsUntilStart, manualActive ? 'Đang giữ thủ công' : (phase === 'active' ? 'Đã bắt đầu' : 'Chưa có lịch'));
                setCountdown($noticeCd, noticeLeft, manualActive ? 'Bỏ qua countdown' : (phase === 'active' ? 'Đã vào bảo trì' : 'Chưa vào 5 phút cảnh báo'));
                setCountdown($end, manualActive ? null : secondsUntilEnd, manualActive ? 'Thủ công, không đếm giờ' : (phase === 'active' ? 'Không có thời lượng kết thúc' : 'Chưa chạy'));
                setCountdown($elapsed, elapsed, phase === 'active' ? '00:00:00' : 'Chưa chạy');

                const meta = [];
                meta.push('Trạng thái: ' + phase.toUpperCase());
                if (state.start_at_display) meta.push('Bắt đầu: ' + state.start_at_display);
                if (state.end_at_display) meta.push('Kết thúc: ' + state.end_at_display);
                meta.push('Notice: ' + (state.notice_minutes || 5) + ' phút');
                $meta.text(meta.join(' | '));
            }

            if (typeof window.KaiMaintenanceRuntime === 'function') {
                const runtime = new window.KaiMaintenanceRuntime({
                    statusUrl: MAINTENANCE_STATUS_URL,
                    pollMs: 3000
                });
                runtime.onUpdate(render);
                maintenanceStatusPollOnce = function () {
                    $.getJSON(MAINTENANCE_STATUS_URL, function (res) {
                        if (!res || !res.success || !res.maintenance) return;
                        render({
                            state: res.maintenance,
                            secondsUntilStart: res.maintenance.seconds_until_start,
                            secondsUntilEnd: res.maintenance.seconds_until_end,
                            noticeSecondsLeft: res.maintenance.notice_seconds_left,
                            serverNowTs: res.maintenance.server_time_ts
                        });
                    });
                };
                runtime.start();
            } else {
                function pollOnce() {
                    $.getJSON(MAINTENANCE_STATUS_URL, function (res) {
                        if (!res || !res.success || !res.maintenance) return;
                        render({
                            state: res.maintenance,
                            secondsUntilStart: res.maintenance.seconds_until_start,
                            secondsUntilEnd: res.maintenance.seconds_until_end,
                            noticeSecondsLeft: res.maintenance.notice_seconds_left,
                            serverNowTs: res.maintenance.server_time_ts
                        });
                    });
                }
                maintenanceStatusPollOnce = pollOnce;
                pollOnce();
                setInterval(pollOnce, 3000);
            }
        })();

        $('#maintenance_enabled').on('change', function () {
            const $switch = $(this);
            const checked = $switch.is(':checked');

            $switch.prop('disabled', true);
            $.post('<?= url('admin/setting/update') ?>', {
                action: 'toggle_maintenance_manual',
                maintenance_enabled: checked ? 1 : 0,
                csrf_token: CSRF_TOKEN
            }, function (res) {
                $switch.prop('disabled', false);

                if (res && res.status === 'success') {
                    Toast.fire({ icon: 'success', title: res.message });
                    if (typeof maintenanceStatusPollOnce === 'function') {
                        maintenanceStatusPollOnce();
                    }
                    return;
                }

                $switch.prop('checked', !checked);
                Toast.fire({ icon: 'error', title: (res && res.message) ? res.message : 'Lỗi cập nhật bảo trì!' });
            }, 'json').fail(function () {
                $switch.prop('disabled', false);
                $switch.prop('checked', !checked);
                Toast.fire({ icon: 'error', title: 'Lỗi máy chủ!' });
            });
        });

        $('#btn-clear-maintenance').on('click', function () {
            $.post('<?= url('admin/setting/update') ?>', { action: 'clear_maintenance', csrf_token: CSRF_TOKEN }, function (res) {
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

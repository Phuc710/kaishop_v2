<?php
/**
 * View: Telegram Bot — Cấu hình
 * Route: GET /admin/telegram/settings
 */
require_once __DIR__ . '/../layout/head.php';
$pageTitle = 'Cấu hình Telegram Bot';
$breadcrumbs = [
    ['label' => 'Telegram Bot', 'url' => url('admin/telegram')],
    ['label' => 'Cấu hình'],
];
require_once __DIR__ . '/../layout/breadcrumb.php';

$botOk = !empty($botInfo['ok']);
$botName = $botOk ? ($botInfo['result']['first_name'] ?? '???') : '—';
$botUsername = $botOk ? ('@' . ($botInfo['result']['username'] ?? '')) : '—';
$webhookOk = !empty($webhookInfo['ok']) && !empty($webhookInfo['result']['url']);

$currentToken = $siteConfig['telegram_bot_token'] ?? '';
$currentChatId = $siteConfig['telegram_chat_id'] ?? '';
$currentSecret = $siteConfig['telegram_webhook_secret'] ?? '';
?>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.8) !important;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
    }

    .form-group label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 8px;
    }

    .form-control {
        height: 45px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        background: #ffffff;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(132, 90, 223, 0.1);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.75rem;
        gap: 6px;
    }

    .btn-action {
        height: 42px;
        padding: 0 20px;
        border-radius: 21px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
</style>

<section class="content pb-5 mt-1">
    <div class="container-fluid">
        <div class="row">
            <!-- Informational Sidebar / Top -->
            <div class="col-lg-4">
                <div class="card custom-card glass-card shadow-sm h-100">
                    <div class="card-header border-0 pb-0">
                        <h5 class="font-weight-bold mb-0 text-primary">TRẠNG THÁI HIỆN TẠI</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="d-inline-block mb-3 p-1 rounded-circle"
                                style="background: var(--primary-light); border: 2px solid var(--primary);">
                                <?php if (!empty($chungapi['logo'])): ?>
                                    <img src="<?= asset($chungapi['logo']) ?>" class="rounded-circle shadow-sm"
                                        style="width: 80px; height: 80px; object-fit: contain; background: #fff;">
                                <?php else: ?>
                                    <div class="bg-primary-light d-inline-block p-4 rounded-circle">
                                        <i class="fab fa-telegram fa-3x text-primary"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h5 class="font-weight-bold mb-1"><?= htmlspecialchars($botName) ?></h5>
                            <p class="text-primary font-weight-bold mb-3"><?= htmlspecialchars($botUsername) ?></p>

                            <?php if ($botOk): ?>
                                <span class="status-badge bg-soft-success text-success">
                                    <i class="fas fa-check-circle"></i> ĐANG ONLINE
                                </span>
                            <?php else: ?>
                                <span class="status-badge bg-soft-danger text-danger">
                                    <i class="fas fa-times-circle"></i> MẤT KẾT NỐI
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="p-3 bg-light rounded-lg border">
                            <h6 class="font-weight-bold small text-muted text-uppercase mb-3">Webhook Status</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small font-weight-bold">Trạng thái:</span>
                                <?php if ($webhookOk): ?>
                                    <span class="badge badge-success px-3">ACTIVE</span>
                                <?php else: ?>
                                    <span class="badge badge-warning px-3">INACTIVE</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($webhookInfo['result']['url'])): ?>
                                <div class="mt-2 pt-2 border-top">
                                    <p class="mb-0 small text-muted text-break">
                                        URL: <code><?= htmlspecialchars($webhookInfo['result']['url']) ?></code>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuration Form -->
            <div class="col-lg-8">
                <div class="card custom-card shadow-sm h-100">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title font-weight-bold mb-0">CẤU HÌNH HỆ THỐNG BOT</h5>
                        <i class="fas fa-cog text-muted"></i>
                    </div>
                    <div class="card-body pt-4">
                        <form id="formTgSettings">
                            <div class="form-group mb-4">
                                <label><i class="fas fa-robot mr-2"></i>Bot Token (API Token)</label>
                                <div class="input-group">
                                    <input type="password" name="telegram_bot_token" id="botTokenInput"
                                        class="form-control" value="<?= htmlspecialchars($currentToken) ?>"
                                        placeholder="Nhập Token lấy từ @BotFather">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button"
                                            onclick="togglePassword('botTokenInput')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">Mã bảo mật để kết nối server KaiShop với Telegram
                                    Bot API.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label><i class="fas fa-id-badge mr-2"></i>Admin Chat ID</label>
                                        <input type="text" name="telegram_chat_id" class="form-control"
                                            value="<?= htmlspecialchars($currentChatId) ?>"
                                            placeholder="Chat ID của admin">
                                        <small class="text-muted mt-2 d-block">Dùng để nhận thông báo nạp tiền, đơn hàng
                                            mới.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label><i class="fas fa-user-secret mr-2"></i>Webhook Secret</label>
                                        <input type="text" name="telegram_webhook_secret" class="form-control"
                                            value="<?= htmlspecialchars($currentSecret) ?>"
                                            placeholder="Mã bảo mật webhook">
                                        <small class="text-muted mt-2 d-block">Tùy chọn. Bảo vệ webhook khỏi các request
                                            lạ.</small>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4 op-1">

                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group-webhook">
                                    <button type="button" class="btn btn-action btn-primary" id="btnSetWebhook"
                                        title="Cập nhật/Đăng ký URL Webhook với Telegram">
                                        <i class="fas fa-plug"></i> Update Webhook
                                    </button>
                                    <button type="button" class="btn btn-action btn-outline-danger ml-2"
                                        id="btnDeleteWebhook">
                                        <i class="fas fa-power-off"></i> Gỡ Webhook
                                    </button>
                                </div>

                                <button type="submit" class="btn btn-action btn-success px-5 shadow">
                                    <i class="fas fa-save"></i> LƯU CẤU HÌNH
                                </button>
                            </div>
                        </form>

                        <div
                            class="mt-5 p-3 rounded-lg bg-soft-info border-info-light border d-flex align-items-center">
                            <div class="mr-3">
                                <i class="fas fa-vial fa-2x text-info"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="font-weight-bold mb-1">Kiểm tra kết nối?</h6>
                                <p class="mb-0 small text-muted">Gửi một tin nhắn test đến Admin Chat ID đã cấu hình ở
                                    trên.</p>
                            </div>
                            <button class="btn btn-info btn-sm px-4 rounded-pill font-weight-bold" id="btnTestBot">
                                <i class="fas fa-paper-plane mr-1"></i> SEND TEST
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<script>
    function togglePassword(id) {
        const input = document.getElementById(id);
        const icon = $(input).parent().find('i');
        if (input.type === "password") {
            input.type = "text";
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.type = "password";
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    }

    $(function () {
        $('#formTgSettings').on('submit', function (e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ĐANG LƯU...');

            $.post('<?= url('admin/telegram/settings/update') ?>', $(this).serialize(), function (res) {
                SwalHelper.toast(res.message, res.success ? 'success' : 'error');
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> LƯU CẤU HÌNH');
            }, 'json').fail(() => {
                SwalHelper.toast('Lỗi kết nối server', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> LƯU CẤU HÌNH');
            });
        });

        function handleAction(url, btn, iconClass) {
            const originalHtml = $(btn).html();
            $(btn).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.post(url, {}, function (res) {
                SwalHelper.toast(res.message, res.success ? 'success' : 'error');
                $(btn).prop('disabled', false).html(originalHtml);
                if (res.success) setTimeout(() => location.reload(), 1500);
            }, 'json').fail(() => {
                SwalHelper.toast('Lỗi thao tác', 'error');
                $(btn).prop('disabled', false).html(originalHtml);
            });
        }

        $('#btnSetWebhook').click(function () { handleAction('<?= url('admin/telegram/webhook/set') ?>', this); });
        $('#btnDeleteWebhook').click(function () { handleAction('<?= url('admin/telegram/webhook/delete') ?>', this); });
        $('#btnTestBot').click(function () { handleAction('<?= url('admin/telegram/test') ?>', this); });
    });
</script>
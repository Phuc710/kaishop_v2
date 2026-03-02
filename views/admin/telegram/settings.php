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
$sentCount = (int) ($outboxStats['sent'] ?? 0);
$pendingCount = (int) ($outboxStats['pending'] ?? 0);
$failedCount = (int) ($outboxStats['failed'] ?? 0);
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

    .stat-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        border-radius: 16px;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }

    .icon-box {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
</style>

<section class="content mt-3">
    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-soft-primary text-primary mr-3">
                                <i class="fab fa-telegram-plane"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-0 font-weight-bold small uppercase">Bot Status</p>
                                <h5 class="mb-0 font-weight-bolder">
                                    <?= $botOk ? '<span class="text-success">Hoạt động</span>' : '<span class="text-danger">Ngắt kết nối</span>' ?>
                                </h5>
                                <small class="text-muted"><?= htmlspecialchars($botUsername) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-soft-info text-info mr-3">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-0 font-weight-bold small uppercase">Đã gửi (HT)</p>
                                <h4 class="mb-0 font-weight-bolder"><?= number_format($sentCount) ?></h4>
                                <small class="text-muted">Tổng số tin nhắn</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-soft-warning text-warning mr-3">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-0 font-weight-bold small uppercase">Hàng đợi</p>
                                <h4 class="mb-0 font-weight-bolder text-warning"><?= number_format($pendingCount) ?></h4>
                                <small class="text-muted">Tin nhắn đang chờ</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-soft-danger text-danger mr-3">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-0 font-weight-bold small uppercase">Lỗi gửi</p>
                                <h4 class="mb-0 font-weight-bolder text-danger"><?= number_format($failedCount) ?></h4>
                                <small class="text-muted">Tin nhắn thất bại</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

            <!-- Configuration Form & Hub -->
            <div class="col-lg-8">
                <div class="card custom-card shadow-sm mb-4">
                    <div class="card-header border-0 pb-0">
                        <ul class="nav nav-tabs card-header-tabs" id="tgSettingsTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active font-weight-bold" id="config-tab" data-toggle="tab"
                                    href="#config" role="tab">CẤU HÌNH HỆ THỐNG</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold" id="channels-tab" data-toggle="tab"
                                    href="#channels" role="tab">KÊNH NHẬN THÔNG BÁO</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold text-danger" id="broadcast-tab" data-toggle="tab"
                                    href="#broadcast" role="tab">GỬI THÔNG BÁO (ALL)</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body pt-4">
                        <div class="tab-content" id="tgSettingsTabsContent">
                            <!-- Tab 1: System Config -->
                            <div class="tab-pane fade show active" id="config" role="tabpanel">
                                <form id="formTgSettings">
                                    <div class="form-group mb-4">
                                        <label><i class="fas fa-robot mr-2"></i>Bot Token (API Token)</label>
                                        <div class="input-group">
                                            <input type="password" name="telegram_bot_token" id="botTokenInput"
                                                class="form-control" value="<?= htmlspecialchars($currentToken) ?>"
                                                placeholder="8547601180:AAGx6y...">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassword('botTokenInput')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-4">
                                                <label><i class="fas fa-id-badge mr-2"></i>Admin ID chính</label>
                                                <input type="text" name="telegram_chat_id" class="form-control"
                                                    value="<?= htmlspecialchars($currentChatId) ?>" placeholder="6560022754">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-4">
                                                <label><i class="fas fa-users-cog mr-2"></i>Admin IDs Phụ (Phẩy)</label>
                                                <input type="text" name="telegram_admin_ids" class="form-control"
                                                    value="<?= htmlspecialchars($siteConfig['telegram_admin_ids'] ?? '') ?>"
                                                    placeholder="123456, 789012...">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-4">
                                                <label><i class="fas fa-clock mr-2"></i>Cảnh báo nạp (Giây)</label>
                                                <input type="number" name="telegram_order_cooldown" class="form-control"
                                                    value="<?= htmlspecialchars($siteConfig['telegram_order_cooldown'] ?? '300') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-4">
                                                <label><i class="fas fa-user-secret mr-2"></i>Webhook Secret</label>
                                                <input type="text" name="telegram_webhook_secret" class="form-control"
                                                    value="<?= htmlspecialchars($currentSecret) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-4">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" id="btnSyncBot">
                                                <i class="fas fa-sync-alt"></i> Sync Menu
                                            </button>
                                            <button type="button" class="btn btn-info btn-sm rounded-pill px-3 ml-2" id="btnTestBot">
                                                <i class="fas fa-paper-plane"></i> Test Bot
                                            </button>
                                        </div>
                                        <button type="submit" class="btn btn-success px-5 shadow rounded-pill font-weight-bold">
                                            <i class="fas fa-save"></i> LƯU THAY ĐỔI
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Tab 2: Channels -->
                            <div class="tab-pane fade" id="channels" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h6 class="font-weight-bold text-muted mb-0">DANH SÁCH KÊNH/NHÓM NHẬN ĐƠN HÀNG</h6>
                                    <button class="btn btn-primary btn-sm rounded-pill" data-toggle="modal" data-target="#modalAddChannel">
                                        <i class="fas fa-plus"></i> THÊM KÊNH
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="bg-light small">
                                            <tr>
                                                <th>TÊN GỢI NHỚ</th>
                                                <th>CHAT ID / CHANNEL</th>
                                                <th class="text-center">TRẠNG THÁI</th>
                                                <th class="text-right">HÀNH ĐỘNG</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($channels)): foreach ($channels as $c): ?>
                                                <tr>
                                                    <td class="font-weight-bold"><?= htmlspecialchars($c['label'] ?: 'Không tên') ?></td>
                                                    <td><code><?= htmlspecialchars($c['chat_id']) ?></code></td>
                                                    <td class="text-center">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input ch-toggle" 
                                                                   id="ch-<?= $c['id'] ?>" data-id="<?= $c['id'] ?>"
                                                                   <?= $c['is_active'] ? 'checked' : '' ?>>
                                                            <label class="custom-control-label" for="ch-<?= $c['id'] ?>"></label>
                                                        </div>
                                                    </td>
                                                    <td class="text-right">
                                                        <button class="btn btn-sm btn-outline-danger border-0 ch-delete" data-id="<?= $c['id'] ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; else: ?>
                                                <tr><td colspan="4" class="text-center py-4 text-muted small">Chưa có kênh phụ nào</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Tab 3: Broadcast -->
                            <div class="tab-pane fade" id="broadcast" role="tabpanel">
                                <div class="alert alert-danger bg-soft-danger border-0 mb-4">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <b>CHÚ Ý:</b> Tin nhắn sẽ được gửi đến <b>TẤT CẢ</b> người dùng đã từng nhắn tin/nhấn <code>/start</code> với Bot. Hãy cân nhắc kỹ trước khi gửi.
                                </div>

                                <form id="formBroadcast">
                                    <div class="form-group mb-4">
                                        <label>Nội dung thông báo (Hỗ trợ HTML)</label>
                                        <textarea name="message" class="form-control" style="height: 150px;" 
                                                  placeholder="Chào anh em, KaiShop vừa cập nhật sản phẩm mới..."></textarea>
                                        <small class="text-muted mt-2 d-block">Sử dụng HTML: <code>&lt;b&gt;đậm&lt;/i&gt;</code>, <code>&lt;i&gt;nghiêng&lt;/i&gt;</code>, <code>&lt;code&gt;mã&lt;/code&gt;</code>, <code>&lt;a href="..."&gt;link&lt;/a&gt;</code>.</small>
                                    </div>
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-danger px-5 shadow rounded-pill font-weight-bold">
                                            <i class="fas fa-broadcast-tower"></i> BẮT ĐẦU GỬI (BROADCAST)
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Webhook Management -->
                <div class="card custom-card glass-card shadow-sm">
                    <div class="card-body py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="small font-weight-bold text-muted text-uppercase">Hành động Webhook:</span>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-outline-primary btn-sm px-3 rounded-pill" id="btnSetWebhook">
                                <i class="fas fa-plug"></i> Update Webhook
                            </button>
                            <button class="btn btn-outline-danger btn-sm px-3 rounded-pill ml-2" id="btnDeleteWebhook">
                                <i class="fas fa-power-off"></i> Gỡ Webhook
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Add Channel -->
            <div class="modal fade" id="modalAddChannel" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title font-weight-bold">THÊM KÊNH NHẬN THÔNG BÁO</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body pt-4">
                            <form id="formAddChannel">
                                <div class="form-group mb-3">
                                    <label>Tên gợi nhớ (Ví dụ: Nhóm nhân viên)</label>
                                    <input type="text" name="label" class="form-control" placeholder="Nhóm nhân viên">
                                </div>
                                <div class="form-group mb-4">
                                    <label>Chat ID hoặc Channel Username (@name)</label>
                                    <input type="text" name="chat_id" class="form-control" placeholder="-100123456789">
                                    <small class="text-muted">Nhập Chat ID của Nhóm/Kênh. Đảm bảo Bot là quản trị viên trong đó.</small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block py-3 rounded-lg font-weight-bold">
                                    <i class="fas fa-plus-circle mr-1"></i> XÁC NHẬN THÊM
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<script>
    $(function () {
        // --- System Settings ---
        $('#formTgSettings').on('submit', function (e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ĐANG LƯU...');

            $.post('<?= url('admin/telegram/settings/update') ?>', $(this).serialize(), function (res) {
                SwalHelper.toast(res.message, res.success ? 'success' : 'error');
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> LƯU THAY ĐỔI');
            }, 'json').fail(() => {
                SwalHelper.toast('Lỗi kết nối server', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> LƯU THAY ĐỔI');
            });
        });

        // --- Broadcast ---
        $('#formBroadcast').on('submit', function (e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            SwalHelper.confirm('Xác nhận Broadcast?', 'Tin nhắn sẽ được gửi đến TẤT CẢ người dùng.', () => {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ĐANG GỬI...');
                $.post('<?= url('admin/telegram/broadcast') ?>', $(this).serialize(), function (res) {
                    SwalHelper.toast(res.message, res.success ? 'success' : 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-broadcast-tower"></i> BẮT ĐẦU GỬI (BROADCAST)');
                    if (res.success) $('#formBroadcast')[0].reset();
                }, 'json').fail(() => {
                    SwalHelper.toast('Lỗi kết nối', 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-broadcast-tower"></i> BẮT ĐẦU GỬI (BROADCAST)');
                });
            });
        });

        // --- Channels Management ---
        $('#formAddChannel').on('submit', function (e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ĐANG THÊM...');

            $.post('<?= url('admin/telegram/notification-channels/add') ?>', $(this).serialize(), function (res) {
                SwalHelper.toast(res.message, res.success ? 'success' : 'error');
                btn.prop('disabled', false).html('<i class="fas fa-plus-circle mr-1"></i> XÁC NHẬN THÊM');
                if (res.success) setTimeout(() => location.reload(), 1000);
            }, 'json');
        });

        $('.ch-toggle').change(function () {
            const id = $(this).data('id');
            $.post('<?= url('admin/telegram/notification-channels/toggle') ?>', { id: id });
        });

        $('.ch-delete').click(function () {
            const id = $(this).data('id');
            SwalHelper.confirm('Xóa kênh này?', 'Kênh này sẽ không nhận thông báo nữa.', () => {
                $.post('<?= url('admin/telegram/notification-channels/delete') ?>', { id: id }, function (res) {
                    if (res.success) location.reload();
                }, 'json');
            });
        });

        // --- Quick Actions ---
        function handleAction(url, btn, reload = false) {
            const originalHtml = $(btn).html();
            $(btn).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.post(url, {}, function (res) {
                SwalHelper.toast(res.message, res.success ? 'success' : 'error');
                $(btn).prop('disabled', false).html(originalHtml);
                if (reload && res.success) setTimeout(() => location.reload(), 1500);
            }, 'json').fail(() => {
                SwalHelper.toast('Lỗi thao tác', 'error');
                $(btn).prop('disabled', false).html(originalHtml);
            });
        }

        $('#btnSetWebhook').click(function () { handleAction('<?= url('admin/telegram/webhook/set') ?>', this, true); });
        $('#btnSyncBot').click(function () { handleAction('<?= url('admin/telegram/sync') ?>', this); });
        $('#btnDeleteWebhook').click(function () { handleAction('<?= url('admin/telegram/webhook/delete') ?>', this, true); });
        $('#btnTestBot').click(function () { handleAction('<?= url('admin/telegram/test') ?>', this); });
    });
</script>

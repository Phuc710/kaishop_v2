<?php
/**
 * View: Telegram Bot Settings Hub (Standard Design)
 * Root: admin/telegram/settings
 */
$pageTitle = 'Cấu hình Telegram Bot';
require_once __DIR__ . '/../layout/head.php';

$breadcrumbs = [
    ['label' => 'Telegram Bot', 'url' => url('admin/telegram/settings')],
    ['label' => 'Cài đặt tổng thể'],
];
require_once __DIR__ . '/../layout/breadcrumb.php';

$botOk = !empty($botInfo['ok']);
$botName = $botOk ? ($botInfo['result']['first_name'] ?? 'Bot') : 'Mất kết nối';
$botUsername = $botOk ? ('@' . ($botInfo['result']['username'] ?? '')) : '—';
$webhookApiOk = !empty($webhookInfo['ok']);

$currentToken = $siteConfig['telegram_bot_token'] ?? '';
$currentChatId = $siteConfig['telegram_chat_id'] ?? '';
$currentMainChannelId = $siteConfig['telegram_main_channel_id'] ?? '';
$currentSecret = $siteConfig['telegram_webhook_secret'] ?? '';
$currentPath = $siteConfig['telegram_webhook_path'] ?? 'bottelekaishop_default';
$botMaintenanceOn = ((int) ($siteConfig['telegram_maintenance_enabled'] ?? 0) === 1);

$baseUrl = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');
$webhookLink = $baseUrl . '/api/' . ltrim($currentPath, '/');
$registeredWebhookUrl = trim((string) ($webhookInfo['result']['url'] ?? ''));
$webhookRegistered = $registeredWebhookUrl !== '';
$webhookMatch = $webhookRegistered && rtrim($registeredWebhookUrl, '/') === rtrim($webhookLink, '/');
$webhookStatusClass = !$webhookApiOk
    ? 'tg-endpoint-status--error'
    : ($webhookMatch ? 'tg-endpoint-status--ok' : ($webhookRegistered ? 'tg-endpoint-status--warn' : 'tg-endpoint-status--warn'));
$webhookStatusText = !$webhookApiOk
    ? 'KHÔNG LẤY ĐƯỢC WEBHOOK INFO'
    : ($webhookMatch ? 'WEBHOOK ĐANG HOẠT ĐỘNG' : ($webhookRegistered ? 'WEBHOOK KHÁC ENDPOINT' : 'CHƯA ĐĂNG KÝ WEBHOOK'));
$tgCssVersion = (string) @filemtime(dirname(__DIR__, 3) . '/assets/css/telegram_admin.css');
?>
<link rel="stylesheet" href="<?= asset('assets/css/telegram_admin.css') ?>?v=<?= urlencode($tgCssVersion) ?>">

<section class="content telegram-settings-page">
    <div class="container-fluid">
        <!-- 1. Top Stats & Status Grid -->
        <div class="tg-stats-grid">
            <!-- Card 1: Bot Info -->
            <div class="tg-stat-card border-left-primary">
                <div class="d-flex align-items-center mb-2">
                    <div class="tg-stat-icon bg-primary-soft text-primary mb-0 mr-3">
                        <i class="fab fa-telegram-plane"></i>
                    </div>
                    <div class="tg-stat-label">Thông tin Bot</div>
                </div>
                <div class="font-weight-bold truncate mb-1" style="font-size: 1.1rem;"><?= htmlspecialchars($botName) ?>
                </div>
                <div class="small text-muted mb-2"><?= htmlspecialchars($botUsername) ?></div>
                <div class="d-flex flex-wrap gap-1 mt-auto">
                    <span
                        class="tg-status-pill <?= $botOk ? 'tg-status-pill--bot-ok' : 'tg-status-pill--bot-off' ?> small py-1">
                        <?= $botOk ? 'BOT: HOẠT ĐỘNG' : 'BOT: MẤT KẾT NỐI' ?>
                    </span>
                    <span
                        class="tg-status-pill <?= $botMaintenanceOn ? 'tg-status-pill--mtn-on' : 'tg-status-pill--mtn-off' ?> small py-1">
                        <?= $botMaintenanceOn ? 'BẢO TRÌ: ON' : 'BẢO TRÌ: OFF' ?>
                    </span>
                </div>
            </div>

            <!-- Card 2: Webhook Sync -->
            <div class="tg-stat-card border-left-warning">
                <div class="d-flex align-items-center mb-2">
                    <div class="tg-stat-icon bg-warning-soft text-warning mb-0 mr-3">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="tg-stat-label">Hệ thống Webhook</div>
                </div>
                <div class="mb-2">
                    <code class="small d-block text-truncate bg-light p-1 rounded border"
                        style="max-width: 100%;"><?= htmlspecialchars($webhookLink) ?></code>
                </div>
                <?php
                $webhookPulse = 'status-offline';
                if ($webhookMatch) {
                    $webhookPulse = 'status-active';
                } elseif ($webhookRegistered) {
                    $webhookPulse = 'status-stalled';
                }
                ?>
                <div class="d-flex align-items-center mt-auto">
                    <div class="status-circle <?= $webhookPulse ?> small mr-3"
                        style="width: 35px; height: 35px; font-size: 0.9rem;">
                        <i class="fas fa-link"></i>
                    </div>
                    <div>
                        <span class="tg-endpoint-status <?= $webhookStatusClass ?> small py-1 px-2 mb-1 d-inline-block"
                            style="border-radius: 4px; font-size: 0.65rem;">
                            <?= $webhookStatusText ?>
                        </span>
                        <div class="small text-muted">
                            Endpoint: <span
                                class="text-dark font-weight-bold"><?= $webhookMatch ? 'KẾT NỐI OK' : ($webhookRegistered ? 'SAI PATH' : 'CHƯA CÓ') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: User Stats -->
            <div class="tg-stat-card border-left-success">
                <div class="d-flex align-items-center mb-2">
                    <div class="tg-stat-icon bg-success-soft text-success mb-0 mr-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="tg-stat-label">Người dùng</div>
                </div>
                <div class="tg-stat-value"><?= number_format($totalUsers) ?></div>
                <div class="small text-muted mt-auto"><?= number_format($totalLinks) ?> liên kết Bot</div>
            </div>

            <!-- Card 4: Order Stats (KPI Details) -->
            <div class="tg-stat-card tg-stat-card--order border-left-info">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <div class="tg-stat-label">Tổng đơn Bot</div>
                        <div class="tg-stat-value"><?= number_format($orderStats['total'] ?? 0) ?></div>
                    </div>
                    <div class="tg-stat-icon bg-info-soft text-info mb-0"><i class="fas fa-shopping-cart"></i></div>
                </div>
                <div class="tg-order-kpis">
                    <div class="tg-order-kpi">
                        <div class="tg-order-kpi-label">Chờ</div>
                        <div class="tg-order-kpi-value tg-order-kpi-value--pending">
                            <?= number_format($orderStats['pending'] ?? 0) ?>
                        </div>
                    </div>
                    <div class="tg-order-kpi">
                        <div class="tg-order-kpi-label">Xong</div>
                        <div class="tg-order-kpi-value tg-order-kpi-value--success">
                            <?= number_format($orderStats['completed'] ?? 0) ?>
                        </div>
                    </div>
                    <div class="tg-order-kpi">
                        <div class="tg-order-kpi-label">Hủy</div>
                        <div class="tg-order-kpi-value tg-order-kpi-value--cancel">
                            <?= number_format($orderStats['cancelled'] ?? 0) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 5: Worker Status -->
            <div class="tg-stat-card border-left-danger overflow-hidden">
                <div class="d-flex align-items-center mb-2">
                    <div class="tg-stat-icon bg-danger-soft text-danger mb-0 mr-3">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="tg-stat-label">Trạng thái Worker</div>
                </div>
                <?php
                $lastCronRun = $lastCronRun ?? null;
                $workerStatus = 'OFF_LINE';
                $workerClass = 'tg-status-pill--bot-off';
                $pulseClass = 'status-offline';

                if ($lastCronRun) {
                    $diff = time() - strtotime($lastCronRun);
                    if ($diff < 120) {
                        $workerStatus = 'ACTIVE';
                        $workerClass = 'tg-status-pill--bot-ok';
                        $pulseClass = 'status-active';
                    } elseif ($diff < 600) {
                        $workerStatus = 'STALLED';
                        $workerClass = 'tg-status-pill--mtn-on';
                        $pulseClass = 'status-stalled';
                    }
                }
                ?>
                <div class="d-flex align-items-center mt-auto">
                    <div>
                        <span class="tg-status-pill <?= $workerClass ?> small py-1 px-2 mb-1 d-inline-block">
                            <?= $workerStatus ?>
                        </span>
                        <div class="small text-muted">
                            Lần cuối: <span
                                class="text-dark font-weight-bold"><?= $lastCronRun ? date('H:i:s d/m', strtotime($lastCronRun)) : 'N/A' ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Tab Navigation -->
        <div class="tg-tabs-nav">
            <button class="tg-tab-link active" data-target="tab-general"><i class="fas fa-cog"></i> Cấu hình
                chung</button>
            <button class="tg-tab-link" data-target="tab-webhook"><i class="fas fa-shield-alt"></i> Webhook & Bảo
                mật</button>
            <button class="tg-tab-link" data-target="tab-maintenance"><i class="fas fa-tools"></i> Bảo trì Bot</button>
            <button class="tg-tab-link" data-target="tab-channels"><i class="fas fa-satellite-dish"></i> Kênh thông
                báo</button>
            <button class="tg-tab-link" data-target="tab-main-channel"><i class="fas fa-bullhorn"></i> Main
                Channel</button>
            <button class="tg-tab-link" data-target="tab-broadcast"><i class="fas fa-paper-plane"></i> Gửi thông
                báo</button>
        </div>

        <!-- 3. Tab Content -->
        <div class="tg-tabs-content">
            <!-- TAB: GENERAL -->
            <div class="tg-tab-pane active" id="tab-general">
                <div class="card custom-card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap"
                        style="gap: 10px;">
                        <h3 class="card-title font-weight-bold mb-0">Cấu hình kết nối Bot</h3>
                        <div class="d-flex align-items-center justify-content-end flex-wrap ml-auto" style="gap: 8px;">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnSendTestMessage"><i
                                    class="fas fa-paper-plane mr-1"></i> Gửi Test</button>
                            <button type="button" class="btn btn-outline-info btn-sm" id="btnSyncBotMenu"><i
                                    class="fas fa-sync-alt mr-1"></i> Đồng bộ Menu</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form class="tg-ajax-form" method="post" action="<?= url('admin/telegram/settings/update') ?>">
                            <div class="form-group mb-4">
                                <label class="filter-label font-weight-bold">Bot Token (API Token)</label>
                                <div class="input-group">
                                    <input type="password" name="telegram_bot_token" id="botToken"
                                        class="form-control bg-light border-0"
                                        value="<?= htmlspecialchars($currentToken) ?>"
                                        placeholder="Token từ @BotFather">
                                    <div class="input-group-append">
                                        <button class="btn btn-light border" type="button"
                                            onclick="toggleView('botToken')"><i
                                                class="fas fa-eye text-muted"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-4">
                                        <label class="filter-label font-weight-bold">Admin Chat ID</label>
                                        <input type="text" name="telegram_chat_id"
                                            class="form-control bg-light border-0"
                                            value="<?= htmlspecialchars($currentChatId) ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-4">
                                        <label class="filter-label font-weight-bold">Secret Key (Xác thực
                                            Webhook)</label>
                                        <div class="input-group">
                                            <input type="password" name="telegram_webhook_secret" id="webhookSecret"
                                                class="form-control bg-light border-0"
                                                value="<?= htmlspecialchars($currentSecret) ?>"
                                                placeholder="Chuỗi bảo mật ngẫu nhiên">
                                            <div class="input-group-append">
                                                <button class="btn btn-light border" type="button"
                                                    onclick="toggleView('webhookSecret')"><i
                                                        class="fas fa-eye text-muted"></i></button>
                                                <button class="btn btn-outline-secondary border-0 bg-light"
                                                    type="button" onclick="randomHex('webhookSecret', 64)"
                                                    title="Tạo mã ngẫu nhiên">
                                                    <i class="fas fa-random text-primary"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-primary px-5 font-weight-bold shadow-sm">LƯU
                                    THAY ĐỔI</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TAB: WEBHOOK -->
            <div class="tg-tab-pane" id="tab-webhook">
                <div class="card custom-card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h3 class="card-title font-weight-bold">Bảo mật Webhook (Anti-Scan)</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info border-0 shadow-sm small mb-4">
                            <i class="fas fa-lightbulb mr-1"></i> Hãy thay đổi <b>đường dẫn</b> thường xuyên để tăng
                            tính bảo mật cho Webhook.
                        </div>
                        <div class="form-group mb-4">
                            <label class="small font-weight-bold text-muted text-uppercase">Endpoint Path Custom</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span
                                        class="input-group-text bg-light border-0">/api/</span></div>
                                <input type="text" id="webhookPathInput" class="form-control bg-light border-0"
                                    value="<?= htmlspecialchars($currentPath) ?>" placeholder="vd: bot_private_path">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary border-0 bg-light" type="button"
                                        onclick="randomHex('webhookPathInput', 32)" title="Tạo path ngẫu nhiên">
                                        <i class="fas fa-random text-primary"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="d-flex justify-content-end align-items-center" style="gap: 10px;">
                            <button class="btn btn-outline-danger px-4 font-weight-bold" id="btnDelWebhook">NGẮT KẾT
                                NỐI</button>
                            <button class="btn btn-outline-primary px-4 font-weight-bold" id="btnSetWebhook">
                                <i class="fas fa-save mr-1"></i> LƯU PATH
                            </button>
                            <button class="btn btn-success px-5 font-weight-bold py-2 shadow-sm"
                                id="btnActivateWebhook">
                                <i class="fas fa-bolt mr-2"></i> KÍCH HOẠT WEBHOOK
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: MAINTENANCE -->
            <div class="tg-tab-pane" id="tab-maintenance">
                <div class="card custom-card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h3 class="card-title font-weight-bold">Chế độ Bảo trì dành riêng cho Bot</h3>
                    </div>
                    <div class="card-body">
                        <form class="tg-ajax-form" method="post" action="<?= url('admin/telegram/settings/update') ?>">
                            <div class="bg-light p-4 rounded mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h5 class="mb-0 font-weight-bold">Trạng thái bảo trì</h5>
                                        <p class="text-muted small mb-0">Khi bật, Bot sẽ từ chối các lệnh và trả về tin
                                            nhắn bảo trì.</p>
                                    </div>
                                    <div class="maintenance-toggle-premium">
                                        <button type="button" id="btnMtnToggle"
                                            class="btn btn-sm px-4 font-weight-bold <?= $botMaintenanceOn ? 'btn-danger' : 'btn-success' ?>"
                                            style="min-width: 160px; border-radius: 50px; box-shadow: 0 4px 12px <?= $botMaintenanceOn ? 'rgba(220, 53, 69, 0.3)' : 'rgba(40, 167, 69, 0.3)' ?>;">
                                            <?= $botMaintenanceOn ? '<i class="fas fa-pause-circle mr-1"></i> BẢO TRÌ' : '<i class="fas fa-play-circle mr-1"></i> HOẠT ĐỘNG' ?>
                                        </button>
                                        <input type="hidden" name="telegram_maintenance_enabled" id="mtnStatusHidden"
                                            value="<?= $botMaintenanceOn ? 1 : 0 ?>">
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold text-muted text-uppercase">Nội dung tin nhắn
                                        bảo trì</label>
                                    <textarea name="telegram_maintenance_message" class="form-control border-0 bg-white"
                                        rows="4"><?= htmlspecialchars($siteConfig['telegram_maintenance_message'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="text-right">
                                <button type="submit" class="btn btn-primary px-5 font-weight-bold shadow-sm">LƯU CÀI
                                    ĐẶT BẢO TRÌ</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TAB: CHANNELS -->
            <div class="tg-tab-pane" id="tab-channels">
                <div class="card custom-card shadow-sm border-0">
                    <div
                        class="card-header bg-white d-flex justify-content-between align-items-center py-3 tg-channels-header">
                        <div>
                            <h3 class="card-title font-weight-bold mb-1">Kênh nhận đơn tự động</h3>
                            <div class="small text-muted">Nhận tất cả thông báo đơn hàng: tạo đơn, hoàn tất và hủy đơn.</div>
                        </div>
                        <button class="btn btn-sm btn-success px-4 tg-add-channel-btn" data-toggle="modal"
                            data-target="#modalAddChannel"><i class="fas fa-plus mr-1"></i> THÊM KÊNH</button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3">GỢI NHỚ</th>
                                    <th>CHAT ID / CHANNEL</th>
                                    <th class="text-center">TRẠNG THÁI</th>
                                    <th class="text-right px-4">THAO TÁC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($channels)):
                                    foreach ($channels as $c): ?>
                                        <tr>
                                            <td class="px-4 py-3 font-weight-bold"><?= htmlspecialchars($c['label'] ?: 'N/A') ?>
                                            </td>
                                            <td><code class="text-primary"><?= htmlspecialchars($c['chat_id']) ?></code></td>
                                            <td class="text-center">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input ch-toggle"
                                                        id="ch-<?= $c['id'] ?>" data-id="<?= $c['id'] ?>" <?= $c['is_active'] ? 'checked' : '' ?>>
                                                    <label class="custom-control-label" for="ch-<?= $c['id'] ?>"></label>
                                                </div>
                                            </td>
                                            <td class="text-right px-4">
                                                <button class="btn btn-sm btn-light text-primary ch-edit"
                                                    data-id="<?= (int) $c['id'] ?>"
                                                    data-chat-id="<?= htmlspecialchars((string) ($c['chat_id'] ?? ''), ENT_QUOTES) ?>"
                                                    data-label="<?= htmlspecialchars((string) ($c['label'] ?? ''), ENT_QUOTES) ?>"
                                                    data-toggle="modal" data-target="#modalEditChannel" title="Sửa kênh">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button class="btn btn-sm btn-light text-danger ch-delete"
                                                    data-id="<?= (int) $c['id'] ?>" title="Xóa kênh">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">Chưa có kênh phụ nào.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <!-- TAB: MAIN CHANNEL ALERT -->
            <div class="tg-tab-pane" id="tab-main-channel">
                <div class="card custom-card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h3 class="card-title font-weight-bold">Main Channel ALERT</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-4">
                            <label class="small font-weight-bold text-muted text-uppercase">Telegram Chat ID /
                                Channel</label>
                            <div class="d-flex align-items-center tg-main-channel-inline" style="gap:10px;">
                                <input type="text" id="mainChannelChatId" class="form-control bg-light border-0"
                                    value="<?= htmlspecialchars($currentMainChannelId) ?>"
                                    placeholder="-100123456789 hoặc @channel">
                                <button type="button" class="btn btn-primary px-4 font-weight-bold"
                                    id="btnSaveMainChannel">LƯU</button>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted text-uppercase">Nội dung ALERT</label>
                            <textarea id="mainAlertMessage" class="form-control bg-light border-0" rows="6"
                                placeholder="Nhập nội dung cảnh báo gửi vào Main Channel..."></textarea>
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-warning px-5 font-weight-bold shadow-sm"
                                id="btnSendMainAlert">
                                <i class="fas fa-paper-plane mr-1"></i> GỬI ALERT
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: BROADCAST -->
            <div class="tg-tab-pane" id="tab-broadcast">
                <div class="card custom-card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h3 class="card-title font-weight-bold">Broadcast Message System</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-4">
                            <label class="small font-weight-bold text-muted text-uppercase">Nội dung tin nhắn quảng
                                bá</label>
                            <textarea id="bcContent" class="form-control bg-light border-0" rows="8"
                                placeholder="Hỗ trợ HTML: <b>, <i>, <code>..."></textarea>
                        </div>
                        <div class="text-right">
                            <button class="btn btn-warning btn-block font-weight-bold py-3 shadow-sm" id="btnBroadcast">
                                <i class="fas fa-paper-plane mr-2"></i> GỬI CHO TẤT CẢ NGƯỜI DÙNG
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modals -->
<div class="modal fade" id="modalAddChannel" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold">Thêm kênh thông báo mới</h5><button type="button" class="close"
                    data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-4">
                <form id="formAddChannel">
                    <div class="form-group mb-3"><label class="small font-weight-bold text-muted text-uppercase">Tên gợi
                            nhớ</label><input type="text" name="label" class="form-control"
                            placeholder="vd: Team Technical"></div>
                    <div class="form-group mb-4"><label class="small font-weight-bold text-muted text-uppercase">Chat ID
                            / Channel Name</label><input type="text" name="chat_id" class="form-control"
                            placeholder="-100123456789"></div>
                    <div class="text-right"><button type="submit" class="btn btn-success px-5 font-weight-bold">XÁC NHẬN
                            THÊM</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditChannel" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold">Sửa kênh thông báo</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-4">
                <form id="formEditChannel">
                    <input type="hidden" name="id" id="editChannelId" value="">
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-muted text-uppercase">Tên gợi nhớ</label>
                        <input type="text" name="label" id="editChannelLabel" class="form-control"
                            placeholder="vd: Team Technical">
                    </div>
                    <div class="form-group mb-4">
                        <label class="small font-weight-bold text-muted text-uppercase">Chat ID / Channel Name</label>
                        <input type="text" name="chat_id" id="editChannelChatId" class="form-control"
                            placeholder="-100123456789">
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary px-5 font-weight-bold">LƯU CẬP NHẬT</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const TAB_KEY = 'telegram_settings_active_tab';
        const DEFAULT_TAB = 'tab-general';

        function activateTab(targetId, persist = true) {
            const targetPane = document.getElementById(targetId);
            const targetLink = document.querySelector(`.tg-tab-link[data-target="${targetId}"]`);
            if (!targetPane || !targetLink) return;

            document.querySelectorAll('.tg-tab-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.tg-tab-pane').forEach(p => p.classList.remove('active'));
            targetLink.classList.add('active');
            targetPane.classList.add('active');

            if (persist) {
                localStorage.setItem(TAB_KEY, targetId);
                if (history.replaceState) history.replaceState(null, '', `#${targetId}`);
                else window.location.hash = targetId;
            }
        }

        function getActiveTabId() {
            const activePane = document.querySelector('.tg-tab-pane.active');
            return activePane ? activePane.id : DEFAULT_TAB;
        }

        function reloadKeepingTab(delayMs = 0) {
            const tabId = getActiveTabId();
            localStorage.setItem(TAB_KEY, tabId);
            if (history.replaceState) history.replaceState(null, '', `#${tabId}`);
            else window.location.hash = tabId;

            if (delayMs > 0) {
                setTimeout(() => window.location.reload(), delayMs);
            } else {
                window.location.reload();
            }
        }

        async function postRequest(url, payload) {
            const body = (payload instanceof FormData) ? payload : new URLSearchParams(payload);
            const res = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body
            });
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Invalid JSON response');
            }
        }

        function setLoading(btn, loading, loadingHtml = '') {
            if (!btn) return;
            if (loading) {
                btn.dataset.originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = loadingHtml || '<i class="fas fa-circle-notch fa-spin"></i> Loading...';
            } else {
                btn.disabled = false;
                btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.tg-tab-link').forEach(link => {
                link.addEventListener('click', () => activateTab(link.dataset.target, true));
            });

            const hashTab = (window.location.hash || '').replace('#', '').trim();
            const savedTab = localStorage.getItem(TAB_KEY);
            activateTab(hashTab || savedTab || DEFAULT_TAB, false);

            document.querySelectorAll('.tg-ajax-form').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const btn = form.querySelector('button[type="submit"]');
                    setLoading(btn, true);
                    try {
                        const res = await postRequest(form.action, new FormData(form));
                        SwalHelper.toast(res.message, res.success ? 'success' : 'error');
                        if (res.success) reloadKeepingTab(700);
                    } catch (err) {
                        SwalHelper.toast('Lỗi hệ thống', 'error');
                    } finally {
                        setLoading(btn, false);
                    }
                });
            });

            const btnSendTestMessage = document.getElementById('btnSendTestMessage');
            const btnSyncBotMenu = document.getElementById('btnSyncBotMenu');
            const btnSetWebhook = document.getElementById('btnSetWebhook');
            const btnDelWebhook = document.getElementById('btnDelWebhook');
            const btnDisconnectBot = document.getElementById('btnDisconnectBot');
            async function runBotQuickAction(buttonEl, actionUrl, loadingHtml, errorMessage) {
                if (!buttonEl) return;
                setLoading(buttonEl, true, loadingHtml);
                try {
                    const res = await postRequest(actionUrl, {});
                    SwalHelper.toast(res.message, res.success ? 'success' : 'error');
                } catch (err) {
                    SwalHelper.toast(errorMessage, 'error');
                } finally {
                    setLoading(buttonEl, false);
                }
            }

            if (btnSendTestMessage) btnSendTestMessage.addEventListener('click', async () => {
                await runBotQuickAction(
                    btnSendTestMessage,
                    '<?= url('admin/telegram/test') ?>',
                    '<i class="fas fa-circle-notch fa-spin mr-1"></i> Đang gửi...',
                    'Lỗi test kết nối'
                );
            });

            if (btnSyncBotMenu) btnSyncBotMenu.addEventListener('click', async () => {
                await runBotQuickAction(
                    btnSyncBotMenu,
                    '<?= url('admin/telegram/sync') ?>',
                    '<i class="fas fa-circle-notch fa-spin mr-1"></i> Đang đồng bộ...',
                    'Lỗi đồng bộ bot'
                );
            });
            if (btnSetWebhook) btnSetWebhook.addEventListener('click', async () => {
                const path = (document.getElementById('webhookPathInput').value || '').trim();
                if (!path) return SwalHelper.toast('Vui lòng nhập đường dẫn webhook', 'error');
                setLoading(btnSetWebhook, true);
                try {
                    const res = await postRequest('<?= url('admin/telegram/webhook/set') ?>', { path });
                    SwalHelper.toast(res.message, res.success ? 'success' : 'error');
                    if (res.success) reloadKeepingTab(800);
                } catch (err) {
                    SwalHelper.toast('Lỗi lưu path', 'error');
                } finally {
                    setLoading(btnSetWebhook, false);
                }
            });

            const btnActivateWebhook = document.getElementById('btnActivateWebhook');
            if (btnActivateWebhook) btnActivateWebhook.addEventListener('click', async () => {
                setLoading(btnActivateWebhook, true, '<i class="fas fa-circle-notch fa-spin mr-1"></i> Đang kích hoạt...');
                try {
                    const res = await postRequest('<?= url('admin/telegram/webhook/activate') ?>', {});
                    SwalHelper.toast(res.message, res.success ? 'success' : 'error');
                    if (res.success) reloadKeepingTab(800);
                } catch (err) {
                    SwalHelper.toast('Lỗi kích hoạt webhook', 'error');
                } finally {
                    setLoading(btnActivateWebhook, false);
                }
            });

            if (btnDelWebhook) btnDelWebhook.addEventListener('click', () => {
                SwalHelper.confirm('Tạm dừng Bot?', 'Ngắt kết nối Webhook hiện tại.', async () => {
                    try {
                        const res = await postRequest('<?= url('admin/telegram/webhook/delete') ?>', {});
                        if (res.success) reloadKeepingTab();
                        else SwalHelper.toast(res.message, 'error');
                    } catch (err) {
                        SwalHelper.toast('Lỗi ngắt webhook', 'error');
                    }
                });
            });

            if (btnDisconnectBot) btnDisconnectBot.addEventListener('click', () => {
                SwalHelper.confirm('Xác nhận ngắt kết nối?', 'Bot sẽ ngừng nhận tin nhắn ngay lập tức.', async () => {
                    try {
                        const res = await postRequest('<?= url('admin/telegram/webhook/delete') ?>', {});
                        if (res.success) reloadKeepingTab();
                        else SwalHelper.toast(res.message, 'error');
                    } catch (err) {
                        SwalHelper.toast('Lỗi ngắt kết nối bot', 'error');
                    }
                });
            });

            const formAddChannel = document.getElementById('formAddChannel');
            if (formAddChannel) {
                formAddChannel.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const btn = formAddChannel.querySelector('button[type="submit"]');
                    setLoading(btn, true);
                    try {
                        activateTab('tab-channels', true);
                        const res = await postRequest('<?= url('admin/telegram/notification-channels/add') ?>', new FormData(formAddChannel));
                        if (res.success) {
                            SwalHelper.toast(res.message || 'Thêm kênh thành công', 'success');
                            reloadKeepingTab(600);
                        } else {
                            SwalHelper.toast(res.message || 'Thêm kênh thất bại', 'error');
                        }
                    } catch (err) {
                        SwalHelper.toast('Không thể thêm kênh, vui lòng thử lại', 'error');
                    } finally {
                        setLoading(btn, false);
                    }
                });
            }

            const formEditChannel = document.getElementById('formEditChannel');
            const editChannelId = document.getElementById('editChannelId');
            const editChannelLabel = document.getElementById('editChannelLabel');
            const editChannelChatId = document.getElementById('editChannelChatId');

            document.querySelectorAll('.ch-edit').forEach(el => {
                el.addEventListener('click', () => {
                    activateTab('tab-channels', true);
                    if (editChannelId) editChannelId.value = el.dataset.id || '';
                    if (editChannelLabel) editChannelLabel.value = el.dataset.label || '';
                    if (editChannelChatId) editChannelChatId.value = el.dataset.chatId || '';
                });
            });

            if (formEditChannel) {
                formEditChannel.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const btn = formEditChannel.querySelector('button[type="submit"]');
                    setLoading(btn, true);
                    try {
                        activateTab('tab-channels', true);
                        const res = await postRequest('<?= url('admin/telegram/notification-channels/update') ?>', new FormData(formEditChannel));
                        if (res.success) {
                            SwalHelper.toast(res.message || 'Cập nhật kênh thành công', 'success');
                            reloadKeepingTab(600);
                        } else {
                            SwalHelper.toast(res.message || 'Cập nhật kênh thất bại', 'error');
                        }
                    } catch (err) {
                        SwalHelper.toast('Không thể cập nhật kênh, vui lòng thử lại', 'error');
                    } finally {
                        setLoading(btn, false);
                    }
                });
            }

            const btnSaveMainChannel = document.getElementById('btnSaveMainChannel');
            const btnSendMainAlert = document.getElementById('btnSendMainAlert');

            if (btnSaveMainChannel) {
                btnSaveMainChannel.addEventListener('click', async () => {
                    const mainChannelChatId = document.getElementById('mainChannelChatId');
                    const chatId = mainChannelChatId ? mainChannelChatId.value.trim() : '';
                    if (!chatId) return SwalHelper.toast('Nhập Telegram Chat ID / Channel', 'error');

                    setLoading(btnSaveMainChannel, true);
                    try {
                        activateTab('tab-main-channel', true);
                        const res = await postRequest('<?= url('admin/telegram/settings/update') ?>', { telegram_main_channel_id: chatId });
                        SwalHelper.toast(res.message || (res.success ? 'Đã lưu Main Channel' : 'Lưu Main Channel thất bại'), res.success ? 'success' : 'error');
                    } catch (err) {
                        SwalHelper.toast('Lỗi lưu Main Channel', 'error');
                    } finally {
                        setLoading(btnSaveMainChannel, false);
                    }
                });
            }

            if (btnSendMainAlert) {
                btnSendMainAlert.addEventListener('click', () => {
                    const mainChannelChatId = document.getElementById('mainChannelChatId');
                    const mainAlertMessage = document.getElementById('mainAlertMessage');
                    const chatId = mainChannelChatId ? mainChannelChatId.value.trim() : '';
                    const message = mainAlertMessage ? mainAlertMessage.value.trim() : '';

                    if (!chatId) return SwalHelper.toast('Nhập Telegram Chat ID / Channel', 'error');
                    if (!message) return SwalHelper.toast('Nhập nội dung ALERT', 'error');

                    SwalHelper.confirm('Gửi ALERT vào Main Channel?', '', async () => {
                        setLoading(btnSendMainAlert, true, 'ĐANG GỬI...');
                        try {
                            activateTab('tab-main-channel', true);
                            const res = await postRequest('<?= url('admin/telegram/main-channel/alert') ?>', { chat_id: chatId, message: message });
                            SwalHelper.toast(res.message || (res.success ? 'Đã gửi ALERT' : 'Gửi ALERT thất bại'), res.success ? 'success' : 'error');
                            if (res.success && mainAlertMessage) mainAlertMessage.value = '';
                        } catch (err) {
                            SwalHelper.toast('Lỗi gửi ALERT', 'error');
                        } finally {
                            setLoading(btnSendMainAlert, false);
                        }
                    });
                });
            }

            const btnBroadcast = document.getElementById('btnBroadcast');
            if (btnBroadcast) {
                btnBroadcast.addEventListener('click', () => {
                    const bc = document.getElementById('bcContent');
                    const message = bc ? bc.value.trim() : '';
                    if (!message) return SwalHelper.toast('Nhập nội dung tin nhắn', 'error');
                    SwalHelper.confirm('Gửi cho tất cả?', 'Hành động này sẽ thêm tin vào hàng đợi.', async () => {
                        setLoading(btnBroadcast, true, 'ĐANG GỬI...');
                        try {
                            const res = await postRequest('<?= url('admin/telegram/broadcast/send') ?>', { message });
                            Swal.fire(res.success ? 'Thành công' : 'Thất bại', res.message, res.success ? 'success' : 'error');
                        } catch (err) {
                            SwalHelper.toast('Lỗi gửi broadcast', 'error');
                        } finally {
                            setLoading(btnBroadcast, false);
                        }
                    });
                });
            }

            // Maintenance Toggle Logic (Single Button)
            const btnMtnToggle = document.getElementById('btnMtnToggle');
            const mtnStatusHidden = document.getElementById('mtnStatusHidden');

            async function toggleMaintenance() {
                if (!mtnStatusHidden || !btnMtnToggle) return;

                const currentValue = parseInt(mtnStatusHidden.value);
                const newValue = currentValue === 1 ? 0 : 1;
                const originalValue = mtnStatusHidden.value;

                mtnStatusHidden.value = newValue;

                // Visual feedback (Loading)
                const originalHtml = btnMtnToggle.innerHTML;
                btnMtnToggle.disabled = true;
                btnMtnToggle.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-1"></i> Đang xử lý...';

                const form = mtnStatusHidden.closest('form');
                try {
                    const res = await postRequest(form.action, new FormData(form));
                    if (res.success) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: newValue === 1 ? 'Bot đã chuyển sang BẢO TRÌ' : 'Bot đã HOẠT ĐỘNG trở lại',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });

                        // Update Button UI
                        btnMtnToggle.className = 'btn btn-sm px-4 font-weight-bold ' + (newValue === 1 ? 'btn-danger' : 'btn-success');
                        btnMtnToggle.style.boxShadow = '0 4px 12px ' + (newValue === 1 ? 'rgba(220, 53, 69, 0.3)' : 'rgba(40, 167, 69, 0.3)');
                        btnMtnToggle.innerHTML = newValue === 1
                            ? '<i class="fas fa-pause-circle mr-1"></i> BẢO TRÌ'
                            : '<i class="fas fa-play-circle mr-1"></i> HOẠT ĐỘNG';

                        // Update Top Stats Grid Pill
                        const mtnPills = document.querySelectorAll('.tg-status-pill--mtn-on, .tg-status-pill--mtn-off');
                        mtnPills.forEach(pill => {
                            pill.className = 'tg-status-pill small py-1 ' + (newValue === 1 ? 'tg-status-pill--mtn-on' : 'tg-status-pill--mtn-off');
                            pill.textContent = 'BẢO TRÌ: ' + (newValue === 1 ? 'ON' : 'OFF');
                        });
                    } else {
                        SwalHelper.toast(res.message, 'error');
                        mtnStatusHidden.value = originalValue;
                        btnMtnToggle.innerHTML = originalHtml;
                    }
                } catch (err) {
                    SwalHelper.toast('Lỗi hệ thống', 'error');
                    mtnStatusHidden.value = originalValue;
                    btnMtnToggle.innerHTML = originalHtml;
                } finally {
                    btnMtnToggle.disabled = false;
                }
            }

            if (btnMtnToggle) btnMtnToggle.addEventListener('click', toggleMaintenance);

            document.querySelectorAll('.ch-toggle').forEach(el => {
                el.addEventListener('change', async () => {
                    try {
                        activateTab('tab-channels', true);
                        const res = await postRequest('<?= url('admin/telegram/notification-channels/toggle') ?>', { id: el.dataset.id });
                        if (!res || res.success === false) {
                            SwalHelper.toast((res && res.message) || 'Cập nhật trạng thái thất bại', 'error');
                            el.checked = !el.checked;
                            return;
                        }
                        SwalHelper.toast('Đã cập nhật trạng thái kênh', 'success');
                    } catch (err) {
                        el.checked = !el.checked;
                        SwalHelper.toast('Lỗi cập nhật trạng thái kênh', 'error');
                    }
                });
            });

            document.querySelectorAll('.ch-delete').forEach(el => {
                el.addEventListener('click', () => {
                    SwalHelper.confirm('Xóa kênh?', 'Hành động này không thể hoàn tác.', async () => {
                        try {
                            activateTab('tab-channels', true);
                            const res = await postRequest('<?= url('admin/telegram/notification-channels/delete') ?>', { id: el.dataset.id });
                            if (res.success) {
                                SwalHelper.toast(res.message || 'Đã xóa kênh', 'success');
                                reloadKeepingTab(400);
                            } else {
                                SwalHelper.toast(res.message || 'Xóa kênh thất bại', 'error');
                            }
                        } catch (err) {
                            SwalHelper.toast('Lỗi xóa kênh', 'error');
                        }
                    });
                });
            });
        });

        window.toggleView = function (id) {
            const i = document.getElementById(id);
            if (i) i.type = (i.type === 'password' ? 'text' : 'password');
        };

        window.randomHex = function (targetId, length) {
            const chars = '0123456789abcdef';
            let result = '';
            for (let i = 0; i < length; i++) {
                result += chars[Math.floor(Math.random() * chars.length)];
            }
            const input = document.getElementById(targetId);
            if (input) {
                input.value = result;
                if (input.type === 'password') input.type = 'text';
                SwalHelper.toast('Đã tạo mã ngẫu nhiên', 'success');
            }
        };
    })();
</script>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<style>
    /* Worker Status Animations */
    .status-circle {
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        position: relative;
        z-index: 2;
    }

    .status-active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .status-stalled {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .status-offline {
        background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
    }

    .status-active::after,
    .status-stalled::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border-radius: 50%;
        z-index: 1;
        animation: pulse-ring 2s infinite;
    }

    .status-active::after {
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.4);
    }

    .status-stalled::after {
        box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.4);
    }

    @keyframes pulse-ring {
        0% {
            transform: scale(0.95);
            opacity: 1;
        }

        70% {
            transform: scale(1.1);
            opacity: 0;
        }

        100% {
            transform: scale(0.95);
            opacity: 0;
        }
    }

    /* Maintenance Toggle Premium */
    .btn-toggle-off.active {
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
    }

    .btn-toggle-on.active {
        box-shadow: 0 0 10px rgba(220, 53, 69, 0.3);
    }

    .maintenance-toggle-premium .btn {
        transition: all 0.3s ease;
        border-radius: 6px;
        letter-spacing: 0.5px;
    }
</style>

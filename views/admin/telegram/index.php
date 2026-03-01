<?php
/**
 * View: Telegram Bot — Dashboard
 * Route: GET /admin/telegram
 */
require_once __DIR__ . '/../layout/head.php';
$pageTitle = 'Telegram Bot — Dashboard';
$breadcrumbs = [
    ['label' => 'Telegram Bot', 'url' => url('admin/telegram')],
    ['label' => 'Dashboard'],
];
require_once __DIR__ . '/../layout/breadcrumb.php';

$botOk = !empty($botInfo['ok']);
$botName = $botOk ? ($botInfo['result']['first_name'] ?? '???') : '—';
$botUsername = $botOk ? ('@' . ($botInfo['result']['username'] ?? '')) : '—';

$webhookOk = !empty($webhookInfo['ok']) && !empty($webhookInfo['result']['url']);
$webhookUrl = $webhookOk ? $webhookInfo['result']['url'] : 'Chưa thiết lập';
$webhookPending = $webhookOk ? (int) ($webhookInfo['result']['pending_update_count'] ?? 0) : 0;
$webhookLastError = $webhookOk ? ($webhookInfo['result']['last_error_message'] ?? '') : '';

$pendingCount = (int) ($outboxStats['pending'] ?? 0);
$sentCount = (int) ($outboxStats['sent'] ?? 0);
$failedCount = (int) ($outboxStats['failed'] ?? 0);
?>

<style>
    .tg-stat-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none !important;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
    }

    .tg-stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1) !important;
    }

    .status-pulse {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 5px;
        position: relative;
    }

    .status-pulse.online {
        background-color: var(--success);
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
        animation: pulse-green 2s infinite;
    }

    .status-pulse.offline {
        background-color: var(--danger);
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        animation: pulse-red 2s infinite;
    }

    @keyframes pulse-green {
        0% {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(34, 197, 94, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
        }
    }

    @keyframes pulse-red {
        0% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }

    .gradient-info {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%) !important;
    }

    .gradient-success {
        background: linear-gradient(135deg, #22c55e 0%, #10b981 100%) !important;
    }

    .gradient-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%) !important;
    }

    .gradient-danger {
        background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%) !important;
    }
</style>

<section class="content pb-4 mt-1">
    <div class="container-fluid">
        <!-- Top row: Status Info -->
        <div class="row mb-4">
            <div class="col-lg-4">
                <div class="card tg-stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0 mr-3">
                                <?php if (!empty($chungapi['logo'])): ?>
                                    <div class="p-1 rounded-circle"
                                        style="background: var(--primary-light); border: 1px solid var(--primary);">
                                        <img src="<?= asset($chungapi['logo']) ?>" class="rounded-circle shadow-sm"
                                            style="width: 50px; height: 50px; object-fit: contain; background: #fff;">
                                    </div>
                                <?php else: ?>
                                    <div class="bg-primary-light p-3 rounded-circle">
                                        <i class="fab fa-telegram-plane fa-2x text-primary"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h6 class="text-muted font-weight-bold mb-1 text-uppercase small">Bot Info</h6>
                                <h4 class="mb-0 font-weight-bold">
                                    <?php if ($botOk): ?>
                                        <span class="status-pulse online"></span> Online
                                    <?php else: ?>
                                        <span class="status-pulse offline"></span> Offline
                                    <?php endif; ?>
                                </h4>
                            </div>
                        </div>
                        <div class="pt-3 border-top">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small font-weight-bold">Tên Bot:</span>
                                <span class="font-weight-bold"><?= htmlspecialchars($botName) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small font-weight-bold">Username:</span>
                                <span class="text-primary font-weight-bold"><?= htmlspecialchars($botUsername) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card tg-stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0 bg-info-light p-3 rounded-circle mr-3"
                                style="background: rgba(13, 202, 240, 0.1);">
                                <i class="fas fa-satellite-dish fa-2x text-info"></i>
                            </div>
                            <div>
                                <h6 class="text-muted font-weight-bold mb-1 text-uppercase small">Webhook Status</h6>
                                <h4 class="mb-0 font-weight-bold">
                                    <?php if ($webhookOk): ?>
                                        <span class="text-success">Active</span>
                                    <?php else: ?>
                                        <span class="text-warning">Inactive</span>
                                    <?php endif; ?>
                                </h4>
                            </div>
                        </div>
                        <div class="pt-3 border-top">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small font-weight-bold">Pending:</span>
                                <span class="badge badge-pill badge-warning"><?= $webhookPending ?> updates</span>
                            </div>
                            <div class="d-flex justify-content-between text-truncate">
                                <span class="text-muted small font-weight-bold">Server:</span>
                                <span class="text-muted small"
                                    title="<?= htmlspecialchars($webhookUrl) ?>"><?= mb_substr($webhookUrl, 0, 30) ?>...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card tg-stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0 bg-success-light p-3 rounded-circle mr-3"
                                style="background: rgba(34, 197, 94, 0.1);">
                                <i class="fas fa-users fa-2x text-success"></i>
                            </div>
                            <div>
                                <h6 class="text-muted font-weight-bold mb-1 text-uppercase small">Total User Links</h6>
                                <h4 class="mb-0 font-weight-bold"><?= number_format($totalLinks) ?></h4>
                            </div>
                        </div>
                        <div class="pt-3 border-top">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small font-weight-bold">Hôm nay:</span>
                                <span class="text-success font-weight-bold">+<?= $newLinksToday ?> links</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small font-weight-bold">Tổng user:</span>
                                <span class="font-weight-bold"><?= number_format($totalUsers) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row: Outbox Stats with Gradients -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card custom-card gradient-warning border-0 text-white shadow">
                    <div class="card-body py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 text-uppercase small font-weight-bold mb-2">Outbox Pending</h6>
                                <h2 class="mb-0 font-weight-bold"><?= number_format($pendingCount) ?></h2>
                            </div>
                            <i class="fas fa-clock fa-3x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card gradient-success border-0 text-white shadow">
                    <div class="card-body py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 text-uppercase small font-weight-bold mb-2">Outbox Sent</h6>
                                <h2 class="mb-0 font-weight-bold"><?= number_format($sentCount) ?></h2>
                            </div>
                            <i class="fas fa-check-double fa-3x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card gradient-danger border-0 text-white shadow">
                    <div class="card-body py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 text-uppercase small font-weight-bold mb-2">Outbox Failed</h6>
                                <h2 class="mb-0 font-weight-bold"><?= number_format($failedCount) ?></h2>
                            </div>
                            <i class="fas fa-exclamation-circle fa-3x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($webhookLastError): ?>
            <div class="alert bg-soft-danger border-danger-light mb-4 fade show">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Cảnh báo Webhook:</strong> <?= htmlspecialchars($webhookLastError) ?>
            </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <div class="card custom-card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center border-0 bg-transparent py-4">
                <h3 class="card-title text-uppercase font-weight-bold mb-0">Hàng đợi tin nhắn mới nhất</h3>
                <a href="<?= url('admin/telegram/outbox') ?>" class="btn btn-sm btn-primary-light">
                    <i class="fas fa-external-link-alt mr-1"></i> Quản lý Outbox
                </a>
            </div>
            <div class="card-body px-0 pt-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100">
                        <thead class="bg-light">
                            <tr>
                                <th class="pl-4">ID</th>
                                <th>Telegram ID</th>
                                <th>Nội dung rút gọn</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Số lượt thử</th>
                                <th class="text-right pr-4">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentOutbox)): ?>
                                <?php foreach ($recentOutbox as $msg): ?>
                                    <tr>
                                        <td class="pl-4 align-middle">#<?= $msg['id'] ?></td>
                                        <td class="align-middle"><code><?= $msg['telegram_id'] ?></code></td>
                                        <td class="align-middle">
                                            <div class="text-muted small" style="max-width:350px;">
                                                <?= htmlspecialchars(mb_substr(strip_tags($msg['message']), 0, 100)) ?>...
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php
                                            $st = $msg['status'];
                                            $badge = match ($st) {
                                                'sent' => 'badge-success',
                                                'pending' => 'badge-warning',
                                                'fail' => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $badge ?> px-3 py-1"><?= strtoupper($st) ?></span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-pill badge-light border"><?= $msg['try_count'] ?></span>
                                        </td>
                                        <td class="text-right pr-4 align-middle font-weight-bold small text-muted">
                                            <?= $msg['created_at'] ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                                        Chưa có tin nhắn nào trong hàng đợi
                                    </td>
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
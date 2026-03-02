<?php
/**
 * View: Telegram Bot — Outbox & Worker
 * Route: GET /admin/telegram/outbox
 */
require_once __DIR__ . '/../layout/head.php';
$pageTitle = 'Hàng đợi Outbox';
$breadcrumbs = [
    ['label' => 'Telegram Bot', 'url' => url('admin/telegram')],
    ['label' => 'Outbox & Worker'],
];
require_once __DIR__ . '/../layout/breadcrumb.php';

$pending = (int) ($stats['pending'] ?? 0);
$sent = (int) ($stats['sent'] ?? 0);
$failed = (int) ($stats['failed'] ?? 0);
?>

<section class="content mt-3">
    <div class="container-fluid">

        <!-- Floating Stats Panel -->
        <div class="card custom-card bg-primary text-white border-0 shadow mb-4"
            style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;">
            <div class="card-body py-4">
                <div class="row align-items-center">
                    <div class="col-md-3 border-right border-secondary">
                        <div class="text-center">
                            <h6 class="text-muted font-weight-bold text-uppercase small mb-2">Trạng thái gửi</h6>
                            <h4 class="mb-0 font-weight-bold"><i class="fas fa-microchip mr-2 text-info"></i>Worker
                                Active</h4>
                        </div>
                    </div>
                    <div class="col-md-3 border-right border-secondary">
                        <div class="text-center">
                            <h6 class="text-muted font-weight-bold text-uppercase small mb-2">Đang chờ</h6>
                            <h3 class="mb-0 font-weight-bold text-warning"><?= number_format($pending) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 border-right border-secondary">
                        <div class="text-center">
                            <h6 class="text-muted font-weight-bold text-uppercase small mb-2">Đã hoàn thành</h6>
                            <h3 class="mb-0 font-weight-bold text-success"><?= number_format($sent) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted font-weight-bold text-uppercase small mb-2">Số lượt lỗi</h6>
                            <h3 class="mb-0 font-weight-bold text-danger"><?= number_format($failed) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card shadow-sm border-0">
            <div class="card-header border-0 bg-transparent py-4 d-flex justify-content-between align-items-center">
                <h3 class="card-title text-uppercase font-weight-bold mb-0">Hàng đợi tin nhắn</h3>
                <div class="card-tools">
                    <div class="btn-group mr-2">
                        <a href="<?= url('admin/telegram/outbox') ?>"
                            class="btn btn-sm btn-action <?= $statusFilter === '' ? 'btn-primary shadow' : 'btn-outline-primary' ?>">Tất
                            cả</a>
                        <a href="<?= url('admin/telegram/outbox?status=pending') ?>"
                            class="btn btn-sm btn-action <?= $statusFilter === 'pending' ? 'btn-warning shadow' : 'btn-outline-warning' ?>">Pending</a>
                        <a href="<?= url('admin/telegram/outbox?status=sent') ?>"
                            class="btn btn-sm btn-action <?= $statusFilter === 'sent' ? 'btn-success shadow' : 'btn-outline-success' ?>">Sent</a>
                        <a href="<?= url('admin/telegram/outbox?status=fail') ?>"
                            class="btn btn-sm btn-action <?= $statusFilter === 'fail' ? 'btn-danger shadow' : 'btn-outline-danger' ?>">Failed</a>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger px-3 shadow-sm btn-action"
                        onclick="batchDeleteMessages()">
                        <i class="fas fa-trash-alt mr-1"></i> Xóa hàng đợi
                    </button>
                    <button type="button" class="btn btn-sm btn-warning px-3 shadow-sm btn-action ml-2"
                        onclick="retryFailedMessages()">
                        <i class="fas fa-sync-alt mr-1"></i> Gửi lại lỗi
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th class="pl-4">ID</th>
                                <th>Người nhận</th>
                                <th>Nội dung</th>
                                <th>Trạng thái</th>
                                <th>Thử lại</th>
                                <th>Lỗi cuối</th>
                                <th class="pr-4 text-right">Cập nhật</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($messages)):
                                foreach ($messages as $msg): ?>
                                    <tr>
                                        <td class="pl-4 font-weight-bold">#<?= $msg['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-light rounded text-primary mr-2 d-flex align-items-center justify-content-center"
                                                    style="width: 32px; height: 32px;">
                                                    <i class="fab fa-telegram-plane"></i>
                                                </div>
                                                <code><?= $msg['telegram_id'] ?></code>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-wrap" style="max-width: 300px; font-size: 0.9rem;">
                                                <?= htmlspecialchars(mb_strimwidth($msg['message'], 0, 100, "...")) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($msg['status'] === 'sent'): ?>
                                                <span class="badge badge-pill badge-outline-success">SENT</span>
                                            <?php elseif ($msg['status'] === 'fail'): ?>
                                                <span class="badge badge-pill badge-outline-danger">FAIL</span>
                                            <?php else: ?>
                                                <span class="badge badge-pill badge-outline-warning">PENDING</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-light"><?= $msg['retry_count'] ?> lần</span>
                                        </td>
                                        <td class="small text-danger">
                                            <?= htmlspecialchars($msg['last_error'] ?? '-') ?>
                                        </td>
                                        <td class="pr-4 text-right text-muted small">
                                            <?= $msg['updated_at'] ?>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">Không tìm thấy tin nhắn nào</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .badge-pill {
        font-weight: 600;
        padding: 0.4em 0.8em;
    }

    .badge-outline-success {
        color: #28a745;
        border: 1px solid #28a745;
        background: transparent;
    }

    .badge-outline-danger {
        color: #dc3545;
        border: 1px solid #dc3545;
        background: transparent;
    }

    .badge-outline-warning {
        color: #ffc107;
        border: 1px solid #ffc107;
        background: transparent;
    }

    .btn-action {
        border-radius: 8px;
        font-weight: 600;
    }

    .custom-card {
        border-radius: 12px;
    }
</style>

<script>
    function batchDeleteMessages() {
        SwalHelper.confirm('Xác nhận xóa?', 'Tất cả tin nhắn trong hàng đợi hiện tại sẽ bị xóa vĩnh viễn.', () => {
            fetch('<?= url('admin/telegram/outbox/delete') ?>', { method: 'POST' })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        SwalHelper.success(res.message).then(() => location.reload());
                    } else {
                        SwalHelper.error(res.message);
                    }
                });
        });
    }

    function retryFailedMessages() {
        SwalHelper.confirm('Thử lại tất cả lỗi?', 'Hệ thống sẽ đặt lại trạng thái PENDING cho các tin nhắn bị lỗi.', () => {
            fetch('<?= url('admin/telegram/outbox/retry') ?>', { method: 'POST' })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        SwalHelper.success(res.message).then(() => location.reload());
                    } else {
                        SwalHelper.error(res.message);
                    }
                });
        });
    }
</script>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
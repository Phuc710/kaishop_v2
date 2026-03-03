<?php
/**
 * View: Telegram Bot — Đơn hàng
 * Route: GET /admin/telegram/orders
 */
require_once __DIR__ . '/../layout/head.php';
$pageTitle = 'Đơn hàng từ Telegram';
$breadcrumbs = [
    ['label' => 'Telegram Bot', 'url' => url('admin/telegram')],
    ['label' => 'Đơn hàng Bot'],
];
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<div class="col-md-6 col-xl-6 mb-2">
    <div class="card custom-card shadow-sm border-0 h-100 overflow-hidden">
        <div class="card-body py-3 d-flex align-items-center">
            <?php
            $lastCronRun = $lastCronRun ?? null;
            $workerStatus = 'OFFLINE';
            $workerClass = 'badge-secondary';
            $pulseClass = 'status-offline';

            if ($lastCronRun) {
                $diff = time() - strtotime($lastCronRun);
                if ($diff < 120) {
                    $workerStatus = 'ACTIVE';
                    $workerClass = 'badge-success';
                    $pulseClass = 'status-active';
                } elseif ($diff < 600) {
                    $workerStatus = 'STALLED';
                    $workerClass = 'badge-warning';
                    $pulseClass = 'status-stalled';
                }
            }
            ?>
            <div class="worker-status-container mr-3">
                <div class="status-circle <?= $pulseClass ?> small">
                    <i class="fas fa-robot"></i>
                </div>
            </div>
            <div class="flex-grow-1">
                <h6 class="text-muted small text-uppercase font-weight-bold mb-1">Trạng thái Worker</h6>
                <div class="d-flex align-items-center">
                    <span class="badge badge-pill <?= $workerClass ?> px-2 mr-2">
                        <i class="fas fa-circle mr-1 small"></i><?= $workerStatus ?>
                    </span>
                    <span class="small text-muted">
                        Lần cuối: <span
                            class="text-dark font-weight-bold"><?= $lastCronRun ? date('H:i:s d/m', strtotime($lastCronRun)) : 'N/A' ?></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
</div>
</div>

<div class="col-12">
    <div class="card custom-card shadow-sm border-0">
        <div class="card-header border-0 bg-transparent py-4">
            <h3 class="card-title text-uppercase font-weight-bold mb-0">Lịch sử đơn hàng qua Telegram</h3>
        </div>
        <div class="card-body px-0 pt-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100">
                    <thead class="bg-light text-uppercase small">
                        <tr>
                            <th class="pl-4">Mã đơn hàng</th>
                            <th>Người mua</th>
                            <th>Sản phẩm</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-right">Giá trị</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-right pr-4">Ngày đặt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td class="pl-4 font-weight-bold text-primary">
                                        <code><?= htmlspecialchars($o['order_code']) ?></code>
                                    </td>
                                    <td class="font-weight-bold">
                                        <i class="fas fa-user-circle text-muted mr-1"></i>
                                        <?= htmlspecialchars($o['buyer_username'] ?? '—') ?>
                                    </td>
                                    <td>
                                        <span class="font-weight-bold"><?= htmlspecialchars($o['product_name']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge badge-pill badge-light border px-2"><?= (int) $o['quantity'] ?></span>
                                    </td>
                                    <td class="text-right font-weight-bold text-dark">
                                        <?= number_format($o['price']) ?>đ
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $st = strtolower($o['status']);
                                        $badge = match ($st) {
                                            'completed' => 'badge-success',
                                            'pending', 'processing' => 'badge-warning',
                                            'cancelled' => 'badge-danger',
                                            default => 'badge-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $badge ?> px-3 py-1"><?= strtoupper($st) ?></span>
                                    </td>
                                    <td class="text-right pr-4 text-muted small">
                                        <?= FormatHelper::eventTime($o['created_at'] ?? null, $o['created_at'] ?? null) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <img src="<?= url('assets/img/empty-orders.svg') ?>" style="width:100px; opacity:0.3;"
                                        class="mb-3 d-block mx-auto">
                                    <p class="text-muted">Chưa có đơn hàng nào phát sinh qua Telegram</p>
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

<style>
    /* Worker Status Animations */
    .worker-status-container {
        position: relative;
        display: inline-block;
    }

    .status-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        position: relative;
        z-index: 2;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .status-circle.small {
        width: 38px;
        height: 38px;
        font-size: 1rem;
    }

    .status-active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .status-stalled {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .status-offline {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
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
</style>
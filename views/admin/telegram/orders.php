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

$totalOrders = count($orders);
$totalRevenue = array_sum(array_column($orders, 'price'));
$filterMode = $filterMode ?? 'none';
?>

<section class="content pb-5 mt-1">
    <div class="container-fluid">
        <?php if ($filterMode === 'none'): ?>
            <div class="alert alert-warning mx-3 mx-md-0">
                Bảng <code>orders</code> hiện chưa có cột <code>source</code> hoặc <code>telegram_id</code>, nên hệ thống
                chưa thể tách riêng đơn hàng tạo từ Telegram trên schema hiện tại.
            </div>
        <?php endif; ?>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card custom-card bg-primary text-white shadow-sm"
                    style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-white-50 p-2 rounded">
                                <i class="fas fa-shopping-bag fa-lg"></i>
                            </div>
                            <div class="ml-3">
                                <h6 class="text-white-50 small font-weight-bold text-uppercase mb-1">Tổng đơn Bot</h6>
                                <h4 class="mb-0 font-weight-bold"><?= number_format($totalOrders) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card custom-card bg-success text-white shadow-sm"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-white-50 p-2 rounded">
                                <i class="fas fa-hand-holding-usd fa-lg"></i>
                            </div>
                            <div class="ml-3">
                                <h6 class="text-white-50 small font-weight-bold text-uppercase mb-1">Doanh thu Bot</h6>
                                <h4 class="mb-0 font-weight-bold"><?= number_format($totalRevenue) ?>đ</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                                            <?= date('H:i d/m/Y', strtotime($o['created_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <img src="<?= url('assets/img/empty-orders.svg') ?>"
                                            style="width:100px; opacity:0.3;" class="mb-3 d-block mx-auto">
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

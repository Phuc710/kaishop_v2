<?php
/**
 * View: Admin Dashboard - Revenue
 * Route: GET /admin
 * Controller: DashboardController@index
 */
$pageTitle = 'Dashboard Doanh Thu';
$breadcrumbs = [
    ['label' => 'Dashboard'],
];
require_once __DIR__ . '/layout/head.php';
require_once __DIR__ . '/layout/breadcrumb.php';

$activeRange = (string) ($activeRange ?? 'all');
$rangeMeta = is_array($rangeMeta ?? null) ? $rangeMeta : [];
$rangeLabel = (string) ($rangeMeta['label'] ?? 'Tất cả');
$rangeText = (string) ($rangeMeta['range_text'] ?? 'Toàn bộ dữ liệu');

$revenueStats = is_array($revenueStats ?? null) ? $revenueStats : [];
$channelBreakdown = is_array($channelBreakdown ?? null) ? $channelBreakdown : [];
$chartData = is_array($chartData ?? null) ? $chartData : [];
$topProducts = is_array($topProducts ?? null) ? $topProducts : [];
$recentOrders = is_array($recentOrders ?? null) ? $recentOrders : [];
$recentDeposits = is_array($recentDeposits ?? null) ? $recentDeposits : [];

$rangeOptions = [
    'all' => 'Tất cả',
    'today' => 'Today',
    'week' => 'Tuần',
    'month' => 'Tháng',
    'quarter' => 'Quý',
];

$fmtMoney = static function ($value): string {
    return number_format((int) $value) . 'đ';
};

$orderStatusBadgeClass = static function (string $status): string {
    return match (strtolower(trim($status))) {
        'completed' => 'badge badge-success',
        'pending' => 'badge badge-warning',
        'processing' => 'badge badge-info',
        'cancelled' => 'badge badge-danger',
        default => 'badge badge-secondary',
    };
};
?>

<section class="content mt-3 pb-4 admin-dashboard-page">
    <div class="container-fluid">
        <div class="card custom-card mb-3">
            <div class="card-header border-0 pb-2 d-flex flex-wrap align-items-center justify-content-between">
                <h3 class="card-title mb-2 mb-md-0">TỔNG QUAN DOANH THU</h3>
                <span class="badge badge-light border px-3 py-2">
                    <?= htmlspecialchars($rangeLabel) ?> | <?= htmlspecialchars($rangeText) ?>
                </span>
            </div>
            <div class="card-body pt-2">
                <div class="dashboard-range-tabs btn-group btn-group-sm flex-wrap" role="group" aria-label="Revenue ranges">
                    <?php foreach ($rangeOptions as $rangeKey => $rangeName): ?>
                        <?php
                        $isActive = $activeRange === $rangeKey;
                        $btnClass = $isActive ? 'btn btn-primary' : 'btn btn-outline-secondary';
                        ?>
                        <a href="<?= url('admin') . '?range=' . urlencode($rangeKey) ?>" class="<?= $btnClass ?>">
                            <?= htmlspecialchars($rangeName) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="dashboard-range-hint text-muted mt-2">
                    Bộ lọc đang áp dụng cho toàn bộ chỉ số doanh thu, đơn hàng và nạp tiền phía dưới.
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 col-md-6 col-12">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $fmtMoney($revenueStats['revenue_total'] ?? 0) ?></h3>
                        <p>Tổng Doanh Thu Bán Hàng</p>
                    </div>
                    <div class="icon"><i class="ion ion-cash"></i></div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-12">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= $fmtMoney($revenueStats['revenue_web'] ?? 0) ?></h3>
                        <p>Doanh Thu Từ Web</p>
                    </div>
                    <div class="icon"><i class="ion ion-monitor"></i></div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-12">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $fmtMoney($revenueStats['revenue_telegram'] ?? 0) ?></h3>
                        <p>Doanh Thu Từ Bot Telegram</p>
                    </div>
                    <div class="icon"><i class="fab fa-telegram-plane"></i></div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-12">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= number_format((int) ($revenueStats['orders_sold'] ?? 0)) ?></h3>
                        <p>Tổng Đơn Đã Bán</p>
                    </div>
                    <div class="icon"><i class="ion ion-bag"></i></div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-12">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= $fmtMoney($revenueStats['deposit_total'] ?? 0) ?></h3>
                        <p>Tổng Tiền Nạp Vào Hệ Thống</p>
                    </div>
                    <div class="icon"><i class="ion ion-card"></i></div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-12">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3><?= number_format((int) ($revenueStats['deposit_count'] ?? 0)) ?></h3>
                        <p>Số Giao Dịch Nạp Thành Công</p>
                    </div>
                    <div class="icon"><i class="ion ion-android-list"></i></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white border dashboard-mini-box">
                    <div class="inner">
                        <h3><?= number_format((int) ($totalUsers ?? 0)) ?></h3>
                        <p>Tổng Thành Viên</p>
                    </div>
                    <div class="icon"><i class="ion ion-person-add text-muted"></i></div>
                    <a href="<?= url('admin/users') ?>" class="small-box-footer">
                        Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white border dashboard-mini-box">
                    <div class="inner">
                        <h3><?= number_format((int) ($totalProducts ?? 0)) ?></h3>
                        <p>Tổng Sản Phẩm</p>
                    </div>
                    <div class="icon"><i class="ion ion-pricetag text-muted"></i></div>
                    <a href="<?= url('admin/products') ?>" class="small-box-footer">
                        Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white border dashboard-mini-box">
                    <div class="inner">
                        <h3><?= number_format((int) ($totalBanned ?? 0)) ?></h3>
                        <p>Thành Viên Bị Khóa</p>
                    </div>
                    <div class="icon"><i class="ion ion-locked text-muted"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white border dashboard-mini-box">
                    <div class="inner">
                        <h3><?= $fmtMoney($totalMoney ?? 0) ?></h3>
                        <p>Tổng Số Dư Người Dùng</p>
                        <div class="dashboard-mini-sub">
                            Tổng nạp tích lũy: <?= $fmtMoney($totalUserDeposited ?? 0) ?>
                        </div>
                    </div>
                    <div class="icon"><i class="ion ion-stats-bars text-muted"></i></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header border-0 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h3 class="card-title mb-0">BIỂU ĐỒ XU HƯỚNG DOANH THU</h3>
                        <span class="badge badge-light border"><?= htmlspecialchars((string) ($chartData['window_label'] ?? $rangeLabel)) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-chart-wrap">
                            <canvas id="revenueTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header border-0 pb-2">
                        <h3 class="card-title">TỶ TRỌNG WEB VS TELEGRAM</h3>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-chart-wrap dashboard-chart-wrap--donut">
                            <canvas id="revenueShareChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header border-0 pb-2">
                        <h3 class="card-title">CHI TIẾT DOANH THU THEO KÊNH</h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Kênh</th>
                                        <th class="text-center">Đơn</th>
                                        <th class="text-right">Doanh thu</th>
                                        <th class="text-center">Tỷ trọng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($channelBreakdown as $channel): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($channel['channel_label'] ?? '--')) ?></td>
                                            <td class="text-center"><?= number_format((int) ($channel['orders_count'] ?? 0)) ?></td>
                                            <td class="text-right"><?= $fmtMoney($channel['revenue_total'] ?? 0) ?></td>
                                            <td class="text-center"><?= number_format((float) ($channel['revenue_ratio'] ?? 0), 1) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header border-0 pb-2">
                        <h3 class="card-title">TOP SẢN PHẨM THEO DOANH THU</h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 52px;">#</th>
                                        <th>Tên sản phẩm</th>
                                        <th class="text-center">Đơn</th>
                                        <th class="text-center">SL</th>
                                        <th class="text-right">Doanh thu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($topProducts)): ?>
                                        <?php foreach ($topProducts as $idx => $row): ?>
                                            <tr>
                                                <td class="text-center"><?= (int) $idx + 1 ?></td>
                                                <td><?= htmlspecialchars((string) ($row['product_name'] ?? '--')) ?></td>
                                                <td class="text-center"><?= number_format((int) ($row['orders_count'] ?? 0)) ?></td>
                                                <td class="text-center"><?= number_format((int) ($row['quantity_total'] ?? 0)) ?></td>
                                                <td class="text-right"><?= $fmtMoney($row['revenue_total'] ?? 0) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Không có dữ liệu trong khoảng thời gian này.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header border-0 pb-2">
                <h3 class="card-title">CHI TIẾT ĐƠN HÀNG GẦN NHẤT</h3>
            </div>
            <div class="card-body pt-2">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 52px;">#</th>
                                <th>Mã đơn</th>
                                <th>Kênh</th>
                                <th>User</th>
                                <th>Sản phẩm</th>
                                <th class="text-center">SL</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-right">Giá</th>
                                <th class="text-center">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentOrders)): ?>
                                <?php foreach ($recentOrders as $idx => $order): ?>
                                    <?php
                                    $status = (string) ($order['status'] ?? '');
                                    $statusLabel = (string) ($order['status_label'] ?? '--');
                                    $statusClass = $orderStatusBadgeClass($status);
                                    $channelKey = (string) ($order['channel_key'] ?? 'web');
                                    $channelBadge = $channelKey === 'telegram'
                                        ? 'badge badge-info'
                                        : 'badge badge-primary';
                                    ?>
                                    <tr>
                                        <td class="text-center"><?= (int) $idx + 1 ?></td>
                                        <td><code><?= htmlspecialchars((string) ($order['order_code'] ?? '--')) ?></code></td>
                                        <td class="text-center">
                                            <span class="<?= $channelBadge ?>">
                                                <?= htmlspecialchars((string) ($order['channel_label'] ?? 'Web')) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars((string) ($order['username'] ?? '--')) ?></td>
                                        <td><?= htmlspecialchars((string) ($order['product_name'] ?? '--')) ?></td>
                                        <td class="text-center"><?= number_format((int) ($order['quantity'] ?? 1)) ?></td>
                                        <td class="text-center"><span class="<?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                                        <td class="text-right"><?= $fmtMoney($order['price'] ?? 0) ?></td>
                                        <td class="text-center"><?= htmlspecialchars((string) ($order['created_display'] ?? '--')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Không có dữ liệu đơn hàng.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header border-0 pb-2">
                <h3 class="card-title">CHI TIẾT NẠP TIỀN GẦN NHẤT</h3>
            </div>
            <div class="card-body pt-2">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 52px;">#</th>
                                <th>Mã GD</th>
                                <th>User</th>
                                <th>Phương thức</th>
                                <th>Nội dung</th>
                                <th class="text-right">Số tiền</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentDeposits)): ?>
                                <?php foreach ($recentDeposits as $idx => $deposit): ?>
                                    <?php
                                    $depositStatus = strtolower(trim((string) ($deposit['status'] ?? '')));
                                    $depositStatusClass = in_array($depositStatus, ['hoantat', 'thanhcong', 'success', 'completed'], true)
                                        ? 'badge badge-success'
                                        : 'badge badge-secondary';
                                    ?>
                                    <tr>
                                        <td class="text-center"><?= (int) $idx + 1 ?></td>
                                        <td><code><?= htmlspecialchars((string) ($deposit['trans_id'] ?? '--')) ?></code></td>
                                        <td><?= htmlspecialchars((string) ($deposit['username'] ?? '--')) ?></td>
                                        <td><?= htmlspecialchars((string) ($deposit['type'] ?? '--')) ?></td>
                                        <td><?= htmlspecialchars((string) ($deposit['ctk'] ?? '--')) ?></td>
                                        <td class="text-right"><?= $fmtMoney($deposit['thucnhan'] ?? 0) ?></td>
                                        <td class="text-center">
                                            <span class="<?= $depositStatusClass ?>">
                                                <?= htmlspecialchars((string) ($deposit['status_label'] ?? '--')) ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars((string) ($deposit['created_display'] ?? '--')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Không có dữ liệu nạp tiền.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    (function () {
        if (typeof Chart !== 'function') return;

        const chartLabels = <?= json_encode(array_values($chartData['labels'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
        const revenueTotal = <?= json_encode(array_values($chartData['revenue_total'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
        const revenueWeb = <?= json_encode(array_values($chartData['revenue_web'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
        const revenueTelegram = <?= json_encode(array_values($chartData['revenue_telegram'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
        const depositTotal = <?= json_encode(array_values($chartData['deposit_total'] ?? []), JSON_UNESCAPED_UNICODE) ?>;

        const totalWeb = Number(<?= json_encode((int) ($revenueStats['revenue_web'] ?? 0), JSON_UNESCAPED_UNICODE) ?>);
        const totalTelegram = Number(<?= json_encode((int) ($revenueStats['revenue_telegram'] ?? 0), JSON_UNESCAPED_UNICODE) ?>);

        const moneyTick = function (value) {
            const number = Number(value || 0);
            return number.toLocaleString('vi-VN') + 'đ';
        };

        const trendCanvas = document.getElementById('revenueTrendChart');
        if (trendCanvas && chartLabels.length > 0) {
            new Chart(trendCanvas, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Doanh thu tổng',
                            data: revenueTotal,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.12)',
                            fill: true,
                            tension: 0.28,
                            borderWidth: 2
                        },
                        {
                            label: 'Web',
                            data: revenueWeb,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.10)',
                            fill: false,
                            tension: 0.28,
                            borderWidth: 2
                        },
                        {
                            label: 'Bot Telegram',
                            data: revenueTelegram,
                            borderColor: '#06b6d4',
                            backgroundColor: 'rgba(6, 182, 212, 0.10)',
                            fill: false,
                            tension: 0.28,
                            borderWidth: 2
                        },
                        {
                            label: 'Nạp tiền',
                            data: depositTotal,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.10)',
                            fill: false,
                            tension: 0.28,
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return ctx.dataset.label + ': ' + moneyTick(ctx.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: function (value) {
                                    return moneyTick(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        const shareCanvas = document.getElementById('revenueShareChart');
        if (shareCanvas) {
            new Chart(shareCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Web', 'Bot Telegram'],
                    datasets: [{
                        data: [totalWeb, totalTelegram],
                        backgroundColor: ['#3b82f6', '#06b6d4'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return ctx.label + ': ' + moneyTick(ctx.raw);
                                }
                            }
                        }
                    }
                }
            });
        }
    })();
</script>

<?php require_once __DIR__ . '/layout/foot.php'; ?>

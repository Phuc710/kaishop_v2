<?php
/**
 * View: Admin Dashboard
 * Route: GET /admin
 * Controller: DashboardController@index
 */
$pageTitle = 'Dashboard Admin';
$breadcrumbs = [
    ['label' => 'Dashboard'],
];
require_once __DIR__ . '/layout/head.php';
require_once __DIR__ . '/layout/breadcrumb.php';
?>
<style>
    .dashboard-kpi-growth {
        font-size: 0.85rem;
        margin-left: 8px;
        font-weight: 600;
        vertical-align: middle;
        white-space: nowrap;
    }
    .card.border-warning { border-top: 3px solid #ffc107 !important; }
    .card.border-success { border-top: 3px solid #16a34a !important; }
    .card.border-primary { border-top: 3px solid #3b82f6 !important; }
    .fw-bold { font-weight: 700 !important; }
</style>
<?php

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
$topDepositors = is_array($topDepositors ?? null) ? $topDepositors : [];

$rangeOptions = [
    'all' => 'Tất cả',
    'today' => 'Hôm nay',
    'week' => 'Tuần này',
    'month' => 'Tháng này',
    'quarter' => 'Quý này',
    'year' => 'Năm này',
];

$fmtMoney = static function ($value): string {
    return number_format((float) $value, 0, ',', '.') . ' VNĐ';
};

$fmtInt = static function ($value): string {
    return number_format((int) $value);
};

$fmtStatusLabel = static function (string $status): string {
    $status = strtolower(trim($status));
    return match ($status) {
        'completed' => 'Xong',
        'pending' => 'Chờ',
        'processing' => 'Xử lý',
        'cancelled', 'canceled', 'failed' => 'Hủy',
        default => strtoupper($status) ?: '--',
    };
};

$orderStatusBadgeClass = static function (string $status): string {
    return match (strtolower(trim($status))) {
        'completed' => 'badge badge-success',
        'pending', 'processing' => 'badge badge-warning',
        'cancelled', 'canceled', 'failed' => 'badge badge-danger',
        default => 'badge badge-secondary',
    };
};

$revenueTotal = (int) ($revenueStats['revenue_total'] ?? 0);
$revenueWeb = (int) ($revenueStats['revenue_web'] ?? 0);
$revenueTelegram = (int) ($revenueStats['revenue_telegram'] ?? 0);
$depositCount = (int) ($revenueStats['deposit_count'] ?? 0);
$netFlow = (int) ($revenueStats['net_flow'] ?? 0);

$channelMap = [
    'web' => [
        'channel_key' => 'web',
        'channel_label' => 'Web',
        'orders_count' => 0,
        'revenue_total' => 0,
        'revenue_ratio' => 0,
        'orders_ratio' => 0,
    ],
    'telegram' => [
        'channel_key' => 'telegram',
        'channel_label' => 'Telegram Bot',
        'orders_count' => 0,
        'revenue_total' => 0,
        'revenue_ratio' => 0,
        'orders_ratio' => 0,
    ],
];

foreach ($channelBreakdown as $row) {
    $key = (string) ($row['channel_key'] ?? '');
    if (!isset($channelMap[$key])) {
        continue;
    }
    $channelMap[$key] = array_merge($channelMap[$key], $row);
}
?>

<section class="content mt-3 pb-4 admin-dashboard-page admin-dashboard-refactor">
    <div class="container-fluid">
        <div class="card custom-card dashboard-hero-card">
            <div class="card-body">
                <div class="dashboard-hero-top">
                    <div>
                        <h1 class="dashboard-page-title">Dashboard tài chính</h1>
                        <p class="dashboard-page-subtitle">
                            Theo dõi dòng tiền nạp, dòng tiền đã mua và hiệu suất theo kênh.
                        </p>
                    </div>
                    <span class="dashboard-range-badge">
                        <?= htmlspecialchars($rangeLabel) ?> | <?= htmlspecialchars($rangeText) ?>
                    </span>
                </div>

                <form method="get" action="<?= url('admin') ?>" class="dashboard-range-form">
                    <label for="dashboardRangeSelect" class="dashboard-range-label">Khoảng thời gian</label>
                    <select
                        id="dashboardRangeSelect"
                        name="range"
                        class="dashboard-range-select"
                        onchange="this.form.submit()"
                    >
                        <?php foreach ($rangeOptions as $rangeKey => $rangeName): ?>
                            <option value="<?= htmlspecialchars($rangeKey) ?>" <?= $activeRange === $rangeKey ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rangeName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>


        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header border-0 pb-2">
                        <h3 class="card-title mb-0">Doanh Thu</h3>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-chart-wrap">
                            <canvas id="cashflowTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header border-0 pb-2">
                        <h3 class="card-title">Kênh mua hàng</h3>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-chart-wrap dashboard-chart-wrap--donut">
                            <canvas id="purchaseChannelChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row mt-4">
            <div class="col-xl-6">
                <div class="card custom-card border-primary">
                    <div class="card-header border-0 pb-2">
                        <h3 class="card-title mb-0 text-primary fw-bold text-center w-100"> Top Mua</h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                 <thead>
                                     <tr>
                                         <th style="width: 50px;" class="text-center">#</th>
                                         <th class="text-center">User</th>
                                         <th class="text-center">Đơn</th>
                                         <th class="text-center">Tổng chi</th>
                                     </tr>
                                 </thead>
                                <tbody>
                                    <?php if (!empty($topSpenders)): ?>
                                        <?php foreach ($topSpenders as $idx => $spender): ?>
                                             <tr>
                                                 <td class="text-center text-muted">#<?= $idx + 1 ?></td>
                                                 <td class="text-center">
                                                     <strong><?= htmlspecialchars($spender['username']) ?></strong>
                                                 </td>
                                                 <td class="text-center"><?= $fmtInt($spender['order_count']) ?></td>
                                                 <td class="text-center text-success fw-bold"><?= $fmtMoney($spender['total_spent']) ?></td>
                                             </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                         <tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu chi tiêu.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card border-success">
                    <div class="card-header border-0 pb-2">
                        <h3 class="card-title mb-0 text-success fw-bold text-center w-100">Top nạp</h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                 <thead>
                                     <tr>
                                         <th style="width: 50px;" class="text-center">#</th>
                                         <th class="text-center">User</th>
                                         <th class="text-center">Lần nạp</th>
                                         <th class="text-center">Tổng nạp</th>
                                     </tr>
                                 </thead>
                                <tbody>
                                    <?php if (!empty($topDepositors)): ?>
                                        <?php foreach ($topDepositors as $idx => $depositor): ?>
                                             <tr>
                                                 <td class="text-center text-muted">#<?= $idx + 1 ?></td>
                                                 <td class="text-center">
                                                     <strong><?= htmlspecialchars((string) ($depositor['username'] ?? '')) ?></strong>
                                                 </td>
                                                 <td class="text-center">
                                                     <?php
                                                     $depositCount = (int) ($depositor['deposit_count'] ?? 0);
                                                     echo $depositCount > 0 ? $fmtInt($depositCount) : '--';
                                                     ?>
                                                 </td>
                                                 <td class="text-center text-success fw-bold"><?= $fmtMoney($depositor['total_deposit'] ?? 0) ?></td>
                                             </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                         <tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu nạp.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header border-0 pb-2">
                        <h3 class="card-title">Top sản phẩm mua nhiều nhất</h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 52px;" class="text-center">#</th>
                                        <th class="text-center">Tên sản phẩm</th>
                                        <th class="text-center">Đơn</th>
                                        <th class="text-center">Doanh thu</th>
                                        <th class="text-center">Tỷ trọng</th>
                                        <th class="text-center">Giá TB</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($topProducts)): ?>
                                        <?php foreach ($topProducts as $idx => $row): ?>
                                            <tr>
                                                <td class="text-center"><?= (int) $idx + 1 ?></td>
                                                <td class="fw-bold text-center"><?= htmlspecialchars((string) ($row['product_name'] ?? '--')) ?></td>
                                                <td class="text-center"><?= $fmtInt($row['orders_count'] ?? 0) ?></td>
                                                <td class="text-center text-primary fw-bold"><?= $fmtMoney($row['revenue_total'] ?? 0) ?></td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-primary">
                                                        <?= $spendTotal > 0 ? round(($row['revenue_total'] / $spendTotal) * 100, 1) : 0 ?>%
                                                    </span>
                                                </td>
                                                <td class="text-center text-muted small">
                                                    <?= $row['orders_count'] > 0 ? $fmtMoney($row['revenue_total'] / $row['orders_count']) : '0đ' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Không có dữ liệu trong khoảng thời gian này.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header border-0 pb-2">
                        <h3 class="card-title">Chi tiết theo kênh mua</h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center">Kênh</th>
                                        <th class="text-center">Số đơn</th>
                                        <th class="text-center">Tiền đã mua</th>
                                        <th class="text-center">Tỷ trọng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($channelBreakdown as $channel): ?>
                                        <tr>
                                            <td class="text-center"><?= htmlspecialchars((string) ($channel['channel_label'] ?? '--')) ?></td>
                                            <td class="text-center"><?= $fmtInt($channel['orders_count'] ?? 0) ?></td>
                                            <td class="text-center"><?= $fmtMoney($channel['revenue_total'] ?? 0) ?></td>
                                            <td class="text-center"><?= number_format((float) ($channel['revenue_ratio'] ?? 0), 1) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
        const seriesSpendTotal = <?= json_encode(array_values($chartData['revenue_total'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
        const seriesSpendWeb = <?= json_encode(array_values($chartData['revenue_web'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
        const seriesSpendTelegram = <?= json_encode(array_values($chartData['revenue_telegram'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
        const seriesDepositTotal = <?= json_encode(array_values($chartData['deposit_total'] ?? []), JSON_UNESCAPED_UNICODE) ?>;

        const totalWeb = Number(<?= json_encode($revenueStats['deposit_web'] ?? 0, JSON_UNESCAPED_UNICODE) ?>);
        const totalTelegram = Number(<?= json_encode($revenueStats['deposit_telegram'] ?? 0, JSON_UNESCAPED_UNICODE) ?>);

        const categoryLabels = <?= json_encode(array_column($categoryBreakdown, 'category_name'), JSON_UNESCAPED_UNICODE) ?>;
        const categoryData = <?= json_encode(array_column($categoryBreakdown, 'revenue_total'), JSON_UNESCAPED_UNICODE) ?>;

        const orderStatusLabels = <?= json_encode(array_map(fn($s) => $fmtStatusLabel($s['status'] ?? ''), $orderStatusBreakdown), JSON_UNESCAPED_UNICODE) ?>;
        const orderStatusData = <?= json_encode(array_column($orderStatusBreakdown, 'count'), JSON_UNESCAPED_UNICODE) ?>;

        const methodLabels = <?= json_encode(array_column($depositMethodBreakdown, 'method'), JSON_UNESCAPED_UNICODE) ?>;
        const methodData = <?= json_encode(array_column($depositMethodBreakdown, 'total_amount'), JSON_UNESCAPED_UNICODE) ?>;
        
        // Channel data for doughnut (Purchases Web vs Bot)
        const buyWeb = Number(<?= json_encode($revenueStats['spend_web'] ?? 0, JSON_UNESCAPED_UNICODE) ?>);
        const buyBot = Number(<?= json_encode($revenueStats['spend_telegram'] ?? 0, JSON_UNESCAPED_UNICODE) ?>);

        const moneyTick = function (value) {
            const number = Number(value || 0);
            return number.toLocaleString('vi-VN') + ' VNĐ';
        };

        const chartColors = [
            '#2563eb', '#16a34a', '#dc2626', '#ca8a04', '#7c3aed', 
            '#0891b2', '#db2777', '#4b5563', '#ea580c', '#65a30d'
        ];

        const trendCanvas = document.getElementById('cashflowTrendChart');
        if (trendCanvas && chartLabels.length > 0) {
            new Chart(trendCanvas, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Web',
                            data: seriesDepositTotal,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.12)',
                            fill: true,
                            tension: 0.25,
                            borderWidth: 2
                        },
                        {
                            label: 'Bot Tele',
                            data: seriesSpendTelegram,
                            borderColor: '#0891b2',
                            backgroundColor: 'rgba(8, 145, 178, 0.12)',
                            fill: true,
                            tension: 0.25,
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
                        legend: { position: 'bottom' },
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
                                callback: function (value) { return moneyTick(value); }
                            }
                        }
                    }
                }
            });
        }

        // Category Chart
        const catCanvas = document.getElementById('categorySalesChart');
        if (catCanvas && categoryLabels.length > 0) {
            new Chart(catCanvas, {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryData,
                        backgroundColor: chartColors,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) { return ctx.label + ': ' + moneyTick(ctx.raw); }
                            }
                        }
                    }
                }
            });
        }

        // Order Status Chart
        const osCanvas = document.getElementById('orderStatusChart');
        if (osCanvas && orderStatusLabels.length > 0) {
            new Chart(osCanvas, {
                type: 'doughnut',
                data: {
                    labels: orderStatusLabels,
                    datasets: [{
                        data: orderStatusData,
                        backgroundColor: ['#ca8a04', '#2563eb', '#16a34a', '#dc2626'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: { 
                        legend: { position: 'bottom' }
                    }
                }
            });
        }

        // Deposit Method Chart
        const mCanvas = document.getElementById('depositMethodChart');
        if (mCanvas && methodLabels.length > 0) {
            new Chart(mCanvas, {
                type: 'doughnut',
                data: {
                    labels: methodLabels,
                    datasets: [{
                        data: methodData,
                        backgroundColor: chartColors,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) { return ctx.label + ': ' + moneyTick(ctx.raw); }
                            }
                        }
                    }
                }
            });
        }

        const shareCanvas = document.getElementById('purchaseChannelChart');
        if (shareCanvas) {
            new Chart(shareCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Mua Web', 'Mua Bot'],
                    datasets: [{
                        data: [buyWeb, buyBot],
                        backgroundColor: ['#2563eb', '#0891b2'],
                        borderColor: ['#ffffff', '#ffffff'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom' },
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
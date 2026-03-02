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

$rangeOptions = [
    'all' => 'Tất cả',
    'today' => 'Hôm nay',
    'week' => 'Tuần này',
    'month' => 'Tháng này',
    'quarter' => 'Quý này',
    'year' => 'Năm này',
];

$fmtMoney = static function ($value): string {
    return number_format((int) $value) . 'đ';
};

$fmtInt = static function ($value): string {
    return number_format((int) $value);
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

$spendWeb = (int) ($revenueStats['spend_web'] ?? $revenueStats['revenue_web'] ?? 0);
$spendTelegram = (int) ($revenueStats['spend_telegram'] ?? $revenueStats['revenue_telegram'] ?? 0);
$spendTotal = $spendWeb + $spendTelegram;
$depositTotal = (int) ($revenueStats['deposit_total'] ?? 0);
$depositCount = (int) ($revenueStats['deposit_count'] ?? 0);
$ordersSold = (int) ($revenueStats['orders_sold'] ?? 0);
$ordersAll = (int) ($revenueStats['orders_all'] ?? 0);
$netFlow = (int) ($revenueStats['net_flow'] ?? ($depositTotal - ($spendWeb + $spendTelegram)));

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

        <div class="dashboard-kpi-grid">
            <div class="dashboard-kpi-card tone-deposit">
                <div class="dashboard-kpi-head">
                    <span>Doanh thu</span>
                    <i class="ion ion-card"></i>
                </div>
                <div class="dashboard-kpi-value">
                    <?= $fmtMoney($depositTotal) ?>
                    <?php
                    $prevDeposit = (int) ($prevRevenueStats['deposit_total'] ?? 0);
                    if ($prevDeposit > 0):
                        $growth = round((($depositTotal - $prevDeposit) / $prevDeposit) * 100, 1);
                        $growthClass = $growth >= 0 ? 'text-success' : 'text-danger';
                        $growthIcon = $growth >= 0 ? 'ion-arrow-up-b' : 'ion-arrow-down-b';
                    ?>
                        <span class="dashboard-kpi-growth <?= $growthClass ?>" title="So với kỳ trước (<?= $fmtMoney($prevDeposit) ?>)">
                            <i class="ion <?= $growthIcon ?>"></i> <?= abs($growth) ?>%
                        </span>
                    <?php endif; ?>
                </div>
                <div class="dashboard-kpi-meta">
                    <?= $fmtInt($depositCount) ?> giao dịch nạp thành công
                </div>
            </div>

            <div class="dashboard-kpi-card tone-spend">
                <div class="dashboard-kpi-head">
                    <span>Doanh Thu web</span>
                    <i class="ion ion-earth"></i>
                </div>
                <div class="dashboard-kpi-value"><?= $fmtMoney($spendWeb) ?></div>
                <div class="dashboard-kpi-meta">
                    <?= $fmtInt($channelMap['web']['orders_count'] ?? 0) ?> đơn | <?= number_format((float) ($channelMap['web']['revenue_ratio'] ?? 0), 1) ?>%
                </div>
            </div>

            <div class="dashboard-kpi-card tone-balance">
                <div class="dashboard-kpi-head">
                    <span>Doanh Thu BotTele</span>
                    <i class="ion ion-paper-plane"></i>
                </div>
                <div class="dashboard-kpi-value"><?= $fmtMoney($spendTelegram) ?></div>
                <div class="dashboard-kpi-meta">
                    <?= $fmtInt($channelMap['telegram']['orders_count'] ?? 0) ?> đơn | <?= number_format((float) ($channelMap['telegram']['revenue_ratio'] ?? 0), 1) ?>%
                </div>
            </div>
        </div>

        <div class="dashboard-channel-grid">
            <div class="dashboard-channel-card">
                <div class="channel-label">Mua từ Web</div>
                <div class="channel-value"><?= $fmtMoney($spendWeb) ?></div>
                <div class="channel-meta">
                    <?= $fmtInt($channelMap['web']['orders_count'] ?? 0) ?> đơn |
                    <?= number_format((float) ($channelMap['web']['revenue_ratio'] ?? 0), 1) ?>%
                </div>
            </div>
            <div class="dashboard-channel-card">
                <div class="channel-label">Mua từ Telegram Bot</div>
                <div class="channel-value"><?= $fmtMoney($spendTelegram) ?></div>
                <div class="channel-meta">
                    <?= $fmtInt($channelMap['telegram']['orders_count'] ?? 0) ?> đơn |
                    <?= number_format((float) ($channelMap['telegram']['revenue_ratio'] ?? 0), 1) ?>%
                </div>
            </div>
            <div class="dashboard-channel-card channel-total">
                <div class="channel-label">Tổng số dư người dùng</div>
                <div class="channel-value"><?= $fmtMoney($totalMoney ?? 0) ?></div>
                <div class="channel-meta">Số dư hiện tại của tất cả user</div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header border-0 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h3 class="card-title mb-0">Xu hướng dòng tiền</h3>
                        <span class="badge badge-light border">
                            <?= htmlspecialchars((string) ($chartData['window_label'] ?? $rangeLabel)) ?>
                        </span>
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
                        <h3 class="card-title">Tỷ trọng mua theo kênh</h3>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-chart-wrap dashboard-chart-wrap--donut">
                            <canvas id="purchaseChannelChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-system-grid">
            <div class="dashboard-system-card">
                <div class="sys-label">Thành viên</div>
                <div class="sys-value"><?= $fmtInt($totalUsers ?? 0) ?></div>
            </div>
            <div class="dashboard-system-card">
                <div class="sys-label">Sản phẩm</div>
                <div class="sys-value"><?= $fmtInt($totalProducts ?? 0) ?></div>
            </div>
            <div class="dashboard-system-card">
                <div class="sys-label">Thành viên bị khóa</div>
                <div class="sys-value"><?= $fmtInt($totalBanned ?? 0) ?></div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-xl-6">
                <div class="card custom-card border-warning">
                    <div class="card-header border-0 pb-2 d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0 text-warning fw-bold"><i class="ion ion-alert-circled"></i> Cảnh báo tồn kho</h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th class="text-center">Loại</th>
                                        <th class="text-right">Còn lại</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($lowStockProducts)): ?>
                                        <?php foreach ($lowStockProducts as $low): ?>
                                            <tr>
                                                <td><a href="<?= url('admin/product/edit/' . $low['id']) ?>" class="text-dark"><?= htmlspecialchars($low['name']) ?></a></td>
                                                <td class="text-center"><span class="badge badge-light"><?= $low['product_type'] === 'account' ? 'Tài khoản' : 'Khác' ?></span></td>
                                                <td class="text-right fw-bold text-danger"><?= $fmtInt($low['available_count']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-muted py-3">Kho hàng hiện tại rất dồi dào. 🎉</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card border-primary">
                    <div class="card-header border-0 pb-2">
                        <h3 class="card-title mb-0 text-primary fw-bold"><i class="ion ion-trophy"></i> Top đại gia chi đậm</h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th class="text-center">Đơn</th>
                                        <th class="text-right">Tổng chi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($topSpenders)): ?>
                                        <?php foreach ($topSpenders as $idx => $spender): ?>
                                            <tr>
                                                <td>
                                                    <span class="text-muted">#<?= $idx + 1 ?></span> 
                                                    <strong><?= htmlspecialchars($spender['username']) ?></strong>
                                                </td>
                                                <td class="text-center"><?= $fmtInt($spender['order_count']) ?></td>
                                                <td class="text-right text-success fw-bold"><?= $fmtMoney($spender['total_spent']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-muted py-3">Chưa có dữ liệu chi tiêu.</td></tr>
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
                                        <th style="width: 52px;">#</th>
                                        <th>Tên sản phẩm</th>
                                        <th class="text-center">Đơn</th>
                                        <th class="text-right">Doanh thu</th>
                                        <th class="text-center">Tỷ trọng</th>
                                        <th class="text-right">Giá TB</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($topProducts)): ?>
                                        <?php foreach ($topProducts as $idx => $row): ?>
                                            <tr>
                                                <td class="text-center"><?= (int) $idx + 1 ?></td>
                                                <td class="fw-bold"><?= htmlspecialchars((string) ($row['product_name'] ?? '--')) ?></td>
                                                <td class="text-center"><?= $fmtInt($row['orders_count'] ?? 0) ?></td>
                                                <td class="text-right text-primary fw-bold"><?= $fmtMoney($row['revenue_total'] ?? 0) ?></td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-primary">
                                                        <?= $spendTotal > 0 ? round(($row['revenue_total'] / $spendTotal) * 100, 1) : 0 ?>%
                                                    </span>
                                                </td>
                                                <td class="text-right text-muted small">
                                                    <?= $row['orders_count'] > 0 ? $fmtMoney($row['revenue_total'] / $row['orders_count']) : '0đ' ?>
                                                </td>
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
                                        <th>Kênh</th>
                                        <th class="text-center">Số đơn</th>
                                        <th class="text-right">Tiền đã mua</th>
                                        <th class="text-center">Tỷ trọng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($channelBreakdown as $channel): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($channel['channel_label'] ?? '--')) ?></td>
                                            <td class="text-center"><?= $fmtInt($channel['orders_count'] ?? 0) ?></td>
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

        const totalWeb = Number(<?= json_encode($spendWeb, JSON_UNESCAPED_UNICODE) ?>);
        const totalTelegram = Number(<?= json_encode($spendTelegram, JSON_UNESCAPED_UNICODE) ?>);

        const moneyTick = function (value) {
            const number = Number(value || 0);
            return number.toLocaleString('vi-VN') + 'đ';
        };

        const trendCanvas = document.getElementById('cashflowTrendChart');
        if (trendCanvas && chartLabels.length > 0) {
            new Chart(trendCanvas, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Tiền nạp',
                            data: seriesDepositTotal,
                            borderColor: '#0f766e',
                            backgroundColor: 'rgba(15, 118, 110, 0.12)',
                            fill: true,
                            tension: 0.25,
                            borderWidth: 2
                        },
                        {
                            label: 'Tiền đã mua (Tổng)',
                            data: seriesSpendTotal,
                            borderColor: '#1d4ed8',
                            backgroundColor: 'rgba(29, 78, 216, 0.08)',
                            fill: false,
                            tension: 0.25,
                            borderWidth: 2
                        },
                        {
                            label: 'Mua từ Web',
                            data: seriesSpendWeb,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.08)',
                            fill: false,
                            tension: 0.25,
                            borderWidth: 2
                        },
                        {
                            label: 'Mua từ Telegram',
                            data: seriesSpendTelegram,
                            borderColor: '#0891b2',
                            backgroundColor: 'rgba(8, 145, 178, 0.08)',
                            fill: false,
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

        const shareCanvas = document.getElementById('purchaseChannelChart');
        if (shareCanvas) {
            new Chart(shareCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Web', 'Telegram Bot'],
                    datasets: [{
                        data: [totalWeb, totalTelegram],
                        backgroundColor: ['#2563eb', '#0891b2'],
                        borderColor: ['#ffffff', '#ffffff'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '64%',
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

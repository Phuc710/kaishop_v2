<?php
/**
 * View: Telegram Bot — Dashboard
 * Route: GET /admin/telegram
 */
require_once __DIR__ . '/../layout/head.php';
$pageTitle = 'Tổng quan Telegram Bot';
$breadcrumbs = [
    ['label' => 'Telegram Bot', 'url' => url('admin/telegram')],
    ['label' => 'Tổng quan'],
];
require_once __DIR__ . '/../layout/breadcrumb.php';

$botOk = !empty($botInfo['ok']);
$botName = $botOk ? ($botInfo['result']['first_name'] ?? '???') : '—';
$botUsername = $botOk ? ('@' . ($botInfo['result']['username'] ?? '')) : '—';
?>

<style>
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

    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }
</style>

<section class="content pb-5 mt-1">
    <div class="container-fluid">
        <div class="row">
            <!-- Charts Column -->
            <div class="col-lg-8">
                <div class="card custom-card shadow-sm mb-4">
                    <div class="card-header border-0 pb-0">
                        <h5 class="card-title font-weight-bold mb-0">THỐNG KÊ HOẠT ĐỘNG (7 NGÀY)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="card custom-card shadow-sm">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title font-weight-bold mb-0">TIN NHẮN GẦN ĐÂY</h5>
                        <a href="<?= url('admin/telegram/outbox') ?>"
                            class="btn btn-primary btn-sm rounded-pill px-3">Xem tất cả</a>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-hover table-borderless align-middle mb-0">
                            <thead class="text-muted small uppercase font-weight-bold border-bottom">
                                <tr>
                                    <th>ID</th>
                                    <th>Người nhận</th>
                                    <th>Nội dung</th>
                                    <th>Trạng thái</th>
                                    <th>Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentMessages)):
                                    foreach ($recentMessages as $msg): ?>
                                        <tr>
                                            <td class="font-weight-bold text-muted">#<?= $msg['id'] ?></td>
                                            <td><code><?= $msg['telegram_id'] ?></code></td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 250px;">
                                                    <?= htmlspecialchars($msg['message']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($msg['status'] === 'sent'): ?>
                                                    <span class="badge bg-soft-success text-success px-3">Thành công</span>
                                                <?php elseif ($msg['status'] === 'fail'): ?>
                                                    <span class="badge bg-soft-danger text-danger px-3">Thất bại</span>
                                                <?php else: ?>
                                                    <span class="badge bg-soft-warning text-warning px-3">Đang chờ</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted"><?= $msg['created_at'] ?></td>
                                        </tr>
                                    <?php endforeach; else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">Chưa có tin nhắn nào</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Action Column -->
            <div class="col-lg-4">

                <div class="card custom-card shadow-sm">
                    <div class="card-header border-0 pb-0">
                        <h5 class="card-title font-weight-bold mb-0">TỈ LỆ TIN NHẮN</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="outboxPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Scripts for Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(function () {
        // Activity Chart
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        const activityData = {
            labels: <?= json_encode(array_column($orderStatsChart, 'date')) ?>,
            datasets: [
                {
                    label: 'Đơn từ Telegram',
                    data: <?= json_encode(array_column($orderStatsChart, 'tele_orders')) ?>,
                    borderColor: '#845adf',
                    backgroundColor: 'rgba(132, 90, 223, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Users liên kết mới',
                    data: <?= json_encode(array_column($linkedUsersChart, 'count')) ?>,
                    borderColor: '#23b7e5',
                    backgroundColor: 'rgba(35, 183, 229, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                }
            ]
        };
        new Chart(activityCtx, {
            type: 'line',
            data: activityData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
            }
        });

        // Pie Chart
        const pieCtx = document.getElementById('outboxPieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Thành công', 'Chờ gửi', 'Thất bại'],
                datasets: [{
                    data: [<?= (int) ($outboxStats['sent'] ?? 0) ?>, <?= (int) ($outboxStats['pending'] ?? 0) ?>, <?= (int) ($outboxStats['failed'] ?? 0) ?>],
                    backgroundColor: ['#26bf94', '#f1b44c', '#f14242'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                cutout: '70%'
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
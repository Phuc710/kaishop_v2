<?php
/**
 * View: Telegram Bot — Nhật ký
 * Route: GET /admin/telegram/logs
 */
require_once __DIR__ . '/../layout/head.php';
$pageTitle = 'Nhật ký Telegram';
$breadcrumbs = [
    ['label' => 'Telegram Bot', 'url' => url('admin/telegram')],
    ['label' => 'Nhật ký'],
];
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<section class="content pb-5 mt-1">
    <div class="container-fluid">
        <!-- Floating Stats Panel -->
        <div class="row mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card custom-card bg-info text-white shadow-sm"
                    style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%) !important;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-white-50 p-2 rounded">
                                <i class="fas fa-list-alt fa-lg"></i>
                            </div>
                            <div class="ml-3">
                                <h6 class="text-white-50 small font-weight-bold text-uppercase mb-1">Tổng nhật ký</h6>
                                <h4 class="mb-0 font-weight-bold"><?= number_format(count($logs)) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card shadow-sm border-0">
            <div class="card-header border-0 bg-transparent py-4">
                <h3 class="card-title text-uppercase font-weight-bold mb-0">Lịch sử hoạt động bot</h3>
            </div>

            <div class="card-body px-0 pt-0">
                <div class="table-responsive">
                    <table id="dtLogs" class="table table-hover align-middle mb-0 w-100">
                        <thead class="bg-light text-uppercase small">
                            <tr>
                                <th class="pl-4 text-center">ID</th>
                                <th class="text-center">Mức độ</th>
                                <th class="text-center">Hành động</th>
                                <th>Mô tả chi tiết</th>
                                <th class="text-right pr-4">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($logs)): ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="pl-4 text-center text-muted font-weight-bold">
                                            #<?= $log['id'] ?>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $levelClass = [
                                                'info' => 'badge-info',
                                                'warning' => 'badge-warning',
                                                'danger' => 'badge-danger',
                                                'success' => 'badge-success',
                                            ];
                                            $lvl = strtolower($log['level'] ?? 'info');
                                            $cls = $levelClass[$lvl] ?? 'badge-secondary';
                                            ?>
                                            <span class="badge <?= $cls ?> px-3 py-1"><?= strtoupper($lvl) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <code
                                                class="text-dark bg-light px-2 py-1 rounded"><?= htmlspecialchars($log['action'] ?? '') ?></code>
                                        </td>
                                        <td class="small text-muted" style="max-width:400px;">
                                            <?= htmlspecialchars($log['description'] ?? '') ?>
                                        </td>
                                        <td class="text-right pr-4 small text-muted font-weight-bold">
                                            <?= date('H:i d/m/Y', strtotime($log['created_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <p class="text-muted font-italic mb-0">Chưa có nhật ký ghi nhận</p>
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
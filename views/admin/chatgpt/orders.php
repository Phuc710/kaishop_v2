<?php
$pageTitle = 'Đơn hàng GPT';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/chatgpt/farms')],
    ['label' => 'Đơn hàng GPT'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$orders = $orders ?? [];
$farms = $farms ?? [];
$filters = $filters ?? [];
$summaryCards = $summaryCards ?? [];
$orderStatusOptions = $orderStatusOptions ?? [];
?>

<section class="content pb-4 mt-1 admin-chatgpt-page">
    <div class="container-fluid">
        <div class="row mb-3">
            <?php foreach ($summaryCards as $card): ?>
                <div class="col-xl col-md-6 mb-3">
                    <div class="gptb-stat-card gptb-stat-card--<?= htmlspecialchars($card['tone']) ?>">
                        <div class="gptb-stat-icon">
                            <i class="<?= htmlspecialchars($card['icon']) ?>"></i>
                        </div>
                        <div class="gptb-stat-body">
                            <div class="gptb-stat-label"><?= htmlspecialchars($card['label']) ?></div>
                            <div class="gptb-stat-value"><?= (int) ($card['value'] ?? 0) ?></div>
                            <div class="gptb-stat-hint"><?= htmlspecialchars($card['hint']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card custom-card">
            <div class="card-header gptb-card-header">
                <h3 class="card-title">ĐƠN HÀNG GPT BUSINESS</h3>
                <div class="gptb-card-actions">
                    <a href="<?= url('admin/chatgpt/farms') ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Về farms
                    </a>
                </div>
            </div>

            <div class="dt-filters">
                <form method="get" class="row align-items-end">
                    <div class="col-lg-3 col-md-6 mb-2">
                        <label class="gptb-filter-label">Email khách</label>
                        <input type="text" name="email" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($filters['email'] ?? '') ?>" placeholder="Nhập email cần tìm...">
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <label class="gptb-filter-label">Trạng thái</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="">Tất cả trạng thái</option>
                            <?php foreach ($orderStatusOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <label class="gptb-filter-label">Farm</label>
                        <select name="farm_id" class="form-control form-control-sm">
                            <option value="0">Tất cả farm</option>
                            <?php foreach ($farms as $farm): ?>
                                <option value="<?= (int) ($farm['id'] ?? 0) ?>" <?= (int) ($filters['farm_id'] ?? 0) === (int) ($farm['id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($farm['farm_name'] ?? '--') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <div class="gptb-filter-actions">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search mr-1"></i> Lọc
                            </button>
                            <a href="<?= url('admin/chatgpt/orders') ?>" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash-alt mr-1"></i> Xóa lọc
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-0">
                    <table class="table table-hover table-bordered admin-table gptb-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">Mã đơn</th>
                                <th class="text-center">Email khách</th>
                                <th class="text-center">Farm</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Hết hạn</th>
                                <th class="text-center">Tạo lúc</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Không có đơn hàng nào phù hợp bộ lọc.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="text-center">
                                            <span class="gptb-code"><?= htmlspecialchars($order['order_code'] ?? '--') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="gptb-email"><?= htmlspecialchars($order['customer_email'] ?? '--') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="cat-badge"><?= htmlspecialchars($order['farm_display'] ?? '--') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="<?= htmlspecialchars($order['status_badge_class'] ?? 'gptb-badge gptb-badge--muted') ?>">
                                                <?= htmlspecialchars($order['status_label'] ?? '--') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="date-badge"><?= htmlspecialchars($order['expires_at_display'] ?? '--') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="date-badge"><?= htmlspecialchars($order['created_at_display'] ?? '--') ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/foot.php'; ?>

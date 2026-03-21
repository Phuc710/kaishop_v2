<?php
$pageTitle = 'ChatGPT Orders';
require __DIR__ . '/../layout/head.php';
$orders = $orders ?? [];
$farms = $farms ?? [];
$stats = $stats ?? [];
$filters = $filters ?? [];
$statusMap = [
    'pending' => ['label' => 'Chờ', 'color' => '#94a3b8'],
    'inviting' => ['label' => 'Đã mời', 'color' => '#fbbf24'],
    'active' => ['label' => 'Active', 'color' => '#34d399'],
    'failed' => ['label' => 'Lỗi', 'color' => '#f87171'],
    'revoked' => ['label' => 'Revoked', 'color' => '#ef4444'],
];
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col">
                <h1 class="m-0" style="font-size:1.3rem">📦 ChatGPT Pro — Đơn hàng</h1>
            </div>
            <div class="col-auto"><a href="<?= url('admin/chatgpt/farms') ?>" class="btn btn-secondary btn-sm">←
                    Farms</a></div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">

        <!-- Stats -->
        <div class="row g-3 mb-3">
            <?php foreach (['active' => '✅ Active', 'inviting' => '⏳ Inviting', 'pending' => '🔵 Pending', 'failed' => '❌ Lỗi', 'revoked' => '🚫 Revoked'] as $k => $label): ?>
                <div class="col-6 col-md-2">
                    <div class="card text-center p-2"
                        style="background:#1e293b;border:1px solid #334155;border-radius:10px;">
                        <div style="font-size:.75rem;color:#64748b">
                            <?= $label ?>
                        </div>
                        <div style="font-size:1.5rem;font-weight:800;color:#e2e8f0">
                            <?= (int) ($stats[$k . '_count'] ?? 0) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <form method="get" class="row g-2 mb-3">
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm"
                    style="background:#1e293b;border-color:#334155;color:#e2e8f0">
                    <option value="">Tất cả status</option>
                    <?php foreach (array_keys($statusMap) as $s): ?>
                        <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>>
                            <?= $statusMap[$s]['label'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="farm_id" class="form-select form-select-sm"
                    style="background:#1e293b;border-color:#334155;color:#e2e8f0">
                    <option value="">Tất cả farm</option>
                    <?php foreach ($farms as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= (int) ($filters['farm_id'] ?? 0) === (int) $f['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['farm_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <input type="text" name="email" class="form-control form-control-sm" placeholder="Email..."
                    style="background:#1e293b;border-color:#334155;color:#e2e8f0"
                    value="<?= htmlspecialchars($filters['email'] ?? '') ?>">
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-outline-primary btn-sm">🔍 Lọc</button></div>
        </form>

        <!-- Table -->
        <div class="card" style="background:#1e293b;border:1px solid #334155;border-radius:14px;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="color:#e2e8f0;font-size:.85rem;">
                        <thead style="border-bottom:1px solid #334155;">
                            <tr style="font-size:.72rem;color:#64748b;text-transform:uppercase;">
                                <th class="ps-3">Mã đơn</th>
                                <th>Gmail</th>
                                <th>Farm</th>
                                <th>Trạng thái</th>
                                <th>Hết hạn</th>
                                <th>Tạo lúc</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4" style="color:#475569">Không có đơn nào.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $o): ?>
                                    <?php
                                    $st = $o['status'] ?? 'pending';
                                    $stInfo = $statusMap[$st] ?? ['label' => $st, 'color' => '#94a3b8'];
                                    ?>
                                    <tr>
                                        <td class="ps-3"><code
                                                style="color:#38bdf8;font-size:.78rem"><?= htmlspecialchars($o['order_code']) ?></code>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($o['customer_email']) ?>
                                        </td>
                                        <td style="color:#94a3b8;font-size:.8rem">
                                            <?= htmlspecialchars($o['farm_name'] ?? '—') ?>
                                        </td>
                                        <td><span
                                                style="background:<?= $stInfo['color'] ?>22;color:<?= $stInfo['color'] ?>;border:1px solid <?= $stInfo['color'] ?>44;border-radius:5px;padding:2px 8px;font-size:.72rem;font-weight:700">
                                                <?= $stInfo['label'] ?>
                                            </span></td>
                                        <td style="color:#64748b;font-size:.78rem">
                                            <?= $o['expires_at'] ? date('d/m/Y', strtotime($o['expires_at'])) : '—' ?>
                                        </td>
                                        <td style="color:#64748b;font-size:.78rem">
                                            <?= $o['created_at'] ? date('d/m H:i', strtotime($o['created_at'])) : '—' ?>
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
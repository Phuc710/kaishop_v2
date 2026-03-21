<?php
$pageTitle = 'ChatGPT Farms';
require __DIR__ . '/../layout/head.php';
?>
<style>
    .cgpt-stat {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 12px;
        padding: 18px 20px;
    }

    .cgpt-stat h6 {
        font-size: .7rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .05em;
        margin: 0 0 4px;
    }

    .cgpt-stat .val {
        font-size: 1.8rem;
        font-weight: 800;
        color: #38bdf8;
        line-height: 1.1;
    }

    .seat-bar {
        height: 6px;
        background: #334155;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 6px;
    }

    .seat-fill {
        height: 100%;
        border-radius: 3px;
        transition: width .3s;
    }

    .badge-active {
        background: #064e3b22;
        color: #34d399;
        border: 1px solid #34d39933;
    }

    .badge-full {
        background: #78350f22;
        color: #fbbf24;
        border: 1px solid #fbbf2433;
    }

    .badge-locked {
        background: #3b082222;
        color: #f87171;
        border: 1px solid #f8717133;
    }
</style>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col">
                <h1 class="m-0" style="font-size:1.3rem">🤖 Quản lý Farm ChatGPT Pro</h1>
            </div>
            <div class="col-auto">
                <a href="<?= url('admin/chatgpt/farms/add') ?>" class="btn btn-primary btn-sm">+ Thêm Farm</a>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <!-- Stats row -->
        <div class="row g-3 mb-4">
            <?php $s = $stats ?? []; ?>
            <div class="col-6 col-md-3">
                <div class="cgpt-stat">
                    <h6>Tổng Farm</h6>
                    <div class="val">
                        <?= (int) ($s['total_farms'] ?? 0) ?>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="cgpt-stat">
                    <h6>Tổng Slot</h6>
                    <div class="val">
                        <?= (int) ($s['total_seats'] ?? 0) ?>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="cgpt-stat">
                    <h6>Đã dùng</h6>
                    <div class="val" style="color:#fbbf24">
                        <?= (int) ($s['used_seats'] ?? 0) ?>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="cgpt-stat">
                    <h6>Còn trống</h6>
                    <div class="val" style="color:#34d399">
                        <?= (int) ($s['available_seats'] ?? 0) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Farms table -->
        <div class="card" style="background:#1e293b;border:1px solid #334155;border-radius:14px;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="color:#e2e8f0;">
                        <thead style="border-bottom:1px solid #334155;">
                            <tr style="font-size:.75rem;color:#64748b;text-transform:uppercase;">
                                <th class="ps-3">Farm</th>
                                <th>Admin Email</th>
                                <th>Slot</th>
                                <th>Trạng thái</th>
                                <th>Last Sync</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($farms)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4" style="color:#475569;">Chưa có farm nào. <a
                                            href="<?= url('admin/chatgpt/farms/add') ?>">Thêm farm đầu tiên →</a></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($farms as $f): ?>
                                    <?php
                                    $used = (int) $f['seat_used'];
                                    $total = (int) $f['seat_total'];
                                    $pct = $total > 0 ? round(($used / $total) * 100) : 0;
                                    $color = $pct >= 100 ? '#ef4444' : ($pct >= 75 ? '#fbbf24' : '#34d399');
                                    $badgeClass = match ($f['status']) { 'full' => 'badge-full', 'locked' => 'badge-locked', default => 'badge-active'};
                                    ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div style="font-weight:600;color:#f1f5f9">
                                                <?= htmlspecialchars($f['farm_name']) ?>
                                            </div>
                                            <div style="font-size:.75rem;color:#475569">#
                                                <?= $f['id'] ?>
                                            </div>
                                        </td>
                                        <td style="font-size:.85rem">
                                            <?= htmlspecialchars($f['admin_email']) ?>
                                        </td>
                                        <td>
                                            <div style="font-size:.85rem;font-weight:600;color:#e2e8f0">
                                                <?= $used ?> /
                                                <?= $total ?>
                                            </div>
                                            <div class="seat-bar" style="width:80px">
                                                <div class="seat-fill" style="width:<?= $pct ?>%;background:<?= $color ?>">
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge <?= $badgeClass ?>"
                                                style="border-radius:6px;font-size:.75rem;padding:3px 8px">
                                                <?= ucfirst($f['status']) ?>
                                            </span></td>
                                        <td style="font-size:.78rem;color:#64748b">
                                            <?= $f['last_sync_at'] ? date('d/m H:i', strtotime($f['last_sync_at'])) : '—' ?>
                                        </td>
                                        <td>
                                            <a href="<?= url('admin/chatgpt/farms/edit/' . $f['id']) ?>"
                                                class="btn btn-outline-secondary btn-sm me-1">Sửa</a>
                                            <button onclick="syncFarm(<?= $f['id'] ?>, this)"
                                                class="btn btn-outline-info btn-sm">Sync</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick nav -->
        <div class="row g-3 mt-3">
            <div class="col-md-4">
                <a href="<?= url('admin/chatgpt/orders') ?>" class="card text-decoration-none"
                    style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:14px 18px;display:block">
                    <div style="font-weight:700;color:#f1f5f9">📦 Đơn hàng</div>
                    <div style="font-size:.82rem;color:#64748b">Quản lý orders ChatGPT</div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?= url('admin/chatgpt/members') ?>" class="card text-decoration-none"
                    style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:14px 18px;display:block">
                    <div style="font-weight:700;color:#f1f5f9">👥 Thành viên</div>
                    <div style="font-size:.82rem;color:#64748b">Snapshot member hiện tại</div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?= url('admin/chatgpt/logs') ?>" class="card text-decoration-none"
                    style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:14px 18px;display:block">
                    <div style="font-weight:700;color:#f1f5f9">📋 Audit Logs</div>
                    <div style="font-size:.82rem;color:#64748b">Lịch sử guard & invite</div>
                </a>
            </div>
        </div>

    </div>
</section>

<?php require __DIR__ . '/../layout/foot.php'; ?>
<script>
    function syncFarm(id, btn) {
        btn.disabled = true;
        btn.textContent = '⏳';
        fetch('<?= url('admin/chatgpt/farms/sync-now/') ?>' + id, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(d => {
                btn.textContent = d.success ? '✅' : '❌';
                if (d.success) setTimeout(() => location.reload(), 800);
            })
            .catch(() => { btn.textContent = '⚠️'; });
    }
</script>
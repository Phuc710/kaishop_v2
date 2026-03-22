<?php
$pageTitle = 'ChatGPT Invites';
require __DIR__ . '/../layout/head.php';
$invites = $invites ?? [];
$allowed = $allowed ?? [];
$farms = $farms ?? [];
$filters = $filters ?? [];

// Build allowed email set for cross-check display
$allowedEmails = array_column($allowed ?? [], 'target_email');
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col">
                <h1 class="m-0" style="font-size:1.3rem">📨 Invite Snapshot</h1>
            </div>
            <div class="col-auto">
                <a href="<?= url('admin/chatgpt/farms') ?>" class="btn btn-secondary btn-sm me-1">← Farms</a>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">

        <form method="get" class="row g-2 mb-3">
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
                <select name="source" class="form-select form-select-sm"
                    style="background:#1e293b;border-color:#334155;color:#e2e8f0">
                    <option value="">Tất cả</option>
                    <option value="approved" <?= ($filters['source'] ?? '') === 'approved' ? 'selected' : '' ?>>✅ Approved
                    </option>
                    <option value="detected_unknown" <?= ($filters['source'] ?? '') === 'detected_unknown' ? 'selected' : '' ?>
                        >⚠️ Unknown</option>
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-outline-primary btn-sm">Lọc</button></div>
        </form>

        <!-- Tab: snapshot vs allowed -->
        <ul class="nav nav-pills mb-3" id="inviteTab">
            <li class="nav-item"><a href="#" class="nav-link active" data-target="snapshot">Live Snapshot (
                    <?= count($invites) ?>)
                </a></li>
            <li class="nav-item"><a href="#" class="nav-link" data-target="allowed">Allowed Invites (
                    <?= count($allowed) ?>)
                </a></li>
        </ul>

        <div id="panelSnapshot">
            <div class="card" style="background:#1e293b;border:1px solid #334155;border-radius:14px;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="color:#e2e8f0;font-size:.85rem;">
                            <thead style="border-bottom:1px solid #334155;">
                                <tr style="font-size:.72rem;color:#64748b;text-transform:uppercase;">
                                    <th class="ps-3">Email</th>
                                    <th>Farm</th>
                                    <th>Status</th>
                                    <th>Nguồn</th>
                                    <th>Thấy lúc</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($invites)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4" style="color:#475569">Chưa có snapshot
                                            invite. Chạy cron sync trước.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($invites as $inv): ?>
                                        <?php $isApproved = ($inv['source'] ?? '') === 'approved'; ?>
                                        <tr>
                                            <td class="ps-3">
                                                <?= htmlspecialchars($inv['email'] ?? '—') ?>
                                            </td>
                                            <td style="color:#94a3b8;font-size:.78rem">
                                                <?= htmlspecialchars($inv['farm_name'] ?? '—') ?>
                                            </td>
                                            <td><span
                                                    style="font-size:.72rem;background:#0f172a;padding:2px 7px;border-radius:4px;color:#fbbf24">
                                                    <?= htmlspecialchars($inv['status'] ?? '—') ?>
                                                </span></td>
                                            <td>
                                                <?= $isApproved ? '<span style="color:#34d399;font-size:.8rem">✅</span>' : '<span style="color:#f87171;font-size:.8rem">⚠️ Unknown</span>' ?>
                                            </td>
                                            <td style="color:#64748b;font-size:.75rem">
                                                <?= $inv['last_seen_at'] ? date('d/m H:i', strtotime($inv['last_seen_at'])) : '—' ?>
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

        <div id="panelAllowed" style="display:none">
            <div class="card" style="background:#1e293b;border:1px solid #334155;border-radius:14px;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="color:#e2e8f0;font-size:.85rem;">
                            <thead style="border-bottom:1px solid #334155;">
                                <tr style="font-size:.72rem;color:#64748b;text-transform:uppercase;">
                                    <th class="ps-3">Email</th>
                                    <th>Farm</th>
                                    <th>Đơn hàng</th>
                                    <th>Status</th>
                                    <th>Tạo lúc</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allowed)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4" style="color:#475569">Chưa có allowed
                                            invites.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allowed as $a): ?>
                                        <?php
                                        $colors = ['accepted' => '#34d399', 'revoked' => '#ef4444', 'expired' => '#94a3b8'];
                                        $stColor = $colors[$a['status'] ?? ''] ?? '#fbbf24';
                                        ?>
                                        <tr>
                                            <td class="ps-3">
                                                <?= htmlspecialchars($a['target_email']) ?>
                                            </td>
                                            <td style="color:#94a3b8;font-size:.78rem">
                                                <?= htmlspecialchars($a['farm_name'] ?? '—') ?>
                                            </td>
                                            <td style="color:#38bdf8;font-size:.75rem;font-family:monospace">
                                                <?= htmlspecialchars($a['order_code'] ?? '—') ?>
                                            </td>
                                            <td><span
                                                    style="font-size:.72rem;color:<?= $stColor ?>;background:<?= $stColor ?>22;padding:2px 7px;border-radius:4px">
                                                    <?= ucfirst($a['status']) ?>
                                                </span></td>
                                            <td style="color:#64748b;font-size:.75rem">
                                                <?= $a['created_at'] ? date('d/m H:i', strtotime($a['created_at'])) : '—' ?>
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

    </div>
</section>
<?php require __DIR__ . '/../layout/foot.php'; ?>
<script>
    document.querySelectorAll('[data-target]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            document.querySelectorAll('[data-target]').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('panelSnapshot').style.display = btn.dataset.target === 'snapshot' ? '' : 'none';
            document.getElementById('panelAllowed').style.display = btn.dataset.target === 'allowed' ? '' : 'none';
        });
    });
</script>
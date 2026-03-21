<?php
$pageTitle = 'ChatGPT Members';
require __DIR__ . '/../layout/head.php';
$members = $members ?? [];
$farms = $farms ?? [];
$filters = $filters ?? [];
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col">
                <h1 class="m-0" style="font-size:1.3rem">👥 Thành viên Farm (Snapshot)</h1>
            </div>
            <div class="col-auto"><a href="<?= url('admin/chatgpt/farms') ?>" class="btn btn-secondary btn-sm">←
                    Farms</a></div>
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
                    <option value="">Tất cả nguồn</option>
                    <option value="approved" <?= ($filters['source'] ?? '') === 'approved' ? 'selected' : '' ?>>✅ Approved
                    </option>
                    <option value="detected_unknown" <?= ($filters['source'] ?? '') === 'detected_unknown' ? 'selected' : '' ?>
                        >⚠️ Unknown</option>
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-outline-primary btn-sm">Lọc</button></div>
        </form>

        <div class="card" style="background:#1e293b;border:1px solid #334155;border-radius:14px;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="color:#e2e8f0;font-size:.85rem;">
                        <thead style="border-bottom:1px solid #334155;">
                            <tr style="font-size:.72rem;color:#64748b;text-transform:uppercase;">
                                <th class="ps-3">Email</th>
                                <th>Farm</th>
                                <th>Role</th>
                                <th>Nguồn</th>
                                <th>Lần đầu</th>
                                <th>Lần cuối</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4" style="color:#475569">Chưa có snapshot nào.
                                        Chạy cron sync trước.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($members as $m): ?>
                                    <?php $isApproved = ($m['source'] ?? '') === 'approved'; ?>
                                    <tr>
                                        <td class="ps-3">
                                            <?= htmlspecialchars($m['email']) ?>
                                        </td>
                                        <td style="color:#94a3b8;font-size:.8rem">
                                            <?= htmlspecialchars($m['farm_name'] ?? '—') ?>
                                        </td>
                                        <td><span
                                                style="font-size:.72rem;background:#06406622;color:#38bdf8;padding:2px 7px;border-radius:4px">
                                                <?= htmlspecialchars($m['role'] ?? 'reader') ?>
                                            </span></td>
                                        <td>
                                            <?php if ($isApproved): ?>
                                                <span style="color:#34d399;font-size:.8rem">✅ Approved</span>
                                            <?php else: ?>
                                                <span style="color:#f87171;font-size:.8rem">⚠️ Unknown</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color:#64748b;font-size:.75rem">
                                            <?= $m['first_seen_at'] ? date('d/m H:i', strtotime($m['first_seen_at'])) : '—' ?>
                                        </td>
                                        <td style="color:#64748b;font-size:.75rem">
                                            <?= $m['last_seen_at'] ? date('d/m H:i', strtotime($m['last_seen_at'])) : '—' ?>
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
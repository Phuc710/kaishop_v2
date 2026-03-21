<?php
$pageTitle = 'ChatGPT Audit Logs';
require __DIR__ . '/../layout/head.php';
$logs = $logs ?? [];
$farms = $farms ?? [];
$actionTypes = $actionTypes ?? [];
$filters = $filters ?? [];

$actionColors = [
    'SYSTEM_INVITE_CREATED' => ['bg' => '#06406622', 'c' => '#38bdf8'],
    'SYSTEM_INVITE_FAILED' => ['bg' => '#78350f22', 'c' => '#f97316'],
    'INVITE_REVOKED_UNAUTHORIZED' => ['bg' => '#78350f22', 'c' => '#fbbf24'],
    'MEMBER_REMOVED_UNAUTHORIZED' => ['bg' => '#3b082222', 'c' => '#f87171'],
    'MEMBER_REMOVED_POLICY' => ['bg' => '#3b082222', 'c' => '#ef4444'],
    'ORDER_ACTIVATED' => ['bg' => '#06402822', 'c' => '#34d399'],
    'FARM_ADDED' => ['bg' => '#1e3a5f22', 'c' => '#60a5fa'],
];
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col">
                <h1 class="m-0" style="font-size:1.3rem">📋 Audit Logs</h1>
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
                <select name="action" class="form-select form-select-sm"
                    style="background:#1e293b;border-color:#334155;color:#e2e8f0">
                    <option value="">Tất cả action</option>
                    <?php foreach ($actionTypes as $at): ?>
                        <option value="<?= $at ?>" <?= ($filters['action'] ?? '') === $at ? 'selected' : '' ?>>
                            <?= $at ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <input type="text" name="target_email" class="form-control form-control-sm" placeholder="Email..."
                    style="background:#1e293b;border-color:#334155;color:#e2e8f0"
                    value="<?= htmlspecialchars($filters['target_email'] ?? '') ?>">
            </div>
            <div class="col-auto">
                <input type="date" name="date_from" class="form-control form-control-sm"
                    style="background:#1e293b;border-color:#334155;color:#e2e8f0"
                    value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-auto">
                <input type="date" name="date_to" class="form-control form-control-sm"
                    style="background:#1e293b;border-color:#334155;color:#e2e8f0"
                    value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-outline-primary btn-sm">🔍 Lọc</button></div>
        </form>

        <div class="card" style="background:#1e293b;border:1px solid #334155;border-radius:14px;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="color:#e2e8f0;font-size:.82rem;">
                        <thead style="border-bottom:1px solid #334155;">
                            <tr style="font-size:.7rem;color:#64748b;text-transform:uppercase;">
                                <th class="ps-3">Thời gian</th>
                                <th>Farm</th>
                                <th>Action</th>
                                <th>Actor</th>
                                <th>Target</th>
                                <th>Result</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4" style="color:#475569">Chưa có log nào.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php
                                    $action = $log['action'] ?? '';
                                    $ac = $actionColors[$action] ?? ['bg' => '#33415522', 'c' => '#94a3b8'];
                                    $resultColor = match ($log['result'] ?? 'OK') { 'OK' => '#34d399', 'FAIL' => '#f87171', default => '#94a3b8'};
                                    ?>
                                    <tr>
                                        <td class="ps-3" style="white-space:nowrap;color:#64748b">
                                            <?= $log['created_at'] ? date('d/m H:i:s', strtotime($log['created_at'])) : '—' ?>
                                        </td>
                                        <td style="color:#94a3b8;font-size:.75rem">
                                            <?= htmlspecialchars($log['farm_name'] ?? '—') ?>
                                        </td>
                                        <td><span
                                                style="background:<?= $ac['bg'] ?>;color:<?= $ac['c'] ?>;padding:2px 7px;border-radius:4px;font-size:.7rem;font-weight:700;white-space:nowrap">
                                                <?= htmlspecialchars($action) ?>
                                            </span></td>
                                        <td style="color:#94a3b8;font-size:.75rem">
                                            <?= htmlspecialchars($log['actor_email'] ?? '—') ?>
                                        </td>
                                        <td style="font-size:.8rem">
                                            <?= htmlspecialchars($log['target_email'] ?? '—') ?>
                                        </td>
                                        <td><span style="color:<?= $resultColor ?>;font-size:.75rem;font-weight:700">
                                                <?= htmlspecialchars($log['result'] ?? 'OK') ?>
                                            </span></td>
                                        <td style="color:#64748b;font-size:.75rem">
                                            <?= htmlspecialchars($log['reason'] ?? '—') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-muted mt-2" style="font-size:.75rem">
            Format log: <code style="color:#38bdf8">YYYY-MM-DD HH:MM:SS | FARM | ACTION | TARGET | RESULT</code>
        </div>

    </div>
</section>
<?php require __DIR__ . '/../layout/foot.php'; ?>
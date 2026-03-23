<?php
$pageTitle = 'Audit Logs';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/chatgpt/farms')],
    ['label' => 'Audit Logs'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$logs = $logs ?? [];
$farms = $farms ?? [];
$filters = $filters ?? [];
$summaryCards = $summaryCards ?? [];
$actionTypeOptions = $actionTypeOptions ?? [];
?>

<section class="content pb-4 mt-1 admin-chatgpt-page">
    <div class="container-fluid">
        <div class="row mb-3">
            <?php foreach ($summaryCards as $card): ?>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="gptb-stat-card gptb-stat-card--<?= htmlspecialchars($card['tone']) ?>">
                        <div class="gptb-stat-icon"><i class="<?= htmlspecialchars($card['icon']) ?>"></i></div>
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
                <h3 class="card-title">GPT BUSINESS AUDIT LOGS</h3>
            </div>

                        <div class="dt-filters">
                <form method="get" class="row align-items-end" id="filterForm">
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="gptb-filter-label">Target email</label>
                        <input type="text" name="target_email" class="form-control form-control-sm" placeholder="Email..." value="<?= htmlspecialchars($filters['target_email'] ?? '') ?>">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="gptb-filter-label">Loại tác vụ</label>
                        <select name="action" class="form-control form-control-sm">
                            <option value="">Tất cả</option>
                            <?php foreach ($actionTypeOptions as $val => $label): ?>
                                <option value="<?= htmlspecialchars($val) ?>" <?= ($filters['action'] ?? '') === $val ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="gptb-filter-label">Farm</label>
                        <select name="farm_id" class="form-control form-control-sm">
                            <option value="0">Tất cả</option>
                            <?php foreach ($farms as $farm): ?>
                                <option value="<?= (int) ($farm['id'] ?? 0) ?>" <?= (int) ($filters['farm_id'] ?? 0) === (int) ($farm['id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($farm['farm_name'] ?? '--') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="gptb-filter-label">Từ ngày</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="gptb-filter-label">Đến ngày</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <div class="gptb-filter-actions">
                            <a href="<?= url('admin/chatgpt/logs') ?>" class="btn btn-danger btn-sm w-100">
                                <i class="fas fa-trash-alt mr-1"></i> Xóa lọc
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-0">
                    <table class="table table-hover table-bordered admin-table gptb-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">Thời gian</th>
                                <th class="text-center">Farm</th>
                                <th class="text-center">Tác vụ</th>
                                <th class="text-center">Actor</th>
                                <th class="text-center">Target</th>
                                <th class="text-center">Kết quả</th>
                                <th class="text-center">Lý do</th>
                                <th class="text-center">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Chưa có audit log nào.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="text-center"><span class="date-badge"><?= htmlspecialchars($log['created_at_display'] ?? '--') ?></span></td>
                                        <td class="text-center"><span class="cat-badge"><?= htmlspecialchars($log['farm_name'] ?? '--') ?></span></td>
                                        <td class="text-center">
                                            <span class="<?= htmlspecialchars($log['action_badge_class'] ?? 'gptb-badge gptb-badge--muted') ?>">
                                                <?= htmlspecialchars($log['action_label'] ?? '--') ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><span class="gptb-email"><?= htmlspecialchars($log['actor_email'] ?? '--') ?></span></td>
                                        <td class="text-center"><span class="gptb-email"><?= htmlspecialchars($log['target_email'] ?? '--') ?></span></td>
                                        <td class="text-center">
                                            <span class="<?= htmlspecialchars($log['result_badge_class'] ?? 'gptb-badge gptb-badge--muted') ?>">
                                                <?= htmlspecialchars($log['result_label'] ?? '--') ?>
                                            </span>
                                        </td>
                                        <td class="text-left"><span class="gptb-subtext gptb-subtext--dark"><?= htmlspecialchars($log['reason'] ?? '--') ?></span></td>
                                        <td class="text-left"><span class="gptb-subtext gptb-subtext--dark"><?= htmlspecialchars($log['meta_display'] ?? '--') ?></span></td>
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
<script>
    (function () {
        function submitClean(form) {
            if (!form) {
                return;
            }

            var params = new URLSearchParams();
            Array.prototype.forEach.call(form.elements, function (field) {
                if (!field.name || field.disabled) {
                    return;
                }

                var value = (field.value || '').trim();
                if (value === '' || (field.name === 'farm_id' && value === '0')) {
                    return;
                }

                params.set(field.name, value);
            });

            var action = form.getAttribute('action') || window.location.pathname;
            window.location.href = params.toString() ? action + '?' + params.toString() : action;
        }

        var form = document.getElementById('filterForm');
        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                submitClean(form);
            });
            form.querySelectorAll('select, input').forEach(function (el) {
                el.addEventListener('change', function () { submitClean(form); });
            });
        }
    })();
</script>

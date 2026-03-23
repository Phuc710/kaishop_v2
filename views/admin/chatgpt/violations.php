<?php
$pageTitle = 'Violations';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/chatgpt/farms')],
    ['label' => 'Violations'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$violations = $violations ?? [];
$farms = $farms ?? [];
$filters = $filters ?? [];
$summaryCards = $summaryCards ?? [];
$violationTypeOptions = $violationTypeOptions ?? [];
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
                <h3 class="card-title">VIOLATIONS</h3>
            </div>

            <div class="dt-filters">
                <form method="get" class="row align-items-end" id="filterForm">
                    <div class="col-lg-4 col-md-6 mb-2">
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
                    <div class="col-lg-4 col-md-6 mb-2">
                        <label class="gptb-filter-label">Loại vi phạm</label>
                        <select name="type" class="form-control form-control-sm">
                            <option value="">Tất cả</option>
                            <?php foreach ($violationTypeOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= ($filters['type'] ?? '') === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-12 mb-2">
                        <label class="gptb-filter-label">Email</label>
                        <input type="text" name="email" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($filters['email'] ?? '') ?>" placeholder="Nhập email cần tìm...">
                    </div>
                </form>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-0">
                    <table class="table table-hover table-bordered admin-table gptb-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">Thời gian</th>
                                <th class="text-center">Farm</th>
                                <th class="text-center">Email</th>
                                <th class="text-center">Loại</th>
                                <th class="text-center">Mức độ</th>
                                <th class="text-center">Lý do</th>
                                <th class="text-center">Đã xử lý</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($violations)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Chưa có vi phạm nào được ghi nhận.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($violations as $violation): ?>
                                    <tr>
                                        <td class="text-center"><span class="date-badge"><?= htmlspecialchars($violation['created_at_display'] ?? '--') ?></span></td>
                                        <td class="text-center"><span class="cat-badge"><?= htmlspecialchars($violation['farm_name'] ?? '--') ?></span></td>
                                        <td class="text-center"><span class="gptb-email"><?= htmlspecialchars($violation['email'] ?? '--') ?></span></td>
                                        <td class="text-center"><span class="gptb-subtext gptb-subtext--dark"><?= htmlspecialchars($violation['type_label'] ?? '--') ?></span></td>
                                        <td class="text-center">
                                            <span class="<?= htmlspecialchars($violation['severity_badge_class'] ?? 'gptb-badge gptb-badge--muted') ?>">
                                                <?= htmlspecialchars($violation['severity_label'] ?? '--') ?>
                                            </span>
                                        </td>
                                        <td class="text-left"><span class="gptb-subtext gptb-subtext--dark"><?= htmlspecialchars($violation['reason'] ?? '--') ?></span></td>
                                        <td class="text-left"><span class="gptb-subtext gptb-subtext--dark"><?= htmlspecialchars($violation['action_taken'] ?? '--') ?></span></td>
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

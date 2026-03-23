<?php
$pageTitle = 'Thành viên Farm';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/chatgpt/farms')],
    ['label' => 'Thành viên Farm'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$members = $members ?? [];
$farms = $farms ?? [];
$filters = $filters ?? [];
$summaryCards = $summaryCards ?? [];
$memberSourceOptions = $memberSourceOptions ?? [];
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
                <h3 class="card-title">THÀNH VIÊN FARM</h3>
            </div>

                        <div class="dt-filters">
                <form method="get" class="row align-items-end" id="filterForm">
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="gptb-filter-label">Email khách</label>
                        <input type="text" name="email" class="form-control form-control-sm" placeholder="Tìm email..." value="<?= htmlspecialchars($filters['email'] ?? '') ?>">
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
                        <label class="gptb-filter-label">Nguồn</label>
                        <select name="source" class="form-control form-control-sm">
                            <option value="">Tất cả</option>
                            <?php foreach ($memberSourceOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= ($filters['source'] ?? '') === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
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
                            <a href="<?= url('admin/chatgpt/members') ?>" class="btn btn-danger btn-sm w-100">
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
                                <th class="text-center">Email</th>
                                <th class="text-center">Farm</th>
                                <th class="text-center">Vai trò</th>
                                <th class="text-center">Nguồn</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Lần đầu</th>
                                <th class="text-center">Lần cuối</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Chưa có snapshot thành viên nào.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td class="text-center"><span class="gptb-email"><?= htmlspecialchars($member['email'] ?? '--') ?></span></td>
                                        <td class="text-center"><span class="cat-badge"><?= htmlspecialchars($member['farm_name'] ?? '--') ?></span></td>
                                        <td class="text-center">
                                            <span class="<?= htmlspecialchars($member['role_badge_class'] ?? 'gptb-badge gptb-badge--primary') ?>">
                                                <?= htmlspecialchars($member['role_label'] ?? 'Thành viên') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="<?= htmlspecialchars($member['source_badge_class'] ?? 'gptb-badge gptb-badge--muted') ?>">
                                                <?= htmlspecialchars($member['source_label'] ?? '--') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="<?= htmlspecialchars($member['status_badge_class'] ?? 'gptb-badge gptb-badge--muted') ?>">
                                                <?= htmlspecialchars($member['status_label'] ?? '--') ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><span class="date-badge"><?= htmlspecialchars($member['first_seen_display'] ?? '--') ?></span></td>
                                        <td class="text-center"><span class="date-badge"><?= htmlspecialchars($member['last_seen_display'] ?? '--') ?></span></td>
                                        <td class="text-center">
                                            <?php if (($member['status'] ?? '') !== 'removed' && !in_array((string) ($member['role'] ?? ''), ['owner'], true)): ?>
                                                <button type="button" class="btn btn-danger btn-sm js-remove-member"
                                                    data-member-id="<?= (int) ($member['id'] ?? 0) ?>" title="Xóa khỏi farm">
                                                    <i class="fas fa-user-times"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">--</span>
                                            <?php endif; ?>
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

        document.addEventListener('click', function (event) {
            var button = event.target.closest('.js-remove-member');
            if (!button || button.disabled) {
                return;
            }

            var memberId = Number(button.getAttribute('data-member-id') || 0);
            if (!memberId) {
                return;
            }

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('<?= url('admin/chatgpt/members/remove/') ?>' + memberId, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data && data.success) {
                        window.location.reload();
                        return;
                    }

                    button.classList.remove('btn-danger');
                    button.classList.add('btn-warning');
                    button.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                })
                .catch(function () {
                    button.classList.remove('btn-danger');
                    button.classList.add('btn-warning');
                    button.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                })
                .finally(function () {
                    setTimeout(function () { button.disabled = false; }, 900);
                });
        });
    })();
</script>

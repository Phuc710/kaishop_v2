<?php
$pageTitle = 'Invites Farm';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/chatgpt/farms')],
    ['label' => 'Invites Farm'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$invites = $invites ?? [];
$allowed = $allowed ?? [];
$farms = $farms ?? [];
$filters = $filters ?? [];
?>

<section class="content pb-4 mt-1 admin-chatgpt-page">
    <div class="container-fluid">
        <div class="card custom-card mb-3">
            <div class="card-header gptb-card-header">
                <h3 class="card-title">INVITE SNAPSHOT</h3>
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
                        <label class="gptb-filter-label">Nguồn</label>
                        <select name="source" class="form-control form-control-sm">
                            <option value="">Tất cả</option>
                            <option value="approved" <?= ($filters['source'] ?? '') === 'approved' ? 'selected' : '' ?>>Đã duyệt</option>
                            <option value="detected_unknown" <?= ($filters['source'] ?? '') === 'detected_unknown' ? 'selected' : '' ?>>Lạ</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <label class="gptb-filter-label">Email</label>
                        <input type="text" name="email" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($filters['email'] ?? '') ?>" placeholder="Nhập email cần tìm...">
                    </div>
                    <div class="col-lg-1 col-md-12 mb-2">
                        <div class="gptb-filter-actions">
                            <a href="<?= url('admin/chatgpt/invites') ?>" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash-alt mr-1"></i> Xóa lọc
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body pt-3">
                <ul class="nav nav-pills mb-3" id="inviteTab">
                    <li class="nav-item"><a href="#" class="nav-link active" data-target="snapshot">Live Snapshot (<?= count($invites) ?>)</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-target="allowed">Allowed Invites (<?= count($allowed) ?>)</a></li>
                </ul>

                <div id="panelSnapshot">
                    <div class="table-responsive table-wrapper mb-0">
                        <table class="table table-hover table-bordered admin-table gptb-table mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">Email</th>
                                    <th class="text-center">Farm</th>
                                    <th class="text-center">Role</th>
                                    <th class="text-center">Trạng thái</th>
                                    <th class="text-center">Nguồn</th>
                                    <th class="text-center">Lần cuối</th>
                                    <th class="text-center">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($invites)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Chưa có snapshot invite. Hãy chạy đồng bộ farm trước.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($invites as $invite): ?>
                                        <tr>
                                            <td class="text-center"><span class="gptb-email"><?= htmlspecialchars($invite['email'] ?? '--') ?></span></td>
                                            <td class="text-center"><span class="cat-badge"><?= htmlspecialchars($invite['farm_name'] ?? '--') ?></span></td>
                                            <td class="text-center"><span class="gptb-subtext gptb-subtext--dark"><?= htmlspecialchars($invite['role'] ?? 'reader') ?></span></td>
                                            <td class="text-center"><span class="date-badge"><?= htmlspecialchars($invite['status'] ?? '--') ?></span></td>
                                            <td class="text-center">
                                                <span class="gptb-badge gptb-badge--<?= ($invite['source'] ?? '') === 'approved' ? 'success' : 'warning' ?>">
                                                    <?= ($invite['source'] ?? '') === 'approved' ? 'Đã duyệt' : 'Lạ' ?>
                                                </span>
                                            </td>
                                            <td class="text-center"><span class="date-badge"><?= !empty($invite['last_seen_at']) ? date('d/m/Y H:i', strtotime($invite['last_seen_at'])) : '--' ?></span></td>
                                            <td class="text-center">
                                                <?php if (($invite['status'] ?? '') === 'pending'): ?>
                                                    <button type="button" class="btn btn-danger btn-sm js-revoke-invite"
                                                        data-invite-id="<?= (int) ($invite['id'] ?? 0) ?>" title="Thu hồi invite">
                                                        <i class="fas fa-ban"></i>
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

                <div id="panelAllowed" style="display:none">
                    <div class="table-responsive table-wrapper mb-0">
                        <table class="table table-hover table-bordered admin-table gptb-table mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">Email</th>
                                    <th class="text-center">Farm</th>
                                    <th class="text-center">Đơn hàng</th>
                                    <th class="text-center">Trạng thái</th>
                                    <th class="text-center">Tạo lúc</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allowed)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Chưa có allowed invite nào.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allowed as $row): ?>
                                        <tr>
                                            <td class="text-center"><span class="gptb-email"><?= htmlspecialchars($row['target_email'] ?? '--') ?></span></td>
                                            <td class="text-center"><span class="cat-badge"><?= htmlspecialchars($row['farm_name'] ?? '--') ?></span></td>
                                            <td class="text-center"><span class="gptb-code"><?= htmlspecialchars($row['order_code'] ?? '--') ?></span></td>
                                            <td class="text-center"><span class="date-badge"><?= htmlspecialchars($row['status'] ?? '--') ?></span></td>
                                            <td class="text-center"><span class="date-badge"><?= !empty($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '--' ?></span></td>
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

        document.querySelectorAll('[data-target]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                document.querySelectorAll('[data-target]').forEach(function (item) { item.classList.remove('active'); });
                button.classList.add('active');
                document.getElementById('panelSnapshot').style.display = button.dataset.target === 'snapshot' ? '' : 'none';
                document.getElementById('panelAllowed').style.display = button.dataset.target === 'allowed' ? '' : 'none';
            });
        });

        document.addEventListener('click', function (event) {
            var button = event.target.closest('.js-revoke-invite');
            if (!button || button.disabled) {
                return;
            }

            var inviteId = Number(button.getAttribute('data-invite-id') || 0);
            if (!inviteId) {
                return;
            }

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('<?= url('admin/chatgpt/invites/revoke/') ?>' + inviteId, {
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

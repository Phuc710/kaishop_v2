<?php
$pageTitle = 'Quản lý Farm GPT';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/chatgpt/farms')],
    ['label' => 'Quản lý Farm'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$summaryCards = $summaryCards ?? [];
$farms = $farms ?? [];
$quickLinks = $quickLinks ?? [];
?>

<section class="content pb-4 mt-1 admin-chatgpt-page">
    <div class="container-fluid">
        <div class="row mb-3">
            <?php foreach ($summaryCards as $card): ?>
                <div class="col-xl-3 col-md-6 mb-3">
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
                <h3 class="card-title">QUẢN LÝ FARM</h3>
                <div class="gptb-card-actions">
                    <a href="<?= url('admin/chatgpt/farms/add') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus-circle mr-1"></i> Thêm farm
                    </a>
                </div>
            </div>
            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-0">
                    <table class="table table-hover table-bordered admin-table gptb-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-left">Farm</th>
                                <th class="text-center">Admin Email</th>
                                <th class="text-center">Slot</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Last Sync</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($farms)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Chưa có farm nào.
                                        <a href="<?= url('admin/chatgpt/farms/add') ?>" class="font-weight-bold">Tạo farm đầu tiên</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($farms as $farm): ?>
                                    <tr>
                                        <td class="text-left">
                                            <div class="font-weight-bold text-dark"><?= htmlspecialchars($farm['farm_name'] ?? '--') ?></div>
                                            <div class="gptb-subtext">#<?= (int) ($farm['id'] ?? 0) ?></div>
                                        </td>
                                        <td class="text-center">
                                            <span class="gptb-email"><?= htmlspecialchars($farm['admin_email'] ?? '--') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="font-weight-bold text-dark"><?= htmlspecialchars($farm['seat_summary'] ?? '0 / 0') ?></div>
                                            <div class="gptb-seat-meter mx-auto">
                                                <span class="<?= htmlspecialchars($farm['seat_fill_class'] ?? 'gptb-seat-fill') ?>"
                                                    style="width: <?= max(0, min(100, (int) ($farm['seat_percent'] ?? 0))) ?>%;"></span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="<?= htmlspecialchars($farm['status_badge_class'] ?? 'gptb-badge gptb-badge--muted') ?>">
                                                <?= htmlspecialchars($farm['status_label'] ?? '--') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="date-badge"><?= htmlspecialchars($farm['last_sync_display'] ?? '--') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="<?= url('admin/chatgpt/farms/edit/' . (int) ($farm['id'] ?? 0)) ?>"
                                                    class="btn btn-search-dt btn-sm" title="Sửa farm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-info btn-sm js-sync-farm"
                                                    data-farm-id="<?= (int) ($farm['id'] ?? 0) ?>" title="Sync ngay">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row mt-1">
            <?php foreach ($quickLinks as $link): ?>
                <div class="col-lg-4 mb-3">
                    <a href="<?= htmlspecialchars($link['href']) ?>" class="gptb-link-card text-decoration-none">
                        <div class="gptb-link-icon">
                            <i class="<?= htmlspecialchars($link['icon']) ?>"></i>
                        </div>
                        <div class="gptb-link-body">
                            <div class="gptb-link-title"><?= htmlspecialchars($link['label']) ?></div>
                            <div class="gptb-link-text"><?= htmlspecialchars($link['description']) ?></div>
                            <div class="gptb-link-meta"><?= htmlspecialchars($link['meta']) ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/foot.php'; ?>
<script>
    (function () {
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.js-sync-farm');
            if (!button) {
                return;
            }

            var farmId = Number(button.getAttribute('data-farm-id') || 0);
            if (!farmId || button.disabled) {
                return;
            }

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('<?= url('admin/chatgpt/farms/sync-now/') ?>' + farmId, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (data && data.success) {
                        button.classList.remove('btn-info');
                        button.classList.add('btn-success');
                        button.innerHTML = '<i class="fas fa-check"></i>';
                        setTimeout(function () {
                            window.location.reload();
                        }, 700);
                        return;
                    }

                    button.classList.remove('btn-info');
                    button.classList.add('btn-danger');
                    button.innerHTML = '<i class="fas fa-times"></i>';
                })
                .catch(function () {
                    button.classList.remove('btn-info');
                    button.classList.add('btn-danger');
                    button.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                })
                .finally(function () {
                    setTimeout(function () {
                        button.disabled = false;
                    }, 900);
                });
        });
    })();
</script>

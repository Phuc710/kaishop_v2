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
                <h3 class="card-title">THÀNH VIÊN FARM</h3>
                <div class="gptb-card-actions">
                    <a href="<?= url('admin/chatgpt/farms') ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Về farms
                    </a>
                </div>
            </div>

            <div class="dt-filters">
                <form method="get" class="row align-items-end">
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
                            <option value="">Tất cả nguồn</option>
                            <?php foreach ($memberSourceOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= ($filters['source'] ?? '') === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-12 mb-2">
                        <div class="gptb-filter-actions">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search mr-1"></i> Lọc
                            </button>
                            <a href="<?= url('admin/chatgpt/members') ?>" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash-alt mr-1"></i> Xóa lọc
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-0">
                    <table class="table table-hover table-bordered admin-table gptb-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">Email</th>
                                <th class="text-center">Farm</th>
                                <th class="text-center">Role</th>
                                <th class="text-center">Nguồn</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Lần đầu</th>
                                <th class="text-center">Lần cuối</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Chưa có snapshot thành viên nào.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td class="text-center">
                                            <span class="gptb-email"><?= htmlspecialchars($member['email'] ?? '--') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="cat-badge"><?= htmlspecialchars($member['farm_name'] ?? '--') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="<?= htmlspecialchars($member['role_badge_class'] ?? 'gptb-badge gptb-badge--primary') ?>">
                                                <?= htmlspecialchars($member['role'] ?? 'reader') ?>
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
                                        <td class="text-center">
                                            <span class="date-badge"><?= htmlspecialchars($member['first_seen_display'] ?? '--') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="date-badge"><?= htmlspecialchars($member['last_seen_display'] ?? '--') ?></span>
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

<?php
$pageTitle = 'Vi phạm GPT';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/gpt-business/farms')],
    ['label' => 'Vi phạm GPT'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$violations = $violations ?? [];
$farms = $farms ?? [];
$filters = $filters ?? [];
$summaryCards = $summaryCards ?? [];
$violationTypeOptions = $violationTypeOptions ?? [];
?>

<style>
    .admin-chatgpt-page .content-header {
        display: none;
    }

    .gptb-card-header {
        background: #fff !important;
        color: #212529 !important;
        border-bottom: 1px solid #ebedf2 !important;
        padding: 15px 20px !important;
    }

    .gptb-title-with-bar {
        border-left: 4px solid #6610f2;
        padding-left: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .gptb-orders-card-body {
        padding: 20px 24px 24px !important;
    }

    .gptb-orders-table-shell {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        overflow: hidden;
        background: #fff;
    }

    .gptb-orders-table {
        margin-bottom: 0 !important;
    }

    .gptb-orders-table thead th {
        background: #f8fafc !important;
        color: #475569 !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        font-size: 13px !important;
        text-align: center !important;
        vertical-align: middle !important;
        border-top: 0 !important;
        border-left: 0 !important;
        border-right: 0 !important;
        border-bottom: 1px solid #dbe4f0 !important;
        padding: 18px 14px !important;
        letter-spacing: 0.02em;
    }

    .gptb-orders-table tbody td {
        vertical-align: middle !important;
        color: #1e293b !important;
        font-size: 14px !important;
        border-left: 0 !important;
        border-right: 0 !important;
        border-bottom: 1px solid #edf2f7 !important;
        padding: 16px 14px !important;
        background: #fff;
    }

    .gptb-orders-table tbody tr:last-child td {
        border-bottom: 0 !important;
    }

    .gptb-orders-table tbody tr:hover td {
        background: #fafbff;
    }

    .gptb-orders-empty {
        padding: 56px 20px !important;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
    }

    .gptb-orders-empty-box {
        max-width: 420px;
        margin: 0 auto;
    }

    .gptb-orders-empty-icon {
        width: 62px;
        height: 62px;
        margin: 0 auto 14px;
        border-radius: 18px;
        background: #eef2ff;
        color: #6366f1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .gptb-orders-empty-title {
        font-size: 19px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 6px;
    }

    .gptb-orders-empty-text {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 0;
    }

    .gptb-orders-table-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 14px 4px 0;
        color: #64748b;
        font-size: 14px;
    }

    @media (max-width: 767.98px) {
        .gptb-orders-card-body {
            padding: 16px !important;
        }

        .gptb-orders-table-footer {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

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
                <span class="gptb-title-with-bar">QUẢN LÝ VI PHẠM GPT BUSINESS</span>
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
                            value="<?= htmlspecialchars($filters['email'] ?? '') ?>"
                            placeholder="Nhập email cần tìm...">
                    </div>
                </form>
            </div>

            <div class="card-body gptb-orders-card-body">
                <div class="gptb-orders-table-shell">
                    <div class="table-responsive table-wrapper mb-0">
                        <table class="table table-hover admin-table gptb-table gptb-orders-table mb-0">
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
                                        <td colspan="7" class="text-center gptb-orders-empty">
                                            <div class="gptb-orders-empty-box">
                                                <div class="gptb-orders-empty-icon"><i class="fas fa-user-shield"></i></div>
                                                <div class="gptb-orders-empty-title">Không tìm thấy dữ liệu</div>
                                                <p class="gptb-orders-empty-text">Chúc mừng! Không có vi phạm nào được ghi
                                                    nhận hệ thống.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($violations as $violation): ?>
                                        <tr>
                                            <td class="text-center"><span
                                                    class="date-badge"><?= htmlspecialchars($violation['created_at_display'] ?? '--') ?></span>
                                            </td>
                                            <td class="text-center"><span
                                                    class="cat-badge"><?= htmlspecialchars($violation['farm_name'] ?? '--') ?></span>
                                            </td>
                                            <td class="text-center"><span
                                                    class="gptb-email"><?= htmlspecialchars($violation['email'] ?? '--') ?></span>
                                            </td>
                                            <td class="text-center"><span
                                                    class="gptb-subtext gptb-subtext--dark"><?= htmlspecialchars($violation['type_label'] ?? '--') ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="<?= htmlspecialchars($violation['severity_badge_class'] ?? 'gptb-badge gptb-badge--muted') ?>">
                                                    <?= htmlspecialchars($violation['severity_label'] ?? '--') ?>
                                                </span>
                                            </td>
                                            <td class="text-left"><span
                                                    class="gptb-subtext gptb-subtext--dark"><?= htmlspecialchars($violation['reason'] ?? '--') ?></span>
                                            </td>
                                            <td class="text-left"><span
                                                    class="gptb-subtext gptb-subtext--dark"><?= htmlspecialchars($violation['action_taken'] ?? '--') ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="gptb-orders-table-footer">
                    <span>Dữ liệu vi phạm GPT Business</span>
                    <span>Hệ thống giám sát bảo mật tự động</span>
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
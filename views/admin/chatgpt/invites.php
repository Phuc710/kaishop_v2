<?php
$pageTitle = 'Lời mời Farm';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/gpt-business/farms')],
    ['label' => 'Lời mời Farm'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$invites = $invites ?? [];
$allowed = $allowed ?? [];
$farms = $farms ?? [];
$filters = $filters ?? [];
$gptTimeService = class_exists('TimeService') ? TimeService::instance() : null;
$formatGptTime = static function ($value, $format = 'd/m/Y H:i') use ($gptTimeService) {
    $raw = trim((string) ($value ?? ''));
    if ($raw === '' || $raw === '0000-00-00 00:00:00') {
        return '--';
    }

    if ($gptTimeService) {
        $formatted = $gptTimeService->formatDisplay($raw, $format, $gptTimeService->getDbTimezone());
        if ($formatted !== '') {
            return $formatted;
        }
    }

    return $raw;
};
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
        <div class="card custom-card mb-3">
            <div class="card-header gptb-card-header">
                <span class="gptb-title-with-bar">QUẢN LÝ LỜI MỜI FARM</span>
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
                            <option value="approved" <?= ($filters['source'] ?? '') === 'approved' ? 'selected' : '' ?>>Đã
                                duyệt</option>
                            <option value="detected_unknown" <?= ($filters['source'] ?? '') === 'detected_unknown' ? 'selected' : '' ?>>Lạ</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <label class="gptb-filter-label">Email</label>
                        <input type="text" name="email" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($filters['email'] ?? '') ?>"
                            placeholder="Nhập email cần tìm...">
                    </div>
                    <div class="col-lg-1 col-md-12 mb-2">
                        <div class="gptb-filter-actions">
                            <a href="<?= url('admin/gpt-business/invites') ?>" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash-alt mr-1"></i> Xóa lọc
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body gptb-orders-card-body">
                <ul class="nav nav-pills mb-4" id="inviteTab">
                    <li class="nav-item"><a href="#" class="nav-link active" data-target="snapshot">Live Snapshot
                            (<?= count($invites) ?>)</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-target="allowed">Allowed Invites
                            (<?= count($allowed) ?>)</a></li>
                </ul>

                <div id="panelSnapshot">
                    <div class="gptb-orders-table-shell">
                        <div class="table-responsive table-wrapper mb-0">
                            <table class="table table-hover admin-table gptb-table gptb-orders-table mb-0">
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
                                            <td colspan="7" class="text-center gptb-orders-empty">
                                                <div class="gptb-orders-empty-box">
                                                    <div class="gptb-orders-empty-icon"><i
                                                            class="fas fa-satellite-dish"></i></div>
                                                    <div class="gptb-orders-empty-title">Không tìm thấy dữ liệu</div>
                                                    <p class="gptb-orders-empty-text">Hãy chạy đồng bộ farm để cập nhật
                                                        trạng thái mới nhất.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($invites as $invite): ?>
                                            <tr>
                                                <td class="text-center"><span
                                                        class="gptb-email"><?= htmlspecialchars($invite['email'] ?? '--') ?></span>
                                                </td>
                                                <td class="text-center"><span
                                                        class="cat-badge"><?= htmlspecialchars($invite['farm_name'] ?? '--') ?></span>
                                                </td>
                                                <td class="text-center"><span
                                                        class="gptb-subtext gptb-subtext--dark"><?= htmlspecialchars($invite['role'] ?? 'reader') ?></span>
                                                </td>
                                                <td class="text-center"><span
                                                        class="date-badge"><?= htmlspecialchars($invite['status'] ?? '--') ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="gptb-badge gptb-badge--<?= ($invite['source'] ?? '') === 'approved' ? 'success' : 'warning' ?>">
                                                        <?= ($invite['source'] ?? '') === 'approved' ? 'Đã duyệt' : 'Lạ' ?>
                                                    </span>
                                                </td>
                                                <td class="text-center"><span
                                                        class="date-badge"><?= htmlspecialchars($formatGptTime($invite['last_seen_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if (($invite['status'] ?? '') === 'pending'): ?>
                                                        <button type="button" class="btn btn-danger btn-sm js-revoke-invite"
                                                            data-invite-id="<?= (int) ($invite['id'] ?? 0) ?>"
                                                            title="Thu hồi invite">
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
                </div>

                <div id="panelAllowed" style="display:none">
                    <div class="gptb-orders-table-shell">
                        <div class="table-responsive table-wrapper mb-0">
                            <table class="table table-hover admin-table gptb-table gptb-orders-table mb-0">
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
                                            <td colspan="5" class="text-center gptb-orders-empty">
                                                <div class="gptb-orders-empty-box">
                                                    <div class="gptb-orders-empty-icon"><i class="fas fa-check-double"></i>
                                                    </div>
                                                    <div class="gptb-orders-empty-title">Không tìm thấy dữ liệu</div>
                                                    <p class="gptb-orders-empty-text">Chưa có danh sách invite được phê
                                                        duyệt nào phù hợp.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($allowed as $row): ?>
                                            <tr>
                                                <td class="text-center"><span
                                                        class="gptb-email"><?= htmlspecialchars($row['target_email'] ?? '--') ?></span>
                                                </td>
                                                <td class="text-center"><span
                                                        class="cat-badge"><?= htmlspecialchars($row['farm_name'] ?? '--') ?></span>
                                                </td>
                                                <td class="text-center"><span
                                                        class="gptb-code"><?= htmlspecialchars($row['order_code'] ?? '--') ?></span>
                                                </td>
                                                <td class="text-center"><span
                                                        class="date-badge"><?= htmlspecialchars($row['status'] ?? '--') ?></span>
                                                </td>
                                                <td class="text-center"><span
                                                        class="date-badge"><?= htmlspecialchars($formatGptTime($row['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="gptb-orders-table-footer">
                    <span>Dữ liệu Invite Snapshot GPT Business</span>
                    <span>Hệ thống tự động đồng bộ theo chu kỳ</span>
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

            fetch('<?= url('admin/gpt-business/invites/revoke/') ?>' + inviteId, {
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

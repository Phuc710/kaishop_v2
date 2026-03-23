<?php
$pageTitle = 'Quản lý Farm GPT';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/gpt-business/farms')],
    ['label' => 'Quản lý Farm'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$summaryCards = $summaryCards ?? [];
$farms = $farms ?? [];
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

    .btn-gptb-add {
        background-color: #ffc107 !important;
        color: #000 !important;
        border: none !important;
        font-weight: 800 !important;
        border-radius: 6px !important;
    }

    .btn-gptb-add:hover {
        background-color: #e0a800 !important;
        color: #000 !important;
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
                <div class="row align-items-center w-100 mx-0">
                    <div class="col-md-6 col-12 px-0">
                        <span class="gptb-title-with-bar">QUẢN LÝ FARM</span>
                    </div>
                    <div class="col-md-6 col-12 px-0 text-md-right text-left mt-2 mt-md-0">
                        <a href="<?= url('admin/gpt-business/farms/add') ?>"
                            class="btn btn-warning btn-sm btn-gptb-add">
                            <i class="fas fa-plus-circle mr-1"></i> THÊM FARM
                        </a>
                    </div>
                </div>

                <div class="card-body gptb-orders-card-body">
                    <div class="gptb-orders-table-shell">
                        <div class="table-responsive table-wrapper mb-0">
                            <table class="table table-hover admin-table gptb-table gptb-orders-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center">FARM</th>
                                        <th class="text-center">EMAIL QUẢN TRỊ</th>
                                        <th class="text-center">SLOT</th>
                                        <th class="text-center">TRẠNG THÁI</th>
                                        <th class="text-center">ĐỒNG BỘ CUỐI</th>
                                        <th class="text-center">HÀNH ĐỘNG</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($farms)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center gptb-orders-empty">
                                                <div class="gptb-orders-empty-box">
                                                    <div class="gptb-orders-empty-icon"><i class="fas fa-tractor"></i></div>
                                                    <div class="gptb-orders-empty-title">Không tìm thấy dữ liệu</div>
                                                    <p class="gptb-orders-empty-text">Bạn chưa có Farm nào. <a
                                                            href="<?= url('admin/gpt-business/farms/add') ?>"
                                                            class="font-weight-bold">Tạo farm đầu tiên</a> để bắt đầu.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($farms as $farm): ?>
                                            <tr>
                                                <td class="text-left">
                                                    <div class="font-weight-bold text-dark">
                                                        <?= htmlspecialchars($farm['farm_name'] ?? '--') ?>
                                                    </div>
                                                    <div class="gptb-subtext">#<?= (int) ($farm['id'] ?? 0) ?></div>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="gptb-email"><?= htmlspecialchars($farm['admin_email'] ?? '--') ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="font-weight-bold text-dark">
                                                        <?= htmlspecialchars($farm['seat_summary'] ?? '0 / 0') ?>
                                                    </div>
                                                    <div class="gptb-seat-meter mx-auto">
                                                        <span
                                                            class="<?= htmlspecialchars($farm['seat_fill_class'] ?? 'gptb-seat-fill') ?>"
                                                            style="width: <?= max(0, min(100, (int) ($farm['seat_percent'] ?? 0))) ?>%;"></span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="<?= htmlspecialchars($farm['status_badge_class'] ?? 'gptb-badge gptb-badge--muted') ?>">
                                                        <?= htmlspecialchars($farm['status_label'] ?? '--') ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="date-badge"><?= htmlspecialchars($farm['last_sync_display'] ?? '--') ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <a href="<?= url('admin/gpt-business/farms/edit/' . (int) ($farm['id'] ?? 0)) ?>"
                                                            class="btn btn-search-dt btn-sm" title="Sửa farm">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-info btn-sm js-sync-farm"
                                                            data-farm-id="<?= (int) ($farm['id'] ?? 0) ?>"
                                                            title="Đồng bộ đầy đủ ngay">
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

                    <div class="gptb-orders-table-footer">
                        <span>Tổng số <strong><?= count($farms) ?></strong> farm đang được quản lý</span>
                        <span>Tự động giám sát trạng thái Slot & Member</span>
                    </div>
                </div>
            </div>
        </div>
</section>

<?php require __DIR__ . '/../layout/foot.php'; ?>
<script>
    (function () {
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.js-sync-farm');
            if (!button || button.disabled) {
                return;
            }

            var farmId = Number(button.getAttribute('data-farm-id') || 0);
            if (!farmId) {
                return;
            }

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('<?= url('admin/gpt-business/farms/sync-now/') ?>' + farmId, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data && data.success) {
                        button.classList.remove('btn-info');
                        button.classList.add('btn-success');
                        button.innerHTML = '<i class="fas fa-check"></i>';
                        setTimeout(function () { window.location.reload(); }, 700);
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
                    setTimeout(function () { button.disabled = false; }, 900);
                });
        });
    })();
</script>
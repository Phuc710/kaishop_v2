<?php
$pageTitle = 'Đơn hàng GPT';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/gpt-business/farms')],
    ['label' => 'Đơn hàng GPT'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$orders = $orders ?? [];
$farms = $farms ?? [];
$filters = $filters ?? [];
$summaryCards = $summaryCards ?? [];
$orderStatusOptions = $orderStatusOptions ?? [];
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
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
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

    .gptb-orders-table-footer strong {
        color: #0f172a;
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
                <div class="col-xl col-md-6 mb-3">
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
                        <span class="gptb-title-with-bar">ĐƠN HÀNG GPT BUSINESS</span>
                    </div>
                    <div class="col-md-6 col-12 px-0 text-md-right text-left mt-2 mt-md-0">
                        <a href="<?= url('admin/gpt-business/orders/add') ?>"
                            class="btn btn-warning btn-sm btn-gptb-add">
                            <i class="fas fa-plus-circle mr-1"></i> Add Farm
                        </a>
                    </div>
                </div>
            </div>

            <div class="dt-filters">
                <form method="get" class="row align-items-end" id="filterForm">
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="gptb-filter-label">Email khách</label>
                        <input type="text" name="email" class="form-control form-control-sm" placeholder="Tìm email..."
                            value="<?= htmlspecialchars($filters['email'] ?? '') ?>">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="gptb-filter-label">Trạng thái</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="">Tất cả</option>
                            <?php foreach ($orderStatusOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>>
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
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="gptb-filter-label">Đến ngày</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <div class="gptb-filter-actions">
                            <a href="<?= url('admin/gpt-business/orders') ?>" class="btn btn-danger btn-sm w-100">
                                <i class="fas fa-trash-alt mr-1"></i> Xóa lọc
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body gptb-orders-card-body">
                <div class="gptb-orders-table-shell">
                    <div class="table-responsive table-wrapper mb-0">
                        <table class="table table-hover admin-table gptb-table gptb-orders-table mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">MÃ ĐƠN</th>
                                    <th class="text-center">EMAIL KHÁCH</th>
                                    <th class="text-center">FARM</th>
                                    <th class="text-center">TRẠNG THÁI</th>
                                    <th class="text-center">HẾT HẠN</th>
                                    <th class="text-center">TẠO LÚC</th>
                                    <th class="text-center">GHI CHÚ</th>
                                    <th class="text-center">HÀNH ĐỘNG</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center gptb-orders-empty">
                                            <div class="gptb-orders-empty-box">
                                                <div class="gptb-orders-empty-icon">
                                                    <i class="fas fa-inbox"></i>
                                                </div>
                                                <div class="gptb-orders-empty-title">Không tìm thấy dữ liệu</div>
                                                <p class="gptb-orders-empty-text">Thử thay đổi bộ lọc hoặc thêm đơn hàng mới
                                                    để bắt đầu.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td class="text-center">
                                                <span
                                                    class="gptb-code"><?= htmlspecialchars($order['order_code'] ?? '--') ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="gptb-email"><?= htmlspecialchars($order['customer_email'] ?? '--') ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="cat-badge"><?= htmlspecialchars($order['farm_display'] ?? '--') ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="<?= htmlspecialchars($order['status_badge_class'] ?? 'gptb-badge gptb-badge--muted') ?>">
                                                    <?= htmlspecialchars($order['status_label'] ?? '--') ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="date-badge"><?= htmlspecialchars($order['expires_at_display'] ?? '--') ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="date-badge"><?= htmlspecialchars($order['created_at_display'] ?? '--') ?></span>
                                            </td>
                                            <td class="text-left">
                                                <span
                                                    class="gptb-subtext gptb-subtext--dark"><?= $order['note_display'] ?? '--' ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if (in_array((string) ($order['status'] ?? ''), ['failed', 'revoked'], true)): ?>
                                                    <button type="button" class="btn btn-info btn-sm js-retry-order"
                                                        data-order-id="<?= (int) ($order['id'] ?? 0) ?>" title="Gửi lại invite">
                                                        <i class="fas fa-paper-plane"></i>
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

                <div class="gptb-orders-table-footer">
                    <span>Hiển thị <strong><?= count($orders) ?></strong> đơn trong danh sách hiện tại</span>
                    <span>Bảng dữ liệu đơn hàng GPT Business</span>
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
            var button = event.target.closest('.js-retry-order');
            if (!button || button.disabled) {
                return;
            }

            var orderId = Number(button.getAttribute('data-order-id') || 0);
            if (!orderId) {
                return;
            }

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('<?= url('admin/gpt-business/orders/retry-invite/') ?>' + orderId, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data && data.success) {
                        window.location.reload();
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
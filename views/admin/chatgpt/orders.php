<?php
$pageTitle = 'Đơn hàng GPT';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/chatgpt/farms')],
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
                <h3 class="card-title">ĐƠN HÀNG GPT BUSINESS</h3>
            </div>

                        <div class="dt-filters">
                <form method="get" class="row align-items-end" id="filterForm">
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="gptb-filter-label">Email khách</label>
                        <input type="text" name="email" class="form-control form-control-sm" placeholder="Tìm email..." value="<?= htmlspecialchars($filters['email'] ?? '') ?>">
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
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="gptb-filter-label">Đến ngày</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <div class="gptb-filter-actions">
                            <a href="<?= url('admin/chatgpt/orders') ?>" class="btn btn-danger btn-sm w-100">
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
                                <th class="text-center">Mã đơn</th>
                                <th class="text-center">Email khách</th>
                                <th class="text-center">Farm</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Hết hạn</th>
                                <th class="text-center">Tạo lúc</th>
                                <th class="text-center">Ghi chú</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Không có đơn hàng nào phù hợp bộ lọc.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="text-center"><span class="gptb-code"><?= htmlspecialchars($order['order_code'] ?? '--') ?></span></td>
                                        <td class="text-center"><span class="gptb-email"><?= htmlspecialchars($order['customer_email'] ?? '--') ?></span></td>
                                        <td class="text-center"><span class="cat-badge"><?= htmlspecialchars($order['farm_display'] ?? '--') ?></span></td>
                                        <td class="text-center">
                                            <span class="<?= htmlspecialchars($order['status_badge_class'] ?? 'gptb-badge gptb-badge--muted') ?>">
                                                <?= htmlspecialchars($order['status_label'] ?? '--') ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><span class="date-badge"><?= htmlspecialchars($order['expires_at_display'] ?? '--') ?></span></td>
                                        <td class="text-center"><span class="date-badge"><?= htmlspecialchars($order['created_at_display'] ?? '--') ?></span></td>
                                        <td class="text-left"><span class="gptb-subtext gptb-subtext--dark"><?= htmlspecialchars($order['note_display'] ?? '--') ?></span></td>
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

            fetch('<?= url('admin/chatgpt/orders/retry-invite/') ?>' + orderId, {
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

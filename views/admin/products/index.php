<?php

/**
 * View: Danh sach san pham
 * Route: GET /admin/products
 * Controller: AdminProductController@index
 */
$pageTitle = 'Danh sach san pham';
$breadcrumbs = [
    ['label' => 'San pham', 'url' => url('admin/products')],
    ['label' => 'Danh sach'],
];
$visibilityLabels = [
    'both' => 'Web + Telegram',
    'web' => 'Chi Web',
    'telegram' => 'Chi Telegram',
    'hidden' => 'An ca 2',
];
$visibilityButtonClasses = [
    'both' => 'btn-success',
    'web' => 'btn-primary',
    'telegram' => 'btn-info',
    'hidden' => 'btn-secondary',
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<section class="content pb-4 mt-1">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-info elevation-1" style="border-radius: 8px;"><i class="fas fa-box-open"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">Tong san pham</span>
                        <span class="info-box-number h4 mb-0"><?= number_format($stats['total'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-success elevation-1" style="border-radius: 8px;"><i class="fas fa-globe"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">Hien tren Web</span>
                        <span class="info-box-number h4 mb-0"><?= number_format($stats['visible_web'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-primary elevation-1" style="border-radius: 8px;"><i class="fab fa-telegram-plane"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">Hien tren Telegram</span>
                        <span class="info-box-number h4 mb-0"><?= number_format($stats['visible_telegram'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-secondary elevation-1" style="border-radius: 8px;"><i class="fas fa-eye-slash"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">An ca 2</span>
                        <span class="info-box-number h4 mb-0"><?= number_format($stats['hidden'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title text-uppercase font-weight-bold">QUAN LY SAN PHAM</h3>
            </div>

            <div class="dt-filters">
                <div class="row g-2 justify-content-center align-items-center mb-3">
                    <div class="col-md-2 mb-2">
                        <input id="f-name" class="form-control form-control-sm" placeholder="Tim ten san pham...">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="f-category" class="form-control form-control-sm">
                            <option value="">-- Tat ca danh muc --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="f-status" class="form-control form-control-sm">
                            <option value="">-- Hien thi --</option>
                            <option value="mode:both">Web + Telegram</option>
                            <option value="mode:web">Chi Web</option>
                            <option value="mode:telegram">Chi Telegram</option>
                            <option value="mode:hidden">An ca 2</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="f-type" class="form-control form-control-sm">
                            <option value="">-- Loai san pham --</option>
                            <option value="Tai Khoan">Tai Khoan</option>
                            <option value="Yeu cau thong tin">Yeu cau thong tin</option>
                            <option value="Source">Source</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 text-center">
                        <button type="button" id="btn-clear" class="btn btn-danger btn-sm shadow-sm w-100">Xoa loc</button>
                    </div>
                    <div class="col-md-2 mb-2 text-right">
                        <a href="<?= url('admin/products/add') ?>" class="btn btn-primary btn-sm shadow-sm w-100">
                            <i class="fas fa-plus mr-1"></i> Them san pham
                        </a>
                    </div>
                </div>

                <div class="top-filter mb-2">
                    <div class="filter-show">
                        <span class="filter-label">Hien thi :</span>
                        <select id="f-length" class="filter-select flex-grow-1">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="filter-short justify-content-end">
                        <span class="filter-label">Loc theo ngay:</span>
                        <select id="f-sort" class="filter-select flex-grow-1">
                            <option value="all">Tat ca</option>
                            <option value="7">7 ngay qua</option>
                            <option value="15">15 ngay qua</option>
                            <option value="30">30 ngay qua</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-3">
                    <table id="productTable" class="table table-hover table-bordered admin-table w-100">
                        <thead>
                            <tr>
                                <th class="text-center font-weight-bold align-middle product-image-col">ANH</th>
                                <th class="text-center font-weight-bold align-middle">TEN SAN PHAM</th>
                                <th class="text-center font-weight-bold align-middle">LOAI</th>
                                <th class="text-center font-weight-bold align-middle">DANH MUC</th>
                                <th class="text-center font-weight-bold align-middle">GIA BAN</th>
                                <th class="text-center font-weight-bold align-middle">KHO / BAN</th>
                                <th class="text-center font-weight-bold align-middle">HIEN THI</th>
                                <th class="text-center font-weight-bold align-middle">NGAY TAO</th>
                                <th class="text-center font-weight-bold align-middle" style="width:140px;">THAO TAC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $p): ?>
                                    <?php
                                    $isAccount = ($p['product_type'] ?? 'account') === 'account';
                                    $isManualRequest = (($p['delivery_mode'] ?? '') === 'manual_info');
                                    $isSourceProduct = (($p['delivery_mode'] ?? '') === 'source_link');
                                    $isStockManaged = !empty($p['stock_managed']);
                                    $pid = (int) $p['id'];
                                    $st = $stockStats[$pid] ?? ['available' => 0, 'sold' => 0, 'unlimited' => false];
                                    $visibilityMode = Product::resolveVisibilityMode($p);
                                    $visibilityLabel = $visibilityLabels[$visibilityMode] ?? $visibilityLabels['both'];
                                    $visibilityButtonClass = $visibilityButtonClasses[$visibilityMode] ?? 'btn-secondary';
                                    ?>
                                    <tr id="row-<?= $pid ?>">
                                        <td class="text-center align-middle product-image-cell">
                                            <?php if (!empty($p['image'])): ?>
                                                <span class="product-thumb">
                                                    <img src="<?= htmlspecialchars($p['image']) ?>" class="product-thumb-img"
                                                        alt="<?= htmlspecialchars($p['name'] ?? 'Product image', ENT_QUOTES, 'UTF-8') ?>"
                                                        decoding="async" loading="lazy">
                                                </span>
                                            <?php else: ?>
                                                <div class="product-thumb product-thumb-placeholder">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <div class="font-weight-bold"><?= htmlspecialchars($p['name']) ?></div>
                                            <?php if (!empty($p['badge_text'])): ?>
                                                <span class="badge badge-warning"><?= htmlspecialchars($p['badge_text']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($isManualRequest): ?>
                                                <span class="badge badge-warning"><i class="fas fa-keyboard mr-1"></i>Yeu cau thong tin</span>
                                            <?php elseif ($isAccount): ?>
                                                <span class="badge badge-info"><i class="fas fa-user mr-1"></i>Tai Khoan</span>
                                            <?php else: ?>
                                                <span class="badge badge-purple" style="background:#8b5cf6;color:#fff;"><i class="fas fa-link mr-1"></i>Source</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle text-muted small">
                                            <?= htmlspecialchars($p['category_name'] ?? '--') ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="font-weight-bold text-success"><?= number_format((int) $p['price_vnd']) ?>d</div>
                                            <?php if (!empty($p['old_price']) && $p['old_price'] > $p['price_vnd']): ?>
                                                <div class="small text-muted" style="text-decoration: line-through;"><?= number_format((int) $p['old_price']) ?>d</div>
                                                <div class="badge badge-danger">-<?= round((($p['old_price'] - $p['price_vnd']) / $p['old_price']) * 100) ?>%</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($isSourceProduct || !empty($st['unlimited'])): ?>
                                                <span class="badge badge-light text-muted"><i class="fas fa-infinity mr-1"></i>Unlimited</span>
                                                <span class="text-muted"> / </span>
                                                <span class="text-danger font-weight-bold"><?= (int) ($st['sold'] ?? 0) ?></span>
                                            <?php elseif ($isManualRequest): ?>
                                                <span class="text-success font-weight-bold" title="Can giao"><?= $st['available'] ?></span>
                                                <span class="text-muted"> / </span>
                                                <span class="text-warning font-weight-bold" title="Cho xu ly"><?= $st['pending'] ?? 0 ?></span>
                                                <span class="text-muted"> / </span>
                                                <span class="text-danger font-weight-bold" title="Da ban"><?= $st['sold'] ?></span>
                                            <?php elseif ($isStockManaged): ?>
                                                <span class="text-success font-weight-bold" title="San co"><?= $st['available'] ?></span>
                                                <span class="text-muted"> / </span>
                                                <span class="text-danger font-weight-bold" title="Da ban"><?= $st['sold'] ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-light text-muted"><i class="fas fa-infinity mr-1"></i>Unlimited</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle visibility-cell" data-id="<?= $pid ?>" data-mode="<?= htmlspecialchars($visibilityMode, ENT_QUOTES, 'UTF-8') ?>">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-xs dropdown-toggle <?= $visibilityButtonClass ?>" data-toggle="dropdown" aria-expanded="false">
                                                    <span style="display:none;">mode:<?= htmlspecialchars($visibilityMode, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="visibility-label"><?= htmlspecialchars($visibilityLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <button type="button" class="dropdown-item set-visibility-btn<?= $visibilityMode === 'both' ? ' active' : '' ?>" data-id="<?= $pid ?>" data-mode="both">Web + Telegram</button>
                                                    <button type="button" class="dropdown-item set-visibility-btn<?= $visibilityMode === 'web' ? ' active' : '' ?>" data-id="<?= $pid ?>" data-mode="web">Chi Web</button>
                                                    <button type="button" class="dropdown-item set-visibility-btn<?= $visibilityMode === 'telegram' ? ' active' : '' ?>" data-id="<?= $pid ?>" data-mode="telegram">Chi Telegram</button>
                                                    <button type="button" class="dropdown-item set-visibility-btn<?= $visibilityMode === 'hidden' ? ' active' : '' ?>" data-id="<?= $pid ?>" data-mode="hidden">An ca 2</button>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle"
                                            data-time-ts="<?= (int) ($p['created_at_ts'] ?? 0) ?>"
                                            data-time-iso="<?= htmlspecialchars((string) ($p['created_at_iso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-order="<?= (int) ($p['created_at_ts'] ?? 0) ?>">
                                            <?= FormatHelper::eventTime($p['created_at_display'] ?? ($p['created_at'] ?? ''), $p['created_at'] ?? ($p['created_at_display'] ?? '')) ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="d-flex justify-content-center align-items-center" style="gap:5px; width:100%;">
                                                <a href="<?= url('admin/products/edit/' . $pid) ?>" class="btn btn-xs px-2" style="background-color: #8b5cf6; color: white;" title="Sua san pham">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?= url('admin/products/stock/' . $pid) ?>" class="btn btn-xs px-2" style="background-color: #0ea5e9; color: white;" title="Quan ly kho">
                                                    <i class="fas fa-warehouse"></i>
                                                </a>
                                                <button class="btn btn-danger btn-xs px-2 delete-btn" data-id="<?= $pid ?>" data-name="<?= htmlspecialchars($p['name']) ?>" title="Xoa san pham">
                                                    <i class="fas fa-trash"></i>
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
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
    let dtProduct;

    const visibilityConfig = {
        both: { label: 'Web + Telegram', buttonClass: 'btn-success', toast: 'Da chuyen sang Web + Telegram' },
        web: { label: 'Chi Web', buttonClass: 'btn-primary', toast: 'Da chuyen sang chi Web' },
        telegram: { label: 'Chi Telegram', buttonClass: 'btn-info', toast: 'Da chuyen sang chi Telegram' },
        hidden: { label: 'An ca 2', buttonClass: 'btn-secondary', toast: 'Da an san pham tren ca 2 kenh' }
    };

    function stripHtmlToText(html) {
        return String(html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getProductRowTimestamp(settings, dataIndex, cellHtml) {
        try {
            var rowMeta = settings && settings.aoData ? settings.aoData[dataIndex] : null;
            var rowNode = rowMeta ? rowMeta.nTr : null;
            var timeCell = rowNode && rowNode.cells ? rowNode.cells[7] : null;
            if (timeCell) {
                var tsAttr = Number(timeCell.getAttribute('data-time-ts') || '');
                if (!isNaN(tsAttr) && tsAttr > 0) return tsAttr * 1000;
                var iso = timeCell.getAttribute('data-time-iso') || '';
                if (iso) {
                    if (window.KaiTime && typeof window.KaiTime.toTimestamp === 'function') {
                        var kaiTs = window.KaiTime.toTimestamp(iso);
                        if (!isNaN(kaiTs) && kaiTs > 0) return kaiTs * 1000;
                    }
                    var nativeTs = Date.parse(iso);
                    if (!isNaN(nativeTs)) return nativeTs;
                }
            }
        } catch (e) {}
        var raw = stripHtmlToText(cellHtml);
        if (window.KaiTime && typeof window.KaiTime.toTimestamp === 'function') {
            var fallbackTs = window.KaiTime.toTimestamp(raw);
            if (!isNaN(fallbackTs) && fallbackTs > 0) return fallbackTs * 1000;
        }
        var ts = Date.parse(raw);
        return isNaN(ts) ? null : ts;
    }

    function renderVisibilityCell(id, mode) {
        const cfg = visibilityConfig[mode] || visibilityConfig.both;
        const options = [
            { key: 'both', label: 'Web + Telegram' },
            { key: 'web', label: 'Chi Web' },
            { key: 'telegram', label: 'Chi Telegram' },
            { key: 'hidden', label: 'An ca 2' }
        ];
        const items = options.map(function (option) {
            const activeClass = option.key === mode ? ' active' : '';
            return `<button type="button" class="dropdown-item set-visibility-btn${activeClass}" data-id="${id}" data-mode="${option.key}">${option.label}</button>`;
        }).join('');

        return `
            <div class="btn-group">
                <button type="button" class="btn btn-xs dropdown-toggle ${cfg.buttonClass}" data-toggle="dropdown" aria-expanded="false">
                    <span style="display:none;">mode:${mode}</span>
                    <span class="visibility-label">${escapeHtml(cfg.label)}</span>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    ${items}
                </div>
            </div>
        `;
    }

    $(function () {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true
        });

        dtProduct = $('#productTable').DataTable({
            dom: 't<"row align-items-center mt-3"<"col-12 d-flex justify-content-md-end justify-content-center"p>>',
            responsive: true,
            autoWidth: false,
            order: [[7, "desc"]],
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [0, 8] },
                { searchable: false, targets: [0, 4, 5, 8] }
            ],
            language: {
                sLengthMenu: 'Hien thi _MENU_ san pham',
                sZeroRecords: '<div class="text-center w-100 font-weight-bold py-3">Khong tim thay san pham nao</div>',
                sInfo: '',
                sInfoEmpty: '',
                sInfoFiltered: '',
                sSearch: 'Tim kiem:',
                oPaginate: {
                    sPrevious: '<i class="fas fa-chevron-left"></i>',
                    sNext: '<i class="fas fa-chevron-right"></i>'
                },
            },
        });

        $('#f-length').change(function () {
            dtProduct.page.len($(this).val()).draw();
        });

        $('#f-name').on('input keyup', function () {
            dtProduct.column(1).search(this.value.trim()).draw();
        });

        $('#f-category').change(function () {
            dtProduct.column(3).search(this.value).draw();
        });

        $('#f-status').change(function () {
            dtProduct.column(6).search(this.value).draw();
        });

        $('#f-type').change(function () {
            dtProduct.column(2).search(this.value).draw();
        });

        $('#f-sort').change(function () {
            dtProduct.draw();
        });

        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'productTable') return true;
            var sortVal = $('#f-sort').val();
            if (sortVal !== 'all') {
                var days = parseInt(sortVal, 10);
                if (!isNaN(days)) {
                    var rowTime = getProductRowTimestamp(settings, dataIndex, data[7]);
                    var pastTime = new Date().getTime() - (days * 24 * 60 * 60 * 1000);
                    if (rowTime !== null && rowTime < pastTime) return false;
                }
            }
            return true;
        });

        $('#btn-clear').click(function () {
            $('#f-name').val('');
            $('#f-category').val('');
            $('#f-status').val('');
            $('#f-type').val('');
            $('#f-length').val('10');
            $('#f-sort').val('all');
            dtProduct.search('').columns().search('');
            dtProduct.page.len(10).order([7, 'desc']).draw();
        });

        $('#productTable tbody').on('click', '.set-visibility-btn', function () {
            const btn = $(this);
            const id = btn.data('id');
            const mode = btn.data('mode');
            const row = btn.closest('tr');
            const statusCell = row.find('td:eq(6)');

            $.post('<?= url("admin/products/toggle-status") ?>', {
                id: id,
                mode: mode
            }, function (res) {
                if (res.success) {
                    const nextMode = res.visibility_mode || mode;
                    statusCell.attr('data-mode', nextMode).html(renderVisibilityCell(id, nextMode));
                    dtProduct.cell(statusCell).data(statusCell.html()).invalidate();

                    Toast.fire({
                        icon: 'success',
                        title: (visibilityConfig[nextMode] || visibilityConfig.both).toast
                    });

                    if ($('#f-status').val() !== '') {
                        dtProduct.draw();
                    }
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: res.message || 'Loi!'
                    });
                }
            }, 'json').fail(() => Toast.fire({
                icon: 'error',
                title: 'Loi may chu!'
            }));
        });

        $('#productTable tbody').on('click', '.delete-btn', function () {
            const btn = $(this);
            const id = btn.data('id');
            const name = btn.data('name');
            Swal.fire({
                title: 'Xoa san pham?',
                text: name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonText: 'Huy',
                confirmButtonText: 'Xoa'
            }).then(r => {
                if (!r.isConfirmed) return;
                $.post('<?= url("admin/products/delete") ?>', {
                    id: id
                }, function (res) {
                    if (res.success) {
                        dtProduct.row(btn.closest('tr')).remove().draw();
                        Toast.fire({
                            icon: 'success',
                            title: 'Da xoa san pham'
                        });
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: res.message || 'Loi!'
                        });
                    }
                }, 'json');
            });
        });
    });
</script>

<?php
/**
 * View: Product stock management (account items)
 * Route: GET /admin/products/stock/{id}
 * Controller: AdminProductController@stock
 */
$productName = (string) ($product['name'] ?? '');
$productId = (int) ($product['id'] ?? 0);

$pageTitle = 'Stock - ' . htmlspecialchars($productName, ENT_QUOTES, 'UTF-8');
$breadcrumbs = [
    ['label' => 'San pham', 'url' => url('admin/products')],
    ['label' => htmlspecialchars($productName, ENT_QUOTES, 'UTF-8'), 'url' => url('admin/products/edit/' . $productId)],
    ['label' => 'Kho'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<section class="content pb-4 mt-1 admin-stock-page">
    <div class="container-fluid">
        <div class="row mb-3 admin-stock-stats">
            <div class="col-lg-4 mb-3 mb-lg-0">
                <div class="info-box stock-stat-box shadow-sm border-0">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-boxes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase font-weight-bold text-muted">Tong kho</span>
                        <span class="info-box-number h4 mb-0" id="stat-total"><?= (int) ($stats['total'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3 mb-lg-0">
                <div class="info-box stock-stat-box stock-stat-box--available shadow-sm border-0">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase font-weight-bold text-muted">Con lai</span>
                        <span class="info-box-number h4 mb-0" id="stat-available"><?= (int) ($stats['available'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="info-box stock-stat-box stock-stat-box--sold shadow-sm border-0">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-shopping-bag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase font-weight-bold text-muted">Da ban</span>
                        <span class="info-box-number h4 mb-0" id="stat-sold"><?= (int) ($stats['sold'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card stock-panel-card shadow-sm border-0">
            <div class="card-header border-0 pb-0">
                <h4 class="card-title font-weight-bold mb-0 text-dark">DANH SACH TRONG KHO</h4>
            </div>

            <div class="dt-filters pt-3">
                <div class="row align-items-center mb-3">
                    <div class="col-lg-4 col-md-6 mb-2">
                        <div class="input-group stock-search-group w-100">
                            <input id="f-search" type="text" class="form-control" placeholder="Tim noi dung tai khoan..."
                                value="<?= htmlspecialchars((string) ($search ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" id="btnSearch" title="Tim kiem">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <select id="f-status" class="form-control stock-filter-select w-100">
                            <option value="" <?= ($statusFilter ?? '') === '' ? 'selected' : '' ?>>-- Tat ca trang thai --</option>
                            <option value="available" <?= ($statusFilter ?? '') === 'available' ? 'selected' : '' ?>>Con lai</option>
                            <option value="sold" <?= ($statusFilter ?? '') === 'sold' ? 'selected' : '' ?>>Da ban</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <button type="button" id="btn-clear" class="btn btn-danger btn-sm shadow-sm w-100">
                            <i class="fas fa-trash"></i> Xoa Loc
                        </button>
                    </div>
                    <div class="col-lg-1 col-md-4 mb-2">
                        <button type="button" id="btnClean" class="btn btn-outline-danger btn-sm shadow-sm w-100">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2 text-md-right">
                        <button type="button" class="btn btn-primary btn-sm shadow-sm w-100" data-toggle="modal"
                            data-target="#importModal">
                            <i class="fas fa-plus mr-1"></i> Them vao kho
                        </button>
                    </div>
                </div>

                <div class="top-filter mb-2">
                    <div class="filter-show">
                        <span class="filter-label">SHOW :</span>
                        <select id="f-length" class="filter-select flex-grow-1">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="filter-short justify-content-end">
                        <span class="filter-label">SHORT BY DATE:</span>
                        <select id="f-sort" class="filter-select flex-grow-1">
                            <option value="all">Tat ca</option>
                            <option value="7">7 days</option>
                            <option value="15">15 days</option>
                            <option value="30">30 days</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-body pt-2">
                <div class="table-responsive table-wrapper mb-0 stock-table-wrap">
                    <table id="stockTable" class="table table-hover table-bordered w-100 stock-table">
                        <thead>
                            <tr>
                                <th class="text-center font-weight-bold align-middle">NOI DUNG TAI KHOAN</th>
                                <th class="text-center font-weight-bold align-middle" style="width: 180px;">NGUOI MUA</th>
                                <th class="text-center font-weight-bold align-middle" style="width: 120px;">TRANG THAI</th>
                                <th class="text-center font-weight-bold align-middle" style="width: 190px;">NGAY NHAP</th>
                                <th class="text-center font-weight-bold align-middle" style="width: 120px;">HANH DONG</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                                        Kho hien dang trong
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item):
                                    $itemId = (int) ($item['id'] ?? 0);
                                    $itemStatus = (string) ($item['status'] ?? 'available');
                                    $isAvailable = $itemStatus === 'available';
                                    $content = (string) ($item['content'] ?? '');
                                    $buyer = (string) ($item['buyer_username'] ?? '');
                                    $createdTs = (int) ($item['created_at_ts'] ?? 0);
                                    $createdIso = (string) ($item['created_at_iso'] ?? '');
                                    $createdDisplay = (string) ($item['created_at_display'] ?? '');
                                    $soldDisplay = (string) ($item['sold_at_display'] ?? '');
                                    $soldTs = (int) ($item['sold_at_ts'] ?? 0);
                                    ?>
                                    <tr id="stock-row-<?= $itemId ?>" data-time-ts="<?= $createdTs ?>"
                                        data-time-iso="<?= htmlspecialchars($createdIso, ENT_QUOTES, 'UTF-8') ?>">
                                        <td class="align-middle text-left stock-content-cell">
                                            <div class="d-flex align-items-start justify-content-between gap-2">
                                                <code class="stock-account-code mb-0<?= !$isAvailable ? ' stock-content-sold' : '' ?>"><?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8') ?></code>
                                                <button type="button" class="btn btn-xs btn-outline-info copy-content-btn"
                                                    data-content="<?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8') ?>" title="Copy">
                                                    <i class="far fa-copy"></i>
                                                </button>
                                            </div>
                                            <?php if (!$isAvailable && $soldDisplay !== ''): ?>
                                                <small class="text-muted d-block mt-2">
                                                    Sold at:
                                                    <span class="font-weight-bold">
                                                        <?= htmlspecialchars($soldDisplay, ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($buyer !== ''): ?>
                                                <a class="font-weight-bold text-primary"
                                                    href="<?= url('admin/users/edit/' . rawurlencode($buyer)) ?>">
                                                    <i class="fas fa-user-circle mr-1"></i><?= htmlspecialchars($buyer, ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">--</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="d-none"><?= $isAvailable ? 'available' : 'sold' ?></span>
                                            <?php if ($isAvailable): ?>
                                                <span class="badge badge-success px-3 py-1">CON HANG</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary px-3 py-1">DA BAN</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle"
                                            data-time-ts="<?= $createdTs ?>"
                                            data-time-iso="<?= htmlspecialchars($createdIso, ENT_QUOTES, 'UTF-8') ?>">
                                            <span class="badge date-badge">
                                                <?= htmlspecialchars($createdDisplay !== '' ? $createdDisplay : ((string) ($item['created_at'] ?? '--')), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="text-center align-middle stock-action-cell">
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($isAvailable): ?>
                                                    <button class="btn btn-search-dt stock-row-action-btn edit-stock-btn"
                                                        data-id="<?= $itemId ?>"
                                                        data-content="<?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8') ?>"
                                                        title="Sua">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-danger stock-row-action-btn delete-stock-btn"
                                                        data-id="<?= $itemId ?>" title="Xoa">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-light border stock-row-action-btn" disabled title="Da ban">
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                    <button class="btn btn-light border stock-row-action-btn" disabled title="Da ban">
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                <?php endif; ?>
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

<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold">NHAP KHO</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="stock-label font-weight-bold mb-0">Danh sach tai khoan (1 dong = 1 item)</label>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnPickStockFile">
                        <i class="fas fa-file-import mr-1"></i>Chon file .txt
                    </button>
                    <input type="file" id="stockFile" style="display:none;" accept=".txt">
                </div>

                <textarea id="importContent" class="form-control stock-import-textarea"
                    placeholder="user1:pass1&#10;user2:pass2&#10;user3:pass3"></textarea>

                <div id="importResult" class="stock-import-result mt-3" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-dismiss="modal">Huy</button>
                <button type="button" id="btnImport" class="btn btn-primary stock-submit-btn">
                    <i class="fas fa-upload mr-1"></i>Nhap kho
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editStockModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning">
                <h5 class="modal-title font-weight-bold">SUA NOI DUNG ITEM</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <label class="font-weight-bold mb-2">Noi dung</label>
                <textarea id="editContent" class="form-control" rows="4" placeholder="user:pass..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-dismiss="modal">Huy</button>
                <button type="button" id="btnSaveEdit" class="btn btn-warning font-weight-bold">Luu cap nhat</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(function () {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true
        });

        const importUrl = '<?= url("admin/products/stock/" . $productId . "/import") ?>';
        const updateUrl = '<?= url("admin/products/stock/update") ?>';
        const deleteUrl = '<?= url("admin/products/stock/delete") ?>';
        const cleanUrl = '<?= url("admin/products/stock/" . $productId . "/clean") ?>';

        function stripHtmlToText(html) {
            return String(html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        }

        function extractAjaxError(xhr, fallbackMessage) {
            let message = fallbackMessage || 'Request failed';
            if (!xhr) return message;

            if (xhr.responseJSON && xhr.responseJSON.message) {
                return xhr.responseJSON.message;
            }

            try {
                const parsed = JSON.parse(xhr.responseText || '{}');
                if (parsed.message) return parsed.message;
            } catch (e) {}

            if (xhr.responseText) {
                const text = String(xhr.responseText).replace(/<[^>]*>/g, ' ').trim();
                if (text !== '') {
                    message = text.length > 200 ? text.slice(0, 200) + '...' : text;
                }
            }

            if (xhr.status) {
                return message + ' (HTTP ' + xhr.status + ')';
            }
            return message;
        }

        function getStockRowTimestamp(settings, dataIndex, cellHtml) {
            try {
                const rowMeta = settings && settings.aoData ? settings.aoData[dataIndex] : null;
                const rowNode = rowMeta ? rowMeta.nTr : null;
                const timeCell = rowNode && rowNode.cells ? rowNode.cells[3] : null;
                if (timeCell) {
                    const tsAttr = Number(timeCell.getAttribute('data-time-ts') || '');
                    if (!isNaN(tsAttr) && tsAttr > 0) return tsAttr * 1000;

                    const iso = timeCell.getAttribute('data-time-iso') || '';
                    if (iso) {
                        if (window.KaiTime && typeof window.KaiTime.toTimestamp === 'function') {
                            const kaiTs = window.KaiTime.toTimestamp(iso);
                            if (!isNaN(kaiTs) && kaiTs > 0) return kaiTs * 1000;
                        }
                        const nativeTs = Date.parse(iso);
                        if (!isNaN(nativeTs)) return nativeTs;
                    }
                }
            } catch (e) {}

            const raw = stripHtmlToText(cellHtml);
            if (window.KaiTime && typeof window.KaiTime.toTimestamp === 'function') {
                const fallbackTs = window.KaiTime.toTimestamp(raw);
                if (!isNaN(fallbackTs) && fallbackTs > 0) return fallbackTs * 1000;
            }
            const ts = Date.parse(raw);
            return isNaN(ts) ? null : ts;
        }

        const dtStock = $('#stockTable').DataTable({
            dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center"p>>',
            responsive: true,
            autoWidth: false,
            order: [[3, 'desc']],
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [4] }
            ],
            language: {
                sZeroRecords: '<div class="text-center w-100 font-weight-bold py-3">Khong tim thay du lieu</div>',
                sInfo: 'Xem _START_-_END_ / _TOTAL_ muc',
                sInfoEmpty: 'Khong co du lieu',
                sInfoFiltered: '(loc tu _MAX_)',
                oPaginate: { sPrevious: '&lsaquo;', sNext: '&rsaquo;' }
            }
        });

        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'stockTable') return true;
            const sortVal = $('#f-sort').val();
            if (sortVal === 'all') return true;
            const days = parseInt(sortVal, 10);
            if (isNaN(days)) return true;
            const rowTime = getStockRowTimestamp(settings, dataIndex, data[3]);
            if (rowTime === null) return true;
            const pastTime = Date.now() - (days * 24 * 60 * 60 * 1000);
            return rowTime >= pastTime;
        });

        $('#f-length').on('change', function () {
            dtStock.page.len(parseInt($(this).val(), 10) || 10).draw();
        });

        $('#f-search').on('input keyup', function () {
            dtStock.column(0).search(this.value.trim()).draw();
        });

        $('#btnSearch').on('click', function () {
            dtStock.column(0).search($('#f-search').val().trim()).draw();
        });

        $('#f-status').on('change', function () {
            const value = String($(this).val() || '');
            if (value === '') {
                dtStock.column(2).search('').draw();
                return;
            }
            dtStock.column(2).search(value, true, false).draw();
        });

        $('#f-sort').on('change', function () {
            dtStock.draw();
        });

        $('#btn-clear').on('click', function () {
            $('#f-search').val('');
            $('#f-status').val('');
            $('#f-sort').val('all');
            $('#f-length').val('10');
            dtStock.search('').columns().search('');
            dtStock.page.len(10).order([3, 'desc']).draw();
        });

        $('#btnPickStockFile').on('click', function () {
            $('#stockFile').trigger('click');
        });

        $('#stockFile').on('change', function () {
            const file = this.files && this.files[0] ? this.files[0] : null;
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                $('#importContent').val((e && e.target && e.target.result) ? String(e.target.result) : '');
                Toast.fire({ icon: 'success', title: 'Da tai noi dung tu file' });
            };
            reader.readAsText(file);
            this.value = '';
        });

        $('#btnImport').on('click', function () {
            const content = String($('#importContent').val() || '').trim();
            if (!content) {
                Toast.fire({ icon: 'warning', title: 'Nhap danh sach truoc khi them' });
                return;
            }

            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Dang xu ly...');

            $.post(importUrl, { content: content }, function (res) {
                btn.prop('disabled', false).html('<i class="fas fa-upload mr-1"></i>Nhap kho');
                if (!res || !res.success) {
                    Toast.fire({ icon: 'error', title: (res && res.message) ? res.message : 'Nhap kho that bai' });
                    return;
                }

                const added = Number(res.added || 0);
                const skipped = Number(res.skipped || 0);
                $('#importResult').html(
                    '<div class="alert alert-success">Da nhap <b>' + added + '</b> item, bo qua <b>' + skipped + '</b> item trung.</div>'
                ).show();
                Toast.fire({ icon: 'success', title: 'Nhap kho thanh cong' });
                setTimeout(function () { window.location.reload(); }, 900);
            }, 'json').fail(function (xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-upload mr-1"></i>Nhap kho');
                Toast.fire({ icon: 'error', title: extractAjaxError(xhr, 'Loi server khi nhap kho') });
            });
        });

        $(document).on('click', '.copy-content-btn', function () {
            const content = String($(this).data('content') || '');
            if (!content) return;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(content)
                    .then(function () {
                        Toast.fire({ icon: 'success', title: 'Da copy noi dung' });
                    })
                    .catch(function () {
                        Toast.fire({ icon: 'error', title: 'Copy that bai' });
                    });
                return;
            }

            const temp = $('<input>');
            $('body').append(temp);
            temp.val(content).select();
            document.execCommand('copy');
            temp.remove();
            Toast.fire({ icon: 'success', title: 'Da copy noi dung' });
        });

        $(document).on('click', '.edit-stock-btn', function () {
            $('#editId').val($(this).data('id'));
            $('#editContent').val($(this).data('content'));
            $('#editStockModal').modal('show');
        });

        $('#btnSaveEdit').on('click', function () {
            const id = parseInt($('#editId').val(), 10) || 0;
            const content = String($('#editContent').val() || '').trim();
            if (id <= 0) {
                Toast.fire({ icon: 'warning', title: 'ID khong hop le' });
                return;
            }
            if (!content) {
                Toast.fire({ icon: 'warning', title: 'Noi dung khong duoc de trong' });
                return;
            }

            const btn = $(this);
            btn.prop('disabled', true).text('Dang luu...');
            $.post(updateUrl, { id: id, content: content }, function (res) {
                btn.prop('disabled', false).text('Luu cap nhat');
                if (!res || !res.success) {
                    Toast.fire({ icon: 'error', title: (res && res.message) ? res.message : 'Cap nhat that bai' });
                    return;
                }

                const row = $('#stock-row-' + id);
                row.find('.stock-account-code').text(content);
                row.find('.copy-content-btn').data('content', content);
                row.find('.edit-stock-btn').data('content', content);
                $('#editStockModal').modal('hide');
                Toast.fire({ icon: 'success', title: 'Da cap nhat item' });
            }, 'json').fail(function (xhr) {
                btn.prop('disabled', false).text('Luu cap nhat');
                Toast.fire({ icon: 'error', title: extractAjaxError(xhr, 'Loi server khi cap nhat') });
            });
        });

        $(document).on('click', '.delete-stock-btn', function () {
            const btn = $(this);
            const id = parseInt(btn.data('id'), 10) || 0;
            if (id <= 0) return;

            Swal.fire({
                title: 'Xoa item nay?',
                text: 'Muc nay se bi xoa khoi kho.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonText: 'Huy',
                confirmButtonText: 'Xoa'
            }).then(function (result) {
                if (!result.isConfirmed) return;

                $.post(deleteUrl, { id: id }, function (res) {
                    if (!res || !res.success) {
                        Toast.fire({ icon: 'error', title: (res && res.message) ? res.message : 'Xoa that bai' });
                        return;
                    }

                    const row = btn.closest('tr');
                    dtStock.row(row).remove().draw(false);

                    const totalEl = $('#stat-total');
                    const availableEl = $('#stat-available');
                    const total = Math.max(0, (parseInt(totalEl.text(), 10) || 0) - 1);
                    const available = Math.max(0, (parseInt(availableEl.text(), 10) || 0) - 1);
                    totalEl.text(total);
                    availableEl.text(available);

                    Toast.fire({ icon: 'success', title: 'Da xoa item' });
                }, 'json').fail(function (xhr) {
                    Toast.fire({ icon: 'error', title: extractAjaxError(xhr, 'Loi server khi xoa item') });
                });
            });
        });

        $('#btnClean').on('click', function () {
            const button = $(this);
            Swal.fire({
                title: 'Don kho?',
                text: 'Toan bo item chua ban se bi xoa.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonText: 'Huy',
                confirmButtonText: 'Dong y'
            }).then(function (result) {
                if (!result.isConfirmed) return;

                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                $.post(cleanUrl, {}, function (res) {
                    if (!res || !res.success) {
                        button.prop('disabled', false).html('<i class="fas fa-eraser"></i>');
                        Toast.fire({ icon: 'error', title: (res && res.message) ? res.message : 'Don kho that bai' });
                        return;
                    }
                    Toast.fire({ icon: 'success', title: res.message || 'Da don kho' });
                    setTimeout(function () { window.location.reload(); }, 700);
                }, 'json').fail(function (xhr) {
                    button.prop('disabled', false).html('<i class="fas fa-eraser"></i>');
                    Toast.fire({ icon: 'error', title: extractAjaxError(xhr, 'Loi server khi don kho') });
                });
            });
        });
    });
</script>

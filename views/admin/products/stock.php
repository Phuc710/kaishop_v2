<?php
/**
 * View: Quản lý kho sản phẩm (tài khoản)
 * Route: GET /admin/products/stock/{id}
 * Controller: AdminProductController@stock
 */
$pageTitle = 'Kho – ' . htmlspecialchars($product['name']);
$breadcrumbs = [
    ['label' => 'Sản phẩm', 'url' => url('admin/products')],
    ['label' => htmlspecialchars($product['name']), 'url' => url('admin/products/edit/' . $product['id'])],
    ['label' => 'Kho'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<section class="content pb-4 mt-3 admin-stock-page">
    <div class="container-fluid">

        <!-- STATS -->
        <div class="row mb-3 admin-stock-stats">
            <div class="col-lg-4 mb-3 mb-lg-0">
                <div class="info-box stock-stat-box shadow-sm border-0">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-boxes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase font-weight-bold text-muted">Tổng kho</span>
                        <span class="info-box-number h4 mb-0" id="stat-total"><?= $stats['total'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3 mb-lg-0">
                <div class="info-box stock-stat-box shadow-sm border-0">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase font-weight-bold text-muted">Còn lại</span>
                        <span class="info-box-number h4 mb-0" id="stat-available"><?= $stats['available'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="info-box stock-stat-box shadow-sm border-0">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-shopping-bag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase font-weight-bold text-muted">Đã bán</span>
                        <span class="info-box-number h4 mb-0" id="stat-sold"><?= $stats['sold'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card custom-card stock-panel-card shadow-sm border-0">
                    <div
                        class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap py-3">
                        <h4 class="card-title font-weight-bold mb-0 text-dark">
                            DANH SÁCH TRONG KHO
                        </h4>
                        <div class="stock-toolbar d-flex align-items-center flex-wrap gap-2 mt-2 mt-md-0">
                            <!-- Uniform height for all items -->
                            <style>
                                .stock-toolbar .form-control,
                                .stock-toolbar .btn,
                                .stock-toolbar .input-group-text,
                                .stock-toolbar .input-group {
                                    height: 38px !important;
                                    display: flex;
                                    align-items: center;
                                }

                                .stock-toolbar .input-group .form-control {
                                    height: 100% !important;
                                }

                                .stock-toolbar .input-group-append .btn {
                                    height: 100% !important;
                                }

                                .stock-toolbar .btn-group .btn {
                                    height: 100% !important;
                                }
                            </style>

                            <div class="input-group mr-2" style="width: 280px;">
                                <input type="text" id="searchTerm" class="form-control" placeholder="Tìm nội dung..."
                                    value="<?= htmlspecialchars($search ?? '') ?>">
                            </div>

                            <select id="filterStatus" class="form-control mr-2 shadow-sm" style="width: 160px;">
                                <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                                <option value="available" <?= $statusFilter === 'available' ? 'selected' : '' ?>>Còn lại
                                </option>
                                <option value="sold" <?= $statusFilter === 'sold' ? 'selected' : '' ?>>Đã bán</option>
                            </select>

                            <div class="btn-group mr-2">
                                <button id="btnClean" class="btn btn-outline-danger" title="Xóa toàn bộ hàng chưa bán">
                                    <i class="fas fa-eraser mr-1"></i>Dọn kho
                                </button>
                            </div>

                            <button type="button" class="btn btn-primary font-weight-bold px-3 shadow-sm"
                                onclick="$('#importModal').modal('show')">
                                <i class="fas fa-plus-circle mr-1"></i> Thêm vào kho
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0" id="stockTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width:70px;"
                                            class="text-center font-weight-bold text-dark text-uppercase small">ID</th>
                                        <th class="text-center font-weight-bold text-dark text-uppercase small">Nội dung
                                            tài khoản</th>
                                        <th style="width:150px;"
                                            class="text-center font-weight-bold text-dark text-uppercase small">Người
                                            mua / Buyer</th>
                                        <th style="width:120px;"
                                            class="text-center font-weight-bold text-dark text-uppercase small">Trạng
                                            thái</th>
                                        <th style="width:120px;"
                                            class="text-center font-weight-bold text-dark text-uppercase small">Ngày
                                            nhập</th>
                                        <th style="width:120px;"
                                            class="text-center font-weight-bold text-dark text-uppercase small">Hành
                                            động</th>
                                    </tr>
                                </thead>
                                <tbody id="stockBody">
                                    <?php if (empty($items)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="fas fa-box-open fa-3x mb-3 d-block opacity-50"></i>
                                                Kho hiện đang trống
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr id="stock-row-<?= $item['id'] ?>"
                                                class="<?= $item['status'] === 'sold' ? 'table-light' : '' ?>">
                                                <td class="text-center align-middle">
                                                    <span class="text-muted font-weight-bold">#<?= $item['id'] ?></span>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <div class="d-flex align-items-center justify-content-center">
                                                        <code class="p-1 px-2 border rounded bg-white text-dark mr-2"
                                                            style="font-size: 14px;"><?= htmlspecialchars($item['content']) ?></code>
                                                        <button class="btn btn-xs btn-outline-info copy-content-btn"
                                                            data-content="<?= htmlspecialchars($item['content']) ?>"
                                                            title="Copy">
                                                            <i class="far fa-copy"></i>
                                                        </button>
                                                    </div>
                                                    <?php if ($item['status'] === 'sold' && $item['sold_at']): ?>
                                                        <div class="mt-1 small text-center">
                                                            <span class="text-danger font-weight-bold"><i
                                                                    class="far fa-clock mr-1"></i>Bán lúc:</span>
                                                            <span
                                                                class="text-muted"><?= htmlspecialchars((string) (class_exists('TimeService') ? TimeService::instance()->formatDisplay($item['sold_at'], 'd/m/Y H:i:s') : date('d/m/Y H:i:s', strtotime($item['sold_at'])))) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <?php if (!empty($item['buyer_username'])): ?>
                                                        <a href="<?= url('admin/users/edit/' . $item['buyer_username']) ?>"
                                                            class="d-inline-flex align-items-center text-primary font-weight-bold">
                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-2"
                                                                style="width:24px; height:24px; font-size:10px;">
                                                                <i class="fas fa-user"></i>
                                                            </div>
                                                            <?= htmlspecialchars($item['buyer_username']) ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted small"><i class="fas fa-minus mr-1"></i>Chưa
                                                            bán</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <?php if ($item['status'] === 'available'): ?>
                                                        <span class="badge badge-success shadow-sm px-3 py-1">CÒN HÀNG</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary shadow-sm px-3 py-1">ĐÃ BÁN</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <span class="text-muted small">
                                                        <?= $item['created_at'] ? htmlspecialchars((string) (class_exists('TimeService') ? TimeService::instance()->formatDisplay($item['created_at'], 'd/m/Y') : date('d/m/Y', strtotime($item['created_at'])))) : '—' ?><br>
                                                        <span
                                                            class="font-weight-bold"><?= $item['created_at'] ? htmlspecialchars((string) (class_exists('TimeService') ? TimeService::instance()->formatDisplay($item['created_at'], 'H:i') : date('H:i', strtotime($item['created_at'])))) : '' ?></span>
                                                    </span>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <button
                                                            class="btn <?= $item['status'] === 'available' ? 'btn-warning' : 'btn-light border' ?> edit-stock-btn"
                                                            data-id="<?= $item['id'] ?>"
                                                            data-content="<?= htmlspecialchars($item['content']) ?>"
                                                            title="<?= $item['status'] === 'available' ? 'Sửa nội dung' : 'Sửa nội dung (Dành cho bảo hành)' ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($item['status'] === 'available'): ?>
                                                            <button class="btn btn-danger delete-stock-btn"
                                                                data-id="<?= $item['id'] ?>" title="Xóa">
                                                                <i class="fas fa-trash"></i>
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
                    <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            Đang hiển thị <b><?= count($items) ?></b> tài khoản.
                        </span>
                        <nav id="paginationWrap">
                            <!-- Pagination can be added here if needed -->
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MODAL IMPORT (POPUP) -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white p-3">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-upload mr-2"></i> NHẬP HÀNG MỚI</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="form-group mb-4">
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <label class="font-weight-bold text-dark mb-0">2. Danh sách tài khoản</label>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="$('#stockFile').click()">
                            <i class="fas fa-file-import mr-1"></i>Chọn từ file (.txt)
                        </button>
                        <input type="file" id="stockFile" style="display:none;" accept=".txt"
                            onchange="handleStockFile(this)">
                    </div>
                    <textarea id="importContent" class="form-control border-primary" rows="12"
                        placeholder="user1:pass1&#10;user2:pass2&#10;user3:pass3|extra_info..."
                        style="font-family: 'Courier New', Courier, monospace; font-size: 14px; border-width: 2px;"></textarea>
                </div>

                <div id="importResult" class="mb-4" style="display:none;"></div>

                <div class="bg-light p-3 rounded d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-link text-muted" data-dismiss="modal">Hủy bỏ</button>
                    <button id="btnImport" class="btn btn-primary btn-lg px-5 font-weight-bold shadow">
                        <i class="fas fa-plus-circle mr-2"></i> BẮT ĐẦU NHẬP KHO
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDIT STOCK -->
<div class="modal fade" id="editStockModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog border-0" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning py-2">
                <h5 class="modal-title font-weight-bold">SỬA NỘI DUNG TÀI KHOẢN</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="editId">
                <div class="form-group mb-0">
                    <label class="font-weight-bold text-dark mb-2">Nội dung (1 dòng duy nhất)</label>
                    <textarea id="editContent" class="form-control" rows="3" placeholder="user:pass..."></textarea>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-exclamation-triangle mr-1 text-warning"></i>
                        Chỉ chỉnh sửa khi cần thiết (Ví dụ: khách báo sai mật khẩu, bảo hành).
                    </small>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 justify-content-center">
                <button type="button" class="btn btn-light border px-4" data-dismiss="modal">Hủy</button>
                <button type="button" id="btnSaveEdit" class="btn btn-warning font-weight-bold px-5 shadow-sm">LƯU CẬP
                    NHẬT</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<script>
    $(function () {
        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });
        const importUrl = '<?= url("admin/products/stock/" . $product['id'] . "/import") ?>';
        const deleteUrl = '<?= url("admin/products/stock/delete") ?>';
        const updateUrl = '<?= url("admin/products/stock/update") ?>';
        const cleanUrl = '<?= url("admin/products/stock/" . $product['id'] . "/clean") ?>';
        const baseUrl = '<?= url("admin/products/stock/" . $product['id']) ?>';

        window.handleStockFile = function (input) {
            const file = input.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#importContent').val(e.target.result);
                Toast.fire({ icon: 'success', title: 'Đã tải nội dung từ file' });
            };
            reader.readAsText(file);
            input.value = '';
        };

        // Copy content
        $(document).on('click', '.copy-content-btn', function () {
            const content = $(this).data('content');
            const temp = $("<input>");
            $("body").append(temp);
            temp.val(content).select();
            document.execCommand("copy");
            temp.remove();
            Toast.fire({ icon: 'success', title: 'Đã copy nội dung' });
        });

        // Import
        $('#btnImport').on('click', function () {
            const content = $('#importContent').val().trim();
            if (!content) { Toast.fire({ icon: 'warning', title: 'Vui lòng nhập danh sách!' }); return; }

            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Đang xử lý...');

            $.post(importUrl, { content: content }, function (res) {
                btn.prop('disabled', false).html('<i class="fas fa-plus-circle mr-2"></i> BẮT ĐẦU NHẬP KHO');
                if (res.success) {
                    $('#importContent').val('');
                    $('#importResult').html(`<div class="alert alert-success border-left">✅ <b>Thành công!</b> Đã nhập <b>${res.added}</b> item mới. Bỏ qua <b>${res.skipped}</b> dòng trùng.</div>`).show();
                    Toast.fire({ icon: 'success', title: 'Đã nhập thành công ' + res.added + ' items' });
                    setTimeout(() => location.reload(), 2000);
                } else {
                    Toast.fire({ icon: 'error', title: res.message || 'Lỗi!' });
                }
            }, 'json').fail(() => {
                btn.prop('disabled', false).html('<i class="fas fa-plus-circle mr-2"></i> BẮT ĐẦU NHẬP KHO');
                Toast.fire({ icon: 'error', title: 'Lỗi server!' });
            });
        });

        // Delete
        $(document).on('click', '.delete-stock-btn', function () {
            const id = $(this).data('id');
            Swal.fire({ title: 'Xác nhận xóa?', text: 'Mục này sẽ bị xóa vĩnh viễn khỏi kho!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonText: 'Hủy', confirmButtonText: 'Xác nhận xóa' })
                .then(r => {
                    if (!r.isConfirmed) return;
                    $.post(deleteUrl, { id: id }, function (res) {
                        if (res.success) {
                            $('#stock-row-' + id).fadeOut(300, function () { $(this).remove(); });
                            Toast.fire({ icon: 'success', title: 'Đã xóa' });
                        } else {
                            Toast.fire({ icon: 'error', title: res.message });
                        }
                    }, 'json');
                });
        });

        // Edit Modal
        $(document).on('click', '.edit-stock-btn', function () {
            $('#editId').val($(this).data('id'));
            $('#editContent').val($(this).data('content'));
            $('#editStockModal').modal('show');
        });

        // Save Edit
        $('#btnSaveEdit').on('click', function () {
            const id = $('#editId').val();
            const content = $('#editContent').val().trim();
            if (!content) return;

            const btn = $(this);
            btn.prop('disabled', true).text('Đang lưu...');
            $.post(updateUrl, { id: id, content: content }, function (res) {
                btn.prop('disabled', false).text('LƯU CẬP NHẬT');
                if (res.success) {
                    $('#editStockModal').modal('hide');
                    $(`#stock-row-${id} code`).text(content);
                    $(`#stock-row-${id} .copy-content-btn`).data('content', content);
                    $(`.edit-stock-btn[data-id="${id}"]`).data('content', content);
                    Toast.fire({ icon: 'success', title: 'Đã cập nhật' });
                } else {
                    Toast.fire({ icon: 'error', title: res.message });
                }
            }, 'json');
        });

        // Debounce search function
        let searchTimer;
        function smartSearch() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const status = $('#filterStatus').val();
                const search = $('#searchTerm').val().trim();

                // Show loading state in table
                $('#stockBody').css('opacity', '0.5');

                $.get(window.location.href, {
                    status_filter: status,
                    search: search
                }, function (res) {
                    $('#stockBody').css('opacity', '1');
                    if (res.success) {
                        renderStockTable(res.items);
                        renderStats(res.stats);
                    }
                }, 'json');
            }, 300);
        }

        function renderStockTable(items) {
            let html = '';
            if (!items || items.length === 0) {
                html = `<tr><td colspan="6" class="text-center py-5 text-muted"> <i class="fas fa-box-open fa-3x mb-3 d-block opacity-50"></i> Kho hiện đang trống</td></tr>`;
            } else {
                items.forEach(item => {
                    const rowClass = item.status === 'sold' ? 'table-light' : '';
                    const statusBadge = item.status === 'available'
                        ? '<span class="badge badge-success shadow-sm px-3 py-1">CÒN HÀNG</span>'
                        : '<span class="badge badge-secondary shadow-sm px-3 py-1">ĐÃ BÁN</span>';

                    let buyerHtml = '<span class="text-muted small"><i class="fas fa-minus mr-1"></i>Chưa bán</span>';
                    if (item.buyer_username) {
                        buyerHtml = `<a href="<?= url('admin/users/edit/') ?>${item.buyer_username}" class="d-inline-flex align-items-center text-primary font-weight-bold">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-2" style="width:24px; height:24px; font-size:10px;"><i class="fas fa-user"></i></div>
                                        ${escapeHtml(item.buyer_username)}
                                    </a>`;
                    }

                    let soldAtHtml = '';
                    if (item.status === 'sold' && item.sold_at) {
                        soldAtHtml = `<div class="mt-1 small text-center"><span class="text-danger font-weight-bold"><i class="far fa-clock mr-1"></i>Bán lúc:</span> <span class="text-muted">${formatDate(item.sold_at, item.sold_at_ts, item.sold_at_display)}</span></div>`;
                    }

                    const editTitle = item.status === 'available' ? 'Sửa nội dung' : 'Sửa nội dung (Dành cho bảo hành)';
                    const actionButtons = `
                        <div class="btn-group btn-group-sm">
                            <button class="btn ${item.status === 'available' ? 'btn-warning' : 'btn-light border'} edit-stock-btn"
                                data-id="${item.id}" data-content="${escapeHtml(item.content)}" title="${editTitle}">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${item.status === 'available' ? `<button class="btn btn-danger delete-stock-btn" data-id="${item.id}" title="Xóa"><i class="fas fa-trash"></i></button>` : ''}
                        </div>`;

                    html += `<tr id="stock-row-${item.id}" class="${rowClass}">
                        <td class="text-center align-middle"><span class="text-muted font-weight-bold">#${item.id}</span></td>
                        <td class="text-center align-middle">
                            <div class="d-flex align-items-center justify-content-center">
                                <code class="p-1 px-2 border rounded bg-white text-dark mr-2" style="font-size: 14px;">${escapeHtml(item.content)}</code>
                                <button class="btn btn-xs btn-outline-info copy-content-btn" data-content="${escapeHtml(item.content)}" title="Copy"><i class="far fa-copy"></i></button>
                            </div>
                            ${soldAtHtml}
                        </td>
                        <td class="text-center align-middle">${buyerHtml}</td>
                        <td class="text-center align-middle">${statusBadge}</td>
                        <td class="text-center align-middle"><span class="text-muted small">${formatDateShort(item.created_at, item.created_at_ts, item.created_at_display)}</span></td>
                        <td class="text-center align-middle">${actionButtons}</td>
                    </tr>`;
                });
            }
            $('#stockBody').html(html);
            $('.card-footer b').text(items.length);
        }

        function renderStats(stats) {
            $('#stat-total').text(stats.total);
            $('#stat-available').text(stats.available);
            $('#stat-sold').text(stats.sold);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateStr, tsSec, displayText) {
            if (!isNaN(Number(tsSec)) && Number(tsSec) > 0) {
                const d = new Date(Number(tsSec) * 1000);
                return d.getDate().toString().padStart(2, '0') + '/' + (d.getMonth() + 1).toString().padStart(2, '0') + '/' + d.getFullYear() + ' ' + d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0') + ':' + d.getSeconds().toString().padStart(2, '0');
            }
            if (displayText && window.KaiTime && typeof window.KaiTime.toTimestamp === 'function') {
                const tsDisplay = window.KaiTime.toTimestamp(displayText);
                if (!isNaN(tsDisplay) && tsDisplay > 0) return formatDate(null, tsDisplay, null);
            }
            if (!dateStr) return '—';
            if (window.KaiTime && typeof window.KaiTime.toTimestamp === 'function') {
                const ts = window.KaiTime.toTimestamp(dateStr);
                if (!isNaN(ts) && ts > 0) return formatDate(null, ts, null);
            }
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return String(dateStr);
            return d.getDate().toString().padStart(2, '0') + '/' + (d.getMonth() + 1).toString().padStart(2, '0') + '/' + d.getFullYear() + ' ' + d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0') + ':' + d.getSeconds().toString().padStart(2, '0');
        }

        function formatDateShort(dateStr, tsSec, displayText) {
            let d = null;
            if (!isNaN(Number(tsSec)) && Number(tsSec) > 0) {
                d = new Date(Number(tsSec) * 1000);
            } else if (displayText && window.KaiTime && typeof window.KaiTime.toTimestamp === 'function') {
                const tsDisplay = window.KaiTime.toTimestamp(displayText);
                if (!isNaN(tsDisplay) && tsDisplay > 0) d = new Date(tsDisplay * 1000);
            } else if (dateStr && window.KaiTime && typeof window.KaiTime.toTimestamp === 'function') {
                const ts = window.KaiTime.toTimestamp(dateStr);
                if (!isNaN(ts) && ts > 0) d = new Date(ts * 1000);
            }
            if (!d && dateStr) d = new Date(dateStr);
            if (!d || isNaN(d.getTime())) return dateStr || '—';
            const date = d.getDate().toString().padStart(2, '0') + '/' + (d.getMonth() + 1).toString().padStart(2, '0') + '/' + d.getFullYear();
            const time = d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
            return `${date}<br><span class="font-weight-bold">${time}</span>`;
        }

        // Apply filters (AJAX)
        $('#filterStatus').on('change', smartSearch);
        $('#searchTerm').on('input', smartSearch);
        $('#btnSearch').on('click', smartSearch);

        // Clean
        $('#btnClean').on('click', function () {
            Swal.fire({
                title: 'Dọn sạch kho?',
                text: 'Toàn bộ mục CHƯA BÁN sẽ bị xóa vĩnh viễn!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Đồng ý, Xóa hết',
                cancelButtonText: 'Hủy'
            }).then(r => {
                if (!r.isConfirmed) return;
                const btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>...');
                $.post(cleanUrl, {}, function (res) {
                    if (res.success) {
                        Toast.fire({ icon: 'success', title: res.message });
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        btn.prop('disabled', false).html('<i class="fas fa-eraser mr-1"></i>Dọn kho');
                        Toast.fire({ icon: 'error', title: res.message });
                    }
                }, 'json');
            });
        });
    });
</script>

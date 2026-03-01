<?php
/**
 * View: Telegram Bot — Outbox & Worker
 * Route: GET /admin/telegram/outbox
 */
require_once __DIR__ . '/../layout/head.php';
$pageTitle = 'Hàng đợi Outbox';
$breadcrumbs = [
    ['label' => 'Telegram Bot', 'url' => url('admin/telegram')],
    ['label' => 'Outbox & Worker'],
];
require_once __DIR__ . '/../layout/breadcrumb.php';

$pending = (int) ($stats['pending'] ?? 0);
$sent = (int) ($stats['sent'] ?? 0);
$failed = (int) ($stats['failed'] ?? 0);
?>

<section class="content pb-5 mt-1">
    <div class="container-fluid">
        <!-- Floating Stats Panel -->
        <div class="card custom-card bg-primary text-white border-0 shadow mb-4"
            style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;">
            <div class="card-body py-4">
                <div class="row align-items-center">
                    <div class="col-md-3 border-right border-secondary">
                        <div class="text-center">
                            <h6 class="text-muted font-weight-bold text-uppercase small mb-2">Trạng thái gửi</h6>
                            <h4 class="mb-0 font-weight-bold"><i class="fas fa-microchip mr-2 text-info"></i>Worker
                                Active</h4>
                        </div>
                    </div>
                    <div class="col-md-3 border-right border-secondary">
                        <div class="text-center">
                            <h6 class="text-muted font-weight-bold text-uppercase small mb-2">Đang chờ</h6>
                            <h3 class="mb-0 font-weight-bold text-warning"><?= number_format($pending) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 border-right border-secondary">
                        <div class="text-center">
                            <h6 class="text-muted font-weight-bold text-uppercase small mb-2">Đã hoàn thành</h6>
                            <h3 class="mb-0 font-weight-bold text-success"><?= number_format($sent) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted font-weight-bold text-uppercase small mb-2">Số lượt lỗi</h6>
                            <h3 class="mb-0 font-weight-bold text-danger"><?= number_format($failed) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card shadow-sm border-0">
            <div class="card-header border-0 bg-transparent py-4 d-flex justify-content-between align-items-center">
                <h3 class="card-title text-uppercase font-weight-bold mb-0">Hàng đợi tin nhắn</h3>
                <div class="card-tools">
                    <div class="btn-group mr-2">
                        <a href="<?= url('admin/telegram/outbox') ?>"
                            class="btn btn-sm btn-action <?= $statusFilter === '' ? 'btn-primary shadow' : 'btn-outline-primary' ?>">Tất
                            cả</a>
                        <a href="<?= url('admin/telegram/outbox?status=pending') ?>"
                            class="btn btn-sm btn-action <?= $statusFilter === 'pending' ? 'btn-warning shadow' : 'btn-outline-warning' ?>">Pending</a>
                        <a href="<?= url('admin/telegram/outbox?status=sent') ?>"
                            class="btn btn-sm btn-action <?= $statusFilter === 'sent' ? 'btn-success shadow' : 'btn-outline-success' ?>">Sent</a>
                        <a href="<?= url('admin/telegram/outbox?status=fail') ?>"
                            class="btn btn-sm btn-action <?= $statusFilter === 'fail' ? 'btn-danger shadow' : 'btn-outline-danger' ?>">Fail</a>
                    </div>

                    <button class="btn btn-sm btn-danger px-3 shadow-sm rounded-pill" onclick="deleteBatch()">
                        <i class="fas fa-trash-alt mr-1"></i> Xóa đã chọn
                    </button>
                    <?php if ($failed > 0): ?>
                        <button class="btn btn-sm btn-warning px-3 shadow-sm rounded-pill ml-2" id="btnRetryAll">
                            <i class="fas fa-redo mr-1"></i> Thử lại toàn bộ lỗi
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body px-0 pt-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100">
                        <thead class="bg-light">
                            <tr>
                                <th class="pl-4" style="width:40px;">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="checkAll">
                                        <label class="custom-control-label" for="checkAll"></label>
                                    </div>
                                </th>
                                <th>ID</th>
                                <th>Telegram ID</th>
                                <th>Nội dung</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Số lượt</th>
                                <th>Lỗi cuối</th>
                                <th class="text-right pr-4">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($messages)): ?>
                                <?php foreach ($messages as $msg): ?>
                                    <tr>
                                        <td class="pl-4">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input row-check"
                                                    id="chk_<?= $msg['id'] ?>" value="<?= $msg['id'] ?>">
                                                <label class="custom-control-label" for="chk_<?= $msg['id'] ?>"></label>
                                            </div>
                                        </td>
                                        <td class="font-weight-bold">#<?= $msg['id'] ?></td>
                                        <td><code><?= $msg['telegram_id'] ?></code></td>
                                        <td>
                                            <div class="text-muted small"
                                                style="max-width:300px; max-height: 2.4em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                                <?= htmlspecialchars(strip_tags($msg['message'])) ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $st = $msg['status'];
                                            $cls = match ($st) {
                                                'sent' => 'badge-success',
                                                'pending' => 'badge-warning shadow-sm',
                                                'fail' => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $cls ?> px-3 py-1"><?= strtoupper($st) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="font-weight-bold"><?= $msg['try_count'] ?></span>
                                        </td>
                                        <td>
                                            <small
                                                class="text-danger"><?= htmlspecialchars(mb_substr($msg['last_error'] ?? '', 0, 50)) ?></small>
                                        </td>
                                        <td class="text-right pr-4">
                                            <div class="btn-group">
                                                <?php if ($msg['status'] === 'fail'): ?>
                                                    <button class="btn btn-outline-warning btn-sm border-0" title="Thử lại"
                                                        onclick="retryIds('<?= $msg['id'] ?>')">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-danger btn-sm border-0" title="Xóa"
                                                    onclick="deleteIds('<?= $msg['id'] ?>')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">Hàng đợi trống</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<script>
    $('#checkAll').change(function () {
        $('.row-check').prop('checked', $(this).is(':checked'));
    });

    function getSelectedIds() {
        return $('.row-check:checked').map(function () { return $(this).val(); }).get().join(',');
    }

    function retryIds(ids) {
        $.post('<?= url('admin/telegram/outbox/retry') ?>', { ids: ids }, function (res) {
            SwalHelper.toast(res.message, res.success ? 'success' : 'error');
            if (res.success) setTimeout(() => location.reload(), 800);
        }, 'json');
    }

    function deleteIds(ids) {
        Swal.fire({
            title: 'Xác nhận xóa?', text: 'Hành động này không thể hoàn tác.', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#64748b',
            confirmButtonText: 'Xóa ngay', cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= url('admin/telegram/outbox/delete') ?>', { ids: ids }, function (res) {
                    SwalHelper.toast(res.message, res.success ? 'success' : 'error');
                    if (res.success) setTimeout(() => location.reload(), 800);
                }, 'json');
            }
        });
    }

    function deleteBatch() {
        const ids = getSelectedIds();
        if (!ids) { SwalHelper.toast('Vui lòng chọn ít nhất 1 tin nhắn', 'warning'); return; }
        deleteIds(ids);
    }

    $('#btnRetryAll').click(function () {
        retryIds('all_fails');
    });
</script>
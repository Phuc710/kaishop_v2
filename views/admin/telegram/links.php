<?php
/**
 * View: Telegram Bot — User Links
 * Route: GET /admin/telegram/links
 */
require_once __DIR__ . '/../layout/head.php';
$pageTitle = 'Liên kết người dùng — Telegram';
$breadcrumbs = [
    ['label' => 'Telegram Bot', 'url' => url('admin/telegram')],
    ['label' => 'User Links'],
];
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<section class="content pb-5 mt-1">
    <div class="container-fluid">
        <!-- Floating Stats Panel -->
        <div class="row mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card custom-card bg-primary text-white shadow-sm"
                    style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-white-50 p-2 rounded">
                                <i class="fas fa-link fa-lg"></i>
                            </div>
                            <div class="ml-3">
                                <h6 class="text-white-50 small font-weight-bold text-uppercase mb-1">Tổng liên kết</h6>
                                <h4 class="mb-0 font-weight-bold"><?= number_format(count($links)) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card shadow-sm border-0">
            <div class="card-header border-0 bg-transparent py-4 d-flex justify-content-between align-items-center">
                <h3 class="card-title text-uppercase font-weight-bold mb-0">Danh sách tài khoản đã liên kết</h3>
                <!-- Search -->
                <div style="width: 300px;">
                    <form method="GET" action="<?= url('admin/telegram/links') ?>">
                        <div class="input-group input-group-sm bg-light border rounded-pill px-2 py-1">
                            <input name="q" class="form-control border-0 bg-transparent"
                                placeholder="Tìm ID / Username / Email..." value="<?= htmlspecialchars($keyword) ?>">
                            <div class="input-group-append">
                                <button class="btn btn-link text-primary" type="submit"><i
                                        class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body px-0 pt-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100">
                        <thead class="bg-light text-uppercase small">
                            <tr>
                                <th class="pl-4 text-center">Web ID</th>
                                <th>Username Web</th>
                                <th class="text-center">Telegram ID</th>
                                <th>@Username TG</th>
                                <th class="text-center">Ngày liên kết</th>
                                <th class="text-center">Hoạt động cuối</th>
                                <th class="text-right pr-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($links)): ?>
                                <?php foreach ($links as $row): ?>
                                    <tr>
                                        <td class="pl-4 text-center font-weight-bold text-muted">
                                            #<?= $row['user_id'] ?>
                                        </td>
                                        <td class="font-weight-bold">
                                            <i class="fas fa-user-circle text-primary mr-1"></i>
                                            <?= htmlspecialchars($row['web_username'] ?? '—') ?>
                                        </td>
                                        <td class="text-center">
                                            <code class="px-2 py-1 bg-light rounded text-dark"><?= $row['telegram_id'] ?></code>
                                        </td>
                                        <td>
                                            <?php if ($row['telegram_username']): ?>
                                                <a href="https://t.me/<?= htmlspecialchars($row['telegram_username']) ?>"
                                                    target="_blank" class="font-weight-bold">
                                                    @<?= htmlspecialchars($row['telegram_username']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small"><em>Không có @username</em></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center small text-muted">
                                            <?= date('H:i d/m/Y', strtotime($row['linked_at'])) ?>
                                        </td>
                                        <td class="text-center small">
                                            <?php if ($row['last_active']): ?>
                                                <span class="text-success font-weight-bold">
                                                    <?= date('H:i d/m/Y', strtotime($row['last_active'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right pr-4">
                                            <button class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                                onclick="unlinkUser(<?= (int) $row['user_id'] ?>)">
                                                <i class="fas fa-unlink mr-1"></i> Hủy
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted opacity-50 mb-3">
                                            <i class="fas fa-link-slash fa-4x"></i>
                                        </div>
                                        <p class="text-muted font-italic mb-0">Chưa có dữ liệu liên kết nào phù hợp</p>
                                    </td>
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
    function unlinkUser(userId) {
        Swal.fire({
            title: 'Hủy liên kết?',
            text: 'Người dùng sẽ không thể sử dụng bot cho đến khi liên kết lại.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4b5c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Đồng ý hủy',
            cancelButtonText: 'Quay lại',
            background: '#fff',
            customClass: {
                confirmButton: 'px-4 rounded-pill',
                cancelButton: 'px-4 rounded-pill'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= url('admin/telegram/links/unlink') ?>', { user_id: userId }, function (res) {
                    SwalHelper.toast(res.message, res.success ? 'success' : 'error');
                    if (res.success) setTimeout(() => location.reload(), 800);
                }, 'json').fail(() => SwalHelper.toast('Lỗi kết nối server', 'error'));
            }
        });
    }
</script>
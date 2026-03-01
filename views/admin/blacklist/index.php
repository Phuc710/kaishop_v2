<?php
$pageTitle = 'Quan ly Blacklist';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('admin')],
    ['label' => 'Blacklist'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';

$tab = $tab ?? 'ip';
$search = $search ?? '';

$statusLabel = static function (bool $active, bool $expired = false): string {
    if ($active) {
        return '<span class="badge badge-danger">Active</span>';
    }
    if ($expired) {
        return '<span class="badge badge-secondary">Expired</span>';
    }
    return '<span class="badge badge-success">Released</span>';
};
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<section class="content pb-4 mt-2">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-danger"><i class="fas fa-network-wired"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">IP Dang Ban</span>
                        <span class="info-box-number"><?= number_format((int) $activeIp) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning"><i class="fas fa-mobile-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">Device Dang Ban</span>
                        <span class="info-box-number"><?= number_format((int) $activeDevice) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-primary"><i class="fas fa-user-shield"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold text-uppercase">Admin Ban Dang Hieu Luc</span>
                        <span class="info-box-number"><?= number_format((int) $adminActive) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-6 mb-2">
                        <label class="font-weight-bold small text-uppercase">Mo khoa bang REF</label>
                        <form action="<?= url('admin/blacklist/unban') ?>" method="POST" class="d-flex">
                            <?php if (function_exists('csrf_token')): ?>
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <?php endif; ?>
                            <input type="hidden" name="type" value="ref">
                            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                            <input type="text" name="ref" class="form-control mr-2" placeholder="Nhap REF..." required>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-unlock mr-1"></i>Mo khoa</button>
                        </form>
                    </div>
                    <div class="col-md-6 mb-2 text-md-right">
                        <form action="<?= url('admin/blacklist/clear-expired') ?>" method="POST" class="d-inline" onsubmit="return confirm('Don tat ca ban da het han?');">
                            <?php if (function_exists('csrf_token')): ?>
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-outline-danger"><i class="fas fa-broom mr-1"></i>Don ban het han</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($_GET['unban'])): ?>
            <div class="alert alert-success">Da mo khoa thanh cong.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['cleared'])): ?>
            <div class="alert alert-info">Da don cac lenh ban het han.</div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-white border-0">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?= $tab === 'ip' ? 'active' : '' ?>" href="<?= url('admin/blacklist?tab=ip') ?>">
                            IP Blacklist (<?= count($ipBans) ?>)
                        </a>
                    </li>
                    <li class="nav-item ml-2">
                        <a class="nav-link <?= $tab === 'device' ? 'active' : '' ?>" href="<?= url('admin/blacklist?tab=device') ?>">
                            Device Bans (<?= count($deviceBans) ?>)
                        </a>
                    </li>
                    <li class="nav-item ml-2">
                        <a class="nav-link <?= $tab === 'admin' ? 'active' : '' ?>" href="<?= url('admin/blacklist?tab=admin') ?>">
                            Admin Banned (<?= count($adminBans) ?>)
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" id="ban-search" class="form-control" placeholder="Tim nhanh..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <select id="ban-length" class="form-control">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <?php if ($tab === 'ip'): ?>
                        <table id="banTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th>IP</th>
                                    <th>REF</th>
                                    <th>Ly do</th>
                                    <th>Nguon</th>
                                    <th>Bat dau</th>
                                    <th>Het han</th>
                                    <th>Trang thai</th>
                                    <th>Thao tac</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ipBans as $ban): ?>
                                    <?php $active = empty($ban['expires_at']) || strtotime((string) $ban['expires_at']) > time(); ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars((string) ($ban['ip_address'] ?? '')) ?></code></td>
                                        <td><code><?= htmlspecialchars((string) ($ban['ref_hash'] ?? '')) ?></code></td>
                                        <td><?= htmlspecialchars((string) ($ban['reason'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($ban['source'] ?? 'antiflood')) ?></td>
                                        <td data-order="<?= strtotime((string) ($ban['banned_at'] ?? '')) ?: 0 ?>"><?= htmlspecialchars((string) ($ban['banned_at'] ?? '')) ?></td>
                                        <td><?= !empty($ban['expires_at']) ? htmlspecialchars((string) $ban['expires_at']) : '<span class="text-danger">Vinh vien</span>' ?></td>
                                        <td><?= $statusLabel($active, !$active) ?></td>
                                        <td>
                                            <form action="<?= url('admin/blacklist/unban') ?>" method="POST" onsubmit="return confirm('Mo khoa IP nay?');">
                                                <?php if (function_exists('csrf_token')): ?>
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <?php endif; ?>
                                                <input type="hidden" name="tab" value="ip">
                                                <input type="hidden" name="type" value="ip">
                                                <input type="hidden" name="id" value="<?= (int) ($ban['id'] ?? 0) ?>">
                                                <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-unlock"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($tab === 'device'): ?>
                        <table id="banTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Fingerprint</th>
                                    <th>User</th>
                                    <th>Ly do</th>
                                    <th>Nguon</th>
                                    <th>Ban boi</th>
                                    <th>Bat dau</th>
                                    <th>Het han</th>
                                    <th>Trang thai</th>
                                    <th>Thao tac</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deviceBans as $ban): ?>
                                    <?php $active = empty($ban['expires_at']) || strtotime((string) $ban['expires_at']) > time(); ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars(substr((string) ($ban['fingerprint_hash'] ?? ''), 0, 24)) ?></code></td>
                                        <td><?= htmlspecialchars((string) ($ban['target_username'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($ban['reason'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($ban['source'] ?? 'system')) ?></td>
                                        <td><?= htmlspecialchars((string) ($ban['banned_by'] ?? '')) ?></td>
                                        <td data-order="<?= strtotime((string) ($ban['created_at'] ?? '')) ?: 0 ?>"><?= htmlspecialchars((string) ($ban['created_at'] ?? '')) ?></td>
                                        <td><?= !empty($ban['expires_at']) ? htmlspecialchars((string) $ban['expires_at']) : '<span class="text-danger">Vinh vien</span>' ?></td>
                                        <td><?= $statusLabel($active, !$active) ?></td>
                                        <td>
                                            <form action="<?= url('admin/blacklist/unban') ?>" method="POST" onsubmit="return confirm('Mo khoa device nay?');">
                                                <?php if (function_exists('csrf_token')): ?>
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <?php endif; ?>
                                                <input type="hidden" name="tab" value="device">
                                                <input type="hidden" name="type" value="device">
                                                <input type="hidden" name="fingerprint" value="<?= htmlspecialchars((string) ($ban['fingerprint_hash'] ?? '')) ?>">
                                                <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-unlock"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <table id="banTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Loai</th>
                                    <th>Muc tieu</th>
                                    <th>Ly do</th>
                                    <th>Admin</th>
                                    <th>Bat dau</th>
                                    <th>Het han</th>
                                    <th>Trang thai</th>
                                    <th>Ket thuc</th>
                                    <th>Thao tac</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($adminBans as $ban): ?>
                                    <?php
                                    $isActive = (string) ($ban['status'] ?? '') === 'active' && (empty($ban['expires_at']) || strtotime((string) $ban['expires_at']) > time());
                                    $isExpired = (string) ($ban['status'] ?? '') === 'expired' || (!empty($ban['expires_at']) && strtotime((string) $ban['expires_at']) <= time());
                                    $target = (string) ($ban['target_username'] ?? '');
                                    if ($target === '' && !empty($ban['target_ip'])) {
                                        $target = (string) $ban['target_ip'];
                                    }
                                    if ($target === '' && !empty($ban['target_fingerprint'])) {
                                        $target = substr((string) $ban['target_fingerprint'], 0, 24);
                                    }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars(strtoupper((string) ($ban['scope'] ?? ''))) ?></td>
                                        <td><?= htmlspecialchars($target) ?></td>
                                        <td><?= htmlspecialchars((string) ($ban['reason'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($ban['banned_by'] ?? '')) ?></td>
                                        <td data-order="<?= strtotime((string) ($ban['started_at'] ?? '')) ?: 0 ?>"><?= htmlspecialchars((string) ($ban['started_at'] ?? '')) ?></td>
                                        <td><?= !empty($ban['expires_at']) ? htmlspecialchars((string) $ban['expires_at']) : '<span class="text-danger">Vinh vien</span>' ?></td>
                                        <td><?= $statusLabel($isActive, $isExpired) ?></td>
                                        <td><?= htmlspecialchars((string) ($ban['ended_at'] ?? '')) ?></td>
                                        <td>
                                            <?php if ($isActive && (string) ($ban['scope'] ?? '') === 'account' && !empty($ban['target_username'])): ?>
                                                <form action="<?= url('admin/blacklist/unban') ?>" method="POST" onsubmit="return confirm('Mo khoa tai khoan nay?');">
                                                    <?php if (function_exists('csrf_token')): ?>
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <?php endif; ?>
                                                    <input type="hidden" name="tab" value="admin">
                                                    <input type="hidden" name="type" value="account">
                                                    <input type="hidden" name="username" value="<?= htmlspecialchars((string) $ban['target_username']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-unlock"></i></button>
                                                </form>
                                            <?php elseif ($isActive && (string) ($ban['scope'] ?? '') === 'device' && !empty($ban['target_fingerprint'])): ?>
                                                <form action="<?= url('admin/blacklist/unban') ?>" method="POST" onsubmit="return confirm('Mo khoa device nay?');">
                                                    <?php if (function_exists('csrf_token')): ?>
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <?php endif; ?>
                                                    <input type="hidden" name="tab" value="admin">
                                                    <input type="hidden" name="type" value="device">
                                                    <input type="hidden" name="fingerprint" value="<?= htmlspecialchars((string) $ban['target_fingerprint']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-unlock"></i></button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(function () {
        var table = $('#banTable').DataTable({
            pageLength: 10,
            responsive: true,
            autoWidth: false,
            order: [[4, 'desc']],
            language: {
                sZeroRecords: 'Khong tim thay du lieu',
                sInfo: 'Xem _START_ - _END_ / _TOTAL_ muc',
                sInfoEmpty: 'Khong co du lieu',
                oPaginate: { sPrevious: '&lsaquo;', sNext: '&rsaquo;' }
            },
            dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-md-flex justify-content-end"p>>'
        });

        $('#ban-search').on('keyup input', function () {
            table.search($(this).val()).draw();
        });

        $('#ban-length').on('change', function () {
            table.page.len($(this).val()).draw();
        });

        <?php if ($search !== ''): ?>
        table.search(<?= json_encode($search, JSON_UNESCAPED_UNICODE) ?>).draw();
        <?php endif; ?>
    });
</script>

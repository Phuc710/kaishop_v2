<!DOCTYPE html>
<html lang="en">

<head>
    <?php require __DIR__ . '/../../../hethong/head2.php'; ?>
    <title>Nhật Ký Hệ Thống |
        <?= htmlspecialchars($chungapi['ten_web'] ?? 'Admin Panel'); ?>
    </title>
    <!-- MDB / Bootstrap -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css" rel="stylesheet" />
    <style>
        .log-table th {
            white-space: nowrap;
            font-weight: 600;
        }

        .log-payload {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
            color: #0d6efd;
        }
    </style>
</head>

<body style="background-color: #f4f6f9;">

    <?php require __DIR__ . '/../../../hethong/nav.php'; ?>

    <div class="container-fluid mt-4 mb-5">
        <div class="card shadow-0 border rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-dark font-weight-bold">
                    <i class="fas fa-shield-alt text-primary me-2"></i>NHẬT KÝ HỆ THỐNG
                </h5>
            </div>

            <div class="card-body">
                <!-- Bộ Lọc -->
                <form method="GET" action="" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Tìm kiếm user, IP, action..."
                            value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="severity" class="form-select">
                            <option value="">Tất cả mức độ</option>
                            <option value="INFO" <?= ($filters['severity'] ?? '') === 'INFO' ? 'selected' : '' ?>>INFO
                                (Thông tin)</option>
                            <option value="WARNING" <?= ($filters['severity'] ?? '') === 'WARNING' ? 'selected' : '' ?>>
                                WARNING (Cảnh báo)</option>
                            <option value="DANGER" <?= ($filters['severity'] ?? '') === 'DANGER' ? 'selected' : '' ?>>
                                DANGER (RED ALERT)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="module" class="form-select">
                            <option value="">Tất cả module</option>
                            <option value="Auth" <?= ($filters['module'] ?? '') === 'Auth' ? 'selected' : '' ?>>Auth
                            </option>
                            <option value="Billing" <?= ($filters['module'] ?? '') === 'Billing' ? 'selected' : '' ?>>
                                Billing</option>
                            <option value="Store" <?= ($filters['module'] ?? '') === 'Store' ? 'selected' : '' ?>>Store
                            </option>
                            <option value="Security" <?= ($filters['module'] ?? '') === 'Security' ? 'selected' : '' ?>>
                                Security</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Lọc
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table align-middle log-table table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>#ID</th>
                                <th>Thời gian</th>
                                <th>Mức độ</th>
                                <th>Module</th>
                                <th>Username</th>
                                <th>Hành động</th>
                                <th>Mô tả chi tiết</th>
                                <th>IP Address</th>
                                <th>Payload</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Không có dữ liệu nhật ký hệ thống.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <?= $log['id'] ?>
                                        </td>
                                        <td>
                                            <?= date('H:i d/m/Y', strtotime($log['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?php if ($log['severity'] === 'INFO'): ?>
                                                <span class="badge bg-info">INFO</span>
                                            <?php elseif ($log['severity'] === 'WARNING'): ?>
                                                <span class="badge bg-warning text-dark">WARNING</span>
                                            <?php elseif ($log['severity'] === 'DANGER'): ?>
                                                <span class="badge bg-danger pulse"><i class="fas fa-exclamation-triangle"></i>
                                                    DANGER</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars($log['severity']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border">
                                                <?= htmlspecialchars($log['module']) ?>
                                            </span></td>
                                        <td>
                                            <?php if ($log['username']): ?>
                                                <a href="<?= url('admin/users/edit/' . urlencode($log['username'])) ?>"
                                                    class="font-weight-bold text-dark">
                                                    <?= htmlspecialchars($log['username']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Guest</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="font-weight-500 text-dark">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </td>
                                        <td class="text-muted">
                                            <?= htmlspecialchars($log['description']) ?>
                                        </td>
                                        <td><span class="badge bg-soft-primary text-primary border-primary-light">
                                                <?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?>
                                            </span></td>
                                        <td>
                                            <?php if (!empty($log['payload'])): ?>
                                                <div class="log-payload"
                                                    onclick="showPayloadModal(<?= htmlspecialchars(json_encode($log['payload'])) ?>)">
                                                    <i class="fas fa-code me-1"></i> Xem JSON
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                    <a class="page-link"
                                        href="?page=<?= $i ?>&search=<?= urlencode($filters['search'] ?? '') ?>&severity=<?= urlencode($filters['severity'] ?? '') ?>&module=<?= urlencode($filters['module'] ?? '') ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Modal for JSON Payload -->
    <div class="modal fade" id="payloadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-bug text-danger me-2"></i>Chi Tiết Payload</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <pre><code id="payloadContent" class="text-dark"></code></pre>
                </div>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/../../../hethong/foot.php'; ?>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.js"></script>
    <script>
        function showPayloadModal(payloadData) {
            try {
                let parsed = typeof payloadData === 'string' ? JSON.parse(payloadData) : payloadData;
                document.getElementById('payloadContent').textContent = JSON.stringify(parsed, null, 4);
                new mdb.Modal(document.getElementById('payloadModal')).show();
            } catch (e) {
                document.getElementById('payloadContent').textContent = payloadData;
                new mdb.Modal(document.getElementById('payloadModal')).show();
            }
        }
    </script>
</body>

</html>
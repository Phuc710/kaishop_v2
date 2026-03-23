<?php
$pageTitle = 'Sửa Farm GPT';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/gpt-business/farms')],
    ['label' => 'Quản lý Farm', 'url' => url('admin/gpt-business/farms')],
    ['label' => 'Sửa farm'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$farm = $farm ?? [];
$error = $error ?? null;
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

    .form-section {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .form-section-title {
        font-weight: 800;
        color: #1e293b;
        text-transform: uppercase;
        font-size: 13px;
        margin-bottom: 16px;
        display: block;
        letter-spacing: 0.02em;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 8px;
    }

    .form-label-req::after {
        content: " *";
        color: #dc3545;
    }

    .gptb-mono-input {
        font-family: 'Courier New', Courier, monospace;
        letter-spacing: 0.05em;
    }

    .gptb-form-actions {
        padding-top: 20px;
        border-top: 1px solid #ebedf2;
        display: flex;
        gap: 10px;
    }
</style>

<section class="content pb-4 mt-1 admin-chatgpt-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="card custom-card gptb-form-card">
                    <div class="card-header gptb-card-header">
                        <span class="gptb-title-with-bar">CẬP NHẬT FARM</span>
                    </div>
                    <div class="card-body">
                        <form method="post"
                            action="<?= url('admin/gpt-business/farms/edit/' . (int) ($farm['id'] ?? 0)) ?>"
                            id="farmEditForm">
                            <div class="form-section">
                                <div class="form-section-title">Thông tin farm</div>
                                <div class="form-group">
                                    <label class="form-label-req">Tên Farm</label>
                                    <input type="text" name="farm_name" class="form-control"
                                        value="<?= htmlspecialchars($farm['farm_name'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label-req">Gmail admin</label>
                                    <input type="email" name="admin_email" class="form-control"
                                        value="<?= htmlspecialchars($farm['admin_email'] ?? '') ?>" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label>API Key mới</label>
                                    <div class="input-group">
                                        <input type="password" name="admin_api_key" class="form-control gptb-mono-input"
                                            id="editApiKeyInput" placeholder="Để trống nếu giữ nguyên"
                                            autocomplete="off">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary"
                                                id="toggleEditApiKeyBtn">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        Key hiện tại:
                                        <code><?= htmlspecialchars($farm['admin_api_key_masked'] ?? '***') ?></code>
                                    </small>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="form-section-title">Cấu hình vận hành</div>
                                <div class="form-row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Số slot user</label>
                                            <input type="number" name="seat_total" class="form-control"
                                                value="<?= (int) ($farm['seat_total'] ?? 4) ?>" min="1" max="20">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Trạng thái</label>
                                            <select name="status" class="form-control">
                                                <option value="active" <?= ($farm['status'] ?? '') === 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
                                                <option value="locked" <?= ($farm['status'] ?? '') === 'locked' ? 'selected' : '' ?>>Bị khóa</option>
                                                <option value="full" <?= ($farm['status'] ?? '') === 'full' ? 'selected' : '' ?>>Đã đầy</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="gptb-form-actions">
                                <button type="submit" class="btn btn-primary" id="saveFarmBtn">
                                    <i class="fas fa-save mr-1"></i> Lưu thay đổi
                                </button>
                                <a href="<?= url('admin/gpt-business/farms') ?>" class="btn btn-secondary">Hủy</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/foot.php'; ?>
<script>
    (function () {
        var input = document.getElementById('editApiKeyInput');
        var toggleButton = document.getElementById('toggleEditApiKeyBtn');
        var form = document.getElementById('farmEditForm');
        var saveButton = document.getElementById('saveFarmBtn');

        if (toggleButton && input) {
            toggleButton.addEventListener('click', function () {
                input.type = input.type === 'password' ? 'text' : 'password';
                toggleButton.innerHTML = input.type === 'password'
                    ? '<i class="fas fa-eye"></i>'
                    : '<i class="fas fa-eye-slash"></i>';
            });
        }

        if (form && saveButton) {
            form.addEventListener('submit', function () {
                saveButton.disabled = true;
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu...';
            });
        }
    })();
</script>
<?php
$pageTitle = 'Thêm Farm GPT';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/gpt-business/farms')],
    ['label' => 'Quản lý Farm', 'url' => url('admin/gpt-business/farms')],
    ['label' => 'Thêm farm'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';
$error = $error ?? null;
?>
<style>
    .admin-chatgpt-page .content-header {
        display: none;
    }

    .gptb-card-header-main {
        background: #fff !important;
        color: #212529 !important;
        border-bottom: 1px solid #ebedf2 !important;
        padding: 15px 20px !important;
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
                        <span class="gptb-title-with-bar">THÊM FARM GPT BUSINESS</span>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?= url('admin/gpt-business/farms/add') ?>" id="farmAddForm">
                            <div class="form-section">
                                <div class="form-section-title">Thông tin farm</div>
                                <div class="form-group">
                                    <label class="form-label-req">Tên Farm</label>
                                    <input type="text" name="farm_name" class="form-control"
                                        placeholder="VD: GPT Business Farm 01" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label-req">Gmail admin của farm</label>
                                    <input type="email" name="admin_email" class="form-control"
                                        placeholder="admin@gmail.com" required>
                                    <small class="form-text text-muted">Email tài khoản OpenAI Business đang vận hành
                                        farm này.</small>
                                </div>
                                <div class="form-group mb-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label-req mb-0">Admin API Key</label>
                                        <a href="https://platform.openai.com/settings/organization/admin-keys"
                                            target="_blank" class="btn btn-xs btn-link p-0" style="font-size: 12px;">
                                            <i class="fas fa-external-link-alt mr-1"></i> Lấy key tại OpenAI
                                        </a>
                                    </div>
                                    <div class="input-group">
                                        <input type="password" name="admin_api_key" class="form-control gptb-mono-input"
                                            id="apiKeyInput" placeholder="sk-admin-..." required autocomplete="off">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary"
                                                id="toggleApiKeyBtn">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Sử dụng <b>Admin Key</b> (sk-admin-...) để có
                                        đầy đủ quyền quản trị thành viên.</small>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="form-section-title">Cấu hình slot</div>
                                <div class="form-row">
                                    <div class="col-md-4">
                                        <div class="form-group mb-md-0">
                                            <label>Số slot user</label>
                                            <input type="number" name="seat_total" class="form-control" value="4"
                                                min="1" max="20">
                                        </div>
                                    </div>
                                </div>
                                <small class="form-text text-muted mt-2">Mặc định 4 slot user, không tính tài khoản
                                    admin của farm.</small>
                            </div>

                            <div class="alert alert-warning mb-4">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Hệ thống sẽ kiểm tra API key trước khi lưu. Nếu key không hợp lệ thì farm sẽ không được
                                tạo.
                            </div>

                            <div class="gptb-form-actions">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-plus-circle mr-1"></i> Xác nhận thêm farm
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
        var input = document.getElementById('apiKeyInput');
        var toggleButton = document.getElementById('toggleApiKeyBtn');
        var form = document.getElementById('farmAddForm');
        var submitButton = document.getElementById('submitBtn');

        if (toggleButton && input) {
            toggleButton.addEventListener('click', function () {
                input.type = input.type === 'password' ? 'text' : 'password';
                toggleButton.innerHTML = input.type === 'password'
                    ? '<i class="fas fa-eye"></i>'
                    : '<i class="fas fa-eye-slash"></i>';
            });
        }

        if (form && submitButton) {
            form.addEventListener('submit', function () {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang kiểm tra key...';
            });
        }
    })();
</script>
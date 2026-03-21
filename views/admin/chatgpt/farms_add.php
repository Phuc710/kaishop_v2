<?php
$pageTitle = 'Thêm Farm ChatGPT';
require __DIR__ . '/../layout/head.php';
$error = $error ?? null;
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col">
                <h1 class="m-0" style="font-size:1.3rem">➕ Thêm Farm ChatGPT Pro</h1>
            </div>
            <div class="col-auto"><a href="<?= url('admin/chatgpt/farms') ?>" class="btn btn-secondary btn-sm">← Quay
                    lại</a></div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-7">

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="card" style="background:#1e293b;border:1px solid #334155;border-radius:14px;">
                    <div class="card-body p-4">
                        <form method="post" action="<?= url('admin/chatgpt/farms/add') ?>" id="farmAddForm">
                            <div class="mb-3">
                                <label class="form-label" style="color:#e2e8f0;font-weight:600">Tên Farm *</label>
                                <input type="text" name="farm_name" class="form-control"
                                    style="background:#0f172a;border-color:#334155;color:#f1f5f9"
                                    placeholder="VD: GPT Business Farm 01" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="color:#e2e8f0;font-weight:600">Gmail của Admin (chủ
                                    farm) *</label>
                                <input type="email" name="admin_email" class="form-control"
                                    style="background:#0f172a;border-color:#334155;color:#f1f5f9"
                                    placeholder="admin@gmail.com" required>
                                <div class="form-text" style="color:#64748b">Email của tài khoản OpenAI Business đang
                                    chạy farm này</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="color:#e2e8f0;font-weight:600">Admin API Key *</label>
                                <input type="password" name="admin_api_key" class="form-control" id="apiKeyInput"
                                    style="background:#0f172a;border-color:#334155;color:#f1f5f9;font-family:monospace"
                                    placeholder="sk-admin-..." required autocomplete="off">
                                <div class="form-text" style="color:#64748b;">Lấy từ platform.openai.com → Settings →
                                    Admin Keys. Key sẽ được mã hóa trước khi lưu DB.</div>
                                <button type="button" onclick="toggleKey()"
                                    class="btn btn-sm btn-outline-secondary mt-1" style="font-size:.75rem">👁 Hiện/Ẩn
                                    Key</button>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="color:#e2e8f0;font-weight:600">Số slot user (không tính
                                    admin)</label>
                                <input type="number" name="seat_total" class="form-control"
                                    style="background:#0f172a;border-color:#334155;color:#f1f5f9;width:120px" value="4"
                                    min="1" max="20">
                                <div class="form-text" style="color:#64748b">Mặc định 4 (1 admin + 4 user = Business
                                    plan)</div>
                            </div>
                            <div class="alert"
                                style="background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.25);border-radius:10px;color:#fbbf24;font-size:.85rem;">
                                ⚠️ Hệ thống sẽ <strong>test API key</strong> trước khi lưu. Nếu key không hợp lệ, farm
                                sẽ không được tạo.
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="submitBtn"
                                    onclick="this.disabled=true;this.textContent='⏳ Đang kiểm tra key...';this.form.submit()">
                                    ✅ Xác nhận thêm Farm
                                </button>
                                <a href="<?= url('admin/chatgpt/farms') ?>" class="btn btn-secondary">Hủy</a>
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
    function toggleKey() {
        const inp = document.getElementById('apiKeyInput');
        inp.type = inp.type === 'password' ? 'text' : 'password';
    }
</script>
<?php
$pageTitle = 'Chỉnh sửa Farm';
require __DIR__ . '/../layout/head.php';
$farm = $farm ?? [];
$error = $error ?? null;
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col">
                <h1 class="m-0" style="font-size:1.3rem">✏️ Sửa Farm:
                    <?= htmlspecialchars($farm['farm_name'] ?? '') ?>
                </h1>
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
                        <form method="post" action="<?= url('admin/chatgpt/farms/edit/' . ($farm['id'] ?? 0)) ?>">
                            <div class="mb-3">
                                <label class="form-label" style="color:#e2e8f0;font-weight:600">Tên Farm *</label>
                                <input type="text" name="farm_name" class="form-control"
                                    style="background:#0f172a;border-color:#334155;color:#f1f5f9"
                                    value="<?= htmlspecialchars($farm['farm_name'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="color:#e2e8f0;font-weight:600">Gmail Admin *</label>
                                <input type="email" name="admin_email" class="form-control"
                                    style="background:#0f172a;border-color:#334155;color:#f1f5f9"
                                    value="<?= htmlspecialchars($farm['admin_email'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="color:#e2e8f0;font-weight:600">API Key mới (để trống
                                    nếu giữ nguyên)</label>
                                <input type="password" name="admin_api_key" class="form-control"
                                    style="background:#0f172a;border-color:#334155;color:#f1f5f9;font-family:monospace"
                                    placeholder="sk-admin-..." autocomplete="off">
                                <div class="form-text" style="color:#64748b">Key hiện tại:
                                    <code><?= htmlspecialchars($farm['admin_api_key_masked'] ?? '***') ?></code></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="color:#e2e8f0;font-weight:600">Số slot</label>
                                <input type="number" name="seat_total" class="form-control"
                                    style="background:#0f172a;border-color:#334155;color:#f1f5f9;width:120px"
                                    value="<?= (int) ($farm['seat_total'] ?? 4) ?>" min="1" max="20">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="color:#e2e8f0;font-weight:600">Trạng thái</label>
                                <select name="status" class="form-select"
                                    style="background:#0f172a;border-color:#334155;color:#f1f5f9;width:160px">
                                    <option value="active" <?= ($farm['status'] ?? '') === 'active' ? 'selected' : '' ?>
                                        >Active</option>
                                    <option value="locked" <?= ($farm['status'] ?? '') === 'locked' ? 'selected' : '' ?>
                                        >Locked</option>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
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
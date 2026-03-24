<?php
$pageTitle = 'Debug GPT Business';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/gpt-business/farms')],
    ['label' => 'Debug API'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$farms = $farms ?? [];
$selectedFarm = $selectedFarm ?? null;
$selectedFarmId = (int) ($selectedFarmId ?? 0);
$actionResult = $actionResult ?? null;
$allowedInvites = $allowedInvites ?? [];
$localMembers = $localMembers ?? [];
$localInvites = $localInvites ?? [];
$debugRoles = $debugRoles ?? ['reader', 'member', 'admin', 'owner'];
$debugMethods = $debugMethods ?? ['GET', 'POST', 'DELETE'];
$csrfToken = function_exists('csrf_token') ? csrf_token() : '';
$postedLimit = max(1, min(200, (int) ($_POST['limit'] ?? 50)));
$postedEmail = trim((string) ($_POST['email'] ?? ''));
$postedRole = trim((string) ($_POST['role'] ?? 'reader'));
$postedInviteId = trim((string) ($_POST['invite_id'] ?? ''));
$postedUserId = trim((string) ($_POST['user_id'] ?? ''));
$postedEndpoint = trim((string) ($_POST['endpoint'] ?? '/organization/invites?limit=10'));
$postedMethod = strtoupper(trim((string) ($_POST['request_method'] ?? 'GET')));
$postedBody = trim((string) ($_POST['request_body'] ?? ''));
?>

<style>
    .admin-chatgpt-page .content-header { display: none; }
    .gptb-debug-hero { border-radius: 24px; padding: 24px 26px; background: radial-gradient(circle at top right, rgba(59,130,246,.18), transparent 32%), linear-gradient(135deg, #0f172a 0%, #111827 48%, #1d4ed8 100%); color: #f8fafc; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18); }
    .gptb-debug-hero__eyebrow { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px; background: rgba(255,255,255,.12); font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .gptb-debug-hero__title { margin: 14px 0 8px; font-size: 28px; font-weight: 800; line-height: 1.2; }
    .gptb-debug-hero__text { margin: 0; color: rgba(248,250,252,.82); max-width: 720px; font-size: 14px; }
    .gptb-debug-hero__actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }
    .gptb-debug-btn-light { border-radius: 12px !important; border: 1px solid rgba(255,255,255,.18) !important; background: rgba(255,255,255,.12) !important; color: #fff !important; font-weight: 700 !important; padding: 10px 14px !important; }
    .gptb-debug-card { border: 0 !important; border-radius: 22px !important; box-shadow: 0 14px 32px rgba(15,23,42,.08) !important; overflow: hidden; }
    .gptb-debug-card .card-header { background: #fff !important; border-bottom: 1px solid #e2e8f0 !important; padding: 18px 22px !important; }
    .gptb-debug-card .card-body { padding: 22px !important; }
    .gptb-debug-title { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; }
    .gptb-debug-subtitle { margin: 4px 0 0; color: #64748b; font-size: 13px; }
    .gptb-debug-stack { display: flex; flex-direction: column; gap: 18px; }
    .gptb-debug-actions { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px; }
    .gptb-debug-actions form { margin: 0; }
    .gptb-debug-actions .btn { width: 100%; border-radius: 14px !important; font-weight: 700 !important; }
    .gptb-debug-field, .gptb-debug-select, .gptb-debug-textarea { border-radius: 14px !important; border: 1px solid #dbe4f0 !important; box-shadow: none !important; }
    .gptb-debug-textarea { min-height: 150px; font-family: Consolas, Monaco, monospace; font-size: 13px; }
    .gptb-debug-metric-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 12px; }
    .gptb-debug-metric { border-radius: 18px; border: 1px solid #e2e8f0; background: linear-gradient(180deg, #fff 0%, #f8fafc 100%); padding: 16px; }
    .gptb-debug-metric__label { display: block; margin-bottom: 8px; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .gptb-debug-metric__value { color: #0f172a; font-size: 22px; font-weight: 800; line-height: 1.2; }
    .gptb-debug-metric__meta { margin-top: 6px; color: #475569; font-size: 13px; }
    .gptb-debug-response-shell { border-radius: 22px; overflow: hidden; background: #020617; box-shadow: 0 20px 40px rgba(2,6,23,.24); }
    .gptb-debug-response-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 16px 18px; background: rgba(15,23,42,.92); border-bottom: 1px solid rgba(148,163,184,.18); }
    .gptb-debug-response-title { margin: 0; color: #f8fafc; font-weight: 800; font-size: 15px; }
    .gptb-debug-response-meta { display: flex; flex-wrap: wrap; gap: 8px; }
    .gptb-debug-note-list { margin: 0; padding-left: 18px; color: #cbd5e1; font-size: 13px; }
    .gptb-debug-response { background: #020617; color: #cbd5f5; border-radius: 18px; padding: 18px; overflow: auto; max-height: 520px; margin: 0; font-size: 12px; line-height: 1.55; }
    .gptb-debug-table-shell { border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden; }
    .gptb-debug-table { margin-bottom: 0 !important; }
    .gptb-debug-table thead th { background: #f8fafc !important; border-top: 0 !important; border-bottom: 1px solid #dbe4f0 !important; color: #475569 !important; font-size: 12px !important; font-weight: 800 !important; text-transform: uppercase !important; letter-spacing: .04em; padding: 14px 12px !important; vertical-align: middle !important; }
    .gptb-debug-table tbody td { border-top: 0 !important; border-bottom: 1px solid #edf2f7 !important; padding: 14px 12px !important; vertical-align: middle !important; color: #1e293b !important; font-size: 13px !important; background: #fff; }
    .gptb-debug-table tbody tr:last-child td { border-bottom: 0 !important; }
    .gptb-debug-code { display: inline-block; max-width: 240px; padding: 4px 8px; border-radius: 10px; background: #eff6ff; color: #1d4ed8; font-family: Consolas, Monaco, monospace; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; vertical-align: middle; }
    .gptb-debug-empty { padding: 48px 20px; border: 1px dashed #cbd5e1; border-radius: 20px; background: linear-gradient(180deg, #fff 0%, #f8fafc 100%); text-align: center; }
    .gptb-debug-empty__title { margin: 0 0 8px; color: #0f172a; font-size: 18px; font-weight: 800; }
    .gptb-debug-empty__text { margin: 0; color: #64748b; font-size: 14px; }
    .gptb-debug-table-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    @media (max-width: 991.98px) { .gptb-debug-hero__actions { justify-content: flex-start; margin-top: 16px; } }
    @media (max-width: 767.98px) { .gptb-debug-hero { padding: 20px; } .gptb-debug-card .card-body, .gptb-debug-card .card-header { padding-left: 16px !important; padding-right: 16px !important; } .gptb-debug-actions, .gptb-debug-metric-grid { grid-template-columns: 1fr; } .gptb-debug-response-head { flex-direction: column; align-items: flex-start; } }
</style>

<section class="content pb-4 mt-1 admin-chatgpt-page">
    <div class="container-fluid">
        <div class="gptb-debug-hero mb-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="gptb-debug-hero__eyebrow"><i class="fas fa-bug"></i> GPT Business Debug</span>
                    <h1 class="gptb-debug-hero__title">Bảng debug đầy đủ cho farm, invite, member và request OpenAI</h1>
                    <p class="gptb-debug-hero__text">Dùng trang này để test API quản trị, đồng bộ guard, kiểm tra seat usage, thao tác nhanh với snapshot nội bộ và gửi request tùy chỉnh trực tiếp đến OpenAI cho từng farm.</p>
                </div>
                <div class="col-lg-4">
                    <div class="gptb-debug-hero__actions">
                        <a href="<?= url('admin/gpt-business/farms') ?>" class="btn gptb-debug-btn-light"><i class="fas fa-server mr-1"></i> Quản lý farm</a>
                        <a href="<?= url('admin/gpt-business/logs') ?>" class="btn gptb-debug-btn-light"><i class="fas fa-clipboard-list mr-1"></i> Nhật ký</a>
                        <a href="<?= url('admin/gpt-business/invites') ?>" class="btn gptb-debug-btn-light"><i class="fas fa-envelope mr-1"></i> Snapshot invite</a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($actionResult): ?>
            <div class="gptb-debug-response-shell mb-4">
                <div class="gptb-debug-response-head">
                    <div>
                        <h2 class="gptb-debug-response-title">Kết quả action: <?= htmlspecialchars((string) ($actionResult['action'] ?? 'unknown')) ?></h2>
                        <div class="text-muted small" style="color:#94a3b8 !important;">
                            Farm: <?= htmlspecialchars((string) (($actionResult['farm']['name'] ?? '') . ' #' . ($actionResult['farm']['id'] ?? 0))) ?> |
                            Thời gian: <?= htmlspecialchars((string) ($actionResult['timestamp'] ?? '')) ?>
                        </div>
                    </div>
                    <div class="gptb-debug-response-meta">
                        <span class="badge badge-<?= !empty($actionResult['success']) ? 'success' : 'danger' ?>"><?= !empty($actionResult['success']) ? 'SUCCESS' : 'FAILED' ?></span>
                        <?php if (isset($actionResult['http_code']) && $actionResult['http_code'] !== null): ?>
                            <span class="badge badge-info">HTTP <?= (int) $actionResult['http_code'] ?></span>
                        <?php endif; ?>
                        <span class="badge badge-secondary"><?= htmlspecialchars((string) ($actionResult['farm']['status'] ?? '')) ?></span>
                    </div>
                </div>
                <?php if (!empty($actionResult['notes'])): ?>
                    <div style="padding:16px 18px 0; background:#020617;">
                        <ul class="gptb-debug-note-list">
                            <?php foreach ((array) $actionResult['notes'] as $note): ?>
                                <li><?= htmlspecialchars((string) $note) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <pre class="gptb-debug-response"><?= htmlspecialchars(json_encode($actionResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
            </div>
        <?php endif; ?>

        <?php if (empty($farms)): ?>
            <div class="gptb-debug-empty">
                <h2 class="gptb-debug-empty__title">Chưa có farm nào để debug</h2>
                <p class="gptb-debug-empty__text">Tạo farm trước tại trang quản lý farm rồi quay lại bảng debug.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-xl-4 mb-4">
                    <div class="gptb-debug-stack">
                        <div class="card gptb-debug-card">
                            <div class="card-header">
                                <h2 class="gptb-debug-title">Chọn farm</h2>
                                <p class="gptb-debug-subtitle">Mọi action bên dưới sẽ chạy trên farm đang chọn.</p>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="<?= url('admin/gpt-business/debug') ?>">
                                    <div class="form-group mb-3">
                                        <label for="farm_id" class="font-weight-bold">Farm hiện tại</label>
                                        <select name="farm_id" id="farm_id" class="form-control gptb-debug-select">
                                            <?php foreach ($farms as $farm): ?>
                                                <option value="<?= (int) $farm['id'] ?>" <?= (int) $farm['id'] === $selectedFarmId ? 'selected' : '' ?>>
                                                    #<?= (int) $farm['id'] ?> -
                                                    <?= htmlspecialchars((string) ($farm['farm_name'] ?? '')) ?> /
                                                    <?= htmlspecialchars((string) ($farm['status'] ?? '')) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-crosshairs mr-1"></i> Chuyển farm</button>
                                </form>
                            </div>
                        </div>

                        <div class="card gptb-debug-card">
                            <div class="card-header">
                                <h2 class="gptb-debug-title">Quick actions</h2>
                                <p class="gptb-debug-subtitle">Kiểm tra key, sync guard, sync seat hoặc đọc dữ liệu live từ OpenAI.</p>
                            </div>
                            <div class="card-body">
                                <div class="gptb-debug-actions">
                                    <?php foreach ([
                                        ['action' => 'validate_key', 'label' => 'Validate key', 'class' => 'btn-outline-primary', 'icon' => 'fa-key'],
                                        ['action' => 'sync_guard', 'label' => 'Guard sync', 'class' => 'btn-primary', 'icon' => 'fa-shield-alt'],
                                        ['action' => 'sync_seats', 'label' => 'Sync seats', 'class' => 'btn-outline-info', 'icon' => 'fa-chair'],
                                        ['action' => 'get_org', 'label' => 'Org info', 'class' => 'btn-outline-secondary', 'icon' => 'fa-building'],
                                        ['action' => 'list_invites', 'label' => 'Live invites', 'class' => 'btn-outline-warning', 'icon' => 'fa-envelope'],
                                        ['action' => 'list_users', 'label' => 'Live members', 'class' => 'btn-outline-dark', 'icon' => 'fa-users'],
                                    ] as $quickAction): ?>
                                        <form method="POST" action="<?= url('admin/gpt-business/debug?farm_id=' . $selectedFarmId) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="farm_id" value="<?= $selectedFarmId ?>">
                                            <input type="hidden" name="action" value="<?= htmlspecialchars($quickAction['action']) ?>">
                                            <?php if (in_array($quickAction['action'], ['list_invites', 'list_users'], true)): ?>
                                                <input type="hidden" name="limit" value="<?= $postedLimit ?>">
                                            <?php endif; ?>
                                            <button type="submit" class="btn <?= htmlspecialchars($quickAction['class']) ?>">
                                                <i class="fas <?= htmlspecialchars($quickAction['icon']) ?> mr-1"></i> <?= htmlspecialchars($quickAction['label']) ?>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>

                                <form method="POST" action="<?= url('admin/gpt-business/debug?farm_id=' . $selectedFarmId) ?>" class="mt-3">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="farm_id" value="<?= $selectedFarmId ?>">
                                    <input type="hidden" name="action" value="list_invites">
                                    <div class="input-group">
                                        <input type="number" min="1" max="200" name="limit" class="form-control gptb-debug-field" value="<?= $postedLimit ?>">
                                        <div class="input-group-append"><button type="submit" class="btn btn-outline-warning">Số lượng invite live</button></div>
                                    </div>
                                </form>

                                <form method="POST" action="<?= url('admin/gpt-business/debug?farm_id=' . $selectedFarmId) ?>" class="mt-2">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="farm_id" value="<?= $selectedFarmId ?>">
                                    <input type="hidden" name="action" value="list_users">
                                    <div class="input-group">
                                        <input type="number" min="1" max="200" name="limit" class="form-control gptb-debug-field" value="<?= $postedLimit ?>">
                                        <div class="input-group-append"><button type="submit" class="btn btn-outline-dark">Số lượng member live</button></div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card gptb-debug-card">
                            <div class="card-header">
                                <h2 class="gptb-debug-title">Tạo invite</h2>
                                <p class="gptb-debug-subtitle">Gọi trực tiếp OpenAI để gửi invite cho farm đang chọn.</p>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?= url('admin/gpt-business/debug?farm_id=' . $selectedFarmId) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="farm_id" value="<?= $selectedFarmId ?>">
                                    <input type="hidden" name="action" value="create_invite">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Email</label>
                                        <input type="email" name="email" class="form-control gptb-debug-field" value="<?= htmlspecialchars($postedEmail) ?>" placeholder="customer@example.com" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="font-weight-bold">Role</label>
                                        <select name="role" class="form-control gptb-debug-select">
                                            <?php foreach ($debugRoles as $role): ?>
                                                <option value="<?= htmlspecialchars((string) $role) ?>" <?= $postedRole === $role ? 'selected' : '' ?>><?= htmlspecialchars((string) $role) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-paper-plane mr-1"></i> Gửi invite</button>
                                </form>
                            </div>
                        </div>

                        <div class="card gptb-debug-card">
                            <div class="card-header">
                                <h2 class="gptb-debug-title">Action hủy / xóa</h2>
                                <p class="gptb-debug-subtitle">Thu hồi invite hoặc xóa member bằng OpenAI ID.</p>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?= url('admin/gpt-business/debug?farm_id=' . $selectedFarmId) ?>" class="mb-3">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="farm_id" value="<?= $selectedFarmId ?>">
                                    <input type="hidden" name="action" value="revoke_invite">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Invite ID</label>
                                        <input type="text" name="invite_id" class="form-control gptb-debug-field" value="<?= htmlspecialchars($postedInviteId) ?>" placeholder="invite-..." required>
                                    </div>
                                    <button type="submit" class="btn btn-danger btn-block"><i class="fas fa-ban mr-1"></i> Thu hồi invite</button>
                                </form>

                                <form method="POST" action="<?= url('admin/gpt-business/debug?farm_id=' . $selectedFarmId) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="farm_id" value="<?= $selectedFarmId ?>">
                                    <input type="hidden" name="action" value="remove_member">
                                    <div class="form-group">
                                        <label class="font-weight-bold">User ID</label>
                                        <input type="text" name="user_id" class="form-control gptb-debug-field" value="<?= htmlspecialchars($postedUserId) ?>" placeholder="user-..." required>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold">Email member (nếu biết)</label>
                                        <input type="email" name="member_email" class="form-control gptb-debug-field" placeholder="Dùng để cập nhật snapshot nhanh">
                                    </div>
                                    <button type="submit" class="btn btn-outline-danger btn-block mt-3"><i class="fas fa-user-times mr-1"></i> Xóa member</button>
                                </form>
                            </div>
                        </div>

                        <div class="card gptb-debug-card">
                            <div class="card-header">
                                <h2 class="gptb-debug-title">Custom request</h2>
                                <p class="gptb-debug-subtitle">Gửi request tùy chỉnh tới `https://api.openai.com/v1` bằng key của farm.</p>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?= url('admin/gpt-business/debug?farm_id=' . $selectedFarmId) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="farm_id" value="<?= $selectedFarmId ?>">
                                    <input type="hidden" name="action" value="custom_request">
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label class="font-weight-bold">Method</label>
                                            <select name="request_method" class="form-control gptb-debug-select">
                                                <?php foreach ($debugMethods as $method): ?>
                                                    <option value="<?= htmlspecialchars((string) $method) ?>" <?= $postedMethod === $method ? 'selected' : '' ?>><?= htmlspecialchars((string) $method) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-8">
                                            <label class="font-weight-bold">Endpoint</label>
                                            <input type="text" name="endpoint" class="form-control gptb-debug-field" value="<?= htmlspecialchars($postedEndpoint) ?>" placeholder="/organization/invites?limit=10" required>
                                        </div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold">JSON body</label>
                                        <textarea name="request_body" class="form-control gptb-debug-textarea" placeholder='{"email":"customer@example.com","role":"reader"}'><?= htmlspecialchars($postedBody) ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-dark btn-block mt-3"><i class="fas fa-terminal mr-1"></i> Chạy request tùy chỉnh</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-8 mb-4">
                    <?php if ($selectedFarm): ?>
                        <div class="card gptb-debug-card mb-4">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-start flex-wrap">
                                    <div>
                                        <h2 class="gptb-debug-title">
                                            <?= htmlspecialchars((string) ($selectedFarm['farm_name'] ?? '')) ?>
                                            <span class="text-muted">#<?= (int) ($selectedFarm['id'] ?? 0) ?></span>
                                        </h2>
                                        <p class="gptb-debug-subtitle">Admin: <?= htmlspecialchars((string) ($selectedFarm['admin_email'] ?? '')) ?></p>
                                    </div>
                                    <div class="mt-2 mt-md-0">
                                        <span class="badge badge-<?= (($selectedFarm['status'] ?? '') === 'locked') ? 'danger' : 'success' ?>">
                                            <?= htmlspecialchars((string) ($selectedFarm['status'] ?? 'unknown')) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="gptb-debug-metric-grid">
                                    <div class="gptb-debug-metric">
                                        <span class="gptb-debug-metric__label">Seat usage</span>
                                        <div class="gptb-debug-metric__value"><?= (int) ($selectedFarm['seat_used'] ?? 0) ?> / <?= (int) ($selectedFarm['seat_total'] ?? 0) ?></div>
                                        <div class="gptb-debug-metric__meta">Số seat đang được hệ thống ghi nhận cho farm.</div>
                                    </div>
                                    <div class="gptb-debug-metric">
                                        <span class="gptb-debug-metric__label">Last sync</span>
                                        <div class="gptb-debug-metric__value" style="font-size:18px;"><?= htmlspecialchars((string) ($selectedFarm['last_sync_at'] ?? 'Chưa đồng bộ')) ?></div>
                                        <div class="gptb-debug-metric__meta">Cập nhật bởi guard sync hoặc seat sync.</div>
                                    </div>
                                    <div class="gptb-debug-metric">
                                        <span class="gptb-debug-metric__label">Allowed invites</span>
                                        <div class="gptb-debug-metric__value"><?= count($allowedInvites) ?></div>
                                        <div class="gptb-debug-metric__meta">Nguồn dữ liệu hợp lệ dùng để guard đối chiếu.</div>
                                    </div>
                                    <div class="gptb-debug-metric">
                                        <span class="gptb-debug-metric__label">Snapshot live</span>
                                        <div class="gptb-debug-metric__value"><?= count($localMembers) ?> member / <?= count($localInvites) ?> invite</div>
                                        <div class="gptb-debug-metric__meta">Dữ liệu local đang lưu sau các lần đồng bộ trước.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card gptb-debug-card mb-4">
                            <div class="card-header">
                                <h2 class="gptb-debug-title">Allowed invites nội bộ</h2>
                                <p class="gptb-debug-subtitle">Danh sách invite được xem là hợp lệ để guard không tự thu hồi.</p>
                            </div>
                            <div class="card-body">
                                <?php if ($allowedInvites): ?>
                                    <div class="gptb-debug-table-shell">
                                        <div class="table-responsive">
                                            <table class="table gptb-debug-table">
                                                <thead>
                                                    <tr>
                                                        <th>Email</th>
                                                        <th>Order</th>
                                                        <th>Status</th>
                                                        <th>Invite ID</th>
                                                        <th>Created</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($allowedInvites as $invite): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars((string) ($invite['target_email'] ?? '')) ?></td>
                                                            <td>
                                                                <?php if (!empty($invite['order_code'])): ?>
                                                                    <span class="badge badge-light border">#<?= htmlspecialchars((string) $invite['order_code']) ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">Không gắn order</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-<?= (($invite['status'] ?? '') === 'accepted') ? 'success' : ((($invite['status'] ?? '') === 'revoked' || ($invite['status'] ?? '') === 'expired') ? 'danger' : 'warning') ?>">
                                                                    <?= htmlspecialchars((string) ($invite['status'] ?? 'pending')) ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($invite['invite_id'])): ?>
                                                                    <span class="gptb-debug-code"><?= htmlspecialchars((string) $invite['invite_id']) ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">Chưa map</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?= htmlspecialchars((string) ($invite['created_at'] ?? '')) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="gptb-debug-empty">
                                        <h3 class="gptb-debug-empty__title">Chưa có allowed invite</h3>
                                        <p class="gptb-debug-empty__text">Các invite tạo thủ công ở đây có thể bị guard thu hồi nếu không đi qua order hợp lệ.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card gptb-debug-card mb-4">
                            <div class="card-header">
                                <h2 class="gptb-debug-title">Snapshot invite</h2>
                                <p class="gptb-debug-subtitle">Dữ liệu invite đang lưu local, có thể thao tác revoke nhanh theo từng dòng.</p>
                            </div>
                            <div class="card-body">
                                <?php if ($localInvites): ?>
                                    <div class="gptb-debug-table-shell">
                                        <div class="table-responsive">
                                            <table class="table gptb-debug-table">
                                                <thead>
                                                    <tr>
                                                        <th>Email</th>
                                                        <th>Invite ID</th>
                                                        <th>Role</th>
                                                        <th>Status</th>
                                                        <th>Source</th>
                                                        <th>Thao tác</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($localInvites as $invite): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars((string) ($invite['email'] ?? '')) ?></td>
                                                            <td><span class="gptb-debug-code"><?= htmlspecialchars((string) ($invite['invite_id'] ?? '')) ?></span></td>
                                                            <td><?= htmlspecialchars((string) ($invite['role'] ?? 'reader')) ?></td>
                                                            <td>
                                                                <span class="badge badge-<?= (($invite['status'] ?? '') === 'pending') ? 'warning' : ((($invite['status'] ?? '') === 'accepted') ? 'success' : 'secondary') ?>">
                                                                    <?= htmlspecialchars((string) ($invite['status'] ?? 'unknown')) ?>
                                                                </span>
                                                            </td>
                                                            <td><?= htmlspecialchars((string) ($invite['source'] ?? '')) ?></td>
                                                            <td>
                                                                <div class="gptb-debug-table-actions">
                                                                    <form method="POST" action="<?= url('admin/gpt-business/debug?farm_id=' . $selectedFarmId) ?>">
                                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                                        <input type="hidden" name="farm_id" value="<?= $selectedFarmId ?>">
                                                                        <input type="hidden" name="action" value="revoke_invite">
                                                                        <input type="hidden" name="invite_id" value="<?= htmlspecialchars((string) ($invite['invite_id'] ?? '')) ?>">
                                                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-ban mr-1"></i> Revoke</button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="gptb-debug-empty">
                                        <h3 class="gptb-debug-empty__title">Snapshot invite trống</h3>
                                        <p class="gptb-debug-empty__text">Chạy `Guard sync` hoặc `Live invites` để cập nhật thêm dữ liệu cho farm.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card gptb-debug-card">
                            <div class="card-header">
                                <h2 class="gptb-debug-title">Snapshot member</h2>
                                <p class="gptb-debug-subtitle">Dữ liệu member local, hỗ trợ xóa nhanh bằng OpenAI user ID.</p>
                            </div>
                            <div class="card-body">
                                <?php if ($localMembers): ?>
                                    <div class="gptb-debug-table-shell">
                                        <div class="table-responsive">
                                            <table class="table gptb-debug-table">
                                                <thead>
                                                    <tr>
                                                        <th>Email</th>
                                                        <th>User ID</th>
                                                        <th>Role</th>
                                                        <th>Status</th>
                                                        <th>Source</th>
                                                        <th>Thao tác</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($localMembers as $member): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars((string) ($member['email'] ?? '')) ?></td>
                                                            <td><span class="gptb-debug-code"><?= htmlspecialchars((string) ($member['openai_user_id'] ?? '')) ?></span></td>
                                                            <td><?= htmlspecialchars((string) ($member['role'] ?? 'reader')) ?></td>
                                                            <td>
                                                                <span class="badge badge-<?= (($member['status'] ?? '') === 'active') ? 'success' : 'secondary' ?>">
                                                                    <?= htmlspecialchars((string) ($member['status'] ?? 'unknown')) ?>
                                                                </span>
                                                            </td>
                                                            <td><?= htmlspecialchars((string) ($member['source'] ?? '')) ?></td>
                                                            <td>
                                                                <div class="gptb-debug-table-actions">
                                                                    <form method="POST" action="<?= url('admin/gpt-business/debug?farm_id=' . $selectedFarmId) ?>">
                                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                                        <input type="hidden" name="farm_id" value="<?= $selectedFarmId ?>">
                                                                        <input type="hidden" name="action" value="remove_member">
                                                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) ($member['openai_user_id'] ?? '')) ?>">
                                                                        <input type="hidden" name="member_email" value="<?= htmlspecialchars((string) ($member['email'] ?? '')) ?>">
                                                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-user-times mr-1"></i> Remove</button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="gptb-debug-empty">
                                        <h3 class="gptb-debug-empty__title">Snapshot member trống</h3>
                                        <p class="gptb-debug-empty__text">Chạy `Guard sync` hoặc `Live members` để nhìn thấy thành viên local của farm.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="gptb-debug-empty">
                            <h2 class="gptb-debug-empty__title">Không xác định được farm</h2>
                            <p class="gptb-debug-empty__text">Chọn một farm hợp lệ ở cột bên trái để bắt đầu debug.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../layout/foot.php'; ?>

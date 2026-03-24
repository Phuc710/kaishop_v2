<?php
/**
 * Interactive API Debugger View
 */
?>
<div class="header">
    <div class="header-body">
        <div class="row align-items-end">
            <div class="col">
                <h6 class="header-pretitle">GPT Business</h6>
                <h1 class="header-title">API Debugger Tool</h1>
            </div>
            <div class="col-auto">
                <a href="<?= url('admin/gpt-business/logs') ?>" class="btn btn-white">Xem Logs</a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <?php if (isset($actionResult)): ?>
        <div class="card bg-dark border-primary mb-5" style="border-width: 2px;">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-header-title text-primary">LATEST API RESPONSE</h4>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-<?= (($actionResult['_http_code'] ?? 0) < 300) ? 'success' : 'danger' ?>">
                            HTTP
                            <?= $actionResult['_http_code'] ?? '???' ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <pre
                    style="color: #60a5fa; max-height: 400px; overflow: auto;"><?= htmlspecialchars(json_encode($actionResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php foreach ($farms as $farm): ?>
            <?php $fid = (int) $farm['id']; ?>
            <div class="col-12 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-header-title">
                            <?= htmlspecialchars($farm['farm_name']) ?>
                            <span class="text-muted small ml-2">(
                                <?= htmlspecialchars($farm['admin_email']) ?> | ID:
                                <?= $fid ?>)
                            </span>
                        </h4>
                        <span class="badge bg-success-soft">
                            <?= htmlspecialchars($farm['status']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-4">
                            <div class="col-4">
                                <form method="POST">
                                    <input type="hidden" name="farm_id" value="<?= $fid ?>">
                                    <input type="hidden" name="action" value="get_org">
                                    <button type="submit" class="btn btn-outline-white w-100">🏢 Org Info</button>
                                </form>
                            </div>
                            <div class="col-4">
                                <form method="POST">
                                    <input type="hidden" name="farm_id" value="<?= $fid ?>">
                                    <input type="hidden" name="action" value="list_invites">
                                    <button type="submit" class="btn btn-outline-white w-100">📧 Invites</button>
                                </form>
                            </div>
                            <div class="col-4">
                                <form method="POST">
                                    <input type="hidden" name="farm_id" value="<?= $fid ?>">
                                    <input type="hidden" name="action" value="list_users">
                                    <button type="submit" class="btn btn-outline-white w-100">👥 Members</button>
                                </form>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Create Invite -->
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="farm_id" value="<?= $fid ?>">
                            <input type="hidden" name="action" value="create_invite">
                            <div class="input-group">
                                <input type="email" name="email" class="form-control" placeholder="Email khách hàng..."
                                    required>
                                <button type="submit" class="btn btn-primary">Send Invite</button>
                            </div>
                        </form>

                        <!-- Revoke Invite -->
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="farm_id" value="<?= $fid ?>">
                            <input type="hidden" name="action" value="revoke_invite">
                            <div class="input-group">
                                <input type="text" name="invite_id" class="form-control"
                                    placeholder="OpenAI Invite ID (invite-...)" required>
                                <button type="submit" class="btn btn-danger">Revoke</button>
                            </div>
                        </form>

                        <!-- Remove Member -->
                        <form method="POST">
                            <input type="hidden" name="farm_id" value="<?= $fid ?>">
                            <input type="hidden" name="action" value="remove_member">
                            <div class="input-group">
                                <input type="text" name="user_id" class="form-control"
                                    placeholder="OpenAI User ID (user-...)" required>
                                <button type="submit" class="btn btn-danger">Kick Member</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
/**
 * ===================================================
 * OpenAI Team Business Manager
 * Author: KaiShop Tool
 * Dùng: OpenAI Admin API (sk-admin-...)
 * Requires: PHP 8.0+, cURL enabled
 * ===================================================
 */

// ============================================================
// ⚙️ CẤU HÌNH — Chỉnh tại đây
// ============================================================

// Email của admin (chính là anh) — người duy nhất được phép mời
define('ADMIN_EMAIL', 'kaiteam01@kaishop.id.vn'); 

// Tự động quét và kick ngay khi load trang?
define('AUTO_SCAN_ON_LOAD', true);

// Giới hạn slot mặc định cho Farm 5 (1 Chủ + 4 Member)
if (!isset($_SESSION['max_slots'])) {
    $_SESSION['max_slots'] = 5; 
}
define('MAX_TEAM_SLOTS', (int)$_SESSION['max_slots']);

// ============================================================
session_start();

// ─── Auth check đơn giản ────────────────────────────────────
define('TOOL_PASSWORD', 'admin123'); // Đổi mật khẩu này!
if (!isset($_SESSION['kai_logged_in'])) {
    if (isset($_POST['tool_password'])) {
        if ($_POST['tool_password'] === TOOL_PASSWORD) {
            $_SESSION['kai_logged_in'] = true;
        } else {
            $loginError = 'Sai mật khẩu!';
        }
    }
    if (!isset($_SESSION['kai_logged_in'])) {
        showLoginForm($loginError ?? null);
        exit;
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ─── Helpers ────────────────────────────────────────────────

function openaiRequest(string $method, string $endpoint, array $body = []): array
{
    $url = 'https://api.openai.com/v1' . $endpoint;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . OPENAI_ADMIN_KEY,
            'Content-Type: application/json',
        ],
    ]);
    if (!empty($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($raw, true) ?: [];
    $data['_http_code'] = $code;
    return $data;
}

function getSlots(): array
{
    $res = openaiRequest('GET', '/organization/invites');
    $pending = count($res['data'] ?? []);
    // OpenAI Business mặc định 250 slot, tuỳ plan
    return ['pending_invites' => $pending];
}

function listPendingInvites(): array
{
    $res = openaiRequest('GET', '/organization/invites');
    return $res['data'] ?? [];
}

function listMembers(): array
{
    $res = openaiRequest('GET', '/organization/users');
    return $res['data'] ?? [];
}

function inviteMember(string $email, string $role = 'reader'): array
{
    return openaiRequest('POST', '/organization/invites', [
        'email' => $email,
        'role' => $role,
    ]);
}

function revokeInvite(string $inviteId): array
{
    return openaiRequest('DELETE', '/organization/invites/' . $inviteId);
}

function removeUser(string $userId): array
{
    return openaiRequest('DELETE', '/organization/users/' . $userId);
}

function getOrgInfo(): array
{
    // Thường endpoint này không public hoàn toàn nhưng ta có thể lấy qua list users (lấy org_id từ 1 node)
    $res = openaiRequest('GET', '/organization/users?limit=1');
    // OpenAI Admin API không trả về org_name trực tiếp ở đây, 
    // nhưng ta có thể hiển thị ID và các thông số tổng hợp.
    return [
        'id' => $_SESSION['org_id'] ?? 'N/A',
        'name' => $_SESSION['org_name'] ?? 'OpenAI Business Farm',
    ];
}

function getAuditLogs(int $limit = 50): array
{
    $res = openaiRequest('GET', '/organization/audit_logs?limit=' . $limit . '&event_type=invite.sent');
    return $res['data'] ?? [];
}

function scanAndKickUnauthorized(array &$log): void
{
    $logs = getAuditLogs(100);
    $members = listMembers();
    $invites = listPendingInvites();

    $adminEmail = strtolower(ADMIN_EMAIL);
    $foundViolation = false;

    foreach ($logs as $entry) {
        $actor = strtolower($entry['actor']['email'] ?? '');
        $target = strtolower($entry['payload']['invite']['email'] ?? '');

        // Nếu actor không phải admin
        if ($actor && $actor !== $adminEmail) {
            $foundViolation = true;
            $log[] = "⚠️ Phát hiện lệnh mời lậu từ: <b>{$actor}</b> (mời {$target})";

            // 1. Kiểm tra xem target còn trong pending invites không để Revoke
            foreach ($invites as $inv) {
                if (strtolower($inv['email'] ?? '') === $target) {
                    revokeInvite($inv['id']);
                    $log[] = "🛡️ Đã REVOKE lời mời tới: <b>{$target}</b>";
                }
            }

            // 2. Kiểm tra xem target đã vào team chưa để Kick
            foreach ($members as $m) {
                if (strtolower($m['email'] ?? '') === $target) {
                    removeUser($m['id']);
                    $log[] = "🛡️ Đã KICK thành viên lậu: <b>{$target}</b>";
                }
            }

            // 3. Kick luôn thằng inviter lậu
            foreach ($members as $m) {
                if (strtolower($m['email'] ?? '') === $actor && $actor !== $adminEmail) {
                    removeUser($m['id']);
                    $log[] = "🚨 Đã KICK kẻ mời lậu: <b>{$actor}</b>";
                }
            }
        }
    }

    if (!$foundViolation) {
        $log[] = "✅ Không phát hiện lời mời lậu nào trong 100 log gần nhất.";
    }
}

function reconcile(array $whitelist, array &$log): void
{
    if (empty($whitelist))
        return;
    $members = listMembers();
    foreach ($members as $m) {
        $email = strtolower($m['email'] ?? '');
        if (!in_array($email, array_map('strtolower', $whitelist))) {
            removeUser($m['id']);
            $log[] = "🔴 Đã REMOVE user ngoài whitelist: <b>{$m['email']}</b>";
        }
    }
    // Invites pending
    $invites = listPendingInvites();
    foreach ($invites as $inv) {
        $email = strtolower($inv['email'] ?? '');
        if (!in_array($email, array_map('strtolower', $whitelist))) {
            revokeInvite($inv['id']);
            $log[] = "🟡 Đã REVOKE invite ngoài whitelist: <b>{$inv['email']}</b>";
        }
    }
}

// ─── Handle POST actions ─────────────────────────────────────

$actionLog = [];
$actionResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'invite') {
        $email = trim($_POST['email'] ?? '');
        $role = 'reader'; // Mặc định luôn là Member (reader)
        if ($email) {
            $r = inviteMember($email, $role);
            $code = $r['_http_code'] ?? 0;
            if ($code >= 200 && $code < 300) {
                $actionLog[] = "✅ Đã mời thành công: <b>{$email}</b> (Role: Member)";
            } else {
                $actionLog[] = "❌ Lỗi khi mời {$email}: " . htmlspecialchars(json_encode($r));
            }
        }

    } elseif ($action === 'audit_scan') {
        scanAndKickUnauthorized($actionLog);

    } elseif ($action === 'set_limit') {
        $limit = (int)($_POST['limit'] ?? 5);
        $_SESSION['max_slots'] = $limit;
        $actionLog[] = "⚙️ Đã cập nhật giới hạn Slot thành: <b>{$limit}</b>";

    } elseif ($action === 'revoke') {
        $inviteId = trim($_POST['invite_id'] ?? '');
        if ($inviteId) {
            $r = revokeInvite($inviteId);
            $actionLog[] = "🟡 Đã revoke invite ID: <b>{$inviteId}</b>";
        }
    }
}

// ─── Auto Trigger Security Scan ──────────────────────────────
if (AUTO_SCAN_ON_LOAD && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    scanAndKickUnauthorized($actionLog);
}

    } elseif ($action === 'kick') {
        $userId = trim($_POST['user_id'] ?? '');
        if ($userId) {
            $r = removeUser($userId);
            $actionLog[] = "🔴 Đã kick user ID: <b>{$userId}</b>";
        }

    } elseif ($action === 'reconcile') {
        // Tương thích ngược nếu anh vẫn muốn dùng whitelist
        $w = defined('WHITELIST') ? WHITELIST : [];
        reconcile($w, $actionLog);
    }
}

// ─── Fetch data ───────────────────────────────────────────────
$pendingInvites = listPendingInvites();
$members = listMembers();
$slots = getSlots();

// ─── HTML Output ─────────────────────────────────────────────
function showLoginForm(?string $error): void
{
    $errorHtml = $error ? "<p class='err'>{$error}</p>" : '';
    echo <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>OpenAI Team Manager — Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#0f172a;display:flex;align-items:center;justify-content:center;min-height:100vh;}
  .card{background:#1e293b;border-radius:16px;padding:40px 36px;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,.5);}
  h2{color:#f1f5f9;margin-bottom:24px;font-size:20px;text-align:center;}
  input{width:100%;padding:12px 14px;border-radius:10px;border:1px solid #334155;background:#0f172a;color:#f1f5f9;font-size:15px;margin-bottom:14px;}
  button{width:100%;padding:12px;border-radius:10px;border:none;background:#1494a9;color:#fff;font-size:15px;font-weight:700;cursor:pointer;}
  .err{color:#f87171;font-size:13px;margin-bottom:10px;text-align:center;}
</style>
</head>
<body>
<div class="card">
  <h2>🔐 OpenAI Team Manager</h2>
  <form method="post">
    {$errorHtml}
    <input type="password" name="tool_password" placeholder="Mật khẩu công cụ..." autofocus>
    <button type="submit">Đăng nhập</button>
  </form>
</div>
</body>
</html>
HTML;
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>OpenAI Team Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }

        header {
            background: #1e293b;
            border-bottom: 1px solid #334155;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        header h1 {
            font-size: 18px;
            font-weight: 800;
            color: #38bdf8;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn {
            background: #334155;
            border: none;
            color: #94a3b8;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
        }

        .logout-btn:hover {
            background: #475569;
            color: #e2e8f0;
        }

        main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 28px 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }

        @media(max-width:700px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            background: #1e293b;
            border-radius: 14px;
            border: 1px solid #334155;
            padding: 20px 24px;
        }

        .stat-card h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: .5px;
            margin-bottom: 8px;
        }

        .stat-card .val {
            font-size: 32px;
            font-weight: 900;
            color: #38bdf8;
        }

        section {
            background: #1e293b;
            border-radius: 14px;
            border: 1px solid #334155;
            padding: 22px 24px;
            margin-bottom: 24px;
        }

        section h2 {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .form-row input,
        .form-row select {
            flex: 1;
            min-width: 180px;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #334155;
            background: #0f172a;
            color: #e2e8f0;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all .2s;
            white-space: nowrap;
        }

        .btn-primary {
            background: #1494a9;
            color: #fff;
        }

        .btn-primary:hover {
            background: #0e7793;
        }

        .btn-danger {
            background: #dc2626;
            color: #fff;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-warning {
            background: #d97706;
            color: #fff;
        }

        .btn-warning:hover {
            background: #b45309;
        }

        .btn-purple {
            background: #7c3aed;
            color: #fff;
        }

        .btn-purple:hover {
            background: #6d28d9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #334155;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #1e293b;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover td {
            background: #1e3a4a20;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-owner {
            background: #7c3aed33;
            color: #a78bfa;
        }

        .badge-reader {
            background: #06406633;
            color: #38bdf8;
        }

        .badge-pending {
            background: #b4570833;
            color: #fbbf24;
        }

        .log-box {
            background: #0f172a;
            border-radius: 10px;
            padding: 14px;
            font-size: 13px;
            margin-bottom: 16px;
            border: 1px solid #334155;
        }

        .log-box p {
            padding: 4px 0;
            line-height: 1.6;
        }

        .empty {
            color: #475569;
            font-size: 13px;
            text-align: center;
            padding: 20px;
        }

        .email-col {
            word-break: break-all;
            max-width: 240px;
        }
    </style>
</head>

<body>
    <header>
        <h1>🤖 OpenAI Team Manager</h1>
        <form method="post" style="display:inline">
            <button type="submit" name="logout" value="1" class="logout-btn">Đăng xuất</button>
        </form>
    </header>
    <main>

        <?php if (!empty($actionLog)): ?>
            <div class="log-box">
                <?php foreach ($actionLog as $line): ?>
                    <p>
                        <?= $line ?>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Farm Info & Stats -->
        <div class="grid">
            <div class="stat-card">
                <h3>🏢 Thông tin Farm</h3>
                <div style="font-size: 14px; margin-top: 10px; color: #94a3b8;">
                    <p>ID: <span style="color:#e2e8f0"><?= substr(OPENAI_ADMIN_KEY, 0, 15) ?>...</span></p>
                    <p>Admin: <span style="color:#38bdf8"><?= ADMIN_EMAIL ?></span></p>
                </div>
                <form method="post" style="margin-top: 15px; display: flex; gap: 5px;">
                    <input type="hidden" name="action" value="set_limit">
                    <input type="number" name="limit" value="<?= MAX_TEAM_SLOTS ?>" style="width: 70px; padding: 5px; font-size: 12px;">
                    <button type="submit" class="btn btn-primary" style="padding: 5px 10px; font-size: 11px;">Sửa giới hạn</button>
                </form>
            </div>
            <div class="stat-card">
                <h3>� Tình trạng Slot (Real-time)</h3>
                <div class="val" style="display: flex; align-items: baseline; gap: 8px;">
                    <?= MAX_TEAM_SLOTS - (count($members) + count($pendingInvites)) ?>
                    <span style="font-size: 14px; color: #64748b; font-weight: 400;">/ <?= MAX_TEAM_SLOTS ?> còn trống</span>
                </div>
                <div style="margin-top: 12px; height: 6px; background: #334155; border-radius: 3px; overflow: hidden;">
                    <?php 
                        $used = count($members) + count($pendingInvites);
                        $percent = ($used / MAX_TEAM_SLOTS) * 100;
                    ?>
                    <div style="width: <?= $percent ?>%; height: 100%; background: <?= $percent > 90 ? '#ef4444' : '#1494a9' ?>;"></div>
                </div>
                <p style="font-size: 11px; color: #64748b; margin-top: 5px;">
                    Đã dùng: <?= count($members) ?> member + <?= count($pendingInvites) ?> pending = <?= $used ?> slot.
                </p>
            </div>
        </div>

        <!-- Invite -->
        <section>
            <h2>✉️ Invite thành viên mới (Mặc định Member)</h2>
            <form method="post">
                <input type="hidden" name="action" value="invite">
                <div class="form-row">
                    <input type="email" name="email" placeholder="Nhập Gmail người muốn mời..." required
                        style="flex: 2;">
                    <button type="submit" class="btn btn-primary">➕ Gửi lời mời ngay</button>
                </div>
            </form>
        </section>

        <!-- Audit Scan -->
        <section>
            <h2>🛡️ Bảo mật Team</h2>
            <div style="display: flex; gap: 10px; align-items: center;">
                <form method="post">
                    <input type="hidden" name="action" value="audit_scan">
                    <button type="submit" class="btn btn-danger" style="background: #e11d48;">🧹 Quét và Kick kẻ mời
                        lậu</button>
                </form>
                <p style="color:#94a3b8;font-size:13px;">
                    Hệ thống sẽ lục lại 100 log gần nhất, nếu ai không phải <code><?= ADMIN_EMAIL ?></code> mà thực hiện
                    mời -> KICK & REVOKE ngay.
                </p>
            </div>
        </section>

        <!-- Danh sách thành viên -->
        <section>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="margin-bottom: 0;">👥 Danh sách thành viên</h2>
                <button onclick="window.location.reload()" class="btn" style="background:transparent; border:1px solid #334155; color:#94a3b8; padding:5px 10px; font-size:12px;">🔄 Làm mới</button>
            </div>
            <?php if (empty($members)): ?>
                <p class="empty">Chưa có thành viên nào.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Tên / Email</th>
                                <th>Role</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $index => $m): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="email-col">
                                        <div style="font-weight: 600; color: #f1f5f9;"><?= htmlspecialchars($m['name'] ?: 'No Name') ?></div>
                                        <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($m['email'] ?? '--') ?></div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $m['role'] ?? 'reader' ?>">
                                            <?= ucfirst($m['role'] ?? '--') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: #05966922; color: #34d399;">Active</span>
                                    </td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Kick user này?')" style="display:inline">
                                            <input type="hidden" name="action" value="kick">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($m['id'] ?? '') ?>">
                                            <button type="submit" class="btn btn-danger"
                                                style="padding:6px 12px;font-size:12px;">Kick</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Pending Invites -->
        <section>
            <h2>📨 Pending Invites</h2>
            <?php if (empty($pendingInvites)): ?>
                <p class="empty">Không có invite nào đang chờ.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Ngày mời</th>
                                <th>Hết hạn</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingInvites as $inv): ?>
                                <tr>
                                    <td class="email-col">
                                        <?= htmlspecialchars($inv['email'] ?? '--') ?>
                                    </td>
                                    <td><span class="badge badge-pending">
                                            <?= ucfirst($inv['role'] ?? '--') ?>
                                        </span></td>
                                    <td>
                                        <?= isset($inv['invited_at']) ? date('d/m/Y H:i', $inv['invited_at']) : '--' ?>
                                    </td>
                                    <td>
                                        <?= isset($inv['expires_at']) ? date('d/m/Y H:i', $inv['expires_at']) : '--' ?>
                                    </td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Revoke invite này?')"
                                            style="display:inline">
                                            <input type="hidden" name="action" value="revoke">
                                            <input type="hidden" name="invite_id"
                                                value="<?= htmlspecialchars($inv['id'] ?? '') ?>">
                                            <button type="submit" class="btn btn-warning"
                                                style="padding:6px 12px;font-size:12px;">Revoke</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Audit Log Quick View -->
        <section>
            <h2>📋 Audit Logs — Invite gần nhất</h2>
            <?php
            $auditLogs = getAuditLogs(30);
            if (empty($auditLogs)):
                ?>
                <p class="empty">Không có audit logs nào.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Actor (người mời)</th>
                                <th>Target (được mời)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditLogs as $entry): ?>
                                <tr>
                                    <td style="white-space:nowrap;">
                                        <?= isset($entry['effective_at']) ? date('d/m/Y H:i:s', $entry['effective_at']) : '--' ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($entry['actor']['email'] ?? '--') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($entry['payload']['invite']['email'] ?? '--') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </main>
</body>

</html>
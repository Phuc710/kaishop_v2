<?php
/**
 * INTERACTIVE API DEBUGGER - GPT BUSINESS
 * Truy cập: /public/gpt-business/debug_invite.php
 */

// Basic bootstrap
define('BASE_PATH', dirname(__DIR__, 2));
require BASE_PATH . '/bootstrap/app.php';

// Load models/services
$farmModel   = new ChatGptFarm();
$farmService = new ChatGptFarmService();

$farms = $farmModel->getAll();
$actionResult = null;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fid = (int)($_POST['farm_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $farm = $farmModel->getById($fid);

    if ($farm) {
        switch ($action) {
            case 'get_org':
                $actionResult = $farmService->request($farm, 'GET', '/organization');
                break;
            case 'list_invites':
                $actionResult = $farmService->request($farm, 'GET', '/organization/invites?limit=50');
                break;
            case 'list_users':
                $actionResult = $farmService->request($farm, 'GET', '/organization/users?limit=50');
                break;
            case 'create_invite':
                    $email = trim($_POST['email'] ?? '');
                if ($email) {
                    $actionResult = $farmService->createInvite($farm, $email);
                } else {
                    $actionResult = ['error' => 'Vui lòng nhập email'];
                }
                break;
            case 'revoke_invite':
                $iid = trim($_POST['invite_id'] ?? '');
                if ($iid) {
                    $actionResult = $farmService->revokeInvite($farm, $iid);
                } else {
                    $actionResult = ['error' => 'Vui lòng nhập Invite ID'];
                }
                break;
            case 'remove_member':
                $uid = trim($_POST['user_id'] ?? '');
                if ($uid) {
                    $actionResult = $farmService->removeMember($farm, $uid);
                } else {
                    $actionResult = ['error' => 'Vui lòng nhập User ID'];
                }
                break;
        }
    }
}

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>OpenAI API Debugger</title>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #0a0a0b; color: #e1e1e3; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #27272a; padding-bottom: 15px; margin-bottom: 25px; }
        .farm-card { background: #18181b; border: 1px solid #27272a; border-radius: 12px; padding: 20px; margin-bottom: 30px; }
        .farm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px dotted #3f3f46; padding-bottom: 10px; }
        .farm-title { font-size: 1.25rem; font-weight: 600; color: #f4f4f5; margin: 0; }
        .farm-meta { font-size: 0.85rem; color: #a1a1aa; }
        
        .btn-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-bottom: 15px; }
        button, .btn { background: #27272a; color: #f4f4f5; border: 1px solid #3f3f46; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: 0.2s; font-size: 0.9rem; font-weight: 500; }
        button:hover { background: #3f3f46; border-color: #52525b; }
        button.primary { background: #3b82f6; border-color: #2563eb; color: white; }
        button.danger { background: #7f1d1d; border-color: #991b1b; color: white; }
        
        .form-row { display: flex; gap: 8px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #27272a; }
        input { background: #09090b; border: 1px solid #27272a; color: white; padding: 8px 12px; border-radius: 6px; flex-grow: 1; outline: none; transition: 0.2s; }
        input:focus { border-color: #3b82f6; }
        
        .result-panel { background: #09090b; border: 1px solid #2563eb; border-radius: 8px; padding: 15px; margin-top: 20px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4); }
        pre { font-family: 'Fira Code', monospace; font-size: 12px; margin: 0; overflow: auto; color: #60a5fa; white-space: pre-wrap; word-break: break-all; }
        
        .tag { font-size: 11px; padding: 2px 6px; border-radius: 4px; font-weight: bold; margin-left: 8px; }
        .tag-success { background: #064e3b; color: #10b981; }
        .tag-error { background: #450a0a; color: #f87171; }
        
        hr { border: 0; border-top: 1px solid #27272a; margin: 20px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🛠️ OpenAI API Debugger</h1>
            <div style='color: #a1a1aa;'>PHP " . PHP_VERSION . " | " . date('H:i:s') . "</div>
        </div>

        " . ($actionResult ? "
        <div class='result-panel'>
            <div style='display: flex; justify-content: space-between; margin-bottom: 10px;'>
                <strong style='color: #60a5fa;'>LATEST API RESPONSE</strong>
                <span class='tag " . (($actionResult['_http_code'] ?? 0) < 300 ? 'tag-success' : 'tag-error') . "'>HTTP " . ($actionResult['_http_code'] ?? '???') . "</span>
            </div>
            <pre>" . htmlspecialchars(json_encode($actionResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>
            <div style='margin-top: 10px; text-align: right;'><a href='debug_invite.php' style='color: #71717a; text-decoration: none; font-size: 0.8rem;'>[Clear Result]</a></div>
        </div>
        " : "") . "

        <div style='margin-top: 30px;'>";

foreach ($farms as $farm) {
    $fid = (int)$farm['id'];
    echo "<div class='farm-card'>
            <div class='farm-header'>
                <div>
                    <h3 class='farm-title'>" . htmlspecialchars($farm['farm_name']) . "</h3>
                    <div class='farm-meta'>" . htmlspecialchars($farm['admin_email']) . " | ID: $fid</div>
                </div>
                <div>Status: <span class='tag tag-success'>" . htmlspecialchars($farm['status']) . "</span></div>
            </div>

            <div class='btn-grid'>
                <form method='POST'>
                    <input type='hidden' name='farm_id' value='$fid'>
                    <input type='hidden' name='action' value='get_org'>
                    <button type='submit' style='width:100%'>🏢 Org Info</button>
                </form>
                <form method='POST'>
                    <input type='hidden' name='farm_id' value='$fid'>
                    <input type='hidden' name='action' value='list_invites'>
                    <button type='submit' style='width:100%'>📧 List Invites</button>
                </form>
                <form method='POST'>
                    <input type='hidden' name='farm_id' value='$fid'>
                    <input type='hidden' name='action' value='list_users'>
                    <button type='submit' style='width:100%'>👥 List Members</button>
                </form>
            </div>

            <!-- Create Invite -->
            <form method='POST' class='form-row'>
                <input type='hidden' name='farm_id' value='$fid'>
                <input type='hidden' name='action' value='create_invite'>
                <input type='email' name='email' placeholder='Email to invite...' required>
                <button type='submit' class='primary'>Send Invite</button>
            </form>

            <!-- Revoke Invite -->
            <form method='POST' class='form-row'>
                <input type='hidden' name='farm_id' value='$fid'>
                <input type='hidden' name='action' value='revoke_invite'>
                <input type='text' name='invite_id' placeholder='OpenAI Invite ID (invite-abc...)' required>
                <button type='submit' class='danger'>Revoke Invite</button>
            </form>

            <!-- Remove Member -->
            <form method='POST' class='form-row'>
                <input type='hidden' name='farm_id' value='$fid'>
                <input type='hidden' name='action' value='remove_member'>
                <input type='text' name='user_id' placeholder='OpenAI User ID (user-abc...)' required>
                <button type='submit' class='danger'>Kick Member</button>
            </form>
          </div>";
}

echo "  </div>
        <div style='text-align: center; margin-top: 50px; color: #3f3f46; font-size: 0.8rem;'>
            ⚠️ IMPORTANT: Delete this debug script after troubleshooting.
        </div>
    </div>
</body>
</html>";

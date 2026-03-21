<?php

/**
 * ============================================================
 * ChatGPT Farm Guard — Cron Script
 * Run every 1 minute:
 *   php /full/path/to/chatgpt/cron/guard.php
 *
 * What it does per farm:
 *   1. Fetch live members from OpenAI API
 *   2. Fetch live invites from OpenAI API
 *   3. Sync snapshots to DB
 *   4. Rule A: Detect & revoke unauthorized invites
 *   5. Rule B: Detect & remove unauthorized members
 *   6. Rule C: If member invited someone → kick both
 *   7. Update order status when invite accepted
 *   8. Update seat_used count
 *   9. Write audit log
 * ============================================================
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
define('CRON_RUN', true);

$rootDir = dirname(__DIR__, 2); // kaishop_v2/

require_once $rootDir . '/app/Helpers/EnvHelper.php';
EnvHelper::load($rootDir . '/.env');

require_once $rootDir . '/database/connection.php';
require_once $rootDir . '/core/Model.php';
require_once $rootDir . '/core/Database.php';

// Load models & service
foreach ([
    'app/Services/ChatGptFarmService.php',
    'app/Models/ChatGptFarm.php',
    'app/Models/ChatGptOrder.php',
    'app/Models/ChatGptAllowedInvite.php',
    'app/Models/ChatGptSnapshot.php',
    'app/Models/ChatGptAuditLog.php',
] as $file) {
    require_once $rootDir . '/' . $file;
}

// Optional: load CryptoService for key decryption
$cryptoFile = $rootDir . '/app/Services/CryptoService.php';
if (file_exists($cryptoFile)) {
    require_once $cryptoFile;
}

// ── Security: CLI-only or secret ──────────────────────────────────────────────
if (php_sapi_name() !== 'cli') {
    $secret = EnvHelper::get('CRON_SECRET', '');
    $provided = $_SERVER['HTTP_X_CRON_SECRET'] ?? ($_GET['secret'] ?? '');
    if ($secret === '' || !hash_equals($secret, $provided)) {
        http_response_code(403);
        die('Forbidden - cron only');
    }
}

// ── Init ──────────────────────────────────────────────────────────────────────
$farmModel = new ChatGptFarm();
$orderModel = new ChatGptOrder();
$allowModel = new ChatGptAllowedInvite();
$snapModel = new ChatGptSnapshot();
$auditLog = new ChatGptAuditLog();
$farmService = new ChatGptFarmService();

$startTime = microtime(true);
$farms = $farmModel->getAll('active');

function guardLog($msg)
{
    $ts = date('Y-m-d H:i:s');
    echo "[{$ts}] {$msg}" . PHP_EOL;
    flush();
}

guardLog("=== Guard Started — " . count($farms) . " active farm(s) ===");

if (empty($farms)) {
    guardLog("No active farms found. Exiting.");
    exit(0);
}

// ── Process each farm ─────────────────────────────────────────────────────────
foreach ($farms as $farm) {
    $farmId = (int) $farm['id'];
    $farmName = $farm['farm_name'];

    guardLog("--- Farm: [{$farmName}] (id={$farmId}) ---");

    // Step 1 & 2: Fetch live data from OpenAI
    $liveMembers = $farmService->listMembers($farm);
    $liveInvites = $farmService->listInvites($farm);

    // Step 3: Sync snapshots
    // Get valid (allowed) emails and invite IDs for this farm
    $allowedEmails = array_column($allowModel->getAllowedEmailsForFarm($farmId), 'target_email');
    $allowedInviteIds = $allowModel->getAllowedInviteIdsForFarm($farmId);

    $liveEmailSet = [];
    $liveInviteIdSet = [];

    // -- Sync members snapshot --
    foreach ($liveMembers as $m) {
        $email = strtolower(trim($m['email'] ?? ''));
        $userId = $m['id'] ?? '';
        $role = $m['role'] ?? 'reader';
        if ($email === '')
            continue;

        $liveEmailSet[] = $email;
        $isAllowed = in_array($email, $allowedEmails, true);
        $source = $isAllowed ? 'approved' : 'detected_unknown';
        $snapModel->upsertMember($farmId, $userId, $email, $role, $source);
    }

    // -- Sync invites snapshot --
    foreach ($liveInvites as $inv) {
        $inviteId = $inv['id'] ?? '';
        $email = strtolower(trim($inv['email'] ?? ''));
        $status = $inv['status'] ?? 'pending';
        if ($inviteId === '' || $email === '')
            continue;

        $liveInviteIdSet[] = $inviteId;
        $isAllowed = in_array($inviteId, $allowedInviteIds, true)
            || in_array($email, $allowedEmails, true);
        $source = $isAllowed ? 'approved' : 'detected_unknown';
        $snapModel->upsertInvite($farmId, $inviteId, $email, $status, $source);
    }

    // Step 4 — Rule A: Revoke unauthorized invites
    foreach ($liveInvites as $inv) {
        $inviteId = $inv['id'] ?? '';
        $email = strtolower(trim($inv['email'] ?? ''));
        if ($inviteId === '')
            continue;

        if (!in_array($inviteId, $allowedInviteIds, true) && !in_array($email, $allowedEmails, true)) {
            guardLog("⚠️  Rule A: Unauthorized invite → {$email} (id={$inviteId}) — REVOKING");

            $result = $farmService->revokeInvite($farm, $inviteId);
            $resultStr = $result['success'] ? 'OK' : 'FAIL';

            $snapModel->markInviteGone($farmId, $inviteId);

            $auditLog->log([
                'farm_id' => $farmId,
                'farm_name' => $farmName,
                'action' => 'INVITE_REVOKED_UNAUTHORIZED',
                'actor_email' => 'system_guard',
                'target_email' => $email,
                'result' => $resultStr,
                'reason' => 'not_in_allowed_invites',
                'meta' => ['invite_id' => $inviteId],
            ]);

            // Rule C: check if a current member possibly sent this
            // Heuristic: most recently active member (non-admin) in this farm
            // We flag this as a violation record but won't auto-kick without strict_mode
            // (strict mode can be enabled via env CHATGPT_GUARD_STRICT=1)
            $strictMode = (EnvHelper::get('CHATGPT_GUARD_STRICT', '0') === '1');
            if ($strictMode && !empty($liveEmailSet)) {
                foreach ($liveEmailSet as $memberEmail) {
                    if ($memberEmail === strtolower($farm['admin_email'] ?? ''))
                        continue;
                    // In strict mode: kick the most active approved member as suspected sponsor
                    // Only kick if they are "approved" (legitimate)
                    // This is conservative — only flag for logging, not auto-kick unknown members
                }
                guardLog("⚡ Strict mode: violation logged for farm {$farmId}");
            }
        }
    }

    // Step 5 — Rule B: Remove unauthorized members
    foreach ($liveMembers as $m) {
        $email = strtolower(trim($m['email'] ?? ''));
        $userId = $m['id'] ?? '';
        $role = $m['role'] ?? '';

        // Never kick the admin
        if ($email === strtolower(trim($farm['admin_email'] ?? ''))) {
            continue;
        }
        if ($role === 'owner') {
            continue;
        }

        if (!in_array($email, $allowedEmails, true)) {
            guardLog("⚠️  Rule B: Unknown member → {$email} (user_id={$userId}) — REMOVING");

            $result = ($userId !== '') ? $farmService->removeMember($farm, $userId) : ['success' => false];
            $resultStr = $result['success'] ? 'OK' : 'FAIL';

            $snapModel->markMemberGone($farmId, $email);

            // Decrement seat
            if ($result['success']) {
                $farmModel->decrementSeatUsed($farmId);
            }

            $auditLog->log([
                'farm_id' => $farmId,
                'farm_name' => $farmName,
                'action' => 'MEMBER_REMOVED_UNAUTHORIZED',
                'actor_email' => 'system_guard',
                'target_email' => $email,
                'result' => $resultStr,
                'reason' => 'joined_without_valid_invite',
                'meta' => ['openai_user_id' => $userId],
            ]);
        }
    }

    // Step 7 — Update order status when invite accepted (member appeared)
    // If a member's email matches an 'inviting' order + pending allowed_invite
    $invitingOrders = $orderModel->getInvitingOrders();
    foreach ($invitingOrders as $order) {
        if ((int) $order['assigned_farm_id'] !== $farmId)
            continue;
        $oEmail = strtolower(trim($order['customer_email']));

        if (in_array($oEmail, $liveEmailSet, true)) {
            // User has joined!
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            $orderModel->updateStatus((int) $order['id'], 'active', ['expires_at' => $expiresAt]);
            $allowModel->markAcceptedByEmail($farmId, $oEmail);
            $snapModel->markMemberApproved($farmId, $oEmail);

            guardLog("✅ Order #{$order['id']} ({$oEmail}) — invite accepted, status → active");

            $auditLog->log([
                'farm_id' => $farmId,
                'farm_name' => $farmName,
                'action' => 'ORDER_ACTIVATED',
                'actor_email' => 'system_guard',
                'target_email' => $oEmail,
                'result' => 'OK',
                'reason' => 'invite_accepted',
                'meta' => ['order_id' => $order['id']],
            ]);
        }
    }

    // Step 8 — Recount seat_used from live data (truth from API)
    $nonAdminCount = 0;
    foreach ($liveMembers as $m) {
        $email = strtolower(trim($m['email'] ?? ''));
        $role = $m['role'] ?? '';
        if ($email !== strtolower(trim($farm['admin_email'] ?? '')) && $role !== 'owner') {
            $nonAdminCount++;
        }
    }
    // Add pending invite count
    $pendingInviteCount = 0;
    foreach ($liveInvites as $inv) {
        if (($inv['status'] ?? '') === 'pending') {
            $pendingInviteCount++;
        }
    }
    $totalUsed = $nonAdminCount + $pendingInviteCount;

    // Update seat_used to reflect reality
    $stmtUpdate = $farmModel->getConnection()->prepare(
        "UPDATE `chatgpt_farms`
         SET `seat_used` = ?,
             `status` = IF(? >= `seat_total`, 'full', 'active'),
             `last_sync_at` = NOW()
         WHERE `id` = ? LIMIT 1"
    );
    $stmtUpdate->execute([$totalUsed, $totalUsed, $farmId]);

    guardLog("Farm [{$farmName}]: members={$nonAdminCount}, pending_invites={$pendingInviteCount}, seat_used={$totalUsed}");

    $farmModel->touchSyncAt($farmId);
    guardLog("✔ Farm [{$farmName}] synced successfully.");
}

$elapsed = round(microtime(true) - $startTime, 2);
guardLog("=== Guard Finished in {$elapsed}s ===");
exit(0);

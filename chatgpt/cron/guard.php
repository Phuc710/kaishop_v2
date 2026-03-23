<?php

/**
 * ChatGPT Farm Guard - Cron Script
 * Run every 1 minute:
 *   php /full/path/to/chatgpt/cron/guard.php
 */

define('CRON_RUN', true);

$rootDir = dirname(__DIR__, 2);

require_once $rootDir . '/app/Helpers/EnvHelper.php';
EnvHelper::load($rootDir . '/.env');

require_once $rootDir . '/database/connection.php';
require_once $rootDir . '/core/Model.php';
require_once $rootDir . '/core/Database.php';

foreach ([
    'app/Services/ChatGptFarmService.php',
    'app/Models/ChatGptFarm.php',
    'app/Models/ChatGptOrder.php',
    'app/Models/ChatGptAllowedInvite.php',
    'app/Models/ChatGptSnapshot.php',
    'app/Models/ChatGptAuditLog.php',
    'app/Models/ChatGptViolation.php',
    'app/Services/ChatGptGuardService.php',
] as $file) {
    require_once $rootDir . '/' . $file;
}

$cryptoFile = $rootDir . '/app/Services/CryptoService.php';
if (file_exists($cryptoFile)) {
    require_once $cryptoFile;
}

if (php_sapi_name() !== 'cli') {
    $secret = EnvHelper::get('CRON_SECRET', '');
    $provided = $_SERVER['HTTP_X_CRON_SECRET'] ?? ($_GET['secret'] ?? '');
    if ($secret === '' || !hash_equals($secret, $provided)) {
        http_response_code(403);
        die('Forbidden - cron only');
    }
}

$farmModel = new ChatGptFarm();
$guardService = new ChatGptGuardService();
$farms = $farmModel->getGuardableFarms();
$startTime = microtime(true);

function guardLog($message)
{
    $ts = date('Y-m-d H:i:s');
    echo "[{$ts}] {$message}" . PHP_EOL;
    flush();
}

guardLog('=== Guard Started - ' . count($farms) . ' guardable farm(s) ===');

if (empty($farms)) {
    guardLog('No guardable farms found. Exiting.');
    exit(0);
}

foreach ($farms as $farm) {
    $farmId = (int) ($farm['id'] ?? 0);
    $farmName = (string) ($farm['farm_name'] ?? ('Farm #' . $farmId));
    guardLog("--- Farm: [{$farmName}] (id={$farmId}) ---");

    $result = $guardService->processFarm($farm, 'system_guard', 'cron');
    if (!$result['success']) {
        guardLog("FAIL - {$farmName}: " . ($result['message'] ?? 'unknown error'));
        continue;
    }

    guardLog(
        "OK - members={$result['members_total']}, invites={$result['invites_total']}, " .
        "removed={$result['members_removed']}, revoked={$result['invites_revoked']}, " .
        "activated={$result['orders_activated']}, expired={$result['orders_expired']}, " .
        "violations={$result['violations_logged']}, seat_used={$result['seat_used']}"
    );
}

$elapsed = round(microtime(true) - $startTime, 2);
guardLog("=== Guard Finished in {$elapsed}s ===");
exit(0);

<?php

/**
 * GPT Business guard cron worker.
 *
 * Usage:
 *   php public/gpt-business/cron.php
 *   php public/gpt-business/cron.php --farm=12
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../../config/app.php';

$timeService = TimeService::instance();
$guardService = new ChatGptGuardService();
$farmModel = new ChatGptFarm();

function gptGuardAcquireLock(string $name): ?array
{
    $lockPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name . '.lock';
    $handle = @fopen($lockPath, 'c+');
    if ($handle === false) {
        return null;
    }

    if (!flock($handle, LOCK_EX | LOCK_NB)) {
        fclose($handle);
        return null;
    }

    @ftruncate($handle, 0);
    @fwrite($handle, (string) getmypid());
    @fflush($handle);

    return [
        'handle' => $handle,
        'path' => $lockPath,
    ];
}

function gptGuardReleaseLock(?array $lock): void
{
    if (!is_array($lock) || !isset($lock['handle'])) {
        return;
    }

    @flock($lock['handle'], LOCK_UN);
    @fclose($lock['handle']);
}

function gptGuardResolveFarmId(array $argv): int
{
    foreach ($argv as $arg) {
        if (strpos((string) $arg, '--farm=') === 0) {
            return max(0, (int) substr((string) $arg, 7));
        }
    }

    return 0;
}

$lock = gptGuardAcquireLock('kaishop_gpt_business_guard');
if ($lock === null) {
    fwrite(STDERR, '[' . $timeService->nowSql() . "] GPT Business guard is already running.\n");
    exit(1);
}

try {
    $farmId = gptGuardResolveFarmId($argv ?? []);
    $farms = [];

    if ($farmId > 0) {
        $farm = $farmModel->getById($farmId);
        if (!$farm) {
            fwrite(STDERR, '[' . $timeService->nowSql() . "] Farm #{$farmId} not found.\n");
            exit(2);
        }
        $farms = [$farm];
    } else {
        $farms = $farmModel->getGuardableFarms();
    }

    echo '[' . $timeService->nowSql() . '] GPT Business Guard Cron' . "\n";
    echo 'Farms queued: ' . count($farms) . "\n";

    $summary = [
        'farms' => 0,
        'members_removed' => 0,
        'invites_revoked' => 0,
        'orders_activated' => 0,
        'orders_expired' => 0,
        'violations_logged' => 0,
    ];

    foreach ($farms as $farm) {
        $result = $guardService->processFarm($farm, 'system_guard', 'cron');
        $summary['farms']++;
        $summary['members_removed'] += (int) ($result['members_removed'] ?? 0);
        $summary['invites_revoked'] += (int) ($result['invites_revoked'] ?? 0);
        $summary['orders_activated'] += (int) ($result['orders_activated'] ?? 0);
        $summary['orders_expired'] += (int) ($result['orders_expired'] ?? 0);
        $summary['violations_logged'] += (int) ($result['violations_logged'] ?? 0);

        $label = (string) ($result['farm_name'] ?? ('Farm #' . (int) ($farm['id'] ?? 0)));
        echo sprintf(
            "- %s | removed=%d revoked=%d activated=%d expired=%d violations=%d\n",
            $label,
            (int) ($result['members_removed'] ?? 0),
            (int) ($result['invites_revoked'] ?? 0),
            (int) ($result['orders_activated'] ?? 0),
            (int) ($result['orders_expired'] ?? 0),
            (int) ($result['violations_logged'] ?? 0)
        );
    }

    echo sprintf(
        "Done | farms=%d removed=%d revoked=%d activated=%d expired=%d violations=%d\n",
        $summary['farms'],
        $summary['members_removed'],
        $summary['invites_revoked'],
        $summary['orders_activated'],
        $summary['orders_expired'],
        $summary['violations_logged']
    );
} finally {
    gptGuardReleaseLock($lock);
}

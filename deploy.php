<?php
declare(strict_types=1);

/**
 * GitHub Auto Deploy Webhook
 *
 * Required .env keys:
 *   DEPLOY_SECRET=<github webhook secret>
 *
 * Optional .env keys:
 *   DEPLOY_BRANCH=main
 *   DEPLOY_REPO=owner/repo
 */

function deploy_log(string $status, string $message, array $context = []): void
{
    $time = date('Y-m-d H:i:s');
    $line = '[' . $time . '] [' . $status . '] ' . $message;
    if (!empty($context)) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $line .= PHP_EOL;

    $logFile = __DIR__ . '/storage/logs/deploy.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

function load_env_file(string $path): array
{
    $vars = [];
    if (!is_file($path)) {
        return $vars;
    }

    $lines = @file($path, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) {
        return $vars;
    }

    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $eqPos));
        if ($key === '') {
            continue;
        }
        $value = trim(substr($line, $eqPos + 1));
        $vars[$key] = trim($value, " \t\n\r\0\x0B\"'");
    }

    return $vars;
}

function env_value(string $key, array $env, string $default = ''): string
{
    if (isset($env[$key])) {
        return (string) $env[$key];
    }

    $value = getenv($key);
    if ($value !== false) {
        return trim((string) $value);
    }

    return $default;
}

function respond_json(int $code, array $payload): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$env = load_env_file(__DIR__ . '/.env');
$secret = trim(env_value('DEPLOY_SECRET', $env));
$targetBranch = trim(env_value('DEPLOY_BRANCH', $env, 'main'));
$expectedRepo = trim(env_value('DEPLOY_REPO', $env));

$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$deliveryId = trim((string) ($_SERVER['HTTP_X_GITHUB_DELIVERY'] ?? ''));
$event = trim((string) ($_SERVER['HTTP_X_GITHUB_EVENT'] ?? ''));
$sigHeader = trim((string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? ''));
$clientIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

if ($requestMethod !== 'POST') {
    deploy_log('REJECT', 'Method not allowed', [
        'method' => $requestMethod,
        'ip' => $clientIp,
    ]);
    respond_json(405, ['ok' => false, 'message' => 'Method Not Allowed']);
}

if ($secret === '') {
    deploy_log('ERROR', 'DEPLOY_SECRET is missing', ['delivery' => $deliveryId]);
    respond_json(500, ['ok' => false, 'message' => 'Server deploy secret is not configured']);
}

$payload = (string) file_get_contents('php://input');
if ($payload === '') {
    deploy_log('REJECT', 'Empty payload', ['delivery' => $deliveryId]);
    respond_json(400, ['ok' => false, 'message' => 'Empty payload']);
}

$expectedSig = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if ($sigHeader === '' || !hash_equals($expectedSig, $sigHeader)) {
    deploy_log('REJECT', 'Invalid signature', [
        'delivery' => $deliveryId,
        'event' => $event,
        'ip' => $clientIp,
    ]);
    respond_json(403, ['ok' => false, 'message' => 'Forbidden: invalid signature']);
}

if ($event !== 'push') {
    deploy_log('IGNORE', 'Ignored non-push event', [
        'delivery' => $deliveryId,
        'event' => $event,
    ]);
    respond_json(202, ['ok' => true, 'message' => 'Ignored event']);
}

$data = json_decode($payload, true);
if (!is_array($data)) {
    deploy_log('REJECT', 'Invalid JSON payload', ['delivery' => $deliveryId]);
    respond_json(400, ['ok' => false, 'message' => 'Invalid JSON payload']);
}

$incomingRef = trim((string) ($data['ref'] ?? ''));
$expectedRef = 'refs/heads/' . $targetBranch;
if ($incomingRef !== $expectedRef) {
    deploy_log('IGNORE', 'Ignored branch', [
        'delivery' => $deliveryId,
        'incoming_ref' => $incomingRef,
        'expected_ref' => $expectedRef,
    ]);
    respond_json(202, ['ok' => true, 'message' => 'Ignored branch']);
}

$incomingRepo = trim((string) ($data['repository']['full_name'] ?? ''));
if ($expectedRepo !== '' && strcasecmp($incomingRepo, $expectedRepo) !== 0) {
    deploy_log('REJECT', 'Repository mismatch', [
        'delivery' => $deliveryId,
        'incoming_repo' => $incomingRepo,
        'expected_repo' => $expectedRepo,
    ]);
    respond_json(403, ['ok' => false, 'message' => 'Forbidden: repository mismatch']);
}

$lockDir = __DIR__ . '/storage/locks';
if (!is_dir($lockDir)) {
    @mkdir($lockDir, 0755, true);
}
$lockFile = $lockDir . '/deploy.lock';
$lockHandle = @fopen($lockFile, 'c+');
if ($lockHandle === false) {
    deploy_log('ERROR', 'Cannot open deploy lock file', ['delivery' => $deliveryId]);
    respond_json(500, ['ok' => false, 'message' => 'Cannot create deploy lock']);
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    deploy_log('IGNORE', 'Deploy already running', ['delivery' => $deliveryId]);
    fclose($lockHandle);
    respond_json(409, ['ok' => false, 'message' => 'Deploy is already running']);
}

$repoDir = __DIR__;
$branchArg = escapeshellarg($targetBranch);
$repoArg = escapeshellarg($repoDir);
$command = 'cd ' . $repoArg
    . ' && git fetch --prune origin ' . $branchArg . ' 2>&1'
    . ' && git checkout ' . $branchArg . ' 2>&1'
    . ' && git pull --ff-only origin ' . $branchArg . ' 2>&1';

$output = [];
$exitCode = 1;
exec($command, $output, $exitCode);

$commit = trim((string) ($data['after'] ?? ''));
$pusher = trim((string) ($data['pusher']['name'] ?? 'unknown'));
$status = $exitCode === 0 ? 'OK' : 'ERROR';
$summaryOutput = array_slice($output, -120);

deploy_log($status, 'Deploy executed', [
    'delivery' => $deliveryId,
    'event' => $event,
    'repo' => $incomingRepo,
    'branch' => $targetBranch,
    'commit' => $commit,
    'pusher' => $pusher,
    'exit_code' => $exitCode,
    'output' => $summaryOutput,
]);

flock($lockHandle, LOCK_UN);
fclose($lockHandle);

if ($exitCode !== 0) {
    respond_json(500, [
        'ok' => false,
        'status' => $status,
        'message' => 'Deploy failed',
        'exit_code' => $exitCode,
        'delivery' => $deliveryId,
    ]);
}

respond_json(200, [
    'ok' => true,
    'status' => $status,
    'message' => 'Deploy completed',
    'delivery' => $deliveryId,
    'repo' => $incomingRepo,
    'branch' => $targetBranch,
    'commit' => $commit,
]);


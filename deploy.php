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
        if (!@mkdir($logDir, 0755, true)) {
            // Fallback: log to root dir if storage/logs cannot be created
            $logFile = __DIR__ . '/deploy_debug.log';
        }
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

/**
 * Find the git binary path — shared hosting servers often don't have it in PATH
 */
function find_git_binary(): string
{
    // Common paths on cPanel/shared hosting
    $candidates = [
        '/usr/bin/git',
        '/usr/local/bin/git',
        '/usr/local/cpanel/3rdparty/bin/git',
        '/opt/cpanel/ez-git/bin/git',
        'git', // fallback: rely on PATH
    ];

    foreach ($candidates as $path) {
        if ($path === 'git') {
            return 'git'; // use whatever is in PATH
        }
        if (is_executable($path)) {
            return $path;
        }
    }

    return 'git';
}

// ─── Load config ────────────────────────────────────────────────────────────
$env = load_env_file(__DIR__ . '/.env');
$secret = trim(env_value('DEPLOY_SECRET', $env));
$targetBranch = trim(env_value('DEPLOY_BRANCH', $env, 'main'));
$expectedRepo = trim(env_value('DEPLOY_REPO', $env));

// ─── Request metadata ────────────────────────────────────────────────────────
$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$deliveryId = trim((string) ($_SERVER['HTTP_X_GITHUB_DELIVERY'] ?? ''));
$event = trim((string) ($_SERVER['HTTP_X_GITHUB_EVENT'] ?? ''));
$sigHeader = trim((string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? ''));
$clientIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

if ($requestMethod !== 'POST') {
    deploy_log('REJECT', 'Method not allowed', ['method' => $requestMethod, 'ip' => $clientIp]);
    respond_json(405, ['ok' => false, 'message' => 'Method Not Allowed']);
}

if ($secret === '') {
    deploy_log('ERROR', 'DEPLOY_SECRET is missing or empty in .env', ['delivery' => $deliveryId]);
    respond_json(500, ['ok' => false, 'message' => 'Server deploy secret is not configured']);
}

$payload = (string) file_get_contents('php://input');

// Diagnostic log every request
deploy_log('DEBUG', 'Webhook received', [
    'ip' => $clientIp,
    'event' => $event,
    'delivery' => $deliveryId,
    'sig_present' => ($sigHeader !== ''),
    'payload_bytes' => strlen($payload),
]);

if ($payload === '') {
    deploy_log('REJECT', 'Empty payload', ['delivery' => $deliveryId]);
    respond_json(400, ['ok' => false, 'message' => 'Empty payload']);
}

// ─── Signature validation ────────────────────────────────────────────────────
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
    deploy_log('IGNORE', 'Non-push event ignored', ['delivery' => $deliveryId, 'event' => $event]);
    respond_json(202, ['ok' => true, 'message' => 'Ignored event: ' . $event]);
}

// ─── Parse payload ───────────────────────────────────────────────────────────
$data = json_decode($payload, true);
if (!is_array($data)) {
    deploy_log('REJECT', 'Invalid JSON payload', ['delivery' => $deliveryId]);
    respond_json(400, ['ok' => false, 'message' => 'Invalid JSON payload']);
}

$incomingRef = trim((string) ($data['ref'] ?? ''));
$expectedRef = 'refs/heads/' . $targetBranch;
if ($incomingRef !== $expectedRef) {
    deploy_log('IGNORE', 'Branch mismatch', [
        'delivery' => $deliveryId,
        'incoming_ref' => $incomingRef,
        'expected_ref' => $expectedRef,
    ]);
    respond_json(202, ['ok' => true, 'message' => 'Ignored: branch mismatch']);
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

// ─── Lock to prevent concurrent deploys ──────────────────────────────────────
$lockDir = __DIR__ . '/storage/locks';
if (!is_dir($lockDir)) {
    @mkdir($lockDir, 0755, true);
}
$lockFile = $lockDir . '/deploy.lock';
$lockHandle = @fopen($lockFile, 'c+');
if ($lockHandle === false) {
    deploy_log('ERROR', 'Cannot open lock file', ['delivery' => $deliveryId, 'path' => $lockFile]);
    respond_json(500, ['ok' => false, 'message' => 'Cannot create deploy lock']);
}
if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    deploy_log('IGNORE', 'Deploy already running', ['delivery' => $deliveryId]);
    fclose($lockHandle);
    respond_json(409, ['ok' => false, 'message' => 'Deploy is already running']);
}

// ─── Pre-flight checks ───────────────────────────────────────────────────────
$repoDir = __DIR__;

if (!is_dir($repoDir . '/.git')) {
    deploy_log('ERROR', 'Not a git repository', ['dir' => $repoDir]);
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    respond_json(500, ['ok' => false, 'message' => 'Server error: not a git repository']);
}

if (!function_exists('exec')) {
    deploy_log('ERROR', 'exec() is disabled on this server');
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    respond_json(500, ['ok' => false, 'message' => 'Server error: exec() is disabled']);
}

// ─── Run git pull ─────────────────────────────────────────────────────────────
$gitBin = find_git_binary();
$branchArg = escapeshellarg($targetBranch);
$repoArg = escapeshellarg($repoDir);

// Use HOME env so git can find SSH/HTTPS credentials in ~/.gitconfig
$homeDir = trim((string) shell_exec('echo $HOME 2>/dev/null')) ?: '/home/' . get_current_user();
$envPrefix = 'HOME=' . escapeshellarg($homeDir) . ' GIT_TERMINAL_PROMPT=0';

$command = $envPrefix . ' ' . escapeshellcmd($gitBin)
    . ' -C ' . $repoArg
    . ' fetch --prune origin ' . $branchArg . ' 2>&1'
    . ' && ' . $envPrefix . ' ' . escapeshellcmd($gitBin)
    . ' -C ' . $repoArg
    . ' checkout ' . $branchArg . ' 2>&1'
    . ' && ' . $envPrefix . ' ' . escapeshellcmd($gitBin)
    . ' -C ' . $repoArg
    . ' pull --ff-only origin ' . $branchArg . ' 2>&1';

$output = [];
$exitCode = 1;
exec($command, $output, $exitCode);

// ─── Log result ───────────────────────────────────────────────────────────────
$commit = trim((string) ($data['after'] ?? ''));
$pusher = trim((string) ($data['pusher']['name'] ?? 'unknown'));
$status = $exitCode === 0 ? 'OK' : 'ERROR';
$lastOut = array_slice($output, -30); // keep last 30 lines

deploy_log($status, 'Deploy executed', [
    'delivery' => $deliveryId,
    'repo' => $incomingRepo,
    'branch' => $targetBranch,
    'commit' => $commit,
    'pusher' => $pusher,
    'exit_code' => $exitCode,
    'git_bin' => $gitBin,
    'output' => $lastOut,
]);

flock($lockHandle, LOCK_UN);
fclose($lockHandle);

if ($exitCode !== 0) {
    respond_json(500, [
        'ok' => false,
        'status' => $status,
        'message' => 'Deploy failed — check storage/logs/deploy.log',
        'exit_code' => $exitCode,
        'output' => $lastOut,
        'delivery' => $deliveryId,
    ]);
}

respond_json(200, [
    'ok' => true,
    'status' => $status,
    'message' => 'Deploy completed successfully',
    'delivery' => $deliveryId,
    'repo' => $incomingRepo,
    'branch' => $targetBranch,
    'commit' => $commit,
    'pusher' => $pusher,
]);

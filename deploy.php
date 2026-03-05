<?php
/**
 * GitHub Auto-Deploy Webhook
 * URL: https://kaishop.id.vn/deploy.php
 *
 * Setup:
 *   1. Add DEPLOY_SECRET=your_random_secret to .env on the server
 *   2. Point GitHub webhook to https://kaishop.id.vn/deploy.php
 *   3. Set Content-Type: application/json
 *   4. Set Secret to the same value as DEPLOY_SECRET
 */

// ── Load secret from .env ─────────────────────────────
$envFile = __DIR__ . '/.env';
$secret = '';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), 'DEPLOY_SECRET=')) {
            $secret = trim(substr($line, strpos($line, '=') + 1), " \t\"'");
            break;
        }
    }
}

// ── Only accept POST ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ── Verify GitHub signature ───────────────────────────
$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if ($secret !== '') {
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expected, $sigHeader)) {
        http_response_code(403);
        exit('Forbidden: invalid signature');
    }
}

// ── Only deploy on push to main branch ───────────────
$data = json_decode($payload, true);
$branch = $data['ref'] ?? '';
if ($branch !== 'refs/heads/main') {
    http_response_code(200);
    exit("Ignored: branch $branch");
}

// ── Run git pull ──────────────────────────────────────
$dir = __DIR__;
$output = [];
$code = 0;

exec("cd " . escapeshellarg($dir) . " && git pull origin main 2>&1", $output, $code);

$log = implode("\n", $output);
$status = $code === 0 ? 'OK' : 'ERROR';
$time = date('Y-m-d H:i:s');
$commit = $data['after'] ?? 'unknown';
$pusher = $data['pusher']['name'] ?? 'unknown';

// ── Log to file ───────────────────────────────────────
$logLine = "[$time] [$status] commit=$commit pusher=$pusher exit=$code\n$log\n\n";
$logFile = __DIR__ . '/storage/logs/deploy.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}
file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

// ── Respond ───────────────────────────────────────────
http_response_code($code === 0 ? 200 : 500);
header('Content-Type: application/json');
echo json_encode([
    'status' => $status,
    'commit' => $commit,
    'pusher' => $pusher,
    'output' => $output,
    'time' => $time,
]);

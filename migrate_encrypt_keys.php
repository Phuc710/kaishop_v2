<?php
/**
 * One-time migration script: Encrypt existing plaintext API keys in DB.
 * Run ONCE via CLI after deploying SecureCrypto:
 *
 *   php migrate_encrypt_keys.php
 *
 * Safe to re-run: already-encrypted values are skipped.
 * CLI-only access guard.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("403 Forbidden\n");
}

require_once __DIR__ . '/config/app.php';

// Keys to migrate
$keysToEncrypt = [
    'binance_api_key',
    'binance_api_secret',
    'sepay_api_key',
    'telegram_bot_token',
    'telegram_webhook_secret',
    'pass_mail_auto',
];

if (!class_exists('SecureCrypto')) {
    echo "[ERROR] SecureCrypto class not found. Check app/Helpers/SecureCrypto.php\n";
    exit(1);
}

if (!defined('APP_KEY') || APP_KEY === '') {
    echo "[ERROR] APP_KEY not set in .env. Cannot encrypt.\n";
    exit(1);
}

global $connection;
if (!($connection instanceof mysqli)) {
    echo "[ERROR] Database connection not available.\n";
    exit(1);
}

$result = $connection->query("SELECT * FROM `setting` LIMIT 1");
if (!$result) {
    echo "[ERROR] Cannot read setting table: " . $connection->error . "\n";
    exit(1);
}

$row = $result->fetch_assoc();
if (!$row) {
    echo "[ERROR] No rows in setting table.\n";
    exit(1);
}

$updates = [];
foreach ($keysToEncrypt as $key) {
    $current = (string) ($row[$key] ?? '');
    if ($current === '') {
        echo "[SKIP]  {$key} — empty, nothing to encrypt\n";
        continue;
    }
    if (SecureCrypto::isEncrypted($current)) {
        echo "[SKIP]  {$key} — already encrypted\n";
        continue;
    }
    $encrypted = SecureCrypto::encrypt($current);
    $safe = $connection->real_escape_string($encrypted);
    $updates[] = "`{$key}` = '{$safe}'";
    echo "[OK]    {$key} — encrypted\n";
}

if (empty($updates)) {
    echo "\nNothing to migrate. All keys are already encrypted or empty.\n";
    exit(0);
}

$sql = "UPDATE `setting` SET " . implode(', ', $updates) . " LIMIT 1";
if ($connection->query($sql)) {
    echo "\n✅ Migration complete. " . count($updates) . " key(s) encrypted in DB.\n";
    echo "   From now on, admin panel will encrypt on save automatically.\n";
} else {
    echo "\n[ERROR] DB update failed: " . $connection->error . "\n";
    exit(1);
}

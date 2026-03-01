<?php

/**
 * Telegram Outbox Worker & Long Polling Tool — Production Optimized
 *
 * Cron (production — mỗi phút):
 *   * * * * * php /path/to/public/telegram/cron.php >> /var/log/kaishop-tg.log 2>&1
 *
 * Polling (dev/local — loop vô hạn):
 *   php public/telegram/cron.php --poll
 *
 * Tối ưu:
 *  - curl_multi_*: Gửi outbox song song (parallel), giảm tổng thời gian chờ từ N×5s → ~5s
 *  - CURLOPT_TIMEOUT = 5s mỗi request (không để treo)
 *  - Lưu `last_cron_run` vào setting để Worker Health monitor
 *  - Đóng PDO connection sau mỗi phase để tránh connection leak
 *  - Cleanup OTP hết hạn + outbox cũ hàng ngày (1 lần/ngày)
 */

require_once __DIR__ . '/../../config/app.php';

$isPollMode = in_array('--poll', $argv ?? [], true);

$telegram = new TelegramService();
$outboxModel = new TelegramOutbox();
$botLogic = new TelegramBotService($telegram);

echo '[' . date('Y-m-d H:i:s') . '] KaiShop Telegram Worker (' . ($isPollMode ? 'POLLING' : 'CRON') . ")\n";

// =========================================================
//  1. Gửi Outbox — PARALLEL via curl_multi
// =========================================================

function processOutboxParallel(TelegramOutbox $outboxModel, string $botToken): void
{
    $pending = $outboxModel->fetchPending(30); // Max 30 per cron cycle
    if (empty($pending)) {
        return;
    }

    echo '  [Outbox] Sending ' . count($pending) . " messages (parallel)...\n";

    $baseUrl = 'https://api.telegram.org/bot' . $botToken . '/sendMessage';
    $multiHandle = curl_multi_init();
    $handles = [];
    $idMap = []; // curl handle index -> outbox row id

    foreach ($pending as $idx => $msg) {
        $payload = http_build_query([
            'chat_id' => (string) $msg['telegram_id'],
            'text' => $msg['message'],
            'parse_mode' => $msg['parse_mode'] ?? 'HTML',
            'disable_web_page_preview' => 'true',
        ]);

        $ch = curl_init($baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 5,          // Max 5s per message — không để treo
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        curl_multi_add_handle($multiHandle, $ch);
        $handles[$idx] = $ch;
        $idMap[$idx] = (int) $msg['id'];
    }

    // Execute all curl handles in parallel
    $running = 0;
    do {
        $status = curl_multi_exec($multiHandle, $running);
        if ($status === CURLM_CALL_MULTI_PERFORM)
            continue;
        if ($running > 0)
            curl_multi_select($multiHandle, 1.0);
    } while ($running > 0 && $status === CURLM_OK);

    // Process results
    foreach ($handles as $idx => $ch) {
        $raw = curl_multi_getcontent($ch);
        $response = $raw ? json_decode($raw, true) : null;
        $outboxId = $idMap[$idx];

        if (!empty($response['ok'])) {
            $outboxModel->markSent($outboxId);
            echo "    [OK] Outbox #{$outboxId} → Chat " . $pending[$idx]['telegram_id'] . "\n";
        } else {
            $errDesc = $response['description'] ?? (curl_error($ch) ?: 'Unknown error');
            $outboxModel->markFailed($outboxId, $errDesc);
            echo "    [FAIL] Outbox #{$outboxId}: {$errDesc}\n";
        }

        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);
    }

    curl_multi_close($multiHandle);
}

// =========================================================
//  2. Cleanup — OTP hết hạn + Outbox cũ
// =========================================================

function cleanupOldData(TelegramOutbox $outboxModel): void
{
    // Chỉ cleanup 1 lần/ngày (giờ 03:xx)
    if ((int) date('G') !== 3)
        return;

    $deletedOutbox = $outboxModel->cleanOldSent(7);
    if ($deletedOutbox > 0) {
        echo "  [Cleanup] Removed {$deletedOutbox} old outbox messages\n";
    }

    $otpModel = new TelegramLinkCode();
    $deletedOtp = $otpModel->cleanExpired();
    if ($deletedOtp > 0) {
        echo "  [Cleanup] Removed {$deletedOtp} expired OTP codes\n";
    }

    // Cleanup rate-limit files cũ quá 10 phút
    $rlDir = '/tmp/kaishop_tg_rl';
    if (is_dir($rlDir)) {
        $cutoff = time() - 600;
        foreach (glob($rlDir . '/*.json') ?: [] as $file) {
            if (@filemtime($file) < $cutoff)
                @unlink($file);
        }
        // Cleanup cooldown files > 1h
        if (is_dir($rlDir . '/cooldown')) {
            $cutoffCooldown = time() - 3600;
            foreach (glob($rlDir . '/cooldown/*.ts') ?: [] as $file) {
                if (@filemtime($file) < $cutoffCooldown)
                    @unlink($file);
            }
        }
    }
}

// =========================================================
//  3. Lưu timestamp last_cron_run (Worker Health Monitor)
// =========================================================

function saveLastCronRun(PDO $db): void
{
    try {
        $now = TimeService::instance()->nowSql();
        $stmt = $db->prepare("UPDATE `setting` SET `last_cron_run`=? ORDER BY `id` ASC LIMIT 1");
        $stmt->execute([$now]);
    } catch (Throwable $e) {
        // Non-blocking — không ảnh hưởng luồng chính
        error_log('[cron] Could not save last_cron_run: ' . $e->getMessage());
    }
}

// =========================================================
//  4. Long Polling (chỉ dùng cho dev/local)
// =========================================================

function runPolling(TelegramService $telegram, TelegramBotService $botLogic): void
{
    $db = (new UserTelegramLink())->getConnection();

    $lastUpdateId = 0;
    try {
        $stmt = $db->query("SELECT `telegram_last_update_id` FROM `setting` ORDER BY `id` ASC LIMIT 1");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        $lastUpdateId = (int) ($row['telegram_last_update_id'] ?? 0);
    } catch (Throwable $e) {
        echo "  [Polling] Warning: Could not load last update ID — " . $e->getMessage() . "\n";
    }

    $response = $telegram->apiCall('getUpdates', [
        'offset' => $lastUpdateId + 1,
        'timeout' => 30,        // long-poll: 30s
        'limit' => 50,
    ]);

    if (!empty($response['ok']) && !empty($response['result'])) {
        foreach ($response['result'] as $update) {
            $lastUpdateId = (int) $update['update_id'];
            echo "  [Polling] Processing Update #{$lastUpdateId}\n";
            try {
                $botLogic->processUpdate($update);
            } catch (Throwable $e) {
                echo "  [Polling] Error: " . $e->getMessage() . "\n";
            }
        }

        try {
            $stmt = $db->prepare("UPDATE `setting` SET `telegram_last_update_id`=? ORDER BY `id` ASC LIMIT 1");
            $stmt->execute([$lastUpdateId]);
        } catch (Throwable $e) {
            echo "  [Polling] Warning: Could not save last update ID\n";
        }

        Config::clearSiteConfigCache();
    }
}

// =========================================================
//  Execution
// =========================================================

if ($isPollMode) {
    echo "  NOTE: Polling mode is for DEVELOPMENT only. Do NOT use in production.\n";
    while (true) {
        try {
            // Lấy token từ config để truyền vào curl_multi
            $botToken = trim((string) get_setting('telegram_bot_token', ''));
            if ($botToken === '') {
                $botToken = trim((string) EnvHelper::get('TELEGRAM_BOT_TOKEN', ''));
            }

            processOutboxParallel($outboxModel, $botToken);
            runPolling($telegram, $botLogic);
            cleanupOldData($outboxModel);
        } catch (Throwable $e) {
            echo "  [Error] " . $e->getMessage() . "\n";
        }
        usleep(500000); // 0.5s
    }
} else {
    // Cron mode: chạy 1 lần rồi thoát
    try {
        $botToken = trim((string) get_setting('telegram_bot_token', ''));
        if ($botToken === '') {
            $botToken = trim((string) EnvHelper::get('TELEGRAM_BOT_TOKEN', ''));
        }

        processOutboxParallel($outboxModel, $botToken);
        cleanupOldData($outboxModel);

        // Lưu last_cron_run cho Worker Health monitor
        $db = (new UserTelegramLink())->getConnection();
        saveLastCronRun($db);
        unset($db);

    } catch (Throwable $e) {
        echo '  [Error] ' . $e->getMessage() . "\n";
    }

    echo '[' . date('Y-m-d H:i:s') . "] Done.\n";
}

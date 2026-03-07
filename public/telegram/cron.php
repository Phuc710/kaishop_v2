<?php

/**
 * Telegram worker + cron tasks.
 *
 * Cron mode:
 *   php public/telegram/cron.php
 *
 * Polling mode (dev):
 *   php public/telegram/cron.php --poll
 */

require_once __DIR__ . '/../../config/app.php';

$isPollMode = in_array('--poll', $argv ?? [], true);

$telegram = new TelegramService();
$outboxModel = new TelegramOutbox();
$depositModel = new PendingDeposit();
$botLogic = new TelegramBotService($telegram);

$timeService = TimeService::instance();
echo '[' . $timeService->nowSql() . '] KaiShop Telegram Worker (' . ($isPollMode ? 'POLLING' : 'CRON') . ")\n";

/**
 * Prevent overlapping cron executions (especially when sweep window is enabled).
 *
 * @return array{handle:resource,path:string}|null
 */
function acquireSingleRunLock(string $lockName): ?array
{
    $lockPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $lockName . '.lock';
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

/**
 * @param array{handle:resource,path:string}|null $lock
 */
function releaseSingleRunLock(?array $lock): void
{
    if (!is_array($lock) || !isset($lock['handle'])) {
        return;
    }

    $handle = $lock['handle'];
    @flock($handle, LOCK_UN);
    @fclose($handle);
}

function processOutboxParallel(TelegramOutbox $outboxModel, string $botToken): void
{
    if ($botToken === '' || !function_exists('curl_multi_init')) {
        return;
    }

    $pending = $outboxModel->fetchPending(30);
    if (empty($pending)) {
        return;
    }

    echo '  [Outbox] Sending ' . count($pending) . " message(s)\n";

    $baseUrl = 'https://api.telegram.org/bot' . $botToken . '/sendMessage';
    $multiHandle = curl_multi_init();
    $handles = [];
    $idMap = [];

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
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        curl_multi_add_handle($multiHandle, $ch);
        $handles[$idx] = $ch;
        $idMap[$idx] = (int) $msg['id'];
    }

    $running = 0;
    do {
        $status = curl_multi_exec($multiHandle, $running);
        if ($status === CURLM_CALL_MULTI_PERFORM) {
            continue;
        }
        if ($running > 0) {
            curl_multi_select($multiHandle, 1.0);
        }
    } while ($running > 0 && $status === CURLM_OK);

    foreach ($handles as $idx => $ch) {
        $raw = curl_multi_getcontent($ch);
        $response = $raw ? json_decode($raw, true) : null;
        $outboxId = $idMap[$idx];

        if (!empty($response['ok'])) {
            $outboxModel->markSent($outboxId);
            echo "    [OK] Outbox #{$outboxId}\n";
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

function sendTelegramWithButton(string $botToken, int $telegramId, string $msg, array $keyboard): bool
{
    if ($botToken === '' || !function_exists('curl_init')) {
        return false;
    }

    $url = 'https://api.telegram.org/bot' . $botToken . '/sendMessage';
    $ch = curl_init($url);
    if ($ch === false) {
        return false;
    }

    $payload = json_encode([
        'chat_id' => $telegramId,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
        'reply_markup' => ['inline_keyboard' => $keyboard],
    ]);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_NOSIGNAL => 1,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $raw = curl_exec($ch);
    curl_close($ch);

    if (!$raw) {
        return false;
    }

    $resp = json_decode((string) $raw, true);
    return !empty($resp['ok']);
}

function cleanupOldData(TelegramOutbox $outboxModel): void
{
    if ((int) TimeService::instance()->nowDateTime()->format('G') !== 3) {
        return;
    }

    $deletedOutbox = $outboxModel->cleanOldSent(7);
    if ($deletedOutbox > 0) {
        echo "  [Cleanup] Removed {$deletedOutbox} old outbox messages\n";
    }

    $otpModel = new TelegramLinkCode();
    $deletedOtp = $otpModel->cleanExpired();
    if ($deletedOtp > 0) {
        echo "  [Cleanup] Removed {$deletedOtp} expired OTP codes\n";
    }

    $rlDir = '/tmp/kaishop_tg_rl';
    if (is_dir($rlDir)) {
        $cutoff = TimeService::instance()->nowTs() - 600;
        foreach (glob($rlDir . '/*.json') ?: [] as $file) {
            if (@filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
        if (is_dir($rlDir . '/cooldown')) {
            $cutoffCooldown = TimeService::instance()->nowTs() - 3600;
            foreach (glob($rlDir . '/cooldown/*.ts') ?: [] as $file) {
                if (@filemtime($file) < $cutoffCooldown) {
                    @unlink($file);
                }
            }
        }
    }
}

function notifyExpiredDeposits(PendingDeposit $depositModel, TelegramOutbox $outboxModel, string $botToken): void
{
    $justExpired = $depositModel->markExpired();
    if (empty($justExpired)) {
        return;
    }

    echo '  [Deposit] ' . count($justExpired) . " deposit(s) just expired\n";

    $linkModel = new UserTelegramLink();

    foreach ($justExpired as $dep) {
        $userId = (int) ($dep['user_id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }

        $link = $linkModel->findByUserId($userId);
        if (!$link) {
            continue;
        }

        $telegramId = (int) $link['telegram_id'];
        $code = htmlspecialchars((string) ($dep['deposit_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $amount = number_format((int) ($dep['amount'] ?? 0), 0, '.', ',');
        $method = strtolower(trim((string) ($dep['method'] ?? 'bank_sepay')));

        if ($method === DepositService::METHOD_BINANCE) {
            $usdtAmount = number_format((float) ($dep['usdt_amount'] ?? 0), 2, '.', '');
            $msg = "⏰ <b>YÊU CẦU NẠP BINANCE PAY ĐÃ HẾT HẠN</b>\n\n";
            $msg .= "📋 Mã nạp: <code>{$code}</code>\n";
            $msg .= "💵 Số tiền đăng ký: <b>{$amount}đ</b>\n";
            $msg .= "💰 USDT yêu cầu: <b>{$usdtAmount} USDT</b>\n\n";
            $msg .= "Lệnh đã quá 5 phút và tự động hủy.\n";
            $msg .= "Nếu đã chuyển tiền, vui lòng liên hệ hỗ trợ kèm TXID.";
        } else {
            $msg = "⏰ <b>GIAO DỊCH NẠP TIỀN ĐÃ HẾT HẠN</b>\n\n";
            $msg .= "📋 Mã nạp: <code>{$code}</code>\n";
            $msg .= "💵 Số tiền: <b>{$amount}đ</b>\n\n";
            $msg .= "Phiên nạp tiền đã quá 5 phút và tự động bị hủy.\n";
            $msg .= "👇 Bấm nút bên dưới để nạp lại.";
        }

        $retryCallback = ($method === DepositService::METHOD_BINANCE) ? 'binance_start' : 'deposit';
        $keyboard = [
            [
                ['text' => '💳 Nạp lại', 'callback_data' => $retryCallback],
                ['text' => '🏠 Menu', 'callback_data' => 'menu'],
            ]
        ];

        $sent = sendTelegramWithButton($botToken, $telegramId, $msg, $keyboard);

        if (!$sent) {
            $outboxModel->enqueue($telegramId, $msg);
            echo "    [Notify-Fallback] Deposit #" . (int) ($dep['id'] ?? 0) . "\n";
        } else {
            echo "    [Notify] Deposit #" . (int) ($dep['id'] ?? 0) . "\n";
        }
    }
}

function processPendingBinanceDeposits(PendingDeposit $depositModel): void
{
    static $lastRunTs = 0;
    $nowTs = TimeService::instance()->nowTs();
    if ($lastRunTs > 0 && ($nowTs - $lastRunTs) < 10) {
        return;
    }
    $lastRunTs = $nowTs;

    if (!class_exists('DepositService') || !class_exists('User')) {
        return;
    }

    $siteConfig = Config::getSiteConfig();
    $depositService = new DepositService($depositModel);
    $binanceService = $depositService->makeBinanceService($siteConfig);
    if (!$binanceService || !$binanceService->isEnabled()) {
        return;
    }

    $pending = $depositModel->fetchPendingByMethod(DepositService::METHOD_BINANCE, 30);
    if (empty($pending)) {
        return;
    }

    $minCreatedTs = TimeService::instance()->nowTs();
    foreach ($pending as $row) {
        $createdTs = TimeService::instance()->toTimestamp((string) ($row['created_at'] ?? ''));
        if ($createdTs !== null) {
            $minCreatedTs = min($minCreatedTs, $createdTs);
        }
    }

    $transactions = $binanceService->getRecentTransactions(
        ($minCreatedTs - 120) * 1000,
        (TimeService::instance()->nowTs() + 30) * 1000,
        100
    );
    if (empty($transactions)) {
        return;
    }

    $userModel = new User();
    $matchedCount = 0;

    foreach ($pending as $dep) {
        if ($depositModel->isLogicallyExpired($dep)) {
            continue;
        }
        $tx = $binanceService->findMatchingTransaction($dep, $transactions);
        if (!$tx) {
            continue;
        }

        $userId = (int) ($dep['user_id'] ?? 0);
        $user = $userId > 0 ? $userModel->find($userId) : null;
        if (!is_array($user) || empty($user['id'])) {
            continue;
        }

        $result = $binanceService->processTransaction($tx, $dep, $user);
        if (!empty($result['success'])) {
            $matchedCount++;
            echo "    [Binance] Credited deposit #" . (int) ($dep['id'] ?? 0) . " for user " . (string) ($user['username'] ?? '') . "\n";
        } else {
            echo "    [Binance] Match failed for deposit #" . (int) ($dep['id'] ?? 0) . ': ' . (string) ($result['message'] ?? 'unknown') . "\n";
        }
    }

    if ($matchedCount > 0) {
        echo "  [Binance] Processed {$matchedCount} pending deposit(s)\n";
    }
}

function saveLastCronRun(PDO $db): void
{
    try {
        $now = TimeService::instance()->nowSql();
        $stmt = $db->prepare("UPDATE `setting` SET `last_cron_run`=? ORDER BY `id` ASC LIMIT 1");
        $stmt->execute([$now]);
    } catch (Throwable $e) {
        error_log('[cron] Could not save last_cron_run: ' . $e->getMessage());
    }
}

function runPolling(TelegramService $telegram, TelegramBotService $botLogic): void
{
    $db = (new UserTelegramLink())->getConnection();

    $lastUpdateId = 0;
    try {
        $stmt = $db->query("SELECT `telegram_last_update_id` FROM `setting` ORDER BY `id` ASC LIMIT 1");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        $lastUpdateId = (int) ($row['telegram_last_update_id'] ?? 0);
    } catch (Throwable $e) {
        echo "  [Polling] Warning: Could not load last update ID - " . $e->getMessage() . "\n";
    }

    $response = $telegram->apiCall('getUpdates', [
        'offset' => $lastUpdateId + 1,
        'timeout' => 30,
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

function runCronSweepWindow(
    PendingDeposit $depositModel,
    TelegramOutbox $outboxModel,
    string $botToken,
    int $windowSeconds,
    int $intervalSeconds
): void {
    $windowSeconds = max(0, min(300, $windowSeconds));
    $intervalSeconds = max(5, min(30, $intervalSeconds));

    $startedAt = TimeService::instance()->nowTs();
    $deadline = $startedAt + $windowSeconds;

    do {
        processPendingBinanceDeposits($depositModel);
        notifyExpiredDeposits($depositModel, $outboxModel, $botToken);
        processOutboxParallel($outboxModel, $botToken);

        if ($windowSeconds <= 0) {
            break;
        }

        $now = TimeService::instance()->nowTs();
        $remaining = $deadline - $now;
        if ($remaining <= 0) {
            break;
        }

        sleep(min($intervalSeconds, $remaining));
    } while (true);

    if ($windowSeconds > 0) {
        echo "  [Binance] Sweep window done ({$windowSeconds}s, every {$intervalSeconds}s)\n";
    }
}

if ($isPollMode) {
    echo "  NOTE: Polling mode is for development only.\n";
    $db = (new UserTelegramLink())->getConnection();
    while (true) {
        try {
            $botToken = trim((string) get_setting('telegram_bot_token', ''));
            if ($botToken === '') {
                $botToken = trim((string) EnvHelper::get('TELEGRAM_BOT_TOKEN', ''));
            }

            processOutboxParallel($outboxModel, $botToken);
            processPendingBinanceDeposits($depositModel);
            notifyExpiredDeposits($depositModel, $outboxModel, $botToken);
            runPolling($telegram, $botLogic);
            saveLastCronRun($db);
            cleanupOldData($outboxModel);
        } catch (Throwable $e) {
            echo "  [Error] " . $e->getMessage() . "\n";
        }
        usleep(500000);
    }
} else {
    $lock = acquireSingleRunLock('kaishop_telegram_cron');
    if ($lock === null) {
        echo "  [Skip] Another cron instance is still running.\n";
        echo '[' . TimeService::instance()->nowSql() . "] Done.\n";
        exit(0);
    }

    try {
        $botToken = trim((string) get_setting('telegram_bot_token', ''));
        if ($botToken === '') {
            $botToken = trim((string) EnvHelper::get('TELEGRAM_BOT_TOKEN', ''));
        }

        $sweepWindowSeconds = (int) EnvHelper::get('BINANCE_SWEEP_WINDOW_SECONDS', 50);
        $sweepIntervalSeconds = (int) EnvHelper::get('BINANCE_SWEEP_INTERVAL_SECONDS', 10);
        runCronSweepWindow($depositModel, $outboxModel, $botToken, $sweepWindowSeconds, $sweepIntervalSeconds);

        cleanupOldData($outboxModel);

        $db = (new UserTelegramLink())->getConnection();
        saveLastCronRun($db);
        unset($db);
    } catch (Throwable $e) {
        echo '  [Error] ' . $e->getMessage() . "\n";
    } finally {
        releaseSingleRunLock($lock);
    }

    echo '[' . TimeService::instance()->nowSql() . "] Done.\n";
}

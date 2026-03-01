<?php

/**
 * Telegram Webhook Entry Point — Random URL (Production Security)
 *
 * URL ẩn: /api/bot_7f3kx9m2p4/index.php
 * Mục đích: Tránh bị người lạ scan port và spam request vào Webhook.
 *
 * Kỹ thuật:
 *  1. parseAndVerify() → kiểm tra Secret Token + IP Rate Limit
 *  2. Trả 200 OK NGAY cho Telegram (fastcgi_finish_request)
 *  3. Xử lý logic nền sau khi Telegram đã nhận phản hồi
 *
 * NOTE: Khi đổi URL này, phải cập nhật Webhook trên Telegram:
 *   Admin Panel → Telegram → Settings → Bật Webhook
 */

require_once __DIR__ . '/../../../../config/app.php';

$controller = new TelegramBotController();
$update = $controller->parseAndVerify();

if ($update === null) {
    // parseAndVerify đã gửi 403/429/400 rồi
    exit;
}

// Gửi 200 OK ngay cho Telegram (không để Telegram retry)
http_response_code(200);
header('Content-Type: application/json');
echo '{"ok":true}';

// Đóng kết nối với Telegram
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} elseif (function_exists('ob_end_flush')) {
    while (ob_get_level() > 0)
        ob_end_flush();
    flush();
}

// Xử lý update trong nền (Telegram đã nhận 200, không còn chờ)
try {
    $controller->processUpdateAsync($update);
} catch (Throwable $e) {
    error_log('[Telegram Webhook] Error: ' . $e->getMessage()
        . ' in ' . $e->getFile() . ':' . $e->getLine());
}

<?php

/**
 * Telegram Webhook Entry Point — Production Optimized
 *
 * Kỹ thuật:
 *  1. Parse JSON & verify → trả 200 OK ngay
 *  2. fastcgi_finish_request() — đóng kết nối với Telegram NGAY
 *  3. Tiếp tục xử lý logic (DB write, outbox push) trong nền
 *
 * Kết quả: Telegram nhận 200 ngay lập tức, không bao giờ retry.
 *          Script PHP vẫn xử lý đầy đủ phía sau mà không block Telegram.
 */

require_once __DIR__ . '/../../config/app.php';

// --- BƯỚC 1: Parse & validate TRƯỚC khi gửi 200 ---
$controller = new TelegramBotController();
$update = $controller->parseAndVerify();

if ($update === null) {
    // Đã trả 403/400 bên trong parseAndVerify()
    exit;
}

// --- BƯỚC 2: Gửi 200 OK ngay vì Telegram đã verified ---
http_response_code(200);
header('Content-Type: application/json');
echo '{"ok":true}';

// Flush buffer + đóng connection với Telegram
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} elseif (function_exists('ob_end_flush')) {
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
}

// --- BƯỚC 3: Xử lý logic nền (không block Telegram) ---
try {
    $controller->processUpdateAsync($update);
} catch (Throwable $e) {
    error_log('[Telegram Webhook] Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}

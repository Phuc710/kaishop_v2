<?php
require_once dirname(__DIR__) . '/config/app.php';

$targetUrl = function_exists('url') ? url('admin/setting') : '/admin/setting';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    header('Location: ' . $targetUrl, true, 302);
    exit;
}

http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => false,
    'message' => 'Legacy admin endpoint has been disabled. Use /admin/setting instead.',
], JSON_UNESCAPED_UNICODE);
exit;

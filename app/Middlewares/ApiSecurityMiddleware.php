<?php

/**
 * API security middleware
 * - Method whitelist
 * - Basic request size limits
 * - Per-IP rate limiting (file-based)
 * - JSON-only enforcement for selected endpoints
 */
class ApiSecurityMiddleware
{
    private const MAX_BODY_BYTES = 262144; // 256KB generic API guard

    /**
     * @param string $method HTTP method
     * @param string $path Normalized path
     */
    public function handle(string $method, string $path): bool
    {
        if (strpos($path, '/api/') !== 0) {
            return true;
        }

        $method = strtoupper($method);
        if (!in_array($method, ['GET', 'POST'], true)) {
            http_response_code(405);
            header('Allow: GET, POST');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return false;
        }

        if ($method === 'POST') {
            $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
            if ($contentLength > self::MAX_BODY_BYTES) {
                http_response_code(413);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Payload too large']);
                return false;
            }
        }

        if (!$this->checkRateLimit($path, $method)) {
            http_response_code(429);
            header('Content-Type: application/json; charset=utf-8');
            header('Retry-After: 60');
            echo json_encode(['success' => false, 'message' => 'Too many requests']);
            return false;
        }

        if ($this->requiresJson($path, $method)) {
            $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
            if ($contentType !== '' && strpos($contentType, 'application/json') === false) {
                http_response_code(415);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Unsupported content type']);
                return false;
            }
        }

        return true;
    }

    private function requiresJson(string $path, string $method): bool
    {
        if ($method !== 'POST') {
            return false;
        }

        return in_array($path, [
            '/api/sepay/webhook',
        ], true);
    }

    private function checkRateLimit(string $path, string $method): bool
    {
        $ip = $this->clientIp();
        $key = hash('sha256', $ip . '|' . $method . '|' . $path);
        $file = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ks_api_rl_' . $key . '.json';

        $windowSeconds = 60;
        $limit = 120;
        if ($path === '/api/sepay/webhook') {
            $limit = 300; // provider retries/bursts
        } elseif ($path === '/api/history-code') {
            $limit = 180;
        }

        $now = time();
        $state = ['start' => $now, 'count' => 0];

        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            return true; // fail-open to avoid site outage if temp dir issue
        }

        try {
            if (!@flock($fp, LOCK_EX)) {
                return true;
            }

            $raw = stream_get_contents($fp);
            if (is_string($raw) && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $state['start'] = (int) ($decoded['start'] ?? $now);
                    $state['count'] = (int) ($decoded['count'] ?? 0);
                }
            }

            if (($now - $state['start']) >= $windowSeconds) {
                $state = ['start' => $now, 'count' => 0];
            }

            $state['count']++;

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($state));
            fflush($fp);

            return $state['count'] <= $limit;
        } finally {
            @flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    private function clientIp(): string
    {
        $raw = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $parts = explode(',', $raw);
        return trim((string) ($parts[0] ?? '0.0.0.0'));
    }
}


<?php

/**
 * TelegramBotController — Production
 *
 * Hai luồng xử lý:
 *  A) Route-based  (POST /api/telegram/webhook → handleWebhook)
 *     Dùng khi project chạy qua Router (FastCGI/Apache mod-rewrite).
 *
 *  B) Direct-file  (public/api/bot_{secret}/index.php)
 *     Dùng khi muốn giấu URL Webhook (random path).
 *     File đó gọi parseAndVerify() + processUpdateAsync() trực tiếp.
 *
 * Bảo mật:
 *  - Secret Token HMAC-safe compare (HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN)
 *  - IP Rate limit: max 120 req/phút/IP (Telegram gửi từ nhiều server)
 */
class TelegramBotController extends Controller
{
    private TelegramService $telegram;
    private TelegramBotService $botLogic;

    public function __construct()
    {
        $this->telegram = new TelegramService();
        $this->botLogic = new TelegramBotService($this->telegram);
    }

    // =========================================================
    // A) Route-based entry point
    //    Route: POST /api/telegram/webhook → handleWebhook()
    // =========================================================

    /**
     * Main webhook handler — registered in routes.php.
     * Verifies, sends 200 OK, then processes in-process background.
     */
    public function handleWebhook(): void
    {
        $update = $this->parseAndVerify();
        if ($update === null)
            return; // already responded with 4xx

        // Flush 200 OK to Telegram immediately
        http_response_code(200);
        header('Content-Type: application/json');
        echo '{"ok":true}';

        // Close connection so Telegram stops waiting
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            while (ob_get_level() > 0)
                ob_end_flush();
            flush();
        }

        // Process in background (Telegram already got 200)
        $this->processUpdateAsync($update);
    }

    // =========================================================
    // B) Direct-file helpers (called from index.php in random dir)
    // =========================================================

    /**
     * Verify secret token + IP rate limit + parse JSON body.
     * Returns the $update array on success, null on failure.
     * Side-effect: sends 403/429/400 response header + body on failure.
     */
    public function parseAndVerify(): ?array
    {
        // 1. Secret token check
        $expectedSecret = TelegramConfig::webhookSecret();
        $providedSecret = trim((string) ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? ''));

        if ($expectedSecret !== '') {
            $hasSecretHeader = $providedSecret !== '';
            if (!$hasSecretHeader || !hash_equals($expectedSecret, $providedSecret)) {
                $reason = $hasSecretHeader ? 'invalid_secret' : 'missing_secret';
                Logger::warning(
                    'TelegramBot',
                    'webhook_unauthorized',
                    'Unauthorized webhook request: ' . $reason,
                    $this->buildUnauthorizedWebhookPayload($reason, $providedSecret)
                );
                http_response_code(403);
                header('Content-Type: application/json');
                echo '{"ok":false,"description":"Unauthorized"}';
                return null;
            }
        }

        // 2. IP rate limit
        $ip = $this->resolveClientIp();
        if (!$this->checkIpRateLimit($ip)) {
            Logger::warning(
                'TelegramBot',
                'webhook_rate_limited',
                'Webhook IP rate limit triggered',
                [
                    'ip' => $ip,
                    'uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
                    'method' => strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'POST')),
                ]
            );
            http_response_code(429);
            echo '{"ok":false,"description":"Rate limited"}';
            return null;
        }

        // 3. Parse JSON body
        $input = (string) @file_get_contents('php://input');
        if ($input === '') {
            http_response_code(400);
            echo '{"ok":false,"description":"Empty body"}';
            return null;
        }

        $update = json_decode($input, true);
        if (!is_array($update) || !isset($update['update_id'])) {
            http_response_code(400);
            echo '{"ok":false,"description":"Invalid JSON update"}';
            return null;
        }

        return $update;
    }

    private function buildUnauthorizedWebhookPayload(string $reason, string $providedSecret): array
    {
        $tokenHashPrefix = '';
        if ($providedSecret !== '') {
            $tokenHashPrefix = substr(hash('sha256', $providedSecret), 0, 16);
        }

        return [
            'reason' => $reason,
            'ip' => $this->resolveClientIp(),
            'uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
            'method' => strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'POST')),
            'has_secret_header' => $providedSecret !== '',
            'provided_secret_length' => strlen($providedSecret),
            'provided_secret_hash16' => $tokenHashPrefix,
            'content_type' => (string) ($_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '')),
            'content_length' => (int) ($_SERVER['CONTENT_LENGTH'] ?? 0),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200),
        ];
    }

    private function resolveClientIp(): string
    {
        $forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwardedFor !== '') {
            $parts = explode(',', $forwardedFor);
            $candidate = trim((string) ($parts[0] ?? ''));
            if ($candidate !== '') {
                return $candidate;
            }
        }

        $realIp = trim((string) ($_SERVER['HTTP_X_REAL_IP'] ?? ''));
        if ($realIp !== '') {
            return $realIp;
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /**
     * Process update — must run AFTER 200 OK is flushed to Telegram.
     */
    public function processUpdateAsync(array $update): void
    {
        try {
            $this->botLogic->processUpdate($update);
        } catch (Throwable $e) {
            Logger::danger(
                'TelegramBot',
                'update_error',
                $e->getMessage(),
                ['trace' => substr($e->getTraceAsString(), 0, 1000)]
            );
        }
    }

    // =========================================================
    // IP Rate Limiting (file-based, persistent across requests)
    // =========================================================

    private function checkIpRateLimit(string $ip): bool
    {
        $dir = TelegramConfig::rateDir();
        if (!is_dir($dir))
            @mkdir($dir, 0700, true);

        $file = $dir . '/' . md5('ip_' . $ip) . '.json';
        $now = time();
        $windowStart = $now - TelegramConfig::RATE_LIMIT_WINDOW;

        $timestamps = [];
        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            $timestamps = $raw ? (json_decode($raw, true) ?: []) : [];
        }

        $timestamps = array_values(array_filter($timestamps, static fn(int $ts) => $ts > $windowStart));

        if (count($timestamps) >= TelegramConfig::IP_RATE_LIMIT_MAX)
            return false;

        $timestamps[] = $now;
        @file_put_contents($file, json_encode($timestamps), LOCK_EX);

        return true;
    }
}

<?php

class Logger
{
    /**
     * Log an INFO message
     */
    public static function info(string $module, string $action, string $description, array $payload = []): void
    {
        self::log('INFO', $module, $action, $description, $payload);
    }

    /**
     * Log a WARNING message
     */
    public static function warning(string $module, string $action, string $description, array $payload = []): void
    {
        self::log('WARNING', $module, $action, $description, $payload);
    }

    /**
     * Log a DANGER (RED) alert message
     */
    public static function danger(string $module, string $action, string $description, array $payload = []): void
    {
        self::log('DANGER', $module, $action, $description, $payload);
    }

    /**
     * Write the log to the database
     */
    private static function log(string $severity, string $module, string $action, string $description, array $payload): void
    {
        try {
            // Ensure UserAgentParser is loaded
            if (!class_exists('UserAgentParser')) {
                require_once __DIR__ . '/UserAgentParser.php';
            }

            $db = Database::getInstance()->getConnection();

            $module = self::normalizeText($module);
            $action = self::normalizeText($action);
            $description = self::normalizeText($description);
            $payload = self::normalizePayload($payload);

            // Extract user info if available
            $userId = null;
            $username = null;

            if (isset($_SESSION['username'])) {
                $username = $_SESSION['username'];

                // Try to get user ID
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $userId = $user['id'];
                }
            }

            // Client Info
            $ipAddress = $_SERVER['HTTP_CLIENT_IP']
                ?? $_SERVER['HTTP_X_FORWARDED_FOR']
                ?? $_SERVER['REMOTE_ADDR']
                ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            // Enrich Payload with Device Information
            $deviceInfo = \App\Helpers\UserAgentParser::parse($userAgent);
            $payload['device_os'] = $deviceInfo['os'];
            $payload['device_browser'] = $deviceInfo['browser'];
            $payload['device_type'] = $deviceInfo['type'];

            // Insert
            $sql = "INSERT INTO `system_logs` 
                    (`user_id`, `username`, `module`, `action`, `description`, `payload`, `ip_address`, `user_agent`, `severity`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId,
                $username,
                $module,
                $action,
                $description,
                !empty($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
                $ipAddress,
                $userAgent,
                $severity
            ]);
        } catch (Exception $e) {
            // Silently fail if log cannot be written (to avoid breaking main application flow)
            error_log("Logger Error: " . $e->getMessage());
        }
    }

    private static function normalizePayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_string($value)) {
                $payload[$key] = self::normalizeText($value);
            } elseif (is_array($value)) {
                $payload[$key] = self::normalizePayload($value);
            }
        }
        return $payload;
    }

    private static function normalizeText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return $text;
        }

        if (!self::looksLikeMojibake($text)) {
            return $text;
        }

        $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $text);
        if (!is_string($converted) || $converted === '') {
            return $text;
        }

        return self::mojibakeScore($converted) < self::mojibakeScore($text) ? $converted : $text;
    }

    private static function looksLikeMojibake(string $text): bool
    {
        return (bool) preg_match('/(?:Ã.|Ä.|áº|á»|Æ.|â€¦|â€™|â€œ|â€|Â.)/u', $text);
    }

    private static function mojibakeScore(string $text): int
    {
        preg_match_all('/(?:Ã.|Ä.|áº|á»|Æ.|â€¦|â€™|â€œ|â€|Â.)/u', $text, $matches);
        return count($matches[0] ?? []);
    }
}

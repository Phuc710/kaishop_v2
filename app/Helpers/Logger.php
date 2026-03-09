<?php

class Logger
{
    private static bool $schemaChecked = false;
    private static bool $hasSourceChannelColumn = false;

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
            self::ensureSystemLogSchema($db);

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
            $ipAddress = ClientIpHelper::detect($_SERVER);
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $sourceChannel = SourceChannelHelper::fromSystemLogContext($module, $action, $payload, $_SERVER);

            // Enrich Payload with Device Information
            $deviceInfo = \App\Helpers\UserAgentParser::parse($userAgent);
            $payload['device_os'] = $deviceInfo['os'];
            $payload['device_browser'] = $deviceInfo['browser'];
            $payload['device_type'] = $deviceInfo['type'];
            $payload['source_channel'] = $sourceChannel;

            // Insert
            $columns = ['user_id', 'username', 'module', 'action', 'description', 'payload', 'ip_address', 'user_agent', 'severity'];
            $values = [
                $userId,
                $username,
                $module,
                $action,
                $description,
                !empty($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
                $ipAddress,
                $userAgent,
                $severity,
            ];
            if (self::$hasSourceChannelColumn) {
                $columns[] = 'source_channel';
                $values[] = $sourceChannel;
            }

            $marks = implode(', ', array_fill(0, count($columns), '?'));
            $sql = "INSERT INTO `system_logs` (`" . implode('`, `', $columns) . "`) VALUES ({$marks})";
            $stmt = $db->prepare($sql);
            $stmt->execute($values);
        } catch (Exception $e) {
            // Silently fail if log cannot be written (to avoid breaking main application flow)
            error_log("Logger Error: " . $e->getMessage());
        }
    }

    private static function ensureSystemLogSchema(PDO $db): void
    {
        if (!self::$schemaChecked) {
            try {
                $stmt = $db->query("
                    SELECT COUNT(*)
                    FROM information_schema.columns
                    WHERE table_schema = DATABASE()
                      AND table_name = 'system_logs'
                      AND column_name = 'source_channel'
                ");
                self::$hasSourceChannelColumn = (int) ($stmt ? $stmt->fetchColumn() : 0) > 0;
            } catch (Throwable $e) {
                self::$hasSourceChannelColumn = false;
            }

            if (!self::$hasSourceChannelColumn) {
                try {
                    $db->exec("ALTER TABLE `system_logs` ADD COLUMN `source_channel` TINYINT(1) NOT NULL DEFAULT 0 AFTER `severity`");
                } catch (Throwable $e) {
                    // ignore if ALTER is restricted
                }
                try {
                    $db->exec("ALTER TABLE `system_logs` ADD KEY `idx_system_logs_source_created` (`source_channel`, `created_at`)");
                } catch (Throwable $e) {
                    // ignore if key exists or ALTER is restricted
                }
                try {
                    $stmt = $db->query("
                        SELECT COUNT(*)
                        FROM information_schema.columns
                        WHERE table_schema = DATABASE()
                          AND table_name = 'system_logs'
                          AND column_name = 'source_channel'
                    ");
                    self::$hasSourceChannelColumn = (int) ($stmt ? $stmt->fetchColumn() : 0) > 0;
                } catch (Throwable $e) {
                    self::$hasSourceChannelColumn = false;
                }
            }

            self::$schemaChecked = true;
        }
    }
}

<?php

/**
 * TelegramService
 * Dedicated OOP service for Telegram bot notifications.
 */
class TelegramService
{
    private string $botToken;
    private string $chatId;
    private int $timeoutSeconds;

    public function __construct(?string $botToken = null, ?string $chatId = null, int $timeoutSeconds = 10)
    {
        $this->botToken = trim((string) ($botToken ?? $this->readConfig('TELEGRAM_BOT_TOKEN', 'telegram_bot_token')));
        $this->chatId = trim((string) ($chatId ?? $this->readConfig('TELEGRAM_CHAT_ID', 'telegram_chat_id')));
        $this->timeoutSeconds = max(3, $timeoutSeconds);
    }

    public function isConfigured(): bool
    {
        return $this->botToken !== '' && $this->chatId !== '';
    }

    public function send(string $message, array $options = []): bool
    {
        $text = trim($message);
        if ($text === '' || !$this->isConfigured()) {
            return false;
        }

        $payload = [
            'chat_id' => $this->chatId,
            'text' => $text,
            'disable_web_page_preview' => !empty($options['disable_web_page_preview']) ? 'true' : 'false',
        ];

        if (!empty($options['parse_mode'])) {
            $payload['parse_mode'] = (string) $options['parse_mode'];
        }

        if (!empty($options['disable_notification'])) {
            $payload['disable_notification'] = 'true';
        }

        return $this->postSendMessage($payload);
    }

    private function postSendMessage(array $payload): bool
    {
        $url = 'https://api.telegram.org/bot' . $this->botToken . '/sendMessage';

        if (!function_exists('curl_init')) {
            error_log('TelegramService: cURL extension is not available.');
            return false;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            error_log('TelegramService: request failed. ' . $curlError);
            return false;
        }

        $response = json_decode((string) $raw, true);
        if ($httpCode >= 200 && $httpCode < 300 && is_array($response) && !empty($response['ok'])) {
            return true;
        }

        $description = '';
        if (is_array($response)) {
            $description = (string) ($response['description'] ?? '');
        }

        error_log('TelegramService: send failed. HTTP ' . $httpCode . ($description !== '' ? (' - ' . $description) : ''));
        return false;
    }

    private function readConfig(string $envKey, string $settingKey): string
    {
        if (function_exists('get_setting')) {
            $fromSetting = trim((string) get_setting($settingKey, ''));
            if ($fromSetting !== '') {
                return $fromSetting;
            }

            $legacySettingMap = [
                'telegram_bot_token' => ['tele_bot_token', 'bot_token', 'apikey'],
                'telegram_chat_id' => ['tele_chat_id', 'chat_id', 'id_tele'],
            ];
            foreach (($legacySettingMap[$settingKey] ?? []) as $legacySettingKey) {
                $legacySettingValue = trim((string) get_setting($legacySettingKey, ''));
                if ($legacySettingValue !== '') {
                    return $legacySettingValue;
                }
            }
        }

        $value = $this->readEnv($envKey);
        if ($value !== '') {
            return $value;
        }

        $legacyEnvMap = [
            'TELEGRAM_BOT_TOKEN' => ['TELE_BOT_TOKEN', 'BOT_TOKEN'],
            'TELEGRAM_CHAT_ID' => ['TELE_CHAT_ID', 'CHAT_ID'],
        ];

        foreach (($legacyEnvMap[$envKey] ?? []) as $legacyKey) {
            $legacyValue = $this->readEnv($legacyKey);
            if ($legacyValue !== '') {
                return $legacyValue;
            }
        }

        return '';
    }

    private function readEnv(string $key): string
    {
        if (class_exists('EnvHelper')) {
            $value = trim((string) EnvHelper::get($key, ''));
            if ($value !== '') {
                return $value;
            }
        }

        $fromEnv = trim((string) ($_ENV[$key] ?? ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        return trim((string) getenv($key));
    }
}

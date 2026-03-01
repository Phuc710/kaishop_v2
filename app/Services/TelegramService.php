<?php

/**
 * Telegram Bot API low-level wrapper.
 */
class TelegramService
{
    private string $botToken;
    private string $chatId;
    private int $timeoutSeconds;

    public function __construct(?string $botToken = null, ?string $chatId = null, int $timeoutSeconds = 0)
    {
        $this->botToken = trim((string) ($botToken ?? TelegramConfig::botToken()));
        $this->chatId = trim((string) ($chatId ?? (string) TelegramConfig::primaryAdminId()));
        $this->timeoutSeconds = $timeoutSeconds > 0 ? $timeoutSeconds : TelegramConfig::CURL_TIMEOUT;
    }

    public function isConfigured(): bool
    {
        return $this->botToken !== '';
    }

    /**
     * Legacy send method. Sends to the default admin chat.
     */
    public function send(string $message, array $options = []): bool
    {
        if ($this->chatId === '') {
            return false;
        }

        return $this->sendTo($this->chatId, $message, $options);
    }

    /**
     * Send a message to a specific chat ID.
     */
    public function sendTo(string $chatId, string $message, array $options = []): bool
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => trim($message),
            'parse_mode' => $options['parse_mode'] ?? 'HTML',
            'disable_web_page_preview' => !empty($options['disable_web_page_preview']) ? 'true' : 'false',
        ];

        if (!empty($options['reply_markup'])) {
            $payload['reply_markup'] = is_string($options['reply_markup'])
                ? $options['reply_markup']
                : json_encode($options['reply_markup'], JSON_UNESCAPED_UNICODE);
        }

        if (!empty($options['disable_notification'])) {
            $payload['disable_notification'] = 'true';
        }

        $result = $this->apiCall('sendMessage', $payload);
        return !empty($result['ok']);
    }

    /**
     * Edit an existing message.
     *
     * @param mixed $keyboard
     */
    public function editMessage(string $chatId, int $messageId, string $text, $keyboard = null): bool
    {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => trim($text),
            'parse_mode' => 'HTML',
        ];

        if ($keyboard !== null) {
            $payload['reply_markup'] = is_string($keyboard)
                ? $keyboard
                : json_encode($keyboard, JSON_UNESCAPED_UNICODE);
        }

        $result = $this->apiCall('editMessageText', $payload);
        return !empty($result['ok']);
    }

    /**
     * Delete a message.
     */
    public function deleteMessage(string $chatId, int $messageId): bool
    {
        $result = $this->apiCall('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);

        return !empty($result['ok']);
    }

    /**
     * Remove callback loading spinner on button click.
     */
    public function answerCallbackQuery(string $callbackId, string $text = '', bool $showAlert = false): bool
    {
        $result = $this->apiCall('answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text' => $text,
            'show_alert' => $showAlert ? 'true' : 'false',
        ]);

        return !empty($result['ok']);
    }

    /**
     * Set the bot webhook.
     */
    public function setWebhook(string $url, ?string $secretToken = null): array
    {
        $payload = ['url' => $url];

        if ($secretToken !== null && $secretToken !== '') {
            $payload['secret_token'] = $secretToken;
        }

        return $this->apiCall('setWebhook', $payload);
    }

    /**
     * Wrapper for general system notifications.
     */
    public function sendNotification(string $chatId, string $text): bool
    {
        return $this->sendTo($chatId, "<b>SYSTEM NOTIFICATION</b>\n\n" . $text);
    }

    /**
     * Get basic bot information.
     */
    public function getMe(): array
    {
        $cacheKey = 'tg_me_' . md5($this->botToken ?: 'no_token');
        $cached = AppCache::get($cacheKey, TelegramConfig::CACHE_TTL_GET_ME);
        if ($cached !== null) {
            return $cached;
        }

        $response = $this->apiCall('getMe');
        if (!empty($response['ok'])) {
            AppCache::set($cacheKey, $response);
        }

        return $response;
    }

    /**
     * Get current webhook information.
     */
    public function getWebhookInfo(): array
    {
        $cacheKey = 'tg_webhook_' . md5($this->botToken ?: 'no_token');
        $cached = AppCache::get($cacheKey, TelegramConfig::CACHE_TTL_WEBHOOK);
        if ($cached !== null) {
            return $cached;
        }

        $response = $this->apiCall('getWebhookInfo');
        if (!empty($response['ok'])) {
            AppCache::set($cacheKey, $response);
        }

        return $response;
    }

    /**
     * Remove webhook integration.
     */
    public function deleteWebhook(bool $dropPendingUpdates = false): array
    {
        $params = [];
        if ($dropPendingUpdates) {
            $params['drop_pending_updates'] = 'true';
        }

        return $this->apiCall('deleteWebhook', $params);
    }

    public function apiCall(string $method, array $params = []): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'description' => 'Bot token not configured'];
        }

        if (!function_exists('curl_init')) {
            error_log('TelegramService: cURL extension not available.');
            return ['ok' => false, 'description' => 'cURL extension not available'];
        }

        $url = 'https://api.telegram.org/bot' . $this->botToken . '/' . $method;
        $ch = curl_init($url);

        if ($ch === false) {
            return ['ok' => false, 'description' => 'Could not initialize cURL'];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => TelegramConfig::CURL_CONNECT_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            error_log('TelegramService cURL error: ' . $curlError . ' (method=' . $method . ')');
            return ['ok' => false, 'description' => 'cURL error: ' . $curlError];
        }

        $response = json_decode((string) $raw, true);
        if (!is_array($response)) {
            return ['ok' => false, 'description' => 'Invalid JSON response'];
        }

        if (empty($response['ok'])) {
            error_log('TelegramService API error [' . $method . ']: ' . ($response['description'] ?? 'unknown'));
        }

        return $response;
    }

    /**
     * Inline keyboard builder.
     */
    public static function buildInlineKeyboard(array $rows): array
    {
        return ['inline_keyboard' => $rows];
    }
}

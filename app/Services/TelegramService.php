<?php

/**
 * Telegram Bot API low-level wrapper.
 */
class TelegramService
{
    private const TELEGRAM_MESSAGE_CHAR_LIMIT = 3500;

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
        $chunks = $this->splitMessageIntoChunks($message, self::TELEGRAM_MESSAGE_CHAR_LIMIT);
        if (empty($chunks)) {
            return false;
        }
        $total = count($chunks);

        foreach ($chunks as $index => $chunk) {
            $payload = [
                'chat_id' => $chatId,
                'text' => $chunk,
                'parse_mode' => $options['parse_mode'] ?? 'HTML',
                'disable_web_page_preview' => !empty($options['disable_web_page_preview']) ? 'true' : 'false',
                'link_preview_options' => !empty($options['disable_web_page_preview']) ? json_encode(['is_disabled' => true]) : null,
            ];

            if (!empty($options['reply_markup']) && $index === 0) {
                $payload['reply_markup'] = is_string($options['reply_markup'])
                    ? $options['reply_markup']
                    : json_encode($options['reply_markup'], JSON_UNESCAPED_UNICODE);
            }

            if (!empty($options['disable_notification']) && $index === 0) {
                $payload['disable_notification'] = 'true';
            }

            // Add a small continuation marker when the response spans multiple messages.
            if ($total > 1 && $index > 0) {
                $payload['text'] = "(tiep)\n" . $payload['text'];
            }

            $result = $this->apiCall('sendMessage', $payload);
            if (empty($result['ok'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Send a message and return the message_id (0 on failure).
     * Used for the edit-message pattern where callers need to track
     * which message to edit later.
     */
    public function sendToWithResult(string $chatId, string $message, array $options = []): int
    {
        $chunks = $this->splitMessageIntoChunks($message, self::TELEGRAM_MESSAGE_CHAR_LIMIT);
        if (empty($chunks)) {
            return 0;
        }
        $total = count($chunks);
        $firstMessageId = 0;

        foreach ($chunks as $index => $chunk) {
            $payload = [
                'chat_id' => $chatId,
                'text' => $chunk,
                'parse_mode' => $options['parse_mode'] ?? 'HTML',
                'disable_web_page_preview' => !empty($options['disable_web_page_preview']) ? 'true' : 'false',
                'link_preview_options' => !empty($options['disable_web_page_preview']) ? json_encode(['is_disabled' => true]) : null,
            ];

            if (!empty($options['reply_markup']) && $index === 0) {
                $payload['reply_markup'] = is_string($options['reply_markup'])
                    ? $options['reply_markup']
                    : json_encode($options['reply_markup'], JSON_UNESCAPED_UNICODE);
            }

            if (!empty($options['disable_notification']) && $index === 0) {
                $payload['disable_notification'] = 'true';
            }

            if ($total > 1 && $index > 0) {
                $payload['text'] = "(tiep)\n" . $payload['text'];
            }

            $result = $this->apiCall('sendMessage', $payload);
            if (empty($result['ok'])) {
                return 0;
            }

            if ($index === 0 && isset($result['result']['message_id'])) {
                $firstMessageId = (int) $result['result']['message_id'];
            }
        }

        return $firstMessageId;
    }

    /**
     * Send photo by URL or file_id to a specific chat.
     */
    public function sendPhotoTo(string $chatId, $photo, ?string $caption = null, array $options = []): bool
    {
        $payload = [
            'chat_id' => $chatId,
            'photo' => trim($photo),
            'parse_mode' => $options['parse_mode'] ?? 'HTML',
        ];

        if ($caption !== null && trim($caption) !== '') {
            $payload['caption'] = trim($caption);
        }

        if (!empty($options['reply_markup'])) {
            $payload['reply_markup'] = is_string($options['reply_markup'])
                ? $options['reply_markup']
                : json_encode($options['reply_markup'], JSON_UNESCAPED_UNICODE);
        }

        $result = $this->apiCall('sendPhoto', $payload);
        return !empty($result['ok']);
    }

    /**
     * Edit an existing message.
     *
     * @param mixed $keyboard
     */
    public function editMessage(string $chatId, int $messageId, string $text, $keyboard = null, array $options = []): bool
    {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => trim($text),
            'parse_mode' => $options['parse_mode'] ?? 'HTML',
            'disable_web_page_preview' => !empty($options['disable_web_page_preview']) ? 'true' : 'false',
            'link_preview_options' => !empty($options['disable_web_page_preview']) ? json_encode(['is_disabled' => true]) : null,
        ];

        if ($keyboard !== null) {
            $payload['reply_markup'] = is_string($keyboard)
                ? $keyboard
                : json_encode($keyboard, JSON_UNESCAPED_UNICODE);
        }

        $result = $this->apiCall('editMessageText', $payload);

        if (empty($result['ok'])) {
            $desc = (string) ($result['description'] ?? '');
            if (str_contains($desc, 'message is not modified')) {
                return true; // Technically success
            }
        }

        return !empty($result['ok']);
    }

    /**
     * Edit existing message if messageId > 0, otherwise send new message.
     * If editMessage fails, falls back to sendTo so user always sees the response.
     *
     * @param array|null $keyboard  Inline keyboard markup array (not encoded)
     */
    public function editOrSend(string $chatId, int $messageId, string $text, ?array $keyboard = null, array $options = []): bool
    {
        if ($messageId > 0) {
            $edited = $this->editMessage($chatId, $messageId, $text, $keyboard, $options);
            if ($edited) {
                return true;
            }
            // Edit failed — fallback to sendTo
        }

        $options = [];
        if ($keyboard !== null) {
            $options['reply_markup'] = $keyboard;
        }
        return $this->sendTo($chatId, $text, $options);
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

    /**
     * Set the bot commands list.
     */
    public function setMyCommands(array $commands, ?string $scope = null, ?string $languageCode = null): array
    {
        $payload = ['commands' => json_encode($commands, JSON_UNESCAPED_UNICODE)];
        if ($scope !== null) {
            $payload['scope'] = json_encode($scope);
        }
        if ($languageCode !== null) {
            $payload['language_code'] = $languageCode;
        }

        return $this->apiCall('setMyCommands', $payload);
    }

    /**
     * Get current bot commands.
     */
    public function getMyCommands(?string $scope = null, ?string $languageCode = null): array
    {
        $payload = [];
        if ($scope !== null) {
            $payload['scope'] = json_encode($scope);
        }
        if ($languageCode !== null) {
            $payload['language_code'] = $languageCode;
        }
        return $this->apiCall('getMyCommands', $payload);
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

        $timeout = $this->timeoutSeconds;
        if ($method === 'getUpdates') {
            $longPollTimeout = (int) ($params['timeout'] ?? 0);
            if ($longPollTimeout > 0) {
                $timeout = max($timeout, $longPollTimeout + 5);
            }
        }

        $postFields = $this->payloadContainsBinary($params) ? $params : http_build_query($params);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_TIMEOUT => $timeout,
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

    private function payloadContainsBinary(array $params): bool
    {
        foreach ($params as $value) {
            if ($value instanceof CURLFile) {
                return true;
            }
        }

        return false;
    }

    /**
     * Split long messages to stay within Telegram sendMessage limits.
     *
     * @return string[]
     */
    private function splitMessageIntoChunks(string $message, int $limit): array
    {
        $message = trim($message);
        if ($message === '') {
            return [];
        }

        if ($this->textLength($message) <= $limit) {
            return [$message];
        }

        $lines = preg_split('/\R/u', $message) ?: [$message];
        $chunks = [];
        $current = '';

        foreach ($lines as $line) {
            $line = rtrim((string) $line);
            $candidate = ($current === '') ? $line : ($current . "\n" . $line);

            if ($this->textLength($candidate) <= $limit) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $chunks[] = $current;
                $current = '';
            }

            if ($this->textLength($line) <= $limit) {
                $current = $line;
                continue;
            }

            foreach ($this->splitOversizedLine($line, $limit) as $piece) {
                $chunks[] = $piece;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks ?: [$this->textSubstr($message, 0, $limit)];
    }

    /**
     * @return string[]
     */
    private function splitOversizedLine(string $line, int $limit): array
    {
        $line = trim($line);
        if ($line === '') {
            return [];
        }

        $parts = [];
        while ($this->textLength($line) > $limit) {
            $slice = $this->textSubstr($line, 0, $limit);
            $cutAt = $this->textLastSpacePos($slice);

            if ($cutAt <= 0 || $cutAt < (int) ($limit * 0.5)) {
                $cutAt = $limit;
            }

            $part = trim($this->textSubstr($line, 0, $cutAt));
            if ($part === '') {
                $part = trim($slice);
                $cutAt = $this->textLength($slice);
            }

            if ($part !== '') {
                $parts[] = $part;
            }

            $line = ltrim($this->textSubstr($line, $cutAt));
        }

        if ($line !== '') {
            $parts[] = $line;
        }

        return $parts;
    }

    private function textLength(string $text): int
    {
        if (function_exists('mb_strlen')) {
            return (int) mb_strlen($text, 'UTF-8');
        }
        return strlen($text);
    }

    private function textSubstr(string $text, int $start, ?int $length = null): string
    {
        if (function_exists('mb_substr')) {
            return (string) mb_substr($text, $start, $length, 'UTF-8');
        }
        return (string) ($length === null ? substr($text, $start) : substr($text, $start, $length));
    }

    private function textLastSpacePos(string $text): int
    {
        if (function_exists('mb_strrpos')) {
            $spacePos = mb_strrpos($text, ' ', 0, 'UTF-8');
            $tabPos = mb_strrpos($text, "\t", 0, 'UTF-8');
            $best = max($spacePos === false ? -1 : (int) $spacePos, $tabPos === false ? -1 : (int) $tabPos);
            return $best;
        }

        $spacePos = strrpos($text, ' ');
        $tabPos = strrpos($text, "\t");
        $best = max($spacePos === false ? -1 : (int) $spacePos, $tabPos === false ? -1 : (int) $tabPos);
        return $best;
    }

    /**
     * Reply keyboard builder (persistent button menu).
     */
    public static function buildReplyKeyboard(
        array $rows,
        bool $resize = true,
        bool $oneTime = false,
        bool $persistent = true
    ): array {
        return [
            'keyboard' => $rows,
            'resize_keyboard' => $resize,
            'one_time_keyboard' => $oneTime,
            'is_persistent' => $persistent,
        ];
    }
}

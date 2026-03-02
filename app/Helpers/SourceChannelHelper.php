<?php

/**
 * SourceChannelHelper
 * Unified source channel mapping:
 * - 0: Web
 * - 1: BotTele
 */
class SourceChannelHelper
{
    public const WEB = 0;
    public const BOTTELE = 1;

    /**
     * @param mixed $value
     */
    public static function normalize($value): int
    {
        if (is_bool($value)) {
            return $value ? self::BOTTELE : self::WEB;
        }

        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric(trim($value)))) {
            return ((int) $value) === self::BOTTELE ? self::BOTTELE : self::WEB;
        }

        $raw = strtolower(trim((string) $value));
        if ($raw === '') {
            return self::WEB;
        }

        if (in_array($raw, ['telegram', 'tele', 'telebot', 'bot', 'tg', 'bottele', 'bot_tele'], true)) {
            return self::BOTTELE;
        }

        return self::WEB;
    }

    public static function label(int $sourceChannel): string
    {
        return $sourceChannel === self::BOTTELE ? 'BotTele' : 'Web';
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function fromOrderRow(array $row): int
    {
        if (array_key_exists('source_channel', $row)) {
            return self::normalize($row['source_channel']);
        }

        $sourceRaw = trim((string) ($row['source'] ?? ''));
        if (self::normalize($sourceRaw) === self::BOTTELE) {
            return self::BOTTELE;
        }

        return ((int) ($row['telegram_id'] ?? 0) > 0) ? self::BOTTELE : self::WEB;
    }

    /**
     * @param mixed $payload
     * @param array<string,mixed> $server
     */
    public static function fromSystemLogContext(string $module, string $action, $payload = null, array $server = []): int
    {
        $payloadArr = self::payloadToArray($payload);
        if (is_array($payloadArr)) {
            if (array_key_exists('source_channel', $payloadArr)) {
                return self::normalize($payloadArr['source_channel']);
            }
            if (array_key_exists('source', $payloadArr)) {
                return self::normalize($payloadArr['source']);
            }
            if (array_key_exists('telegram_id', $payloadArr) && (int) $payloadArr['telegram_id'] > 0) {
                return self::BOTTELE;
            }
        }

        $moduleNorm = strtolower(trim($module));
        $actionNorm = strtolower(trim($action));
        if (strpos($moduleNorm, 'telegram') !== false || strpos($actionNorm, 'telegram') !== false) {
            return self::BOTTELE;
        }

        $requestUri = strtolower(trim((string) ($server['REQUEST_URI'] ?? '')));
        if ($requestUri !== '' && strpos($requestUri, '/api/telegram/') !== false) {
            return self::BOTTELE;
        }

        return self::WEB;
    }

    /**
     * @param mixed $payload
     * @return array<string,mixed>|null
     */
    private static function payloadToArray($payload): ?array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (!is_string($payload) || trim($payload) === '') {
            return null;
        }

        $decoded = json_decode($payload, true);
        return is_array($decoded) ? $decoded : null;
    }
}

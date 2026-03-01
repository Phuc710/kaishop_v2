<?php

/**
 * CryptoService
 * Encrypt/decrypt short sensitive strings stored in DB.
 */
class CryptoService
{
    private const VERSION_PREFIX = 'enc:v1:';
    private const CIPHER = 'aes-256-gcm';
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;

    private string $key;

    public function __construct(?string $appKey = null)
    {
        $resolvedKey = $appKey;
        if ($resolvedKey === null) {
            $resolvedKey = defined('APP_KEY') ? (string) APP_KEY : '';
        }

        $resolvedKey = trim((string) $resolvedKey);
        $this->key = $resolvedKey !== '' ? $this->normalizeKey($resolvedKey) : '';
    }

    public function isEnabled(): bool
    {
        return $this->key !== '' && extension_loaded('openssl');
    }

    public function encryptString(string $plainText): string
    {
        if ($plainText === '' || !$this->isEnabled()) {
            return $plainText;
        }

        try {
            $iv = random_bytes(self::IV_LENGTH);
            $tag = '';
            $cipherText = openssl_encrypt(
                $plainText,
                self::CIPHER,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                '',
                self::TAG_LENGTH
            );

            if (!is_string($cipherText) || $cipherText === '' || !is_string($tag) || $tag === '') {
                return $plainText;
            }

            $payload = $iv . $tag . $cipherText;
            return self::VERSION_PREFIX . $this->base64UrlEncode($payload);
        } catch (Throwable $e) {
            return $plainText;
        }
    }

    public function decryptString(string $storedValue): string
    {
        if ($storedValue === '') {
            return '';
        }

        if (strpos($storedValue, self::VERSION_PREFIX) !== 0) {
            return $storedValue;
        }

        if (!$this->isEnabled()) {
            return $storedValue;
        }

        $encoded = substr($storedValue, strlen(self::VERSION_PREFIX));
        $payload = $this->base64UrlDecode($encoded);
        if (!is_string($payload) || $payload === '') {
            return $storedValue;
        }

        if (strlen($payload) <= (self::IV_LENGTH + self::TAG_LENGTH)) {
            return $storedValue;
        }

        $iv = substr($payload, 0, self::IV_LENGTH);
        $tag = substr($payload, self::IV_LENGTH, self::TAG_LENGTH);
        $cipherText = substr($payload, self::IV_LENGTH + self::TAG_LENGTH);

        try {
            $plainText = openssl_decrypt(
                $cipherText,
                self::CIPHER,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            return is_string($plainText) ? $plainText : $storedValue;
        } catch (Throwable $e) {
            return $storedValue;
        }
    }

    private function normalizeKey(string $rawKey): string
    {
        if (ctype_xdigit($rawKey) && strlen($rawKey) % 2 === 0) {
            $bin = hex2bin($rawKey);
            if (is_string($bin) && $bin !== '') {
                return hash('sha256', $bin, true);
            }
        }

        return hash('sha256', $rawKey, true);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data)
    {
        $base64 = strtr($data, '-_', '+/');
        $padding = strlen($base64) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($base64, true);
    }
}

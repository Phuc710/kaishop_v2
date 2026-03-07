<?php

/**
 * SecureCrypto
 *
 * AES-256-GCM authenticated encryption for sensitive DB values.
 * Uses APP_KEY (64-char hex from .env) as the master key.
 *
 * Format of encrypted value stored in DB:
 *   enc:<base64(iv)>.<base64(ciphertext)>.<base64(tag)>
 *
 * Values NOT prefixed with "enc:" are treated as plaintext (backward-compat).
 */
class SecureCrypto
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_LEN = 12;  // GCM standard
    private const TAG_LEN = 16;
    private const PREFIX = 'enc:';

    /**
     * Encrypt a plaintext string using APP_KEY.
     * Returns the original value unchanged if:
     *   - APP_KEY is not set / empty
     *   - OpenSSL GCM is unavailable
     *   - Input is already encrypted
     */
    public static function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            return $plaintext;
        }

        // Already encrypted → don't double-encrypt
        if (str_starts_with($plaintext, self::PREFIX)) {
            return $plaintext;
        }

        $key = self::resolveKey();
        if ($key === '') {
            return $plaintext;  // No key → store as-is (safe fallback)
        }

        $iv = random_bytes(self::IV_LEN);
        $tag = '';

        $cipher = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LEN
        );

        if ($cipher === false) {
            return $plaintext;  // Encryption failed → store as-is
        }

        return self::PREFIX
            . base64_encode($iv) . '.'
            . base64_encode($cipher) . '.'
            . base64_encode($tag);
    }

    /**
     * Decrypt a value encrypted by encrypt().
     * Returns the original value unchanged if not encrypted.
     */
    public static function decrypt(string $value): string
    {
        if ($value === '' || !str_starts_with($value, self::PREFIX)) {
            return $value;  // Plaintext passthrough (backward-compat)
        }

        $key = self::resolveKey();
        if ($key === '') {
            return $value;
        }

        $body = substr($value, strlen(self::PREFIX));
        $parts = explode('.', $body, 3);

        if (count($parts) !== 3) {
            return $value;  // Malformed → return as-is
        }

        [$ivB64, $cipherB64, $tagB64] = $parts;

        $iv = base64_decode($ivB64, true);
        $cipher = base64_decode($cipherB64, true);
        $tag = base64_decode($tagB64, true);

        if ($iv === false || $cipher === false || $tag === false) {
            return $value;
        }

        $plain = openssl_decrypt(
            $cipher,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $plain !== false ? $plain : $value;
    }

    /**
     * Returns true if the value looks like it was encrypted by this class.
     */
    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    /**
     * Resolve 32-byte raw key from APP_KEY (hex string).
     */
    private static function resolveKey(): string
    {
        $hex = defined('APP_KEY') ? (string) APP_KEY : '';
        if ($hex === '') {
            $hex = (string) (class_exists('EnvHelper') ? EnvHelper::get('APP_KEY', '') : '');
        }

        $hex = trim($hex);
        if (strlen($hex) < 64 || !ctype_xdigit($hex)) {
            return '';
        }

        // Use first 32 bytes (256 bits) from the hex key
        $raw = hex2bin(substr($hex, 0, 64));
        return $raw !== false ? $raw : '';
    }
}

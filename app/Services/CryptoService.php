<?php

/**
 * Lightweight application crypto helper (AES-256-GCM/CBC) using APP_KEY.
 * Used for encrypting sensitive data at rest (e.g., product stock content).
 */
class CryptoService
{
    private string $key;
    private bool $enabled;

    public function __construct()
    {
        $appKey = '';
        if (class_exists('EnvHelper')) {
            $appKey = (string) EnvHelper::get('APP_KEY', '');
        } elseif (defined('APP_KEY')) {
            $appKey = (string) APP_KEY;
        }

        $appKey = trim($appKey);
        $this->enabled = $appKey !== '';
        $this->key = $this->enabled ? hash('sha256', $appKey, true) : '';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function encryptString(string $plain): string
    {
        if (!$this->enabled || $plain === '') {
            return $plain;
        }

        if (strpos($plain, 'enc:v') === 0) {
            return $plain;
        }

        if (in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
            $iv = random_bytes(12);
            $tag = '';
            $cipher = openssl_encrypt($plain, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);
            if ($cipher !== false && $tag !== '') {
                return 'enc:v2:gcm:' . base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($cipher);
            }
        }

        // Fallback AES-256-CBC + HMAC (works on older OpenSSL configs)
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            return $plain;
        }
        $mac = hash_hmac('sha256', $iv . $cipher, $this->key, true);
        return 'enc:v1:cbc:' . base64_encode($iv) . ':' . base64_encode($mac) . ':' . base64_encode($cipher);
    }

    public function decryptString(string $value): string
    {
        if (!$this->enabled || $value === '' || strpos($value, 'enc:v') !== 0) {
            return $value;
        }

        $parts = explode(':', $value, 6);
        if (count($parts) < 6) {
            return $value;
        }

        // enc:v2:gcm:iv:tag:cipher OR enc:v1:cbc:iv:mac:cipher
        [, $version, $mode, $ivB64, $authB64, $cipherB64] = $parts;
        $iv = base64_decode($ivB64, true);
        $auth = base64_decode($authB64, true);
        $cipher = base64_decode($cipherB64, true);
        if ($iv === false || $auth === false || $cipher === false) {
            return $value;
        }

        if ($version === 'v2' && $mode === 'gcm') {
            $plain = openssl_decrypt($cipher, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $auth);
            return is_string($plain) ? $plain : $value;
        }

        if ($version === 'v1' && $mode === 'cbc') {
            $mac = hash_hmac('sha256', $iv . $cipher, $this->key, true);
            if (!hash_equals($mac, $auth)) {
                return $value;
            }
            $plain = openssl_decrypt($cipher, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv);
            return is_string($plain) ? $plain : $value;
        }

        return $value;
    }
}

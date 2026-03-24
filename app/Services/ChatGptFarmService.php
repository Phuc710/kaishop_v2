<?php

/**
 * ChatGptFarmService
 * Multi-farm OpenAI API wrapper
 * Handles all calls to OpenAI Admin API on behalf of a specific farm (by its key)
 */
class ChatGptFarmService
{
    const BASE_URL = 'https://api.openai.com/v1';
    const TIMEOUT = 20;

    /**
     * Make an HTTP request to OpenAI Admin API using the given farm's key
     *
     * @param array  $farm     Farm record (must include admin_api_key)
     * @param string $method   HTTP method: GET, POST, DELETE
     * @param string $endpoint e.g. /organization/invites
     * @param array  $body     Optional request body
     * @return array ['data' => ..., '_http_code' => int, '_error' => string|null]
     */
    public function request($farm, $method, $endpoint, $body = [])
    {
        $apiKey = $this->decryptKey($farm['admin_api_key'] ?? '');
        if ($apiKey === '') {
            return ['_http_code' => 0, '_error' => 'Missing or unreadable API key for farm #' . ($farm['id'] ?? '?')];
        }

        $url = self::BASE_URL . $endpoint;
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ];

        if (!empty($farm['openai_org_id'])) {
            $headers[] = 'OpenAI-Organization: ' . $farm['openai_org_id'];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            return ['_http_code' => 0, '_error' => 'cURL error: ' . $curlError];
        }

        $data = json_decode((string) $raw, true) ?? [];
        $data['_http_code'] = $code;
        $data['_error'] = ($code < 200 || $code >= 300) ? ($data['error']['message'] ?? 'HTTP ' . $code) : null;

        return $data;
    }

    /**
     * Validate that a farm's API key actually works by listing invites
     */
    public function validateKey($farm)
    {
        $res = $this->request($farm, 'GET', '/organization/invites?limit=1');
        return ($res['_http_code'] ?? 0) >= 200 && ($res['_http_code'] ?? 0) < 300;
    }

    /**
     * List all current members in the farm's organization
     */
    public function listMembers($farm, $limit = 100)
    {
        $res = $this->request($farm, 'GET', '/organization/users?limit=' . $limit);
        return $res['data'] ?? [];
    }

    /**
     * List all pending/active invites in the farm's organization
     */
    public function listInvites($farm, $limit = 100)
    {
        $res = $this->request($farm, 'GET', '/organization/invites?limit=' . $limit);
        return $res['data'] ?? [];
    }

    /**
     * Create an invite for $email with role 'reader' (default)
     *
     * @return array ['success' => bool, 'invite_id' => string|null, 'error' => string|null]
     */
    public function createInvite($farm, $email, $role = 'reader')
    {
        $res = $this->request($farm, 'POST', '/organization/invites', [
            'email' => $email,
            'role' => $role,
        ]);
        $code = $res['_http_code'] ?? 0;
        if ($code >= 200 && $code < 300) {
            return [
                'success' => true,
                'invite_id' => $res['id'] ?? null,
                'email' => $res['email'] ?? $email,
                'role' => $res['role'] ?? $role,
            ];
        }
        return [
            'success' => false,
            'invite_id' => null,
            'error' => $res['_error'] ?? 'Unknown API error',
        ];
    }

    /**
     * Revoke (delete) a pending invite by its OpenAI invite_id
     *
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function revokeInvite($farm, $inviteId)
    {
        $res = $this->request($farm, 'DELETE', '/organization/invites/' . $inviteId);
        $code = $res['_http_code'] ?? 0;
        return [
            'success' => ($code >= 200 && $code < 300),
            'error' => $res['_error'],
        ];
    }

    /**
     * Remove (kick) an active member by their OpenAI user_id
     *
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function removeMember($farm, $openaiUserId)
    {
        $res = $this->request($farm, 'DELETE', '/organization/users/' . $openaiUserId);
        $code = $res['_http_code'] ?? 0;
        return [
            'success' => ($code >= 200 && $code < 300),
            'error' => $res['_error'],
        ];
    }

    /**
     * Decrypt API key if CryptoService is available, otherwise return as-is
     */
    private function decryptKey($key)
    {
        if ($key === '') {
            return '';
        }
        if (class_exists('CryptoService')) {
            try {
                $crypto = new CryptoService();
                if ($crypto->isEnabled()) {
                    return (string) $crypto->decryptString($key);
                }
            } catch (Throwable $e) {
                // Fallback to raw key
            }
        }
        return $key;
    }

    /**
     * Encrypt API key before saving to DB
     */
    public function encryptKey($key)
    {
        if ($key === '') {
            return '';
        }
        if (class_exists('CryptoService')) {
            try {
                $crypto = new CryptoService();
                if ($crypto->isEnabled()) {
                    return (string) $crypto->encryptString($key);
                }
            } catch (Throwable $e) {
                // Fallback to raw
            }
        }
        return $key;
    }
}

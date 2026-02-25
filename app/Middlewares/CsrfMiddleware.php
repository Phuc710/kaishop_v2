<?php

/**
 * Global CSRF middleware for state-changing requests (POST/PUT/PATCH/DELETE).
 * Accepts token from header `X-CSRF-Token` or request body `csrf_token`.
 */
class CsrfMiddleware
{
    /**
     * @param string $method HTTP method
     * @param string $path Normalized app path (e.g. /login)
     */
    public function handle(string $method, string $path): bool
    {
        $method = strtoupper($method);
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        if ($this->isExempt($path)) {
            return true;
        }

        if (!function_exists('csrf_validate_request') || !csrf_validate_request()) {
            http_response_code(419);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'CSRF token khong hop le hoac da het han.',
                'code' => 'csrf_invalid',
            ], JSON_UNESCAPED_UNICODE);
            return false;
        }

        return true;
    }

    private function isExempt(string $path): bool
    {
        static $exactExempt = [
            '/api/sepay/webhook', // external provider callback
        ];

        return in_array($path, $exactExempt, true);
    }
}


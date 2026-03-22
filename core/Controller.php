<?php

/**
 * Base Controller Class
 * Provides common methods for all controllers
 */
class Controller
{

    /**
     * Render a view with data
     * @param string $view View path (e.g., 'profile/index')
     * @param array $data Data to pass to view
     */
    protected function view($view, $data = [])
    {
        // Extract data to variables
        extract($data);

        $localizedViewPath = __DIR__ . "/../views/" . (app_locale() === 'en' ? "en/{$view}.php" : "{$view}.php");
        $viewPath = file_exists($localizedViewPath)
            ? $localizedViewPath
            : __DIR__ . "/../views/{$view}.php";

        if (!file_exists($viewPath)) {
            die("View not found: {$view}");
        }

        if (app_locale() === 'en' && !file_exists($localizedViewPath) && class_exists('LocaleViewService')) {
            ob_start();
            require $viewPath;
            $html = (string) ob_get_clean();
            echo (new LocaleViewService())->transform($html, (string) $view);
            return;
        }

        require_once $viewPath;
    }

    /**
     * Return JSON response
     * @param array $data Data to return
     * @param int $status HTTP status code
     */
    protected function json($data, $status = 200)
    {
        // Defensive: clear any buffered output (warnings/BOM/noise) before JSON
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Set no-cache headers (use on sensitive/private pages)
     */
    protected function setNoCache(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    /**
     * Redirect to URL
     * @param string $url URL to redirect to
     */
    protected function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Get POST data
     * @param string $key Key to get
     * @param mixed $default Default value
     * @return mixed
     */
    protected function post($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    /**
     * Get GET data
     * @param string $key Key to get
     * @param mixed $default Default value
     * @return mixed
     */
    protected function get($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    /**
     * Validate CSRF token
     * @return bool
     */
    protected function validateCsrf()
    {
        if (function_exists('csrf_validate_request')) {
            return csrf_validate_request();
        }

        $token = $this->post('csrf_token');
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Allow trusted same-origin browser requests as a fallback when CSRF token
     * is missing or stale, while still rejecting cross-site posts.
     */
    protected function isSameOriginRequest(): bool
    {
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            return false;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
        $currentOrigin = ($isHttps ? 'https://' : 'http://') . $host;
        $current = parse_url($currentOrigin);
        if (!is_array($current)) {
            return false;
        }

        foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $headerName) {
            $value = trim((string) ($_SERVER[$headerName] ?? ''));
            if ($value === '') {
                continue;
            }

            $parts = parse_url($value);
            if (!is_array($parts) || empty($parts['host'])) {
                continue;
            }

            $requestScheme = strtolower((string) ($parts['scheme'] ?? ($current['scheme'] ?? 'http')));
            $currentScheme = strtolower((string) ($current['scheme'] ?? 'http'));
            $requestHost = strtolower((string) ($parts['host'] ?? ''));
            $currentHost = strtolower((string) ($current['host'] ?? ''));
            $requestPort = (int) ($parts['port'] ?? ($requestScheme === 'https' ? 443 : 80));
            $currentPort = (int) ($current['port'] ?? ($currentScheme === 'https' ? 443 : 80));

            if ($requestScheme === $currentScheme && $requestHost === $currentHost && $requestPort === $currentPort) {
                return true;
            }
        }

        return false;
    }

    protected function validateCsrfOrSameOrigin(): bool
    {
        return $this->validateCsrf() || $this->isSameOriginRequest();
    }

    /**
     * Check if request is AJAX
     * @return bool
     */
    protected function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

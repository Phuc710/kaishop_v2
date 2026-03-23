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
     * Enforce CSRF validation for state-changing requests.
     */
    protected function rejectInvalidCsrf($redirectUrl = '', $expectsJson = false, $message = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.')
    {
        if ($this->validateCsrf()) {
            return;
        }

        if ($expectsJson || $this->isAjax()) {
            $this->json([
                'success' => false,
                'message' => $message,
            ], 419);
        }

        $_SESSION['notify'] = [
            'type' => 'error',
            'title' => 'Phiên không hợp lệ',
            'message' => $message,
        ];

        if ($redirectUrl === '') {
            $redirectUrl = function_exists('url') ? url('admin') : '/';
        }

        $this->redirect($redirectUrl);
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

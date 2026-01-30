<?php

/**
 * Base Controller Class
 * Provides common methods for all controllers
 */
class Controller {
    
    /**
     * Render a view with data
     * @param string $view View path (e.g., 'profile/index')
     * @param array $data Data to pass to view
     */
    protected function view($view, $data = []) {
        // Extract data to variables
        extract($data);
        
        // Include view file
        $viewPath = __DIR__ . "/../views/{$view}.php";
        
        if (!file_exists($viewPath)) {
            die("View not found: {$view}");
        }
        
        require_once $viewPath;
    }
    
    /**
     * Return JSON response
     * @param array $data Data to return
     * @param int $status HTTP status code
     */
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Redirect to URL
     * @param string $url URL to redirect to
     */
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Get POST data
     * @param string $key Key to get
     * @param mixed $default Default value
     * @return mixed
     */
    protected function post($key = null, $default = null) {
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
    protected function get($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
    
    /**
     * Validate CSRF token
     * @return bool
     */
    protected function validateCsrf() {
        $token = $this->post('csrf_token');
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

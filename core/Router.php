<?php

/**
 * Simple Router Class
 * Maps URLs to Controllers
 */
class Router {
    private $routes = [];
    
    /**
     * Add a GET route
     */
    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
    }
    
    /**
     * Add a POST route
     */
    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
    }
    
    /**
     * Add route to collection
     */
    private function addRoute($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    /**
     * Dispatch request to appropriate controller
     */
    public function dispatch($uri) {
        // Get the script directory (e.g., /kaishop_v2/public)
        $scriptName = $_SERVER['SCRIPT_NAME']; // e.g., /kaishop_v2/public/index_new.php
        $scriptDir = dirname($scriptName); // e.g., /kaishop_v2/public
        
        // Remove script directory from URI to get the path
        if (strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }
        
        // Remove query string
        $uri = strtok($uri, '?');
        
        // If URI is the entry point file itself, treat as root
        if ($uri === '/index_new.php' || $uri === 'index_new.php') {
            $uri = '/';
        }
        
        // Remove trailing slash
        $uri = rtrim($uri, '/');
        if (empty($uri)) {
            $uri = '/';
        }
        
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Find matching route
        foreach ($this->routes as $route) {
            $params = [];
            if ($route['method'] === $method && $this->matchPath($route['path'], $uri, $params)) {
                return $this->callHandler($route['handler'], $params);
            }
        }
        
        // 404 - No route found
        // 404 - No route found
        http_response_code(404);
        if (file_exists(__DIR__ . '/../404.php')) {
            require __DIR__ . '/../404.php';
        } else {
            echo "404 - Page not found";
        }
    }
    
    /**
     * Check if path matches URI
     */
    private function matchPath($path, $uri, &$params = []) {
        // Convert route like /user/{id} to regex /user/([a-zA-Z0-9-_]+)
        // Or simply accept using regex in routes like /user/([0-9]+)
        
        // Escape forward slashes
        $pattern = preg_replace('/\//', '\\/', $path);
        
        // Convert {param} to capture group (simple alphanumeric)
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9-_]+)', $pattern);
        
        // Add start and end delimiters
        $pattern = '/^' . $pattern . '$/';
        
        if (preg_match($pattern, $uri, $matches)) {
            // Filter out numeric keys, keep named keys
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Call controller method
     */
    private function callHandler($handler, $params = []) {
        if (is_callable($handler)) {
            return call_user_func_array($handler, array_values($params));
        }
        
        if (is_string($handler)) {
            list($controller, $method) = explode('@', $handler);
            
            $controllerFile = __DIR__ . "/../app/Controllers/{$controller}.php";
            
            if (!file_exists($controllerFile)) {
                die("Controller not found: {$controller}");
            }
            
            require_once $controllerFile;
            
            $controllerInstance = new $controller();
            
            if (!method_exists($controllerInstance, $method)) {
                die("Method not found: {$method}");
            }
            
            return call_user_func_array([$controllerInstance, $method], array_values($params));
        }
    }
}

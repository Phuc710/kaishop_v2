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
        // Remove query string
        $uri = strtok($uri, '?');
        
        // Remove trailing slash
        $uri = rtrim($uri, '/');
        if (empty($uri)) {
            $uri = '/';
        }
        
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $uri)) {
                return $this->callHandler($route['handler']);
            }
        }
        
        // 404 - No route found
        http_response_code(404);
        echo "404 - Page not found";
    }
    
    /**
     * Check if path matches URI
     */
    private function matchPath($path, $uri) {
        // Simple exact match for now
        // TODO: Add support for parameters like /user/:id
        return $path === $uri;
    }
    
    /**
     * Call controller method
     */
    private function callHandler($handler) {
        if (is_callable($handler)) {
            return call_user_func($handler);
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
            
            return $controllerInstance->$method();
        }
    }
}

<?php

/**
 * New Entry Point (index.php replacement)
 * Routes requests to appropriate controllers
 */

// Load application config and autoloader
require_once __DIR__ . '/config/app.php';

// Load routes
$routes = require_once __DIR__ . '/config/routes.php';

// Initialize router
$router = new Router();

// Register routes
foreach ($routes as $route) {
    list($method, $path, $handler) = $route;
    
    if ($method === 'GET') {
        $router->get($path, $handler);
    } elseif ($method === 'POST') {
        $router->post($path, $handler);
    }
}

// Dispatch request
$router->dispatch($_SERVER['REQUEST_URI']);

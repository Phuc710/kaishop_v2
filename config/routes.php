<?php

/**
 * Routes Configuration
 * Maps URLs to Controllers
 */

return [
    // ========== HOME ==========
    ['GET', '/', 'HomeController@index'],
    
    // ========== AUTH ROUTES ==========
    ['GET', '/login', 'AuthController@showLogin'],
    ['POST', '/login', 'AuthController@login'],
    ['GET', '/register', 'AuthController@showRegister'],
    ['POST', '/register', 'AuthController@register'],
    ['GET', '/logout', 'AuthController@logout'],
    
    // ========== PROFILE ROUTES ==========
    ['GET', '/profile', 'ProfileController@index'],
    ['POST', '/profile', 'ProfileController@index'], // Allow POST for backward compatibility if needed, though ProfileController::update is mapped to /profile/update
    ['POST', '/profile/update', 'ProfileController@update'],
    ['GET', '/changepass', 'ProfileController@showChangePassword'],
    ['POST', '/changepass', 'ProfileController@changePassword'],
    ['GET', '/password', function() { global $connection, $username, $user, $chungapi; require_once __DIR__ . '/../pages/changepass.php'; }],
    
    // ========== PAYMENT ROUTES ==========
    ['GET', '/payment/card', 'PaymentController@showCard'],
    ['POST', '/payment/card', 'PaymentController@processCard'],
    ['GET', '/payment/bank', 'PaymentController@showBank'],
    ['GET', '/payment/history', 'PaymentController@history'],
    
    // ========== DOMAIN ROUTES ==========
    ['GET', '/domain', 'DomainController@shop'],
    ['GET', '/domain/history', 'DomainController@history'],
    ['POST', '/domain/buy', 'DomainController@buy'],
    ['GET', '/domain/manage', 'DomainController@manage'],
    
    // ========== HOSTING ROUTES ==========
    ['GET', '/hosting', 'HostingController@shop'],
    ['GET', '/hosting/history', 'HostingController@history'],
    ['POST', '/hosting/buy', 'HostingController@buy'],
    ['GET', '/hosting/manage', 'HostingController@manage'],
    
    // ========== SUBDOMAIN ROUTES ==========
    ['GET', '/subdomain', 'SubdomainController@shop'],
    ['GET', '/subdomain/history', 'SubdomainController@history'],
    ['POST', '/subdomain/rent', 'SubdomainController@rent'],
    ['GET', '/subdomain/manage', 'SubdomainController@manage'],
    
    // ========== LOGO ROUTES ==========
    ['GET', '/logo/create', 'LogoController@create'],
    ['POST', '/logo/process', 'LogoController@process'],
    ['GET', '/logo/history', 'LogoController@history'],
    
    // ========== WEBSITE ROUTES ==========
    ['GET', '/website/templates', 'WebsiteController@templates'],
    ['POST', '/website/create', 'WebsiteController@create'],
    ['GET', '/website/history', 'WebsiteController@history'],
    
    // ========== SOURCE CODE ROUTES ==========
    ['GET', '/sourcecode', 'SourceCodeController@shop'],
    ['POST', '/sourcecode/buy', 'SourceCodeController@buy'],
    ['GET', '/sourcecode/history', 'SourceCodeController@history'],
    ['GET', '/sourcecode/download', 'SourceCodeController@download'],
    
    // ========== OTHER ROUTES ==========
    // Subdomain, Logo, Website, SourceCode routes can be added here
    
    // ========== ADMIN ROUTES ==========
    ['GET', '/admin', 'Admin\\DashboardController@index'],
    ['GET', '/admin/users', 'Admin\\UserController@index'],
    ['POST', '/admin/users/edit', 'Admin\\UserController@edit'],
    ['POST', '/admin/users/delete', 'Admin\\UserController@delete'],
    // ========== VIETNAMESE ROUTES (Backward Compatibility) ==========
    ['GET', '/tao-web', 'WebsiteController@templates'],
    ['GET', '/server-hosting', 'HostingController@shop'],
    ['GET', '/tao-logo', 'LogoController@create'],
    ['GET', '/mua-mien', 'DomainController@shop'],
    ['GET', '/subdomain', 'SubdomainController@shop'],
    ['GET', '/ma-nguon', 'SourceCodeController@shop'],
    ['GET', '/nap-the', 'PaymentController@showCard'],
    ['GET', '/nap-bank', 'PaymentController@showBank'],
    
    // ========== LEGACY DYNAMIC ROUTES (Bridge to old pages) ==========
    ['GET', '/history-subdomain/{id}', function($id) { global $connection, $username, $user, $chungapi; $_GET['id'] = $id; require_once __DIR__ . '/../pages/subdomain/quanlysubdomain.php'; }],
    ['GET', '/add-record/{id}', function($id) { global $connection, $username, $user, $chungapi; $_GET['id'] = $id; require_once __DIR__ . '/../pages/subdomain/add-record.php'; }],
    ['GET', '/quanly-subdomain/{id}', function($id) { global $connection, $username, $user, $chungapi; $_GET['id'] = $id; require_once __DIR__ . '/../pages/subdomain/quanlysubdomain.php'; }],
    ['GET', '/edit-record/{id}', function($id) { global $connection, $username, $user, $chungapi; $_GET['id'] = $id; require_once __DIR__ . '/../pages/subdomain/edit-record.php'; }],
    ['GET', '/history-reg-web/{id}', function($id) { global $connection, $username, $user, $chungapi; $_GET['id'] = $id; require_once __DIR__ . '/../pages/taoweb/viewtaoweb.php'; }],
    ['GET', '/view-web/{id}', function($id) { global $connection, $username, $user, $chungapi; $_GET['id'] = $id; require_once __DIR__ . '/../pages/taoweb/taoweb.php'; }],
    ['GET', '/view-logo/{id}', function($id) { global $connection, $username, $user, $chungapi; $_GET['id'] = $id; require_once __DIR__ . '/../pages/taologo/viewlogo.php'; }],
    ['GET', '/tao-web/{id}', function($id) { global $connection, $username, $user, $chungapi; $_GET['id'] = $id; require_once __DIR__ . '/../pages/taoweb/viewweb.php'; }],
    ['GET', '/ma-nguon/{id}', function($id) { global $connection, $username, $user, $chungapi; $_GET['id'] = $id; require_once __DIR__ . '/../pages/manguon/viewcode.php'; }],
    ['GET', '/quan-ly-mien/{id}', function($id) { global $connection, $username, $user, $chungapi; $_GET['id'] = $id; require_once __DIR__ . '/../pages/domain/quanlymien.php'; }],
    ['GET', '/server-hosting/{server}', function($server) { global $connection, $username, $user, $chungapi; $_GET['server'] = $server; require_once __DIR__ . '/../pages/hosting/cuahang.php'; }],
    ['GET', '/thanh-toan-host/{id}', function($id) { global $connection, $username, $user, $chungapi; $_GET['id'] = $id; require_once __DIR__ . '/../pages/hosting/thanhtoan.php'; }],
    ['GET', '/quan-ly-host/{id}', function($id) { global $connection, $username, $user, $chungapi; $_GET['id'] = $id; require_once __DIR__ . '/../pages/hosting/quanlyhost.php'; }],
    // Add other necessary routes here
];

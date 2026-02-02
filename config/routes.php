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
    
    // ========== PRODUCT ROUTES ==========
    ['GET', '/product/{id}', 'ProductController@show'],

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
    ['GET', '/admin/users/edit/{username}', 'Admin\\UserController@edit'],
    ['POST', '/admin/users/edit/{username}', 'Admin\\UserController@update'],
    ['POST', '/admin/users/add-money/{username}', 'Admin\\UserController@addMoney'],
    ['POST', '/admin/users/sub-money/{username}', 'Admin\\UserController@subMoney'],
    ['POST', '/admin/users/sub-money/{username}', 'Admin\\UserController@subMoney'],
    ['POST', '/admin/users/delete', 'Admin\\UserController@delete'],
    
    // Admin Categories
    ['GET', '/admin/categories', 'Admin\\CategoryController@index'],
    ['GET', '/admin/categories/add', 'Admin\\CategoryController@add'],
    ['POST', '/admin/categories/add', 'Admin\\CategoryController@store'],
    ['GET', '/admin/categories/edit/{id}', 'Admin\\CategoryController@edit'],
    ['POST', '/admin/categories/edit/{id}', 'Admin\\CategoryController@update'],
    
    // Admin Finance
    ['GET', '/admin/finance/banks', 'Admin\\FinanceController@banks'],
    ['GET', '/admin/finance/banks/add', 'Admin\\FinanceController@addBank'],
    ['POST', '/admin/finance/banks/add', 'Admin\\FinanceController@storeBank'],
    ['GET', '/admin/finance/banks/edit/{id}', 'Admin\\FinanceController@editBank'],
    ['POST', '/admin/finance/banks/edit/{id}', 'Admin\\FinanceController@updateBank'],
    ['GET', '/admin/finance/histories/banks', 'Admin\\FinanceController@historyBanks'],
    ['GET', '/admin/finance/histories/cards', 'Admin\\FinanceController@historyCards'],
    
    // Admin Giftcodes
    ['GET', '/admin/finance/giftcodes', 'Admin\\FinanceController@giftcodes'],
    ['GET', '/admin/finance/giftcodes/add', 'Admin\\FinanceController@addGiftcode'],
    ['POST', '/admin/finance/giftcodes/add', 'Admin\\FinanceController@storeGiftcode'],
    ['GET', '/admin/finance/giftcodes/edit/{id}', 'Admin\\FinanceController@editGiftcode'],
    ['POST', '/admin/finance/giftcodes/edit/{id}', 'Admin\\FinanceController@updateGiftcode'],
    
    // Admin Services - Codes
    ['GET', '/admin/services/codes', 'Admin\\ServiceController@codes'],
    ['GET', '/admin/services/codes/add', 'Admin\\ServiceController@addCode'],
    ['POST', '/admin/services/codes/add', 'Admin\\ServiceController@storeCode'],
    ['GET', '/admin/services/codes/edit/{id}', 'Admin\\ServiceController@editCode'],
    ['POST', '/admin/services/codes/edit/{id}', 'Admin\\ServiceController@updateCode'],
    
    // Admin Services - Logos
    ['GET', '/admin/services/logos', 'Admin\\ServiceController@logos'],
    ['GET', '/admin/services/logos/add', 'Admin\\ServiceController@addLogo'],
    ['POST', '/admin/services/logos/add', 'Admin\\ServiceController@storeLogo'],
    ['GET', '/admin/services/logos/edit/{id}', 'Admin\\ServiceController@editLogo'],
    ['POST', '/admin/services/logos/edit/{id}', 'Admin\\ServiceController@updateLogo'],
    
    // Admin Services - Domains
    ['GET', '/admin/services/domains', 'Admin\\ServiceController@domains'],
    ['GET', '/admin/services/domains/add', 'Admin\\ServiceController@addDomain'],
    ['POST', '/admin/services/domains/add', 'Admin\\ServiceController@storeDomain'],
    ['GET', '/admin/services/domains/edit/{id}', 'Admin\\ServiceController@editDomain'],
    ['POST', '/admin/services/domains/edit/{id}', 'Admin\\ServiceController@updateDomain'],
    
    // Admin Services - Hosting
    ['GET', '/admin/services/hosting/packs', 'Admin\\ServiceController@hostPacks'],
    ['GET', '/admin/services/hosting/packs/add', 'Admin\\ServiceController@addHostPack'],
    ['POST', '/admin/services/hosting/packs/add', 'Admin\\ServiceController@storeHostPack'],
    ['GET', '/admin/services/hosting/packs/edit/{id}', 'Admin\\ServiceController@editHostPack'],
    ['POST', '/admin/services/hosting/packs/edit/{id}', 'Admin\\ServiceController@updateHostPack'],
    
    ['GET', '/admin/services/hosting/servers', 'Admin\\ServiceController@hostServers'],
    ['GET', '/admin/services/hosting/servers/add', 'Admin\\ServiceController@addHostServer'],
    ['POST', '/admin/services/hosting/servers/add', 'Admin\\ServiceController@storeHostServer'],
    ['GET', '/admin/services/hosting/servers/edit/{id}', 'Admin\\ServiceController@editHostServer'],
    ['POST', '/admin/services/hosting/servers/edit/{id}', 'Admin\\ServiceController@updateHostServer'],
    
    // Admin Products
    ['GET', '/admin/products', 'Admin\\AdminProductController@index'],
    ['GET', '/admin/products/add', 'Admin\\AdminProductController@add'],
    ['POST', '/admin/products/add', 'Admin\\AdminProductController@store'],
    ['GET', '/admin/products/edit/{id}', 'Admin\\AdminProductController@edit'],
    ['POST', '/admin/products/edit/{id}', 'Admin\\AdminProductController@update'],
    // ========== VIETNAMESE ROUTES (Backward Compatibility) ==========
    ['GET', '/tao-web', 'WebsiteController@templates'],
    ['GET', '/server-hosting', 'HostingController@shop'],
    ['GET', '/tao-logo', 'LogoController@create'],
    ['GET', '/mua-mien', 'DomainController@shop'],
    ['GET', '/subdomain', 'SubdomainController@shop'],
    ['GET', '/ma-nguon', 'SourceCodeController@shop'],
    ['GET', '/nap-the', 'PaymentController@showCard'],
    ['GET', '/nap-bank', 'PaymentController@showBank'],
    
    // History Routes (Vietnamese/Legacy)
    ['GET', '/history-code', 'SourceCodeController@history'],
    ['GET', '/history-tao-web', 'WebsiteController@history'],
    ['GET', '/history-hosting', 'HostingController@history'],
    ['GET', '/history-logo', 'LogoController@history'],
    ['GET', '/history-mien', 'DomainController@history'],
    ['GET', '/history-subdomain', 'SubdomainController@history'],
    
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

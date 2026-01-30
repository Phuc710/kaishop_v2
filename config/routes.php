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
    ['POST', '/profile/update', 'ProfileController@update'],
    ['GET', '/changepass', 'ProfileController@showChangePassword'],
    ['POST', '/changepass', 'ProfileController@changePassword'],
    
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
    // Admin routes will be added later
];

<?php

/**
 * Routes Configuration
 * Maps URLs to Controllers
 */

return [
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
    
    // ========== OTHER ROUTES ==========
    // Subdomain, Logo, Website, SourceCode routes can be added here
    
    // ========== ADMIN ROUTES ==========
    // Admin routes will be added later
];

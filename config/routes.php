<?php

/**
 * Routes Configuration
 * Maps URLs to Controllers
 */

return [
    // ========== HOME ==========
    ['GET', '/', 'HomeController@index'],
    ['GET', '/category/{slug}', 'HomeController@category'],

    // ========== POLICY & TERMS ROUTES ==========
    ['GET', '/chinh-sach', 'PolicyController@index'],
    ['GET', '/dieu-khoan', 'TermsController@index'],

    // ========== AUTH ROUTES ==========
    ['GET', '/login', 'AuthController@showLogin'],
    ['POST', '/login', 'AuthController@login'],
    ['GET', '/register', 'AuthController@showRegister'],
    ['POST', '/register', 'AuthController@register'],
    ['GET', '/logout', 'AuthController@logout'],
    ['GET', '/password-reset', 'AuthController@showForgotPassword'],
    ['POST', '/password-reset', 'AuthController@processForgotPassword'],
    ['GET', '/password-reset/{id}', 'AuthController@showResetPassword'],
    ['POST', '/password-reset/{id}', 'AuthController@processResetPassword'],
    ['POST', '/api/update-fingerprint', 'AuthController@updateFingerprint'],

    // ========== PRODUCT ROUTES ==========
    ['GET', '/product/{id}', 'ProductController@show'],

    // ========== PROFILE ROUTES ==========
    ['GET', '/profile', 'ProfileController@index'],
    ['POST', '/profile/update', 'ProfileController@update'],
    [
        'GET',
        '/password',
        function () {
            global $connection, $username, $user, $chungapi;
            require_once __DIR__ . '/../pages/changepass.php';
        }
    ],

    // ========== ADMIN ROUTES ==========
    ['GET', '/admin', 'Admin\\DashboardController@index'],

    // Admin Users
    ['GET', '/admin/users', 'Admin\\UserController@index'],
    ['GET', '/admin/users/edit/{username}', 'Admin\\UserController@edit'],
    ['POST', '/admin/users/edit/{username}', 'Admin\\UserController@update'],
    ['POST', '/admin/users/add-money/{username}', 'Admin\\UserController@addMoney'],
    ['POST', '/admin/users/sub-money/{username}', 'Admin\\UserController@subMoney'],
    ['POST', '/admin/users/delete', 'Admin\\UserController@delete'],
    ['POST', '/admin/users/ban/{username}', 'Admin\\UserController@banUser'],
    ['POST', '/admin/users/unban/{username}', 'Admin\\UserController@unbanUser'],

    // Admin Categories
    ['GET', '/admin/categories', 'Admin\\CategoryController@index'],
    ['GET', '/admin/categories/add', 'Admin\\CategoryController@add'],
    ['POST', '/admin/categories/add', 'Admin\\CategoryController@store'],
    ['GET', '/admin/categories/edit/{id}', 'Admin\\CategoryController@edit'],
    ['POST', '/admin/categories/edit/{id}', 'Admin\\CategoryController@update'],
    ['POST', '/admin/categories/delete', 'Admin\\CategoryController@delete'],

    // Admin Journals
    ['GET', '/admin/logs/activities', 'Admin\\JournalController@activities'],
    ['GET', '/admin/logs/balance-changes', 'Admin\\JournalController@balanceChanges'],
    ['GET', '/admin/logs/system', 'Admin\\JournalController@systemLogs'],

    // Admin Finance - Giftcodes
    ['GET', '/admin/finance/giftcodes', 'Admin\\FinanceController@giftcodes'],
    ['GET', '/admin/finance/giftcodes/add', 'Admin\\FinanceController@addGiftcode'],
    ['POST', '/admin/finance/giftcodes/add', 'Admin\\FinanceController@storeGiftcode'],
    ['GET', '/admin/finance/giftcodes/edit/{id}', 'Admin\\FinanceController@editGiftcode'],
    ['POST', '/admin/finance/giftcodes/edit/{id}', 'Admin\\FinanceController@updateGiftcode'],
    ['POST', '/admin/finance/giftcodes/delete/{id}', 'Admin\\FinanceController@deleteGiftcode'],
    ['GET', '/admin/finance/giftcodes/log/{id}', 'Admin\\FinanceController@giftcodeLog'],

    // Admin Products
    ['GET', '/admin/products', 'Admin\\AdminProductController@index'],
    ['GET', '/admin/products/add', 'Admin\\AdminProductController@add'],
    ['POST', '/admin/products/add', 'Admin\\AdminProductController@store'],
    ['GET', '/admin/products/edit/{id}', 'Admin\\AdminProductController@edit'],
    ['POST', '/admin/products/edit/{id}', 'Admin\\AdminProductController@update'],
    ['POST', '/admin/products/delete', 'Admin\\AdminProductController@delete'],
    ['POST', '/admin/products/toggle-hide', 'Admin\\AdminProductController@toggleHide'],
    ['POST', '/admin/products/toggle-pin', 'Admin\\AdminProductController@togglePin'],
    ['POST', '/admin/products/toggle-active', 'Admin\\AdminProductController@toggleActive'],
];

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
    ['GET', '/lien-he', 'ContactController@index'],
    ['GET', '/lienhe', 'ContactController@index'],
    ['GET', '/bao-tri', 'MaintenanceController@index'],
    ['GET', '/api/system/maintenance-status', 'Api\\MaintenanceStatusController@show'],

    // ========== AUTH ROUTES ==========
    ['GET', '/login', 'AuthController@showLogin'],
    ['POST', '/login', 'AuthController@login'],
    ['GET', '/login-otp', 'AuthController@showLoginOtp'],
    ['POST', '/auth/2fa/verify-login', 'AuthController@verifyLoginOtp'],
    ['POST', '/auth/2fa/resend-login', 'AuthController@resendLoginOtp'],
    ['GET', '/register', 'AuthController@showRegister'],
    ['POST', '/register', 'AuthController@register'],
    ['POST', '/auth/google', 'AuthController@googleLogin'],
    ['GET', '/logout', 'AuthController@logout'],
    ['GET', '/password-reset', 'AuthController@showForgotPassword'],
    ['POST', '/password-reset', 'AuthController@processForgotPassword'],
    ['POST', '/password-reset/verify-otp', 'AuthController@verifyForgotPasswordOtp'],
    ['GET', '/password-reset/{id}', 'AuthController@showResetPassword'],
    ['POST', '/password-reset/{id}', 'AuthController@processResetPassword'],
    ['POST', '/api/update-fingerprint', 'AuthController@updateFingerprint'],

    // ========== PRODUCT ROUTES ==========
    ['GET', '/product/{id}', 'ProductController@show'],
    ['POST', '/product/{id}/quote', 'ProductController@quote'],
    ['POST', '/product/{id}/purchase', 'ProductController@purchase'],

    // ========== PROFILE ROUTES ==========
    ['GET', '/profile', 'ProfileController@index'],
    ['POST', '/profile/update', 'ProfileController@update'],
    ['GET', '/password', 'PasswordController@index'],
    ['POST', '/password/update', 'PasswordController@update'],
    ['POST', '/password/security', 'PasswordController@updateSecurity'],
    // Balance history (preferred canonical routes)
    ['GET', '/history-balance', 'HistoryController@index'],
    ['POST', '/api/history-balance', 'HistoryController@data'],
    // Legacy aliases (backward compatibility)
    ['GET', '/history-code', 'HistoryController@index'],
    ['POST', '/api/history-code', 'HistoryController@data'],
    ['GET', '/history-orders', 'OrderHistoryController@index'],
    ['POST', '/api/history-orders', 'OrderHistoryController@data'],
    ['GET', '/api/history-orders/detail/{id}', 'OrderHistoryController@detail'],
    ['GET', '/history-orders/download/{id}', 'OrderHistoryController@download'],
    ['POST', '/api/history-orders/delete', 'OrderHistoryController@delete'],
    ['GET', '/balance', 'DepositController@balance'],
    ['GET', '/balance/{method}', 'DepositController@balanceMethod'],

    // ========== ADMIN ROUTES ==========
    ['GET', '/admin', 'Admin\\DashboardController@index'],

    // Admin Settings
    ['GET', '/admin/setting', 'Admin\\SettingController@index'],
    ['POST', '/admin/setting/update', 'Admin\\SettingController@update'],

    // Admin Users
    ['GET', '/admin/users', 'Admin\\UserController@index'],
    ['GET', '/admin/users/edit/{username}', 'Admin\\UserController@edit'],
    ['POST', '/admin/users/edit/{username}', 'Admin\\UserController@update'],
    ['POST', '/admin/users/add-money/{username}', 'Admin\\UserController@addMoney'],
    ['POST', '/admin/users/sub-money/{username}', 'Admin\\UserController@subMoney'],
    ['POST', '/admin/users/delete', 'Admin\\UserController@delete'],
    ['POST', '/admin/users/ban/{username}', 'Admin\\UserController@banUser'],
    ['POST', '/admin/users/ban-device/{username}', 'Admin\\UserController@banDevice'],
    ['POST', '/admin/users/unban/{username}', 'Admin\\UserController@unbanUser'],

    // Admin Categories
    ['GET', '/admin/categories', 'Admin\\CategoryController@index'],
    ['GET', '/admin/categories/add', 'Admin\\CategoryController@add'],
    ['POST', '/admin/categories/add', 'Admin\\CategoryController@store'],
    ['GET', '/admin/categories/edit/{id}', 'Admin\\CategoryController@edit'],
    ['POST', '/admin/categories/edit/{id}', 'Admin\\CategoryController@update'],
    ['POST', '/admin/categories/delete', 'Admin\\CategoryController@delete'],

    // Admin Journals
    ['GET', '/admin/logs/buying', 'Admin\\JournalController@buying'],
    ['GET', '/admin/logs/buying/detail/{id}', 'Admin\\JournalController@purchaseDetail'],
    ['POST', '/admin/logs/buying/fulfill', 'Admin\\JournalController@fulfillPurchase'],
    ['POST', '/admin/logs/buying/cancel', 'Admin\\JournalController@cancelPurchase'],
    ['GET', '/admin/logs/balance-changes', 'Admin\\JournalController@balanceChanges'],
    ['GET', '/admin/logs/system', 'Admin\\JournalController@systemLogs'],
    ['GET', '/admin/logs/deposits', 'Admin\\JournalController@deposits'],

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
    ['POST', '/admin/products/toggle-status', 'Admin\\AdminProductController@toggleStatus'],
    // Stock (Kho)
    ['GET', '/admin/products/stock/{id}', 'Admin\\AdminProductController@stock'],
    ['POST', '/admin/products/stock/{id}/import', 'Admin\\AdminProductController@stockImport'],
    ['POST', '/admin/products/stock/update', 'Admin\\AdminProductController@stockUpdate'],
    ['POST', '/admin/products/stock/delete', 'Admin\\AdminProductController@stockDelete'],
    ['POST', '/admin/products/stock/update', 'Admin\\AdminProductController@stockUpdate'],
    // ========== DEPOSIT (User) ==========
    ['GET', '/deposit-bank', 'DepositController@index'],
    ['GET', '/deposit', 'DepositController@legacyRedirect'],
    ['POST', '/deposit/create', 'DepositController@create'],
    ['GET', '/deposit/status/{code}', 'DepositController@status'],
    ['POST', '/deposit/cancel', 'DepositController@cancel'],

    // ========== SEPAY WEBHOOK (API) ==========
    ['POST', '/api/sepay/webhook', 'Api\\SepayWebhookController@handle'],

    // ========== CANONICAL PRODUCT SLUG ROUTE ==========
    // Keep this near the end to avoid catching admin/api routes like /admin/users
    ['GET', '/{categorySlug}/{productSlug}', 'ProductController@showBySlug'],
];

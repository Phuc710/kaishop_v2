<?php

/**
 * Routes Configuration
 * Maps URLs to Controllers
 */

$routes = [
    // ========== HOME ==========
    ['GET', '/', 'HomeController@index'],
    ['GET', '/category/{slug}', 'HomeController@category'],

    // ========== POLICY & TERMS ROUTES ==========
    ['GET', '/chinh-sach', 'PolicyController@index'],
    ['GET', '/dieu-khoan', 'TermsController@index'],
    ['GET', '/lien-he', 'ContactController@index'],
    ['GET', '/lienhe', 'ContactController@index'],
    ['GET', '/robots.txt', 'SeoController@robots'],
    ['GET', '/sitemap.xml', 'SeoController@sitemap'],
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

    // Admin Blacklist
    ['GET', '/admin/blacklist', 'Admin\\BlacklistController@index'],
    ['POST', '/admin/blacklist/unban', 'Admin\\BlacklistController@unban'],
    ['POST', '/admin/blacklist/clear-expired', 'Admin\\BlacklistController@clearExpired'],

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
    ['POST', '/admin/products/stock/action/{id}', 'Admin\\AdminProductController@stockAction'],
    ['POST', '/admin/products/stock/{id}/import', 'Admin\\AdminProductController@stockImport'],
    ['POST', '/admin/products/stock/{id}/clean', 'Admin\\AdminProductController@stockClean'],
    // ========== DEPOSIT (User) ==========
    ['GET', '/deposit-bank', 'DepositController@index'],
    ['GET', '/deposit', 'DepositController@legacyRedirect'],
    ['POST', '/deposit/create', 'DepositController@create'],
    ['POST', '/deposit/create-binance', 'DepositController@createBinance'],
    ['POST', '/wallet/binance-session', 'DepositController@createBinance'],
    ['GET', '/deposit/status-wait/{code}', 'DepositController@statusWait'],
    ['GET', '/deposit/status/{code}', 'DepositController@status'],
    ['POST', '/deposit/cancel', 'DepositController@cancel'],

    // ========== SEPAY WEBHOOK (API) ==========
    ['POST', '/api/sepay/webhook', 'Api\\SepayWebhookController@handle'],

    // ========== TELEGRAM BOT ==========
    ['POST', '/api/telegram/webhook', 'TelegramBotController@handleWebhook'],
    ['POST', '/api/telegram/generate-link', 'ProfileController@generateTelegramLink'],
    ['POST', '/api/telegram/unlink', 'ProfileController@unlinkTelegram'],

    // ========== ADMIN TELEGRAM ==========
    ['GET', '/admin/telegram', 'Admin\\TelegramAdminController@index'],
    ['GET', '/admin/telegram/settings', 'Admin\\TelegramAdminController@settings'],
    ['POST', '/admin/telegram/settings/update', 'Admin\\TelegramAdminController@updateSettings'],
    ['POST', '/admin/telegram/webhook/set', 'Admin\\TelegramAdminController@setWebhookAction'],
    ['POST', '/admin/telegram/webhook/activate', 'Admin\\TelegramAdminController@activateWebhookAction'],
    ['POST', '/admin/telegram/webhook/delete', 'Admin\\TelegramAdminController@deleteWebhookAction'],
    ['POST', '/admin/telegram/test', 'Admin\\TelegramAdminController@testNotification'],
    ['POST', '/admin/telegram/sync', 'Admin\\TelegramAdminController@syncBotAction'],
    ['GET', '/admin/telegram/notification-channels', 'Admin\\TelegramAdminController@notificationChannels'],
    ['POST', '/admin/telegram/notification-channels/add', 'Admin\\TelegramAdminController@addChannelAction'],
    ['POST', '/admin/telegram/notification-channels/update', 'Admin\\TelegramAdminController@updateChannelAction'],
    ['POST', '/admin/telegram/notification-channels/toggle', 'Admin\\TelegramAdminController@toggleChannelAction'],
    ['POST', '/admin/telegram/notification-channels/delete', 'Admin\\TelegramAdminController@deleteChannelAction'],
    ['POST', '/admin/telegram/main-channel/alert', 'Admin\\TelegramAdminController@sendMainChannelAlertAction'],
    ['POST', '/admin/telegram/broadcast/send', 'Admin\\TelegramAdminController@broadcastAction'],
    ['GET', '/admin/telegram/outbox', 'Admin\\TelegramAdminController@outbox'],
    ['POST', '/admin/telegram/outbox/retry', 'Admin\\TelegramAdminController@outboxRetry'],
    ['POST', '/admin/telegram/outbox/delete', 'Admin\\TelegramAdminController@outboxDelete'],
    ['GET', '/admin/telegram/logs', 'Admin\\TelegramAdminController@logs'],
    ['GET', '/admin/telegram/orders', 'Admin\\TelegramAdminController@orders'],
    ['GET', '/admin/telegram/terminal', 'Admin\\TelegramAdminController@terminal'],
    ['GET', '/admin/telegram/terminal/poll', 'Admin\\TelegramAdminController@terminalPoll'],

    // ========== CHATGPT PRO FARM ROUTES ==========
    ['GET', '/gpt-business/buy', 'ChatGptController@product'],
    ['POST', '/gpt-business/order', 'ChatGptController@order'],
    ['GET', '/gpt-business/success', 'ChatGptController@success'],

    // Admin ChatGPT Panel
    ['GET', '/admin/gpt-business/farms', 'Admin\\ChatGptAdminController@farms'],
    ['GET', '/admin/gpt-business/farms/add', 'Admin\\ChatGptAdminController@farmAdd'],
    ['POST', '/admin/gpt-business/farms/add', 'Admin\\ChatGptAdminController@farmStore'],
    ['GET', '/admin/gpt-business/farms/edit/{id}', 'Admin\\ChatGptAdminController@farmEdit'],
    ['POST', '/admin/gpt-business/farms/edit/{id}', 'Admin\\ChatGptAdminController@farmUpdate'],
    ['POST', '/admin/gpt-business/farms/sync-now/{id}', 'Admin\\ChatGptAdminController@farmSyncNow'],
    ['GET', '/admin/gpt-business/orders', 'Admin\\ChatGptAdminController@orders'],
    ['GET', '/admin/gpt-business/orders/add', 'Admin\\ChatGptAdminController@orderAdd'],
    ['POST', '/admin/gpt-business/orders/add', 'Admin\\ChatGptAdminController@orderStore'],
    ['POST', '/admin/gpt-business/orders/retry-invite/{id}', 'Admin\\ChatGptAdminController@orderRetryInvite'],
    ['GET', '/admin/gpt-business/members', 'Admin\\ChatGptAdminController@members'],
    ['POST', '/admin/gpt-business/members/remove/{id}', 'Admin\\ChatGptAdminController@memberRemove'],
    ['GET', '/admin/gpt-business/invites', 'Admin\\ChatGptAdminController@invites'],
    ['POST', '/admin/gpt-business/invites/revoke/{id}', 'Admin\\ChatGptAdminController@inviteRevoke'],
    ['GET', '/admin/gpt-business/product', 'Admin\\ChatGptAdminController@productEdit'],
    ['POST', '/admin/gpt-business/product', 'Admin\\ChatGptAdminController@productUpdate'],
    ['GET', '/admin/gpt-business/logs', 'Admin\\ChatGptAdminController@logs'],
    ['GET', '/admin/gpt-business/violations', 'Admin\\ChatGptAdminController@violations'],

    // ========== CANONICAL PRODUCT SLUG ROUTE ==========
    // Keep this near the end to avoid catching admin/api routes like /admin/users
    ['GET', '/{categorySlug}/{productSlug}', 'ProductController@showBySlug'],
];


// ========== DYNAMIC TELEGRAM WEBHOOK ROUTE ==========
// Reads webhook path from DB settings and registers a matching route dynamically.
// This allows admin to change the webhook URL in settings without code changes.
try {
    if (function_exists('get_setting')) {
        $tgWebhookPath = trim((string) get_setting('telegram_webhook_path', ''));
        if ($tgWebhookPath !== '' && $tgWebhookPath !== 'telegram/webhook') {
            $dynamicRoute = '/api/' . ltrim($tgWebhookPath, '/');
            // Insert before the catch-all slug route (second-to-last position)
            array_splice($routes, -1, 0, [
                ['POST', $dynamicRoute, 'TelegramBotController@handleWebhook'],
            ]);
        }
    }
} catch (Throwable $e) {
    // Silently ignore if DB not ready yet (first setup, migration, etc.)
}

return $routes;


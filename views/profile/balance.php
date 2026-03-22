<?php
$depositPanel = $depositPanel ?? [];
$methods = is_array($depositPanel['methods'] ?? null) ? $depositPanel['methods'] : [];
$activeMethod = (string) ($depositPanel['active_method'] ?? 'bank_sepay');
$bankName = (string) ($depositPanel['bankName'] ?? 'MB Bank');
$bankAccount = (string) ($depositPanel['bankAccount'] ?? '');
$bankOwner = (string) ($depositPanel['bankOwner'] ?? '');
$bonusTiers = is_array($depositPanel['bonusTiers'] ?? null) ? $depositPanel['bonusTiers'] : [];
$activeDepositPayload = is_array($depositPanel['activeDepositPayload'] ?? null) ? $depositPanel['activeDepositPayload'] : null;
$ttlSeconds = (int) ($depositPanel['ttlSeconds'] ?? 300);

$routeMethodMap = [];
$activeMethodMeta = null;
foreach ($methods as $method) {
    $code = (string) ($method['code'] ?? '');
    if ($code === '') {
        continue;
    }
    $routeMethodMap[$code] = ($code === 'bank_sepay') ? 'bank' : $code;
    if ($code === $activeMethod) {
        $activeMethodMeta = $method;
    }
}

$activeMethodRoute = (string) ($depositRouteMethod ?? ($routeMethodMap[$activeMethod] ?? 'bank'));
$activeDepositExists = !empty($activeDepositPayload['deposit_code']);

$methodIconMap = [];
$methodImageMap = [
    'bank_sepay' => [
        'src' => (string) asset('assets/images/bank.png'),
        'alt' => 'Ngân hàng',
    ],
    'binance' => [
        'src' => (string) asset('assets/images/Binance_icon.svg'),
        'alt' => 'Binance',
    ],
    'momo' => [
        'src' => (string) asset('assets/images/momo.webp'),
        'alt' => 'MoMo',
    ],
];

$placeholderQr = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
$qrUrl = $activeDepositExists ? (string) ($activeDepositPayload['qr_url'] ?? $placeholderQr) : $placeholderQr;

$activeMethodLabel = (string) ($activeMethodMeta['label'] ?? 'Nạp tiền');
$activeMethodImage = (array) ($methodImageMap[$activeMethod] ?? []);
$activeMethodImageSrc = trim((string) ($activeMethodImage['src'] ?? ''));
$activeMethodImageAlt = trim((string) ($activeMethodImage['alt'] ?? $activeMethodLabel));
$activeMethodIconClass = (string) ($methodIconMap[$activeMethod] ?? '');

$methodRoutes = [];
foreach ($methods as $method) {
    $code = (string) ($method['code'] ?? '');
    if ($code === '') {
        continue;
    }
    $methodRoutes[$code] = (string) url('balance/' . ($routeMethodMap[$code] ?? $code));
}

$balanceMethodPartialMap = [
    'bank_sepay' => 'balance_method_bank.php',
    'momo' => 'balance_method_momo.php',
    'binance' => 'balance_method_binance.php',
];
$balanceMethodPartialName = $balanceMethodPartialMap[$activeMethod] ?? 'balance_method_bank.php';
$balanceMethodPartialPath = __DIR__ . '/partials/' . $balanceMethodPartialName;
if (!is_file($balanceMethodPartialPath)) {
    $balanceMethodPartialPath = __DIR__ . '/partials/balance_method_bank.php';
}
?>
<div id="profile-balance-card" class="profile-card user-deposit-card" data-balance-bank-root>
    <div class="profile-card-header profile-card-header--with-actions">
        <div>
            <h5 class="text-dark mb-1">NẠP TIỀN</h5>
            <div class="user-card-subtitle">Chọn phương thức nạp tiền phù hợp.</div>
        </div>
        <div class="profile-card-header-actions">
        </div>
    </div>

    <div class="profile-card-body p-4">
        <?php if (!empty($methods)): ?>
            <div class="deposit-method-list" role="tablist" aria-label="Phương thức nạp tiền">
                <?php foreach ($methods as $method): ?>
                    <?php
                    $code = (string) ($method['code'] ?? '');
                    $enabled = !empty($method['enabled']);
                    $isActive = $code === $activeMethod;
                    $isInteractiveDisabled = !$enabled && !$isActive;
                    $methodImage = (array) ($methodImageMap[$code] ?? []);
                    $methodImageSrc = trim((string) ($methodImage['src'] ?? ''));
                    $methodImageAlt = trim((string) ($methodImage['alt'] ?? (string) ($method['label'] ?? $code)));
                    $methodIconClass = (string) ($methodIconMap[$code] ?? '');
                    if ($code === '') {
                        continue;
                    }
                    ?>
                    <button type="button"
                        class="deposit-method-pill <?= $isActive ? 'is-active' : '' ?> <?= $isInteractiveDisabled ? 'is-disabled' : '' ?>"
                        data-method-code="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                        data-method-enabled="<?= $enabled ? '1' : '0' ?>"
                        aria-disabled="<?= $isInteractiveDisabled ? 'true' : 'false' ?>">
                        <span class="deposit-method-pill__badge" aria-hidden="true">
                            <?php if ($methodImageSrc !== ''): ?>
                                <img src="<?= htmlspecialchars($methodImageSrc, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($methodImageAlt, ENT_QUOTES, 'UTF-8') ?>"
                                    class="deposit-method-pill__badge-img <?= $code === 'binance' ? 'is-binance-img' : '' ?>">
                            <?php elseif ($methodIconClass !== ''): ?>
                                <i
                                    class="<?= htmlspecialchars($methodIconClass, ENT_QUOTES, 'UTF-8') ?> <?= $code === 'binance' ? 'is-binance' : '' ?>"></i>
                            <?php endif; ?>
                        </span>
                        <span class="deposit-method-pill__label">
                            <?= htmlspecialchars((string) ($method['label'] ?? $code), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="deposit-panel-shell">
            <?php require $balanceMethodPartialPath; ?>
        </div>
    </div>

    <script type="application/json" id="balance-bank-config"><?=
        json_encode([
            'baseUrl' => rtrim((string) url(''), '/'),
            'csrfToken' => function_exists('csrf_token') ? (string) csrf_token() : '',
            'serverNowTs' => TimeService::instance()->nowTs(),
            'storageKey' => 'ks_balance_' . $activeMethod . '_' . md5((string) ($username ?? 'guest')),
            'endpoints' => [
                'create' => (string) url('deposit/create'),
                'createByMethod' => [
                    'bank_sepay' => (string) url('deposit/create'),
                    'binance' => (string) url('wallet/binance-session'),
                ],
                'cancel' => (string) url('deposit/cancel'),
                'statusBase' => (string) url('deposit/status'),
                'statusWaitBase' => (string) url('deposit/status-wait'),
                'profile' => (string) url('balance/' . $activeMethodRoute),
            ],
            'methodRoutes' => $methodRoutes,
            'activeMethod' => $activeMethod,
            'ttlSeconds' => $ttlSeconds,
            'bonusTiers' => $bonusTiers,
            'bank' => [
                'name' => $bankName,
                'shortName' => (string) ($depositPanel['bankShortName'] ?? $bankName),
                'account' => $bankAccount,
                'owner' => $bankOwner,
            ],
            'binance' => [
                'rateVnd' => (int) ($depositPanel['binanceRateVnd'] ?? 25000),
            ],
            'activeDeposit' => $activeDepositExists ? $activeDepositPayload : null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
        ?></script>
</div>

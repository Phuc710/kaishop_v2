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
$isBankMethod = $activeMethod === 'bank_sepay';
$activeDepositExists = $isBankMethod && !empty($activeDepositPayload['deposit_code']);

$placeholderQr = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
$qrUrl = $activeDepositExists ? (string) ($activeDepositPayload['qr_url'] ?? $placeholderQr) : $placeholderQr;

$activeMethodLabel = (string) ($activeMethodMeta['label'] ?? 'Nạp tiền');
$activeMethodBadge = trim((string) ($activeMethodMeta['badge'] ?? ''));
$activeMethodEnabled = !empty($activeMethodMeta['enabled']);

$methodRoutes = [];
foreach ($methods as $method) {
    $code = (string) ($method['code'] ?? '');
    if ($code === '') {
        continue;
    }
    $methodRoutes[$code] = (string) url('balance/' . ($routeMethodMap[$code] ?? $code));
}
?>
<div id="profile-deposit-card" class="profile-card user-deposit-card" data-deposit-bank-root>
    <div class="profile-card-header profile-card-header--with-actions">
        <div>
            <h5 class="text-dark mb-1">Nạp tiền</h5>
            <div class="user-card-subtitle">Chọn phương thức nạp tiền phù hợp và làm theo hướng dẫn.</div>
        </div>
        <div class="profile-card-header-actions">
            <span class="user-card-badge user-card-badge--top-right">
                <?= htmlspecialchars($activeMethodLabel, ENT_QUOTES, 'UTF-8') ?>
                <?php if ($activeMethodBadge !== ''): ?>
                    · <?= htmlspecialchars($activeMethodBadge, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <div class="profile-card-body pt-0">
        <?php if (!empty($methods)): ?>
            <div class="deposit-method-list" role="tablist" aria-label="Phương thức nạp tiền">
                <?php foreach ($methods as $method): ?>
                    <?php
                    $code = (string) ($method['code'] ?? '');
                    $enabled = !empty($method['enabled']);
                    $isActive = $code === $activeMethod;
                    if ($code === '') {
                        continue;
                    }
                    ?>
                    <button type="button"
                        class="deposit-method-pill <?= $isActive ? 'is-active' : '' ?> <?= !$enabled ? 'is-disabled' : '' ?>"
                        data-method-code="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                        data-method-enabled="<?= $enabled ? '1' : '0' ?>"
                        aria-disabled="<?= $enabled ? 'false' : 'true' ?>">
                        <span class="deposit-method-pill__label">
                            <?= htmlspecialchars((string) ($method['label'] ?? $code), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="deposit-method-pill__badge">
                            <?= htmlspecialchars((string) ($method['badge'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="deposit-panel-shell">
            <?php if (!$isBankMethod): ?>
                <div class="deposit-method-coming-soon">
                    <div class="deposit-method-coming-soon__icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="deposit-method-coming-soon__content">
                        <h6 class="deposit-panel-title mb-2">
                            <?= htmlspecialchars($activeMethodLabel, ENT_QUOTES, 'UTF-8') ?> đang được chuẩn bị
                        </h6>
                        <p class="deposit-panel-help mb-3">
                            Phương thức này chưa mở trên hệ thống. Bạn có thể dùng nạp tiền qua ngân hàng trong thời gian chờ cập nhật.
                        </p>
                        <a href="<?= htmlspecialchars((string) url('balance/bank'), ENT_QUOTES, 'UTF-8') ?>"
                            class="btn btn-edit-profile">
                            <i class="fas fa-university me-1"></i> Chuyển sang nạp qua ngân hàng
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="deposit-panel-step" data-deposit-step="amount" <?= $activeDepositExists ? 'hidden' : '' ?>>
                    <div class="deposit-panel-title-row">
                        <h6 class="deposit-panel-title">Chọn số tiền nạp</h6>
                    </div>
                    <p class="deposit-panel-help">Chọn nhanh mệnh giá hoặc nhập số tiền bạn muốn nạp.</p>

                    <div class="deposit-grid">
                        <?php foreach ($bonusTiers as $btn): ?>
                            <?php
                            $amt = (int) ($btn['amount'] ?? 0);
                            $pct = (int) ($btn['percent'] ?? 0);
                            if ($amt <= 0) {
                                continue;
                            }
                            ?>
                            <button type="button" class="deposit-quick-btn" data-deposit-quick data-amount="<?= $amt ?>"
                                data-bonus="<?= $pct ?>">
                                <span class="deposit-amount"><?= number_format($amt, 0, ',', '.') ?>đ</span>
                                <?php if ($pct > 0): ?>
                                    <span class="deposit-bonus">+<?= $pct ?>%</span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-group mt-3 mb-2">
                        <label class="user-label" for="depositAmountInput">Nhập số tiền khác</label>
                        <div class="input-group">
                            <input type="number" id="depositAmountInput" class="form-control" min="10000" step="1000"
                                value="10000" data-deposit-input-amount>
                            <span class="input-group-text">VND</span>
                        </div>
                        <small class="text-muted">Tối thiểu 10.000đ, tối đa 50.000.000đ</small>
                    </div>

                    <div class="deposit-preview" data-deposit-preview hidden>
                        <div class="deposit-preview-row">
                            <span>Số tiền nạp</span>
                            <strong data-preview-amount>0đ</strong>
                        </div>
                        <div class="deposit-preview-row" data-preview-bonus-row hidden>
                            <span>Bonus</span>
                            <strong class="text-success" data-preview-bonus>+0đ</strong>
                        </div>
                        <div class="deposit-preview-divider"></div>
                        <div class="deposit-preview-row is-total">
                            <span>Tổng nhận</span>
                            <strong data-preview-total>0đ</strong>
                        </div>
                    </div>

                    <button type="button" class="btn btn-edit-profile w-100 mt-3" data-deposit-action="create">
                        <i class="fas fa-paper-plane me-1"></i> Tạo giao dịch nạp tiền
                    </button>
                </div>

                <div class="deposit-panel-step" data-deposit-step="transfer" <?= $activeDepositExists ? '' : 'hidden' ?>>
                    <div class="deposit-panel-title-row">
                        <h6 class="deposit-panel-title">Thông tin chuyển khoản</h6>
                        <span class="user-card-badge">Đang chờ xử lý</span>
                    </div>

                    <div class="row align-items-start g-3">
                        <div class="col-lg-5">
                            <div class="deposit-qr-card">
                                <div class="deposit-qr-box">
                                    <img data-deposit-qr src="<?= htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8') ?>"
                                        alt="QR thanh toán">
                                    <div class="deposit-qr-logo">
                                        <img src="<?= htmlspecialchars((string) ($chungapi['favicon'] ?? ($chungapi['logo'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                            alt="Logo">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-search-custom w-100 mt-3"
                                    data-deposit-action="download-qr">
                                    <i class="fas fa-download me-1"></i> Tải QR
                                </button>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="deposit-info-list">
                                <div class="deposit-info-row">
                                    <span class="deposit-info-label">Ngân hàng</span>
                                    <span class="deposit-info-value"
                                        data-tf-bank><?= htmlspecialchars($activeDepositPayload['bank_name'] ?? $bankName, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="deposit-info-row">
                                    <span class="deposit-info-label">Chủ tài khoản</span>
                                    <span class="deposit-info-value is-uppercase"
                                        data-tf-owner><?= htmlspecialchars($activeDepositPayload['bank_owner'] ?? $bankOwner, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="deposit-info-row">
                                    <span class="deposit-info-label">Số tài khoản</span>
                                    <div class="deposit-info-actions">
                                        <span class="deposit-info-value"
                                            data-tf-account><?= htmlspecialchars($activeDepositPayload['bank_account'] ?? $bankAccount, ENT_QUOTES, 'UTF-8') ?></span>
                                        <button type="button" class="btn-copy" data-copy-target="account"><i
                                                class="fas fa-copy"></i></button>
                                    </div>
                                </div>
                                <div class="deposit-info-row">
                                    <span class="deposit-info-label text-danger">Nội dung *</span>
                                    <div class="deposit-info-actions">
                                        <span class="deposit-info-value is-highlight"
                                            data-tf-content><?= htmlspecialchars((string) ($activeDepositPayload['deposit_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <button type="button" class="btn-copy" data-copy-target="content"><i
                                                class="fas fa-copy"></i></button>
                                    </div>
                                </div>
                                <div class="deposit-info-row">
                                    <span class="deposit-info-label">Số tiền</span>
                                    <span class="deposit-info-value is-amount"
                                        data-tf-amount><?= !empty($activeDepositPayload['amount']) ? number_format((int) $activeDepositPayload['amount'], 0, ',', '.') . 'đ' : '' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="deposit-countdown-wrap mt-3" data-deposit-countdown-wrap>
                        <div class="deposit-countdown-label">Thời gian còn lại</div>
                        <div class="deposit-countdown" data-deposit-countdown>05:00</div>
                        <div class="deposit-countdown-bar">
                            <div class="deposit-countdown-fill" data-deposit-countdown-fill style="width:100%"></div>
                        </div>
                    </div>

                    <div class="alert alert-warning deposit-warning mt-3 mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Nhập đúng <strong>nội dung chuyển khoản</strong> để hệ thống tự động cộng tiền.
                    </div>

                    <button type="button" class="btn btn-clear-custom w-100 mt-3" data-deposit-action="cancel">
                        <i class="fas fa-times me-1"></i> Huỷ giao dịch nạp tiền
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script type="application/json" id="deposit-bank-config"><?=
        json_encode([
            'baseUrl' => rtrim((string) url(''), '/'),
            'endpoints' => [
                'create' => (string) url('deposit/create'),
                'cancel' => (string) url('deposit/cancel'),
                'statusBase' => (string) url('deposit/status'),
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
            'activeDeposit' => $activeDepositExists ? $activeDepositPayload : null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
        ?></script>
</div>

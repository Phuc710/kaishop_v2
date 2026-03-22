<?php
$binanceRateVnd = max(1, (int) ($depositPanel['binanceRateVnd'] ?? 25000));
$isBinanceEnabled = ((int) ($chungapi['binance_pay_enabled'] ?? 0) === 1);

// Hardcoded $1.00 standard + 3 tiers from DB
$quickButtons = [['usd' => 1.0, 'percent' => 0]];
for ($i = 1; $i <= 3; $i++) {
    $amt = (float) ($chungapi["binance_bonus_{$i}_amount"] ?? 0);
    $pct = (int) ($chungapi["binance_bonus_{$i}_percent"] ?? 0);
    if ($amt > 0) {
        $quickButtons[] = ['usd' => $amt, 'percent' => $pct];
    }
}
?>

<?php if (!$isBinanceEnabled && !$activeDepositExists): ?>
    <div class="maintenance-notice text-center py-5">
        <div class="maintenance-icon mb-4">
            <i class="fas fa-tools fa-4x text-muted"></i>
        </div>
        <h4 class="font-weight-bold text-dark">METHOD UNDER MAINTENANCE</h4>
        <p class="text-muted">Binance Pay is currently under maintenance for system updates. Please check back later or use
            other payment methods.</p>
        <a href="<?= url('') ?>" class="btn btn-outline-primary mt-3">
            <i class="fas fa-home me-1"></i> Back to Home
        </a>
    </div>
    <?php return; endif; ?>

<?php
$binanceQrSetting = get_setting('binance_qr_image', 'assets/images/qr_binane.jpg');
$binanceQrUrl = asset($binanceQrSetting);
$binanceTimeService = class_exists('TimeService') ? TimeService::instance() : null;
$binanceReceiverUid = (string) ($activeDepositPayload['binance_uid'] ?? ($chungapi['binance_uid'] ?? ''));
$binancePayerUid = (string) ($activeDepositPayload['payer_uid'] ?? '');
$binanceDepositCode = (string) ($activeDepositPayload['deposit_code'] ?? '');
$binanceUsdtAmount = number_format((float) ($activeDepositPayload['usdt_amount'] ?? 0), 2, '.', '');
$binanceExpiresAtTs = (int) ($activeDepositPayload['expires_at_ts'] ?? 0);
$binanceExpiresAtDisplay = $binanceExpiresAtTs > 0
    ? ($binanceTimeService ? $binanceTimeService->formatDisplay($binanceExpiresAtTs, 'Y-m-d H:i:s', 'UTC') : date('Y-m-d H:i:s', $binanceExpiresAtTs))
    : '';
?>
<div class="deposit-panel-step" data-deposit-step="amount" <?= $activeDepositExists ? 'hidden' : '' ?>>
    <div class="deposit-panel-title-row">
        <h6 class="deposit-panel-title">Select Deposit Amount</h6>
    </div>
    <p class="deposit-panel-help">
        Please enter the correct Binance UID for automatic credits to your account.
    </p>

    <div class="deposit-grid">
        <?php foreach ($quickButtons as $btn): ?>
            <?php
            $usdAmountBtn = (float) ($btn['usd'] ?? 0);
            $bonusPercent = (int) ($btn['percent'] ?? 0);
            ?>
            <button type="button" class="deposit-quick-btn" data-deposit-quick data-amount="<?= $usdAmountBtn ?>">
                <span class="deposit-amount">$<?= number_format($usdAmountBtn, 2, '.', ',') ?></span>
                <?php if ($bonusPercent > 0): ?>
                    <span class="deposit-bonus">+<?= $bonusPercent ?>%</span>
                <?php endif; ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="form-group mt-3 mb-2">
        <label class="user-label" for="depositPayerUidInput">Binance UID</label>
        <div class="input-group">
            <input type="text" id="depositPayerUidInput" class="form-control" inputmode="numeric" autocomplete="off"
                placeholder="Enter Binance UID" data-deposit-input-payer-uid>
            <span class="input-group-text">UID</span>
        </div>
        <small class="text-muted">Note: Incorrect Binance UID will prevent automatic verification.</small>
    </div>

    <div class="form-group mt-3 mb-2">
        <label class="user-label" for="depositAmountInput">Enter Other Amount</label>
        <div class="input-group">
            <input type="number" id="depositAmountInput" class="form-control" min="1" step="0.01" value="1"
                data-deposit-input-amount>
            <span class="input-group-text">USD</span>
        </div>
        <small class="text-muted">Minimum $1.00</small>
    </div>

    <div class="deposit-preview" data-deposit-preview hidden>
        <div class="deposit-preview-row">
            <span>Amount</span>
            <strong data-preview-amount>$0.00</strong>
        </div>
        <div class="deposit-preview-row" data-preview-bonus-row hidden>
            <span>Bonus</span>
            <strong class="text-success" data-preview-bonus>+$0.00</strong>
        </div>

        <div class="deposit-preview-divider"></div>
        <div class="deposit-preview-row is-total">
            <span>Total</span>
            <strong data-preview-total>$0.00</strong>
        </div>
    </div>

    <button type="button" class="btn btn-edit-profile w-100 mt-3" data-deposit-action="create">
        <i class="fas fa-paper-plane me-1"></i> Create Transaction
    </button>
</div>

<div class="deposit-panel-step" data-deposit-step="transfer" <?= $activeDepositExists ? '' : 'hidden' ?>>
    <div class="deposit-panel-title-row deposit-panel-title-row--center">
        <h6 class="deposit-panel-title">Binance Payment Details</h6>
    </div>

    <div class="row align-items-start g-3 mt-2">
        <div class="col-lg-5">
            <div class="deposit-qr-card h-100 mb-0">
                <div class="deposit-qr-box text-center">
                    <img src="<?= htmlspecialchars($binanceQrUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Binance QR UID"
                        class="img-fluid rounded shadow-sm" style="max-width: 220px; border: 1px solid #eee;">
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="deposit-info-list mt-0">
                <div class="deposit-info-row">
                    <span class="deposit-info-label">Pay to Binance ID</span>
                    <div class="deposit-info-actions">
                        <strong class="deposit-info-value is-highlight"
                            data-tf-binance-uid><?= htmlspecialchars($binanceReceiverUid, ENT_QUOTES, 'UTF-8') ?></strong>
                        <button type="button" class="btn-copy" data-copy-target="uid"><i
                                class="fas fa-copy"></i></button>
                    </div>
                </div>
                <div class="deposit-info-row">
                    <span class="deposit-info-label">UID</span>
                    <span class="deposit-info-value"
                        data-tf-payer-uid><?= htmlspecialchars($binancePayerUid, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="deposit-info-row">
                    <span class="deposit-info-label">Send EXACTLY</span>
                    <div class="deposit-info-actions">
                        <span class="deposit-info-value is-highlight"
                            data-tf-usdt>$<?= htmlspecialchars($binanceUsdtAmount, ENT_QUOTES, 'UTF-8') ?> USDT</span>
                        <button type="button" class="btn-copy" data-copy-target="usdt"><i
                                class="fas fa-copy"></i></button>
                    </div>
                </div>
                <div class="deposit-info-row">
                    <span class="deposit-info-label">Expires At</span>
                    <span class="deposit-info-value"
                        data-tf-expires><?= htmlspecialchars($binanceExpiresAtDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="deposit-countdown-wrap mt-3" data-deposit-countdown-wrap>
        <div class="deposit-countdown-label">Time Remaining</div>
        <div class="deposit-countdown" data-deposit-countdown>05:00</div>
        <div class="deposit-countdown-bar">
            <div class="deposit-countdown-fill" data-deposit-countdown-fill style="width:100%"></div>
        </div>
    </div>

    <div class="alert alert-warning deposit-warning mt-3 mb-0">
        Note: price detection may be inaccurate. Enter your correct <strong>Binance UID</strong> and send the exact
        <strong><?= htmlspecialchars($binanceUsdtAmount, ENT_QUOTES, 'UTF-8') ?> USDT</strong> shown above so the system
        can auto-match your payment.
    </div>

    <div class="row g-2 mt-3">
        <div class="col-md-6">
            <button type="button" class="btn btn-search-custom w-100" data-deposit-action="check">
                <i class="fas fa-search me-1"></i> Check Payment
            </button>
        </div>
        <div class="col-md-6">
            <button type="button" class="btn btn-clear-custom w-100" data-deposit-action="cancel">
                <i class="fas fa-times me-1"></i> Cancel
            </button>
        </div>
    </div>
</div>
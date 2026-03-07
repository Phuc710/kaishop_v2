<?php
$binanceRateVnd = max(1, (int) ($depositPanel['binanceRateVnd'] ?? 25000));
$isBinanceEnabled = ((int) ($chungapi['binance_pay_enabled'] ?? 0) === 1);

// Hardcoded tiers requested by user
$quickButtons = [
    ['usd' => 1.0, 'percent' => 0],
    ['usd' => 4.0, 'percent' => 10],
    ['usd' => 8.0, 'percent' => 15],
    ['usd' => 20.0, 'percent' => 20],
];
?>

<?php if (!$isBinanceEnabled && !$activeDepositExists): ?>
    <div class="maintenance-notice text-center py-5">
        <div class="maintenance-icon mb-4">
            <i class="fas fa-tools fa-4x text-muted"></i>
        </div>
        <h4 class="font-weight-bold text-dark">METHOD UNDER MAINTENANCE</h4>
        <p class="text-muted">Binance Pay is currently under maintenance for system updates. Please check back later or use
            other payment methods.</p>
        <a href="<?= url('balance/bank') ?>" class="btn btn-outline-primary mt-3">
            <i class="fas fa-exchange-alt me-1"></i> Switch to Bank Transfer
        </a>
    </div>
    <?php return; endif; ?>
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
        <h6 class="deposit-panel-title">Payment Details</h6>
    </div>

    <div class="deposit-info-list">
        <div class="deposit-info-row">
            <span class="deposit-info-label">Recipient Binance ID</span>
            <div class="deposit-info-actions">
                <span class="deposit-info-value"
                    data-tf-binance-uid><?= htmlspecialchars((string) ($activeDepositPayload['binance_uid'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <button type="button" class="btn-copy" data-copy-target="uid"><i class="fas fa-copy"></i></button>
            </div>
        </div>
        <div class="deposit-info-row">
            <span class="deposit-info-label">Sender UID</span>
            <span class="deposit-info-value"
                data-tf-payer-uid><?= htmlspecialchars((string) ($activeDepositPayload['payer_uid'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="deposit-info-row">
            <span class="deposit-info-label">Amount to Send</span>
            <div class="deposit-info-actions">
                <span class="deposit-info-value is-highlight"
                    data-tf-usdt><?= htmlspecialchars((string) ($activeDepositPayload['usdt_amount'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>
                    USDT</span>
                <button type="button" class="btn-copy" data-copy-target="usdt"><i class="fas fa-copy"></i></button>
            </div>
        </div>
        <div class="deposit-info-row">
            <span class="deposit-info-label">Status</span>
            <span class="pd-chip info" data-tf-status><i class="fas fa-spinner fa-spin"></i> Pending</span>
        </div>
    </div>

    <div class="deposit-countdown-wrap mt-3" data-deposit-countdown-wrap>
        <div class="deposit-countdown-label">Time Remaining</div>
        <div class="deposit-countdown" data-deposit-countdown>05:00</div>
        <div class="deposit-countdown-bar">
            <div class="deposit-countdown-fill" data-deposit-countdown-fill style="width:100%"></div>
        </div>
    </div>

    <?php
    $binanceWarning = (string) ($chungapi['deposit_warning_binance'] ?? '');
    if (!empty($binanceWarning)) {
        $amountToSwap = htmlspecialchars((string) ($activeDepositPayload['usdt_amount'] ?? '0.00'), ENT_QUOTES, 'UTF-8');
        $uidToSwap = htmlspecialchars((string) ($activeDepositPayload['binance_uid'] ?? ''), ENT_QUOTES, 'UTF-8');
        $binanceWarning = str_replace(['{amount}', '{uid}'], ["<strong>{$amountToSwap} USDT</strong>", "<strong>{$uidToSwap}</strong>"], $binanceWarning);
    }
    ?>
    <?php if (!empty($binanceWarning)): ?>
        <div class="alert alert-warning deposit-warning mt-3 mb-0">
            <?= $binanceWarning ?>
        </div>
    <?php endif; ?>

    <button type="button" class="btn btn-edit-profile w-100 mt-3" data-deposit-action="check">
        <i class="fas fa-search me-1"></i> Check Payment
    </button>

    <button type="button" class="btn btn-clear-custom w-100 mt-3" data-deposit-action="cancel">
        <i class="fas fa-times me-1"></i> Cancel Transaction
    </button>
</div>
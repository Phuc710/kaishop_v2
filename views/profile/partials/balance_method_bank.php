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
            <input type="number" id="depositAmountInput" class="form-control" min="10000" step="1000" value="10000"
                data-deposit-input-amount>
            <span class="input-group-text">VND</span>
        </div>
        <small class="text-muted">Tối thiểu 10.000đ</small>
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
        <i class="fas fa-paper-plane me-1"></i> Tạo giao dịch
    </button>
</div>

<div class="deposit-panel-step" data-deposit-step="transfer" <?= $activeDepositExists ? '' : 'hidden' ?>>
    <div class="deposit-panel-title-row deposit-panel-title-row--center">
        <h6 class="deposit-panel-title">Thông tin chuyển khoản</h6>
    </div>

    <div class="row align-items-start g-3">
        <div class="col-lg-5">
            <div class="deposit-qr-card">
                <div class="deposit-qr-box">
                    <img data-deposit-qr src="<?= htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8') ?>" alt="QR thanh toán">
                    <div class="deposit-qr-logo">
                        <img src="<?= htmlspecialchars((string) ($chungapi['favicon'] ?? ($chungapi['logo'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                            alt="Logo">
                    </div>
                </div>
                <button type="button" class="btn btn-search-custom w-100 mt-3" data-deposit-action="download-qr">
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
                    <span class="deposit-info-label">Nội dung *</span>
                    <div class="deposit-info-actions">
                        <span class="deposit-info-value is-highlight"
                            data-tf-content><?= htmlspecialchars((string) ($activeDepositPayload['deposit_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <button type="button" class="btn-copy" data-copy-target="content"><i
                                class="fas fa-copy"></i></button>
                    </div>
                </div>
                <div class="deposit-info-row">
                    <span class="deposit-info-label">Tình trạng</span>
                    <span class="pd-chip info" data-tf-status><i class="fas fa-spinner fa-spin"></i> Đang xử
                        lý</span>
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
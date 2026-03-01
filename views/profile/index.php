<?php
$profileSection = (string) ($profileSection ?? 'profile');
$isBalanceSection = $profileSection === 'balance';
$userPageTitle = $isBalanceSection ? 'Nạp tiền' : 'Thông tin tài khoản';
$userPageAssetFlags = [
    'interactive_bundle' => false,
];
$activePage = $isBalanceSection ? 'balance' : 'profile';
require __DIR__ . '/layout/header.php';
?>

<?php if ($isBalanceSection): ?>
    <?php require __DIR__ . '/balance.php'; ?>
    <script src="<?= asset('assets/js/balance-success.js') ?>"></script>
    <script src="<?= asset('assets/js/balance-bank.js') ?>"></script>
<?php else: ?>
    <?php
    $currentBalance = (int) ($user['money'] ?? 0);
    $totalDeposit = (int) ($user['tong_nap'] ?? 0);
    $usedBalance = max(0, $totalDeposit - $currentBalance);
    $twofaEnabled = (int) ($user['twofa_enabled'] ?? 0) === 1;
    ?>

    <div class="profile-card">
        <div class="profile-card-header profile-card-header--with-actions">
            <div>
                <h5 class="text-dark mb-1">VÍ CỦA TÔI</h5>
            </div>
        </div>
        <div class="profile-card-body p-4">
            <div class="row g-3">
                <div class="col-xl-4 col-md-6">
                    <div class="stat-box neutral">
                        <div class="user-label user-label--sm">Số dư hiện tại</div>
                        <div class="balance-amount " style="color: #198754 !important;"><?= tien($currentBalance); ?>đ</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="stat-box neutral">
                        <div class="user-label user-label--sm">Tổng tiền nạp</div>
                        <div class="balance-amount"><?= tien($totalDeposit); ?>đ</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-12">
                    <div class="stat-box neutral">
                        <div class="user-label user-label--sm">Số dư đã sử dụng</div>
                        <div class="balance-amount"><?= tien($usedBalance); ?>đ</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-card" id="profile-info-card">
        <div class="profile-card-header profile-card-header--with-actions">
            <div>
                <h5 class="text-dark mb-1">HỒ SƠ CỦA BẠN</h5>
            </div>
            <div class="profile-card-header-actions">
                <button type="button" id="btn-edit" class="btn btn-edit-profile">Chỉnh sửa thông tin</button>
            </div>
        </div>
        <div class="profile-card-body p-4">
            <form id="profile-form" class="row g-4" novalidate>
                <input type="hidden" name="twofa_enabled" value="<?= $twofaEnabled ? '1' : '0' ?>">

                <div class="col-md-6">
                    <label class="form-label user-label">Tên đăng nhập</label>
                    <div class="custom-input-wrap">
                        <input type="text" class="form-control custom-readonly"
                            value="<?= htmlspecialchars((string) $username, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label user-label">Địa chỉ Email</label>
                    <div class="custom-input-wrap">
                        <input type="email" name="email" id="email_input" class="form-control custom-readonly"
                            value="<?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label user-label">Ngày đăng ký</label>
                    <div class="custom-input-wrap">
                        <input type="text" class="form-control custom-readonly"
                            value="<?= htmlspecialchars((string) ($user['time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label user-label">Đăng nhập gần nhất</label>
                    <div class="custom-input-wrap">
                        <input type="text" class="form-control custom-readonly"
                            value="<?= htmlspecialchars((string) ($user['ip'] ?? 'Chưa cập nhật'), ENT_QUOTES, 'UTF-8'); ?>"
                            readonly>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php
    $linkModel = new UserTelegramLink();
    $tgLink = $linkModel->findByUserId($user['id']);
    $isTelegramSection = ($profileSection === 'telegram');
    ?>
    <div class="profile-card mt-4 <?= $isTelegramSection ? 'border-primary shadow-sm' : '' ?>" id="telegram-link-section">
        <div class="profile-card-header">
            <div>
                <h5 class="text-dark mb-1">LIÊN KẾT TELEGRAM BOT</h5>
            </div>
        </div>
        <div class="profile-card-body p-4">
            <?php if ($tgLink): ?>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fab fa-telegram fa-3x" style="color: #0088cc;"></i>
                        </div>
                        <div>
                            <div class="user-label">Đang liên kết với:</div>
                            <div class="fw-bold text-dark">
                                <?= htmlspecialchars((string) ($tgLink['telegram_username'] ?? $tgLink['first_name'] ?? 'Người dùng Telegram'), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="unlinkTelegram()" class="btn btn-outline-danger btn-sm rounded-pill px-3">Hủy
                        liên kết</button>
                </div>
            <?php else: ?>
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <p class="mb-0 text-muted">Liên kết tài khoản với Telegram Bot để nhận thông báo nạp tiền, mua hàng và
                            truy cập các tính năng nhanh ngay trên Telegram.</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <button type="button" id="btn-generate-tg" onclick="generateTelegramLink()"
                            class="btn btn-save-green w-100 w-md-auto">Tạo mã liên kết</button>
                    </div>
                </div>
                <div id="tg-otp-display" class="mt-3 p-4 bg-light rounded-3 border text-center"
                    style="display: none; border-style: dashed !important;">
                    <div class="user-label mb-2">Mã liên kết của bạn là:</div>
                    <div class="display-5 fw-bold text-success mb-2" id="tg-otp-code" style="letter-spacing: 5px;">000000</div>
                    <div class="small text-muted">
                        Vui lòng gửi lệnh bên dưới cho Bot <a
                            href="https://t.me/<?= trim((string) get_setting('telegram_bot_user', 'KaiShopBot')); ?>"
                            target="_blank"
                            class="fw-bold text-primary">@<?= htmlspecialchars((string) get_setting('telegram_bot_user', 'KaiShopBot')); ?></a>
                        <div class="mt-2 p-2 bg-white border rounded">
                            <code>/link <span id="tg-otp-code-val">000000</span></code>
                        </div>
                        <div class="mt-3 d-flex flex-column flex-sm-row justify-content-center align-items-center gap-2">
                            <button type="button" id="tg-copy-command" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-copy me-1"></i> Sao chép lệnh
                            </button>
                            <div class="text-danger">
                                <i class="fas fa-clock me-1"></i> Hết hạn sau <strong id="tg-countdown">05:00</strong>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-scroll to telegram section if requested
        document.addEventListener('DOMContentLoaded', function () {
            if (window.location.search.includes('section=telegram')) {
                const section = document.getElementById('telegram-link-section');
                if (section) {
                    section.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    </script>

    <script>
        /**
         * TelegramLinkManager (OOP)
         * Quản lý liên kết tài khoản với Telegram Bot
         */
        class TelegramLinkManager {
            constructor(config) {
                this.apiUrl = config.apiUrl;
                this.unlinkUrl = config.unlinkUrl;
                this.activeOtp = config.activeOtp || null;

                // Elements
                this.btnGenerate = document.getElementById('btn-generate-tg');
                this.displayContainer = document.getElementById('tg-otp-display');
                this.otpText = document.getElementById('tg-otp-code');
                this.otpValSnippet = document.getElementById('tg-otp-code-val');
                this.countdownText = document.getElementById('tg-countdown');
                this.copyButton = document.getElementById('tg-copy-command');

                this.timer = null;
                this.init();
            }

            init() {
                this.bindEvents();

                if (this.activeOtp && this.activeOtp.code) {
                    this.showOtp(this.activeOtp.code, this.activeOtp.expires_at);
                }
            }

            bindEvents() {
                if (!this.copyButton) return;

                this.copyButton.addEventListener('click', () => {
                    this.copyCommand();
                });
            }

            async generate() {
                if (!this.btnGenerate) return;

                this.btnGenerate.disabled = true;
                this.btnGenerate.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang tạo...';

                try {
                    const response = await fetch(this.apiUrl, { method: 'POST' });
                    const data = await response.json();

                    if (data.success) {
                        // Update bot link if dynamic username provided
                        if (data.bot_username) {
                            this.updateBotLink(data.bot_username);
                        }

                        this.showOtp(data.code, data.expires_at || null, true);
                        this.btnGenerate.innerHTML = '<i class="fas fa-check"></i> Đã tạo mã';
                    } else {
                        SwalHelper.error(data.message || 'Không thể tạo mã');
                        this.resetButton();
                    }
                } catch (e) {
                    SwalHelper.error('Có lỗi xảy ra');
                    this.resetButton();
                }
            }

            updateBotLink(username) {
                const linkEl = this.displayContainer ? this.displayContainer.querySelector('a') : null;
                if (linkEl) {
                    linkEl.href = `https://t.me/${username}`;
                    linkEl.innerText = `@${username}`;
                }
            }

            showOtp(code, expiresAt = null, shouldFocus = false) {
                if (!this.displayContainer || !this.otpText) return;

                this.otpText.innerText = code;
                if (this.otpValSnippet) this.otpValSnippet.innerText = code;
                this.displayContainer.style.display = 'block';
                if (shouldFocus) {
                    this.displayContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }

                // Handle auto-expiration UI
                if (expiresAt) {
                    const expiryTs = this.parseExpiry(expiresAt);
                    this.startExpirationTimer(expiryTs);
                }
            }

            parseExpiry(expiresAt) {
                if (typeof expiresAt === 'number') return expiresAt;
                if (expiresAt instanceof Date) return expiresAt.getTime();

                const raw = String(expiresAt || '').trim();
                if (!raw) return Date.now() + (5 * 60 * 1000);

                const normalized = raw.replace(' ', 'T');
                const parsed = Date.parse(normalized);
                if (!Number.isNaN(parsed)) return parsed;

                const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})$/);
                if (!match) return Date.now() + (5 * 60 * 1000);

                const [, year, month, day, hour, minute, second] = match;
                return new Date(
                    Number(year),
                    Number(month) - 1,
                    Number(day),
                    Number(hour),
                    Number(minute),
                    Number(second)
                ).getTime();
            }

            startExpirationTimer(expiryTs) {
                if (this.timer) clearInterval(this.timer);

                const update = () => {
                    const now = new Date().getTime();
                    const diff = expiryTs - now;

                    if (diff <= 0) {
                        this.onOtpExpired();
                        clearInterval(this.timer);
                        return;
                    }

                    if (this.countdownText) {
                        const minutes = Math.floor(diff / 60000);
                        const seconds = Math.floor((diff % 60000) / 1000);
                        this.countdownText.innerText = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    }
                };

                this.timer = setInterval(update, 1000);
                update();
            }

            onOtpExpired() {
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }

                if (this.displayContainer) {
                    this.displayContainer.style.transition = 'opacity 0.5s';
                    this.displayContainer.style.opacity = '0';
                    setTimeout(() => {
                        this.displayContainer.style.display = 'none';
                        this.displayContainer.style.opacity = '1';
                        this.resetButton();
                        if (this.countdownText) this.countdownText.innerText = '05:00';
                    }, 500);
                }
            }

            resetButton() {
                if (this.btnGenerate) {
                    this.btnGenerate.disabled = false;
                    this.btnGenerate.innerHTML = 'Tạo mã liên kết';
                }
            }

            async copyCommand() {
                const code = this.otpValSnippet ? this.otpValSnippet.innerText.trim() : '';
                if (!code || code === '000000') {
                    SwalHelper.error('Chưa có mã liên kết để sao chép');
                    return;
                }

                const command = `/link ${code}`;

                try {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(command);
                    } else {
                        const tempInput = document.createElement('input');
                        tempInput.value = command;
                        document.body.appendChild(tempInput);
                        tempInput.select();
                        document.execCommand('copy');
                        tempInput.remove();
                    }

                    SwalHelper.toast('Đã sao chép lệnh liên kết', 'success');
                } catch (e) {
                    SwalHelper.error('Không thể sao chép lệnh liên kết');
                }
            }

            async unlink() {
                const confirmed = await SwalHelper.confirm('Bạn có chắc muốn hủy liên kết Telegram?', 'Hành động này sẽ ngắt kết nối với Bot.');
                if (!confirmed) return;

                try {
                    const response = await fetch(this.unlinkUrl, { method: 'POST' });
                    const data = await response.json();
                    if (data.success) {
                        SwalHelper.toast(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        SwalHelper.error(data.message);
                    }
                } catch (e) {
                    SwalHelper.error('Có lỗi xảy ra');
                }
            }
        }

        // Initialize Manager
        const tgManager = new TelegramLinkManager({
            apiUrl: '<?= url('api/telegram/generate-link') ?>',
            unlinkUrl: '<?= url('api/telegram/unlink') ?>',
            activeOtp: <?= json_encode($activeTgOtp ?? null) ?>
        });

        // Wrapper functions for HTML onclick
        function generateTelegramLink() { tgManager.generate(); }
        function unlinkTelegram() { tgManager.unlink(); }
    </script>


    <script>
        (function () {
            let editMode = false;
            const form = document.getElementById('profile-form');
            const emailInput = document.getElementById('email_input');
            const editBtn = document.getElementById('btn-edit');

            function setEditMode(enabled) {
                editMode = !!enabled;
                if (!emailInput || !editBtn) {
                    return;
                }

                if (editMode) {
                    emailInput.removeAttribute('readonly');
                    emailInput.classList.remove('custom-readonly');
                    editBtn.innerHTML = 'Lưu thay đổi';
                    editBtn.classList.remove('btn-edit-profile');
                    editBtn.classList.add('btn-save-green');
                    emailInput.focus();
                    return;
                }

                emailInput.setAttribute('readonly', 'readonly');
                emailInput.classList.add('custom-readonly');
                editBtn.innerHTML = 'Chỉnh sửa thông tin';
                editBtn.classList.remove('btn-save-green');
                editBtn.classList.add('btn-edit-profile');
                editBtn.disabled = false;
            }

            async function submitProfile() {
                const formData = new FormData(form);

                if (window.KaiAuthForms && typeof window.KaiAuthForms.fetchFormJson === 'function') {
                    const params = new URLSearchParams();
                    formData.forEach((value, key) => params.append(key, value));
                    const result = await window.KaiAuthForms.fetchFormJson('<?= url('profile/update') ?>', params, { timeoutMs: 15000 });
                    return result.data || {};
                }

                const response = await fetch('<?= url('profile/update') ?>', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    cache: 'no-store'
                });
                const raw = await response.text();
                return JSON.parse(String(raw || '').replace(/^\uFEFF/, '').trim() || '{}');
            }

            if (!form || !emailInput || !editBtn) {
                return;
            }

            editBtn.addEventListener('click', async function () {
                if (!editMode) {
                    setEditMode(true);
                    return;
                }

                editBtn.disabled = true;
                editBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang lưu...';

                try {
                    const data = await submitProfile();
                    if (data && data.success) {
                        SwalHelper.toast(data.message || 'Đã cập nhật thông tin', 'success');
                        setEditMode(false);
                        return;
                    }
                    SwalHelper.error((data && data.message) ? data.message : 'Có lỗi xảy ra');
                    editBtn.disabled = false;
                    editBtn.innerHTML = 'Lưu thay đổi';
                } catch (error) {
                    SwalHelper.error('Có lỗi xảy ra, vui lòng thử lại!');
                    editBtn.disabled = false;
                    editBtn.innerHTML = 'Lưu thay đổi';
                }
            });
        })();
    </script>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>

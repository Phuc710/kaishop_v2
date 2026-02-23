<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Nạp Tiền |
        <?= $chungapi['ten_web']; ?>
    </title>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main>
        <section class="py-110">
            <div class="container">
                <?php require __DIR__ . '/../../hethong/settings_head.php'; ?>

                <div class="row justify-content-center">
                    <div class="col-lg-7 col-xl-6">

                        <!-- STEP 1: Chọn Số Tiền -->
                        <div id="step-amount" class="settings-card" <?= $activeDeposit ? 'style="display:none"' : '' ?>>
                            <div class="settings-card-head">
                                <h4><i class="fas fa-wallet mr-2"></i>NẠP TIỀN QUA NGÂN HÀNG</h4>
                            </div>
                            <div class="settings-card-body">
                                <p class="text-muted mb-3" style="font-size:14px;">Chọn nhanh mệnh giá hoặc nhập số tiền
                                    bạn muốn nạp:</p>

                                <!-- Quick Select Buttons -->
                                <div class="deposit-grid mb-4">
                                    <?php
                                    $baseButtons = [
                                        ['amount' => 10000, 'percent' => 0],
                                        ['amount' => 20000, 'percent' => 0],
                                        ['amount' => 50000, 'percent' => 0]
                                    ];
                                    $allButtons = array_merge($baseButtons, $bonusTiers);
                                    usort($allButtons, function($a, $b) { return $a['amount'] <=> $b['amount']; });
                                    
                                    $uniqueButtons = [];
                                    foreach ($allButtons as $btn) {
                                        if ($btn['amount'] > 0) {
                                            $amt = $btn['amount'];
                                            if (!isset($uniqueButtons[$amt]) || $uniqueButtons[$amt]['percent'] < $btn['percent']) {
                                                $uniqueButtons[$amt] = $btn;
                                            }
                                        }
                                    }
                                    
                                    foreach ($uniqueButtons as $btn):
                                        $amt = $btn['amount'];
                                        $pct = $btn['percent'];
                                    ?>
                                        <button type="button" class="deposit-quick-btn" data-amount="<?= $amt ?>" data-bonus="<?= $pct ?>"
                                            onclick="selectAmount(this)">
                                            <span class="deposit-amount"><?= number_format($amt, 0, ',', '.') ?>đ</span>
                                            <?php if ($pct > 0): ?>
                                            <span class="deposit-bonus">+<?= $pct ?>%</span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Custom Amount -->
                                <div class="mb-3">
                                    <label class="form-label text-muted" style="font-size:13px;">Hoặc nhập số tiền
                                        khác:</label>
                                    <div class="input-group">
                                        <input type="number" id="input-amount" class="form-control shadow-none"
                                            placeholder="10000" min="10000" step="1000" value="10000">
                                        <span class="input-group-text"
                                            style="background:#f1f5f9; font-weight:600;">VND</span>
                                    </div>
                                    <small class="text-muted">Số tiền nạp tối thiểu: <b>10.000đ</b></small>
                                </div>

                                <!-- Preview -->
                                <div id="deposit-preview" class="deposit-preview mb-3" style="display:none;">
                                    <div class="d-flex justify-content-between">
                                        <span>Số tiền nạp:</span>
                                        <b id="preview-amount">0đ</b>
                                    </div>
                                    <div class="d-flex justify-content-between" id="preview-bonus-row"
                                        style="display:none;">
                                        <span>Bonus:</span>
                                        <b id="preview-bonus" class="text-success">+0đ</b>
                                    </div>
                                    <hr style="margin:8px 0;">
                                    <div class="d-flex justify-content-between">
                                        <span><b>Tổng nhận:</b></span>
                                        <b id="preview-total"
                                            style="color: var(--primary-color); font-size:18px;">0đ</b>
                                    </div>
                                </div>

                                <button type="button" id="btn-create-deposit" class="btn btn-primary w-100 py-2"
                                    style="font-size:16px; font-weight:600; border-radius:10px;"
                                    onclick="createDeposit()">
                                    <i class="fas fa-paper-plane mr-1"></i> Tạo Giao Dịch
                                </button>
                            </div>
                        </div>

                        <!-- STEP 2: Thông Tin Chuyển Khoản -->
                        <div id="step-transfer" class="settings-card" <?= $activeDeposit ? '' : 'style="display:none"' ?>
                            >
                            <div class="settings-card-head text-center">
                                <h4><i class="fas fa-university mr-2"></i> THÔNG TIN CHUYỂN KHOẢN</h4>
                            </div>
                            <div class="settings-card-body">
                            
                                <?php
                                    // Map common bank names to VietQR compliant short names
                                    $bankMap = [
                                        'MB Bank' => 'MB',
                                        'Vietcombank' => 'VCB',
                                        'Techcombank' => 'TCB',
                                        'VietinBank' => 'CTG',
                                        'BIDV' => 'BIDV',
                                        'Agribank' => 'VBA',
                                        'VPBank' => 'VPB',
                                        'ACB' => 'ACB',
                                        'Sacombank' => 'STB',
                                        'TPBank' => 'TPB',
                                        'VIB' => 'VIB',
                                    ];
                                    $qrBankName = $bankMap[$bankName] ?? $bankName;
                                    $qrUrl = $activeDeposit ? 'https://img.vietqr.io/image/'.urlencode($qrBankName).'-'.urlencode($bankAccount).'-qr_only.png?amount='.$activeDeposit['amount'].'&addInfo='.$activeDeposit['deposit_code'].'&accountName='.urlencode($bankOwner) : 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
                                ?>
                                <div class="row align-items-center">
                                    <div class="col-md-5 text-center mb-4 mb-md-0">
                                        <div class="mx-auto" style="max-width:320px;">
                                            <div class="qr-wrapper bg-white shadow-sm" style="padding: 15px; border-radius: 16px; border: 2px solid #e2e8f0; position: relative; display: inline-block;">
                                                <img id="tf-qr" src="<?= $qrUrl ?>" alt="QR Code" class="img-fluid" style="width:100%; border-radius:8px;">
                                                <!-- Overlay Logo -->
                                                <div class="qr-logo-overlay" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 40px; height: 40px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15); padding: 5px;">
                                                    <img src="<?= $chungapi['favicon'] ?? $chungapi['logo']; ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 50%;">
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-success btn-lg mt-3 w-100 font-weight-bold shadow-sm" style="border-radius:12px; background-color: #10b981; border:none; padding:12px; letter-spacing:0.5px; text-transform:uppercase; font-size:13px;" onclick="downloadQR()">
                                                <i class="fas fa-download mr-2"></i> Tải QR về máy
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-7">
                                        <!-- Transfer Info -->
                                        <div class="deposit-info-list rounded" style="font-family: 'Roboto', sans-serif;">
                                            <div class="deposit-info-row">
                                                <span class="deposit-info-label">NGÂN HÀNG</span>
                                                <span class="deposit-info-value font-weight-bold text-dark text-right" id="tf-bank">
                                                    <?= htmlspecialchars($bankName) ?>
                                                </span>
                                            </div>
                                            <div class="deposit-info-row">
                                                <span class="deposit-info-label">CHỦ TÀI KHOẢN</span>
                                                <span class="deposit-info-value font-weight-bold text-dark text-right" id="tf-owner" style="text-transform:uppercase;">
                                                    <?= htmlspecialchars($bankOwner) ?>
                                                </span>
                                            </div>
                                            <div class="deposit-info-row">
                                                <span class="deposit-info-label">SỐ TÀI KHOẢN</span>
                                                <div class="d-flex align-items-center justify-content-end gap-2">
                                                    <span class="deposit-info-value font-weight-bold text-dark" id="tf-account"
                                                        style="letter-spacing:1px; cursor:pointer; font-size:18px;" onclick="copyText('tf-account')">
                                                        <?= htmlspecialchars($bankAccount) ?>
                                                    </span>
                                                    <button type="button" class="btn-copy" onclick="copyText('tf-account')" title="Copy">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="deposit-info-row">
                                                <span class="deposit-info-label text-dark font-weight-bold">NỘI DUNG <span class="text-danger">*</span></span>
                                                <div class="d-flex align-items-center justify-content-end gap-2">
                                                    <span class="deposit-info-value font-weight-bold text-dark" id="tf-content"
                                                        style="font-size:20px; cursor:pointer;" onclick="copyText('tf-content')">
                                                        <?= $activeDeposit ? htmlspecialchars($activeDeposit['deposit_code']) : '' ?>
                                                    </span>
                                                    <button type="button" class="btn-copy" onclick="copyText('tf-content')" title="Copy">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="deposit-info-row">
                                                <span class="deposit-info-label">SỐ TIỀN</span>
                                                <div class="d-flex align-items-center justify-content-end gap-2">
                                                    <span class="deposit-info-value font-weight-bold" id="tf-amount"
                                                        style="color:#10b981; font-size:24px;">
                                                        <?= $activeDeposit ? number_format((int) $activeDeposit['amount']) . 'đ' : '' ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <!-- Countdown -->
                                <div class="deposit-countdown-wrap text-center mb-4">
                                    <div class="text-muted mb-1" style="font-size:18px; font-weight:bold;">Thời gian còn lại</div>
                                    <div id="countdown-timer" class="deposit-countdown">05:00</div>
                                    <div class="deposit-countdown-bar">
                                        <div id="countdown-progress" class="deposit-countdown-fill" style="width:100%;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Warning -->
                                <div class="alert alert-warning mt-4 mb-3" style="border-radius:10px; font-size:15px; border-left: 4px solid #f59e0b;">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <b>Lưu ý nghiêm ngặt:</b> Bạn <b>phải</b> nhập đúng chính xác NỘI DUNG<span class="text-danger">*</span> chuyển khoản để hệ thống máy chủ tự động cộng tiền. Việc sai nội dung sẽ không được tự động xử lý.
                                </div>

                                <button type="button" id="btn-cancel-deposit" class="btn btn-cancel-custom w-100 py-3 mt-4" onclick="cancelDeposit()">
                                    <i class="fas fa-times mr-2"></i> HUỶ GIAO DỊCH
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    </main>

    <style>
        .deposit-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .deposit-quick-btn {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 14px 8px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s ease;
            min-height: 64px;
        }

        .deposit-quick-btn:hover,
        .deposit-quick-btn.active {
            border-color: #ff6900;
            background: #fff9f5;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 105, 0, 0.15);
        }

        .deposit-quick-btn .deposit-amount {
            font-weight: 700;
            font-size: 15px;
            color: #1e293b;
        }

        .deposit-quick-btn .deposit-bonus {
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            padding: 1px 8px;
            border-radius: 20px;
            margin-top: 4px;
        }

        .deposit-preview {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 16px;
            font-size: 14px;
        }

        .deposit-countdown-wrap {}

        .deposit-countdown {
            font-size: 40px;
            font-weight: 800;
            font-family: 'Courier New', monospace;
            color: #ef4444;
            letter-spacing: 3px;
        }

        .deposit-countdown-bar {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            margin-top: 8px;
            overflow: hidden;
        }

        .deposit-countdown-fill {
            height: 100%;
            background: linear-gradient(90deg, #ef4444, #f87171);
            border-radius: 3px;
            transition: width 1s linear;
        }

        .deposit-info-list {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .deposit-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border: 2px solid #e2e8f0;
            border-bottom: none;
            background: #fff;
        }

        .deposit-info-row:first-child {
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .deposit-info-row:last-child {
            border-bottom: 2px solid #e2e8f0;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .deposit-info-label {
            color: #475569;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .deposit-info-value {
            font-size: 16px;
            color: #0f172a;
        }



        .btn-cancel-custom {
            color: #ffffffff;
            background-color: #ef4444;
            border: 2px solid #ef4444;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px;
            transition: all 0.2s;
        }

        .btn-cancel-custom:hover {
            color: #ff0000ff;
            background-color: #ffffffff;
            border: 2px solid #ff0000ff;


        }

        .btn-copy {
            background: none;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 4px 8px;
            cursor: pointer;
            color: #64748b;
            font-size: 12px;
            transition: all 0.15s;
            margin-left: 8px;
        }

        .btn-copy:hover {
            background: #f1f5f9;
            color: #ff6900;
        }

        .gap-2 {
            gap: 8px;
        }

        @media (max-width: 480px) {
            .deposit-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .deposit-countdown {
                font-size: 32px;
            }
        }
    </style>

    <script>
        (function () {
            const BASE = '<?= BASE_URL ?>';
            let currentAmount = 10000;
            let currentBonus = 0;
            let countdownInterval = null;
            let pollInterval = null;
            let activeCode = '<?= $activeDeposit ? $activeDeposit['deposit_code'] : '' ?>';
            let activeExpiresAt = <?= $activeDeposit ? (strtotime($activeDeposit['created_at']) + 300) : 0 ?>;

            // If there's an active deposit, start countdown + polling immediately
            if (activeCode) {
                startCountdown(activeExpiresAt - Math.floor(Date.now() / 1000));
                startPolling(activeCode);
            }

            // Quick select button
            window.selectAmount = function (btn) {
                document.querySelectorAll('.deposit-quick-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentAmount = parseInt(btn.dataset.amount);
                currentBonus = parseInt(btn.dataset.bonus);
                document.getElementById('input-amount').value = currentAmount;
                updatePreview();
            };

            // Input change
            document.getElementById('input-amount').addEventListener('input', function () {
                currentAmount = parseInt(this.value) || 0;
                // Recalculate bonus dynamically
                const tiers = <?= json_encode($bonusTiers) ?>;
                tiers.sort((a, b) => b.amount - a.amount); // Highest first
                currentBonus = 0;
                for (let i = 0; i < tiers.length; i++) {
                    if (tiers[i].amount > 0 && currentAmount >= tiers[i].amount) {
                        currentBonus = tiers[i].percent;
                        break;
                    }
                }

                // Update quick-btn highlight
                document.querySelectorAll('.deposit-quick-btn').forEach(b => {
                    b.classList.toggle('active', parseInt(b.dataset.amount) === currentAmount);
                });
                updatePreview();
            });

            function updatePreview() {
                const preview = document.getElementById('deposit-preview');
                if (currentAmount >= 10000) {
                    preview.style.display = 'block';
                    const bonusAmt = Math.floor(currentAmount * currentBonus / 100);
                    document.getElementById('preview-amount').textContent = formatVND(currentAmount) + 'đ';
                    const bonusRow = document.getElementById('preview-bonus-row');
                    if (currentBonus > 0) {
                        bonusRow.style.display = 'flex';
                        document.getElementById('preview-bonus').textContent = '+' + formatVND(bonusAmt) + 'đ (' + currentBonus + '%)';
                    } else {
                        bonusRow.style.display = 'none';
                    }
                    document.getElementById('preview-total').textContent = formatVND(currentAmount + bonusAmt) + 'đ';
                } else {
                    preview.style.display = 'none';
                }
            }

            // Create deposit
            window.createDeposit = function () {
                const amount = parseInt(document.getElementById('input-amount').value) || 0;
                if (amount < 10000) {
                    Swal.fire({ icon: 'warning', title: 'Lỗi', text: 'Số tiền nạp tối thiểu 10.000đ' });
                    return;
                }

                const btn = document.getElementById('btn-create-deposit');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang tạo...';

                fetch(BASE + '/deposit/create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'amount=' + amount
                })
                    .then(r => r.json())
                    .then(res => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Tạo Giao Dịch';

                        if (res.success) {
                            const d = res.data;
                            activeCode = d.deposit_code;

                            // Fill step 2
                            document.getElementById('tf-bank').textContent = d.bank_name;
                            document.getElementById('tf-owner').textContent = d.bank_owner;
                            document.getElementById('tf-account').textContent = d.bank_account;
                            document.getElementById('tf-content').textContent = d.deposit_code;
                            document.getElementById('tf-amount').textContent = formatVND(d.amount) + 'đ';

                            // Update QR Code with correct short name
                            const bankMap = { 'MB Bank':'MB', 'Vietcombank':'VCB', 'Techcombank':'TCB', 'VietinBank':'CTG', 'BIDV':'BIDV', 'Agribank':'VBA', 'VPBank':'VPB', 'ACB':'ACB', 'Sacombank':'STB', 'TPBank':'TPB', 'MSB':'MSB', 'OCB':'OCB', 'VIB':'VIB', 'Momo':'MOMO' };
                            const qrBankName = bankMap[d.bank_name] || d.bank_name;
                            const qrUrl = "https://img.vietqr.io/image/" + encodeURIComponent(qrBankName) + "-" + encodeURIComponent(d.bank_account) + "-qr_only.png?amount=" + d.amount + "&addInfo=" + encodeURIComponent(d.deposit_code) + "&accountName=" + encodeURIComponent(d.bank_owner);
                            document.getElementById('tf-qr').src = qrUrl;

                            // Switch view
                            document.getElementById('step-amount').style.display = 'none';
                            document.getElementById('step-transfer').style.display = 'block';

                            // Start countdown (5 min = 300s)
                            startCountdown(300);
                            startPolling(d.deposit_code);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Lỗi', text: res.message });
                        }
                    })
                    .catch(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Tạo Giao Dịch';
                        Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể kết nối server' });
                    });
            };

            // Download QR
            window.downloadQR = function() {
                const img = document.getElementById('tf-qr');
                const src = img.src;
                if (!src || src.includes('placeholder')) {
                    Swal.fire({ icon: 'warning', title: 'Chưa có QR', text: 'Vui lòng chờ QR tải xong.' });
                    return;
                }
                
                // Fetch and trigger download to avoid cross-origin issues with direct a.href
                fetch(src)
                  .then(response => response.blob())
                  .then(blob => {
                      const link = document.createElement('a');
                      link.href = URL.createObjectURL(blob);
                      link.download = 'QR_ThanhToan_' + activeCode + '.png';
                      document.body.appendChild(link);
                      link.click();
                      document.body.removeChild(link);
                  })
                  .catch(console.error);
            };

            // Cancel deposit
            window.cancelDeposit = function () {
                Swal.fire({
                    icon: 'warning',
                    title: 'Huỷ giao dịch?',
                    text: 'Bạn có chắc muốn huỷ giao dịch nạp tiền này?',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Huỷ ngay',
                    cancelButtonText: 'Không'
                }).then(result => {
                    if (result.isConfirmed) {
                        fetch(BASE + '/deposit/cancel', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'deposit_code=' + encodeURIComponent(activeCode)
                        })
                            .then(r => r.json())
                            .then(res => {
                                stopAll();
                                if (res.success) {
                                    Swal.fire({ icon: 'info', title: 'Đã huỷ', text: 'Giao dịch đã được huỷ.' })
                                        .then(() => location.reload());
                                } else {
                                    Swal.fire({ icon: 'error', title: 'Lỗi', text: res.message });
                                }
                            });
                    }
                });
            };

            // Copy helper (Top Right Toast)
            window.copyText = function (elId) {
                const el = document.getElementById(elId);
                const text = el.textContent.trim();
                navigator.clipboard.writeText(text).then(() => {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Đã copy: ' + text, showConfirmButton: false, timer: 1500 });
                }).catch(() => {
                    // Fallback
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Đã copy', showConfirmButton: false, timer: 1500 });
                });
            };

            // Countdown
            function startCountdown(seconds) {
                if (countdownInterval) clearInterval(countdownInterval);
                let remaining = Math.max(0, seconds);
                const total = 300;

                function tick() {
                    const m = Math.floor(remaining / 60);
                    const s = remaining % 60;
                    document.getElementById('countdown-timer').textContent =
                        String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                    document.getElementById('countdown-progress').style.width =
                        (remaining / total * 100) + '%';

                    if (remaining <= 0) {
                        clearInterval(countdownInterval);
                        stopAll();
                        Swal.fire({ icon: 'info', title: 'Hết thời gian', text: 'Giao dịch đã hết hạn. Vui lòng tạo giao dịch mới.' })
                            .then(() => location.reload());
                        return;
                    }
                    remaining--;
                }
                tick();
                countdownInterval = setInterval(tick, 1000);
            }

            // Long polling
            function startPolling(code) {
                if (pollInterval) clearInterval(pollInterval);
                pollInterval = setInterval(() => {
                    fetch(BASE + '/deposit/status/' + encodeURIComponent(code))
                        .then(r => r.json())
                        .then(res => {
                            if (!res.success) return;

                            if (res.status === 'completed') {
                                stopAll();
                                Swal.fire({
                                    icon: 'success',
                                    title: '🎉 Nạp tiền thành công!',
                                    html: 'Số dư mới: <b>' + formatVND(res.new_balance) + 'đ</b>',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#3085d6'
                                }).then(() => {
                                    window.location.href = BASE + '/profile';
                                });
                            } else if (res.status === 'expired' || res.status === 'cancelled') {
                                stopAll();
                                location.reload();
                            }
                        })
                        .catch(() => { }); // Silently ignore network errors
                }, 3000);
            }

            function stopAll() {
                if (countdownInterval) clearInterval(countdownInterval);
                if (pollInterval) clearInterval(pollInterval);
            }

            function formatVND(n) {
                return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            // Init preview
            updatePreview();
        })();
    </script>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>
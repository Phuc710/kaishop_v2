<?php
$stock = (int) ($stock ?? 0);
$error = (string) ($error ?? '');
$success = (string) ($success ?? '');
$siteUrl = url('');
$productImg = 'https://i.imgur.com/gpt-pro-thumb.jpg'; // Replace with real CDN image if needed
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>ChatGPT Pro Business — Thêm Farm 1 Tháng |
        <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop', ENT_QUOTES, 'UTF-8') ?>
    </title>
    <meta name="description"
        content="Mua slot ChatGPT Pro Business — Nhập Gmail, hệ thống tự động invite vào Farm ngay lập tức. Bảo hành tự động bằng AI Guard.">
    <style>
        :root {
            --cgpt-green: #10a37f;
            --cgpt-dark: #0f172a;
            --cgpt-card: #1e293b;
            --cgpt-border: #334155;
        }

        .cgpt-wrap {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1a2744 60%, #0e3d2e 100%);
            padding: 80px 0 60px;
        }

        .cgpt-hero {
            text-align: center;
            margin-bottom: 40px;
        }

        .cgpt-logo {
            width: 72px;
            height: 72px;
            background: #10a37f;
            border-radius: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin-bottom: 18px;
            box-shadow: 0 0 40px rgba(16, 163, 127, .35);
        }

        .cgpt-hero h1 {
            color: #f1f5f9;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .cgpt-hero p {
            color: #94a3b8;
            font-size: 1rem;
            max-width: 480px;
            margin: 0 auto;
        }

        .cgpt-card {
            background: rgba(30, 41, 59, .92);
            border: 1px solid #334155;
            border-radius: 20px;
            padding: 36px 32px;
            backdrop-filter: blur(12px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, .4);
        }

        .cgpt-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 28px;
        }

        .cgpt-feature {
            background: rgba(16, 163, 127, .08);
            border: 1px solid rgba(16, 163, 127, .25);
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .cgpt-feature-icon {
            font-size: 20px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .cgpt-feature-text strong {
            display: block;
            color: #e2e8f0;
            font-size: .88rem;
            font-weight: 700;
        }

        .cgpt-feature-text span {
            font-size: .78rem;
            color: #64748b;
        }

        .cgpt-price-block {
            background: linear-gradient(135deg, rgba(16, 163, 127, .15) 0%, rgba(16, 163, 127, .05) 100%);
            border: 1px solid rgba(16, 163, 127, .3);
            border-radius: 14px;
            padding: 20px 24px;
            text-align: center;
            margin-bottom: 24px;
        }

        .cgpt-price-label {
            font-size: .78rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 4px;
        }

        .cgpt-price-val {
            font-size: 2.4rem;
            font-weight: 900;
            color: #10a37f;
            line-height: 1;
        }

        .cgpt-price-old {
            font-size: .9rem;
            color: #475569;
            text-decoration: line-through;
            margin-left: 8px;
        }

        .cgpt-price-period {
            font-size: .82rem;
            color: #94a3b8;
            margin-top: 4px;
        }

        .cgpt-stock-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }

        .cgpt-stock-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background:
                <?= $stock > 0 ? '#10a37f' : '#ef4444' ?>
            ;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .4
            }
        }

        .cgpt-stock-text {
            font-size: .82rem;
            color:
                <?= $stock > 0 ? '#34d399' : '#f87171' ?>
            ;
            font-weight: 600;
        }

        .cgpt-form-label {
            display: block;
            font-size: .85rem;
            font-weight: 700;
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .cgpt-form-input {
            width: 100%;
            padding: 14px 16px;
            background: #0f172a;
            border: 1.5px solid #334155;
            border-radius: 12px;
            color: #f1f5f9;
            font-size: .95rem;
            transition: border-color .2s, box-shadow .2s;
            margin-bottom: 16px;
        }

        .cgpt-form-input:focus {
            outline: none;
            border-color: #10a37f;
            box-shadow: 0 0 0 3px rgba(16, 163, 127, .15);
        }

        .cgpt-form-input::placeholder {
            color: #475569;
        }

        .cgpt-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #10a37f, #059669);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform .15s, box-shadow .2s;
            letter-spacing: .02em;
        }

        .cgpt-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(16, 163, 127, .35);
        }

        .cgpt-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
            transform: none;
        }

        .cgpt-alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: .88rem;
            font-weight: 600;
        }

        .cgpt-alert-error {
            background: rgba(239, 68, 68, .12);
            border: 1px solid rgba(239, 68, 68, .3);
            color: #fca5a5;
        }

        .cgpt-alert-success {
            background: rgba(16, 163, 127, .12);
            border: 1px solid rgba(16, 163, 127, .3);
            color: #6ee7b7;
        }

        .cgpt-note {
            font-size: .78rem;
            color: #475569;
            line-height: 1.6;
            margin-top: 12px;
        }

        .cgpt-divider {
            border: none;
            border-top: 1px solid #1e293b;
            margin: 24px 0;
        }

        .cgpt-steps {
            list-style: none;
            padding: 0;
            margin: 0;
            counter-reset: step;
        }

        .cgpt-steps li {
            counter-increment: step;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 8px 0;
            color: #94a3b8;
            font-size: .85rem;
        }

        .cgpt-steps li::before {
            content: counter(step);
            width: 24px;
            height: 24px;
            background: #10a37f;
            color: #fff;
            border-radius: 50%;
            font-size: .72rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        @media (max-width: 576px) {
            .cgpt-features {
                grid-template-columns: 1fr;
            }

            .cgpt-card {
                padding: 24px 18px;
            }

            .cgpt-hero h1 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="cgpt-wrap">
        <div class="container">
            <div class="cgpt-hero">
                <div class="cgpt-logo">🤖</div>
                <h1>ChatGPT Pro Business</h1>
                <p>Thêm slot vào Farm — Hệ thống invite tự động, bảo vệ bởi AI Guard 24/7</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row g-4">
                        <!-- Left: Features -->
                        <div class="col-md-5">
                            <div class="cgpt-card h-100">
                                <h5 style="color:#e2e8f0;font-weight:700;margin-bottom:20px;">✨ Tính năng nổi bật</h5>
                                <div class="cgpt-features">
                                    <div class="cgpt-feature">
                                        <div class="cgpt-feature-icon">⚡</div>
                                        <div class="cgpt-feature-text">
                                            <strong>Auto Invite</strong>
                                            <span>Gửi invite tự động ngay sau thanh toán</span>
                                        </div>
                                    </div>
                                    <div class="cgpt-feature">
                                        <div class="cgpt-feature-icon">🛡️</div>
                                        <div class="cgpt-feature-text">
                                            <strong>AI Guard 24/7</strong>
                                            <span>Cron tự động bảo vệ farm mỗi phút</span>
                                        </div>
                                    </div>
                                    <div class="cgpt-feature">
                                        <div class="cgpt-feature-icon">👥</div>
                                        <div class="cgpt-feature-text">
                                            <strong>Farm Business</strong>
                                            <span>Tổ chức OpenAI</span>
                                        </div>
                                    </div>
                                    <div class="cgpt-feature">
                                        <div class="cgpt-feature-icon">🔒</div>
                                        <div class="cgpt-feature-text">
                                            <strong>An toàn tuyệt đối</strong>
                                            <span>Key mã hóa, không lộ ra frontend</span>
                                        </div>
                                    </div>
                                </div>

                                <hr class="cgpt-divider">

                                <h6 style="color:#94a3b8;font-weight:700;margin-bottom:12px;">📋 Quy trình</h6>
                                <ol class="cgpt-steps">
                                    <li>Nhập Gmail Google của bạn</li>
                                    <li>Hệ thống tự chọn Farm còn slot</li>
                                    <li>Invite tự động được gửi đến Gmail</li>
                                    <li>Kiểm tra email và chấp nhận lời mời</li>
                                    <li>Truy cập ChatGPT Pro Business ngay</li>
                                </ol>
                            </div>
                        </div>

                        <!-- Right: Order Form -->
                        <div class="col-md-7">
                            <div class="cgpt-card">
                                <div class="cgpt-price-block">
                                    <div class="cgpt-price-label">Giá thuê slot</div>
                                    <div>
                                        <span class="cgpt-price-val">Liên hệ</span>
                                    </div>
                                    <div class="cgpt-price-period">/ 1 tháng · ChatGPT Pro Business</div>
                                    <div class="cgpt-stock-bar">
                                        <div class="cgpt-stock-dot"></div>
                                        <div class="cgpt-stock-text">
                                            <?= $stock > 0 ? "Còn {$stock} slot trống" : 'Tạm hết slot — Liên hệ admin' ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($error !== ''): ?>
                                    <div class="cgpt-alert cgpt-alert-error">⚠️
                                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($success !== ''): ?>
                                    <div class="cgpt-alert cgpt-alert-success">✅
                                        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($stock > 0): ?>
                                    <form method="post" action="<?= url('gpt-business/order') ?>" id="cgptOrderForm">
                                        <label class="cgpt-form-label" for="customer_email">
                                            📧 Gmail của bạn <span style="color:#ef4444">*</span>
                                        </label>
                                        <input type="email" id="customer_email" name="customer_email"
                                            class="cgpt-form-input" placeholder="example@gmail.com" required
                                            autocomplete="email">
                                        <div class="cgpt-note" style="margin-top:-10px;margin-bottom:14px;">
                                            ⚠️ Nhập đúng Gmail bạn dùng để đăng nhập ChatGPT. Invite sẽ gửi đến địa chỉ này.
                                        </div>

                                        <button type="submit" class="cgpt-btn" id="cgptSubmitBtn">
                                            🚀 Đăng ký slot ngay
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="cgpt-alert cgpt-alert-error text-center">
                                        😔 Tất cả slot đã được đặt. Vui lòng liên hệ admin để thêm farm mới.
                                    </div>
                                <?php endif; ?>

                                <p class="cgpt-note text-center" style="margin-top:16px;">
                                    Bằng cách đặt hàng, bạn đồng ý với
                                    <a href="<?= url('chinh-sach') ?>" style="color:#10a37f">Chính sách dịch vụ</a>.
                                    Invite sẽ hết hạn sau 72h nếu không chấp nhận.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
    <script>
        document.getElementById('cgptOrderForm')?.addEventListener('submit', function (e) {
            var btn = document.getElementById('cgptSubmitBtn');
            btn.disabled = true;
            btn.textContent = '⏳ Đang xử lý...';
        });
    </script>
</body>

</html>
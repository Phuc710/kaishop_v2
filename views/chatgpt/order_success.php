<?php
$order = $order ?? [];
$email = (string) ($email ?? $order['customer_email'] ?? '');
$orderCode = (string) ($order['order_code'] ?? '');
?>
<!DOCTYPE html>
<html lang="<?= function_exists('app_is_english') && app_is_english() ? 'en' : 'vi' ?>">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Đặt hàng thành công — ChatGPT Pro |
        <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop', ENT_QUOTES, 'UTF-8') ?>
    </title>
    <style>
        .success-wrap {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1a2744 60%, #0e3d2e 100%);
            display: flex;
            align-items: center;
            padding: 80px 0;
        }

        .success-card {
            background: rgba(30, 41, 59, .92);
            border: 1px solid #334155;
            border-radius: 24px;
            padding: 48px 40px;
            text-align: center;
            backdrop-filter: blur(12px);
            box-shadow: 0 24px 64px rgba(0, 0, 0, .5);
            max-width: 560px;
            margin: 0 auto;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10a37f, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            margin: 0 auto 24px;
            box-shadow: 0 0 40px rgba(16, 163, 127, .4);
            animation: bounceIn .5s ease;
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0.5);
                opacity: 0
            }

            70% {
                transform: scale(1.1)
            }

            100% {
                transform: scale(1);
                opacity: 1
            }
        }

        .success-title {
            color: #f1f5f9;
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .success-sub {
            color: #64748b;
            font-size: .95rem;
            margin-bottom: 28px;
        }

        .success-info-box {
            background: #0f172a;
            border: 1px solid #1e3a50;
            border-radius: 14px;
            padding: 20px 24px;
            text-align: left;
            margin-bottom: 24px;
        }

        .success-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: .88rem;
        }

        .success-info-row+.success-info-row {
            border-top: 1px solid #1e293b;
        }

        .success-info-label {
            color: #64748b;
        }

        .success-info-val {
            color: #e2e8f0;
            font-weight: 600;
        }

        .success-steps {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: left;
        }

        .success-steps li {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #1e293b;
            color: #94a3b8;
            font-size: .87rem;
        }

        .success-steps li:last-child {
            border-bottom: none;
        }

        .success-step-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #10a37f22;
            border: 1px solid #10a37f55;
            color: #10a37f;
            font-size: .7rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .success-btn {
            display: inline-block;
            padding: 12px 28px;
            background: linear-gradient(135deg, #10a37f, #059669);
            color: #fff;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: .95rem;
            transition: transform .15s, box-shadow .2s;
            margin-top: 4px;
        }

        .success-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(16, 163, 127, .3);
            color: #fff;
        }
    </style>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="success-wrap">
        <div class="container">
            <div class="success-card">
                <div class="success-icon">✅</div>
                <h1 class="success-title">Đặt slot thành công!</h1>
                <p class="success-sub">Lời mời đã được gửi đến Gmail của bạn. Vui lòng kiểm tra hộp thư.</p>

                <div class="success-info-box">
                    <?php if ($orderCode !== ''): ?>
                        <div class="success-info-row">
                            <span class="success-info-label">Mã đơn hàng</span>
                            <span class="success-info-val" style="font-family:monospace;color:#38bdf8">
                                <?= htmlspecialchars($orderCode) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div class="success-info-row">
                        <span class="success-info-label">Gmail nhận invite</span>
                        <span class="success-info-val">
                            <?= htmlspecialchars($email) ?>
                        </span>
                    </div>
                    <div class="success-info-row">
                        <span class="success-info-label">Sản phẩm</span>
                        <span class="success-info-val">ChatGPT Pro Business</span>
                    </div>
                    <div class="success-info-row">
                        <span class="success-info-label">Trạng thái</span>
                        <span class="success-info-val" style="color:#fbbf24">⏳ Chờ chấp nhận invite</span>
                    </div>
                </div>

                <div class="success-info-box">
                    <div
                        style="font-size:.82rem;color:#64748b;font-weight:700;text-transform:uppercase;margin-bottom:8px;">
                        Các bước tiếp theo
                    </div>
                    <ol class="success-steps">
                        <li>
                            <div class="success-step-num">1</div>
                            <span>Kiểm tra Gmail <strong style="color:#e2e8f0">
                                    <?= htmlspecialchars($email) ?>
                                </strong> — tìm email từ OpenAI với tiêu đề "You've been invited to join..."</span>
                        </li>
                        <li>
                            <div class="success-step-num">2</div>
                            <span>Nhấn <strong style="color:#10a37f">Accept invitation</strong> trong email</span>
                        </li>
                        <li>
                            <div class="success-step-num">3</div>
                            <span>Đăng nhập ChatGPT tại <strong style="color:#38bdf8">chatgpt.com</strong> bằng chính
                                Gmail đó</span>
                        </li>
                        <li>
                            <div class="success-step-num">4</div>
                            <span>Chọn tổ chức Business — Enjoy ChatGPT Pro 🎉</span>
                        </li>
                    </ol>
                </div>

                <p style="color:#475569;font-size:.78rem;margin-bottom:16px;">
                    ⚠️ Invite có hiệu lực 72h. Nếu không nhận được email, kiểm tra thư mục Spam hoặc liên hệ hỗ trợ.
                </p>

                <a href="<?= url('gpt-business/buy') ?>" class="success-btn">
                    ← Mua thêm slot
                </a>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>

<?php
/**
 * Test Mail — Full email template tester using MailService
 * URL: /test_mail.php
 */

require_once __DIR__ . '/app/Helpers/EnvHelper.php';
EnvHelper::load(__DIR__ . '/.env');
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/hethong/config.php';
require_once __DIR__ . '/app/Services/MailService.php';

$templates = [
    'test' => '🧪 Test cơ bản',
    'welcome' => '🎉 Welcome (Đăng ký thành công)',
    'otp_login' => '🔐 OTP Đăng nhập (2FA)',
    'otp_forgot' => '🔑 OTP Quên mật khẩu',
    'password_reset' => '🔒 Link đặt lại mật khẩu',
    'order_account' => '📦 Đơn hàng — Tài khoản (account_stock)',
    'order_source' => '📦 Đơn hàng — Source link',
    'order_manual' => '📦 Đơn hàng — Yêu cầu thông tin',
    'all' => '🚀 GỬI TẤT CẢ (Test toàn bộ)',
];

$result = null;
$results = [];
$error = null;
$sentTo = '';
$selectedTemplate = 'test';

// Fake data for testing
function fakeUser(string $email): array
{
    return [
        'id' => 999,
        'username' => 'TestUser',
        'email' => $email,
        'level' => '0',
        'money' => '500000',
    ];
}

function fakeOrder(): array
{
    return [
        'order_code_short' => strtoupper(substr(md5(uniqid()), 0, 8)),
        'product_name' => 'Netflix Premium 1 Tháng',
        'quantity' => 1,
        'unit_price' => 79000,
        'total_price' => 79000,
        'ordered_at' => date('Y-m-d H:i:s'),
        'status' => 'completed',
        'delivery_content' => "email: test@netflix.com\npassword: KaiShop@2025",
        'customer_input' => '',
        'source_link' => 'https://example.com/download/abc123',
        'info_instructions' => 'Vui lòng cung cấp email Netflix của bạn để nhận tài khoản.',
    ];
}

function fakeProduct(string $mode): array
{
    $types = [
        'account_stock' => ['delivery_type' => 'account_stock', 'type' => '1'],
        'source_link' => ['delivery_type' => 'source_link', 'type' => '3'],
        'manual_info' => ['delivery_type' => 'manual_info', 'type' => '2'],
    ];
    return array_merge([
        'name' => 'Netflix Premium 1 Tháng',
        'image' => '',
        'source_link' => 'https://example.com/download/abc123',
        'info_instructions' => 'Vui lòng cung cấp email Netflix để nhận tài khoản.',
    ], $types[$mode] ?? $types['account_stock']);
}

function sendTemplate(MailService $mail, string $type, string $email): array
{
    $user = fakeUser($email);
    $label = '';
    $ok = false;

    try {
        switch ($type) {
            case 'test':
                $label = '🧪 Test cơ bản';
                $ts = class_exists('TimeService') ? TimeService::instance()->nowSql() : date('Y-m-d H:i:s');
                $body = $mail->buildLayout(
                    '🧪 Test Email — KaiShop',
                    '📧 Test Email Thành Công!',
                    '<p style="font-size:16px;color:#333;">Đây là email test từ <strong>KaiShop</strong>.</p>'
                    . '<p style="color:#888;margin-top:12px;">Thời gian: ' . $ts . '</p>'
                );
                $ok = $mail->send($email, $email, '🧪 Test Email — KaiShop', $body);
                break;

            case 'welcome':
                $label = '🎉 Welcome';
                $ok = $mail->sendWelcomeRegister($user);
                break;

            case 'otp_login':
                $label = '🔐 OTP Đăng nhập';
                $mail->sendOtp($email, 'TestUser', '483927', 'login_2fa', 300);
                $ok = true;
                break;

            case 'otp_forgot':
                $label = '🔑 OTP Quên mật khẩu';
                $mail->sendOtp($email, 'TestUser', '715308', 'forgot_password', 600);
                $ok = true;
                break;

            case 'password_reset':
                $label = '🔒 Password Reset';
                $ok = $mail->sendPasswordReset($user, 'TESTRESET' . strtoupper(substr(md5(time()), 0, 6)));
                break;

            case 'order_account':
                $label = '📦 Order (Tài khoản)';
                $ok = $mail->sendOrderSuccess($user, fakeOrder(), fakeProduct('account_stock'));
                break;

            case 'order_source':
                $label = '📦 Order (Source)';
                $ok = $mail->sendOrderSuccess($user, fakeOrder(), fakeProduct('source_link'));
                break;

            case 'order_manual':
                $label = '📦 Order (Yêu cầu)';
                $order = fakeOrder();
                $order['status'] = 'pending';
                $ok = $mail->sendOrderSuccess($user, $order, fakeProduct('manual_info'));
                break;
        }
    } catch (Throwable $e) {
        return ['type' => $type, 'label' => $label, 'ok' => false, 'error' => $e->getMessage()];
    }

    return ['type' => $type, 'label' => $label, 'ok' => $ok, 'error' => $ok ? '' : 'send() returned false'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toEmail = trim($_POST['to_email'] ?? '');
    $selectedTemplate = trim($_POST['template'] ?? 'test');
    $sentTo = $toEmail;

    if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } else {
        $mail = new MailService();

        if ($selectedTemplate === 'all') {
            $allTypes = ['test', 'welcome', 'otp_login', 'otp_forgot', 'password_reset', 'order_account', 'order_source', 'order_manual'];
            foreach ($allTypes as $t) {
                $results[] = sendTemplate($mail, $t, $toEmail);
                usleep(500000); // 0.5s delay between sends
            }
        } else {
            $results[] = sendTemplate($mail, $selectedTemplate, $toEmail);
        }
    }
}

$successCount = count(array_filter($results, fn($r) => $r['ok']));
$failCount = count(array_filter($results, fn($r) => !$r['ok']));
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Mail — KaiShop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 16px;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            font-family: 'Inter', system-ui, sans-serif;
            color: #e2e8f0;
            padding: 20px
        }

        .card {
            background: rgba(255, 255, 255, .06);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 24px;
            padding: 40px 36px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, .45)
        }

        .logo {
            text-align: center;
            margin-bottom: 6px
        }

        .logo h1 {
            font-size: 1.7rem;
            font-weight: 800;
            letter-spacing: -.5px
        }

        .logo h1 span {
            color: #a78bfa
        }

        .subtitle {
            text-align: center;
            font-size: .85rem;
            color: rgba(255, 255, 255, .4);
            margin-bottom: 28px
        }

        .form-group {
            margin-bottom: 18px
        }

        label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: rgba(255, 255, 255, .55);
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: .5px
        }

        input[type="email"],
        select {
            width: 100%;
            padding: 13px 16px;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 13px;
            color: #fff;
            font-size: .95rem;
            font-family: inherit;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
            appearance: none;
            -webkit-appearance: none
        }

        input::placeholder {
            color: rgba(255, 255, 255, .25)
        }

        input:focus,
        select:focus {
            border-color: #a78bfa;
            box-shadow: 0 0 0 3px rgba(167, 139, 250, .2)
        }

        select {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23999' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 36px
        }

        select option {
            background: #1e1b4b;
            color: #e2e8f0;
            padding: 8px
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: transform .15s, box-shadow .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(167, 139, 250, .35)
        }

        .btn:active {
            transform: translateY(0)
        }

        .btn-primary {
            background: linear-gradient(135deg, #a78bfa, #7c3aed);
            color: #fff
        }

        .btn:disabled {
            opacity: .6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none
        }

        /* Results */
        .results {
            margin-top: 20px
        }

        .results h3 {
            font-size: .9rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: rgba(255, 255, 255, .7)
        }

        .result-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 11px;
            margin-bottom: 8px;
            font-size: .88rem;
            transition: background .2s
        }

        .result-item.ok {
            background: rgba(52, 211, 153, .1);
            border: 1px solid rgba(52, 211, 153, .2);
            color: #a7f3d0
        }

        .result-item.fail {
            background: rgba(248, 113, 113, .1);
            border: 1px solid rgba(248, 113, 113, .2);
            color: #fca5a5
        }

        .result-item .icon {
            font-size: 1.1rem;
            flex-shrink: 0
        }

        .result-item .label {
            flex: 1;
            font-weight: 500
        }

        .result-item .detail {
            font-size: .75rem;
            color: rgba(255, 255, 255, .35);
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .summary {
            display: flex;
            gap: 12px;
            margin-bottom: 16px
        }

        .summary-badge {
            flex: 1;
            padding: 10px;
            border-radius: 11px;
            text-align: center;
            font-weight: 700;
            font-size: .9rem
        }

        .summary-badge.ok {
            background: rgba(52, 211, 153, .12);
            border: 1px solid rgba(52, 211, 153, .25);
            color: #6ee7b7
        }

        .summary-badge.fail {
            background: rgba(248, 113, 113, .12);
            border: 1px solid rgba(248, 113, 113, .25);
            color: #fca5a5
        }

        .summary-badge small {
            display: block;
            font-size: .7rem;
            font-weight: 400;
            color: rgba(255, 255, 255, .4);
            margin-top: 2px
        }

        .error-box {
            margin-top: 16px;
            padding: 14px 18px;
            border-radius: 13px;
            background: rgba(248, 113, 113, .1);
            border: 1px solid rgba(248, 113, 113, .25);
            color: #fca5a5;
            font-size: .88rem
        }

        .smtp-info {
            margin-top: 20px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, .03);
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 13px;
            font-size: .75rem;
            color: rgba(255, 255, 255, .35)
        }

        .smtp-info .row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0
        }

        .smtp-info .row span:last-child {
            color: rgba(255, 255, 255, .55);
            font-family: monospace
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: rgba(255, 255, 255, .3);
            font-size: .8rem;
            text-decoration: none;
            transition: color .2s
        }

        .back-link:hover {
            color: #a78bfa
        }

        @keyframes spin {
            to {
                transform: rotate(360deg)
            }
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, .3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: inline-block
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="logo">
            <h1>📧 Kai<span>Mail</span> Test</h1>
        </div>
        <p class="subtitle">Test tất cả email templates qua MailService SMTP</p>

        <form method="POST" id="mailForm">
            <div class="form-group">
                <label for="to_email">Gmail nhận</label>
                <input type="email" id="to_email" name="to_email" placeholder="example@gmail.com"
                    value="<?= htmlspecialchars($sentTo) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="template">Loại email</label>
                <select id="template" name="template">
                    <?php foreach ($templates as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $selectedTemplate === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" id="sendBtn">
                <span id="btnText">🚀 Gửi Test Email</span>
                <span id="btnSpinner" style="display:none"><span class="spinner"></span></span>
            </button>
        </form>

        <?php if ($error): ?>
            <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
            <div class="results">
                <?php if (count($results) > 1): ?>
                    <div class="summary">
                        <div class="summary-badge ok"><?= $successCount ?><small>Thành công</small></div>
                        <div class="summary-badge fail"><?= $failCount ?><small>Thất bại</small></div>
                    </div>
                <?php endif; ?>

                <h3>📋 Kết quả gửi đến <?= htmlspecialchars($sentTo) ?></h3>
                <?php foreach ($results as $r): ?>
                    <div class="result-item <?= $r['ok'] ? 'ok' : 'fail' ?>">
                        <span class="icon"><?= $r['ok'] ? '✅' : '❌' ?></span>
                        <span class="label"><?= htmlspecialchars($r['label']) ?></span>
                        <?php if (!$r['ok'] && $r['error']): ?>
                            <span class="detail"
                                title="<?= htmlspecialchars($r['error']) ?>"><?= htmlspecialchars($r['error']) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="smtp-info">
            <div class="row"><span>SMTP
                    Host</span><span><?= htmlspecialchars(EnvHelper::get('SMTP_HOST', '—')) ?></span></div>
            <div class="row"><span>SMTP
                    Port</span><span><?= htmlspecialchars(EnvHelper::get('SMTP_PORT', '—')) ?></span></div>
            <div class="row">
                <span>From</span><span><?= htmlspecialchars(EnvHelper::get('SMTP_FROM_EMAIL', EnvHelper::get('SMTP_USER', '—'))) ?></span>
            </div>
        </div>

        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/" class="back-link">← Về trang chủ</a>
    </div>

    <script>
        document.getElementById('mailForm').addEventListener('submit', function () {
            const btn = document.getElementById('sendBtn');
            btn.disabled = true;
            document.getElementById('btnText').textContent = 'Đang gửi...';
            document.getElementById('btnSpinner').style.display = 'inline-flex';
        });

        // Update button text when "all" is selected
        document.getElementById('template').addEventListener('change', function () {
            const btnText = document.getElementById('btnText');
            btnText.textContent = this.value === 'all' ? '🚀 Gửi TẤT CẢ (8 emails)' : '🚀 Gửi Test Email';
        });
        // Init
        if (document.getElementById('template').value === 'all') {
            document.getElementById('btnText').textContent = '🚀 Gửi TẤT CẢ (8 emails)';
        }
    </script>
</body>

</html>
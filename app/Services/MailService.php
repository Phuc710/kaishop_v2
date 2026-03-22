<?php

/**
 * MailService
 *
 * Transactional email service using PHPMailer SMTP.
 * Templates:
 * - Welcome register
 * - OTP verify
 * - Password reset
 * - Order success confirmation
 */
class MailService
{
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $smtpTimeout;
    private $fromEmail;
    private $fromName;
    private $siteUrl;
    private $siteName;

    public function __construct()
    {
        $cfg = $this->loadConfig();

        $this->smtpHost = $cfg['smtp_host'];
        $this->smtpPort = (int) $cfg['smtp_port'];
        $this->smtpUser = $cfg['smtp_user'];
        $this->smtpPass = $cfg['smtp_pass'];
        $this->smtpTimeout = (int) $cfg['smtp_timeout'];
        $this->fromEmail = $cfg['from_email'];
        $this->fromName = $cfg['from_name'];
        $this->siteUrl = $cfg['site_url'];
        $this->siteName = $cfg['site_name'];
    }

    public function sendWelcomeRegister($user)
    {
        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '') {
            return false;
        }

        $username = $this->resolveRecipientName($user);
        $subject = '[🎉 Welcome] Tài khoản của bạn đã sẵn sàng!';
        $body = $this->buildLayout(
            $subject,
            '🎉 Chào mừng bạn đến với ' . $this->siteName . '!',
            $this->tplWelcomeRegister($username)
        );

        return $this->send($email, $username, $subject, $body);
    }

    public function sendPasswordReset($user, $otpcode)
    {
        $email = trim((string) ($user['email'] ?? ''));
        $recipientName = $this->resolveRecipientName($user);
        $otpcode = trim($otpcode);

        if ($email === '' || $otpcode === '') {
            return false;
        }

        $resetUrl = rtrim($this->siteUrl, '/') . '/password-reset/' . rawurlencode($otpcode);
        $subject = '[🔒 Mật khẩu] Khôi phục mật khẩu';
        $body = $this->buildLayout(
            $subject,
            'Yêu cầu đặt lại mật khẩu',
            $this->tplPasswordReset($recipientName, $resetUrl)
        );

        return $this->send($email, $recipientName, $subject, $body);
    }

    public function sendOtp(
        $email,
        $username,
        $otpCode,
        $purpose = 'login_2fa',
        $ttlSeconds = 300
    ) {
        $email = trim($email);
        if ($email === '') {
            return;
        }

        $minutes = max(1, (int) ceil($ttlSeconds / 60));
        $purposeText = $purpose === 'forgot_password' ? 'xác minh quên mật khẩu' : 'xác minh đăng nhập';
        $subject = '[🔐 OTP] Mã xác minh của bạn';
        $body = $this->buildLayout(
            $subject,
            'Mã xác minh OTP',
            $this->tplOtp($username, $otpCode, $purposeText, $minutes)
        );

        $this->send($email, $username, $subject, $body);
    }

    /**
     * Send order mail by product delivery mode:
     * - account_stock (Tai khoan)
     * - source_link (Source)
     * - manual_info (Yeu cau)
     *
     * @param array<string,mixed> $user
     * @param array<string,mixed> $order
     * @param array<string,mixed> $product
     */
    public function sendOrderSuccess($user, $order, $product = [])
    {
        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '') {
            return false;
        }

        $recipientName = $this->resolveRecipientName($user);
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $totalPrice = (int) ($order['total_price'] ?? $order['price'] ?? 0);
        $unitPrice = (int) ($order['unit_price'] ?? ($quantity > 0 ? (int) floor($totalPrice / $quantity) : $totalPrice));
        $orderCode = trim((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''));
        $orderedAt = trim((string) ($order['ordered_at'] ?? $order['created_at'] ?? date('Y-m-d H:i:s')));
        $productName = trim((string) ($order['product_name'] ?? $product['name'] ?? 'Sản phẩm'));
        $statusRaw = strtolower(trim((string) ($order['status'] ?? 'completed')));
        switch ($statusRaw) {
            case 'pending':
            case 'processing':
                $statusLabel = 'ĐANG XỬ LÝ';
                break;
            case 'cancelled':
            case 'canceled':
            case 'failed':
                $statusLabel = 'ĐÃ HỦY';
                break;
            case 'completed':
            default:
                $statusLabel = 'HOÀN TẤT';
                break;
        }
        $deliveryMode = $this->resolveDeliveryMode($order, $product);

        $baseData = [
            'username' => $recipientName,
            'order_code' => $orderCode,
            'ordered_at' => $orderedAt,
            'status' => $statusLabel,
            'product_name' => $productName,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'product_image' => $this->normalizeAssetUrl((string) ($product['image'] ?? $order['product_image'] ?? '')),
            'customer_input' => (string) ($order['customer_input'] ?? ''),
            'delivery_content' => (string) ($order['delivery_content'] ?? $order['content'] ?? ''),
            'source_link' => (string) ($order['source_link'] ?? $product['source_link'] ?? ''),
            'info_instructions' => (string) ($order['info_instructions'] ?? $product['info_instructions'] ?? ''),
        ];

        $subject = "[📦 Đơn hàng] #{$orderCode} đã hoàn tất";
        $headline = "Đơn hàng #{$orderCode} đã hoàn tất 🎉";

        if ($deliveryMode === 'source_link') {
            $content = $this->tplOrderSource($baseData);
        } elseif ($deliveryMode === 'manual_info') {
            $content = $this->tplOrderManual($baseData, $statusRaw);
        } else {
            $content = $this->tplOrderAccount($baseData);
        }

        $body = $this->buildLayout($subject, $headline, $content);
        return $this->send($email, $recipientName, $subject, $body);
    }

    public function buildLayout($subject, $headline, $content)
    {
        $safeSubject = $this->e($subject);
        $safeSiteName = $this->e($this->siteName);
        $safeSiteUrl = $this->e($this->siteUrl !== '' ? $this->siteUrl : '#');
        $year = date('Y');
        $currentTime = date('d/m/Y H:i:s');

        return <<<HTML
<!DOCTYPE html>
<html lang="vi" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<meta name="color-scheme" content="light only">
<meta name="supported-color-schemes" content="light only">
<title>{$safeSubject}</title>
<!--[if mso]><style>body,table,td{font-family:Segoe UI,Tahoma,sans-serif!important}</style><![endif]-->
<style>
:root{color-scheme:light only;supported-color-schemes:light only}
body,table,td,div,p,span,a,h1,h2,h3{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
[data-ogsc] body,[data-ogsb] body{background-color:#f0f4f8!important;color:#334155!important}
[data-ogsc] .ks-white-bg,[data-ogsb] .ks-white-bg{background-color:#ffffff!important}
[data-ogsc] .ks-content-text,[data-ogsb] .ks-content-text{color:#334155!important}
[data-ogsc] .ks-label-text,[data-ogsb] .ks-label-text{color:#64748b!important}
[data-ogsc] .ks-value-text,[data-ogsb] .ks-value-text{color:#1e293b!important}
@media only screen and (max-width:620px){
    .ks-main-table{width:100%!important;border-radius:0!important}
    .ks-content-cell{padding:28px 20px!important}
    .ks-footer-cell{padding:16px 20px!important}
    .ks-order-box{padding:18px 16px!important}
}
</style>
</head>
<body style="margin:0;padding:0;background-color:#f0f4f8;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;-webkit-font-smoothing:antialiased;color:#334155;">
<div style="width:100%;table-layout:fixed;background-color:#f0f4f8;padding:40px 10px;">
    <table role="presentation" class="ks-main-table ks-white-bg" style="background-color:#ffffff;margin:0 auto;width:100%;max-width:600px;border-spacing:0;color:#334155;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
        <tr>
            <td style="padding:0;text-align:center;">
                <img src="https://i.imghippo.com/files/vgUu1217Q.jpg" alt="Header" style="width:100%;max-width:600px;display:block;">
            </td>
        </tr>
        <tr>
            <td class="ks-content-cell ks-white-bg ks-content-text" style="padding:36px 32px;line-height:1.7;font-size:15px;color:#334155;background-color:#ffffff;">
                {$content}
            </td>
        </tr>
        <tr>
            <td class="ks-footer-cell" style="padding:20px 32px;text-align:center;font-size:12px;color:#000000;background-color:#f8fafc;border-top:1px solid #f1f5f9;">
                <p style="margin:0 0 4px;">&copy; {$year} <strong>{$safeSiteName}</strong>. All rights reserved.</p>
                <p style="margin:0;color:#000000;">Đây là email tự động, vui lòng không trả lời email này.</p>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
HTML;
    }

    private function tplWelcomeRegister($username)
    {
        $safeUser = $this->e($username !== '' ? $username : 'bạn');
        $safeHome = $this->e($this->siteUrl !== '' ? $this->siteUrl : '#');

        return <<<HTML
<p>👋 Xin chào <strong>{$safeUser}</strong>,</p>
<p>🎉 Tài khoản của bạn đã được tạo thành công tại <strong>{$this->siteName}</strong>.</p>
<p>Chúng tôi rất vui khi được đồng hành cùng bạn!</p>
<p>🚀 Bạn có thể bắt đầu khám phá sản phẩm, nạp tiền và trải nghiệm dịch vụ ngay hôm nay.</p>
<div style="text-align:center;margin-top:24px;">
    <a href="{$safeHome}" style="display:inline-block;padding:13px 32px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff!important;text-decoration:none;border-radius:10px;font-weight:700;font-size:14px;">Bắt đầu ngay tại đây</a>
</div>
HTML;
    }

    private function tplPasswordReset($username, $resetUrl)
    {
        $safeUser = $this->e($username !== '' ? $username : 'bạn');
        $safeReset = $this->e($resetUrl);

        return <<<HTML
<p>👋 Xin chào <strong>{$safeUser}</strong>,</p>
<p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại {$this->siteName}.</p>
<p>Vui lòng nhấn vào nút bên dưới để tiến hành thay đổi mật khẩu:</p>
<div style="text-align:center;margin-top:24px;">
    <a href="{$safeReset}" style="display:inline-block;padding:13px 32px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff!important;text-decoration:none;border-radius:10px;font-weight:700;font-size:14px;">Đặt lại mật khẩu</a>
</div>
<p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
HTML;
    }

    private function tplOtp($username, $otpCode, $purposeText, $minutes)
    {
        $safeUser = $this->e($username !== '' ? $username : 'bạn');
        $safeCode = $this->e(trim($otpCode));
        $safePurpose = $this->e($purposeText);

        return <<<HTML
<p>Xin chào <strong>{$safeUser}</strong>,</p>
<p>Để hoàn tất <strong>{$safePurpose}</strong>, vui lòng sử dụng mã OTP dưới đây:</p>
<div style="text-align:center;">
    <div style="display:inline-block;background-color:#f1f5f9;border:2px dashed #cbd5e1;border-radius:12px;padding:20px 40px;margin:20px 0;">
        <span style="font-size:32px;letter-spacing:8px;font-weight:800;color:#1e293b;font-family:'Courier New',Courier,monospace;">{$safeCode}</span>
    </div>
</div>
<p style="color: #ef4444; font-weight: 600;">Mã này có hiệu lực trong {$minutes} phút.</p>
<p>Vì lý do bảo mật, tuyệt đối không chia sẻ mã này với bất kỳ ai.</p>
HTML;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function tplOrderAccount($data)
    {
        $delivery = trim((string) ($data['delivery_content'] ?? ''));
        $deliveryHtml = $delivery !== ''
            ? $this->renderInfoBox('📦 Tài khoản đã bàn giao', $delivery)
            : '<p style="color: #64748b; font-style: italic;">Chi tiết tài khoản sẽ được hiển thị trong lịch sử đơn hàng của bạn.</p>';

        return $this->tplOrderBase($data, $deliveryHtml);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function tplOrderSource($data)
    {
        $link = trim((string) ($data['delivery_content'] ?? ''));
        if ($link === '') {
            $link = trim((string) ($data['source_link'] ?? ''));
        }

        $linkHtml = '';
        if ($link !== '') {
            $safeLink = $this->e($this->normalizeAssetUrl($link));
            $linkHtml = '<div style="margin:20px 0;padding:18px 20px;border-radius:10px;background-color:#ffffff;border:1px solid #e2e8f0;border-left:4px solid #3b82f6;">'
                . '<strong style="font-size:13px;color:#1e293b;text-transform:uppercase;letter-spacing:0.3px;">🔗 Link tải Source Code:</strong><br>'
                . '<a href="' . $safeLink . '" style="color:#2563eb;word-break:break-all;font-size:13px;">' . $safeLink . '</a>'
                . '</div>';
        }

        return $this->tplOrderBase($data, $linkHtml);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function tplOrderManual($data, $statusRaw)
    {
        $isPending = $statusRaw === 'pending';
        $intro = $isPending
            ? '<p>Đơn yêu cầu của bạn đã được tiếp nhận. Đội ngũ kỹ thuật sẽ xử lý sớm nhất có thể.</p>'
            : '<p>Đơn yêu cầu của bạn đã được Admin hoàn tất và bàn giao kết quả.</p>';

        $customerInput = trim((string) ($data['customer_input'] ?? ''));
        $inputHtml = $customerInput !== ''
            ? $this->renderInfoBox('📝 Thông tin bạn đã gửi', $customerInput)
            : '';

        $instruction = trim((string) ($data['info_instructions'] ?? ''));
        $instructionHtml = ($isPending && $instruction !== '')
            ? $this->renderInfoBox('💡 Hướng dẫn xử lý', $instruction)
            : '';

        $delivery = trim((string) ($data['delivery_content'] ?? ''));
        $deliveryHtml = (!$isPending && $delivery !== '')
            ? $this->renderInfoBox('✅ Nội dung bàn giao', $delivery)
            : '';

        return $this->tplOrderBase($data, $intro . $inputHtml . $instructionHtml . $deliveryHtml);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function tplOrderBase($data, $extraContent)
    {
        $username = $this->e((string) ($data['username'] ?? 'Khách hàng'));
        $orderCode = $this->e((string) ($data['order_code'] ?? ''));
        $historyUrl = $this->e(rtrim($this->siteUrl, '/') . '/order-history');

        return <<<HTML
<p style="font-size:15px;color:#334155;">Xin chào <strong style="color:#1e293b;">{$username}</strong>,</p>
<p style="font-size:15px;color:#334155;">Đơn hàng <strong style="color:#2563eb;">#{$orderCode}</strong> của bạn tại <strong>{$this->siteName}</strong> đã được xử lý thành công 🎉</p>

<div class="ks-order-box ks-white-bg" style="background-color:#ffffff;border-radius:12px;padding:24px 28px;margin:24px 0;border:1px solid #e2e8f0;">
    <h3 style="margin:0 0 18px 0;color:#1e293b;font-size:16px;font-weight:700;text-align:center;border-bottom:2px solid #e2e8f0;padding-bottom:12px;letter-spacing:0.3px;">🧾 Chi tiết đơn hàng</h3>
    {$this->renderOrderSummaryTable($data)}
</div>

{$extraContent}

<div style="text-align:center;margin-top:28px;">
    <a href="{$historyUrl}" style="display:inline-block;padding:13px 32px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff!important;text-decoration:none;border-radius:10px;font-weight:700;font-size:14px;letter-spacing:0.3px;">👉 Xem đơn hàng của bạn</a>
</div>
HTML;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function renderOrderSummaryTable($data)
    {
        $orderCode = $this->e((string) ($data['order_code'] ?? ''));
        $orderedAt = $this->e((string) ($data['ordered_at'] ?? ''));
        $status = $this->e((string) ($data['status'] ?? 'HOÀN TẤT'));
        $productName = $this->e((string) ($data['product_name'] ?? 'Sản phẩm'));
        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        $unitPrice = (int) ($data['unit_price'] ?? 0);
        $totalPrice = (int) ($data['total_price'] ?? 0);
        $productImage = trim((string) ($data['product_image'] ?? ''));

        $productImageHtml = '';
        if ($productImage !== '') {
            $safeProductImage = $this->e($productImage);
            $productImageHtml = '<tr><td colspan="2" style="padding-bottom:16px;text-align:center;">'
                . '<img src="' . $safeProductImage . '" alt="Product" style="max-width: 200px; border-radius: 8px; border: 1px solid #e2e8f0;">'
                . '</td></tr>';
        }

        // Row styling for clean white light UI
        $labelStyle = 'padding:10px 12px;color:#64748b;font-size:13px;font-weight:500;white-space:nowrap;';
        $valueStyle = 'padding:10px 12px;text-align:right;font-size:13px;color:#1e293b;font-weight:600;';
        $rowEven = 'background-color:#ffffff;';
        $rowOdd = 'background-color:#ffffff;';

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#475569;border-radius:8px;overflow:hidden;">'
            . $productImageHtml
            . '<tr style="' . $rowEven . '"><td style="' . $labelStyle . '">Mã đơn hàng</td><td style="' . $valueStyle . 'color:#2563eb;font-weight:800;">#' . $orderCode . '</td></tr>'
            . '<tr style="' . $rowOdd . '"><td style="' . $labelStyle . '">Sản phẩm</td><td style="' . $valueStyle . 'font-weight:700;color:#1e293b;">' . $productName . '</td></tr>'
            . '<tr style="' . $rowEven . '"><td style="' . $labelStyle . '">Số lượng</td><td style="' . $valueStyle . '">' . $quantity . '</td></tr>'
            . '<tr style="' . $rowOdd . '"><td style="' . $labelStyle . '">Tổng tiền</td><td style="' . $valueStyle . 'color:#dc2626;font-weight:800;font-size:15px;">' . $this->e($this->formatMoney($totalPrice)) . '</td></tr>'
            . '<tr style="' . $rowEven . '"><td style="' . $labelStyle . '">Thời gian</td><td style="' . $valueStyle . '">' . $orderedAt . '</td></tr>'
            . '<tr style="' . $rowOdd . '"><td style="' . $labelStyle . '">Trạng thái</td><td style="' . $valueStyle . 'color:#059669;font-weight:800;">' . $status . '</td></tr>'
            . '</table>';
    }

    private function renderInfoBox($title, $content)
    {
        $safeTitle = $this->e($title);
        $safeContent = nl2br($this->e($content));
        return '<div style="margin:20px 0;padding:18px 20px;border-radius:10px;background-color:#ffffff;border:1px solid #e2e8f0;border-left:4px solid #3b82f6;">'
            . '<div style="font-size:13px;color:#1e293b;font-weight:700;margin-bottom:10px;text-transform:uppercase;letter-spacing:0.3px;">' . $safeTitle . '</div>'
            . '<div style="font-size:13px;color:#475569;line-height:1.8;word-break:break-all;font-family:\'Courier New\',Courier,monospace;background:#ffffff;padding:12px 14px;border-radius:8px;border:1px solid #e2e8f0;">' . $safeContent . '</div>'
            . '</div>';
    }

    public function send($toEmail, $toName, $subject, $body)
    {
        $toEmail = trim($toEmail);
        if ($toEmail === '') {
            return false;
        }

        if ($this->smtpUser === '' || $this->smtpPass === '') {
            if (function_exists('sendCSM')) {
                return (bool) sendCSM($toEmail, $toName, $subject, $body, '');
            }
            return false;
        }

        $smtpBase = dirname(__DIR__, 2) . '/hethong/SMTP';
        if (!class_exists('PHPMailer')) {
            require_once $smtpBase . '/PHPMailerAutoload.php';
            require_once $smtpBase . '/class.smtp.php';
            require_once $smtpBase . '/class.phpmailer.php';
        }

        try {
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->SMTPDebug = 0;
            $mail->Host = $this->smtpHost;
            $mail->Port = $this->smtpPort;
            $mail->SMTPAuth = true;
            $mail->SMTPAutoTLS = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->SMTPSecure = $this->smtpPort === 465 ? 'ssl' : 'tls';
            $mail->Timeout = $this->smtpTimeout > 0 ? $this->smtpTimeout : 8;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
            $mail->addReplyTo($this->fromEmail, $this->fromName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $plain = preg_replace('/<br\s*\/?>/i', "\n", $body) ?? $body;
            $plain = preg_replace('/<\/p>/i', "\n\n", $plain) ?? $plain;
            $mail->AltBody = html_entity_decode(strip_tags($plain), ENT_QUOTES, 'UTF-8');

            return $mail->send();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @return array{
     *   smtp_host:string,
     *   smtp_port:int,
     *   smtp_user:string,
     *   smtp_pass:string,
     *   smtp_timeout:int,
     *   from_email:string,
     *   from_name:string,
     *   site_url:string,
     *   site_name:string
     * }
     */
    private function loadConfig()
    {
        $host = class_exists('EnvHelper') ? (string) EnvHelper::get('SMTP_HOST', 'smtp.gmail.com') : 'smtp.gmail.com';
        $port = class_exists('EnvHelper') ? (int) EnvHelper::get('SMTP_PORT', 587) : 587;
        $user = class_exists('EnvHelper') ? (string) EnvHelper::get('SMTP_USER', '') : '';
        $pass = class_exists('EnvHelper') ? (string) EnvHelper::get('SMTP_PASS', '') : '';
        $timeout = class_exists('EnvHelper') ? (int) EnvHelper::get('SMTP_TIMEOUT', 8) : 8;
        $fromMail = class_exists('EnvHelper') ? (string) EnvHelper::get('EMAIL_FROM', $user) : $user;
        $fromName = class_exists('EnvHelper') ? (string) EnvHelper::get('EMAIL_FROM_NAME', 'KaiShop') : 'KaiShop';

        if (function_exists('get_setting')) {
            $host = (string) get_setting('smtp_host', get_setting('smtp', $host));
            $port = (int) get_setting('smtp_port', get_setting('port_smtp', $port));
            $user = (string) get_setting('email_auto', $user);
            $pass = (string) get_setting('pass_mail_auto', $pass);
            $fromName = (string) get_setting('ten_nguoi_gui', $fromName);
            if ($fromMail === '') {
                $fromMail = $user;
            }
        }

        if ($host === '') {
            $host = 'smtp.gmail.com';
        }
        if ($port <= 0) {
            $port = 587;
        }
        if ($timeout <= 0) {
            $timeout = 8;
        }

        $siteName = function_exists('get_setting') ? (string) get_setting('ten_web', 'KaiShop') : 'KaiShop';
        $siteUrl = '';
        if (defined('BASE_URL')) {
            $siteUrl = rtrim((string) BASE_URL, '/');
        } elseif (class_exists('EnvHelper')) {
            $appDir = (string) EnvHelper::get('APP_DIR', '');
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $hostHeader = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $siteUrl = rtrim($scheme . '://' . $hostHeader . $appDir, '/');
        }

        return [
            'smtp_host' => $host,
            'smtp_port' => $port,
            'smtp_user' => $user,
            'smtp_pass' => $pass,
            'smtp_timeout' => $timeout,
            'from_email' => $fromMail !== '' ? $fromMail : $user,
            'from_name' => $fromName !== '' ? $fromName : $siteName,
            'site_url' => $siteUrl,
            'site_name' => $siteName !== '' ? $siteName : 'KaiShop',
        ];
    }

    private function normalizeAssetUrl($value)
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $value) === 1) {
            return $value;
        }
        if ($this->siteUrl === '') {
            return $value;
        }
        return rtrim($this->siteUrl, '/') . '/' . ltrim($value, '/');
    }

    /**
     * @param array<string,mixed> $order
     * @param array<string,mixed> $product
     */
    private function resolveDeliveryMode($order, $product)
    {
        $mode = trim((string) ($order['delivery_mode'] ?? $product['delivery_mode'] ?? ''));
        if ($mode !== '') {
            return $mode;
        }

        $productType = trim((string) ($order['product_type'] ?? $product['product_type'] ?? 'account'));
        $requiresInfo = (int) ($order['requires_info'] ?? $product['requires_info'] ?? 0) === 1;

        if ($productType === 'link') {
            return 'source_link';
        }
        if ($requiresInfo) {
            return 'manual_info';
        }

        return 'account_stock';
    }

    private function formatMoney($amount)
    {
        return number_format($amount, 0, ',', '.') . ' VND';
    }

    private function e($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function resolveRecipientName($user)
    {
        $fullName = trim((string) ($user['full_name'] ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        $username = trim((string) ($user['username'] ?? ''));
        if ($username !== '') {
            return $username;
        }

        $email = trim((string) ($user['email'] ?? ''));
        if ($email !== '' && strpos($email, '@') !== false) {
            $localPart = strstr($email, '@', true);
            if (is_string($localPart) && $localPart !== '') {
                return $localPart;
            }
        }

        return $email;
    }
}

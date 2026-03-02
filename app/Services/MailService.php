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
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUser;
    private string $smtpPass;
    private string $fromEmail;
    private string $fromName;
    private string $siteUrl;
    private string $siteName;

    public function __construct()
    {
        $cfg = $this->loadConfig();

        $this->smtpHost = $cfg['smtp_host'];
        $this->smtpPort = (int) $cfg['smtp_port'];
        $this->smtpUser = $cfg['smtp_user'];
        $this->smtpPass = $cfg['smtp_pass'];
        $this->fromEmail = $cfg['from_email'];
        $this->fromName = $cfg['from_name'];
        $this->siteUrl = $cfg['site_url'];
        $this->siteName = $cfg['site_name'];
    }

    public function sendWelcomeRegister(array $user): bool
    {
        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '') {
            return false;
        }

        $username = trim((string) ($user['username'] ?? ''));
        $subject = '🎉 Welcome to ' . $this->siteName . ' – Tài khoản của bạn đã sẵn sàng!';
        $body = $this->buildLayout(
            $subject,
            '🎉 Chào mừng bạn đến với ' . $this->siteName . '!',
            $this->tplWelcomeRegister($username)
        );

        return $this->send($email, $username, $subject, $body);
    }

    public function sendPasswordReset(array $user, string $otpcode): bool
    {
        $email = trim((string) ($user['email'] ?? ''));
        $username = trim((string) ($user['username'] ?? ''));
        $otpcode = trim($otpcode);

        if ($email === '' || $otpcode === '') {
            return false;
        }

        $resetUrl = rtrim($this->siteUrl, '/') . '/password-reset/' . rawurlencode($otpcode);
        $subject = 'Khôi phục mật khẩu - ' . $this->siteName;
        $body = $this->buildLayout(
            $subject,
            'Yêu cầu đặt lại mật khẩu',
            $this->tplPasswordReset($username, $resetUrl)
        );

        return $this->send($email, $username, $subject, $body);
    }

    public function sendOtp(
        string $email,
        string $username,
        string $otpCode,
        string $purpose = 'login_2fa',
        int $ttlSeconds = 300
    ): void {
        $email = trim($email);
        if ($email === '') {
            return;
        }

        $minutes = max(1, (int) ceil($ttlSeconds / 60));
        $purposeText = $purpose === 'forgot_password' ? 'xác minh quên mật khẩu' : 'xác minh đăng nhập';
        $subject = 'Mã OTP của bạn - ' . $this->siteName;
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
    public function sendOrderSuccess(array $user, array $order, array $product = []): bool
    {
        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '') {
            return false;
        }

        $username = trim((string) ($user['username'] ?? ''));
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $totalPrice = (int) ($order['total_price'] ?? $order['price'] ?? 0);
        $unitPrice = (int) ($order['unit_price'] ?? ($quantity > 0 ? (int) floor($totalPrice / $quantity) : $totalPrice));
        $orderCode = trim((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''));
        $orderedAt = trim((string) ($order['ordered_at'] ?? $order['created_at'] ?? date('Y-m-d H:i:s')));
        $productName = trim((string) ($order['product_name'] ?? $product['name'] ?? 'Sản phẩm'));
        $statusRaw = strtolower(trim((string) ($order['status'] ?? 'completed')));
        $statusLabel = $statusRaw === 'pending' ? 'ĐANG CHỜ' : ($statusRaw === 'processing' ? 'ĐANG XỬ LÝ' : 'HOÀN TẤT');
        $deliveryMode = $this->resolveDeliveryMode($order, $product);

        $baseData = [
            'username' => $username,
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

        $subject = "📦 Đơn hàng #{$orderCode} đã được giao thành công";
        $headline = "Đơn hàng #{$orderCode} đã hoàn tất 🎉";

        if ($deliveryMode === 'source_link') {
            $content = $this->tplOrderSource($baseData);
        } elseif ($deliveryMode === 'manual_info') {
            $content = $this->tplOrderManual($baseData, $statusRaw);
        } else {
            $content = $this->tplOrderAccount($baseData);
        }

        $body = $this->buildLayout($subject, $headline, $content);
        return $this->send($email, $username, $subject, $body);
    }

    public function buildLayout(string $subject, string $headline, string $content): string
    {
        $safeSubject = $this->e($subject);
        $safeHeadline = $this->e($headline);
        $safeSiteName = $this->e($this->siteName);
        $safeSiteUrl = $this->e($this->siteUrl !== '' ? $this->siteUrl : '#');
        $year = date('Y');
        $currentTime = date('d/m/Y H:i:s');

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{$safeSubject}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f6f9fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f6f9fc; padding-bottom: 40px; }
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-spacing: 0; color: #4a4a4a; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 40px; }
        .header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 30px; text-align: center; color: #ffffff; }
        .content { padding: 40px 30px; line-height: 1.6; }
        .footer { padding: 20px; text-align: center; font-size: 13px; color: #94a3b8; background-color: #f8fafc; }
        .button { display: inline-block; padding: 12px 24px; background-color: #2563eb; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 20px; transition: background-color 0.3s; }
        .info-box { background-color: #f1f5f9; border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #2563eb; }
        h1 { margin: 0; font-size: 24px; font-weight: 700; color: #ffffff; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <table class="main" role="presentation">
            <tr>
                <td class="header">
                    <h1>{$safeHeadline}</h1>
                </td>
            </tr>
            <tr>
                <td class="content">
                    {$content}
                    <p style="margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 14px; color: #64748b;">
                        Nếu cần hỗ trợ, đội ngũ <strong>{$safeSiteName}</strong> luôn sẵn sàng giúp bạn.<br>
                        Thời gian gửi: {$currentTime}
                    </p>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    <p>&copy; {$year} <strong>{$safeSiteName}</strong>. All rights reserved.</p>
                    <p>Đây là email tự động, vui lòng không trả lời email này.</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
HTML;
    }

    private function tplWelcomeRegister(string $username): string
    {
        $safeUser = $this->e($username !== '' ? $username : 'bạn');
        $safeHome = $this->e($this->siteUrl !== '' ? $this->siteUrl : '#');

        return <<<HTML
<p>Xin chào <strong>{$safeUser}</strong>,</p>
<p>Tài khoản của bạn đã được tạo thành công tại <strong>{$this->siteName}</strong>.</p>
<p>Chúng tôi rất vui khi được đồng hành cùng bạn!</p>
<p>🚀 Bạn có thể bắt đầu khám phá sản phẩm, nạp tiền và trải nghiệm dịch vụ ngay hôm nay.</p>
<div class="text-center">
    <a href="{$safeHome}" class="button">Bắt đầu ngay tại đây</a>
</div>
<p style="margin-top: 20px;">Trân trọng,<br>Đội ngũ {$this->siteName}</p>
HTML;
    }

    private function tplPasswordReset(string $username, string $resetUrl): string
    {
        $safeUser = $this->e($username !== '' ? $username : 'bạn');
        $safeReset = $this->e($resetUrl);

        return <<<HTML
<p>Xin chào <strong>{$safeUser}</strong>,</p>
<p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại {$this->siteName}.</p>
<p>Vui lòng nhấn vào nút bên dưới để tiến hành thay đổi mật khẩu:</p>
<div class="text-center">
    <a href="{$safeReset}" class="button">Đặt lại mật khẩu</a>
</div>
<p style="margin-top: 20px; color: #64748b; font-size: 13px;">Nếu nút trên không hoạt động, bạn có thể sao chép và dán liên kết sau vào trình duyệt:</p>
<p style="word-break: break-all; font-size: 12px; color: #2563eb;">{$safeReset}</p>
<p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
HTML;
    }

    private function tplOtp(string $username, string $otpCode, string $purposeText, int $minutes): string
    {
        $safeUser = $this->e($username !== '' ? $username : 'bạn');
        $safeCode = $this->e(trim($otpCode));
        $safePurpose = $this->e($purposeText);

        return <<<HTML
<p>Xin chào <strong>{$safeUser}</strong>,</p>
<p>Để hoàn tất <strong>{$safePurpose}</strong>, vui lòng sử dụng mã OTP dưới đây:</p>
<div class="text-center">
    <div style="display: inline-block; background-color: #f1f5f9; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px 40px; margin: 20px 0;">
        <span style="font-size: 32px; letter-spacing: 8px; font-weight: 800; color: #1e293b; font-family: 'Courier New', Courier, monospace;">{$safeCode}</span>
    </div>
</div>
<p style="color: #ef4444; font-weight: 600;">Mã này có hiệu lực trong {$minutes} phút.</p>
<p>Vì lý do bảo mật, tuyệt đối không chia sẻ mã này với bất kỳ ai.</p>
HTML;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function tplOrderAccount(array $data): string
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
    private function tplOrderSource(array $data): string
    {
        $link = trim((string) ($data['delivery_content'] ?? ''));
        if ($link === '') {
            $link = trim((string) ($data['source_link'] ?? ''));
        }

        $linkHtml = '';
        if ($link !== '') {
            $safeLink = $this->e($this->normalizeAssetUrl($link));
            $linkHtml = '<div class="info-box">'
                . '<strong>🔗 Link tải Source Code:</strong><br>'
                . '<a href="' . $safeLink . '" style="color:#2563eb;word-break:break-all;">' . $safeLink . '</a>'
                . '</div>';
        }

        return $this->tplOrderBase($data, $linkHtml);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function tplOrderManual(array $data, string $statusRaw): string
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
    private function tplOrderBase(array $data, string $extraContent): string
    {
        $username = $this->e((string) ($data['username'] ?? 'Khách hàng'));
        $orderCode = $this->e((string) ($data['order_code'] ?? ''));
        $historyUrl = $this->e(rtrim($this->siteUrl, '/') . '/order-history');

        return <<<HTML
<p>Xin chào <strong>{$username}</strong>,</p>
<p>Đơn hàng <strong>#{$orderCode}</strong> của bạn tại KaiShop đã được xử lý và giao thành công 🎉</p>
<div style="background-color: #f8fafc; border-radius: 8px; padding: 25px; margin: 20px 0;">
    <h3 style="margin: 0 0 15px 0; color: #1e293b; font-size: 18px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">🧾 Chi tiết đơn hàng</h3>
    {$this->renderOrderSummaryTable($data)}
</div>
{$extraContent}
<div class="text-center">
    <a href="{$historyUrl}" class="button">👉 Xem đơn hàng của bạn</a>
</div>
HTML;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function renderOrderSummaryTable(array $data): string
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
            $productImageHtml = '<tr><td colspan="2" style="padding-bottom: 20px; text-align: center;">'
                . '<img src="' . $safeProductImage . '" alt="Product" style="max-width: 200px; border-radius: 8px; border: 1px solid #e2e8f0;">'
                . '</td></tr>';
        }

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #475569;">'
            . $productImageHtml
            . '<tr><td style="padding: 8px 0; color: #1e293b; font-weight: 600;">Mã đơn hàng:</td><td style="padding: 8px 0; text-align: right; color: #2563eb; font-weight: 700;">#' . $orderCode . '</td></tr>'
            . '<tr><td style="padding: 8px 0; color: #1e293b; font-weight: 600;">Sản phẩm:</td><td style="padding: 8px 0; text-align: right; font-weight: 600;">' . $productName . '</td></tr>'
            . '<tr><td style="padding: 8px 0; color: #1e293b; font-weight: 600;">Số lượng:</td><td style="padding: 8px 0; text-align: right;">' . $quantity . '</td></tr>'
            . '<tr><td style="padding: 8px 0; color: #1e293b; font-weight: 600;">Tổng tiền:</td><td style="padding: 8px 0; text-align: right; color: #ef4444; font-weight: 700; font-size: 16px;">' . $this->e($this->formatMoney($totalPrice)) . '</td></tr>'
            . '<tr><td style="padding: 8px 0; color: #1e293b; font-weight: 600;">Thời gian giao:</td><td style="padding: 8px 0; text-align: right;">' . $orderedAt . '</td></tr>'
            . '<tr><td style="padding: 8px 0; color: #1e293b; font-weight: 600;">Trạng thái:</td><td style="padding: 8px 0; text-align: right; color: #059669; font-weight: 700;">' . $status . '</td></tr>'
            . '</table>';
    }

    private function renderInfoBox(string $title, string $content): string
    {
        $safeTitle = $this->e($title);
        $safeContent = nl2br($this->e($content));
        return '<div style="margin: 20px 0; padding: 15px; border-radius: 8px; background-color: #f1f5f9; border-left: 4px solid #3b82f6;">'
            . '<div style="font-size: 14px; color: #1e293b; font-weight: 700; margin-bottom: 8px;">' . $safeTitle . '</div>'
            . '<div style="font-size: 13px; color: #475569; line-height: 1.7; word-break: break-all; font-family: monospace;">' . $safeContent . '</div>'
            . '</div>';
    }

    public function send(string $toEmail, string $toName, string $subject, string $body): bool
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
            $mail->Timeout = 20;
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
     *   from_email:string,
     *   from_name:string,
     *   site_url:string,
     *   site_name:string
     * }
     */
    private function loadConfig(): array
    {
        $host = class_exists('EnvHelper') ? (string) EnvHelper::get('SMTP_HOST', 'smtp.gmail.com') : 'smtp.gmail.com';
        $port = class_exists('EnvHelper') ? (int) EnvHelper::get('SMTP_PORT', 587) : 587;
        $user = class_exists('EnvHelper') ? (string) EnvHelper::get('SMTP_USER', '') : '';
        $pass = class_exists('EnvHelper') ? (string) EnvHelper::get('SMTP_PASS', '') : '';
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
            'from_email' => $fromMail !== '' ? $fromMail : $user,
            'from_name' => $fromName !== '' ? $fromName : $siteName,
            'site_url' => $siteUrl,
            'site_name' => $siteName !== '' ? $siteName : 'KaiShop',
        ];
    }

    private function normalizeAssetUrl(string $value): string
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
    private function resolveDeliveryMode(array $order, array $product): string
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

    private function formatMoney(int $amount): string
    {
        return number_format($amount, 0, ',', '.') . ' VND';
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

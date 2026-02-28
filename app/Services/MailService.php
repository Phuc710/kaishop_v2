<?php

/**
 * MailService
 * 
 * Clean OOP email service. Single file, all templates.
 * Sends transactional emails via PHPMailer (SMTP).
 * 
 * Templates:
 *  - sendWelcomeRegister()  → Email chào mừng đăng ký
 *  - sendPasswordReset()    → Email đặt lại mật khẩu
 *  - sendOtp()              → Mã OTP xác minh (2FA / forgot password)
 */
class MailService
{
    // ─── SMTP Config ───────────────────────────────────────
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUser;
    private string $smtpPass;
    private string $fromEmail;
    private string $fromName;
    private string $siteUrl;
    private string $siteName;
    private string $siteLogo;

    public function __construct()
    {
        // Read from DB setting (chungapi) first, fallback to .env
        $cfg = $this->loadConfig();

        $this->smtpHost = $cfg['smtp_host'];
        $this->smtpPort = (int) $cfg['smtp_port'];
        $this->smtpUser = $cfg['smtp_user'];
        $this->smtpPass = $cfg['smtp_pass'];
        $this->fromEmail = $cfg['from_email'];
        $this->fromName = $cfg['from_name'];
        $this->siteUrl = $cfg['site_url'];
        $this->siteName = $cfg['site_name'];
        $this->siteLogo = $cfg['site_logo'];
    }

    // ═══════════════════════════════════════════════════════
    // PUBLIC METHODS — Email Templates
    // ═══════════════════════════════════════════════════════

    /**
     * Gửi email chào mừng khi đăng ký tài khoản mới.
     *
     * @param  array $user  User row từ DB (username, email)
     * @return bool
     */
    public function sendWelcomeRegister(array $user): bool
    {
        $email = (string) ($user['email'] ?? '');
        $username = (string) ($user['username'] ?? '');

        if ($email === '') {
            return false;
        }

        $subject = "Chào mừng bạn đến với {$this->siteName}!";
        $body = $this->buildLayout(
            subject: $subject,
            headline: "🎉 Chào mừng bạn!",
            content: $this->tplWelcomeRegister($username),
        );

        return $this->send(
            toEmail: $email,
            toName: $username,
            subject: $subject,
            body: $body,
        );
    }

    /**
     * Gửi email đặt lại mật khẩu (forgot password).
     *
     * @param  array  $user     User row từ DB (username, email)
     * @param  string $otpcode  Token reset password
     * @return bool
     */
    public function sendPasswordReset(array $user, string $otpcode): bool
    {
        $email = (string) ($user['email'] ?? '');
        $username = (string) ($user['username'] ?? '');

        if ($email === '' || $otpcode === '') {
            return false;
        }

        $resetUrl = rtrim($this->siteUrl, '/') . '/password-reset/' . rawurlencode($otpcode);
        $subject = "Yêu cầu đặt lại mật khẩu – {$this->siteName}";
        $body = $this->buildLayout(
            subject: $subject,
            headline: "🔐 Đặt lại mật khẩu",
            content: $this->tplPasswordReset($username, $resetUrl),
        );

        return $this->send(
            toEmail: $email,
            toName: $username,
            subject: $subject,
            body: $body,
        );
    }

    /**
     * Gửi mã OTP xác minh (2FA / forgot password).
     *
     * @param  string $email
     * @param  string $username
     * @param  string $otpCode
     * @param  string $purpose    'login_2fa' | 'forgot_password'
     * @param  int    $ttlSeconds
     * @return void
     */
    public function sendOtp(
        string $email,
        string $username,
        string $otpCode,
        string $purpose = 'login_2fa',
        int $ttlSeconds = 300
    ): void {
        if ($email === '') {
            return;
        }

        $minutes = max(1, (int) ceil($ttlSeconds / 60));
        $purposeText = $purpose === 'forgot_password'
            ? 'xác minh quên mật khẩu'
            : 'xác minh đăng nhập';

        $subject = "Mã xác minh của bạn – {$this->siteName}";
        $body = $this->buildLayout(
            subject: $subject,
            headline: "🔑 Mã xác minh",
            content: $this->tplOtp($username, $otpCode, $purposeText, $minutes),
        );

        $this->send(
            toEmail: $email,
            toName: $username,
            subject: $subject,
            body: $body,
        );
    }

    // ═══════════════════════════════════════════════════════
    // PRIVATE — HTML Templates
    // ═══════════════════════════════════════════════════════

    private function tplWelcomeRegister(string $username): string
    {
        $esc = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $home = $esc($this->siteUrl);
        $name = $esc($this->siteName);
        $user = $esc($username);

        return <<<HTML
        <p style="margin:0 0 16px;font-size:16px;color:#374151;">Xin chào <strong>{$user}</strong>,</p>

        <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.7;">
            Tài khoản của bạn tại <strong>{$name}</strong> đã được tạo thành công.
            Chúng tôi rất vui khi có bạn đồng hành!
        </p>

        <p style="margin:0 0 20px;font-size:15px;color:#374151;line-height:1.7;">
            Hãy bắt đầu khám phá các sản phẩm và dịch vụ của chúng tôi ngay bây giờ.
        </p>

        <div style="text-align:center;margin:28px 0;">
            <a href="{$home}"
               style="display:inline-block;background:linear-gradient(135deg,#6366f1,#8b5cf6);
                      color:#fff;text-decoration:none;font-size:15px;font-weight:600;
                      padding:14px 36px;border-radius:8px;letter-spacing:.3px;">
                🛍️ Khám phá ngay
            </a>
        </div>

        <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">

        <p style="margin:0;font-size:13px;color:#9ca3af;line-height:1.6;">
            Nếu bạn không thực hiện hành động này, vui lòng bỏ qua email này.<br>
            Email này được gửi tự động, vui lòng không trả lời.
        </p>
        HTML;
    }

    private function tplPasswordReset(string $username, string $resetUrl): string
    {
        $esc = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $user = $esc($username);
        $safeUrl = $esc($resetUrl);

        return <<<HTML
        <p style="margin:0 0 16px;font-size:16px;color:#374151;">Xin chào <strong>{$user}</strong>,</p>

        <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.7;">
            Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.
            Vui lòng nhấp vào nút bên dưới để tiếp tục.
        </p>

        <div style="text-align:center;margin:28px 0;">
            <a href="{$safeUrl}"
               style="display:inline-block;background:linear-gradient(135deg,#ef4444,#dc2626);
                      color:#fff;text-decoration:none;font-size:15px;font-weight:600;
                      padding:14px 36px;border-radius:8px;letter-spacing:.3px;">
                🔐 Đặt lại mật khẩu
            </a>
        </div>

        <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">Hoặc dán liên kết này vào trình duyệt:</p>
        <p style="margin:0 0 24px;font-size:12px;word-break:break-all;">
            <a href="{$safeUrl}" style="color:#6366f1;">{$safeUrl}</a>
        </p>

        <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">

        <p style="margin:0;font-size:13px;color:#9ca3af;line-height:1.6;">
            Liên kết này chỉ sử dụng <strong>một lần</strong> và sẽ hết hiệu lực sau một thời gian.<br>
            Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.<br>
            Email này được gửi tự động, vui lòng không trả lời.
        </p>
        HTML;
    }

    private function tplOtp(
        string $username,
        string $otpCode,
        string $purposeText,
        int $minutes
    ): string {
        $esc = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $user = $esc($username);
        $code = $esc($otpCode);
        $purposeEsc = $esc($purposeText);

        return <<<HTML
        <p style="margin:0 0 16px;font-size:16px;color:#374151;">Xin chào <strong>{$user}</strong>,</p>

        <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.7;">
            Đây là mã xác minh dùng để <strong>{$purposeEsc}</strong>:
        </p>

        <div style="text-align:center;margin:24px 0;">
            <div style="display:inline-block;background:#f3f4f6;border:2px dashed #d1d5db;
                        border-radius:12px;padding:18px 40px;">
                <span style="font-size:36px;font-weight:800;letter-spacing:10px;
                             color:#111827;font-family:'Courier New',monospace;">{$code}</span>
            </div>
        </div>

        <p style="text-align:center;margin:0 0 24px;font-size:14px;color:#6b7280;">
            ⏱️ Mã có hiệu lực trong <strong>{$minutes} phút</strong>.
        </p>

        <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">

        <p style="margin:0;font-size:13px;color:#9ca3af;line-height:1.6;">
            Không chia sẻ mã này với bất kỳ ai.<br>
            Nếu không phải bạn thực hiện, hãy đổi mật khẩu ngay lập tức.<br>
            Email này được gửi tự động, vui lòng không trả lời.
        </p>
        HTML;
    }

    // ═══════════════════════════════════════════════════════
    // PRIVATE — Shared HTML Layout (SEO email shell)
    // ═══════════════════════════════════════════════════════

    /**
     * Bao bọc nội dung vào layout HTML email chuẩn, center, responsive.
     */
    public function buildLayout(
        string $subject,
        string $headline,
        string $content,
    ): string {
        $esc = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $year = date('Y');
        $siteName = $esc($this->siteName);
        $siteUrl = $esc($this->siteUrl);
        $subj = $esc($subject);
        $logo = $esc($this->siteLogo);

        $logoHtml = $logo !== ''
            ? "<img src=\"{$logo}\" alt=\"{$siteName}\" style=\"max-height:44px;max-width:160px;\" />"
            : "<span style=\"font-size:22px;font-weight:800;color:#6366f1;\">{$siteName}</span>";

        return <<<HTML
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width,initial-scale=1.0">
            <meta name="robots" content="noindex,nofollow">
            <title>{$subj}</title>
        </head>
        <body style="margin:0;padding:0;background-color:#f6f7fb;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background:#f6f7fb;padding:40px 16px;">
                <tr>
                    <td align="center">
                        <!-- Email Container -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                               style="max-width:560px;width:100%;">

                            <!-- Header / Logo -->
                            <tr>
                                <td align="center" style="padding-bottom:24px;">
                                    <a href="{$siteUrl}" style="text-decoration:none;">
                                        {$logoHtml}
                                    </a>
                                </td>
                            </tr>

                            <!-- Card -->
                            <tr>
                                <td style="background:#ffffff;border-radius:16px;padding:36px 40px;
                                           box-shadow:0 2px 12px rgba(0,0,0,.07);">

                                    <!-- Headline -->
                                    <h1 style="margin:0 0 20px;font-size:22px;font-weight:700;
                                               color:#111827;line-height:1.3;">{$headline}</h1>

                                    <!-- Dynamic Content -->
                                    {$content}

                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td align="center" style="padding:24px 0 0;">
                                    <p style="margin:0;font-size:12px;color:#9ca3af;">
                                        © {$year} <a href="{$siteUrl}" style="color:#6366f1;text-decoration:none;">{$siteName}</a>.
                                        Mọi quyền được bảo lưu.
                                    </p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        HTML;
    }

    // ═══════════════════════════════════════════════════════
    // PRIVATE — Send via PHPMailer
    // ═══════════════════════════════════════════════════════

    /**
     * Core send method: wraps PHPMailer SMTP.
     */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $body,
    ): bool {
        if ($this->smtpUser === '' || $this->smtpPass === '') {
            // Fallback to legacy sendCSM() if PHPMailer creds not ready
            if (function_exists('sendCSM')) {
                return (bool) sendCSM($toEmail, $toName, $subject, $body, '');
            }
            return false;
        }

        // Ensure PHPMailer is loaded
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
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->SMTPSecure = $this->smtpPort === 465 ? 'ssl' : 'tls';
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->addReplyTo($this->fromEmail, $this->fromName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>', '</div>'], "\n", $body));

            return $mail->send();
        } catch (Throwable $e) {
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════
    // PRIVATE — Config Loader
    // ═══════════════════════════════════════════════════════

    private function loadConfig(): array
    {
        // Defaults from .env
        $host = '';
        $port = '587';
        $user = '';
        $pass = '';
        $fromMail = '';
        $fromName = 'KaiShop';
        $siteName = 'KaiShop';
        $siteUrl = '';
        $logo = '';

        // Load via EnvHelper if available
        if (class_exists('EnvHelper')) {
            $host = (string) EnvHelper::get('SMTP_HOST', 'smtp.gmail.com');
            $port = (string) EnvHelper::get('SMTP_PORT', '587');
            $user = (string) EnvHelper::get('SMTP_USER', '');
            $pass = (string) EnvHelper::get('SMTP_PASS', '');
            $fromMail = (string) EnvHelper::get('EMAIL_FROM', $user);
            $fromName = (string) EnvHelper::get('EMAIL_FROM_NAME', 'KaiShop');
        }

        // Override with DB settings (chungapi) if available
        if (function_exists('get_setting')) {
            $host = get_setting('smtp', $host);
            $port = get_setting('port_smtp', $port);
            $user = get_setting('email_auto', $user);
            $pass = get_setting('pass_mail_auto', $pass);
            $fromName = get_setting('ten_nguoi_gui', $fromName);
            $fromMail = $fromMail !== '' ? $fromMail : $user;
        }

        // Site info
        if (function_exists('get_setting')) {
            $siteName = get_setting('ten_web', 'KaiShop');
            $logo = get_setting('logo', '');
        }

        if (defined('BASE_URL')) {
            $siteUrl = rtrim((string) BASE_URL, '/');
        } elseif (class_exists('EnvHelper')) {
            $appDir = (string) EnvHelper::get('APP_DIR', '');
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host2 = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $siteUrl = $scheme . '://' . $host2 . $appDir;
        }

        return [
            'smtp_host' => $host,
            'smtp_port' => $port,
            'smtp_user' => $user,
            'smtp_pass' => $pass,
            'from_email' => $fromMail !== '' ? $fromMail : $user,
            'from_name' => $fromName,
            'site_url' => $siteUrl,
            'site_name' => $siteName,
            'site_logo' => $logo,
        ];
    }
}

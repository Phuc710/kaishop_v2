<?php
/**
 * banned.php — Trang truy cập bị hạn chế (HACKER STYLE)
 */
require_once __DIR__ . '/hethong/config.php';
global $chungapi;
http_response_code(403);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$siteName = (string) ($chungapi['ten_web'] ?? 'KaiShop');
$sitePolicy = function_exists('url') ? url('chinh-sach') : '/chinh-sach';
$timeService = class_exists('TimeService') ? TimeService::instance() : null;
$displayNow = $timeService ? $timeService->formatDisplay($timeService->nowTs(), 'Y-m-d H:i:s') : date('Y-m-d H:i:s');
$serverNowTs = $timeService ? $timeService->nowTs() : time();

$banContext = [
    'scope' => '',
    'reason' => '',
    'expires_at' => null,
    'source' => '',
    'banned_by' => '',
    'banned_at' => '',
];

$sessionBanMeta = $_SESSION['banned_meta'] ?? null;
if (is_array($sessionBanMeta)) {
    $banContext['scope'] = (string) ($sessionBanMeta['scope'] ?? '');
    $banContext['reason'] = trim((string) ($sessionBanMeta['reason'] ?? ''));
    $banContext['expires_at'] = trim((string) ($sessionBanMeta['expires_at'] ?? '')) ?: null;
    $banContext['source'] = trim((string) ($sessionBanMeta['source'] ?? ''));
    $banContext['banned_by'] = trim((string) ($sessionBanMeta['banned_by'] ?? ''));
    $banContext['banned_at'] = trim((string) ($sessionBanMeta['banned_at'] ?? ''));
}

$currentIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$cookieDevice = trim((string) ($_COOKIE['ks_dv'] ?? ''));
$currentUa = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
$deviceHash = ($cookieDevice !== '' && preg_match('/^[a-f0-9]{20,64}$/', $cookieDevice))
    ? hash('sha256', 'device:' . $cookieDevice . '|ua:' . $currentUa)
    : '';

try {
    if (class_exists('BanService')) {
        $banService = new BanService();
        if ($currentIp !== '') {
            $ipBan = $banService->getActiveIpBan($currentIp);
            if (is_array($ipBan)) {
                $banContext['scope'] = 'ip';
                $banContext['reason'] = trim((string) ($ipBan['reason'] ?? $banContext['reason']));
                $banContext['expires_at'] = !empty($ipBan['expires_at']) ? (string) $ipBan['expires_at'] : null;
                $banContext['source'] = trim((string) ($ipBan['source'] ?? $banContext['source']));
                $banContext['banned_by'] = trim((string) ($ipBan['banned_by'] ?? $banContext['banned_by']));
                $banContext['banned_at'] = trim((string) ($ipBan['banned_at'] ?? $banContext['banned_at']));
            }
        }

        if ($banContext['scope'] === '' && $deviceHash !== '') {
            $deviceBan = $banService->getActiveDeviceBan($deviceHash);
            if (is_array($deviceBan)) {
                $banContext['scope'] = 'device';
                $banContext['reason'] = trim((string) ($deviceBan['reason'] ?? $banContext['reason']));
                $banContext['expires_at'] = !empty($deviceBan['expires_at']) ? (string) $deviceBan['expires_at'] : null;
                $banContext['source'] = trim((string) ($deviceBan['source'] ?? $banContext['source']));
                $banContext['banned_by'] = trim((string) ($deviceBan['banned_by'] ?? $banContext['banned_by']));
                $banContext['banned_at'] = trim((string) ($deviceBan['created_at'] ?? $banContext['banned_at']));
            }
        }
    }
} catch (Throwable $e) {
    // non-blocking
}

if (!empty($_SESSION['banned_reason']) && $banContext['reason'] === '') {
    $banContext['reason'] = trim((string) $_SESSION['banned_reason']);
}
unset($_SESSION['banned_reason']);

$banExpiresAt = $banContext['expires_at'] ?: null;
$banExpiresTs = $banExpiresAt ? (strtotime($banExpiresAt) ?: null) : null;
$isPermanentBan = $banContext['scope'] !== '' && $banExpiresAt === null;
$reason = $banContext['reason'] !== '' ? htmlspecialchars($banContext['reason'], ENT_QUOTES, 'UTF-8') : '';
$bannedBy = $banContext['banned_by'] !== '' ? htmlspecialchars($banContext['banned_by'], ENT_QUOTES, 'UTF-8') : '';
$banSource = $banContext['source'] !== '' ? htmlspecialchars($banContext['source'], ENT_QUOTES, 'UTF-8') : '';
$banStartedAt = $banContext['banned_at'] !== '' ? htmlspecialchars($banContext['banned_at'], ENT_QUOTES, 'UTF-8') : '';

function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// Ref id nhẹ để nhìn "terminal"
$ref = substr(hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 12);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ACCESS DENIED | <?= e($siteName) ?></title>

    <meta name="description"
        content="Quyền truy cập của bạn vào <?= e($siteName) ?> đã bị hạn chế. Vui lòng liên hệ hỗ trợ để biết thêm thông tin." />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="canonical" href="<?= e($sitePolicy) ?>" />
    <?php
    $resolveSiteIconUrl = static function ($path): string {
        $cleanPath = trim(preg_replace('/\s+/', '', (string) $path));
        if ($cleanPath === '') {
            return '';
        }

        if (preg_match('~^(?:https?:)?//|^(?:data|blob):~i', $cleanPath)) {
            return $cleanPath;
        }

        return asset(ltrim($cleanPath, '/'));
    };

    $appendIconVersion = static function (string $href, string $version): string {
        if ($href === '' || $version === '') {
            return $href;
        }

        return $href . (str_contains($href, '?') ? '&' : '?') . 'v=' . rawurlencode($version);
    };

    $faviconHref = '';
    $faviconVersion = trim((string) ($chungapi['updated_at'] ?? ''));
    foreach (
        [
            (string) ($chungapi['favicon'] ?? ''),
            (string) ($chungapi['logo'] ?? ''),
            'assets/images/header_logo.gif',
        ] as $iconCandidate
    ) {
        $resolvedIcon = $resolveSiteIconUrl($iconCandidate);
        if ($resolvedIcon !== '') {
            $faviconHref = $resolvedIcon;
            break;
        }
    }
    $faviconHref = $appendIconVersion($faviconHref, $faviconVersion);
    ?>
    <link rel="icon" href="<?= e($faviconHref) ?>" />
    <link rel="shortcut icon" href="<?= e($faviconHref) ?>" />

    <style>
        :root {
            --bg: #050807;
            --panel: rgba(0, 20, 10, 0.35);
            --line: rgba(87, 255, 138, 0.22);
            --g: #57ff8a;
            --g2: #76ff9f;
            --muted: rgba(87, 255, 138, 0.70);
            --shadow: rgba(87, 255, 138, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            background: radial-gradient(900px 600px at 15% 10%, rgba(87, 255, 138, .14), transparent 60%),
                radial-gradient(900px 600px at 80% 20%, rgba(87, 255, 138, .10), transparent 55%),
                var(--bg);
            color: var(--g);
            font-family: Consolas, "Courier New", ui-monospace, monospace;
            overflow: hidden;
        }

        /* Matrix canvas */
        #matrix {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: 0.3;
        }

        /* Scanlines + vignette */
        .scanlines {
            position: fixed;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            background:
                linear-gradient(to bottom,
                    rgba(0, 0, 0, 0.12) 0%,
                    rgba(0, 0, 0, 0.12) 50%,
                    rgba(0, 0, 0, 0.22) 51%,
                    rgba(0, 0, 0, 0.22) 100%);
            background-size: 100% 4px;
            mix-blend-mode: multiply;
        }

        .vignette {
            position: fixed;
            inset: 0;
            z-index: 3;
            pointer-events: none;
            background: radial-gradient(circle at center, rgba(255, 0, 0, 0) 0%, rgba(0, 0, 0, 0.55) 60%, rgba(0, 0, 0, 0.92) 100%);
        }

        .wrap {
            position: relative;
            z-index: 5;
            height: 100%;
            padding: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            width: 100%;
            max-width: 720px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: rgba(0, 16, 8, 0.22);
            box-shadow: 0 0 26px var(--shadow);
            overflow: hidden;
        }

        .topbar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-bottom: 1px solid rgba(87, 255, 138, 0.16);
            background: var(--panel);
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 99px;
            background: rgba(87, 255, 138, 0.75);
            box-shadow: 0 0 14px rgba(87, 255, 138, 0.6);
            animation: pulse 1.5s infinite;
            flex: 0 0 auto;
        }

        @keyframes pulse {
            0% {
                opacity: .35;
                transform: scale(.9);
            }

            50% {
                opacity: 1;
                transform: scale(1.1);
            }

            100% {
                opacity: .35;
                transform: scale(.9);
            }
        }

        .brand {
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-shadow: 0 0 14px rgba(87, 255, 138, 0.35);
            white-space: nowrap;
        }

        .marq {
            flex: 1;
            opacity: .9;
        }

        marquee {
            color: var(--g2);
            text-shadow: 0 0 10px rgba(87, 255, 138, 0.25);
        }

        .body {
            padding: 16px;
        }

        .glitch {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            position: relative;
            display: inline-block;
            margin: 0 0 10px 0;
            text-shadow: 0 0 18px rgba(87, 255, 138, 0.25);
        }

        .glitch:before,
        .glitch:after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            opacity: .85;
            clip: rect(0, 0, 0, 0);
        }

        .glitch:before {
            color: #8dffb6;
            animation: glitchTop 2.2s infinite linear alternate-reverse;
        }

        .glitch:after {
            color: #2eff6a;
            animation: glitchBot 2.0s infinite linear alternate-reverse;
        }

        @keyframes glitchTop {
            0% {
                clip: rect(0px, 9999px, 0px, 0px);
                transform: translate(0, 0);
            }

            10% {
                clip: rect(0px, 9999px, 12px, 0px);
                transform: translate(-1px, -1px);
            }

            30% {
                clip: rect(0px, 9999px, 8px, 0px);
                transform: translate(2px, -1px);
            }

            60% {
                clip: rect(0px, 9999px, 16px, 0px);
                transform: translate(-2px, 0);
            }

            100% {
                clip: rect(0px, 9999px, 0px, 0px);
                transform: translate(0, 0);
            }
        }

        @keyframes glitchBot {
            0% {
                clip: rect(0px, 9999px, 0px, 0px);
                transform: translate(0, 0);
            }

            15% {
                clip: rect(14px, 9999px, 28px, 0px);
                transform: translate(1px, 0);
            }

            35% {
                clip: rect(10px, 9999px, 26px, 0px);
                transform: translate(-2px, 1px);
            }

            70% {
                clip: rect(18px, 9999px, 34px, 0px);
                transform: translate(2px, 1px);
            }

            100% {
                clip: rect(0px, 9999px, 0px, 0px);
                transform: translate(0, 0);
            }
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 8px 0 12px 0;
            font-size: 12px;
            color: rgba(87, 255, 138, 0.72);
        }

        .pill {
            border: 1px solid rgba(87, 255, 138, 0.18);
            background: rgba(0, 20, 10, 0.30);
            border-radius: 999px;
            padding: 4px 10px;
        }

        .term {
            border: 1px solid rgba(87, 255, 138, 0.20);
            background: rgba(0, 0, 0, 0.25);
            border-radius: 14px;
            padding: 12px;
            min-height: 220px;
            box-shadow: 0 0 18px rgba(87, 255, 138, 0.08);
            position: relative;
            overflow: hidden;
        }

        .lines {
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.45;
            font-size: 14px;
            text-shadow: 0 0 10px rgba(87, 255, 138, 0.18);
        }

        .cursor {
            display: inline-block;
            width: 10px;
            height: 16px;
            background: rgba(87, 255, 138, 0.75);
            box-shadow: 0 0 12px rgba(87, 255, 138, 0.55);
            vertical-align: -2px;
            margin-left: 4px;
            animation: blink .9s infinite;
        }

        @keyframes blink {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        .actions {
            margin-top: 12px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid rgba(87, 255, 138, 0.22);
            background: rgba(0, 20, 10, 0.25);
            color: var(--g2);
            text-decoration: none;
            font-weight: 700;
            letter-spacing: .2px;
            transition: transform .12s ease, border-color .12s ease, background .12s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            border-color: rgba(87, 255, 138, 0.35);
            background: rgba(0, 20, 10, 0.38);
        }

        .small {
            margin-top: 10px;
            font-size: 12px;
            color: rgba(87, 255, 138, 0.55);
        }

        .countdown-box {
            margin-top: 14px;
            padding: 12px 14px;
            border: 1px solid rgba(87, 255, 138, 0.18);
            border-radius: 14px;
            background: rgba(0, 20, 10, 0.24);
        }

        .detail-grid {
            margin-top: 14px;
            display: grid;
            gap: 10px;
        }

        .detail-item {
            padding: 12px 14px;
            border: 1px solid rgba(87, 255, 138, 0.18);
            border-radius: 14px;
            background: rgba(0, 20, 10, 0.24);
        }

        .detail-label {
            font-size: 12px;
            color: rgba(87, 255, 138, 0.62);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .detail-value {
            margin-top: 6px;
            font-size: 15px;
            color: var(--g2);
            line-height: 1.55;
            word-break: break-word;
        }

        .countdown-label {
            font-size: 12px;
            color: rgba(87, 255, 138, 0.62);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .countdown-value {
            margin-top: 6px;
            font-size: 28px;
            font-weight: 800;
            color: var(--g2);
            text-shadow: 0 0 14px rgba(87, 255, 138, 0.18);
        }

        .countdown-note {
            margin-top: 6px;
            font-size: 12px;
            color: rgba(87, 255, 138, 0.58);
        }

        @media (max-width:520px) {
            .glitch {
                font-size: 18px;
            }

            .term {
                min-height: 260px;
            }
        }
    </style>
</head>

<body>
    <canvas id="matrix"></canvas>
    <div class="scanlines"></div>
    <div class="vignette"></div>

    <div class="wrap">
        <div class="card">
            <div class="topbar">
                <span class="dot"></span>
                <span class="brand"><?= e($siteName) ?> :: ACCESS TERMINAL</span>
                <div class="marq">
                    <marquee behavior="scroll" direction="left" scrollamount="6">
                        [403] ACCESS DENIED • POLICY ENFORCEMENT ENABLED • TRACE: <?= e($ref) ?> • PRESS [SPACE] TO
                        SPEEDUP • PRESS [R] TO REPLAY
                    </marquee>
                </div>
            </div>

            <div class="body">
                <div class="glitch" data-text="ACCESS DENIED">ACCESS DENIED</div>

                <div class="meta">
                    <span class="pill">HTTP: 403 FORBIDDEN</span>
                    <span class="pill">REF: <?= e($ref) ?></span>
                    <span class="pill">NODE: vn-hcm-01</span>
                    <span class="pill">TIME: <?= e($displayNow) ?></span>
                    <?php if ($banContext['scope'] !== ''): ?>
                        <span class="pill">SCOPE: <?= e(strtoupper($banContext['scope'])) ?></span>
                    <?php endif; ?>
                    <?php if ($banExpiresAt): ?>
                        <span class="pill">EXPIRES: <?= e((string) $banExpiresAt) ?></span>
                    <?php endif; ?>
                </div>

                <div class="term">
                    <div class="lines"><span id="out"></span><span class="cursor"></span></div>
                </div>
                <?php if ($reason !== '' || $banStartedAt !== '' || $bannedBy !== '' || $banSource !== ''): ?>
                    <div class="detail-grid">
                        <?php if ($reason !== ''): ?>
                            <div class="detail-item">
                                <div class="detail-label">Ly do</div>
                                <div class="detail-value"><?= $reason ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($banStartedAt !== '' || $bannedBy !== '' || $banSource !== ''): ?>
                            <div class="detail-item">
                                <div class="detail-label">Thong tin ban</div>
                                <div class="detail-value">
                                    <?php if ($banStartedAt !== ''): ?>Bat dau: <?= $banStartedAt ?><br><?php endif; ?>
                                    <?php if ($bannedBy !== ''): ?>Thuc hien: <?= $bannedBy ?><br><?php endif; ?>
                                    <?php if ($banSource !== ''): ?>Nguon: <?= e(strtoupper($banSource)) ?><?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($banExpiresTs !== null): ?>
                    <div class="countdown-box">
                        <div class="countdown-label">Tu mo lai sau</div>
                        <div class="countdown-value" id="banCountdown">--:--</div>
                        <div class="countdown-note" id="banCountdownNote">
                            Du kien mo lai luc <?= e((string) $banExpiresAt) ?>. Trang se tu tai lai khi het thoi gian.
                        </div>
                    </div>
                <?php elseif ($isPermanentBan): ?>
                    <div class="countdown-box">
                        <div class="countdown-label">Trang thai</div>
                        <div class="countdown-value">Permanent</div>
                        <div class="countdown-note">Lenh ban nay chi duoc go bo thu cong boi admin.</div>
                    </div>
                <?php endif; ?>
                <div class="small">
                    Gợi ý: Nếu bạn nghĩ đây là nhầm lẫn, liên hệ hỗ trợ và cung cấp mã REF ở trên.
                </div>
            </div>
        </div>
    </div>

    <script>
        /* <![CDATA[ */
        (function () {
            // ===== Terminal typing =====
            var out = document.getElementById('out');

            var site = <?= json_encode($siteName, JSON_UNESCAPED_UNICODE) ?>;
            var reason = <?= json_encode($reason, JSON_UNESCAPED_UNICODE) ?>;
            var ref = <?= json_encode($ref, JSON_UNESCAPED_UNICODE) ?>;
            var banScope = <?= json_encode((string) ($banContext['scope'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
            var banStartedAt = <?= json_encode((string) ($banContext['banned_at'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
            var bannedBy = <?= json_encode($bannedBy !== '' ? html_entity_decode($bannedBy, ENT_QUOTES, 'UTF-8') : '', JSON_UNESCAPED_UNICODE) ?>;
            var serverNowMs = <?= (int) ($serverNowTs * 1000) ?>;
            var banExpiresMs = <?= $banExpiresTs !== null ? (int) ($banExpiresTs * 1000) : 'null' ?>;
            var banExpiresAt = <?= json_encode((string) ($banExpiresAt ?? ''), JSON_UNESCAPED_UNICODE) ?>;

            var lines = [];
            lines.push("root@" + site.toLowerCase().replace(/\s+/g, '') + ":~$ auth --check --strict");
            lines.push("[+] handshake .......... ");
            lines.push("[+] access control ..... DENY");
            lines.push("[!] result: HTTP 403 (FORBIDDEN)");
            lines.push("");

            if (reason && reason.length) {
                lines.push("[i] reason: " + reason);
            } else {
                lines.push("[i] reason: (hidden) automatic restriction");
            }

            if (banScope) {
                lines.push("[i] scope: " + banScope.toUpperCase());
            }
            if (banStartedAt) {
                lines.push("[i] started: " + banStartedAt);
            }
            if (banExpiresAt) {
                lines.push("[i] expires: " + banExpiresAt);
            }
            if (bannedBy) {
                lines.push("[i] by: " + bannedBy);
            }
            lines.push("[i] reference: " + ref);
            lines.push("");
            lines.push("[*] hint: contact support if needed.");
            lines.push("");
            lines.push("root@terminal:~$ _");

            var text = lines.join("\n");
            var i = 0;
            var speed = 14;
            var boost = 1;

            function clearNode(node) {
                while (node.firstChild) { node.removeChild(node.firstChild); }
            }

            function reset() {
                i = 0; boost = 1;
                clearNode(out);
            }

            function tick() {
                if (i >= text.length) return;

                var chunk = 1 * boost;
                while (chunk > 0 && i < text.length) {
                    out.appendChild(document.createTextNode(text.charAt(i)));
                    i += 1; chunk -= 1;
                }
                window.setTimeout(tick, speed);
            }

            reset(); tick();

            var countdownEl = document.getElementById('banCountdown');
            var countdownNoteEl = document.getElementById('banCountdownNote');
            var retryTriggered = false;

            function formatDuration(ms) {
                var totalSeconds = Math.max(0, Math.floor(ms / 1000));
                var hours = Math.floor(totalSeconds / 3600);
                var minutes = Math.floor((totalSeconds % 3600) / 60);
                var seconds = totalSeconds % 60;

                if (hours > 0) {
                    return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                }

                return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            }

            function retryAccess() {
                if (retryTriggered) {
                    return;
                }

                retryTriggered = true;
                if (countdownNoteEl) {
                    countdownNoteEl.textContent = 'Đang thử truy cập lại...';
                }

                window.setTimeout(function () {
                    if (window.location.pathname.toLowerCase().indexOf('/banned.php') !== -1) {
                        window.location.replace(<?= json_encode(url(''), JSON_UNESCAPED_UNICODE) ?>);
                        return;
                    }

                    window.location.reload();
                }, 700);
            }

            if (countdownEl && typeof banExpiresMs === 'number') {
                var clientStartMs = Date.now();
                var updateCountdown = function () {
                    var estimatedNowMs = serverNowMs + (Date.now() - clientStartMs);
                    var diffMs = banExpiresMs - estimatedNowMs;

                    if (diffMs <= 0) {
                        countdownEl.textContent = '00:00';
                        retryAccess();
                        return;
                    }

                    countdownEl.textContent = formatDuration(diffMs);
                };

                updateCountdown();
                window.setInterval(updateCountdown, 250);
            }

            document.addEventListener('keydown', function (e) {
                if (e.keyCode === 32) { boost = 6; }          // SPACE
                if (e.keyCode === 82) { reset(); tick(); }    // R
            }, true);

            document.addEventListener('keyup', function (e) {
                if (e.keyCode === 32) { boost = 1; }
            }, true);

            // ===== Matrix background (lightweight) =====
            var canvas = document.getElementById('matrix');
            var ctx = canvas.getContext('2d');

            function resize() {
                canvas.width = window.innerWidth || document.documentElement.clientWidth || 1200;
                canvas.height = window.innerHeight || document.documentElement.clientHeight || 700;
            }
            resize();

            var letters = "01abcdefhijklmnpqrstuvwxyz#$%&*@";
            var fontSize = 14;
            var columns = Math.floor(canvas.width / fontSize);
            var drops = [];
            for (var x = 0; x < columns; x++) { drops[x] = Math.floor(Math.random() * canvas.height / fontSize); }

            function draw() {
                ctx.fillStyle = "rgba(0, 0, 0, 0.08)";
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                ctx.font = fontSize + "px monospace";
                ctx.fillStyle = "rgba(87, 255, 138, 0.9)";

                for (var i2 = 0; i2 < drops.length; i2++) {
                    var ch = letters.charAt(Math.floor(Math.random() * letters.length));
                    ctx.fillText(ch, i2 * fontSize, drops[i2] * fontSize);

                    if (drops[i2] * fontSize > canvas.height && Math.random() > 0.975) drops[i2] = 0;
                    drops[i2] = drops[i2] + 1;
                }
                window.requestAnimationFrame(draw);
            }
            draw();

            window.addEventListener('resize', function () {
                resize();
                columns = Math.floor(canvas.width / fontSize);
                drops = [];
                for (var j = 0; j < columns; j++) { drops[j] = Math.floor(Math.random() * canvas.height / fontSize); }
            }, true);

            // ===== Anti-Debug / Anti-F12 =====
            // Block keyboard shortcuts (F12, Ctrl+Shift+I/J/C, Ctrl+U, Ctrl+S)
            document.addEventListener('keydown', function (e) {
                if (e.key === 'F12' || e.keyCode === 123) { e.preventDefault(); return false; }
                if (e.ctrlKey && e.shiftKey && 'IJCijc'.indexOf(e.key) !== -1) { e.preventDefault(); return false; }
                if (e.ctrlKey && (e.key === 'u' || e.key === 'U')) { e.preventDefault(); return false; }
                if (e.ctrlKey && (e.key === 's' || e.key === 'S')) { e.preventDefault(); return false; }
                if (e.ctrlKey && e.shiftKey && (e.key === 'K' || e.key === 'k')) { e.preventDefault(); return false; }
            }, true);

            // Block right-click, select, drag, copy
            document.addEventListener('contextmenu', function (e) { e.preventDefault(); }, true);
            document.addEventListener('selectstart', function (e) { e.preventDefault(); }, true);
            document.addEventListener('dragstart', function (e) { e.preventDefault(); }, true);
            document.addEventListener('copy', function (e) { e.preventDefault(); }, true);

            // DevTools debugger trap
            var _devOpen = false;
            setInterval(function () {
                var t0 = performance.now();
                debugger;
                if (performance.now() - t0 > 100 && !_devOpen) {
                    _devOpen = true;
                    document.body.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100vh;background:#050807;"><p style="color:#57ff8a;font-size:20px;font-weight:700;font-family:monospace;">⛔ ACCESS DENIED — DevTools detected</p></div>';
                }
            }, 1500);

            // Window size delta detection (docked DevTools)
            var _dw = window.outerWidth - window.innerWidth;
            var _dh = window.outerHeight - window.innerHeight;
            setInterval(function () {
                if (window.outerWidth - window.innerWidth > _dw + 160 || window.outerHeight - window.innerHeight > _dh + 160) {
                    document.body.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100vh;background:#050807;"><p style="color:#57ff8a;font-size:20px;font-weight:700;font-family:monospace;">⛔ ACCESS DENIED — DevTools detected</p></div>';
                }
            }, 500);

            // Disable console
            try {
                Object.defineProperty(window, 'console', {
                    get: function () { return { log: function () { }, warn: function () { }, error: function () { }, info: function () { }, dir: function () { }, table: function () { } }; },
                    set: function () { }
                });
            } catch (x) { }

        })();
        /* ]]> */
    </script>
</body>

</html>
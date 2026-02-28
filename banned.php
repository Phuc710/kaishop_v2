<?php
/**
 * banned.php — Trang truy cập bị hạn chế
 */
require_once __DIR__ . '/hethong/config.php';
http_response_code(403);

$siteName = $chungapi['ten_web'] ?? 'KaiShop';
$sitePolicy = url('chinh-sach');

$reason = '';
if (!empty($_SESSION['banned_reason'])) {
    $reason = htmlspecialchars((string) $_SESSION['banned_reason'], ENT_QUOTES, 'UTF-8');
}
// Clear session sau khi đọc
session_destroy();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require HETHONG_PATH . '/head2.php'; ?>
    <title>Truy Cập Bị Hạn Chế |
        <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>
    </title>
    <meta name="description"
        content="Quyền truy cập của bạn vào <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?> đã bị hạn chế. Vui lòng liên hệ hỗ trợ để biết thêm thông tin.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= $sitePolicy ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/error-pages.css') ?>">
    <style>
        /* ── Banned card extras ─────────────────────── */
        .banned-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(239, 68, 68, .35);
            animation: pulse-red 2.5s ease-in-out infinite;
        }

        .banned-icon i {
            font-size: 30px;
            color: #fff;
        }

        @keyframes pulse-red {

            0%,
            100% {
                box-shadow: 0 8px 24px rgba(239, 68, 68, .35);
            }

            50% {
                box-shadow: 0 8px 36px rgba(239, 68, 68, .6);
            }
        }

        .reason-box {
            background: rgba(239, 68, 68, .07);
            border-left: 3px solid #ef4444;
            border-radius: 6px;
            padding: 14px 18px;
            margin: 18px 0 24px;
            text-align: left;
        }

        .reason-box strong {
            display: block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .6px;
            text-transform: uppercase;
            color: #ef4444;
            margin-bottom: 6px;
        }

        .reason-box p {
            margin: 0;
            font-size: 14px;
            color: #4b5563;
            line-height: 1.6;
        }

        .action-row {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 8px;
        }
    </style>
</head>

<body class="error-page-wrapper">
    <div class="error-card" style="max-width:480px;">

        <div class="banned-icon">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <h1 class="error-title" style="font-size:22px;margin-bottom:8px;">
            Truy Cập Bị Hạn Chế
        </h1>

        <p class="error-message" style="margin-bottom:4px;">
            Quyền truy cập của bạn vào <strong>
                <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>
            </strong>
            đã bị hạn chế theo chính sách sử dụng dịch vụ.
        </p>

        <?php if ($reason !== ''): ?>
            <!-- Admin banned manually → show reason -->
            <div class="reason-box">
                <strong>Lý do</strong>
                <p><?= $reason ?></p>
            </div>
        <?php else: ?>
            <!-- Auto-banned by system → hide reason -->
            <div class="reason-box">
                <strong>Thông báo</strong>
                <p>Nếu bạn cho rằng đây là nhầm lẫn, vui lòng liên hệ bộ phận hỗ trợ.</p>
            </div>
        <?php endif; ?>

        <div class="action-row">
            <a href="<?= $sitePolicy ?>" class="error-action">
                <i class="fa-solid fa-book-open"></i> Xem chính sách
            </a>
        </div>

    </div>

    <script>
        // ─── Anti-Debug / Anti-F12 ───────────────────────────────
        (function () {
            // 1. Block keyboard shortcuts
            document.addEventListener('keydown', function (e) {
                // F12
                if (e.key === 'F12' || e.keyCode === 123) { e.preventDefault(); return false; }
                // Ctrl+Shift+I / Ctrl+Shift+J / Ctrl+Shift+C (DevTools)
                if (e.ctrlKey && e.shiftKey && ['I', 'J', 'C', 'i', 'j', 'c'].includes(e.key)) { e.preventDefault(); return false; }
                // Ctrl+U (View Source)
                if (e.ctrlKey && (e.key === 'u' || e.key === 'U')) { e.preventDefault(); return false; }
                // Ctrl+S (Save Page)
                if (e.ctrlKey && (e.key === 's' || e.key === 'S')) { e.preventDefault(); return false; }
                // Ctrl+Shift+K (Firefox Console)
                if (e.ctrlKey && e.shiftKey && (e.key === 'K' || e.key === 'k')) { e.preventDefault(); return false; }
            }, true);

            // 2. Block right-click context menu
            document.addEventListener('contextmenu', function (e) { e.preventDefault(); return false; }, true);

            // 3. Block text selection & drag
            document.addEventListener('selectstart', function (e) { e.preventDefault(); }, true);
            document.addEventListener('dragstart', function (e) { e.preventDefault(); }, true);
            document.addEventListener('copy', function (e) { e.preventDefault(); }, true);

            // 4. DevTools open detection via debugger trap
            var _dt = false;
            setInterval(function () {
                var t0 = performance.now();
                debugger;
                if (performance.now() - t0 > 100) {
                    if (!_dt) {
                        _dt = true;
                        document.body.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100vh;background:#0f172a;"><p style="color:#ef4444;font-size:20px;font-weight:700;font-family:sans-serif;">⛔ Truy cập bị từ chối</p></div>';
                    }
                }
            }, 1000);

            // 5. Window size delta detection (DevTools docked changes viewport)
            var _w = window.outerWidth - window.innerWidth;
            var _h = window.outerHeight - window.innerHeight;
            setInterval(function () {
                var dw = window.outerWidth - window.innerWidth;
                var dh = window.outerHeight - window.innerHeight;
                if (dw > _w + 160 || dh > _h + 160) {
                    document.body.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100vh;background:#0f172a;"><p style="color:#ef4444;font-size:20px;font-weight:700;font-family:sans-serif;">⛔ Truy cập bị từ chối</p></div>';
                }
            }, 500);

            // 6. Disable console methods
            try {
                Object.defineProperty(window, 'console', {
                    get: function () {
                        return { log: function () { }, warn: function () { }, error: function () { }, info: function () { }, dir: function () { }, table: function () { } };
                    },
                    set: function () { }
                });
            } catch (e) { }
        })();
    </script>
</body>

</html>
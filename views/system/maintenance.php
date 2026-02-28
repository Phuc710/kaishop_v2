<?php
$message = (string) ($maintenanceState['message'] ?? 'Hệ thống đang bảo trì để nâng cấp dịch vụ. Vui lòng quay lại sau.');
$siteName = isset($siteName) ? (string) $siteName : 'KaiShop';
$siteFavicon = isset($siteFavicon) ? (string) $siteFavicon : '';
$maintenanceJson = json_encode(
    $maintenanceState ?? [],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảo trì hệ thống | <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description"
        content="Hệ thống <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?> đang bảo trì. Vui lòng quay lại sau.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= htmlspecialchars(url(''), ENT_QUOTES, 'UTF-8') ?>">
    <?php $cleanSiteFavicon = trim(preg_replace('/\s+/', '', (string) ($siteFavicon ?? ''))); ?>
    <link rel="shortcut icon"
        href="<?= htmlspecialchars($cleanSiteFavicon !== '' ? (str_starts_with($cleanSiteFavicon, 'http') ? $cleanSiteFavicon : asset(ltrim($cleanSiteFavicon, '/'))) : asset('assets/images/favicon.png'), ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/error-pages.css') ?>">
    <style>
        /* Digit block countdown */
        .mnt-clock {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 16px 0 8px;
        }

        .mnt-clock__block {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .mnt-clock__digits {
            display: flex;
            gap: 3px;
        }

        .mnt-clock__digit {
            width: 44px;
            height: 56px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: #f8fafc;
            border-radius: 8px;
            font-size: 1.75rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.06);
            letter-spacing: 0;
        }

        .mnt-clock__label {
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-top: 5px;
        }

        .mnt-clock__sep {
            font-size: 1.6rem;
            font-weight: 800;
            color: #334155;
            padding-bottom: 14px;
            line-height: 56px;
        }

        .mnt-clock__end {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        @media (max-width: 480px) {
            .mnt-clock__digit {
                width: 36px;
                height: 46px;
                font-size: 1.35rem;
                border-radius: 6px;
            }

            .mnt-clock__sep {
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body class="error-page-wrapper maintenance-page">
    <div class="error-card maintenance-card">
        <img class="maintenance-gif" src="<?= asset('assets/images/maintenance.gif?v=1771944320') ?>"
            alt="Bảo trì hệ thống">
        <h1 class="error-title">HỆ THỐNG ĐANG BẢO TRÌ</h1>
        <p class="error-message">Chúng tôi đang nâng cấp hệ thống để dịch vụ ổn định hơn. Vui lòng quay lại sau.</p>

        <!-- Countdown clock (hidden until JS confirms active+scheduled) -->
        <div id="maintenanceCountdownWrap" class="maintenance-countdown-wrap" hidden>
            <div class="maintenance-countdown-label" id="countdownLabel">Thời gian bảo trì dự kiến còn lại</div>
            <div class="mnt-clock" id="mntClock">
                <div class="mnt-clock__block">
                    <div class="mnt-clock__digits">
                        <span class="mnt-clock__digit" id="dH0">0</span>
                        <span class="mnt-clock__digit" id="dH1">0</span>
                    </div>
                    <div class="mnt-clock__label">Giờ</div>
                </div>
                <div class="mnt-clock__sep">:</div>
                <div class="mnt-clock__block">
                    <div class="mnt-clock__digits">
                        <span class="mnt-clock__digit" id="dM0">0</span>
                        <span class="mnt-clock__digit" id="dM1">0</span>
                    </div>
                    <div class="mnt-clock__label">Phút</div>
                </div>
                <div class="mnt-clock__sep">:</div>
                <div class="mnt-clock__block">
                    <div class="mnt-clock__digits">
                        <span class="mnt-clock__digit" id="dS0">0</span>
                        <span class="mnt-clock__digit" id="dS1">0</span>
                    </div>
                    <div class="mnt-clock__label">Giây</div>
                </div>
            </div>
        </div>

        <div class="maintenance-message-box">
            <div class="maintenance-message-title">
                <i class="fa-solid fa-bullhorn"></i> Thông báo
            </div>
            <div class="maintenance-message-content"><?= nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) ?></div>
        </div>

        <div class="maintenance-actions">
            <a href="<?= url('') ?>" class="error-action">
                <i class="fa-solid fa-rotate-right"></i> Thử tải lại
            </a>
        </div>
    </div>

    <script src="<?= asset('assets/js/maintenance-runtime.js') ?>"></script>
    <script>
        (function () {
            const statusUrl = '<?= url('api/system/maintenance-status') ?>';
            const homeUrl = '<?= url('') ?>';
            const countdownWrap = document.getElementById('maintenanceCountdownWrap');
            const countdownMeta = document.getElementById('maintenanceCountdownMeta');

            const dH0 = document.getElementById('dH0');
            const dH1 = document.getElementById('dH1');
            const dM0 = document.getElementById('dM0');
            const dM1 = document.getElementById('dM1');
            const dS0 = document.getElementById('dS0');
            const dS1 = document.getElementById('dS1');

            let currentState = <?= $maintenanceJson ?: '{}' ?>;
            let localSecondsLeft = Math.max(0, Number((currentState && currentState.seconds_until_end) || 0));

            function pad2(n) { return String(Math.max(0, n)).padStart(2, '0'); }

            function setDigits(elA, elB, val) {
                const s = pad2(val);
                elA.textContent = s[0];
                elB.textContent = s[1];
            }

            function renderClock(sec) {
                sec = Math.max(0, sec);
                const h = Math.floor(sec / 3600);
                const m = Math.floor((sec % 3600) / 60);
                const s = sec % 60;
                setDigits(dH0, dH1, h);
                setDigits(dM0, dM1, m);
                setDigits(dS0, dS1, s);
            }

            function updateUI() {
                if (!currentState || !currentState.active) {
                    if (countdownWrap) countdownWrap.hidden = true;
                    return;
                }

                const showCountdown = !!currentState.show_end_countdown;
                const manualOverdue = !!currentState.manual_overdue;

                if (countdownWrap) countdownWrap.hidden = false;

                if (showCountdown && localSecondsLeft > 0) {
                    renderClock(localSecondsLeft);
                    if (countdownMeta) {
                        countdownMeta.textContent = currentState.end_at_display
                            ? 'Dự kiến kết thúc: ' + currentState.end_at_display
                            : (currentState.end_at ? 'Dự kiến kết thúc: ' + currentState.end_at : '');
                    }
                } else {
                    renderClock(0);
                    if (countdownMeta) {
                        countdownMeta.textContent = manualOverdue
                            ? 'Đã quá thời gian dự kiến, admin vẫn đang giữ bảo trì thủ công.'
                            : '';
                    }
                }
            }

            function applyRuntime(payload) {
                const m = payload && payload.state ? payload.state : null;
                if (!m || !m.active) {
                    window.location.href = homeUrl;
                    return;
                }

                currentState = m;
                const sec = payload && Number.isFinite(Number(payload.secondsUntilEnd))
                    ? Number(payload.secondsUntilEnd)
                    : Number(m.seconds_until_end || 0);
                localSecondsLeft = Math.max(0, sec);
                updateUI();
            }

            updateUI();

            if (typeof window.KaiMaintenanceRuntime === 'function') {
                const runtime = new window.KaiMaintenanceRuntime({
                    statusUrl: statusUrl,
                    pollMs: 10000
                });
                runtime.onUpdate(applyRuntime);
                runtime.start();
            } else {
                fetch(statusUrl, { credentials: 'same-origin', cache: 'no-store' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        const m = data && data.maintenance ? data.maintenance : null;
                        if (!m || !m.active) {
                            window.location.href = homeUrl;
                            return;
                        }
                        currentState = m;
                        localSecondsLeft = Math.max(0, Number(m.seconds_until_end || 0));
                        updateUI();
                    })
                    .catch(function () { });
            }
        })();
    </script>
</body>

</html>
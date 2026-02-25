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
    <meta name="description" content="Hệ thống <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?> đang bảo trì. Vui lòng quay lại sau.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= htmlspecialchars(url(''), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="shortcut icon" href="<?= htmlspecialchars($siteFavicon, ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/error-pages.css') ?>">
</head>

<body class="error-page-wrapper maintenance-page">
    <div class="error-card maintenance-card">
        <div class="maintenance-glow" aria-hidden="true"></div>
        <img class="maintenance-gif" src="<?= asset('assets/images/maintenance.gif') ?>" alt="Bảo trì hệ thống">
        <div class="error-code maintenance-code"><i class="fa-solid fa-screwdriver-wrench"></i></div>
        <h1 class="error-title">HỆ THỐNG ĐANG BẢO TRÌ</h1>
        <p class="error-message">Chúng tôi đang nâng cấp hệ thống để dịch vụ ổn định hơn. Vui lòng quay lại sau.</p>

        <div id="maintenanceCountdownWrap" class="maintenance-countdown-wrap" hidden>
            <div class="maintenance-countdown-label">Thời gian bảo trì dự kiến còn lại</div>
            <div id="maintenanceCountdownTimer" class="maintenance-countdown-timer">00:00:00</div>
            <div id="maintenanceCountdownMeta" class="maintenance-countdown-meta"></div>
        </div>

        <div class="maintenance-message-box">
            <div class="maintenance-message-title">
                <i class="fa-solid fa-bullhorn"></i> Thông báo từ admin
            </div>
            <div class="maintenance-message-content"><?= nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) ?></div>
        </div>

        <div class="maintenance-actions">
            <a href="<?= url('') ?>" class="error-action">
                <i class="fa-solid fa-rotate-right"></i> Thử tải lại
            </a>
        </div>
    </div>

    <script>
        (function () {
            const statusUrl = '<?= url('api/system/maintenance-status') ?>';
            const homeUrl = '<?= url('') ?>';
            const countdownWrap = document.getElementById('maintenanceCountdownWrap');
            const countdownTimer = document.getElementById('maintenanceCountdownTimer');
            const countdownMeta = document.getElementById('maintenanceCountdownMeta');
            let currentState = <?= $maintenanceJson ?: '{}' ?>;
            let localSecondsLeft = Number((currentState && currentState.seconds_until_end) || 0);

            function formatHms(totalSeconds) {
                const sec = Math.max(0, Number(totalSeconds || 0));
                const h = Math.floor(sec / 3600);
                const m = Math.floor((sec % 3600) / 60);
                const s = sec % 60;
                return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            }

            function updateCountdownUi() {
                if (!countdownWrap || !countdownTimer || !countdownMeta) {
                    return;
                }

                const active = !!(currentState && currentState.active);
                const showCountdown = !!(currentState && currentState.show_end_countdown);
                const manualOverdue = !!(currentState && currentState.manual_overdue);

                if (!active) {
                    countdownWrap.hidden = true;
                    return;
                }

                if (showCountdown && localSecondsLeft > 0) {
                    countdownWrap.hidden = false;
                    countdownTimer.textContent = formatHms(localSecondsLeft);
                    countdownMeta.textContent = currentState && currentState.end_at ? ('Dự kiến kết thúc: ' + currentState.end_at) : '';
                    return;
                }

                countdownWrap.hidden = false;
                countdownTimer.textContent = '--:--:--';
                if (manualOverdue) {
                    countdownMeta.textContent = 'Đã quá thời gian dự kiến nhưng admin vẫn đang giữ bảo trì thủ công.';
                } else {
                    countdownMeta.textContent = 'Admin sẽ mở lại hệ thống sau khi hoàn tất bảo trì.';
                }
            }

            setInterval(function () {
                if (currentState && currentState.active && currentState.show_end_countdown && localSecondsLeft > 0) {
                    localSecondsLeft -= 1;
                    updateCountdownUi();
                }
            }, 1000);

            function pollStatus() {
                fetch(statusUrl, { credentials: 'same-origin', cache: 'no-store' })
                    .then(r => r.json())
                    .then(data => {
                        const m = data && data.maintenance ? data.maintenance : null;
                        if (!m || !m.active) {
                            window.location.href = homeUrl;
                            return;
                        }
                        currentState = m;
                        localSecondsLeft = Number(m.seconds_until_end || 0);
                        updateCountdownUi();
                    })
                    .catch(() => { });
            }

            updateCountdownUi();
            pollStatus();
            setInterval(pollStatus, 10000);
        })();
    </script>
</body>

</html>

<?php
/**
 * Popup Notification Component
 * Được include trong foot.php, hiển thị popup thông báo cho user.
 * Cấu hình qua Admin > Cài đặt > Thông báo & Popup
 *   popup_template: '0' = tắt, '1' = mặc định (khuyến mãi), '2' = thông báo (thongbao)
 */

// Only show popup on homepage
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestPath = '/' . ltrim((string) $requestPath, '/');

if (defined('APP_DIR') && APP_DIR !== '' && strpos($requestPath, APP_DIR) === 0) {
    $requestPath = substr($requestPath, strlen(APP_DIR));
    $requestPath = '/' . ltrim((string) $requestPath, '/');
}

if ($requestPath === '/index.php') {
    $requestPath = '/';
}

$requestPath = rtrim($requestPath, '/') ?: '/';
if ($requestPath !== '/') {
    return;
}

// Get popup settings
$active_template = get_setting('popup_template', '1');

// If template is 0 (disabled), don't show anything
if ($active_template === '0') {
    return;
}

$siteName = get_setting('ten_web', 'Kaishop');
$telegramLink = get_setting('tele_admin', 'https://t.me/Biinj');
$thongbaoContent = get_setting('thongbao', '');

// If template 2 (thongbao) but no content, fallback to default
if ($active_template === '2' && empty($thongbaoContent)) {
    $active_template = '1';
}
?>

<!-- Popup Notification Overlay -->
<div id="notification-popup-overlay" class="light-popup-overlay" style="display:none;">
    <div class="light-popup-container">
        <!-- Header -->
        <div class="light-popup-header">
            <h3 class="light-popup-title">Thông báo</h3>
            <button class="light-popup-close" onclick="closeNotificationPopup()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Content -->
        <div class="light-popup-body">

            <?php if ($active_template === '1'): // Default Popup ?>
                <div class="lp-content">
                    <!-- Fire GIF Header -->
                    <div class="lp-gifs-row">
                        <img src="https://media.giphy.com/media/kEhKBVTIMz6c10g3Lz/giphy.gif" alt="fire"
                            class="lp-gif-fire">
                        <span class="lp-text-teal lp-bold lp-uppercase">DỊCH VỤ THIẾT KẾ WEB - TÀI NGUYÊN - TỐI ƯU
                            SEO</span>
                        <img src="https://media.giphy.com/media/kEhKBVTIMz6c10g3Lz/giphy.gif" alt="fire"
                            class="lp-gif-fire">
                    </div>

                    <p class="lp-text-red lp-bold">Chân thành cảm ơn quý khách đã tin tưởng
                        <span class="lp-text-red">
                            <?= htmlspecialchars($siteName) ?>
                        </span>!
                    </p>

                    <p class="lp-mt-2">
                        Tham gia nhóm Tele: <a href="<?= htmlspecialchars($telegramLink) ?>" target="_blank"
                            class="lp-link-red">TẠI ĐÂY</a>
                    </p>
                    <strong class="lp-text-blue lp-bold">NHÓM SHARE TOOL, FILE, BOT ZALO, TELE + SOUCER WEB</strong>


                    <p class="lp-text-red lp-bold">
                        <?= htmlspecialchars($siteName) ?> chuyên cung cấp Tài Nguyên, dịch vụ
                        thiết kế
                        website MMO
                    </p>

                    <!-- Deposit Promotion Box -->
                    <div class="lp-promo-box lp-mt-2"
                        style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 2px dashed #2980b9;">
                        <div
                            style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                            <img src="https://media.giphy.com/media/xje7ITeGqNAFWyvZ7a/giphy.gif" alt="money"
                                style="width: 24px; height: 24px; object-fit: contain;">
                            <span class="lp-text-blue lp-bold" style="font-size: 1.1em;"> KHUYẾN MÃI NẠP TIỀN </span>
                            <img src="https://media.giphy.com/media/xje7ITeGqNAFWyvZ7a/giphy.gif" alt="money"
                                style="width: 24px; height: 24px; object-fit: contain;">
                        </div>
                        <div style="display: grid; gap: 6px; margin: 10px 0;">
                            <div
                                style="background: rgba(41, 128, 185, 0.1); padding: 6px 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #333; font-weight: 600;">Nạp từ 100.000đ</span>
                                <span
                                    style="background: #2980b9; color: white; padding: 2px 8px; border-radius: 12px; font-weight: 800; font-size: 0.9em;">+10%</span>
                            </div>
                            <div
                                style="background: rgba(41, 128, 185, 0.1); padding: 6px 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #333; font-weight: 600;">Nạp từ 200.000đ</span>
                                <span
                                    style="background: #2980b9; color: white; padding: 2px 8px; border-radius: 12px; font-weight: 800; font-size: 0.9em;">+15%</span>
                            </div>
                            <div
                                style="background: rgba(41, 128, 185, 0.1); padding: 6px 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #333; font-weight: 600;">Nạp từ 500.000đ</span>
                                <span
                                    style="background: #2980b9; color: white; padding: 2px 8px; border-radius: 12px; font-weight: 800; font-size: 0.9em;">+20%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sale GIF + Policy Link -->
                    <div class="lp-policy-row lp-mt-2">
                        <img src="https://media.giphy.com/media/KBlX7iF04rYrtuvSHc/giphy.gif" alt="sale"
                            class="lp-gif-sale">
                        <span class="lp-text-teal lp-bold">Chính sách mua hàng của website: <a
                                href="<?= url('chinhsach') ?>" target="_blank" class="lp-link-red">Tại Đây</a></span>
                        <img src="https://media.giphy.com/media/KBlX7iF04rYrtuvSHc/giphy.gif" alt="sale"
                            class="lp-gif-sale">
                    </div>
                    <p class="lp-text-muted">(Vui lòng đọc kĩ trước khi mua sản phẩm)</p>
                </div>

            <?php elseif ($active_template === '2'): // Thông báo Popup ?>
                <div class="lp-content">
                    <div class="lp-thongbao-content">
                        <?= $thongbaoContent ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Button -->
            <div class="lp-footer-action">
                <button onclick="closePopupFor2Hours()" class="lp-btn-close-2h">
                    Không hiển thị lại trong 2 giờ
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* ===== LIGHT POPUP STYLES ===== */
    .light-popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        opacity: 0;
    }

    .light-popup-container {
        width: 100%;
        max-width: 650px;
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        opacity: 0;
        transform: translateY(10px) scale(0.96);
    }

    .light-popup-overlay.lp-popup-show {
        animation: lpFadeIn 0.5s ease-out forwards;
    }

    .light-popup-overlay.lp-popup-show .light-popup-container {
        animation: lpScaleIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .light-popup-overlay.lp-popup-hide {
        pointer-events: none;
        animation: lpFadeOut 0.5s ease forwards;
    }

    .light-popup-overlay.lp-popup-hide .light-popup-container {
        animation: lpScaleOut 0.5s ease forwards;
    }

    /* Header */
    .light-popup-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid #f0f0f0;
    }

    .light-popup-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #333;
        margin: 0;
    }

    .light-popup-close {
        background: #f0f0f0;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        color: #666;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.9rem;
    }

    .light-popup-close:hover {
        background: #e0e0e0;
        color: #333;
    }

    /* Body */
    .light-popup-body {
        padding: 20px 30px;
        text-align: center;
    }

    .lp-content p {
        line-height: 1.6;
        font-size: 1rem;
        color: #333;
    }

    .lp-mt-2 {
        margin-top: 15px !important;
    }

    /* Text Helpers */
    .lp-bold {
        font-weight: 700;
    }

    .lp-uppercase {
        text-transform: uppercase;
    }

    .lp-text-orange {
        color: #f59e0b;
    }

    .lp-text-red {
        color: #ef4444;
    }

    .lp-text-green {
        color: #10b981;
    }

    .lp-text-black {
        color: #000;
    }

    .lp-text-teal {
        color: #16a085;
    }

    .lp-text-blue {
        color: #2980b9;
    }

    .lp-link-red {
        color: #ef4444;
        font-weight: 700;
        text-decoration: none;
    }

    .lp-link-red:hover {
        text-decoration: underline;
    }

    /* Promo Box */
    .lp-promo-box {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 2px dashed #f59e0b;
        border-radius: 10px;
        padding: 10px 15px;
        margin: 12px 0;
    }

    .lp-promo-code {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: #fff;
        font-weight: 800;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 1.1em;
        letter-spacing: 1px;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        animation: lpPulse 1.5s ease-in-out infinite;
    }

    @keyframes lpPulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }

    /* Footer Button */
    .lp-footer-action {
        margin-top: 25px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
    }

    .lp-btn-close-2h {
        background: #ff6900;
        border-radius: 10px;
        border: 1px solid #ff6900;
        transition: all 0.7s;
        padding: 8px 18px;
        color: #fff;
        font-weight: 600;
        font-size: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .lp-btn-close-2h:hover {
        background: #e55d00;
        border-color: #e55d00;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 105, 0, 0.4);
    }

    /* GIF Styles */
    .lp-gifs-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .lp-gif-fire {
        width: 30px;
        height: 30px;
        object-fit: contain;
    }

    .lp-gif-sale {
        width: 28px;
        height: 28px;
        object-fit: contain;
    }

    .lp-policy-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 12px;
        background: linear-gradient(135deg, #fff3cd 0%, #ffe4a0 100%);
        border-radius: 8px;
        border: 1px dashed #f59e0b;
    }

    .lp-gif-sticker {
        width: 35px;
        height: 35px;
        object-fit: contain;
        animation: lpBounce 1.5s ease-in-out infinite;
    }

    .lp-text-muted {
        color: #888;
        font-size: 0.85rem;
        font-style: italic;
        margin-top: 5px;
    }

    /* Thongbao Content */
    .lp-thongbao-content {
        text-align: left;
        padding: 10px;
        line-height: 1.7;
        font-size: 0.95rem;
        color: #333;
    }

    .lp-thongbao-content img {
        max-width: 100%;
        border-radius: 8px;
    }

    @keyframes lpBounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-5px);
        }
    }

    /* Animations */
    @keyframes lpFadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes lpScaleIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes lpFadeOut {
        from {
            opacity: 1;
        }

        to {
            opacity: 0;
        }
    }

    @keyframes lpScaleOut {
        from {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        to {
            opacity: 0;
            transform: translateY(8px) scale(0.96);
        }
    }

    @media (prefers-reduced-motion: reduce) {

        .light-popup-overlay.lp-popup-show,
        .light-popup-overlay.lp-popup-show .light-popup-container,
        .light-popup-overlay.lp-popup-hide,
        .light-popup-overlay.lp-popup-hide .light-popup-container {
            animation: none !important;
        }

        .light-popup-overlay,
        .light-popup-container {
            opacity: 1;
            transform: none;
        }
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .light-popup-overlay {
            padding: 0.3rem;
        }

        .light-popup-container {
            max-width: 95%;
            max-height: 92vh;
            overflow-y: auto;
        }

        .light-popup-header {
            padding: 8px 12px;
        }

        .light-popup-title {
            font-size: 0.95rem;
        }

        .light-popup-body {
            padding: 10px 15px;
        }

        .lp-content p {
            font-size: 0.8rem;
            margin: 5px 0;
            line-height: 1.4;
        }

        .lp-content strong {
            font-size: 0.8rem;
        }

        .lp-gif-fire {
            width: 18px;
            height: 18px;
        }

        .lp-gif-sale {
            width: 16px;
            height: 16px;
        }

        .lp-gifs-row {
            gap: 5px;
            flex-wrap: wrap;
            margin-bottom: 6px;
        }

        .lp-gifs-row span {
            font-size: 0.7rem;
            line-height: 1.2;
        }

        .lp-policy-row {
            gap: 4px;
            padding: 5px 8px;
            flex-wrap: wrap;
            margin-top: 8px !important;
        }

        .lp-policy-row span {
            font-size: 0.7rem;
        }

        .lp-promo-box {
            padding: 6px 10px;
            margin: 8px 0;
        }

        .lp-btn-close-2h {
            width: 100%;
            padding: 8px 12px;
            font-size: 0.8rem;
        }

        .lp-popup-image {
            max-height: 280px;
        }

        .lp-footer-action {
            margin-top: 10px;
            padding-top: 10px;
        }

        .lp-mt-2 {
            margin-top: 8px !important;
        }

        .lp-text-muted {
            font-size: 0.7rem;
            margin-top: 3px;
        }
    }

    @media (max-width: 480px) {
        .light-popup-overlay {
            padding: 0.2rem;
        }

        .light-popup-container {
            max-width: 98%;
            border-radius: 6px;
            max-height: 94vh;
        }

        .light-popup-header {
            padding: 6px 10px;
        }

        .light-popup-title {
            font-size: 0.85rem;
        }

        .light-popup-close {
            width: 24px;
            height: 24px;
            font-size: 0.75rem;
        }

        .light-popup-body {
            padding: 8px 12px;
        }

        .lp-content p {
            font-size: 0.72rem;
            line-height: 1.3;
            margin: 4px 0;
        }

        .lp-content strong {
            font-size: 0.72rem;
        }

        .lp-gif-fire {
            width: 14px;
            height: 14px;
        }

        .lp-gif-sale {
            width: 14px;
            height: 14px;
        }

        .lp-gifs-row {
            margin-bottom: 4px;
            gap: 4px;
        }

        .lp-gifs-row span {
            font-size: 0.65rem;
        }

        .lp-policy-row {
            padding: 4px 6px;
            gap: 3px;
        }

        .lp-policy-row span {
            font-size: 0.65rem;
        }

        .lp-mt-2 {
            margin-top: 6px !important;
        }

        .lp-btn-close-2h {
            padding: 7px 10px;
            font-size: 0.75rem;
        }

        .lp-popup-image {
            max-height: 220px;
            border-radius: 6px;
        }

        .lp-text-muted {
            font-size: 0.65rem;
        }

        .lp-footer-action {
            margin-top: 8px;
            padding-top: 8px;
        }
    }

    @media (max-width: 360px) {
        .light-popup-title {
            font-size: 0.8rem;
        }

        .lp-content p {
            font-size: 0.68rem;
        }

        .lp-gif-fire {
            width: 12px;
            height: 12px;
        }

        .lp-gifs-row span {
            font-size: 0.6rem;
        }

        .lp-btn-close-2h {
            font-size: 0.7rem;
            padding: 6px 10px;
        }
    }
</style>

<script>
    (function () {
        const popupKey = 'kaishop_popup_closed_until';

        // Check local storage for timestamp
        const closedUntil = localStorage.getItem(popupKey);
        if (closedUntil && new Date().getTime() < parseInt(closedUntil)) {
            return; // Still within closed period
        }

        // Show popup with animation
        const overlay = document.getElementById('notification-popup-overlay');
        if (!overlay) return;
        overlay.style.display = 'flex';
        overlay.classList.remove('lp-popup-hide');
        requestAnimationFrame(() => {
            overlay.classList.add('lp-popup-show');
        });
    })();

    function closeNotificationPopup() {
        const overlay = document.getElementById('notification-popup-overlay');
        if (!overlay || overlay.classList.contains('lp-popup-hide')) return;
        overlay.classList.remove('lp-popup-show');
        overlay.classList.add('lp-popup-hide');
        setTimeout(() => {
            overlay.style.display = 'none';
            overlay.classList.remove('lp-popup-hide');
        }, 220);
    }

    function closePopupFor2Hours() {
        const popupKey = 'kaishop_popup_closed_until';

        // Set expiry 2 hours from now
        const expiry = new Date().getTime() + (2 * 60 * 60 * 1000);
        localStorage.setItem(popupKey, expiry.toString());

        closeNotificationPopup();
    }
</script>
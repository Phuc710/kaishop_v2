<script>
    document.addEventListener("DOMContentLoaded", function () {
        const filterButtons = document.querySelectorAll('.service-filter-btn');
        const gridItems = document.querySelectorAll('.grid-item');
        filterButtons.forEach(button => {
            button.addEventListener('click', function () {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                const filterValue = this.getAttribute('data-filter');
                gridItems.forEach(item => {
                    if (filterValue === '.category1' || item.classList.contains(filterValue.replace('.', ''))) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    });
</script>
<script>
    $(function () {
        $("img.lazyLoad").lazyload({
            effect: "fadeIn"
        });
    });
    function displayStars(averageRating) {
        const starsContainer = document.querySelector('.rating');
        const averageRatingElement = document.getElementById('averageRating');
        const roundedRating = Math.round(averageRating);

        averageRatingElement.textContent = averageRating.toFixed(1);

        const allStars = starsContainer.querySelectorAll('input[name="rating"]');
        allStars.forEach(star => (star.checked = false));

        const selectedStar = starsContainer.querySelector(`#stars${roundedRating}`);
        if (selectedStar) {
            selectedStar.checked = true;
        }
    }
    $(document).ready(function () {
        $('.service-filter-btn').on('click', function () {
            $('#loading-indicator').addClass('show');
            setTimeout(function () {
                $('#loading-indicator').removeClass('show');
            }, 300);
        });
    });
</script>
<script>
    function openModal(modalId) {
        var modal = document.getElementById(modalId);
        modal.classList.remove('hidden');
    }

    function closeModal(modalId) {
        var modal = document.getElementById(modalId);
        modal.classList.add('hidden');
    }

    window.gtranslateSettings = {
        "default_language": "vi",
        "detect_browser_language": true,
        "languages": ["vi", "en", "ru", "th", "km", "lo", "id", "fr", "de", "ja", "pt", "ko"],
        "wrapper_selector": ".gtranslate_wrapper"
    }
</script>
<script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>

<?php include_once(__DIR__ . '/popup.php'); ?>

<style>
/* Premium Footer Styling */
.premium-footer {
    background: linear-gradient(to right, #0a0f1c, #1a1f33);
    color: #a0aec0;
    padding-top: 80px;
    font-family: 'Inter', 'Segoe UI', sans-serif;
    position: relative;
    overflow: hidden;
}

.premium-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #ff6900, #ff9100, #ff6900);
    background-size: 200% auto;
    animation: gradientShift 3s linear infinite;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    100% { background-position: 100% 50%; }
}

.premium-footer .footer-glow {
    position: absolute;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(255,105,0,0.05) 0%, rgba(0,0,0,0) 70%);
    top: -200px;
    left: -200px;
    border-radius: 50%;
    pointer-events: none;
}

.premium-footer .footer-glow-right {
    position: absolute;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(55,114,255,0.05) 0%, rgba(0,0,0,0) 70%);
    bottom: -150px;
    right: -150px;
    border-radius: 50%;
    pointer-events: none;
}

.premium-footer-top {
    position: relative;
    z-index: 1;
    padding-bottom: 50px;
}

.premium-footer .footer-widget h3 {
    color: #ffffff;
    font-size: 1.15rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    position: relative;
    display: inline-block;
}

.premium-footer .footer-widget h3::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -8px;
    width: 40px;
    height: 2px;
    background: #ff6900;
    border-radius: 2px;
    transition: width 0.3s ease;
}

.premium-footer .footer-widget:hover h3::after {
    width: 100%;
}

.premium-footer .menu-items {
    list-style: none;
    padding: 0;
    margin: 0;
}

.premium-footer .menu-items li {
    margin-bottom: 0.8rem;
    transition: transform 0.2s ease;
}

.premium-footer .menu-items li a {
    color: #a0aec0;
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.premium-footer .menu-items li a::before {
    content: '\f105';
    font-family: 'Font Awesome 5 Free', 'Font Awesome 6 Free';
    font-weight: 900;
    margin-right: 8px;
    font-size: 0.8rem;
    color: transparent;
    transition: all 0.3s ease;
    transform: translateX(-10px);
    opacity: 0;
}

.premium-footer .menu-items li:hover a::before {
    color: #ff6900;
    transform: translateX(0);
    opacity: 1;
}

.premium-footer .menu-items li:hover a {
    color: #ffffff;
    transform: translateX(5px);
}

.premium-footer .footer-logo-area p {
    line-height: 1.7;
    margin-top: 1.2rem;
    font-size: 0.95rem;
}

.premium-footer .social-buttons {
    display: flex;
    gap: 12px;
    margin-top: 1.5rem;
}

.premium-footer .social-btn {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 1.1rem;
    text-decoration: none;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.premium-footer .social-btn::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 0;
}

.premium-footer .social-btn.facebook::before { background: #1877f2; }
.premium-footer .social-btn.telegram::before { background: #0088cc; }
.premium-footer .social-btn.tiktok::before { background: #000000; }
.premium-footer .social-btn.youtube::before { background: #ff0000; }

.premium-footer .social-btn:hover {
    transform: translateY(-5px);
    border-color: transparent;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.premium-footer .social-btn:hover i {
    z-index: 1;
    color: #ffffff;
    transform: scale(1.1);
}

.premium-footer .social-btn:hover::before {
    opacity: 1;
}

.premium-footer .social-btn i {
    z-index: 1;
    transition: transform 0.3s ease;
}

.pf-contact-box {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 2rem;
    backdrop-filter: blur(10px);
}

.pf-contact-item {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.pf-contact-item:last-child {
    margin-bottom: 0;
}

.pf-contact-icon {
    width: 40px;
    height: 40px;
    background: rgba(255,105,0,0.1);
    color: #ff6900;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-right: 15px;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.pf-contact-item:hover .pf-contact-icon {
    background: #ff6900;
    color: #ffffff;
    transform: rotate(5deg) scale(1.1);
}

.pf-contact-info h6 {
    color: #ffffff;
    font-size: 0.9rem;
    margin: 0 0 3px 0;
}

.pf-contact-info p {
    color: #a0aec0;
    margin: 0;
    font-size: 0.95rem;
    font-weight: 500;
}

.premium-footer-bottom {
    background: #060a12;
    padding: 1.5rem 0;
    position: relative;
    z-index: 1;
    border-top: 1px solid rgba(255,255,255,0.05);
}

.pf-bottom-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.pf-copyright {
    display: flex;
    align-items: center;
    color: #a0aec0;
    font-size: 0.9rem;
}

.pf-copyright span {
    color: #ffffff;
    font-weight: 600;
}

.pf-bottom-links {
    display: flex;
    gap: 20px;
    list-style: none;
    margin: 0; padding: 0;
}

.pf-bottom-links a {
    color: #a0aec0;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.pf-bottom-links a:hover {
    color: #ff6900;
}

.pf-badge {
    background: linear-gradient(135deg, rgba(255,105,0,0.1), rgba(255,105,0,0.02));
    border: 1px solid rgba(255,105,0,0.2);
    color: #ff6900;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 1rem;
    box-shadow: 0 4px 15px rgba(255,105,0,0.05);
}

.pf-badge i {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.2); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}

@media (max-width: 991px) {
    .premium-footer {
        padding-top: 60px;
    }
    .footer-widget {
        margin-bottom: 2.5rem;
    }
    .pf-bottom-flex {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    .pf-bottom-links {
        justify-content: center;
    }
}
</style>

<!-- Premium Footer -->
<footer class="premium-footer">
    <div class="footer-glow"></div>
    <div class="footer-glow-right"></div>
    
    <div class="premium-footer-top">
        <div class="container">
            <div class="row">
                <!-- Branding & About -->
                <div class="col-xl-4 col-lg-4 col-md-12 mb-5 mb-lg-0">
                    <div class="footer-widget footer-logo-area pr-lg-4" style="text-align: center;">
                        <?php global $chungapi; ?>
                        <a href="<?= url('') ?>" class="d-inline-block">
                            <img src="<?= asset($chungapi['logo_footer'] ?? $chungapi['logo'] ?? ''); ?>" style="max-height: 50px; filter: drop-shadow(0 0 10px rgba(255,255,255,0.1));" alt="KaiShop">
                        </a>
                        <p>
                            <?= !empty($chungapi['mo_ta']) ? htmlspecialchars($chungapi['mo_ta']) : 'Hệ thống cung cấp Source Code, Tài khoản MMO, Công cụ và Dịch vụ chất lượng cao với độ tin cậy tuyệt đối.'; ?>
                        </p>
                        
                        <div class="pf-badge">
                            <i class="fa-solid fa-bolt"></i> Thanh toán tự động &bull; Hỗ trợ 24/7
                        </div>
                        
                        <div class="social-buttons">
                            <?php if (!empty($chungapi['fb_admin'])): ?>
                                <a href="<?= htmlspecialchars($chungapi['fb_admin']); ?>" target="_blank" class="social-btn facebook" aria-label="Facebook">
                                    <i class="fa-brands fa-facebook-f"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($chungapi['tele_admin'])): ?>
                                <a href="<?= htmlspecialchars($chungapi['tele_admin']); ?>" target="_blank" class="social-btn telegram" aria-label="Telegram">
                                    <i class="fa-brands fa-telegram-plane"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($chungapi['tiktok_admin'])): ?>
                                <a href="<?= htmlspecialchars($chungapi['tiktok_admin']); ?>" target="_blank" class="social-btn tiktok" aria-label="TikTok">
                                    <i class="fa-brands fa-tiktok"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($chungapi['youtube_admin'])): ?>
                                <a href="<?= htmlspecialchars($chungapi['youtube_admin']); ?>" target="_blank" class="social-btn youtube" aria-label="YouTube">
                                    <i class="fa-brands fa-youtube"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Featured Categories -->
                <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6 mb-4 mb-md-0">
                    <div class="footer-widget">
                        <h3>Danh mục nổi bật</h3>
                        <ul class="menu-items">
                            <?php
                            $footer_categories = [];
                            try {
                                $db = class_exists('Database') ? Database::getInstance()->getConnection() : null;
                                if ($db instanceof PDO) {
                                    $stmt = $db->prepare("SELECT * FROM categories WHERE status = ? ORDER BY display_order ASC, id ASC LIMIT 5");
                                    $stmt->execute(['ON']);
                                    $footer_categories = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                                } else {
                                    global $connection;
                                    $footer_categories = $connection->query("SELECT * FROM categories WHERE status = 'ON' LIMIT 5")->fetch_all(MYSQLI_ASSOC);
                                }
                            } catch (Throwable $e) {
                                $footer_categories = [];
                            }
                            if (count($footer_categories) > 0) {
                                foreach ($footer_categories as $cat):
                                    ?>
                                    <li>
                                        <a href="<?= url('category/' . xoadau($cat['name'])) ?>">
                                            <?= htmlspecialchars($cat['name']); ?>
                                        </a>
                                    </li>
                                    <?php
                                endforeach;
                            } else {
                                echo '<li><a href="javascript:void(0)">Đang cập nhật...</a></li>';
                            }
                            ?>
                        </ul>
                    </div>
                </div>

                <!-- Customer Support -->
                <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6 mb-4 mb-md-0">
                    <div class="footer-widget">
                        <h3>Hỗ trợ khác</h3>
                        <ul class="menu-items">
                            <li><a href="<?= url('chinh-sach') ?>">Chính sách chung</a></li>
                            <li><a href="<?= url('dieu-khoan') ?>">Điều khoản sử dụng</a></li>
                            <li><a href="javascript:void(0)" onclick="SwalHelper.toast('Tính năng đang phát triển','info')">Giải đáp thắc mắc</a></li>
                            <li><a href="<?= !empty($chungapi['tele_admin']) ? htmlspecialchars($chungapi['tele_admin']) : 'javascript:void(0)' ?>" target="_blank">Liên hệ tư vấn</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Contact Box -->
                <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12">
                    <div class="footer-widget">
                        <h3>Thông tin liên hệ</h3>
                        <p class="mb-0" style="color: #a0aec0; font-size: 0.95rem;">Bạn cần hỗ trợ? Hãy liên hệ với chúng tôi qua các kênh dưới đây.</p>
                        
                        <div class="pf-contact-box">
                            <div class="pf-contact-item">
                                <div class="pf-contact-icon">
                                    <i class="fa-solid fa-phone-volume"></i>
                                </div>
                                <div class="pf-contact-info">
                                    <h6>Hotline</h6>
                                    <p><?= htmlspecialchars($chungapi['sdt_admin'] ?? 'Đang cập nhật'); ?></p>
                                </div>
                            </div>
                            
                            <div class="pf-contact-item mt-3">
                                <div class="pf-contact-icon">
                                    <i class="fa-solid fa-envelope-open-text"></i>
                                </div>
                                <div class="pf-contact-info">
                                    <h6>Email Support</h6>
                                    <p><?= htmlspecialchars($chungapi['email_cf'] ?? 'Đang cập nhật'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <div class="premium-footer-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0 text-center text-md-start">
                    <div class="pf-copyright">
                        <i class="fa-regular fa-copyright me-2"></i> <?= date('Y') ?> &nbsp;<span><?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop'); ?></span>. All rights reserved.
                    </div>
                </div>
                
                <div class="col-md-6">
                    <ul class="pf-bottom-links justify-content-center justify-content-md-end">
                        <li><a href="<?= url('chinh-sach') ?>">Privacy Policy</a></li>
                        <li><a href="<?= url('dieu-khoan') ?>">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>

<div class="back-to-top">
    <a class="back-to-top-icon align-items-center justify-content-center d-flex" href="#top">
        <img src="<?= asset('assets/images/arrow-badge-up.svg') ?>" alt="img">
    </a>
</div>

<?php if (empty($_SESSION['admin'])): ?>
    <div id="maintenanceNoticeBanner" class="maintenance-notice-banner" hidden>
        <div class="maintenance-notice-banner__icon">
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>
        <div class="maintenance-notice-banner__content">
            <div class="maintenance-notice-banner__title">Sắp bảo trì hệ thống</div>
            <div id="maintenanceNoticeText" class="maintenance-notice-banner__text">
                Hệ thống sẽ bảo trì sau 05:00. Vui lòng hoàn tất thao tác đang thực hiện.
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$pageAssetFlagsResolved = isset($GLOBALS['pageAssetFlagsResolved']) && is_array($GLOBALS['pageAssetFlagsResolved'])
    ? $GLOBALS['pageAssetFlagsResolved']
    : ['interactive_bundle' => true];
$loadInteractiveBundle = !empty($pageAssetFlagsResolved['interactive_bundle']);
?>
<?php if ($loadInteractiveBundle): ?>
    <script src="<?= asset('assets/js/owl_carousel.js') ?>"></script>
    <script src="<?= asset('assets/js/jquery_migrate.js') ?>"></script>
    <script src="<?= asset('assets/js/counterup.js') ?>"></script>
    <script src="<?= asset('assets/js/waypoints.js') ?>"></script>
    <script src="<?= asset('assets/js/nice_select.js') ?>"></script>
    <script src="<?= asset('assets/js/isotope.js') ?>"></script>
    <script src="<?= asset('assets/js/imagesloaded.js') ?>"></script>
    <script src="<?= asset('assets/js/aos.js') ?>"></script>
    <script src="<?= asset('assets/js/quill.js') ?>"></script>
    <script src="<?= asset('assets/js/glightbox.js') ?>"></script>
<?php endif; ?>
<!-- Popper -->
<script src="<?= asset('assets/js/popper.js') ?>"></script>
<!-- Bootstrap -->
<script src="<?= asset('assets/js/bootstrap.js') ?>"></script>
<?php if ($loadInteractiveBundle): ?>
    <script src="<?= asset('assets/js/swiper.js') ?>"></script>
    <script src="<?= asset('assets/js/script.js?khangapi=') ?><?= time() ?>"></script>
    <script src="<?= asset('assets/js/main.js') ?>"></script>
<?php endif; ?>
<script src="<?= asset('assets/js/clipboard.js') ?>"></script>

<script>
    // Global Sticky Menu & Back to top
    function initGlobalStickyAndTop() {
        var header = document.querySelector(".header-primary");
        if (header) {
            window.addEventListener("scroll", function () {
                if (window.scrollY > 100) {
                    header.classList.add("sticky");
                } else {
                    header.classList.remove("sticky");
                }
            });
        }

        var toTopBtn = document.getElementById("toTopBtn");
        if (toTopBtn) {
            window.addEventListener("scroll", function () {
                if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                    toTopBtn.style.display = "block";
                } else {
                    toTopBtn.style.display = "none";
                }
            });
            toTopBtn.addEventListener("click", function () {
                window.scrollTo({ top: 0, behavior: "smooth" });
            });
        }
    }
    document.addEventListener("DOMContentLoaded", initGlobalStickyAndTop);
</script>

<script>
    var o = new ClipboardJS(".copy");
    o.on("success", function (e) {
        SwalHelper.toast('Sao chép thành công', 'success');
    });
    o.on("error", function (e) {
        SwalHelper.toast('Sao chép thất bại', 'error');
    });
</script>

<?php if (empty($_SESSION['admin'])): ?>
    <script>
        (function () {
            const banner = document.getElementById('maintenanceNoticeBanner');
            const noticeText = document.getElementById('maintenanceNoticeText');
            const statusUrl = '<?= url('api/system/maintenance-status') ?>';
            const maintenanceUrl = '<?= url('bao-tri') ?>';

            if (!banner || !noticeText) {
                return;
            }

            let nextPollDelay = 15000;
            let timerId = null;

            function normalizedPath(value) {
                const raw = String(value || '/').replace(/\/+$/, '');
                return raw === '' ? '/' : raw;
            }

            function formatSeconds(totalSeconds) {
                const sec = Math.max(0, Number(totalSeconds || 0));
                const minutes = Math.floor(sec / 60);
                const seconds = sec % 60;
                return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            }

            function hideBanner() {
                banner.hidden = true;
                banner.classList.remove('is-visible');
            }

            function showBanner(secondsLeft) {
                noticeText.textContent = 'Hệ thống sẽ bảo trì sau ' + formatSeconds(secondsLeft) + '. Vui lòng hoàn tất thao tác đang thực hiện.';
                banner.hidden = false;
                banner.classList.add('is-visible');
            }

            function redirectMaintenance() {
                const currentPath = normalizedPath(window.location.pathname);
                const targetPath = normalizedPath(new URL(maintenanceUrl, window.location.origin).pathname);
                if (currentPath !== targetPath) {
                    window.location.href = maintenanceUrl;
                }
            }

            function scheduleNext() {
                window.clearTimeout(timerId);
                timerId = window.setTimeout(pollStatus, nextPollDelay);
            }

            function pollStatus() {
                fetch(statusUrl, { credentials: 'same-origin', cache: 'no-store' })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        const m = data && data.maintenance ? data.maintenance : null;

                        if (!m) {
                            hideBanner();
                            nextPollDelay = 15000;
                            scheduleNext();
                            return;
                        }

                        if (m.active) {
                            hideBanner();
                            redirectMaintenance();
                            return;
                        }

                        if (m.notice_active) {
                            showBanner(m.notice_seconds_left || 0);
                            nextPollDelay = 3000;
                        } else {
                            hideBanner();
                            nextPollDelay = 15000;
                        }

                        scheduleNext();
                    })
                    .catch(function () {
                        nextPollDelay = 15000;
                        scheduleNext();
                    });
            }

            pollStatus();
        })();
    </script>
<?php endif; ?>
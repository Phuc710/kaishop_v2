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

    document.addEventListener("DOMContentLoaded", function () {
        if (!document.querySelector(window.gtranslateSettings.wrapper_selector)) {
            var fallbackWrapper = document.createElement("div");
            fallbackWrapper.className = window.gtranslateSettings.wrapper_selector.replace('.', '');
            fallbackWrapper.style.display = "none";
            document.body.appendChild(fallbackWrapper);
        }
    });
</script>
<script>
    (function () {
        var loaded = false;
        function loadGTranslate() {
            if (loaded) return;
            loaded = true;
            var script = document.createElement('script');
            script.src = 'https://cdn.gtranslate.net/widgets/latest/float.js';
            script.defer = true;
            script.async = true;
            document.body.appendChild(script);
        }

        function scheduleLoad() {
            if ('requestIdleCallback' in window) {
                requestIdleCallback(loadGTranslate, { timeout: 2500 });
            } else {
                setTimeout(loadGTranslate, 1200);
            }
        }

        if (document.readyState === 'complete') {
            scheduleLoad();
        } else {
            window.addEventListener('load', scheduleLoad, { once: true });
        }
    })();
</script>

<?php include_once(__DIR__ . '/popup.php'); ?>


<style>
    :root {
        --secondary-color: #64748B;
    }
    .text-decoration-none  {
        color: var(--secondary-color);
        font-weight: 400;
        
    }
</style>

<!-- Footer -->
<footer class="kai-glass-footer">
    <!-- Top CTA Banner -->
    

    <div class="container footer-main-content">
        <div class="row">
            <!-- Column 1: Brand -->
            <div class="col-12 col-md-6 col-lg-3 footer-col mb-4 mb-lg-0">
                <a href="<?= url('') ?>" class="d-inline-block mb-3 footer-logo">
                    <img src="<?= $chungapi['logo_footer'] ?? $chungapi['logo']; ?>" height="40" alt="KaiShop">
                </a>
                <p class="footer-about-text mb-3">
                    <?= !empty($chungapi['mo_ta']) ? htmlspecialchars($chungapi['mo_ta']) : 'Empowering global brands through high-end technology, innovative strategy, and creative design excellence.'; ?>
                </p>
                <div class="footer-social-icons">
                    <?php if (!empty($chungapi['fb_admin'])): ?>
                        <a href="<?= htmlspecialchars($chungapi['fb_admin']); ?>" target="_blank" class="social-icon" aria-label="Facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($chungapi['tele_admin'])): ?>
                        <a href="<?= htmlspecialchars($chungapi['tele_admin']); ?>" target="_blank" class="social-icon" aria-label="Telegram">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($chungapi['tiktok_admin'])): ?>
                        <a href="<?= htmlspecialchars($chungapi['tiktok_admin']); ?>" target="_blank" class="social-icon" aria-label="TikTok">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($chungapi['youtube_admin'])): ?>
                        <a href="<?= htmlspecialchars($chungapi['youtube_admin']); ?>" target="_blank" class="social-icon" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Column 2: Core Services -->
            <div class="col-12 col-md-6 col-lg-3 footer-col mb-4 mb-lg-0">
                <h4 class="footer-col-title">Core Services</h4>
                <ul class="footer-links">
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
                            <li><a href="<?= url('category/' . xoadau($cat['name'])) ?>">
                                <i class="fas fa-check-circle me-2 icon-accent"></i><?= htmlspecialchars($cat['name']); ?>
                            </a></li>
                            <?php
                        endforeach;
                    } else {
                        echo '<li><a href="javascript:void(0)"><i class="fas fa-check-circle me-2 icon-accent"></i>Đang cập nhật...</a></li>';
                    }
                    ?>
                </ul>
            </div>

            <!-- Column 3: Company -->
            <div class="col-12 col-md-6 col-lg-3 footer-col mb-4 mb-lg-0">
                <h4 class="footer-col-title">Company</h4>
                <ul class="footer-links">
                    <li><a href="<?= url('chinh-sach') ?>"><span class="dot-accent"></span> Chính sách & Quy định</a></li>
                    <li><a href="<?= url('dieu-khoan') ?>"><span class="dot-accent"></span> Điều khoản sử dụng</a></li>
                    <li><a href="<?= !empty($chungapi['tele_admin']) ? htmlspecialchars($chungapi['tele_admin']) : 'javascript:void(0)' ?>" target="_blank"><span class="dot-accent"></span> Liên hệ với chúng tôi</a></li>
                </ul>
            </div>

            <!-- Column 4: Get in Touch -->
            <div class="col-12 col-md-6 col-lg-3 footer-col">
                <h4 class="footer-col-title">Get in Touch</h4>
                <div class="contact-pills-container mt-3">
                    <a href="mailto:<?= htmlspecialchars($chungapi['email_cf']); ?>" class="contact-pill">
                        <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                        <div class="contact-text">
                            <span class="d-block small text-muted-blue">Email Us</span>
                            <strong><?= htmlspecialchars($chungapi['email_cf']); ?></strong>
                        </div>
                    </a>
                    <a href="tel:<?= htmlspecialchars($chungapi['sdt_admin']); ?>" class="contact-pill mt-3">
                        <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="contact-text">
                            <span class="d-block small text-muted-blue">Call Us</span>
                            <strong><?= htmlspecialchars($chungapi['sdt_admin']); ?></strong>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="footer-bottom-bar">
        <div class="container border-top-glass">
            <div class="row pt-4 pb-4 align-items-center">
                <div class="col-md-5 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-0 " style="color: var(--secondary-color);">&copy; <?= date('Y') ?> <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop'); ?></p>
                </div>
                <div class="col-md-7">
                    <div class="d-flex flex-wrap justify-content-center justify-content-md-end align-items-center gap-3 gap-md-4">
                        <a href="<?= url('chinh-sach') ?>" class="text-decoration-none ">Privacy Policy</a>
                        <a href="<?= url('dieu-khoan') ?>" class="text-decoration-none ">Cookie Policy</a>
                        <!-- <div class="system-status d-flex align-items-center gap-2">
                            <span class="status-indicator"></span>
                            <span class="small">Systems Operational</span>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<div class="back-to-top">
    <a id="toTopBtn" class="back-to-top-icon align-items-center justify-content-center d-flex" href="#top">
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
    <script src="<?= asset('assets/js/owl_carousel.js') ?>" defer></script>
    <script src="<?= asset('assets/js/jquery_migrate.js') ?>" defer></script>
    <script src="<?= asset('assets/js/counterup.js') ?>" defer></script>
    <script src="<?= asset('assets/js/waypoints.js') ?>" defer></script>
    <script src="<?= asset('assets/js/nice_select.js') ?>" defer></script>
    <?php if (!empty($pageAssetFlagsResolved['vendor_isotope'])): ?>
        <script src="<?= asset('assets/js/isotope.js') ?>" defer></script>
        <script src="<?= asset('assets/js/imagesloaded.js') ?>" defer></script>
    <?php endif; ?>
    <?php if (!empty($pageAssetFlagsResolved['vendor_aos'])): ?>
        <script src="<?= asset('assets/js/aos.js') ?>" defer></script>
    <?php endif; ?>
    <?php if (!empty($pageAssetFlagsResolved['vendor_quill'])): ?>
        <script src="<?= asset('assets/js/quill.js') ?>" defer></script>
    <?php endif; ?>
    <?php if (!empty($pageAssetFlagsResolved['vendor_glightbox'])): ?>
        <script src="<?= asset('assets/js/glightbox.js') ?>" defer></script>
    <?php endif; ?>
<?php endif; ?>
<!-- Popper -->
<script src="<?= asset('assets/js/popper.js') ?>"></script>
<!-- Bootstrap -->
<script src="<?= asset('assets/js/bootstrap.js') ?>"></script>
<?php if ($loadInteractiveBundle): ?>
    <?php if (!empty($pageAssetFlagsResolved['vendor_swiper'])): ?>
        <script src="<?= asset('assets/js/swiper.js') ?>" defer></script>
    <?php endif; ?>
    <?php
    $publicScriptPath = dirname(__DIR__) . '/assets/js/script.js';
    $publicScriptVer = @filemtime($publicScriptPath) ?: '1';
    ?>
    <script src="<?= asset('assets/js/script.js?v=' . $publicScriptVer) ?>"></script>
    <script src="<?= asset('assets/js/main.js') ?>"></script>
<?php endif; ?>
<script src="<?= asset('assets/js/clipboard.js') ?>" defer></script>
<script src="<?= asset('assets/js/perf-loader.js') ?>" defer></script>

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
                    toTopBtn.classList.add("show");
                } else {
                    toTopBtn.classList.remove("show");
                }
            });
            toTopBtn.addEventListener("click", function (e) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: "smooth" });
            });
        }
    }
    document.addEventListener("DOMContentLoaded", initGlobalStickyAndTop);
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        if (typeof ClipboardJS !== "function" || !document.querySelector(".copy")) {
            return;
        }

        var clipboard = new ClipboardJS(".copy");
        clipboard.on("success", function () {
            SwalHelper.toast('Sao chép thành công', 'success');
        });
        clipboard.on("error", function () {
            SwalHelper.toast('Sao chép thất bại', 'error');
        });
    });
</script>

<?php if (empty($_SESSION['admin'])): ?>
    <script>
        (function () {
            const banner = document.getElementById('maintenanceNoticeBanner');
            const noticeText = document.getElementById('maintenanceNoticeText');
            const statusUrl = '<?= url('api/system/maintenance-status') ?>';
            const maintenanceUrl = '<?= url('bao-tri') ?>';

            if (!banner || !noticeText) return;

            let tickTimerId = null;
            let localSecondsLeft = 0;
            let noticeActive = false;
            let runtime = null;

            function normalizedPath(value) {
                const raw = String(value || '/').replace(/\/+$/, '');
                return raw === '' ? '/' : raw;
            }

            function pad2(n) { return String(Math.max(0, n)).padStart(2, '0'); }

            function formatCountdown(sec) {
                sec = Math.max(0, sec);
                const h = Math.floor(sec / 3600);
                const m = Math.floor((sec % 3600) / 60);
                const s = sec % 60;
                return h > 0
                    ? pad2(h) + ':' + pad2(m) + ':' + pad2(s)
                    : pad2(m) + ':' + pad2(s);
            }

            function hideBanner() {
                banner.hidden = true;
                banner.classList.remove('is-visible', 'is-urgent');
                noticeActive = false;
                window.clearInterval(tickTimerId);
                tickTimerId = null;
            }

            function updateBannerText() {
                if (!noticeActive) return;
                const isUrgent = localSecondsLeft <= 60;
                noticeText.textContent = 'Hệ thống sẽ bảo trì sau ' + formatCountdown(localSecondsLeft) + '. Vui lòng hoàn tất thao tác đang thực hiện.';
                banner.classList.toggle('is-urgent', isUrgent);
                if (localSecondsLeft <= 0) {
                    redirectMaintenance();
                }
            }

            function startTick() {
                if (tickTimerId !== null) return;
                tickTimerId = window.setInterval(function () {
                    if (!noticeActive) { window.clearInterval(tickTimerId); tickTimerId = null; return; }
                    if (localSecondsLeft > 0) { localSecondsLeft--; }
                    updateBannerText();
                }, 1000);
            }

            function showBanner(secondsLeft) {
                localSecondsLeft = Math.max(0, Math.round(secondsLeft));
                noticeActive = true;
                banner.hidden = false;
                banner.classList.add('is-visible');
                updateBannerText();
                startTick();
            }

            function redirectMaintenance() {
                const currentPath = normalizedPath(window.location.pathname);
                const targetPath = normalizedPath(new URL(maintenanceUrl, window.location.origin).pathname);
                if (currentPath !== targetPath) {
                    window.location.href = maintenanceUrl;
                }
            }

            function applyState(payload) {
                const m = payload && payload.state ? payload.state : null;
                if (!m) { hideBanner(); return; }

                if (m.active) { hideBanner(); redirectMaintenance(); return; }

                const noticeSeconds = payload.noticeSecondsLeft;
                const shouldShow = Number.isFinite(Number(noticeSeconds)) && Number(noticeSeconds) > 0;
                if (shouldShow) {
                    showBanner(Number(noticeSeconds));
                } else {
                    hideBanner();
                }
            }

            if (typeof window.KaiMaintenanceRuntime === 'function') {
                runtime = new window.KaiMaintenanceRuntime({
                    statusUrl: statusUrl,
                    pollMs: 3000
                });
                runtime.onUpdate(applyState);
                runtime.start();
            } else {
                // Lightweight fallback when runtime bundle is unavailable
                const pollFallback = function () {
                    fetch(statusUrl, { credentials: 'same-origin', cache: 'no-store' })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            const m = data && data.maintenance ? data.maintenance : null;
                            if (!m) { hideBanner(); return; }
                            if (m.active) { hideBanner(); redirectMaintenance(); return; }
                            if (m.notice_active && Number(m.notice_seconds_left) > 0) {
                                showBanner(Number(m.notice_seconds_left));
                            } else {
                                hideBanner();
                            }
                        })
                        .catch(function () { });
                };
                pollFallback();
                window.setInterval(pollFallback, 3000);
            }
        })();
    </script>
<?php endif; ?>

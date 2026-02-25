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

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-top">
            <div class="row">
                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
                    <div class="footer-widget">
                        <?php global $chungapi; ?>
                        <a href="<?= url('') ?>">
                            <img src="<?= $chungapi['logo_footer'] ?? $chungapi['logo']; ?>" width="150" alt="KaiShop">
                        </a>
                        <p>
                            <?= !empty($chungapi['mo_ta']) ? htmlspecialchars($chungapi['mo_ta']) : 'Hệ thống cung cấp Source Code, Tài khoản MMO, Công cụ và Dịch vụ chất lượng cao.'; ?>
                        </p>
                        <h6 class="mt-3"
                            style="background-color: rgba(255, 105, 0, 0.05); border-radius: 99px; padding: 8px 16px; display: inline-block; color: #ff6900; font-size: 14px; border: 1px solid rgba(255, 105, 0, 0.2);">
                            Thanh toán tự động &bull; Hỗ trợ 24/7</h6>
                        <div class="kai-footer-social mt-3">
                            <div class="social-buttons">
                                <?php if (!empty($chungapi['fb_admin'])): ?>
                                    <a href="<?= htmlspecialchars($chungapi['fb_admin']); ?>" target="_blank"
                                        class="social-btn facebook" aria-label="Facebook">
                                        <i class="fa-brands fa-facebook"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($chungapi['tele_admin'])): ?>
                                    <a href="<?= htmlspecialchars($chungapi['tele_admin']); ?>" target="_blank"
                                        class="social-btn telegram" aria-label="Telegram">
                                        <i class="fab fa-telegram-plane"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($chungapi['tiktok_admin'])): ?>
                                    <a href="<?= htmlspecialchars($chungapi['tiktok_admin']); ?>" target="_blank"
                                        class="social-btn tiktok" aria-label="TikTok">
                                        <i class="fab fa-tiktok"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($chungapi['youtube_admin'])): ?>
                                    <a href="<?= htmlspecialchars($chungapi['youtube_admin']); ?>" target="_blank"
                                        class="social-btn youtube" aria-label="YouTube">
                                        <i class="fab fa-youtube"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                    <div class="footer-widget">
                        <h3>Danh mục nổi bật</h3>
                        <div class="row">
                            <div class="col-md-12">
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
                                            <li><a
                                                    href="<?= url('category/' . xoadau($cat['name'])) ?>"><?= htmlspecialchars($cat['name']); ?></a>
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
                    </div>
                </div>

                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                    <div class="footer-widget">
                        <h3>Hỗ trợ khách hàng</h3>
                        <ul class="menu-items">
                            <li><a href="<?= url('chinh-sach') ?>">Chính sách & Quy định</a></li>
                            <li><a href="<?= url('dieu-khoan') ?>">Điều khoản sử dụng</a></li>
                            <li><a href="<?= !empty($chungapi['tele_admin']) ? htmlspecialchars($chungapi['tele_admin']) : 'javascript:void(0)' ?>"
                                    target="_blank">Liên hệ với chúng tôi</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                    <div class="footer-widget">
                        <h3>Dịch vụ chuyên nghiệp</h3>
                        <ul class="menu-items">
                            <li><a href="javascript:void(0)">Cung cấp Mã Nguồn</a></li>
                            <li><a href="javascript:void(0)">Thiết kế Website</a></li>
                            <li><a href="javascript:void(0)">Dịch vụ MMO</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="contact-widget">
                <div class="row align-items-center">
                    <div class="col-xl-9">
                        <ul class="location-list">
                            <li>
                                <span><i class="fa-solid fa-phone"></i></span>
                                <div class="location-info">
                                    <h6>Phone</h6>
                                    <p><?= $chungapi['sdt_admin']; ?></p>
                                </div>
                            </li>
                            <li>
                                <span><i class="fa-regular fa-envelope"></i></span>
                                <div class="location-info">
                                    <h6>Email</h6>
                                    <p><?= $chungapi['email_cf']; ?></p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="col-xl-3 text-xl-end"></div>
                </div>
            </div>

        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="copy-right">
                        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop'); ?>.
                            All rights reserved.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="footer-bottom-links">
                        <ul>
                            <li><a href="<?= url('chinh-sach') ?>">Chính sách</a></li>
                            <li><a href="<?= url('dieu-khoan') ?>">Điều khoản & Điều kiện</a></li>
                        </ul>
                    </div>
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

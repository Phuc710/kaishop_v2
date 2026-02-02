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
        "native_language_names": true,
        "globe_color": "#66aaff",
        "wrapper_selector": ".gtranslate_wrapper",
        "flag_size": 28,
        "alt_flags": {
            "en": "usa"
        },
        "globe_size": 24
    }
</script>
<script src="https://cdn.gtranslate.net/widgets/latest/globe.js" defer></script>
</body>
<!-- Logout modal removed -->
<!-- Footer  -->
<footer class="footer">
    <div class="container">
        <div class="footer-top">
            <div class="row">
                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
                    <div class="footer-widget">
                        <?php global $chungapi; ?>
                        <a href="<?= url('') ?>">
                            <img src="<?= $chungapi['logo']; ?>" width="150" alt="dailycode.vn">
                        </a>
                        <p>Dịch vụ thiết kế website theo yêu cầu, mua bán mã nguồn, dịch vụ uy tín, hỗ trợ nhiệt tình.
                            Đội ngũ chăm sóc khách hàng 24/24</p>
                        <div class="social-links">
                            <ul>
                                <li><a href="javascript:void(0);"><i class="fa-brands fa-facebook"></i></a></li>
                                <li><a href="javascript:void(0);"><i class="fa-brands fa-x-twitter"></i></a></li>
                                <li><a href="javascript:void(0);"><i class="fa-brands fa-instagram"></i></a></li>
                                <li><a href="javascript:void(0);"><i class="fa-brands fa-google"></i></a></li>
                                <li><a href="javascript:void(0);"><i class="fa-brands fa-youtube"></i></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                    <div class="footer-widget">
                        <h3>Danh mục nổi bật</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="menu-items">
                                    <li><a href="<?= url('ma-nguon') ?>">Mã Nguồn</a></li>
                                    <li><a href="<?= url('server-hosting') ?>">Hosting việt nam</a></li>
                                    <li><a href="<?= url('tao-logo') ?>">Thiết kế logo</a></li>
                                    <li><a href="<?= url('mua-mien') ?>">Mua tên miền</a></li>
                                </ul>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                    <div class="footer-widget">
                        <h3>Dịch vụ khác</h3>
                        <ul class="menu-items">
                            <li><a href="https://cron.dailycode.vn">Cronjob</a></li>

                        </ul>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                    <div class="footer-widget">
                        <h3>Thể loại blog</h3>
                        <ul class="menu-items">
                            <li><a href="<?= url('') ?>">Tin tức</a></li>
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
                                    <p> <?= $chungapi['sdt_admin']; ?> </p>
                                </div>
                            </li>
                            <li>
                                <span><i class="fa-regular fa-envelope"></i></span>
                                <div class="location-info">
                                    <h6>Email</h6>
                                    <p> <?= $chungapi['email_cf']; ?> </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="col-xl-3 text-xl-end">

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="copy-right">
                        <p>Copyright 2025, All Rights Reserved | Software By <?= $chungapi['ten_web']; ?> </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="footer-bottom-links">
                        <ul>
                            <li><a href="<?= url('chinh-sach') ?>">Chính sách</a></li>
                            <li><a href="<?= url('') ?>">Điều khoản & Điều kiện</a></li>
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



<script data-cfasync="false"
    src="<?= asset('cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js') ?>"></script>
<script src="<?= asset('assets/js/owl.carousel.min.js') ?>"></script>
<script src="<?= asset('assets/plugins/slick/slick.js') ?>"></script>
<script src="<?= asset('assets/js/script.js?khangapi=') ?><?= time() ?>"></script>
<!-- Migrate  -->
<script src="<?= asset('assets/js/jquery-migrate.min.js') ?>"></script>
<!-- CounterUp  -->
<script src="<?= asset('assets/js/jquery.counterup.min.js') ?>"></script>
<!-- Waypoint -->
<script src="<?= asset('assets/js/waypoints.min.js') ?>"></script>
<!-- Nice Select -->
<script src="<?= asset('assets/js/jquery.nice-select.min.js') ?>"></script>
<!-- Isotope -->
<script src="<?= asset('assets/js/isotope.pkgd.min.js') ?>"></script>
<!-- ImgLoaded -->
<script src="<?= asset('assets/js/imagesloaded.pkgd.min.js') ?>"></script>
<!-- AOS -->
<script src="<?= asset('assets/js/aos.js') ?>"></script>
<!-- Quill Editor -->
<script src="<?= asset('assets/js/quill.js') ?>"></script>
<!-- GLightBox -->
<script src="<?= asset('assets/js/glightbox.min.js') ?>"></script>
<!-- Popper -->
<script src="<?= asset('assets/js/popper.min.js') ?>"></script>
<!-- Bootstrap -->
<script src="<?= asset('assets/js/bootstrap.bundle.min.js') ?>"></script>
<!-- Swiper -->
<script src="<?= asset('assets/js/swiper-bundle.min.js') ?>"></script>
<!-- Main -->
<script src="<?= asset('assets/js/main.js') ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.11/clipboard.min.js"></script>
<script>
    function showMessage(message, type) {
        alert(message);
    }

    var o = new ClipboardJS(".copy");
    o.on("success", function (e) {
        showMessage('Sao Chép Thành Công', 'success');
    });
    o.on("error", function (e) {
        showMessage('Sao Chép Thất Bại', 'error');
    });
</script>
</body>

</html>
(function ($) {
    ("use strict");

    // Sticky Menu
    function hasStickyMenu() {
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
    }
    hasStickyMenu();

    function hasBackToTop() {
        // Slide to Top Button
        var toTopBtn = document.getElementById("toTopBtn");
        if (toTopBtn) {
            window.onscroll = function () {
                scrollFunction();
            };

            function scrollFunction() {
                if (
                    document.body.scrollTop > 200 ||
                    document.documentElement.scrollTop > 200
                ) {
                    toTopBtn.style.display = "block";
                } else {
                    toTopBtn.style.display = "none";
                }
            }
            toTopBtn.onclick = function () {
                window.scrollTo({ top: 0, behavior: "smooth" });
            };
        }
    }

    hasBackToTop();

    function QuillExist() {
        const editorOne = document.querySelector("#editor");
        const editorTwo = document.querySelector("#editor-seo");
        if (typeof Quill !== "function") {
            return false;
        }

        // Editor
        var toolbarOptions = [
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            ["bold", "italic", "underline", "link"], // toggled buttons
            [{ list: "ordered" }, { list: "bullet" }],
            [{ align: [] }],
        ];

        if (editorOne) {
            let quillEditorOne = new Quill("#editor", {
                modules: {
                    toolbar: toolbarOptions,
                },
                theme: "snow",
            });
        } else if (editorTwo) {
            let quillEditorTwo = new Quill("#editor-seo", {
                modules: {
                    toolbar: toolbarOptions,
                },
                theme: "snow",
            });
        } else {
            return false;
        }
    }

    QuillExist();

    // Nice Select
    $(".select-dropdown").niceSelect();

    function createSwiperIfExists(selector, options) {
        if (typeof Swiper !== "function") return null;
        if (!document.querySelector(selector)) return null;
        return new Swiper(selector, options);
    }

    createSwiperIfExists(".featureCategory", {
        loop: true,
        slidesPerView: 1,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        spaceBetween: 10,
        navigation: {
            nextEl: ".featureNext",
            prevEl: ".featurePrev",
        },
        breakpoints: {
            640: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 3,
                spaceBetween: 24,
            },
            1200: {
                slidesPerView: 4,
                spaceBetween: 24,
            },
            1400: {
                slidesPerView: 6,
                spaceBetween: 24,
            },
        },
    });

    createSwiperIfExists(".serviceSlider", {
        loop: true,
        slidesPerView: 1,
        spaceBetween: 10,
        autoplay: {
            delay: 2500,
            disableOnInteraction: false,
        },
        navigation: {
            nextEl: ".serviceNext",
            prevEl: ".servicePrev",
        },
        breakpoints: {
            640: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 24,
            },

            1200: {
                slidesPerView: 3,
                spaceBetween: 24,
            },
            1400: {
                slidesPerView: 5,
                spaceBetween: 24,
            },
        },
    });

    createSwiperIfExists(".recentJob", {
        slidesPerView: 1,
        spaceBetween: 10,
        autoplay: {
            delay: 2500,
            disableOnInteraction: false,
        },
        navigation: {
            nextEl: ".recentJobNext",
            prevEl: ".recentJobPrev",
        },
        breakpoints: {
            640: {
                slidesPerView: 1,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 24,
            },
            1200: {
                slidesPerView: 4,
                spaceBetween: 24,
            },
            1400: {
                slidesPerView: 5,
                spaceBetween: 24,
            },
        },
    });

    // Testimonial Slider Top
    createSwiperIfExists(".testimonialsSlider", {
        spaceBetween: 30,
        centeredSlides: true,
        freeMode: true,
        speed: 10000,
        autoplay: {
            delay: 1,
            disableOnInteraction: false,
        },
        loop: true,
        slidesPerView: 1,
        allowTouchMove: false,
        breakpoints: {
            1: {
                slidesPerView: 1.1,
            },
            768: {
                slidesPerView: 2,
            },
            992: {
                slidesPerView: 2.5,
            },
            1200: {
                slidesPerView: 3,
            },
            1400: {
                slidesPerView: 3.5,
            },
            1600: {
                slidesPerView: 4,
            },
            1900: {
                slidesPerView: 4.5,
            },
        },
    });
    // Testimonial Slider Bottom
    createSwiperIfExists(".testimonialsSliderBottom", {
        spaceBetween: 30,
        centeredSlides: true,
        freeMode: true,
        speed: 8000,
        autoplay: {
            delay: 1,
            reverseDirection: true,
            disableOnInteraction: false,
        },
        loop: true,
        slidesPerView: 1,
        allowTouchMove: false,

        breakpoints: {
            1: {
                slidesPerView: 1.1,
            },
            768: {
                slidesPerView: 2,
            },
            992: {
                slidesPerView: 2.5,
            },
            1200: {
                slidesPerView: 3,
            },
            1400: {
                slidesPerView: 3.5,
            },
            1600: {
                slidesPerView: 4,
            },
            1900: {
                slidesPerView: 4.5,
            },
        },
    });

    // Services Details
    createSwiperIfExists(".mySwiper2", {
        loop: true,
        spaceBetween: 25,
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
    });

    // AOS
    if (typeof AOS !== "undefined" && AOS && typeof AOS.init === "function") {
        AOS.init({
            offset: 250,
        });
    }

    //Counter up
    if ($.fn && typeof $.fn.counterUp === "function" && $(".counter").length) {
        $(".counter").counterUp({
            delay: 10,
            time: 1000,
        });
    }

    // Lightbox Gallery
    if (typeof GLightbox === "function") {
        if (document.querySelector(".gallery")) {
            GLightbox({
                selector: ".gallery",
                touchNavigation: true,
                loop: true,
            });
        }

        if (document.querySelector(".video-popup")) {
            GLightbox({
                selector: ".video-popup",
                touchNavigation: true,
                loop: false,
            });
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        var grid = document.querySelector(".grid");

        if (grid && typeof imagesLoaded === "function" && typeof Isotope === "function") {
            imagesLoaded(grid, function () {
                var iso = new Isotope(grid, {
                    itemSelector: ".grid-item",
                    layoutMode: "fitRows",
                    filter: "*",
                });

                var filters = document.querySelector(".filters-btns");
                if (!filters) return;
                filters.addEventListener("click", function (event) {
                    if (!event.target.matches("button")) return;
                    var filterValue = event.target.getAttribute("data-filter");
                    iso.arrange({ filter: filterValue });
                });

                var filterButtons = document.querySelector(".filters-btns");
                if (!filterButtons) return;
                var buttons = filterButtons.querySelectorAll("button");

                buttons.forEach(function (button) {
                    button.addEventListener("click", function () {
                        // Remove active class from all buttons
                        buttons.forEach(function (btn) {
                            btn.classList.remove("active");
                        });

                        // Add active class to clicked button
                        this.classList.add("active");

                        var filterValue = this.getAttribute("data-filter");
                        iso.arrange({ filter: filterValue });
                    });
                });
            });
        }
    });

    //Dashboard Sidebar
    function dashboardSidebar() {
        const links = document.querySelectorAll(".sidebar-nav li a");

        if (links) {
        }
        links.forEach((link) => {
            link.addEventListener("click", function () {
                links.forEach((link) => link.classList.remove("active"));
                this.classList.add("active");
            });
        });
    }
    dashboardSidebar();

    // Payment
    function paymentSelection() {
        const paymentOptions = document.querySelectorAll(
            ".single-payment-option"
        );

        paymentOptions.forEach((option) => {
            option.addEventListener("click", function () {
                // Remove the 'selected' class from all options
                paymentOptions.forEach((opt) =>
                    opt.classList.remove("selected")
                );

                // Add the 'selected' class to the clicked option
                this.classList.add("selected");
            });
        });
    }
    paymentSelection();
})(jQuery, window);

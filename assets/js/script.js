(function($) {
	"use strict";
	var $slimScrolls = $('.slimscroll');
	if ($(window).width() > 767) {
		if ($('.theiaStickySidebar').length > 0) {
			$('.theiaStickySidebar').theiaStickySidebar({
				additionalMarginTop: 30
			});
		}
	}
	var $wrapper = $('.main-wrapper');
	if ($(window).width() <= 991) {
		var Sidemenu = function() {
			this.$menuItem = $('.main-nav a');
		};

		function init() {
			var $this = Sidemenu;
			$('.main-nav a').on('click', function(e) {
				if ($(this).parent().hasClass('has-submenu')) {
					e.preventDefault();
				}
				if (!$(this).hasClass('submenu')) {
					$('ul', $(this).parents('ul:first')).slideUp(350);
					$('a', $(this).parents('ul:first')).removeClass('submenu');
					$(this).next('ul').slideDown(350);
					$(this).addClass('submenu');
				} else if ($(this).hasClass('submenu')) {
					$(this).removeClass('submenu');
					$(this).next('ul').slideUp(350);
				}
			});
		}
		init();
	}
	$(window).scroll(function() {
		var sticky = $('.header'),
			scroll = $(window).scrollTop();
		if (scroll >= 50) sticky.addClass('fixed');
		else sticky.removeClass('fixed');
	});
	$('.header-fixed').append('<div class="sidebar-overlay"></div>');
	$(document).on('click', '#mobile_btn', function() {
		$('main-wrapper').toggleClass('slide-nav');
		$('.sidebar-overlay').toggleClass('opened');
		$('html').addClass('menu-opened');
		return false;
	});
	$(document).on('click', '.sidebar-overlay', function() {
		$('html').removeClass('menu-opened');
		$(this).removeClass('opened');
		$('main-wrapper').removeClass('slide-nav');
		$('#task_window').removeClass('opened');
	});
	$(document).on('click', '#menu_close', function() {
		$('html').removeClass('menu-opened');
		$('.sidebar-overlay').removeClass('opened');
		$('main-wrapper').removeClass('slide-nav');
	});
	$(document).on('click', '#toggle_btn', function() {
		if ($('body').hasClass('mini-sidebar')) {
			$('body').removeClass('mini-sidebar');
			$('.subdrop + ul').slideDown();
		} else {
			$('body').addClass('mini-sidebar');
			$('.subdrop + ul').slideUp();
		}
		return false;
	});
	$(document).on('mouseover', function(e) {
		e.stopPropagation();
		if ($('body').hasClass('mini-sidebar') && $('#toggle_btn').is(':visible')) {
			var targ = $(e.target).closest('.sidebar').length;
			if (targ) {
				$('body').addClass('expand-menu');
				$('.subdrop + ul').slideDown();
			} else {
				$('body').removeClass('expand-menu');
				$('.subdrop + ul').slideUp();
			}
			return false;
		}
	});
	if ($('.main-wrapper .aos').length > 0) {
		AOS.init({
			duration: 1200,
			once: true,
		});
	}
	$('body').append('<div class="sidebar-overlay"></div>');
	$(document).on('click', '#mobile_btns', function() {
		$wrapper.toggleClass('slide-nav');
		$('.sidebar-overlay').toggleClass('opened');
		$('html').toggleClass('menu-opened');
		return false;
	});
	var Sidemenu = function() {
		this.$menuItem = $('#sidebar-menu a');
	};

	function initi() {
		var $this = Sidemenu;
		$('#sidebar-menu a').on('click', function(e) {
			if ($(this).parent().hasClass('submenu')) {
				e.preventDefault();
			}
			if (!$(this).hasClass('subdrop')) {
				$('ul', $(this).parents('ul:first')).slideUp(350);
				$('a', $(this).parents('ul:first')).removeClass('subdrop');
				$(this).next('ul').slideDown(350);
				$(this).addClass('subdrop');
			} else if ($(this).hasClass('subdrop')) {
				$(this).removeClass('subdrop');
				$(this).next('ul').slideUp(350);
			}
		});
		$('#sidebar-menu ul li.submenu a.active').parents('li:last').children('a:first').addClass('active').trigger('click');
	}
	initi();
	if ($slimScrolls.length > 0) {
		$slimScrolls.slimScroll({
			height: 'auto',
			width: '100%',
			position: 'right',
			size: '7px',
			color: '#ccc',
			wheelStep: 10,
			touchScrollStep: 100
		});
		var wHeight = $(window).height();
		$slimScrolls.height(wHeight);
		$('.left-sidebar .slimScrollDiv, .sidebar-menu .slimScrollDiv, .sidebar-menu .slimScrollDiv').height(wHeight);
		$('.right-sidebar .slimScrollDiv').height(wHeight - 30);
		$('.chat .slimScrollDiv').height(wHeight - 70);
		$('.chat.settings-main .slimScrollDiv').height(wHeight);
		$('.right-sidebar.video-right-sidebar .slimScrollDiv').height(wHeight - 90);
		$(window).resize(function() {
			var rHeight = $(window).height();
			$slimScrolls.height(rHeight);
			$('.left-sidebar .slimScrollDiv, .sidebar-menu .slimScrollDiv, .sidebar-menu .slimScrollDiv').height(rHeight);
			$('.right-sidebar .slimScrollDiv').height(wHeight - 30);
			$('.chat .slimScrollDiv').height(rHeight - 70);
			$('.chat.settings-main .slimScrollDiv').height(wHeight);
			$('.right-sidebar.video-right-sidebar .slimScrollDiv').height(wHeight - 90);
		});
	}
	if ($('.gigs-slider').length > 0) {
		$('.gigs-slider').owlCarousel({
			loop: false,
			margin: 24,
			nav: true,
			dots: false,
			smartSpeed: 2000,
			autoplay: false,
			navText: ['<i class="fa-solid fa-chevron-left"></i>', '<i class="fa-solid fa-chevron-right"></i>'],
			navContainer: '.worknav',
			responsive: {
				0: {
					items: 1
				},
				550: {
					items: 1
				},
				768: {
					items: 2
				},
				1000: {
					items: 3
				}
			}
		})
	}
	if ($('.gigs-card-slider').length > 0) {
		$('.gigs-card-slider').owlCarousel({
			loop: false,
			margin: 24,
			nav: true,
			dots: false,
			smartSpeed: 2000,
			autoplay: false,
			navText: ['<i class="fa-solid fa-chevron-left"></i>', '<i class="fa-solid fa-chevron-right"></i>'],
			responsive: {
				0: {
					items: 1
				},
				550: {
					items: 1
				},
				768: {
					items: 2
				},
				1000: {
					items: 3
				}
			}
		})
	}
	if ($('.img-slider').length > 0) {
		$('.img-slider').owlCarousel({
			loop: true,
			margin: 24,
			nav: false,
			dots: true,
			smartSpeed: 2000,
			autoplay: false,
			navText: ['<i class="fa-solid fa-chevron-left"></i>', '<i class="fa-solid fa-chevron-right"></i>'],
			responsive: {
				0: {
					items: 1
				},
				550: {
					items: 1
				},
				768: {
					items: 1
				},
				1000: {
					items: 1
				}
			}
		})
	}
	if ($('.clients-slider').length > 0) {
		$('.clients-slider').owlCarousel({
			loop: true,
			margin: 24,
			nav: false,
			dots: false,
			smartSpeed: 2000,
			autoplay: true,
			navText: ['<i class="fa-solid fa-chevron-left"></i>', '<i class="fa-solid fa-chevron-right"></i>'],
			responsive: {
				0: {
					items: 2
				},
				550: {
					items: 3
				},
				768: {
					items: 5
				},
				1000: {
					items: 5
				}
			}
		})
	}
	if ($('.popular-category-slider').length > 0) {
		$('.popular-category-slider').owlCarousel({
			loop: true,
			margin: 24,
			nav: false,
			dots: true,
			smartSpeed: 2000,
			autoplay: false,
			navText: ['<i class="fa-solid fa-chevron-left"></i>', '<i class="fa-solid fa-chevron-right"></i>'],
			responsive: {
				0: {
					items: 1
				},
				550: {
					items: 2
				},
				768: {
					items: 3
				},
				1000: {
					items: 4
				},
				1200: {
					items: 5
				}
			}
		})
	}
	if ($('.review-slider').length > 0) {
		$('.review-slider').owlCarousel({
			loop: true,
			margin: 24,
			nav: true,
			dots: false,
			smartSpeed: 2000,
			autoplay: false,
			navText: ['<i class="fa-solid fa-chevron-left"></i>', '<i class="fa-solid fa-chevron-right"></i>'],
			responsive: {
				0: {
					items: 1
				},
				550: {
					items: 1
				},
				768: {
					items: 2
				},
				1000: {
					items: 2
				},
				1200: {
					items: 3
				}
			}
		})
	}
	if ($('.blog-carousel').length > 0) {
		$('.blog-carousel').owlCarousel({
			loop: true,
			margin: 24,
			nav: false,
			dots: true,
			smartSpeed: 2000,
			autoplay: false,
			navText: ['<i class="fa-solid fa-chevron-left"></i>', '<i class="fa-solid fa-chevron-right"></i>'],
			responsive: {
				0: {
					items: 1
				},
				550: {
					items: 1
				},
				768: {
					items: 2
				},
				1000: {
					items: 2
				},
				1200: {
					items: 3
				}
			}
		})
	}
	if ($('.team-slider').length > 0) {
		$('.team-slider').owlCarousel({
			loop: false,
			margin: 24,
			nav: false,
			dots: true,
			smartSpeed: 2000,
			autoplay: false,
			responsive: {
				0: {
					items: 1
				},
				550: {
					items: 1
				},
				768: {
					items: 2
				},
				1000: {
					items: 3
				}
			}
		})
	}

	function mim_tm_cursor() {
		var myCursor = jQuery('.mouse-cursor');
		if (myCursor.length) {
			if ($("body")) {
				const e = document.querySelector(".cursor-inner"),
					t = document.querySelector(".cursor-outer");
				let n, i = 0,
					o = !1;
				window.onmousemove = function(s) {
					o || (t.style.transform = "translate(" + s.clientX + "px, " + s.clientY + "px)"), e.style.transform = "translate(" + s.clientX + "px, " + s.clientY + "px)", n = s.clientY, i = s.clientX
				}, $("body").on("mouseenter", "a, .cursor-pointer", function() {
					e.classList.add("cursor-hover"), t.classList.add("cursor-hover")
				}), $("body").on("mouseleave", "a, .cursor-pointer", function() {
					$(this).is("a") && $(this).closest(".cursor-pointer").length || (e.classList.remove("cursor-hover"), t.classList.remove("cursor-hover"))
				}), e.style.visibility = "visible", t.style.visibility = "visible"
			}
		}
	};
	mim_tm_cursor()
	$(window).scroll(function() {
		var scroll = $(window).scrollTop();
		if (scroll >= 500) {
			$(".back-to-top-icon").addClass("show");
		} else {
			$(".back-to-top-icon").removeClass("show");
		}
	});
	if ($('.counter').length > 0) {
		$('.counter').counterUp({
			delay: 10,
			time: 2000
		});
		$('.counter').addClass('animated fadeInDownBig');
	}
	var TxtRotate = function(el, toRotate, period) {
		this.toRotate = toRotate;
		this.el = el;
		this.loopNum = 0;
		this.period = parseInt(period, 10) || 2000;
		this.txt = '';
		this.tick();
		this.isDeleting = false;
	};
	TxtRotate.prototype.tick = function() {
		var i = this.loopNum % this.toRotate.length;
		var fullTxt = this.toRotate[i];
		if (this.isDeleting) {
			this.txt = fullTxt.substring(0, this.txt.length - 1);
		} else {
			this.txt = fullTxt.substring(0, this.txt.length + 1);
		}
		this.el.innerHTML = ' <span class = "wrap"> ' + this.txt + ' </span>';
		var that = this;
		var delta = 300 - Math.random() * 100;
		if (this.isDeleting) {
			delta /= 2;
		}
		if (!this.isDeleting && this.txt === fullTxt) {
			delta = this.period;
			this.isDeleting = true;
		} else if (this.isDeleting && this.txt === '') {
			this.isDeleting = false;
			this.loopNum++;
			delta = 500;
		}
		setTimeout(function() {
			that.tick();
		}, delta);
	};
	window.onload = function() {
		var elements = document.getElementsByClassName('txt-rotate');
		for (var i = 0; i < elements.length; i++) {
			var toRotate = elements[i].getAttribute('data-rotate');
			var period = elements[i].getAttribute('data-period');
			if (toRotate) {
				new TxtRotate(elements[i], JSON.parse(toRotate), period);
			}
		}
		var css = document.createElement("style");
		css.type = "text/css";
		css.innerHTML = ".txt-rotate > .wrap { border-right: 0 }";
		document.body.appendChild(css);
	};
	setTimeout(function() {
		$('.loader-main');
		setTimeout(function() {
			$(".loader-main").hide();
		}, 1000);
	}, 1000);
	$('.fav-icon').on('click', function() {
		$(this).toggleClass('favourite');
	});

	function emailcreate() {
		$.ajax({
			url: "mail.php",
			type: "post",
			dataType: "json",
			data: $("#contact_form").serialize(),
			success: function(result) {
				console.log(result);
				var messageAlert = 'alert-' + result.type;
				var messageText = result.message;
				var alertBox = '<div class="alert ' + messageAlert + ' alert-dismissable"><button type="button" class="close" data-bs-dismiss="alert" aria-hidden="true">&times;</button>' + messageText + '</div>';
				if (messageAlert && messageText) {
					console.log(alertBox);
					$('.messages').html(alertBox);
					$('#contact_form')[0].reset();
				}
			}
		});
		return false;
	}
	$('#phone-num').keyup(function() {
		if (this.value.match(/[^0-9]/g)) {
			this.value = this.value.replace(/[^0-9^-]/g, '');
		}
	});
	if ($('.select').length > 0) {
		$('.select').select2({
			minimumResultsForSearch: -1,
			width: '100%'
		});
	}
	if ($('.delivery-add').length > 0) {
		$('.delivery-add .btn').on('click', function(e) {
			$(this).addClass("active");
			$(this).text("Added");
			$(this).prepend("<i class='feather-check'></i>");
		});
	}
	if ($('.service-slider').length > 0) {
		$('.service-slider').slick({
			slidesToShow: 1,
			slidesToScroll: 1,
			arrows: true,
			fade: true,
			asNavFor: '.slider-nav-thumbnails'
		});
	}
	if ($('.slider-nav-thumbnails').length > 0) {
		$('.slider-nav-thumbnails').slick({
			slidesToShow: 4,
			slidesToScroll: 1,
			asNavFor: '.service-slider',
			dots: false,
			arrows: false,
			centerMode: false,
			focusOnSelect: true
		});
	}
	if ($('.more-content').length > 0) {
		$(".more-content").hide();
		$(".read-more").on("click", function() {
			$(this).text($(this).text() === "Read Less" ? "Read More" : "Read Less");
			$(".more-content").toggle(900);
		});
	}
	if ($('.recent-carousel').length > 0) {
		$('.recent-carousel').owlCarousel({
			loop: true,
			margin: 24,
			nav: true,
			dots: false,
			smartSpeed: 2000,
			autoplay: false,
			navText: ['<i class="fa-solid fa-chevron-left"></i>', '<i class="fa-solid fa-chevron-right"></i>'],
			navContainer: '.mynav1',
			responsive: {
				0: {
					items: 1
				},
				550: {
					items: 1
				},
				768: {
					items: 2
				},
				1200: {
					items: 3
				}
			}
		})
	}
	if ($('.service-sliders').length > 0) {
		$('.service-sliders').owlCarousel({
			loop: true,
			margin: 24,
			nav: true,
			dots: false,
			smartSpeed: 2000,
			autoplay: false,
			navText: ['<i class="fa-solid fa-chevron-left"></i>', '<i class="fa-solid fa-chevron-right"></i>'],
			navContainer: '.service-nav',
			responsive: {
				0: {
					items: 2
				},
				600: {
					items: 2
				},
				992: {
					items: 3
				},
				1200: {
					items: 4
				}
			}
		})
	}
	if ($('.trend-items').length > 0) {
		$('.trend-items').owlCarousel({
			loop: true,
			margin: 22,
			nav: true,
			dots: false,
			smartSpeed: 2000,
			autoplay: false,
			navText: ['<i class="fa-solid fa-chevron-left"></i>', '<i class="fa-solid fa-chevron-right"></i>'],
			navContainer: '.trend-nav',
			responsive: {
				0: {
					items: 1
				},
				600: {
					items: 2
				},
				992: {
					items: 3
				},
				1200: {
					items: 4
				}
			}
		})
	}
	if ($('.relate-slider').length > 0) {
		$('.relate-slider').owlCarousel({
			loop: true,
			margin: 22,
			nav: false,
			dots: false,
			smartSpeed: 2000,
			autoplay: false,
			responsive: {
				0: {
					items: 1
				},
				600: {
					items: 2
				},
				1200: {
					items: 3
				}
			}
		})
	}
	if ($('.testimonial-slider').length > 0) {
		$('.testimonial-slider').owlCarousel({
			loop: true,
			margin: 22,
			nav: true,
			dots: false,
			smartSpeed: 2000,
			autoplay: false,
			navText: ['<i class="fa-solid fa-chevron-left"></i>', '<i class="fa-solid fa-chevron-right"></i>'],
			responsive: {
				0: {
					items: 1
				},
				600: {
					items: 2
				},
				1200: {
					items: 3
				}
			}
		})
	}
	if ($('.login-carousel').length > 0) {
		$('.login-carousel').owlCarousel({
			loop: true,
			margin: 24,
			nav: false,
			dots: true,
			smartSpeed: 2000,
			autoplay: false,
			responsive: {
				0: {
					items: 1
				}
			}
		})
	}
	if ($('.viewall-one').length > 0) {
		$(".viewall-one").hide();
		$(".viewall-button-one").on("click", function() {
			$(this).text($(this).text() === "Less Categories" ? "More 20+ Categories" : "Less Categories");
			$(".viewall-one").slideToggle(900);
		});
	}
	if ($('.viewall-location').length > 0) {
		$(".viewall-location").hide();
		$(".viewall-btn-location").on("click", function() {
			$(this).text($(this).text() === "Less Locations" ? "More 20+ Locations" : "Less Locations");
			$(".viewall-location").slideToggle(900);
		});
	}
	if ($('.filters-wrap').length > 0) {
		var show = true;
		$('.filter-header a').on("click", function() {
			if (show) {
				$(this).closest(".collapse-card").children(".collapse-body").css("display", "block");
				$(this).closest(".collapse-card").addClass('active');
				show = false;
			} else {
				$(".collapse-body").css("display", "none");
				$(this).closest(".collapse-card").removeClass('active');
				show = true;
			}
		});
	}
	if ($('.more-content').length > 0) {
		$(".more-content").hide();
		$(".show-more").on("click", function() {
			$(this).text($(this).text() === "Show Less" ? "Show More" : "Show Less");
			$(".more-content").toggle(900);
		});
	}
	if ($('.toggle-password').length > 0) {
		$(document).on('click', '.toggle-password', function() {
			$(this).toggleClass("feather-eye feather-eye-off");
			var input = $(".pass-input");
			if (input.attr("type") === "password") {
				input.attr("type", "text");
			} else {
				input.attr("type", "password");
			}
		});
	}
	if ($('.toggle-password-confirm').length > 0) {
		$(document).on('click', '.toggle-password-confirm', function() {
			$(this).toggleClass("feather-eye feather-eye-off");
			var input = $(".pass-confirm");
			if (input.attr("type") === "password") {
				input.attr("type", "text");
			} else {
				input.attr("type", "password");
			}
		});
	}
	if ($('.floating').length > 0) {
		$('.floating').on('focus blur input change', function(e) {
			$(this).parents('.form-focus').toggleClass('focused', (e.type === 'focus' || this.value.length > 0));
		}).trigger('blur');
		// Extra: handle autofill after DOM ready
		setTimeout(function() {
			$('.floating').each(function() {
				if (this.value.length > 0) {
					$(this).parents('.form-focus').addClass('focused');
				}
			});
		}, 300);
	}
	if ($('[data-bs-toggle="tooltip"]').length > 0) {
		var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
		var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
			return new bootstrap.Tooltip(tooltipTriggerEl)
		})
	}
	$(".extra-serv .checkmark").on("click", function() {
		var $listSort = $('.exta-label');
		if ($listSort.attr('disabled')) {
			$listSort.removeAttr('disabled');
		} else {
			$listSort.attr('disabled', 'disabled');
		}
	});
	if ($('.days-count').length > 0) {
		let day = document.querySelector('.days');
		let hour = document.querySelector('.hours');
		let minute = document.querySelector('.minutes');
		let second = document.querySelector('.seconds');

		function setCountdown() {
			let countdownDate = new Date('sep 27, 2024 16:00:00').getTime();
			let updateCount = setInterval(function() {
				let todayDate = new Date().getTime();
				let distance = countdownDate - todayDate;
				let days = Math.floor(distance / (1000 * 60 * 60 * 24));
				let hours = Math.floor(distance % (1000 * 60 * 60 * 24) / (1000 * 60 * 60));
				let minutes = Math.floor(distance % (1000 * 60 * 60) / (1000 * 60));
				let seconds = Math.floor(distance % (1000 * 60) / 1000);
				day.textContent = days;
				hour.textContent = hours;
				minute.textContent = minutes;
				second.textContent = seconds;
				if (distance < 0) {
					clearInterval(updateCount);
					document.querySelector(".days-count").innerHTML = '<h1>EXPIRED</h1>'
				}
			}, 1000)
		}
		setCountdown()
	}
	$(document).on('click', '.trash-sign', function() {
		$(this).closest('.sign-cont').remove();
		return false;
	});
	$(document).on('click', '.amount-add', function() {
		var signcontent = '<div class="row sign-cont">' +
			'<div class="col-md-4">' +
			'<div class="form-wrap">' +
			'<input type="text" class="form-control" placeholder="I Can">' +
			'</div>' +
			'</div>' +
			'<div class="col-md-4">' +
			'<div class="form-wrap">' +
			'<input type="text" class="form-control" placeholder="For ($)">' +
			'</div>' +
			'</div>' +
			'<div class="col-md-4">' +
			'<div class="form-wrap d-flex align-items-center">' +
			'<input type="text" class="form-control" placeholder="In (Day)">' +
			'<a href="javascript:void(0);" class="trash-sign ms-2 text-danger"><i class="feather-trash-2"></i></a>' +
			'</div>' +
			'</div>' +
			'</div>';
		$(".add-content").append(signcontent);
		return false;
	});
	if ($('.datatable').length > 0) {
		$('.datatable').DataTable({
			"bFilter": true,
			"bLengthChange": false,
			"bInfo": true,
			"ordering": false,
			"language": {
				search: ' ',
				searchPlaceholder: "Search",
				paginate: {
					next: ' <i class=" fa fa-angle-right"></i>',
					previous: '<i class="fa fa-angle-left"></i> '
				},
			},
			initComplete: (settings, json) => {
				$('.dataTables_paginate').appendTo('#tablepage');
				$('.dataTables_filter').appendTo('#tablefilter');
				$('.dataTables_info').appendTo('#tableinfo');
			},
		});
	}
	if ($('.datetimepicker').length > 0) {
		$('.datetimepicker').datetimepicker({
			format: 'DD-MM-YYYY',
			icons: {
				up: "fa fa-angle-up",
				down: "fa-solid fa-angle-down",
				next: 'fa-solid fa-angle-right',
				previous: 'fa-solid fa-angle-left'
			}
		});
	}
	if ($('.top-online-contacts .swiper-container').length > 0) {
		var swiper = new Swiper('.top-online-contacts .swiper-container', {
			slidesPerView: 5,
			spaceBetween: 15,
		});
	}
	$('.user-chat-search-btn').on('click', function() {
		$('.user-chat-search').addClass('visible-chat');
	});
	$('.user-close-btn-chat').on('click', function() {
		$('.user-chat-search').removeClass('visible-chat');
	});
	$('.chat-search-btn').on('click', function() {
		$('.chat-search').addClass('visible-chat');
	});
	$('.close-btn-chat').on('click', function() {
		$('.chat-search').removeClass('visible-chat');
	});
	$(".chat-search .form-control").on("keyup", function() {
		var value = $(this).val().toLowerCase();
		$(".chat .chat-body .messages .chats").filter(function() {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
		});
	});
	$(".user-list-item:not(body.status-page .user-list-item, body.voice-call-page .user-list-item)").on('click', function() {
		if ($(window).width() < 992) {
			$('.left-sidebar').addClass('hide-left-sidebar');
			$('.chat').addClass('show-chatbar');
		}
	});
	$(".left_sides").on('click', function() {
		if ($(window).width() <= 991) {
			$('.sidebar-group').removeClass('hide-left-sidebar');
			$('.sidebar-menu').removeClass('d-none');
		}
	});
	$(".left_sides").on('click', function() {
		if ($(window).width() <= 991) {
			$('.chat-messages').removeClass('show-chatbar');
		}
	});
	$(".user-list li a").on('click', function() {
		if ($(window).width() <= 767) {
			$('.left-sidebar').addClass('hide-left-sidebar');
			$('.sidebar-menu').addClass('d-none');
		}
	});
	const $menu = $('.dropdowns')
	const onMouseUp = e => {
		if (!$menu.is(e.target) && $menu.has(e.target).length === 0) {
			$menu.removeClass('is-active')
		}
	}
	$('.toggle').on('click', () => {
		$menu.toggleClass('is-active').promise().done(() => {
			if ($menu.hasClass('is-active')) {
				$(document).on('mouseup', onMouseUp)
			} else {
				$(document).off('mouseup', onMouseUp)
			}
		})
	})
})(jQuery);
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <?php
    $check_id = antixss($_GET['id']);
    $api_checkid = $connection->query("SELECT * FROM `khologo` WHERE `id` = '$check_id' ")->fetch_array();

    if (!$api_checkid) {
        header("Location: /tao-logo");
        exit();
    } else {
        $id = antixss($_GET['id']);
        $api_logo = $connection->query("SELECT * FROM `khologo` WHERE `id` = '$id'")->fetch_array();
        mysqli_query($connection, "UPDATE `khologo` SET `view` = `view` + 1 WHERE `id` = '$id'");
    }
    ?>

    <title>
        <?= $api_logo['title']; ?> |
        <?= $chungapi['ten_web']; ?>
    </title>
</head>
<main>
    <div class="w-breadcrumb-area">
        <div class="breadcrumb-img">
            <div class="breadcrumb-left">
                <img src="<?= asset('assets/images/banner-bg-03.png') ?>" alt="img">
            </div>
        </div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 col-12">
                    <nav aria-label="breadcrumb" class="page-breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="/">Trang chủ</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="/tao-logo">Danh mục</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="/">Design</li>
                        </ol>
                    </nav>
                    <h2 class="breadcrumb-title">
                        <?= $api_logo['title']; ?>
                    </h2>
                </div>
                <div class="col-lg-5 col-12">
                    <ul class="breadcrumb-links">
                        <li>
                            <a href="javascript:void(0);" class="fav-icon" data-product-id="40">
                                <span><i class="fa-regular fa-heart"></i></span> Yêu thích
                            </a>
                        </li>
                        <li>
                            <a target="_blank" href="#"><span><i class="fa-brands fa-facebook"></i></span>Chia sẽ</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <section class="py-110">
        <div class="container">
            <div class="row">
                <div class="col-xl-9 col-lg-8">
                    <!-- Slider Ảnh Chính -->
                    <div class="slider-card mb-4">
                        <div class="slider service-slider">
                            <?php
                            $images = array_filter(array_map('trim', explode("\n", $api_logo['list_img'])));
                            if (count($images) > 0) {
                                foreach ($images as $img) {
                                    echo '<div class="service-img-wrap"><img src="' . $img . '" alt="Slider Image"></div>';
                                }
                            } else {
                                // fallback ảnh mặc định nếu không có ảnh nào
                                echo '<div class="service-img-wrap"><img src="' . asset('assets/images/no-image.png') . '" alt="No image"></div>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Thumbnail -->
                    <div class="slider slider-nav-thumbnails">
                        <?php
                        if (count($images) > 0) {
                            foreach ($images as $img) {
                                echo '<div><img src="' . $img . '" alt="Thumbnail"></div>';
                            }
                        }
                        ?>
                    </div>
                    <input type="hidden" value="17" id="id_product">
                    <div class="mt-40">
                        <div class="service_details legal-content">
                            <div class="content-details service-wrap">
                                <p> </p>

                                <p>
                                    <?= $api_logo['title']; ?>
                                </p>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- Right Sidebar -->
                <div class="col-xl-3 col-lg-4 mt-30 mt-xl-0">
                    <aside class="d-flex flex-column gap-4">
                        <div class="service-widget">
                            <div class="service-amt d-flex align-items-center justify-content-between">
                                <p>Giá bán</p>
                                <h2>
                                    <?= tien($api_logo['gia']); ?>đ
                                </h2>
                            </div>
                            <ul class="mb-4">
                                <li class="fs-6 d-flex align-items-center gap-3 text-dark-200">
                                    <i class="fa fa-check"></i>Cam kết như demo 100%.
                                </li>
                            </ul>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#stripePayment"
                                class="btn btn-primary w-100"><i class="fa fa-shopping-cart"></i> Thanh Toán</a>
                        </div>
                        <!-- Card -->

                        <div class="service-widget member-widget">
                            <div class="user-details">
                                <div class="user-img">
                                    <img src="<?= asset('assets/images/avt.png') ?>" alt="dailycode.vn">
                                </div>
                                <div class="user-info">
                                    <h5><span class="me-2">Đại Lý Code</span>
                                        <span class="badge bg-soft-danger"><i class="fa-solid fa-circle"></i>
                                            Offline</span>
                                    </h5>
                                </div>
                            </div>
                            <ul class="member-info">
                                <li>
                                    Địa chỉ
                                    <span>Việt Nam</span>
                                </li>
                            </ul>
                            <a href="/" class="btn btn-primary mb-0 w-100">
                                Xem cửa hàng
                            </a>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </section>
    <!-- Services Details End -->

    <div class="modal new-modal fade" id="stripePayment" data-keyboard="false" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xác nhận thanh toán</h5>
                    <button type="button" class="close-btn" data-bs-dismiss="modal"><span>×</span></button>
                </div>
                <div class="modal-body service-modal">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="order-status">
                                <div class="order-item">
                                    <div class="order-img">
                                        <img src="<?= $api_logo['img']; ?>" alt="img">
                                    </div>
                                    <div class="order-info">
                                        <h5>
                                            <?= $api_logo['name']; ?>
                                        </h5>
                                        <ul>
                                            <li>ID : #
                                                <?= $api_logo['id']; ?>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <h6 class="title">Người bán</h6>
                                <div class="user-details">
                                    <div class="user-img">
                                        <img src="<?= asset('assets/images/avt.png') ?>" alt="img">
                                    </div>
                                    <div class="user-info">
                                        <h5>Đại Lý Code <span class="location">Việt Nam</span></h5>
                                    </div>
                                </div>
                                <h6 class="title">Chi tiết thanh toán</h6>
                                <div class="detail-table table-responsive">
                                    <table class="table">

                                        <tbody>

                                            <tr>
                                                <td>Tên logo</td>

                                                <td>
                                                    <input type="text" class="form-control shadow-none" id="yeucau"
                                                        name="yeucau" onchange="totalPayment()" onkeyup="totalPayment()"
                                                        placeholder="Nhập tên và yêu cầu" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Mã giảm giá</td>

                                                <td>
                                                    <input type="text" class="form-control shadow-none" id="giftcode"
                                                        name="giftcode" onchange="totalPayment()"
                                                        onkeyup="totalPayment()" placeholder="Nhập mã giảm giá" />
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="1">Tổng tiền</th>
                                                <th class="text-primary"><b id="total">
                                                        <?= tien($api_logo['gia']); ?>đ
                                                    </b></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="modal-btn">
                                    <div class="row gx-2">
                                        <div class="col-6">
                                            <a href="#" data-bs-dismiss="modal"
                                                class="btn btn-secondary w-100 justify-content-center">Đóng</a>
                                        </div>
                                        <div class="col-6">
                                            <button type="button" onclick="taologo()" class="btn btn-primary w-100">
                                                <span id="button1" class="indicator-label">Thanh Toán</span>
                                                <span id="button2" class="indicator-progress" style="display: none;"> <i
                                                        class="fa fa-spinner fa-spin"></i> Đang xử lý.. </span></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thư viện JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>

    <script>
        $(document).ready(function () {
            // Slider lớn
            $('.service-slider').slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                arrows: false,
                fade: true,
                rtl: true, // Lướt từ phải sang trái
                autoplay: true, // Tự động lướt
                autoplaySpeed: 2000, // Tốc độ 2 giây
                asNavFor: '.slider-nav-thumbnails' // Kết nối với thumbnails
            });

            // Slider thumbnails
            $('.slider-nav-thumbnails').slick({
                slidesToShow: 4,
                slidesToScroll: 1,
                asNavFor: '.service-slider',
                dots: false,
                centerMode: true,
                focusOnSelect: true,
                rtl: true // Lướt từ phải sang trái
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var modal = document.getElementById('modal_notification');
            var dontShowAgainBtn = document.getElementById('dontShowAgainBtn');
            var modalClosedTime = localStorage.getItem('modalClosedTime');
            if (!modalClosedTime || (Date.now() - parseInt(modalClosedTime) > 2 * 60 * 60 * 1000)) {
                var bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            }
            dontShowAgainBtn.addEventListener('click', function () {
                localStorage.setItem('modalClosedTime', Date.now());
                var bootstrapModal = bootstrap.Modal.getInstance(modal);
                bootstrapModal.hide();
            });
        });
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
        function taologo() {
            const button1 = document.getElementById("button1");
            const button2 = document.getElementById("button2");

            const username = "<?= $username ?>";
            const id_code = "<?= $id ?>";
            const yeucau = document.getElementById("yeucau").value;
            const giftcode = document.getElementById("giftcode").value; // THÊM DÒNG NÀY

            // Giao diện xử lý
            button1.style.display = "none";
            button2.style.display = "inline-block";
            button2.disabled = true;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", BASE_URL + "/ajax/taologo/process_logo.php");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onload = function () {
                button1.style.display = "inline-block";
                button2.style.display = "none";
                button2.disabled = false;

                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showMessage("Tạo logo thành công!", "success");
                    setTimeout(() => window.location.href = BASE_URL + "/history-logo", 3000);
                } else {
                    showMessage(response.message, "error");
                }
            };

            xhr.onerror = function () {
                button1.style.display = "inline-block";
                button2.style.display = "none";
                button2.disabled = false;
                showMessage("Lỗi kết nối đến máy chủ!", "error");
            };

            xhr.send(
                "username=" + encodeURIComponent(username) +
                "&giftcode=" + encodeURIComponent(giftcode) +
                "&yeucau=" + encodeURIComponent(yeucau) +
                "&id_code=" + encodeURIComponent(id_code)
            );
        }
    </script>
    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
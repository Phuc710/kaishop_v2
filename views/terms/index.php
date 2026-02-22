<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Điều Khoản & Điều Kiện | <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop') ?></title>

    <meta name="description"
        content="Điều khoản & điều kiện sử dụng dịch vụ tại <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop') ?>: giao dịch, hoàn tiền, bảo hành và bảo mật tài khoản.">
    <meta name="keywords"
        content="điều khoản, điều kiện, hoàn tiền, bảo hành, bảo mật, <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop') ?>">
    <meta name="robots" content="index, follow">

    <!-- SEO OpenGraph -->
    <meta property="og:title"
        content="Điều Khoản & Điều Kiện | <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop') ?>">
    <meta property="og:description" content="Điều khoản dịch vụ, hoàn tiền, bảo hành và bảo mật tài khoản.">
    <meta property="og:type" content="website">

    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <link rel="stylesheet" href="<?= asset('assets/css/policy.css') ?>">
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
</head>

<body>
    <main>
        <div class="container">
            <div class="policy-wrapper">
                <h1>Điều Khoản & Điều Kiện</h1>

                <p>
                    - Khi truy cập hoặc mua hàng tại
                    <strong ><?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop') ?></strong>,
                    bạn đồng ý tuân thủ các điều khoản dưới đây. Vui lòng đọc kỹ trước khi giao dịch.
                </p>

                <h2>1. Quyền lợi & điều khoản giao dịch</h2>
                <ul>
                    <li>
                        <strong>Hoàn tiền:</strong> Hoàn tiền về <strong>số dư website</strong> nếu sản phẩm
                        lỗi do hệ thống phát hành
                        hoặc không thể bàn giao đúng mô tả. Không hoàn tiền trong trường hợp mua nhầm, đổi ý,
                        hoặc lỗi thao tác từ phía người dùng.
                    </li>
                    <li>
                        <strong>Bảo hành:</strong> Áp dụng theo mô tả từng sản phẩm (nếu có). Trường hợp sản
                        phẩm có bảo hành,
                        hỗ trợ theo đúng thời gian và điều kiện ghi rõ trên trang sản phẩm.
                    </li>
                    <li>
                        <strong>Thời gian xử lý:</strong> Đơn hàng tự động/ thủ công sẽ được xử lý theo
                        trạng thái hệ thống.
                        Trong một số trường hợp phát sinh (bảo trì, quá tải, đối tác lỗi), thời gian có thể kéo dài.
                    </li>
                </ul>

                <h2>2. Bảo mật tài khoản & trách nhiệm người dùng</h2>
                <ul>
                    <li>
                        Khách hàng phải <strong>đổi mật khẩu</strong> và <strong>email/ thông tin khôi phục</strong>
                        (nếu có)
                        ngay sau khi nhận tài khoản.
                    </li>
                    <li>
                        Không chia sẻ tài khoản/ thông tin đăng nhập cho bên thứ ba. Mọi rủi ro phát sinh do lộ lọt
                        thông tin
                        từ phía khách hàng, website không chịu trách nhiệm.
                    </li>
                    <li>
                        Khuyến nghị bật bảo mật 2 lớp (nếu nền tảng hỗ trợ) để hạn chế rủi ro.
                    </li>
                </ul>

                <h2>3. Sử dụng hợp pháp</h2>
                <p>
                    Khách hàng cam kết sử dụng sản phẩm/dịch vụ đúng mục đích hợp pháp.
                    Nếu phát hiện hành vi vi phạm pháp luật, website có quyền từ chối hỗ trợ và khóa giao dịch liên
                    quan.
                </p>

                <h2>4. Cập nhật điều khoản</h2>
                <p>
                    <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop') ?> có quyền điều chỉnh nội dung điều khoản
                    bất cứ lúc nào
                    để phù hợp quy định và vận hành hệ thống. Phiên bản cập nhật sẽ có hiệu lực ngay khi được đăng tải.
                </p>

                <div class="policy-notice">
                    <i class="fas fa-info-circle"></i>
                    <div>Bằng việc sử dụng dịch vụ, bạn xác nhận đã đọc, hiểu và đồng ý với <strong>Điều Khoản & Điều
                            Kiện</strong> này.</div>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>

    <!-- SEO JSON-LD Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "Điều Khoản & Điều Kiện | <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop') ?>",
        "description": "Điều khoản dịch vụ, hoàn tiền, bảo hành và bảo mật tài khoản.",
        "inLanguage": "vi"
    }
    </script>
</body>

</html>
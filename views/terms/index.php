<!DOCTYPE html>
<html lang="vi">

<head>
    <?php
    $siteName = (string) ($chungapi['ten_web'] ?? 'KaiShop');
    $seoTitle = 'Điều Khoản & Điều Kiện | ' . $siteName;
    $seoDescription = 'Điều khoản sử dụng dịch vụ, hoàn tiền, bảo hành và bảo mật tài khoản tại ' . $siteName . '.';
    $seoKeywords = 'điều khoản, điều kiện, hoàn tiền, bảo hành, bảo mật, ' . $siteName;
    $seoRobots = 'index, follow';
    ?>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title><?= htmlspecialchars($seoTitle) ?></title>
    <link rel="stylesheet" href="<?= asset('assets/css/policy.css') ?>">
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main>
        <div class="container">
            <div class="policy-wrapper">
                <h1>Điều Khoản & Điều Kiện</h1>

                <p>
                    Khi truy cập hoặc mua hàng tại <strong><?= htmlspecialchars($siteName) ?></strong>, bạn đồng ý tuân thủ các điều khoản dưới đây.
                    Vui lòng đọc kỹ trước khi thanh toán và sử dụng dịch vụ.
                </p>

                <h2>1. Điều khoản giao dịch</h2>
                <ul>
                    <li>
                        <strong>Hoàn tiền:</strong> Xử lý theo chính sách của website và mô tả từng sản phẩm.
                        Các trường hợp mua nhầm / đổi ý / thao tác sai từ phía người dùng có thể không thuộc diện hoàn tiền.
                    </li>
                    <li>
                        <strong>Bảo hành:</strong> Áp dụng theo nội dung mô tả trên từng sản phẩm/dịch vụ (nếu có).
                    </li>
                    <li>
                        <strong>Thời gian xử lý:</strong> Tùy theo trạng thái hệ thống và hình thức giao hàng (tự động/thủ công).
                        Trong các trường hợp phát sinh (bảo trì, quá tải, lỗi nhà cung cấp), thời gian xử lý có thể kéo dài hơn dự kiến.
                    </li>
                </ul>

                <h2>2. Trách nhiệm người dùng</h2>
                <ul>
                    <li>Tự bảo mật thông tin đăng nhập và thông tin khôi phục tài khoản sau khi nhận hàng.</li>
                    <li>Không chia sẻ tài khoản cho bên thứ ba nếu điều đó làm phát sinh rủi ro mất quyền truy cập.</li>
                    <li>Sử dụng sản phẩm/dịch vụ đúng mục đích hợp pháp và đúng quy định nền tảng liên quan.</li>
                </ul>

                <h2>3. Quyền của website</h2>
                <p>
                    Website có quyền từ chối hỗ trợ hoặc khóa tài khoản nếu phát hiện hành vi gian lận, lạm dụng, vi phạm pháp luật
                    hoặc vi phạm các điều khoản công bố trên website.
                </p>

                <h2>4. Cập nhật điều khoản</h2>
                <p>
                    Điều khoản có thể được điều chỉnh theo từng thời điểm để phù hợp với vận hành hệ thống.
                    Phiên bản cập nhật có hiệu lực ngay khi đăng tải.
                </p>

                <div class="policy-notice">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        Bằng việc sử dụng dịch vụ, bạn xác nhận đã đọc, hiểu và đồng ý với Điều khoản & Điều kiện này.
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "Điều Khoản & Điều Kiện | <?= htmlspecialchars($siteName) ?>",
        "description": "Điều khoản sử dụng dịch vụ, hoàn tiền, bảo hành và bảo mật tài khoản tại <?= htmlspecialchars($siteName) ?>.",
        "inLanguage": "vi"
    }
    </script>
</body>

</html>

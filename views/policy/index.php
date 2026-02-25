<!DOCTYPE html>
<html lang="vi">

<head>
    <?php
    $siteName = (string) ($chungapi['ten_web'] ?? 'KaiShop');
    $seoTitle = 'Chính Sách | ' . $siteName;
    $seoDescription = 'Tuyên bố miễn trừ trách nhiệm và chính sách sử dụng dịch vụ tại ' . $siteName . '.';
    $seoKeywords = 'chính sách, quy định, miễn trừ trách nhiệm, ' . $siteName;
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
                <h1>Chính Sách & Quy Định</h1>

                <h2>1. Giới hạn trách nhiệm</h2>
                <p>
                    <?= htmlspecialchars($siteName) ?> cung cấp sản phẩm/dịch vụ số theo mô tả công khai trên website.
                    Người dùng có trách nhiệm kiểm tra kỹ thông tin sản phẩm trước khi thanh toán.
                    Website không chịu trách nhiệm với các hành vi sử dụng sản phẩm sai mục đích, vi phạm pháp luật
                    hoặc vi phạm điều khoản của nền tảng bên thứ ba.
                </p>

                <h2>2. Chính sách sử dụng hợp pháp</h2>
                <p>Người dùng cam kết không sử dụng sản phẩm/dịch vụ vào các mục đích trái pháp luật, bao gồm nhưng không giới hạn:</p>
                <ul>
                    <li>Lừa đảo, chiếm đoạt tài sản</li>
                    <li>Phát tán nội dung vi phạm pháp luật hoặc xâm phạm quyền của bên thứ ba</li>
                    <li>Tấn công, phá hoại hệ thống hoặc khai thác trái phép tài nguyên</li>
                    <li>Các hành vi bị cấm theo quy định pháp luật hiện hành</li>
                </ul>

                <h2>3. Xử lý vi phạm</h2>
                <p>
                    Khi phát hiện dấu hiệu vi phạm, website có quyền tạm khóa hoặc khóa vĩnh viễn tài khoản,
                    từ chối hỗ trợ, từ chối bảo hành và/hoặc từ chối hoàn tiền tùy theo mức độ vi phạm.
                </p>

                <h2>4. Cập nhật chính sách</h2>
                <p>
                    Chính sách có thể được cập nhật để phù hợp với vận hành thực tế và quy định hiện hành.
                    Phiên bản mới có hiệu lực kể từ thời điểm được công bố trên website.
                </p>

                <div class="policy-notice">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        Khi tiếp tục sử dụng dịch vụ tại website, bạn xác nhận đã đọc, hiểu và đồng ý với các chính sách/quy định được công bố.
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
        "name": "Chính Sách | <?= htmlspecialchars($siteName) ?>",
        "description": "Tuyên bố miễn trừ trách nhiệm và chính sách sử dụng dịch vụ tại <?= htmlspecialchars($siteName) ?>.",
        "inLanguage": "vi"
    }
    </script>
</body>

</html>
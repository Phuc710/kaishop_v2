<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Chính Sách | <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop') ?></title>

    <meta name="description"
        content="Tuyên bố miễn trừ trách nhiệm và chính sách sử dụng dịch vụ tại <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop') ?>. Quy định rõ ràng trách nhiệm của khách hàng khi mua và sử dụng tài khoản.">
    <meta name="keywords"
        content="tuyên bố miễn trừ trách nhiệm, chính sách sử dụng, quy định sử dụng, <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop') ?>">
    <meta name="robots" content="index, follow">

    <!-- SEO OpenGraph -->
    <meta property="og:title"
        content="Chính Sách | <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop') ?>">
    <meta property="og:description"
        content="Quy định sử dụng dịch vụ và trách nhiệm pháp lý khi mua và sử dụng tài khoản.">
    <meta property="og:type" content="website">

    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <link rel="stylesheet" href="<?= asset('assets/css/policy.css') ?>">
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
</head>

<body>
    <main>
        <div class="container">
            <div class="policy-wrapper">

                <h1>Chính Sách & Quy Định</h1>

                <h2>1. Giới hạn trách nhiệm</h2>
                <p>
                    Website chỉ cung cấp tài khoản mạng xã hội cho mục đích quảng cáo và kinh doanh thương mại hợp pháp.
                    <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop') ?> không chịu bất kỳ trách nhiệm dân sự hoặc
                    pháp lý nào
                    đối với việc khách hàng sử dụng sai mục đích, hoặc thực hiện hành vi vi phạm pháp luật Việt Nam sau
                    khi mua hàng.
                </p>

                <h2>2. Nghiêm cấm sử dụng</h2>
                <p>Khách hàng tuyệt đối không được sử dụng tài khoản vào các hoạt động vi phạm pháp luật, bao gồm nhưng
                    không giới hạn:</p>
                <ul>
                    <li>Lừa đảo, chiếm đoạt tài sản</li>
                    <li>Phát tán nội dung trái phép, sai sự thật, đồi trụy</li>
                    <li>Hoạt động chống phá Nhà nước CHXHCN Việt Nam</li>
                    <li>Bất kỳ hành vi vi phạm pháp luật hiện hành nào khác</li>
                </ul>
                <p>
                    Nếu phát hiện vi phạm, tài khoản có thể bị khóa hoặc xóa vĩnh viễn và không hỗ trợ hoàn tiền.
                    Người sử dụng phải tự chịu trách nhiệm hoàn toàn trước pháp luật.
                </p>

                <div class="policy-notice">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>Lưu ý: Khi mua hàng tại website, bạn xác nhận đã đọc, hiểu và đồng ý với tuyên bố miễn trừ trách
                    nhiệm này.</div>
                </div>

            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>

    <!-- SEO JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "Tuyên Bố Miễn Trừ Trách Nhiệm | <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop') ?>",
        "description": "Tuyên bố miễn trừ trách nhiệm và chính sách sử dụng dịch vụ, quy định trách nhiệm khách hàng khi mua và sử dụng tài khoản.",
        "inLanguage": "vi"
    }
    </script>
</body>

</html>
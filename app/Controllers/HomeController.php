<?php

/**
 * Home Controller
 * Handles homepage
 */
class HomeController extends Controller
{
    private function buildHomeSeoData(string $siteName, array $categories, array $products, array $settings): array
    {
        $categoryNames = SeoContentHelper::namesFromRows($categories, 'name', 5);
        $productNames = SeoContentHelper::namesFromRows($products, 'name', 6);
        $categoryLead = !empty($categoryNames) ? implode(', ', $categoryNames) : 'dịch vụ số và source code';
        $productLead = !empty($productNames) ? implode(', ', $productNames) : 'sản phẩm số giao nhanh';

        $description = SeoContentHelper::excerpt(
            $siteName . ' cung cấp ' . $categoryLead . ', nạp tiền tự động 24/7, source code và sản phẩm số giao nhanh. '
            . 'Danh mục nổi bật gồm ' . $productLead . ', hỗ trợ mua nhanh, thanh toán rõ ràng và bảo mật ổn định.',
            165
        );

        return [
            'seoTitle' => $siteName . ' - Nạp tiền tự động 24/7, source code và dịch vụ số uy tín',
            'seoDescription' => $description,
            'seoKeywords' => SeoContentHelper::keywordString([
                $siteName,
                'nạp tiền tự động 24/7',
                'dịch vụ số',
                'source code',
                'mua source code uy tín',
                'sản phẩm số giao nhanh',
                ...$categoryNames,
                ...$productNames,
            ]),
            'seoCanonical' => url(''),
            'pageKicker' => 'Hệ sinh thái dịch vụ số',
            'pageHeading' => $siteName . ' giúp mua nhanh source code, tài nguyên số và nạp tiền tự động',
            'pageIntro' => SeoContentHelper::excerpt(
                'Trang chủ tập trung các nhóm sản phẩm có nhu cầu tìm kiếm cao như ' . $categoryLead
                . '. Nội dung được trình bày gọn, sạch, bám đúng ý định tìm kiếm mua hàng và tra cứu sản phẩm.',
                210
            ),
            'pageBodyTitle' => 'Danh mục được gom theo cụm tìm kiếm rõ ràng',
            'pageBodyText' => SeoContentHelper::excerpt(
                $siteName . ' sắp xếp sản phẩm theo danh mục để người dùng tìm đúng nhóm nhu cầu, so sánh giá nhanh và đi thẳng tới trang chi tiết có nội dung mô tả, tồn kho và thanh toán.',
                220
            ),
            'pageBottomTitle' => 'Mua source code và dịch vụ số theo từng nhóm nhu cầu',
            'pageBottomText' => SeoContentHelper::excerpt(
                'Mỗi danh mục là một cụm nội dung riêng giúp tăng độ liên quan từ khóa, tăng internal link và giữ cấu trúc crawl sạch cho bot tìm kiếm.',
                220
            ),
        ];
    }

    private function buildCategorySeoData(string $siteName, array $category, array $products): array
    {
        $categoryName = SeoContentHelper::cleanText($category['name'] ?? 'Danh mục');
        $categorySlug = trim((string) ($category['slug'] ?? ''));
        $productNames = SeoContentHelper::namesFromRows($products, 'name', 6);
        $productCount = count($products);
        $productLead = !empty($productNames) ? implode(', ', $productNames) : 'sản phẩm đang mở bán';

        $description = SeoContentHelper::excerpt(
            $siteName . ' tổng hợp ' . $productCount . ' sản phẩm thuộc danh mục ' . $categoryName
            . '. Nội dung tập trung đúng nhóm từ khóa ' . $categoryName
            . ', có mô tả sạch, liên kết nội bộ rõ và đường dẫn chuẩn để tăng khả năng index.',
            165
        );

        return [
            'seoTitle' => $categoryName . ' | ' . $siteName,
            'seoDescription' => $description,
            'seoKeywords' => SeoContentHelper::keywordString([
                $categoryName,
                'mua ' . $categoryName,
                $categoryName . ' uy tín',
                $categoryName . ' giá tốt',
                $siteName,
                ...$productNames,
            ]),
            'seoCanonical' => url('category/' . $categorySlug),
            'pageKicker' => 'Danh mục trọng tâm',
            'pageHeading' => $categoryName . ' tại ' . $siteName,
            'pageIntro' => SeoContentHelper::excerpt(
                'Trang danh mục này gom toàn bộ sản phẩm liên quan đến ' . $categoryName
                . ', giúp người dùng đọc nhanh, lọc nhanh và đi vào đúng sản phẩm cần mua mà không bị nhiễu bởi các nhóm khác.',
                210
            ),
            'pageBodyTitle' => 'Tập trung đúng ý định tìm kiếm của danh mục',
            'pageBodyText' => SeoContentHelper::excerpt(
                'Danh sách hiện có ' . $productCount . ' lựa chọn như ' . $productLead
                . '. Đây là trang đích phù hợp cho truy vấn mang ý định giao dịch, so sánh giá hoặc tìm thông tin trước khi mua.',
                220
            ),
            'pageBottomTitle' => 'Liên kết nội bộ sạch cho cụm nội dung ' . $categoryName,
            'pageBottomText' => SeoContentHelper::excerpt(
                'Từ trang danh mục, người dùng có thể đi trực tiếp tới từng trang chi tiết để đọc mô tả, xem tồn kho và hoàn tất thanh toán. Cấu trúc này giúp bot hiểu quan hệ giữa danh mục và sản phẩm tốt hơn.',
                220
            ),
        ];
    }

    /**
     * Show homepage
     */
    public function index()
    {
        global $connection, $chungapi, $username, $user; // Ensure globals are available

        $categoryModel = new Category();
        $productModel = new Product();
        $inventoryService = new ProductInventoryService();

        $categories = $categoryModel->getActive();
        $allProducts = $productModel->getAvailable(Product::CHANNEL_WEB);

        $stockStats = $inventoryService->getStatsForProducts($allProducts);

        // Group products by category ID
        $productsByCategory = [];
        foreach ($allProducts as $product) {
            $productsByCategory[$product['category_id']][] = $product;
        }

        // Lấy bot info để hiển thị username trong banner (fail-safe)
        $botInfo = [];
        try {
            $tgService = telegram_service();
            if ($tgService) {
                $botInfo = $tgService->getMe();
            }
        } catch (\Throwable $e) {
            // ignore - banner vẫn hiển thị bình thường
        }

        $siteName = (string) ($chungapi['ten_web'] ?? 'KaiShop');
        $seoData = $this->buildHomeSeoData($siteName, $categories, $allProducts, $chungapi);

        $this->view('home/index', array_merge([
            'categories' => $categories,
            'navCategories' => $categories,
            'displayCategories' => $categories,
            'productsByCategory' => $productsByCategory,
            'stockStats' => $stockStats,
            'user' => $user, // Pass user data to view
            'chungapi' => $chungapi,
            'botInfo' => $botInfo,
        ], $seoData));
    }

    /**
     * Show category filter page (renders homepage UI but filtered)
     */
    public function category($slug)
    {
        global $connection, $chungapi, $username, $user;

        $categoryModel = new Category();
        $productModel = new Product();
        $inventoryService = new ProductInventoryService();

        $categoryData = $categoryModel->findBySlug($slug);

        if (!$categoryData) {
            $categories = $categoryModel->getActive();
            foreach ($categories as $cat) {
                if (xoadau($cat['name']) === $slug) {
                    $categoryData = $cat;
                    break;
                }
            }
        }

        if (!$categoryData) {
            $this->redirect(url(''));
        }

        $categories = $categoryModel->getActive();

        $allProducts = $productModel->getFiltered([
            'category_id' => (int) $categoryData['id'],
            'channel' => Product::CHANNEL_WEB,
        ]);

        $stockStats = $inventoryService->getStatsForProducts($allProducts);

        $productsByCategory = [];
        foreach ($allProducts as $product) {
            $productsByCategory[$product['category_id']][] = $product;
        }

        // Lấy bot info để hiển thị username trong banner (fail-safe)
        $botInfo = [];
        try {
            $tgService = telegram_service();
            if ($tgService) {
                $botInfo = $tgService->getMe();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $siteName = (string) ($chungapi['ten_web'] ?? 'KaiShop');
        $seoData = $this->buildCategorySeoData($siteName, $categoryData, $allProducts);

        $this->view('home/index', array_merge([
            'categories' => $categories,
            'navCategories' => $categories,
            'displayCategories' => [$categoryData],
            'productsByCategory' => $productsByCategory,
            'stockStats' => $stockStats,
            'user' => $user,
            'chungapi' => $chungapi,
            'is_category_page' => true,
            'selectedCategory' => $categoryData,
            'botInfo' => $botInfo,
        ], $seoData));
    }
}

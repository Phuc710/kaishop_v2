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
        $productLead = !empty($productNames) ? implode(', ', $productNames) : 'sản phẩm số giao nhanh Auto 24/7';

        $description = $siteName;

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
            'pageBottomTitle' => '',
            'pageBottomText' => '',
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
            'pageBottomTitle' => '',
            'pageBottomText' => '',
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

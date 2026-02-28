<?php

/**
 * Home Controller
 * Handles homepage
 */
class HomeController extends Controller
{

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
        $allProducts = $productModel->getAvailable();

        $stockStats = $inventoryService->getStatsForProducts($allProducts);

        // Group products by category ID
        $productsByCategory = [];
        foreach ($allProducts as $product) {
            $productsByCategory[$product['category_id']][] = $product;
        }

        $this->view('home/index', [
            'categories' => $categories,
            'productsByCategory' => $productsByCategory,
            'stockStats' => $stockStats,
            'user' => $user, // Pass user data to view
            'chungapi' => $chungapi
        ]);
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
            $this->redirect(BASE_URL . '/');
        }

        $categories = [$categoryData];

        $allProducts = $productModel->getFiltered([
            'category_id' => (int) $categoryData['id'],
            'status' => 'ON'
        ]);

        $stockStats = $inventoryService->getStatsForProducts($allProducts);

        $productsByCategory = [];
        foreach ($allProducts as $product) {
            $productsByCategory[$product['category_id']][] = $product;
        }

        $this->view('home/index', [
            'categories' => $categories,
            'productsByCategory' => $productsByCategory,
            'stockStats' => $stockStats,
            'user' => $user,
            'chungapi' => $chungapi,
            'is_category_page' => true
        ]);
    }
}

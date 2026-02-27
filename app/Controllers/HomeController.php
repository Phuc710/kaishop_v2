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
        $stockModel = new ProductStock();

        $categories = $categoryModel->getActive();
        $allProducts = $productModel->getAvailable();

        // Fetch stock stats for all products
        $productIds = array_map(fn($p) => (int) $p['id'], $allProducts);
        $stockStats = $stockModel->getStatsForProducts($productIds);

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
        $stockModel = new ProductStock();

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

        $productIds = array_map(fn($p) => (int) $p['id'], $allProducts);
        $stockStats = empty($productIds) ? [] : $stockModel->getStatsForProducts($productIds);

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

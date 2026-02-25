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

        $categories = $categoryModel->getActive();
        $allProducts = $productModel->getAvailable();

        // Group products by category ID
        $productsByCategory = [];
        foreach ($allProducts as $product) {
            $productsByCategory[$product['category_id']][] = $product;
        }

        $this->view('home/index', [
            'categories' => $categories,
            'productsByCategory' => $productsByCategory,
            'user' => $user, // Pass user data to view
            'chungapi' => $chungapi
        ]);
    }

    /**
     * Show category filter page
     */
    public function category($slug)
    {
        global $chungapi, $user;

        $categoryModel = new Category();
        $productModel = new Product();

        // Find category by slug (or name using xoadau for backward compatibility)
        $categoryData = $categoryModel->findBySlug($slug);

        if (!$categoryData) {
            // Fallback: search active categories by xoadau() name just in case
            $categories = $categoryModel->getActive();
            foreach ($categories as $cat) {
                if (xoadau($cat['name']) === $slug) {
                    $categoryData = $cat;
                    break;
                }
            }
        }

        if (!$categoryData) {
            $this->redirect(BASE_URL . '/NotFound.php');
        }

        // Fetch products for this category only using its true ID
        $products = $productModel->getFiltered([
            'category_id' => (int) $categoryData['id'],
            'status' => 'ON'
        ]);

        $this->view('home/category', [
            'category' => $categoryData,
            'products' => $products,
            'user' => $user,
            'chungapi' => $chungapi
        ]);
    }
}

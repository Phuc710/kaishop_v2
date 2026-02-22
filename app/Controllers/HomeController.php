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
        global $connection, $chungapi, $username, $user;

        $categoryModel = new Category();
        $productModel = new Product();

        // Fetch specific category by slug (or exact name match if no dedicated slug column)
        // Note: admin uses xoadau() to generate slugs, we'll assume the URL passes that.
        // We'll search by matching the generated slug dynamically in the query or if a slug column exists.

        $categories = $categoryModel->getActive();
        $categoryData = null;

        foreach ($categories as $cat) {
            if (xoadau($cat['name']) === $slug) {
                $categoryData = $cat;
                break;
            }
        }

        if (!$categoryData) {
            $this->redirect(BASE_URL . '/NotFound.php');
        }

        // Fetch products for this category only using its true ID
        $products = $productModel->getFiltered([
            'category_id' => (int) $categoryData['id'],
            'status' => 'ON',
            'hidden' => 0
        ]);

        $this->view('home/category', [
            'category' => $categoryData,
            'products' => $products,
            'user' => $user,
            'chungapi' => $chungapi
        ]);
    }
}

<?php

/**
 * Home Controller
 * Handles homepage
 */
class HomeController extends Controller {
    
    /**
     * Show homepage
     */
    public function index() {
        global $connection, $chungapi, $username, $user; // Ensure globals are available
        
        $productModel = new Product();
        $products = $productModel->getAvailable();
        
        $this->view('home/index', [
            'products' => $products,
            'user' => $user, // Pass user data to view
            'chungapi' => $chungapi
        ]);
    }
}

<?php

/**
 * Product Controller
 * Handles product display
 */
class ProductController extends Controller {
    private $productModel;
    
    public function __construct() {
        $this->productModel = new Product();
    }
    
    /**
     * Show product detail
     * @param int $id
     */
    public function show($id) {
        global $chungapi, $user;
        
        $product = $this->productModel->find($id);
        
        if (!$product || $product['status'] != 'ON') {
            die('Product not found or unavailable');
        }
        
        $this->view('product/detail', [
            'user' => isset($_SESSION['session']) ? $user : null,
            'chungapi' => $chungapi,
            'product' => $product
        ]);
    }
}

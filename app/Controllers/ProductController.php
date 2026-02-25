<?php

/**
 * Product Controller
 * Handles product display
 */
class ProductController extends Controller {
    private $productModel;
    private $authService;
    private $purchaseService;
    
    public function __construct() {
        $this->productModel = new Product();
        $this->authService = new AuthService();
        $this->purchaseService = new PurchaseService();
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

    /**
     * POST /product/{id}/purchase
     */
    public function purchase($id)
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Ban chua dang nhap.'], 401);
        }

        $user = $this->authService->getCurrentUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Khong the xac thuc tai khoan.'], 401);
        }

        $result = $this->purchaseService->purchaseWithWallet((int) $id, $user);
        return $this->json($result, !empty($result['success']) ? 200 : 400);
    }
}

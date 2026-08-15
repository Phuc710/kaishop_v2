<?php

/**
 * Product Controller
 * Handles product display
 */
class ProductController extends Controller
{
    private $productModel;
    private $stockModel;
    private $inventoryService;
    private $authService;
    private $purchaseService;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->stockModel = new ProductStock();
        $this->inventoryService = new ProductInventoryService($this->stockModel);
        $this->authService = new AuthService();
        $this->purchaseService = new PurchaseService();
    }

    /**
     * Show product detail
     * @param int $id
     */
    public function show($id)
    {
        $product = $this->productModel->findVisibleForChannel((int) $id, Product::CHANNEL_WEB);
        if (!$product) {
            http_response_code(404);
            die('Product not found or unavailable');
        }

        $canonicalPath = (string) ($product['public_path'] ?? '');
        if ($canonicalPath !== '' && strpos($canonicalPath, 'product/') !== 0) {
            header('Location: ' . url($canonicalPath), true, 301);
            exit;
        }

        $this->renderDetail($product);
    }

    /**
     * Show product detail by canonical slug URL: /{category-slug}/{product-slug}
     */
    public function showBySlug($categorySlug, $productSlug)
    {
        $categorySlug = trim((string) $categorySlug, " /|");
        $productSlug = trim((string) $productSlug, " /|");

        $product = $this->productModel->findByCategoryAndProductSlug($categorySlug, $productSlug, Product::CHANNEL_WEB);
        if (!$product) {
            // Check if productSlug is actually an ID (fallback for old links)
            if (is_numeric($productSlug)) {
                return $this->show((int) $productSlug);
            }

            http_response_code(404);
            die('Product not found or unavailable');
        }

        $this->renderDetail($product);
    }

    /**
     * POST /product/{id}/quote
     * Preview pricing (quantity + giftcode) before purchase
     */
    public function quote($id)
    {
        $result = $this->purchaseService->quoteForDisplay((int) $id, [
            'quantity'       => max(1, (int) $this->input('quantity', 1)),
            'giftcode'       => strtoupper(trim((string) $this->input('giftcode', ''))),
            'source_channel' => Product::CHANNEL_WEB,
        ]);

        return $this->json($result, !empty($result['success']) ? 200 : 400);
    }

    /**
     * POST /product/{id}/purchase
     */
    public function purchase($id)
    {
        if (!$this->authService->isLoggedIn() || !($user = $this->authService->getCurrentUser())) {
            return $this->json(['success' => false, 'message' => 'Bạn chưa đăng nhập hoặc phiên đăng nhập đã hết hạn.', 'error_code' => 'UNAUTHORIZED'], 401);
        }

        if (!$this->validateCsrf()) {
            return $this->json(['success' => false, 'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.', 'error_code' => 'CSRF_INVALID'], 419);
        }

        try {
            $result = $this->purchaseService->purchaseWithWallet((int) $id, $user, [
                'quantity'       => max(1, (int) $this->input('quantity', 1)),
                'customer_input' => trim((string) $this->input('customer_input', '')),
                'giftcode'       => strtoupper(trim((string) $this->input('giftcode', ''))),
                'source_channel' => Product::CHANNEL_WEB,
            ]);

            return $this->json($result, !empty($result['success']) ? 200 : 400);
        } catch (Throwable $e) {
            return $this->json([
                'success'    => false,
                'message'    => $e->getMessage() ?: 'Có lỗi xảy ra khi xử lý mua hàng. Vui lòng thử lại.',
                'error_code' => 'PURCHASE_ERROR'
            ], 400);
        }
    }

    private function renderDetail(array $product): void
    {
        global $chungapi, $user;

        $availableStock = $this->inventoryService->getAvailableStock($product);

        $product['available_stock'] = $availableStock;
        $product['public_url'] = url((string) ($product['public_path'] ?? ('product/' . (int) ($product['id'] ?? 0))));

        $this->view('product/detail', [
            'user' => isset($_SESSION['session']) ? $user : null,
            'chungapi' => $chungapi,
            'product' => $product
        ]);
    }
}

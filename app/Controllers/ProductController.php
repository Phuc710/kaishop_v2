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
        $product = $this->productModel->find($id);
        if (!$product || (string) ($product['status'] ?? '') !== 'ON') {
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

        $product = $this->productModel->findByCategoryAndProductSlug($categorySlug, $productSlug);
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
        $payload = $_POST;
        if (empty($payload)) {
            $raw = file_get_contents('php://input');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $result = $this->purchaseService->quoteForDisplay((int) $id, [
            'quantity' => (int) ($payload['quantity'] ?? 1),
            'giftcode' => (string) ($payload['giftcode'] ?? ''),
        ]);

        return $this->json($result, !empty($result['success']) ? 200 : 400);
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

        $payload = $_POST;
        if (empty($payload)) {
            $raw = file_get_contents('php://input');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $result = $this->purchaseService->purchaseWithWallet((int) $id, $user, [
            'quantity' => (int) ($payload['quantity'] ?? 1),
            'customer_input' => (string) ($payload['customer_input'] ?? ''),
            'giftcode' => (string) ($payload['giftcode'] ?? ''),
        ]);
        return $this->json($result, !empty($result['success']) ? 200 : 400);
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

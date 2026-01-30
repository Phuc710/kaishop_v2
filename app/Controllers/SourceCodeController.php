<?php

/**
 * SourceCode Controller
 * Handles source code purchase
 */
class SourceCodeController extends Controller {
    private $authService;
    private $sourceCodeModel;
    
    public function __construct() {
        $this->authService = new AuthService();
        $this->sourceCodeModel = new SourceCode();
    }
    
    /**
     * Show source code shop
     */
    public function shop() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        global $chungapi;
        
        $sourceCodes = $this->sourceCodeModel->getAvailable();
        
        $this->view('sourcecode/shop', [
            'user' => $user,
            'chungapi' => $chungapi,
            'sourcecodes' => $sourceCodes
        ]);
    }
    
    /**
     * Purchase source code (AJAX)
     */
    public function buy() {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Not logged in'], 401);
        }
        
        $codeId = $this->post('code_id');
        
        if (empty($codeId)) {
            return $this->json(['success' => false, 'message' => 'Please select source code'], 400);
        }
        
        $user = $this->authService->getCurrentUser();
        
        // Process purchase
        return $this->json(['success' => true, 'message' => 'Source code purchased successfully']);
    }
    
    /**
     * Show purchase history
     */
    public function history() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        global $chungapi;
        
        $purchases = $this->sourceCodeModel->getUserPurchases($user['username']);
        
        $this->view('sourcecode/history', [
            'user' => $user,
            'chungapi' => $chungapi,
            'purchases' => $purchases
        ]);
    }
    
    /**
     * Download source code
     */
    public function download($id) {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        
        $purchase = $this->sourceCodeModel->find($id);
        
        if (!$purchase || $purchase['username'] !== $user['username']) {
            return $this->json(['success' => false, 'message' => 'Not found'], 404);
        }
        
        // Return download link
        return $this->json([
            'success' => true,
            'download_url' => $purchase['download_url']
        ]);
    }
}

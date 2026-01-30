<?php

/**
 * Hosting Controller
 * Handles hosting purchase and management
 */
class HostingController extends Controller {
    private $authService;
    private $hostingModel;
    
    public function __construct() {
        $this->authService = new AuthService();
        $this->hostingModel = new Hosting();
    }
    
    /**
     * Show hosting shop
     */
    public function shop() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        global $chungapi;
        
        $packages = $this->hostingModel->getPackages();
        
        $this->view('hosting/shop', [
            'user' => $user,
            'chungapi' => $chungapi,
            'packages' => $packages
        ]);
    }
    
    /**
     * Purchase hosting (AJAX)
     */
    public function buy() {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Chưa đăng nhập'], 401);
        }
        
        $packageId = $this->post('package_id');
        $domain = trim($this->post('domain', ''));
        
        if (empty($packageId) || empty($domain)) {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'], 400);
        }
        
        // Process hosting purchase
        return $this->json(['success' => true, 'message' => 'Mua hosting thành công']);
    }
    
    /**
     * Show purchase history
     */
    public function history() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        global $chungapi;
        
        $hostings = $this->hostingModel->getUserHostings($user['username']);
        
        $this->view('hosting/history', [
            'user' => $user,
            'chungapi' => $chungapi,
            'hostings' => $hostings
        ]);
    }
    
    /**
     * Manage hosting
     */
    public function manage($id) {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        
        $hosting = $this->hostingModel->find($id);
        
        if (!$hosting || $hosting['username'] !== $user['username']) {
            return $this->json(['success' => false, 'message' => 'Không tìm thấy hosting'], 404);
        }
        
        $this->view('hosting/manage', [
            'user' => $user,
            'hosting' => $hosting
        ]);
    }
}

<?php

/**
 * Logo Controller
 * Handles logo creation service
 */
class LogoController extends Controller {
    private $authService;
    private $logoModel;
    
    public function __construct() {
        $this->authService = new AuthService();
        $this->logoModel = new Logo();
    }
    
    /**
     * Show logo creation page
     */
    public function create() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        global $chungapi;
        
        $this->view('logo/create', [
            'user' => $user,
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Process logo creation (AJAX)
     */
    public function process() {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Not logged in'], 401);
        }
        
        $logoName = trim($this->post('logo_name', ''));
        $logoRequest = trim($this->post('logo_request', ''));
        
        if (empty($logoName)) {
            return $this->json(['success' => false, 'message' => 'Please enter logo name'], 400);
        }
        
        $user = $this->authService->getCurrentUser();
        
        // Create logo order
        $logoId = $this->logoModel->createOrder([
            'username' => $user['username'],
            'name' => $logoName,
            'request' => $logoRequest,
            'status' => 'pending'
        ]);
        
        return $this->json(['success' => true, 'message' => 'Logo created successfully', 'logo_id' => $logoId]);
    }
    
    /**
     * Show logo history
     */
    public function history() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        global $chungapi;
        
        $logos = $this->logoModel->getUserLogos($user['username']);
        
        $this->view('logo/history', [
            'user' => $user,
            'chungapi' => $chungapi,
            'logos' => $logos
        ]);
    }
}

<?php

/**
 * Website Controller
 * Handles website creation service
 */
class WebsiteController extends Controller {
    private $authService;
    private $websiteModel;
    
    public function __construct() {
        $this->authService = new AuthService();
        $this->websiteModel = new Website();
    }
    
    /**
     * Show website templates
     */
    public function templates() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        global $chungapi;
        
        $templates = $this->websiteModel->getTemplates();
        
        $this->view('website/templates', [
            'user' => $user,
            'chungapi' => $chungapi,
            'templates' => $templates
        ]);
    }
    
    /**
     * Create website (AJAX)
     */
    public function create() {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Not logged in'], 401);
        }
        
        $templateId = $this->post('template_id');
        $websiteName = trim($this->post('website_name', ''));
        
        if (empty($templateId) || empty($websiteName)) {
            return $this->json(['success' => false, 'message' => 'Please fill all required fields'], 400);
        }
        
        $user = $this->authService->getCurrentUser();
        
        // Create website order
        $websiteId = $this->websiteModel->createOrder([
            'username' => $user['username'],
            'template_id' => $templateId,
            'name' => $websiteName,
            'status' => 'pending'
        ]);
        
        return $this->json(['success' => true, 'message' => 'Website created successfully']);
    }
    
    /**
     * Show creation history
     */
    public function history() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        global $chungapi;
        
        $websites = $this->websiteModel->getUserWebsites($user['username']);
        
        $this->view('website/history', [
            'user' => $user,
            'chungapi' => $chungapi,
            'websites' => $websites
        ]);
    }
}

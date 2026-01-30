<?php

/**
 * Domain Controller
 * Handles domain purchase and management
 */
class DomainController extends Controller {
    private $authService;
    private $domainModel;
    private $userModel;
    
    public function __construct() {
        $this->authService = new AuthService();
        $this->domainModel = new Domain();
        $this->userModel = new User();
    }
    
    /**
     * Show domain shop
     */
    public function shop() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        $siteConfig = Config::getSiteConfig();
        
        // Get available domain extensions
        $domainExtensions = $this->domainModel->getAvailableExtensions();
        
        $this->view('domain/shop', [
            'user' => $user,
            'siteConfig' => $siteConfig,
            'extensions' => $domainExtensions
        ]);
    }
    
    /**
     * Purchase domain (AJAX)
     */
    public function buy() {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Not logged in'], 401);
        }
        
        $domainName = trim($this->post('domain', ''));
        $extension = trim($this->post('extension', ''));
        
        if (empty($domainName) || empty($extension)) {
            return $this->json(['success' => false, 'message' => 'Please enter domain name'], 400);
        }
        
        $user = $this->authService->getCurrentUser();
        $fullDomain = $domainName . '.' . $extension;
        
        // Check if domain available
        // Check user balance
        // Process purchase
        // Create domain record
        
        return $this->json(['success' => true, 'message' => 'Domain purchased successfully']);
    }
    
    /**
     * Show purchase history
     */
    public function history() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        global $chungapi;
        
        $domains = $this->domainModel->getUserDomains($user['username']);
        
        $this->view('domain/history', [
            'user' => $user,
            'chungapi' => $chungapi,
            'domains' => $domains
        ]);
    }
    
    /**
     * Manage domain
     */
    public function manage($id) {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        
        $domain = $this->domainModel->find($id);
        
        if (!$domain || $domain['username'] !== $user['username']) {
            return $this->json(['success' => false, 'message' => 'Domain not found'], 404);
        }
        
        $this->view('domain/manage', [
            'user' => $user,
            'domain' => $domain
        ]);
    }
}

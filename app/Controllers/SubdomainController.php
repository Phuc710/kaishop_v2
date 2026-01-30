<?php

/**
 * Subdomain Controller
 * Handles subdomain rental and management
 */
class SubdomainController extends Controller {
    private $authService;
    private $subdomainModel;
    
    public function __construct() {
        $this->authService = new AuthService();
        $this->subdomainModel = new Subdomain();
    }
    
    /**
     * Show subdomain shop
     */
    public function shop() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        global $chungapi;
        
        $availableSubdomains = $this->subdomainModel->getAvailable();
        
        $this->view('subdomain/shop', [
            'user' => $user,
            'chungapi' => $chungapi,
            'subdomains' => $availableSubdomains
        ]);
    }
    
    /**
     * Rent subdomain (AJAX)
     */
    public function rent() {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Not logged in'], 401);
        }
        
        $subdomainId = $this->post('subdomain_id');
        $user = $this->authService->getCurrentUser();
        
        // Process rental
        return $this->json(['success' => true, 'message' => 'Subdomain rented successfully']);
    }
    
    /**
     * Show rental history
     */
    public function history() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        global $chungapi;
        
        $subdomains = $this->subdomainModel->getUserSubdomains($user['username']);
        
        $this->view('subdomain/history', [
            'user' => $user,
            'chungapi' => $chungapi,
            'subdomains' => $subdomains
        ]);
    }
    
    /**
     * Manage subdomain
     */
    public function manage($id) {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        
        $subdomain = $this->subdomainModel->find($id);
        
        if (!$subdomain || $subdomain['username'] !== $user['username']) {
            return $this->json(['success' => false, 'message' => 'Subdomain not found'], 404);
        }
        
        $this->view('subdomain/manage', [
            'user' => $user,
            'subdomain' => $subdomain
        ]);
    }
}

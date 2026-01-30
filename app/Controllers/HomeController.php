<?php

/**
 * Home Controller
 * Handles homepage
 */
class HomeController extends Controller {
    
    /**
     * Show homepage
     */
    public function index() {
        // Redirect to old index.php for now
        // Or create a new homepage view
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

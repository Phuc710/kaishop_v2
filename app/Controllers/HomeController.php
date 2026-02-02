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
        // Render the old homepage content
        global $connection, $chungapi, $username, $user; // Ensure globals are available
        require_once __DIR__ . '/../../index_old.php';
    }
}

<?php

/**
 * Routes Configuration
 * Maps URLs to Controllers
 */

return [
    // Profile routes
    ['GET', '/profile', 'ProfileController@index'],
    ['POST', '/profile/update', 'ProfileController@update'],
    
    // Auth routes (future)
    // ['GET', '/login', 'AuthController@showLogin'],
    // ['POST', '/login', 'AuthController@login'],
    
    // Add more routes here...
];

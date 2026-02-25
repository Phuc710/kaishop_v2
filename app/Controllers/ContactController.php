<?php

class ContactController extends Controller
{
    public function index()
    {
        global $chungapi;

        $pagePath = BASE_PATH . '/pages/lienhe.php';
        if (!file_exists($pagePath)) {
            http_response_code(404);
            require BASE_PATH . '/404.php';
            return;
        }

        require $pagePath;
    }
}

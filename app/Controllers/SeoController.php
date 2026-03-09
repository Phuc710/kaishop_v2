<?php

class SeoController extends Controller
{
    public function robots()
    {
        $path = BASE_PATH . '/robots.txt.php';
        if (file_exists($path)) {
            header('Content-Type: text/plain');
            require $path;
        } else {
            http_response_code(404);
        }
        exit;
    }

    public function sitemap()
    {
        $path = BASE_PATH . '/sitemap.xml.php';
        if (file_exists($path)) {
            require $path;
        } else {
            http_response_code(404);
        }
        exit;
    }
}

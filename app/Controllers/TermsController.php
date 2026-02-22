<?php

class TermsController extends Controller
{
    public function index()
    {
        global $chungapi; // Để lấy setting cho tên web
        $this->view('terms/index', [
            'chungapi' => $chungapi
        ]);
    }
}

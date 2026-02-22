<?php

class PolicyController extends Controller
{
    public function index()
    {
        global $chungapi; // Để lấy setting cho tên web
        $this->view('policy/index', [
            'chungapi' => $chungapi
        ]);
    }
}

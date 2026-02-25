<?php

class MaintenanceController extends Controller
{
    public function index()
    {
        global $chungapi;

        $service = new MaintenanceService();
        $state = $service->getState(true);

        if (empty($state['active'])) {
            $this->redirect(url(''));
        }

        http_response_code(503);

        $this->view('system/maintenance', [
            'maintenanceState' => $state,
            'siteName' => (string) ($chungapi['ten_web'] ?? 'KaiShop'),
            'siteFavicon' => (string) ($chungapi['favicon'] ?? ''),
        ]);
    }
}

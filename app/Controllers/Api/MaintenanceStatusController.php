<?php

class MaintenanceStatusController extends Controller
{
    public function show()
    {
        $service = new MaintenanceService();
        $state = $service->getState(true);

        return $this->json([
            'success' => true,
            'maintenance' => array_merge($state, [
                'page_url' => url('bao-tri'),
            ]),
        ]);
    }
}


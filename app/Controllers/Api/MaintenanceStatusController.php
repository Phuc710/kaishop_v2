<?php

class MaintenanceStatusController extends Controller
{
    public function show()
    {
        $service = new MaintenanceService();
        $state = $service->getState(true);

        // Sanitize and expose only safe public fields
        $safeMaintenance = [
            'enabled' => (bool) ($state['enabled'] ?? false),
            'active' => (bool) ($state['active'] ?? false),
            'phase' => (string) ($state['phase'] ?? 'off'),
            'message' => (string) ($state['message'] ?? ''),
            'notice_active' => (bool) ($state['notice_active'] ?? false),
            'notice_seconds_left' => $state['notice_seconds_left'] ?? null,
            'seconds_until_start' => $state['seconds_until_start'] ?? null,
            'seconds_until_end' => $state['seconds_until_end'] ?? null,
            'start_at_ts' => $state['start_at_ts'] ?? null,
            'end_at_ts' => $state['end_at_ts'] ?? null,
            'show_end_countdown' => (bool) ($state['show_end_countdown'] ?? false),
            'server_time_ts' => time(),
            'page_url' => url('bao-tri'),
        ];

        return $this->json([
            'success' => true,
            'maintenance' => $safeMaintenance,
        ]);
    }
}


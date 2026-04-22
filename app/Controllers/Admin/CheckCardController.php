<?php

namespace Admin;

use AuthService;
use CheckCardCardGeneratorService;
use CheckCardGatewayService;
use CheckCardJobService;
use CheckCardRepository;
use Controller;
use Database;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class CheckCardController extends Controller
{
    private const ADMIN_LEVEL = 9;

    private AuthService $authService;
    private CheckCardJobService $jobService;

    public function __construct()
    {
        $this->authService = new AuthService();

        $repository = new CheckCardRepository(Database::getInstance()->getConnection());
        $cardGenerator = new CheckCardCardGeneratorService();
        $gatewayService = new CheckCardGatewayService($cardGenerator);

        $this->jobService = new CheckCardJobService(
            $repository,
            $gatewayService,
            $cardGenerator
        );
    }

    public function index()
    {
        $this->requireAdmin();

        $this->view('admin/checkcard/index', [
            'title' => 'Check Card',
            ...$this->jobService->getPageData(),
        ]);
    }

    public function startJob()
    {
        $this->requireAdmin();

        try {
            $this->json($this->jobService->start($this->input()));
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            $this->json(['error' => 'Start job failed'], 500);
        }
    }

    public function stopJob()
    {
        $this->requireAdmin();

        try {
            $this->json($this->jobService->stop((int) ($this->input()['job_id'] ?? 0)));
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            $this->json(['error' => 'Stop job failed'], 500);
        }
    }

    public function jobStatus()
    {
        $this->requireAdmin();

        $jobIds = array_values(array_filter(
            array_map('intval', explode(',', (string) $this->get('job_ids', '')))
        ));

        $lastLiveMap = [];
        foreach ($jobIds as $jobId) {
            $lastLiveMap[$jobId] = (int) $this->get('last_live_' . $jobId, 0);
        }

        $this->json($this->jobService->buildStatusPayload($jobIds, $lastLiveMap));
    }

    public function clearLog()
    {
        $this->requireAdmin();

        try {
            $this->json($this->jobService->clearLog((int) ($this->input()['job_id'] ?? 0)));
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            $this->json(['error' => 'Clear log failed'], 500);
        }
    }

    public function daemon()
    {
        $response = $this->jobService->handleDaemonRequest(
            (int) $this->get('job_id', 0),
            (string) $this->get('secret', '')
        );

        if ($response !== '') {
            exit($response);
        }
    }

    private function requireAdmin(): void
    {
        $this->authService->requireAuth();

        $user = $this->authService->getCurrentUser();

        if ((int) ($user['level'] ?? 0) !== self::ADMIN_LEVEL) {
            http_response_code(403);
            exit('Access denied');
        }
    }

    private function input(): array
    {
        return json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
    }
}

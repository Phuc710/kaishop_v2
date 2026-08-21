<?php

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

        $pageData = $this->jobService->getPageData();

        $this->view('admin/checkcard/index', array_merge([
            'title' => 'Check Card',
        ], $pageData));
    }

    public function startJob()
    {
        $this->requireAdmin();

        try {
            $this->json($this->jobService->start($this->input()));
        } catch (InvalidArgumentException | RuntimeException $e) {
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
        } catch (InvalidArgumentException | RuntimeException $e) {
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
        } catch (InvalidArgumentException | RuntimeException $e) {
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

    public function binLookup()
    {
        $this->requireAdmin();

        $bin = preg_replace('/[^0-9]/', '', (string) $this->get('bin', ''));
        $bin = substr($bin, 0, 8);

        if (strlen($bin) < 6) {
            $this->json(['error' => 'BIN phải có ít nhất 6 chữ số'], 400);
            return;
        }

        $ch = curl_init("https://lookup.binlist.net/{$bin}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_HTTPHEADER => ['Accept-Version: 3'],
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 200 && $body) {
            http_response_code(200);
            header('Content-Type: application/json');
            echo $body;
            exit;
        }

        // Secondary Fallback: HandyAPI
        $ch2 = curl_init("https://data.handyapi.com/bin/{$bin}");
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $body2 = curl_exec($ch2);
        $status2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);

        if ($status2 === 200 && $body2) {
            $data2 = json_decode($body2, true);
            if (($data2['Status'] ?? '') === 'SUCCESS') {
                $cc = $data2['Country']['A2'] ?? '';
                $flag = '🌍';
                if (strlen($cc) === 2 && function_exists('mb_chr')) {
                    $flag = mb_chr(127397 + ord(strtoupper($cc[0]))) . mb_chr(127397 + ord(strtoupper($cc[1])));
                }
                $normalized = [
                    'scheme' => strtolower($data2['Scheme'] ?? ''),
                    'type' => strtolower($data2['Type'] ?? ''),
                    'brand' => $data2['CardTier'] ?? '',
                    'country' => [
                        'name' => $data2['Country']['Name'] ?? '',
                        'emoji' => $flag,
                        'alpha2' => $cc,
                    ],
                    'bank' => [
                        'name' => $data2['Issuer'] ?? '',
                    ],
                ];
                $this->json($normalized);
                return;
            }
        }

        http_response_code($status ?: 404);
        header('Content-Type: application/json');
        echo $body ?: json_encode(['error' => 'BIN not found']);
        exit;
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
}


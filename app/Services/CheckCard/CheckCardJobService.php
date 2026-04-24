<?php

class CheckCardJobService
{
    private const DAEMON_SECRET = 'kaishop_cgpt_secret_v3';
    private const DEFAULT_BASE_IP = '178.128.110.246';
    private const MAX_LOG_ITEMS = 30;

    private array $binMetaCache = [];

    public function __construct(
        private CheckCardRepository $repository,
        private CheckCardGatewayService $gatewayService,
        private CheckCardCardGeneratorService $cardGenerator
    ) {
    }

    public function getPageData(): array
    {
        $this->repository->stopStaleRunningJobs();

        $gateways = $this->gatewayService->getGateways();
        $historyLives = [];
        
        foreach ($gateways as $id => $gate) {
            $historyLives[$id] = $this->repository->getLatestLivesByGate((string) $id, 100);
        }

        return [
            'gateways' => $gateways,
            'activeJobs' => $this->repository->getActiveJobs(),
            'globalTotals' => $this->repository->getGlobalTotals(),
            'historyLives' => $historyLives,
        ];
    }

    public function start(array $data): array
    {
        $gateId = (string) ($data['gate_id'] ?? '');
        $gateway = $this->gatewayService->getGateway($gateId);

        if ($gateway === null) {
            throw new InvalidArgumentException('Invalid gateway');
        }

        if ($this->repository->isGateRunning($gateId)) {
            throw new RuntimeException('Gateway already running');
        }

        $config = $this->normalizeConfig($data);
        $jobId = $this->repository->getOrCreateJobForGate(
            $gateId,
            (string) $gateway['name'],
            $config,
            (int) $config['threads'],
            0 // Force total_target to 0 for infinite loop
        );

        $this->triggerDaemon($jobId);

        return [
            'success' => true,
            'job_id' => $jobId,
        ];
    }

    public function stop(int $jobId): array
    {
        if ($jobId <= 0) {
            throw new InvalidArgumentException('Missing Job ID');
        }

        $this->repository->stopJob($jobId);

        return ['success' => true];
    }

    public function buildStatusPayload(array $jobIds, array $lastLiveMap): array
    {
        if ($jobIds === []) {
            return [];
        }

        $jobs = $this->repository->findJobsByIds($jobIds);
        $lives = [];

        foreach ($jobIds as $jobId) {
            $lives[$jobId] = $this->repository->getLiveRowsSince(
                $jobId,
                (int) ($lastLiveMap[$jobId] ?? 0)
            );
        }

        return [
            'jobs' => $jobs,
            'lives' => $lives,
            'global_totals' => $this->repository->getGlobalTotals(),
        ];
    }

    public function clearLog(int $jobId): array
    {
        if ($jobId <= 0) {
            throw new InvalidArgumentException('Missing Job ID');
        }

        $this->repository->clearJobLog($jobId);

        return ['success' => true];
    }

    public function handleDaemonRequest(int $jobId, string $secret): string
    {
        if (!$this->isDaemonAuthorized($jobId, $secret)) {
            return 'Unauthorized';
        }

        $job = $this->repository->findJobById($jobId);

        if (!$job || ($job['status'] ?? '') !== 'running') {
            return 'Stopped';
        }

        ignore_user_abort(true);
        set_time_limit(0);

        $this->runDaemonLoop($job);

        return '';
    }

    private function runDaemonLoop(array $job): void
    {
        $jobId = (int) $job['id'];
        $config = json_decode((string) ($job['config_json'] ?? '{}'), true) ?: [];
        $gateway = $this->gatewayService->getGateway((string) ($job['gate_id'] ?? ''));

        if ($gateway === null) {
            $this->repository->stopJob($jobId);
            return;
        }

        $checked = (int) ($job['checked_count'] ?? 0);
        $threads = max(1, (int) ($job['threads'] ?? 1));
        $startedAt = time();

        while (true) {
            if (!$this->repository->jobIsRunning($jobId)) {
                return;
            }

            $batchSize = $threads;

            $result = $this->gatewayService->runBatch($batchSize, $config, $gateway);

            $checked += $batchSize;
            $this->repository->updateJobProgress(
                $jobId,
                $checked,
                (int) ($result['live'] ?? 0),
                (int) ($result['dead'] ?? 0),
                (int) ($result['err'] ?? 0)
            );

            $this->persistApprovedCards(
                $jobId,
                (string) $gateway['name'],
                $result['approved_cards'] ?? []
            );

            if (time() - $startedAt >= 20) {
                $this->triggerDaemon($jobId);
                return;
            }
        }

        if ($this->repository->jobIsRunning($jobId)) {
            $this->repository->finishJob($jobId);
        }
    }

    private function persistApprovedCards(int $jobId, string $gateName, array $rows): void
    {
        foreach ($rows as $row) {
            $card = (string) ($row['card'] ?? '');

            if ($card === '') {
                continue;
            }

            $bin = substr(preg_replace('/\D/', '', $card), 0, 6);
            $meta = $this->resolveBinMeta($bin);

            $this->repository->saveLive($jobId, $gateName, [
                'card' => $card,
                'msg' => (string) ($row['msg'] ?? 'Approved'),
            ], $meta);
        }
    }

    private function resolveBinMeta(string $bin): array
    {
        $bin = substr(preg_replace('/[^0-9]/', '', $bin), 0, 8);
        if (strlen($bin) < 6) {
            return $this->fallbackBinMeta();
        }

        if (isset($this->binMetaCache[$bin])) {
            return $this->binMetaCache[$bin];
        }

        // Try binlist.net
        $ch = curl_init("https://lookup.binlist.net/{$bin}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Accept-Version: 3'],
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 200 && $body) {
            $data = json_decode($body, true);
            if (is_array($data)) {
                $meta = [
                    'bank' => $data['bank']['name'] ?? 'Unknown',
                    'country' => $data['country']['name'] ?? 'Unknown',
                    'flag' => $data['country']['emoji'] ?? '🏳',
                    'scheme' => strtoupper($data['scheme'] ?? '?'),
                    'type' => ucfirst($data['type'] ?? '?'),
                    'brand' => strtoupper($data['brand'] ?? 'CLASSIC'),
                    'extra_info' => '-',
                ];
                $this->binMetaCache[$bin] = $meta;
                return $meta;
            }
        }

        $fallback = $this->fallbackBinMeta();
        $this->binMetaCache[$bin] = $fallback;
        return $fallback;
    }

    private function fallbackBinMeta(): array
    {
        return [
            'bank' => 'Unknown',
            'country' => 'Unknown',
            'flag' => '?',
            'scheme' => '?',
            'type' => '?',
            'brand' => '?',
            'extra_info' => '-',
        ];
    }


    private function normalizeConfig(array $data): array
    {
        return [
            'api_url' => trim((string) ($data['api_url'] ?? '')),
            'api_param' => trim((string) ($data['api_param'] ?? '')) ?: 'card',
            'bin' => trim((string) ($data['bin'] ?? '')) ?: '515462',
            'mm' => strtoupper(trim((string) ($data['mm'] ?? ''))) ?: 'RN',
            'yy' => strtoupper(trim((string) ($data['yy'] ?? ''))) ?: 'RN',
            'cvv' => strtoupper(trim((string) ($data['cvv'] ?? ''))) ?: 'RN',
            'threads' => max(1, min(100, (int) ($data['threads'] ?? 20))),
            'batch' => max(0, (int) ($data['batch'] ?? 0)),
        ];
    }

    private function isDaemonAuthorized(int $jobId, string $secret): bool
    {
        return hash_equals($this->daemonSecret($jobId), $secret);
    }

    private function daemonSecret(int $jobId): string
    {
        return hash_hmac('sha256', (string) $jobId, self::DAEMON_SECRET);
    }

    private function triggerDaemon(int $jobId): void
    {
        $url = $this->resolveBaseUrl()
            . '/admin/api/check-card/daemon'
            . '?job_id=' . $jobId
            . '&secret=' . urlencode($this->daemonSecret($jobId));

        $handle = curl_init($url);

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_HTTPHEADER => ['Connection: close'],
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => 1,
        ]);

        curl_exec($handle);
        curl_close($handle);
    }

    private function resolveBaseUrl(): string
    {
        $appDir = defined('APP_DIR') ? rtrim((string) APP_DIR, '/') : '';
        $baseUrl = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';

        if ($baseUrl !== '') {
            $basePath = (string) (parse_url($baseUrl, PHP_URL_PATH) ?? '');

            if ($appDir !== '' && !str_ends_with(rtrim($basePath, '/'), $appDir)) {
                $baseUrl .= $appDir;
            }

            return $baseUrl;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . $appDir;
    }

}

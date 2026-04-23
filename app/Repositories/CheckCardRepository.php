<?php

class CheckCardRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function stopStaleRunningJobs(int $minutes = 5): void
    {
        $stmt = $this->db->prepare("
            UPDATE checkcard_jobs
            SET status = 'stopped'
            WHERE status = 'running'
              AND updated_at < NOW() - INTERVAL ? MINUTE
        ");

        $stmt->execute([$minutes]);
    }

    public function getActiveJobs(): array
    {
        return $this->db
            ->query("
                SELECT id, gate_id, status
                FROM checkcard_jobs
                WHERE status IN ('running', 'paused')
            ")
            ->fetchAll();
    }

    public function isGateRunning(string $gateId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM checkcard_jobs
            WHERE gate_id = ?
              AND status = 'running'
            LIMIT 1
        ");

        $stmt->execute([$gateId]);

        return (bool) $stmt->fetch();
    }

    public function createJob(
        string $gateId,
        string $gateName,
        array $config,
        int $threads,
        int $target
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO checkcard_jobs
            (
                gate_id,
                gate_name,
                config_json,
                threads,
                total_target,
                status
            )
            VALUES (?, ?, ?, ?, ?, 'running')
        ");

        $stmt->execute([
            $gateId,
            $gateName,
            json_encode($config, JSON_UNESCAPED_UNICODE),
            $threads,
            $target,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function stopJob(int $jobId): void
    {
        $stmt = $this->db->prepare("
            UPDATE checkcard_jobs
            SET status = 'stopped'
            WHERE id = ?
        ");

        $stmt->execute([$jobId]);
    }

    public function findJobsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("
            SELECT *
            FROM checkcard_jobs
            WHERE id IN ($placeholders)
        ");

        $stmt->execute(array_values($ids));

        return $stmt->fetchAll();
    }

    public function getLiveRowsSince(int $jobId, int $lastId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT id, card, bank, country, flag, scheme, type, brand, extra_info, message, created_at
            FROM checkcard_lives
            WHERE job_id = ?
              AND id > ?
            ORDER BY id ASC
            LIMIT ?
        ");

        $stmt->bindValue(1, $jobId, PDO::PARAM_INT);
        $stmt->bindValue(2, $lastId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function clearJobLog(int $jobId): void
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                DELETE FROM checkcard_lives
                WHERE job_id = ?
            ");
            $stmt->execute([$jobId]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function findJobById(int $jobId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM checkcard_jobs
            WHERE id = ?
        ");

        $stmt->execute([$jobId]);

        return $stmt->fetch() ?: null;
    }

    public function jobIsRunning(int $jobId): bool
    {
        $stmt = $this->db->prepare("
            SELECT status
            FROM checkcard_jobs
            WHERE id = ?
        ");

        $stmt->execute([$jobId]);

        return $stmt->fetchColumn() === 'running';
    }

    public function updateJobProgress(
        int $jobId,
        int $checkedCount,
        int $liveCount,
        int $deadCount,
        int $errorCount
    ): void {
        $stmt = $this->db->prepare("
            UPDATE checkcard_jobs
            SET checked_count = ?,
                live_count = live_count + ?,
                dead_count = dead_count + ?,
                err_count = err_count + ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $checkedCount,
            $liveCount,
            $deadCount,
            $errorCount,
            $jobId,
        ]);
    }

    public function finishJob(int $jobId): void
    {
        $stmt = $this->db->prepare("
            UPDATE checkcard_jobs
            SET status = 'finished'
            WHERE id = ?
        ");

        $stmt->execute([$jobId]);
    }


    public function saveLive(int $jobId, string $gateName, array $row, array $meta): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO checkcard_lives
            (
                job_id,
                card,
                bank,
                country,
                flag,
                scheme,
                type,
                brand,
                extra_info,
                gate_name,
                message
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $jobId,
            $row['card'],
            $meta['bank'],
            $meta['country'],
            $meta['flag'],
            $meta['scheme'],
            $meta['type'],
            $meta['brand'],
            $meta['extra_info'],
            $gateName,
            $row['msg'],
        ]);
    }

    public function getGlobalTotals(): array
    {
        return $this->db
            ->query("
                SELECT 
                    SUM(checked_count) as total,
                    SUM(live_count) as live,
                    SUM(dead_count) as dead,
                    SUM(err_count) as err
                FROM checkcard_jobs
            ")
            ->fetch() ?: [
                'total' => 0,
                'live' => 0,
                'dead' => 0,
                'err' => 0
            ];
    }
}

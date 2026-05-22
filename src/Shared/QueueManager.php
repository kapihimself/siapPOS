<?php
declare(strict_types=1);

namespace Siappos\Shared;

use PDO;

class QueueManager
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function push(string $jobClass, array $payload): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO jobs (job_class, payload, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)');
        $stmt->execute([$jobClass, json_encode($payload)]);
    }

    public function pop(): ?array
    {
        $this->pdo->beginTransaction();
        try {
            // Find oldest available job
            $stmt = $this->pdo->prepare("SELECT * FROM jobs WHERE status = 'pending' AND attempts < max_attempts ORDER BY created_at ASC LIMIT 1");
            $stmt->execute();
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                $this->pdo->rollBack();
                return null;
            }

            // Mark as processing
            $update = $this->pdo->prepare("UPDATE jobs SET status = 'processing', reserved_at = CURRENT_TIMESTAMP WHERE id = ?");
            $update->execute([$job['id']]);

            $this->pdo->commit();
            return $job;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return null;
        }
    }

    public function markCompleted(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE jobs SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function markFailed(int $id, string $error): void
    {
        $stmt = $this->pdo->prepare("UPDATE jobs SET status = 'failed', last_error = ?, attempts = attempts + 1 WHERE id = ?");
        $stmt->execute([$error, $id]);
    }
}

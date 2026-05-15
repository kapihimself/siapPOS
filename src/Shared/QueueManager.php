<?php
declare(strict_types=1);

namespace Siappos\Shared;

use PDO;

final class QueueManager
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function push(string $jobClass, array $payload): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO jobs (payload) VALUES (:payload)');
        $stmt->execute([
            ':payload' => json_encode([
                'class' => $jobClass,
                'data' => $payload
            ], JSON_THROW_ON_ERROR)
        ]);
    }

    public function pop(): ?array
    {
        $stmt = $this->pdo->query('SELECT * FROM jobs ORDER BY id ASC LIMIT 1');
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($job !== false) {
            $stmtDel = $this->pdo->prepare('DELETE FROM jobs WHERE id = :id');
            $stmtDel->execute([':id' => $job['id']]);

            $payload = json_decode((string) $job['payload'], true, 512, JSON_THROW_ON_ERROR);
            return [
                'id' => (int) $job['id'],
                'class' => $payload['class'],
                'data' => $payload['data']
            ];
        }

        return null;
    }
}

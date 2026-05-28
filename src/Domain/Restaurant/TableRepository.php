<?php
declare(strict_types=1);

namespace Siappos\Domain\Restaurant;

use PDO;

final class TableRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(int $businessId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM res_tables WHERE business_id = :business_id ORDER BY name ASC');
        $stmt->execute([':business_id' => $businessId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(int $businessId, string $name, ?string $description = null): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO res_tables (business_id, name, description) VALUES (:business_id, :name, :description)');
        $stmt->execute([
            ':business_id' => $businessId,
            ':name' => $name,
            ':description' => $description
        ]);
    }
}

<?php
declare(strict_types=1);

namespace Siappos\Domain\Product;

use PDO;

final class BrandRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(int $businessId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM brands WHERE business_id = :business_id ORDER BY name ASC');
        $stmt->execute([':business_id' => $businessId]);

        return $stmt->fetchAll();
    }

    public function create(int $businessId, string $name): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO brands (business_id, name, created_at, updated_at) VALUES (:business_id, :name, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':business_id' => $businessId,
            ':name' => $name,
        ]);
    }
}

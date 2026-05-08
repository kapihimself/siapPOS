<?php
declare(strict_types=1);

namespace Siappos\Domain\Taxonomy;

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
}

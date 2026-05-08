<?php
declare(strict_types=1);

namespace Siappos\Domain\Contact;

use PDO;

final class ContactRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(int $businessId, ?string $search = null): array
    {
        if ($search !== null && $search !== '') {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM contacts WHERE business_id = :business_id AND name LIKE :search ORDER BY name ASC'
            );
            $stmt->execute([':business_id' => $businessId, ':search' => '%' . $search . '%']);

            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare('SELECT * FROM contacts WHERE business_id = :business_id ORDER BY name ASC');
        $stmt->execute([':business_id' => $businessId]);

        return $stmt->fetchAll();
    }

    public function count(int $businessId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM contacts WHERE business_id = :business_id');
        $stmt->execute([':business_id' => $businessId]);
        return (int) $stmt->fetchColumn();
    }
}

<?php
declare(strict_types=1);

namespace Siappos\Domain\Restaurant;

use PDO;

final class ResModifierRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function getSets(int $businessId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM res_modifier_sets WHERE business_id = :business_id ORDER BY name ASC');
        $stmt->execute([':business_id' => $businessId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int, array<string, mixed>> */
    public function getModifiersBySet(int $setId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM res_modifiers WHERE modifier_set_id = :set_id ORDER BY name ASC');
        $stmt->execute([':set_id' => $setId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createSet(int $businessId, string $name): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO res_modifier_sets (business_id, name) VALUES (:business_id, :name)');
        $stmt->execute([
            ':business_id' => $businessId,
            ':name' => $name
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function createModifier(int $setId, string $name, int $priceCents): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO res_modifiers (modifier_set_id, name, price_cents) VALUES (:set_id, :name, :price_cents)');
        $stmt->execute([
            ':set_id' => $setId,
            ':name' => $name,
            ':price_cents' => $priceCents
        ]);
    }
}

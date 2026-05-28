<?php
declare(strict_types=1);

namespace Siappos\Domain\Restaurant;

use PDO;

class ResModifierRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getSets(int $businessId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM res_modifier_sets WHERE business_id = ? ORDER BY name ASC');
        $stmt->execute([$businessId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getModifiersInSet(int $setId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM res_modifiers WHERE modifier_set_id = ? ORDER BY name ASC');
        $stmt->execute([$setId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createSet(int $businessId, string $name): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO res_modifier_sets (business_id, name) VALUES (?, ?)');
        $stmt->execute([$businessId, $name]);
        return (int) $this->pdo->lastInsertId();
    }

    public function createModifier(int $setId, string $name, int $priceCents): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO res_modifiers (modifier_set_id, name, price_cents) VALUES (?, ?, ?)');
        $stmt->execute([$setId, $name, $priceCents]);
        return (int) $this->pdo->lastInsertId();
    }

    public function linkProductToModifierSet(int $productId, int $setId): void
    {
        $stmt = $this->pdo->prepare('INSERT OR IGNORE INTO res_product_modifier_sets (product_id, modifier_modifier_set_id) VALUES (?, ?)');
        $stmt->execute([$productId, $setId]);
    }

    public function unlinkProductFromModifierSet(int $productId, int $setId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM res_product_modifier_sets WHERE product_id = ? AND modifier_modifier_set_id = ?');
        $stmt->execute([$productId, $setId]);
    }

    public function getModifiersForProduct(int $productId): array
    {
        $sql = '
            SELECT m.id, m.name, m.price_cents, s.id as modifier_set_id, s.name as set_name
            FROM res_modifiers m
            JOIN res_modifier_sets s ON m.modifier_set_id = s.id
            JOIN res_product_modifier_sets ps ON s.id = ps.modifier_modifier_set_id
            WHERE ps.product_id = ?
            ORDER BY s.name ASC, m.name ASC
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

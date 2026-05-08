<?php
declare(strict_types=1);

namespace Siappos\Domain\Product;

use PDO;
use RuntimeException;

final class VariationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function activeForPos(int $businessId, ?string $search = null): array
    {
        if ($search !== null && $search !== '') {
            $stmt = $this->pdo->prepare(
                'SELECT v.*, p.name as product_name, p.type as product_type, c.name as category_name, b.name as brand_name, u.name as unit_name
                 FROM variations v
                 JOIN products p ON v.product_id = p.id
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN brands b ON p.brand_id = b.id
                 LEFT JOIN units u ON p.unit_id = u.id
                 WHERE p.business_id = :business_id AND p.is_active = 1 AND (p.name LIKE :search OR v.sku LIKE :search)
                 ORDER BY p.name ASC, v.name ASC'
            );
            $stmt->execute([':business_id' => $businessId, ':search' => '%' . $search . '%']);

            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare(
            'SELECT v.*, p.name as product_name, p.type as product_type, c.name as category_name, b.name as brand_name, u.name as unit_name
             FROM variations v
             JOIN products p ON v.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN brands b ON p.brand_id = b.id
             LEFT JOIN units u ON p.unit_id = u.id
             WHERE p.business_id = :business_id AND p.is_active = 1
             ORDER BY p.name ASC, v.name ASC'
        );
        $stmt->execute([':business_id' => $businessId]);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM variations WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function decrementStock(int $variationId, int $outletId, float $quantity): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM variation_location_details WHERE variation_id = :variation_id AND outlet_id = :outlet_id LIMIT 1');
        $stmt->execute([':variation_id' => $variationId, ':outlet_id' => $outletId]);
        $stock = $stmt->fetch();

        if (!is_array($stock)) {
            throw new RuntimeException('Stok produk tidak ditemukan untuk outlet ini.');
        }

        $currentStock = (float) $stock['qty_available'];

        if ($currentStock < $quantity) {
            throw new RuntimeException('Stok produk tidak mencukupi untuk transaksi.');
        }

        $stmtUpdate = $this->pdo->prepare(
            'UPDATE variation_location_details SET qty_available = :new_stock, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );

        $stmtUpdate->execute([
            ':id' => $stock['id'],
            ':new_stock' => round($currentStock - $quantity, 3),
        ]);
    }
}

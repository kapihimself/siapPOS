<?php
declare(strict_types=1);

namespace Siappos\Domain\Product;

use PDO;
use RuntimeException;

final class ProductRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(int $businessId, ?string $search = null): array
    {
        if ($search !== null && $search !== '') {
            $stmt = $this->pdo->prepare(
                'SELECT p.*, v.sku, v.sell_price_inc_tax_cents, c.name as category_name, b.name as brand_name, u.name as unit_name
                 FROM products p
                 LEFT JOIN variations v ON p.id = v.product_id
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN brands b ON p.brand_id = b.id
                 LEFT JOIN units u ON p.unit_id = u.id
                 WHERE p.business_id = :business_id AND (p.name LIKE :search OR v.sku LIKE :search) ORDER BY p.id DESC'
            );
            $stmt->execute([':business_id' => $businessId, ':search' => '%' . $search . '%']);

            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare('
            SELECT p.*, v.sku, v.sell_price_inc_tax_cents, c.name as category_name, b.name as brand_name, u.name as unit_name
            FROM products p
            LEFT JOIN variations v ON p.id = v.product_id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE p.business_id = :business_id ORDER BY p.id DESC
        ');
        $stmt->execute([':business_id' => $businessId]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $businessId, int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE business_id = :business_id AND id = :id LIMIT 1');
        $stmt->execute([':business_id' => $businessId, ':id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function delete(int $businessId, int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE business_id = :business_id AND id = :id');
        $stmt->execute([':business_id' => $businessId, ':id' => $id]);
    }

    public function count(int $businessId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM products WHERE business_id = :business_id');
        $stmt->execute([':business_id' => $businessId]);
        return (int) $stmt->fetchColumn();
    }
}

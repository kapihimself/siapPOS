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
                'SELECT * FROM products WHERE business_id = :business_id AND (name LIKE :search OR sku LIKE :search) ORDER BY id DESC'
            );
            $stmt->execute([':business_id' => $businessId, ':search' => '%' . $search . '%']);

            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE business_id = :business_id ORDER BY id DESC');
        $stmt->execute([':business_id' => $businessId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function activeForPos(int $businessId, ?string $search = null): array
    {
        if ($search !== null && $search !== '') {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM products WHERE business_id = :business_id AND is_active = 1 AND (name LIKE :search OR sku LIKE :search) ORDER BY name ASC'
            );
            $stmt->execute([':business_id' => $businessId, ':search' => '%' . $search . '%']);

            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE business_id = :business_id AND is_active = 1 ORDER BY name ASC');
        $stmt->execute([':business_id' => $businessId]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id, int $businessId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id = :id AND business_id = :business_id LIMIT 1');
        $stmt->execute([':id' => $id, ':business_id' => $businessId]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /** @param array<int, int> $ids @return array<int, array<string, mixed>> */
    public function findManyByIds(array $ids, int $businessId): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE business_id = ? AND id IN ($placeholders)");
        $params = array_merge([$businessId], array_values($ids));
        $stmt->execute($params);

        $rows = $stmt->fetchAll();
        $mapped = [];

        foreach ($rows as $row) {
            $mapped[(int) $row['id']] = $row;
        }

        return $mapped;
    }

    public function create(ProductData $data, int $businessId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (business_id, sku, name, unit, price_cents, stock_qty, is_active, created_at, updated_at)
             VALUES (:business_id, :sku, :name, :unit, :price_cents, :stock_qty, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );

        $stmt->execute([
            ':business_id' => $businessId,
            ':sku' => $data->sku,
            ':name' => $data->name,
            ':unit' => $data->unit,
            ':price_cents' => $data->priceCents,
            ':stock_qty' => $data->stockQty,
            ':is_active' => $data->isActive ? 1 : 0,
        ]);
    }

    public function update(int $id, ProductData $data, int $businessId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE products
             SET sku = :sku,
                 name = :name,
                 unit = :unit,
                 price_cents = :price_cents,
                 stock_qty = :stock_qty,
                 is_active = :is_active,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND business_id = :business_id'
        );

        $stmt->execute([
            ':id' => $id,
            ':business_id' => $businessId,
            ':sku' => $data->sku,
            ':name' => $data->name,
            ':unit' => $data->unit,
            ':price_cents' => $data->priceCents,
            ':stock_qty' => $data->stockQty,
            ':is_active' => $data->isActive ? 1 : 0,
        ]);
    }

    public function delete(int $id, int $businessId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = :id AND business_id = :business_id');
        $stmt->execute([':id' => $id, ':business_id' => $businessId]);
    }

    public function decrementStock(int $id, float $quantity, int $businessId): void
    {
        $product = $this->find($id, $businessId);

        if (!is_array($product)) {
            throw new RuntimeException('Produk tidak ditemukan saat update stok.');
        }

        $currentStock = (float) $product['stock_qty'];

        if ($currentStock < $quantity) {
            throw new RuntimeException('Stok produk tidak mencukupi untuk transaksi.');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE products SET stock_qty = :new_stock, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND business_id = :business_id'
        );

        $stmt->execute([
            ':id' => $id,
            ':business_id' => $businessId,
            ':new_stock' => round($currentStock - $quantity, 3),
        ]);
    }

    public function count(int $businessId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM products WHERE business_id = :business_id');
        $stmt->execute([':business_id' => $businessId]);
        return (int) $stmt->fetchColumn();
    }
}

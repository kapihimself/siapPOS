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
    public function all(?string $search = null): array
    {
        if ($search !== null && $search !== '') {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM products WHERE name LIKE :search OR sku LIKE :search ORDER BY id DESC'
            );
            $stmt->execute([':search' => '%' . $search . '%']);

            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->query('SELECT * FROM products ORDER BY id DESC');

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function activeForPos(?string $search = null): array
    {
        if ($search !== null && $search !== '') {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM products WHERE is_active = 1 AND (name LIKE :search OR sku LIKE :search) ORDER BY name ASC'
            );
            $stmt->execute([':search' => '%' . $search . '%']);

            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->query('SELECT * FROM products WHERE is_active = 1 ORDER BY name ASC');

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /** @param array<int, int> $ids @return array<int, array<string, mixed>> */
    public function findManyByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute(array_values($ids));

        $rows = $stmt->fetchAll();
        $mapped = [];

        foreach ($rows as $row) {
            $mapped[(int) $row['id']] = $row;
        }

        return $mapped;
    }

    public function create(ProductData $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (sku, name, unit, price_cents, stock_qty, is_active, created_at, updated_at)
             VALUES (:sku, :name, :unit, :price_cents, :stock_qty, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );

        $stmt->execute([
            ':sku' => $data->sku,
            ':name' => $data->name,
            ':unit' => $data->unit,
            ':price_cents' => $data->priceCents,
            ':stock_qty' => $data->stockQty,
            ':is_active' => $data->isActive ? 1 : 0,
        ]);
    }

    public function update(int $id, ProductData $data): void
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
             WHERE id = :id'
        );

        $stmt->execute([
            ':id' => $id,
            ':sku' => $data->sku,
            ':name' => $data->name,
            ':unit' => $data->unit,
            ':price_cents' => $data->priceCents,
            ':stock_qty' => $data->stockQty,
            ':is_active' => $data->isActive ? 1 : 0,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function decrementStock(int $id, float $quantity): void
    {
        $product = $this->find($id);

        if (!is_array($product)) {
            throw new RuntimeException('Produk tidak ditemukan saat update stok.');
        }

        $currentStock = (float) $product['stock_qty'];

        if ($currentStock < $quantity) {
            throw new RuntimeException('Stok produk tidak mencukupi untuk transaksi.');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE products SET stock_qty = :new_stock, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );

        $stmt->execute([
            ':id' => $id,
            ':new_stock' => round($currentStock - $quantity, 3),
        ]);
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }
}

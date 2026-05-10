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
        $baseSql = "
            SELECT
                p.id as id,
                NULL as variation_id,
                p.name as name,
                p.sku as sku,
                p.price_cents as price_cents,
                p.stock_qty as stock_qty,
                p.type as type
            FROM products p
            WHERE p.business_id = :business_id AND p.is_active = 1 AND p.type = 'single'

            UNION ALL

            SELECT
                p.id as id,
                v.id as variation_id,
                p.name || ' - ' || v.name as name,
                v.sku as sku,
                v.price_cents as price_cents,
                v.stock_qty as stock_qty,
                p.type as type
            FROM products p
            JOIN product_variations v ON v.product_id = p.id
            WHERE p.business_id = :business_id AND p.is_active = 1 AND p.type = 'variable'
        ";

        if ($search !== null && $search !== '') {
            $sql = "
                WITH combined AS ($baseSql)
                SELECT * FROM combined
                WHERE name LIKE :search OR sku LIKE :search
                ORDER BY name ASC
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':business_id' => $businessId,
                ':search' => '%' . $search . '%'
            ]);
            return $stmt->fetchAll();
        }

        $sql = "
            WITH combined AS ($baseSql)
            SELECT * FROM combined
            ORDER BY name ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':business_id' => $businessId]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id, int $businessId, ?int $variationId = null): ?array
    {
        if ($variationId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT
                    p.*,
                    v.id as variation_id,
                    p.name || ' - ' || v.name as name,
                    v.sku as sku,
                    v.price_cents as price_cents,
                    v.stock_qty as stock_qty
                FROM products p
                JOIN product_variations v ON v.product_id = p.id
                WHERE p.id = :id AND v.id = :variation_id AND p.business_id = :business_id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id, ':variation_id' => $variationId, ':business_id' => $businessId]);
            $row = $stmt->fetch();
            return is_array($row) ? $row : null;
        }

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

    public function decrementStock(int $id, float $quantity, int $businessId, ?int $variationId = null): void
    {
        if ($variationId !== null) {
            $stmt = $this->pdo->prepare('SELECT stock_qty FROM product_variations WHERE id = :id AND product_id = :product_id LIMIT 1');
            $stmt->execute([':id' => $variationId, ':product_id' => $id]);
            $currentStock = $stmt->fetchColumn();

            if ($currentStock === false) {
                throw new RuntimeException('Variasi produk tidak ditemukan saat update stok.');
            }

            $currentStock = (float) $currentStock;
            if ($currentStock < $quantity) {
                throw new RuntimeException('Stok variasi tidak mencukupi untuk transaksi.');
            }

            $updateStmt = $this->pdo->prepare(
                'UPDATE product_variations SET stock_qty = :new_stock, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            $updateStmt->execute([
                ':id' => $variationId,
                ':new_stock' => round($currentStock - $quantity, 3),
            ]);

            return;
        }

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



    public function adjustStock(int $id, float $quantityDelta, int $businessId, ?int $variationId = null): void
    {
        if ($variationId !== null) {
            $stmt = $this->pdo->prepare('SELECT stock_qty FROM product_variations WHERE id = :id AND product_id = :product_id LIMIT 1');
            $stmt->execute([':id' => $variationId, ':product_id' => $id]);
            $currentStock = $stmt->fetchColumn();

            if ($currentStock === false) {
                throw new \RuntimeException('Variasi produk tidak ditemukan saat update stok.');
            }

            $newStock = (float) $currentStock + $quantityDelta;
            if ($newStock < 0) {
                throw new \RuntimeException('Stok tidak boleh negatif.');
            }

            $updateStmt = $this->pdo->prepare(
                'UPDATE product_variations SET stock_qty = :new_stock, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            $updateStmt->execute([
                ':id' => $variationId,
                ':new_stock' => round($newStock, 3),
            ]);

            return;
        }

        $product = $this->find($id, $businessId);

        if (!is_array($product)) {
            throw new \RuntimeException('Produk tidak ditemukan saat update stok.');
        }

        $currentStock = (float) $product['stock_qty'];
        $newStock = $currentStock + $quantityDelta;

        if ($newStock < 0) {
            throw new \RuntimeException('Stok produk tidak boleh negatif.');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE products SET stock_qty = :new_stock, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND business_id = :business_id'
        );

        $stmt->execute([
            ':id' => $id,
            ':business_id' => $businessId,
            ':new_stock' => round($newStock, 3),
        ]);
    }


    public function count(int $businessId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM products WHERE business_id = :business_id');
        $stmt->execute([':business_id' => $businessId]);
        return (int) $stmt->fetchColumn();
    }
}

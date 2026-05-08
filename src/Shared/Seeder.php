<?php
declare(strict_types=1);

namespace Siappos\Shared;

use PDO;

final class Seeder
{
    public static function seed(PDO $pdo): void
    {
        $businessId = self::seedBusiness($pdo);
        $outletId = self::seedOutlets($pdo, $businessId);
        self::seedSettings($pdo, $businessId);
        self::seedUsers($pdo, $businessId);
        self::seedContacts($pdo, $businessId);
        $categoryId = self::seedCategory($pdo, $businessId);
        $brandId = self::seedBrand($pdo, $businessId);
        $unitId = self::seedUnit($pdo, $businessId);
        self::seedProducts($pdo, $businessId, $outletId, $categoryId, $brandId, $unitId);
    }

    private static function seedBusiness(PDO $pdo): int
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM businesses')->fetchColumn();

        if ($count > 0) {
            return (int) $pdo->query('SELECT id FROM businesses ORDER BY id ASC LIMIT 1')->fetchColumn();
        }

        $stmt = $pdo->prepare('INSERT INTO businesses (name) VALUES (:name)');
        $stmt->execute([':name' => 'Demo Business']);

        return (int) $pdo->lastInsertId();
    }

    private static function seedOutlets(PDO $pdo, int $businessId): int
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM outlets')->fetchColumn();

        if ($count > 0) {
            return (int) $pdo->query('SELECT id FROM outlets ORDER BY id ASC LIMIT 1')->fetchColumn();
        }

        $stmt = $pdo->prepare('INSERT INTO outlets (business_id, name, address, phone) VALUES (:business_id, :name, :address, :phone)');
        $stmt->execute([
            ':business_id' => $businessId,
            ':name' => 'Outlet Utama',
            ':address' => 'Belum diatur',
            ':phone' => '-',
        ]);

        return (int) $pdo->lastInsertId();
    }

    private static function seedSettings(PDO $pdo, int $businessId): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();

        if ($count > 0) {
            $pdo->exec(
                "UPDATE settings
                 SET outlet_name = COALESCE(NULLIF(outlet_name, ''), 'Outlet Utama'),
                     active_template = COALESCE(NULLIF(active_template, ''), 'retail')"
            );

            return;
        }

        $stmt = $pdo->prepare('INSERT INTO settings (business_id, business_name, outlet_name, active_template, onboarding_completed, pb1_rate, currency_code) VALUES (:business_id, :business_name, :outlet_name, :active_template, :onboarding_completed, :pb1_rate, :currency_code)');
        $stmt->execute([
            ':business_id' => $businessId,
            ':business_name' => 'SiapPOS Demo',
            ':outlet_name' => 'Outlet Utama',
            ':active_template' => 'retail',
            ':onboarding_completed' => 0,
            ':pb1_rate' => 10,
            ':currency_code' => 'IDR',
        ]);
    }

    private static function seedUsers(PDO $pdo, int $businessId): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

        if ($count > 0) {
            return;
        }

        $users = [
            ['owner', 'Owner SiapPOS', 'admin', '1111'],
            ['manager', 'Manager Shift', 'manager', '2222'],
            ['cashier', 'Kasir Utama', 'cashier', '3333'],
        ];

        $stmt = $pdo->prepare('INSERT INTO users (business_id, username, full_name, role, pin_hash) VALUES (:business_id, :username, :full_name, :role, :pin_hash)');

        foreach ($users as [$username, $fullName, $role, $pin]) {
            $stmt->execute([
                ':business_id' => $businessId,
                ':username' => $username,
                ':full_name' => $fullName,
                ':role' => $role,
                ':pin_hash' => password_hash($pin, PASSWORD_BCRYPT),
            ]);
        }
    }

    private static function seedContacts(PDO $pdo, int $businessId): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM contacts')->fetchColumn();

        if ($count > 0) {
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO contacts (business_id, type, name, email, phone) VALUES (:business_id, :type, :name, :email, :phone)');
        $stmt->execute([
            ':business_id' => $businessId,
            ':type' => 'customer',
            ':name' => 'Pelanggan Umum (Walk-in)',
            ':email' => '-',
            ':phone' => '-',
        ]);
        $stmt->execute([
            ':business_id' => $businessId,
            ':type' => 'supplier',
            ':name' => 'Supplier Kopi ABC',
            ':email' => 'abc@supplier.com',
            ':phone' => '081234567890',
        ]);
    }

    private static function seedCategory(PDO $pdo, int $businessId): int
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();

        if ($count > 0) {
            return (int) $pdo->query('SELECT id FROM categories ORDER BY id ASC LIMIT 1')->fetchColumn();
        }

        $stmt = $pdo->prepare('INSERT INTO categories (business_id, name, description) VALUES (:business_id, :name, :description)');
        $stmt->execute([
            ':business_id' => $businessId,
            ':name' => 'Minuman',
            ':description' => 'Segala jenis minuman',
        ]);

        return (int) $pdo->lastInsertId();
    }

    private static function seedBrand(PDO $pdo, int $businessId): int
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM brands')->fetchColumn();

        if ($count > 0) {
            return (int) $pdo->query('SELECT id FROM brands ORDER BY id ASC LIMIT 1')->fetchColumn();
        }

        $stmt = $pdo->prepare('INSERT INTO brands (business_id, name, description) VALUES (:business_id, :name, :description)');
        $stmt->execute([
            ':business_id' => $businessId,
            ':name' => 'SiapPOS Signature',
            ':description' => 'Produk unggulan buatan sendiri',
        ]);

        return (int) $pdo->lastInsertId();
    }

    private static function seedUnit(PDO $pdo, int $businessId): int
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM units')->fetchColumn();

        if ($count > 0) {
            return (int) $pdo->query('SELECT id FROM units ORDER BY id ASC LIMIT 1')->fetchColumn();
        }

        $stmt = $pdo->prepare('INSERT INTO units (business_id, name, short_name, allow_decimal) VALUES (:business_id, :name, :short_name, :allow_decimal)');
        $stmt->execute([
            ':business_id' => $businessId,
            ':name' => 'Cup',
            ':short_name' => 'cup',
            ':allow_decimal' => 0,
        ]);

        return (int) $pdo->lastInsertId();
    }

    private static function seedProducts(PDO $pdo, int $businessId, int $outletId, int $categoryId, int $brandId, int $unitId): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

        if ($count > 0) {
            return;
        }

        $products = [
            ['SKU-KOPI-001', 'Kopi Susu Gula Aren', 22000, 120.000],
            ['SKU-TEH-001', 'Teh Lemon', 18000, 90.000],
        ];

        $stmtProduct = $pdo->prepare('INSERT INTO products (business_id, name, category_id, brand_id, unit_id, type) VALUES (:business_id, :name, :category_id, :brand_id, :unit_id, "single")');
        $stmtVariation = $pdo->prepare('INSERT INTO variations (product_id, sku, name, sell_price_inc_tax_cents) VALUES (:product_id, :sku, "DUMMY", :price)');
        $stmtLocation = $pdo->prepare('INSERT INTO variation_location_details (variation_id, outlet_id, qty_available) VALUES (:variation_id, :outlet_id, :qty)');

        foreach ($products as [$sku, $name, $priceCents, $stockQty]) {
            // 1. Insert Parent Product
            $stmtProduct->execute([
                ':business_id' => $businessId,
                ':name' => $name,
                ':category_id' => $categoryId,
                ':brand_id' => $brandId,
                ':unit_id' => $unitId,
            ]);
            $productId = (int) $pdo->lastInsertId();

            // 2. Insert Dummy Variation (Single Product pattern)
            $stmtVariation->execute([
                ':product_id' => $productId,
                ':sku' => $sku,
                ':price' => $priceCents,
            ]);
            $variationId = (int) $pdo->lastInsertId();

            // 3. Insert Location Details (Stock)
            $stmtLocation->execute([
                ':variation_id' => $variationId,
                ':outlet_id' => $outletId,
                ':qty' => $stockQty,
            ]);
        }
    }
}

<?php
declare(strict_types=1);

namespace Siappos\Shared;

use PDO;

final class Seeder
{
    public static function seed(PDO $pdo): void
    {
        $businessId = self::seedBusiness($pdo);
        self::seedOutlets($pdo, $businessId);
        self::seedSettings($pdo, $businessId);
        self::seedUsers($pdo, $businessId);
        self::seedProducts($pdo, $businessId);
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

    private static function seedOutlets(PDO $pdo, int $businessId): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM outlets')->fetchColumn();

        if ($count > 0) {
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO outlets (business_id, name, address, phone) VALUES (:business_id, :name, :address, :phone)');
        $stmt->execute([
            ':business_id' => $businessId,
            ':name' => 'Outlet Utama',
            ':address' => 'Belum diatur',
            ':phone' => '-',
        ]);
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

    private static function seedProducts(PDO $pdo, int $businessId): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

        if ($count > 0) {
            return;
        }

        $products = [
            ['SKU-KOPI-001', 'Kopi Susu Gula Aren', 'cup', 22000, 120.000],
            ['SKU-ROTI-001', 'Roti Butter', 'pcs', 8000, 85.000],
            ['SKU-GULA-001', 'Gula Curah', 'kg', 14000, 32.500],
            ['SKU-TEH-001', 'Teh Lemon', 'cup', 18000, 90.000],
            ['SKU-AIR-001', 'Air Mineral 600ml', 'botol', 6000, 200.000],
        ];

        $stmt = $pdo->prepare('INSERT INTO products (business_id, sku, name, unit, price_cents, stock_qty, is_active) VALUES (:business_id, :sku, :name, :unit, :price_cents, :stock_qty, 1)');

        foreach ($products as [$sku, $name, $unit, $priceCents, $stockQty]) {
            $stmt->execute([
                ':business_id' => $businessId,
                ':sku' => $sku,
                ':name' => $name,
                ':unit' => $unit,
                ':price_cents' => $priceCents,
                ':stock_qty' => $stockQty,
            ]);
        }
    }
}

<?php
declare(strict_types=1);

namespace Siappos\Domain\Auth\Actions;

use PDO;
use RuntimeException;
use Siappos\Domain\Settings\BusinessTemplate;

final class RegisterTenantAction
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function execute(string $businessName, string $username, string $fullName, string $pin): void
    {
        $this->pdo->beginTransaction();

        try {
            // Check if username already exists globally (for login simplicity)
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
            $stmt->execute([':username' => $username]);
            if ($stmt->fetchColumn() > 0) {
                throw new RuntimeException('Username sudah digunakan. Silakan pilih username lain.');
            }

            // Create Business
            $stmt = $this->pdo->prepare('INSERT INTO businesses (name) VALUES (:name)');
            $stmt->execute([':name' => $businessName]);
            $businessId = (int) $this->pdo->lastInsertId();

            // Create Owner User
            $stmt = $this->pdo->prepare('INSERT INTO users (business_id, username, full_name, role, pin_hash) VALUES (:business_id, :username, :full_name, :role, :pin_hash)');
            $stmt->execute([
                ':business_id' => $businessId,
                ':username' => $username,
                ':full_name' => $fullName,
                ':role' => 'admin',
                ':pin_hash' => password_hash($pin, PASSWORD_BCRYPT),
            ]);

            // Create Default Outlet
            $stmt = $this->pdo->prepare('INSERT INTO outlets (business_id, name, address, phone) VALUES (:business_id, :name, :address, :phone)');
            $stmt->execute([
                ':business_id' => $businessId,
                ':name' => 'Outlet Utama',
                ':address' => '-',
                ':phone' => '-',
            ]);

            // Create Default Settings
            $stmt = $this->pdo->prepare('INSERT INTO settings (business_id, business_name, outlet_name, active_template, onboarding_completed, pb1_rate, currency_code) VALUES (:business_id, :business_name, :outlet_name, :active_template, :onboarding_completed, :pb1_rate, :currency_code)');
            $stmt->execute([
                ':business_id' => $businessId,
                ':business_name' => $businessName,
                ':outlet_name' => 'Outlet Utama',
                ':active_template' => BusinessTemplate::Retail->value,
                ':onboarding_completed' => 0,
                ':pb1_rate' => 10,
                ':currency_code' => 'IDR',
            ]);

            $this->pdo->commit();
        } catch (\Throwable $th) {
            $this->pdo->rollBack();
            throw $th;
        }
    }
}

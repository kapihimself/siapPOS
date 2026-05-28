<?php
declare(strict_types=1);

namespace Siappos\Domain\Auth;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function findByPin(string $pin, int $businessId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE business_id = :business_id');
        $stmt->execute([':business_id' => $businessId]);

        foreach ($stmt->fetchAll() as $user) {
            if (password_verify($pin, (string) $user['pin_hash'])) {
                return $user;
            }
        }

        return null;
    }

    /** @return array<int, array<string, mixed>> */
    public function all(int $businessId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, full_name, role, created_at FROM users WHERE business_id = :business_id ORDER BY id ASC');
        $stmt->execute([':business_id' => $businessId]);

        return $stmt->fetchAll();
    }

    public function count(int $businessId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE business_id = :business_id');
        $stmt->execute([':business_id' => $businessId]);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public function getCommissionAgents(int $businessId): array
    {
        $stmt = $this->pdo->prepare("SELECT id, username, full_name, role FROM users WHERE business_id = :business_id AND role IN ('admin', 'manager', 'cashier') ORDER BY full_name ASC");
        $stmt->execute([':business_id' => $businessId]);
        return $stmt->fetchAll();
    }
}

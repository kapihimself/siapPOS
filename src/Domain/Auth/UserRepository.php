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
        // For SaaS, login usually either includes business domain, or usernames are globally unique.
        // If usernames are unique per business, the login form needs to know the business.
        // Assuming global uniqueness of username for login simplicity in this demo.
        // Otherwise, this query will just pick the first user with the username across all businesses.
        // The schema still enforces unique(business_id, username).
        // We will fetch the business_id along with the user.
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function findByPin(string $pin): ?array
    {
        // PIN login is usually per-device/per-tenant.
        // We should ideally filter by business_id here, but PIN login isn't currently receiving business_id.
        $stmt = $this->pdo->query('SELECT * FROM users');

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
        $stmt = $this->pdo->prepare('SELECT id, business_id, username, full_name, role, created_at FROM users WHERE business_id = :business_id ORDER BY id ASC');
        $stmt->execute([':business_id' => $businessId]);

        return $stmt->fetchAll();
    }

    public function count(int $businessId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE business_id = :business_id');
        $stmt->execute([':business_id' => $businessId]);
        return (int) $stmt->fetchColumn();
    }
}

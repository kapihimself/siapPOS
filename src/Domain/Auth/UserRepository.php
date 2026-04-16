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

    public function findByPin(string $pin): ?array
    {
        $stmt = $this->pdo->query('SELECT * FROM users');

        foreach ($stmt->fetchAll() as $user) {
            if (password_verify($pin, (string) $user['pin_hash'])) {
                return $user;
            }
        }

        return null;
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, username, full_name, role, created_at FROM users ORDER BY id ASC');

        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}

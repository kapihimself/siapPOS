<?php
declare(strict_types=1);

namespace Siappos\App;

final class Auth
{
    /** @param array<string, mixed> $user */
    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['auth_user'] = [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'full_name' => (string) $user['full_name'],
            'role' => (string) $user['role'],
            'logged_in_at' => time(),
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['auth_user']);
        unset($_SESSION['_csrf_token']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        $user = $_SESSION['auth_user'] ?? null;

        return is_array($user) ? $user : null;
    }

    public static function check(): bool
    {
        return is_array(self::user());
    }

    public static function id(): int
    {
        $user = self::user();

        return (int) ($user['id'] ?? 0);
    }

    public static function role(): string
    {
        $user = self::user();

        return (string) ($user['role'] ?? 'guest');
    }

    public static function hasAnyRole(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }
}

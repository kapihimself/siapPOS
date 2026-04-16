<?php
declare(strict_types=1);

namespace Siappos\Shared;

final class Flash
{
    public static function success(string $message): void
    {
        $_SESSION['_flash_success'] = $message;
    }

    public static function error(string $message): void
    {
        $_SESSION['_flash_error'] = $message;
    }

    public static function consumeSuccess(): ?string
    {
        $message = $_SESSION['_flash_success'] ?? null;
        unset($_SESSION['_flash_success']);

        return is_string($message) ? $message : null;
    }

    public static function consumeError(): ?string
    {
        $message = $_SESSION['_flash_error'] ?? null;
        unset($_SESSION['_flash_error']);

        return is_string($message) ? $message : null;
    }
}

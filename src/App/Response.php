<?php
declare(strict_types=1);

namespace Siappos\App;

final class Response
{
    public static function redirect(string $to): never
    {
        header('Location: ' . $to);
        exit;
    }
}

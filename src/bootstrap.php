<?php
declare(strict_types=1);

if (!defined('SIAPPOS_ROOT')) {
    define('SIAPPOS_ROOT', dirname(__DIR__));
}

date_default_timezone_set('Asia/Jakarta');

spl_autoload_register(static function (string $class): void {
    $prefix = 'Siappos\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = SIAPPOS_ROOT . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

$pdo = \Siappos\Shared\Database::connect(SIAPPOS_ROOT . '/storage/siappos.sqlite');
\Siappos\Shared\Seeder::seed($pdo);

<?php
declare(strict_types=1);

namespace Siappos\App;

use RuntimeException;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $viewName, array $data = []): void
    {
        $templatePath = SIAPPOS_ROOT . '/views/' . $viewName . '.php';

        if (!is_file($templatePath)) {
            throw new RuntimeException('View not found: ' . $viewName);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $templatePath;
        $content = (string) ob_get_clean();

        $layoutPath = SIAPPOS_ROOT . '/views/layout.php';
        require $layoutPath;
    }
}

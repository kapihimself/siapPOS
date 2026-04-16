<?php
declare(strict_types=1);

namespace Siappos\Shared;

final class Money
{
    public static function toCents(float|string $value): int
    {
        $normalized = self::normalizeDecimal($value);

        return (int) round($normalized * 100);
    }

    public static function toFloat(float|string $value): float
    {
        return self::normalizeDecimal($value);
    }

    public static function formatCents(int $cents): string
    {
        $negative = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return $negative . 'Rp' . number_format($absolute / 100, 2, ',', '.');
    }

    private static function normalizeDecimal(float|string $value): float
    {
        if (is_float($value)) {
            return $value;
        }

        $clean = str_replace(' ', '', trim($value));

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (str_contains($clean, ',')) {
            $clean = str_replace(',', '.', $clean);
        }

        return (float) $clean;
    }
}

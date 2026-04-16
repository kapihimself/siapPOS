<?php
declare(strict_types=1);

namespace Siappos\Domain\Product;

use InvalidArgumentException;
use Siappos\Shared\Money;

final class ProductData
{
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly string $unit,
        public readonly int $priceCents,
        public readonly float $stockQty,
        public readonly bool $isActive = true,
    ) {
        if ($this->sku === '') {
            throw new InvalidArgumentException('SKU wajib diisi.');
        }

        if ($this->name === '') {
            throw new InvalidArgumentException('Nama produk wajib diisi.');
        }

        if ($this->unit === '') {
            throw new InvalidArgumentException('Satuan wajib diisi.');
        }

        if ($this->priceCents < 0) {
            throw new InvalidArgumentException('Harga tidak boleh negatif.');
        }

        if ($this->stockQty < 0) {
            throw new InvalidArgumentException('Stok tidak boleh negatif.');
        }
    }

    /** @param array<string, mixed> $payload */
    public static function fromRequest(array $payload): self
    {
        $priceValue = (string) ($payload['price'] ?? '0');
        $stockValue = self::normalizeDecimal((string) ($payload['stock_qty'] ?? '0'));

        return new self(
            sku: strtoupper(trim((string) ($payload['sku'] ?? ''))),
            name: trim((string) ($payload['name'] ?? '')),
            unit: trim((string) ($payload['unit'] ?? 'pcs')),
            priceCents: Money::toCents($priceValue),
            stockQty: $stockValue,
            isActive: ((int) ($payload['is_active'] ?? 1)) === 1,
        );
    }

    private static function normalizeDecimal(string $value): float
    {
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

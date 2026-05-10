<?php
declare(strict_types=1);

namespace Siappos\Domain\Transaction\DTO;

use InvalidArgumentException;

final class PurchaseLineData
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly float $qty,
        public readonly int $unitPriceCents,
        public readonly int $lineTotalCents,
        public readonly ?int $variationId = null,
    ) {
        if ($this->productId <= 0) {
            throw new InvalidArgumentException('Produk tidak valid di baris pembelian.');
        }

        if ($this->qty <= 0) {
            throw new InvalidArgumentException('Kuantitas harus lebih besar dari 0.');
        }

        if ($this->unitPriceCents < 0 || $this->lineTotalCents < 0) {
            throw new InvalidArgumentException('Harga tidak valid.');
        }
    }
}

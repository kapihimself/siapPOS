<?php
declare(strict_types=1);

namespace Siappos\Domain\Order\DTO;

use InvalidArgumentException;

final class CartItemData
{
    public function __construct(
        public readonly int $productId,
        public readonly float $qty,
    ) {
        if ($this->productId <= 0) {
            throw new InvalidArgumentException('Produk tidak valid di cart.');
        }

        if ($this->qty <= 0) {
            throw new InvalidArgumentException('Qty harus lebih besar dari 0.');
        }
    }
}

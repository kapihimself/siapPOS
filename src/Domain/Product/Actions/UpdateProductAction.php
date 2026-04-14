<?php
declare(strict_types=1);

namespace Siappos\Domain\Product\Actions;

use Siappos\Domain\Product\ProductData;
use Siappos\Domain\Product\ProductRepository;

final class UpdateProductAction
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function execute(int $id, ProductData $data): void
    {
        $this->products->update($id, $data);
    }
}

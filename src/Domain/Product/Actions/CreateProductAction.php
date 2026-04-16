<?php
declare(strict_types=1);

namespace Siappos\Domain\Product\Actions;

use Siappos\Domain\Product\ProductData;
use Siappos\Domain\Product\ProductRepository;

final class CreateProductAction
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function execute(ProductData $data): void
    {
        $this->products->create($data);
    }
}

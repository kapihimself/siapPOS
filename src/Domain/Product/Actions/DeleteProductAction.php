<?php
declare(strict_types=1);

namespace Siappos\Domain\Product\Actions;

use Siappos\Domain\Product\ProductRepository;

final class DeleteProductAction
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function execute(int $id): void
    {
        $this->products->delete($id);
    }
}

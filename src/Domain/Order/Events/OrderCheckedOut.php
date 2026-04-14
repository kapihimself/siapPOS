<?php
declare(strict_types=1);

namespace Siappos\Domain\Order\Events;

final class OrderCheckedOut
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $orderNumber,
        public readonly int $totalCents,
        public readonly string $paymentMethod,
        public readonly int $userId,
    ) {
    }
}

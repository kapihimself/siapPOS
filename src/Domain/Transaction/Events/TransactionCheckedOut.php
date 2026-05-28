<?php
declare(strict_types=1);

namespace Siappos\Domain\Transaction\Events;

final class TransactionCheckedOut
{
    public function __construct(
        public readonly int $businessId,
        public readonly int $transactionId,
        public readonly string $transactionNumber,
        public readonly int $totalCents,
        public readonly string $paymentMethod,
        public readonly int $userId,
    ) {
    }
}

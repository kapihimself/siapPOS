<?php
declare(strict_types=1);

namespace Siappos\Domain\Order\Events;

final class CartVoided
{
    public function __construct(
        public readonly int $actorUserId,
        public readonly int $approverUserId,
        public readonly string $reason,
    ) {
    }
}

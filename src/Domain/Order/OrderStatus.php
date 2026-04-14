<?php
declare(strict_types=1);

namespace Siappos\Domain\Order;

enum OrderStatus: string
{
    case Draft = 'draft';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::CheckedOut, self::Cancelled], true),
            self::CheckedOut, self::Cancelled => false,
        };
    }
}

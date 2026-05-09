<?php
declare(strict_types=1);

namespace Siappos\Domain\Transaction;

enum TransactionStatus: string
{
    case Draft = 'draft';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';
    case Received = 'received';
    case Pending = 'pending';
    case Ordered = 'ordered';
    case Final = 'final';
    case Quotation = 'quotation';
    case Proforma = 'proforma';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::CheckedOut, self::Cancelled, self::Final, self::Quotation, self::Proforma], true),
            self::Quotation, self::Proforma => in_array($next, [self::Draft, self::Cancelled, self::Final], true),
            self::Pending, self::Ordered => in_array($next, [self::Received, self::Cancelled], true),
            self::CheckedOut, self::Cancelled, self::Final, self::Received => false,
        };
    }
}

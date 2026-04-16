<?php
declare(strict_types=1);

namespace Siappos\Domain\Auth;

enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Cashier = 'cashier';

    public function canManageProducts(): bool
    {
        return in_array($this, [self::Admin, self::Manager], true);
    }

    public function canDeleteProducts(): bool
    {
        return $this === self::Admin;
    }

    public function canUsePos(): bool
    {
        return true;
    }

    public function canViewDashboard(): bool
    {
        return in_array($this, [self::Admin, self::Manager], true);
    }
}

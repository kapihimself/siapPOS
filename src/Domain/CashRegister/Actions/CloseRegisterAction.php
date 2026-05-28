<?php
declare(strict_types=1);

namespace Siappos\Domain\CashRegister\Actions;

use RuntimeException;
use Siappos\Domain\CashRegister\CashRegisterRepository;

final class CloseRegisterAction
{
    public function __construct(private readonly CashRegisterRepository $repository)
    {
    }

    public function execute(int $businessId, int $userId, int $closingAmountCents): int
    {
        $activeRegister = $this->repository->getActiveRegister($businessId, $userId);

        if ($activeRegister === null) {
            throw new RuntimeException('Tidak ada shift kasir yang sedang aktif untuk ditutup.');
        }

        if ($closingAmountCents < 0) {
            throw new RuntimeException('Saldo penutupan tidak boleh negatif.');
        }

        $this->repository->closeRegister((int) $activeRegister['id'], $closingAmountCents);
        return (int) $activeRegister['id'];
    }
}

<?php
declare(strict_types=1);

namespace Siappos\Domain\CashRegister\Actions;

use RuntimeException;
use Siappos\Domain\CashRegister\CashRegisterRepository;

final class OpenRegisterAction
{
    public function __construct(private readonly CashRegisterRepository $repository)
    {
    }

    public function execute(int $businessId, int $userId, int $openingAmountCents): int
    {
        $activeRegister = $this->repository->getActiveRegister($businessId, $userId);

        if ($activeRegister !== null) {
            throw new RuntimeException('Shift kasir sudah terbuka. Tutup shift sebelumnya untuk membuka yang baru.');
        }

        if ($openingAmountCents < 0) {
            throw new RuntimeException('Saldo awal tidak boleh negatif.');
        }

        return $this->repository->openRegister($businessId, $userId, $openingAmountCents);
    }
}

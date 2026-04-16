<?php
declare(strict_types=1);

namespace Siappos\Domain\Order\DTO;

use InvalidArgumentException;
use Siappos\Shared\Money;

final class CheckoutData
{
    /** @param list<CartItemData> $items */
    public function __construct(
        public readonly array $items,
        public readonly string $discountType,
        public readonly float $discountValue,
        public readonly float $taxRate,
        public readonly string $paymentMethod,
        public readonly int $cashReceivedCents,
        public readonly int $actorUserId,
    ) {
        if ($this->items === []) {
            throw new InvalidArgumentException('Keranjang masih kosong.');
        }

        foreach ($this->items as $item) {
            if (!$item instanceof CartItemData) {
                throw new InvalidArgumentException('Data cart tidak valid.');
            }
        }

        if (!in_array($this->discountType, ['none', 'percent', 'fixed'], true)) {
            throw new InvalidArgumentException('Tipe diskon tidak valid.');
        }

        if ($this->discountValue < 0) {
            throw new InvalidArgumentException('Nilai diskon tidak valid.');
        }

        if ($this->taxRate < 0) {
            throw new InvalidArgumentException('Tarif pajak tidak valid.');
        }

        if (!in_array($this->paymentMethod, ['cash', 'qris'], true)) {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }

        if ($this->actorUserId <= 0) {
            throw new InvalidArgumentException('Aktor transaksi tidak valid.');
        }
    }

    /** @param list<CartItemData> $items */
    public static function fromRequest(array $payload, array $items, int $actorUserId, float $taxRate): self
    {
        $discountType = (string) ($payload['discount_type'] ?? 'none');
        $discountRaw = (string) ($payload['discount_value'] ?? '0');
        $paymentMethod = (string) ($payload['payment_method'] ?? 'cash');
        $cashRaw = (string) ($payload['cash_received'] ?? '0');

        $discountValue = self::normalizeDecimal($discountRaw);
        $cashReceivedCents = Money::toCents($cashRaw);

        return new self(
            items: $items,
            discountType: $discountType,
            discountValue: $discountValue,
            taxRate: $taxRate,
            paymentMethod: $paymentMethod,
            cashReceivedCents: $cashReceivedCents,
            actorUserId: $actorUserId,
        );
    }

    private static function normalizeDecimal(string $value): float
    {
        $clean = str_replace(' ', '', trim($value));

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (str_contains($clean, ',')) {
            $clean = str_replace(',', '.', $clean);
        }

        return (float) $clean;
    }
}

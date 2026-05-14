<?php
declare(strict_types=1);

namespace Siappos\Domain\Transaction\DTO;

use InvalidArgumentException;
use Siappos\Shared\Money;

final class CheckoutData
{
    /** @param list<CartItemData> $items */
    public function __construct(
        public readonly int $businessId,
        public readonly array $items,
        public readonly string $discountType,
        public readonly float $discountValue,
        public readonly float $taxRate,
        public readonly string $paymentMethod,
        public readonly int $cashReceivedCents,
        public readonly int $actorUserId,
        public readonly ?int $contactId = null,
        public readonly string $type = 'sell',
        public readonly ?int $cashRegisterId = null,
        public readonly string $status = 'checked_out',
        public readonly ?int $commissionAgentId = null,
        public readonly ?int $resTableId = null,
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

        if (!in_array($this->paymentMethod, ['cash', 'qris', 'bank_transfer', 'custom'], true)) {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }

        if ($this->actorUserId <= 0) {
            throw new InvalidArgumentException('Aktor transaksi tidak valid.');
        }
    }

    /** @param list<CartItemData> $items */
    public static function fromRequest(array $payload, array $items, int $actorUserId, float $taxRate, int $businessId, ?int $cashRegisterId = null): self
    {
        $discountType = (string) ($payload['discount_type'] ?? 'none');
        $discountRaw = (string) ($payload['discount_value'] ?? '0');
        $paymentMethod = (string) ($payload['payment_method'] ?? 'cash');
        $cashRaw = (string) ($payload['cash_received'] ?? '0');
        $contactId = isset($payload['contact_id']) && is_numeric($payload['contact_id']) ? (int) $payload['contact_id'] : null;
        $type = (string) ($payload['type'] ?? 'sell');
        $status = (string) ($payload['status'] ?? 'checked_out');
        $commissionAgentId = isset($payload['commission_agent_id']) && is_numeric($payload['commission_agent_id']) ? (int) $payload['commission_agent_id'] : null;
        $resTableId = isset($payload['res_table_id']) && is_numeric($payload['res_table_id']) ? (int) $payload['res_table_id'] : null;

        $discountValue = self::normalizeDecimal($discountRaw);
        $cashReceivedCents = Money::toCents($cashRaw);

        return new self(
            businessId: $businessId,
            items: $items,
            discountType: $discountType,
            discountValue: $discountValue,
            taxRate: $taxRate,
            paymentMethod: $paymentMethod,
            cashReceivedCents: $cashReceivedCents,
            actorUserId: $actorUserId,
            contactId: $contactId,
            type: $type,
            cashRegisterId: $cashRegisterId,
            status: $status,
            commissionAgentId: $commissionAgentId,
            resTableId: $resTableId,
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

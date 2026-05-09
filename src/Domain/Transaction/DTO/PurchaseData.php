<?php
declare(strict_types=1);

namespace Siappos\Domain\Transaction\DTO;

use InvalidArgumentException;
use Siappos\Shared\Money;

final class PurchaseData
{
    /** @param list<PurchaseLineData> $lines */
    public function __construct(
        public readonly int $businessId,
        public readonly array $lines,
        public readonly string $transactionNumber,
        public readonly string $status,
        public readonly int $subtotalCents,
        public readonly int $totalCents,
        public readonly string $paymentMethod,
        public readonly int $actorUserId,
        public readonly ?int $contactId = null,
        public readonly string $discountType = 'none',
    ) {
        if ($this->lines === []) {
            throw new InvalidArgumentException('Daftar barang tidak boleh kosong.');
        }

        foreach ($this->lines as $line) {
            if (!$line instanceof PurchaseLineData) {
                throw new InvalidArgumentException('Baris pembelian tidak valid.');
            }
        }

        if ($this->transactionNumber === '') {
            throw new InvalidArgumentException('Nomor referensi pembelian (Invoice/PO) harus diisi.');
        }

        if (!in_array($this->status, ['received', 'pending', 'ordered', 'final'], true)) {
            throw new InvalidArgumentException('Status pembelian tidak valid.');
        }

        if (!in_array($this->paymentMethod, ['cash', 'bank_transfer', 'custom'], true)) {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }

        if ($this->actorUserId <= 0) {
            throw new InvalidArgumentException('Aktor transaksi tidak valid.');
        }
    }

    /** @param list<PurchaseLineData> $lines */
    public static function fromRequest(array $payload, array $lines, int $actorUserId, int $businessId): self
    {
        $transactionNumber = trim((string) ($payload['transaction_number'] ?? ''));
        $status = (string) ($payload['status'] ?? 'received');
        $paymentMethod = (string) ($payload['payment_method'] ?? 'cash');
        $contactId = isset($payload['contact_id']) && is_numeric($payload['contact_id']) ? (int) $payload['contact_id'] : null;

        $subtotalCents = 0;
        foreach ($lines as $line) {
            $subtotalCents += $line->lineTotalCents;
        }

        return new self(
            businessId: $businessId,
            lines: $lines,
            transactionNumber: $transactionNumber,
            status: $status,
            subtotalCents: $subtotalCents,
            totalCents: $subtotalCents, // Simplified: Purchase total = sum of line totals (no complex tax/discount for MVP)
            paymentMethod: $paymentMethod,
            actorUserId: $actorUserId,
            contactId: $contactId,
        );
    }
}

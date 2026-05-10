<?php
declare(strict_types=1);

namespace Siappos\Domain\Accounting\Actions;

use PDO;
use RuntimeException;

final class RecordTransactionAction
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function execute(
        int $businessId,
        int $accountId,
        string $type,
        int $amountCents,
        string $description,
        ?int $transactionId = null
    ): void {
        if ($amountCents <= 0) {
            throw new RuntimeException('Jumlah transaksi akuntansi harus lebih besar dari 0.');
        }

        if (!in_array($type, ['debit', 'credit'], true)) {
            throw new RuntimeException('Tipe transaksi harus debit atau credit.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO account_transactions (business_id, account_id, transaction_id, type, amount_cents, description, created_at)
             VALUES (:business_id, :account_id, :transaction_id, :type, :amount_cents, :description, CURRENT_TIMESTAMP)'
        );

        $stmt->execute([
            ':business_id' => $businessId,
            ':account_id' => $accountId,
            ':transaction_id' => $transactionId,
            ':type' => $type,
            ':amount_cents' => $amountCents,
            ':description' => $description,
        ]);
    }
}

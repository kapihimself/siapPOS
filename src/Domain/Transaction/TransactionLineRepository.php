<?php
declare(strict_types=1);

namespace Siappos\Domain\Transaction;

use PDO;

final class TransactionLineRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function getLinesForTransaction(int $transactionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT tl.*, v.sku, v.name as variation_name, p.name as product_name
             FROM transaction_lines tl
             JOIN variations v ON tl.variation_id = v.id
             JOIN products p ON v.product_id = p.id
             WHERE tl.transaction_id = :transaction_id
             ORDER BY tl.id ASC'
        );
        $stmt->execute([':transaction_id' => $transactionId]);

        return $stmt->fetchAll();
    }
}

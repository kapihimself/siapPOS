<?php
declare(strict_types=1);

namespace Siappos\Domain\Transaction;

use PDO;

final class TransactionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function allPurchases(int $businessId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.*, c.name as contact_name
             FROM transactions t
             LEFT JOIN contacts c ON t.contact_id = c.id
             WHERE t.business_id = :business_id AND t.type = "purchase"
             ORDER BY t.id DESC'
        );
        $stmt->execute([':business_id' => $businessId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function allStockAdjustments(int $businessId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM transactions
             WHERE business_id = :business_id AND type = "stock_adjustment"
             ORDER BY id DESC'
        );
        $stmt->execute([':business_id' => $businessId]);

        return $stmt->fetchAll();
    }

    public function countPurchases(int $businessId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM transactions WHERE business_id = :business_id AND type = "purchase"');
        $stmt->execute([':business_id' => $businessId]);
        return (int) $stmt->fetchColumn();
    }
}

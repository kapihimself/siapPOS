<?php
declare(strict_types=1);

namespace Siappos\Domain\Accounting;

use PDO;
use RuntimeException;

final class AccountRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(int $businessId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM accounts WHERE business_id = :business_id ORDER BY type ASC, name ASC');
        $stmt->execute([':business_id' => $businessId]);

        return $stmt->fetchAll();
    }

    public function create(int $businessId, string $name, string $accountNumber, string $type): void
    {
        $stmtCheck = $this->pdo->prepare('SELECT id FROM accounts WHERE business_id = :business_id AND account_number = :account_number LIMIT 1');
        $stmtCheck->execute([
            ':business_id' => $businessId,
            ':account_number' => $accountNumber,
        ]);

        if ($stmtCheck->fetch() !== false) {
            throw new RuntimeException("Nomor Akun '{$accountNumber}' sudah digunakan.");
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO accounts (business_id, name, account_number, type, created_at, updated_at)
             VALUES (:business_id, :name, :account_number, :type, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':business_id' => $businessId,
            ':name' => $name,
            ':account_number' => $accountNumber,
            ':type' => $type,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function getLedger(int $accountId, int $businessId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT at.*, t.transaction_number
            FROM account_transactions at
            LEFT JOIN transactions t ON at.transaction_id = t.id
            WHERE at.account_id = :account_id AND at.business_id = :business_id
            ORDER BY at.created_at ASC
        ');
        $stmt->execute([
            ':account_id' => $accountId,
            ':business_id' => $businessId,
        ]);

        return $stmt->fetchAll();
    }
}

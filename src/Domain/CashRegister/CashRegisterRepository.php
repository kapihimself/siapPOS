<?php
declare(strict_types=1);

namespace Siappos\Domain\CashRegister;

use PDO;

final class CashRegisterRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function getActiveRegister(int $businessId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM cash_registers WHERE business_id = :business_id AND user_id = :user_id AND status = \'open\' LIMIT 1'
        );
        $stmt->execute([
            ':business_id' => $businessId,
            ':user_id' => $userId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function openRegister(int $businessId, int $userId, int $openingAmountCents): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cash_registers (business_id, user_id, status, opening_amount_cents, opened_at)
             VALUES (:business_id, :user_id, \'open\', :opening_amount, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':business_id' => $businessId,
            ':user_id' => $userId,
            ':opening_amount' => $openingAmountCents,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function closeRegister(int $registerId, int $closingAmountCents): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cash_registers SET status = \'closed\', closing_amount_cents = :closing_amount, closed_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $registerId,
            ':closing_amount' => $closingAmountCents,
        ]);
    }

    /** @return array<string, mixed> */
    public function getZReportData(int $businessId, int $registerId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cash_registers WHERE id = :id AND business_id = :business_id');
        $stmt->execute([':id' => $registerId, ':business_id' => $businessId]);
        $register = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$register) {
            throw new \RuntimeException('Register not found');
        }

        $stmtSales = $this->pdo->prepare("
            SELECT COALESCE(SUM(total_cents), 0) as total_sales
            FROM transactions
            WHERE business_id = :business_id
              AND type = 'sell'
              AND status IN ('checked_out', 'final')
              AND created_at >= :opened_at
              AND (created_at <= :closed_at OR :closed_at_null IS NULL)
        ");
        $stmtSales->execute([
            ':business_id' => $businessId,
            ':opened_at' => $register['opened_at'],
            ':closed_at' => $register['closed_at'],
            ':closed_at_null' => $register['closed_at'],
        ]);
        $salesData = $stmtSales->fetch(PDO::FETCH_ASSOC);

        $totalSales = (int) ($salesData['total_sales'] ?? 0);
        $openingAmount = (int) $register['opening_amount_cents'];
        $closingAmount = $register['status'] === 'closed' ? (int) $register['closing_amount_cents'] : null;

        return [
            'register' => $register,
            'total_sales_cents' => $totalSales,
            'expected_closing_amount_cents' => $openingAmount + $totalSales,
            'actual_closing_amount_cents' => $closingAmount,
        ];
    }
}

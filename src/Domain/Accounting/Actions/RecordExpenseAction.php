<?php
declare(strict_types=1);

namespace Siappos\Domain\Accounting\Actions;

use PDO;
use Exception;
use RuntimeException;

final class RecordExpenseAction
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function execute(
        int $businessId,
        int $accountId,
        int $amountCents,
        string $paymentMethod,
        string $description,
        int $actorUserId
    ): int {
        $this->pdo->beginTransaction();

        try {
            // 1. Verifikasi akun pengeluaran
            $stmtAccount = $this->pdo->prepare('SELECT type FROM accounts WHERE id = :id AND business_id = :business_id LIMIT 1');
            $stmtAccount->execute([':id' => $accountId, ':business_id' => $businessId]);
            $account = $stmtAccount->fetch();

            if (!$account) {
                throw new RuntimeException('Akun tidak ditemukan.');
            }

            // 2. Buat transaksi Expense
            $transactionNumber = 'EXP-' . date('YmdHis') . '-' . random_int(100, 999);

            $stmt = $this->pdo->prepare(
                'INSERT INTO transactions (
                    business_id, transaction_number, type, status,
                    subtotal_cents, discount_type, discount_value, discount_cents,
                    tax_rate, tax_cents, total_cents, payment_method,
                    change_cents, created_by
                ) VALUES (
                    :business_id, :transaction_number, :type, :status,
                    :subtotal_cents, :discount_type, 0, 0,
                    0, 0, :total_cents, :payment_method,
                    0, :created_by
                )'
            );

            $stmt->execute([
                ':business_id' => $businessId,
                ':transaction_number' => $transactionNumber,
                ':type' => 'expense',
                ':status' => 'final',
                ':subtotal_cents' => $amountCents,
                ':discount_type' => 'none',
                ':total_cents' => $amountCents,
                ':payment_method' => $paymentMethod,
                ':created_by' => $actorUserId,
            ]);

            $transactionId = (int) $this->pdo->lastInsertId();

            // 3. Catat di jurnal buku besar (Debit ke beban, diasumsikan pembayaran langsung tunai/bank)
            // Ini penyederhanaan; aslinya butuh double entry (Credit ke Kas/Bank).
            // Kita catat Debit di akun Expense yang dipilih.
            $stmtLedger = $this->pdo->prepare(
                'INSERT INTO account_transactions (
                    business_id, account_id, transaction_id, type, amount_cents, description, created_at
                ) VALUES (
                    :business_id, :account_id, :transaction_id, :type, :amount_cents, :description, CURRENT_TIMESTAMP
                )'
            );

            $stmtLedger->execute([
                ':business_id' => $businessId,
                ':account_id' => $accountId,
                ':transaction_id' => $transactionId,
                ':type' => 'debit',
                ':amount_cents' => $amountCents,
                ':description' => "Pengeluaran: " . $description,
            ]);

            $this->pdo->commit();

            return $transactionId;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

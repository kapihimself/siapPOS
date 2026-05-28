<?php
declare(strict_types=1);

namespace Siappos\Domain\Accounting\Actions;

use PDO;
use Exception;
use RuntimeException;

final class TransferFundAction
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function execute(
        int $businessId,
        int $fromAccountId,
        int $toAccountId,
        int $amountCents,
        string $description
    ): void {
        if ($fromAccountId === $toAccountId) {
            throw new RuntimeException('Akun asal dan tujuan tidak boleh sama.');
        }

        if ($amountCents <= 0) {
            throw new RuntimeException('Jumlah mutasi harus lebih dari 0.');
        }

        $this->pdo->beginTransaction();

        try {
            // 1. Verifikasi kedua akun ada
            $stmtAccount = $this->pdo->prepare('SELECT id, name FROM accounts WHERE id IN (:from, :to) AND business_id = :business_id');
            $stmtAccount->execute([
                ':from' => $fromAccountId,
                ':to' => $toAccountId,
                ':business_id' => $businessId
            ]);

            $accounts = $stmtAccount->fetchAll();
            if (count($accounts) !== 2) {
                throw new RuntimeException('Salah satu atau kedua akun tidak ditemukan.');
            }

            // 2. Insert Credit (Pengurangan di Akun Asal)
            $stmtLedger = $this->pdo->prepare(
                'INSERT INTO account_transactions (
                    business_id, account_id, type, amount_cents, description, created_at
                ) VALUES (
                    :business_id, :account_id, :type, :amount_cents, :description, CURRENT_TIMESTAMP
                )'
            );

            $stmtLedger->execute([
                ':business_id' => $businessId,
                ':account_id' => $fromAccountId,
                ':type' => 'credit',
                ':amount_cents' => $amountCents,
                ':description' => "Mutasi Keluar: " . $description,
            ]);

            // 3. Insert Debit (Penambahan di Akun Tujuan)
            $stmtLedger->execute([
                ':business_id' => $businessId,
                ':account_id' => $toAccountId,
                ':type' => 'debit',
                ':amount_cents' => $amountCents,
                ':description' => "Mutasi Masuk: " . $description,
            ]);

            $this->pdo->commit();

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
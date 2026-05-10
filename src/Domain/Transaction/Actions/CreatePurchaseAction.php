<?php
declare(strict_types=1);

namespace Siappos\Domain\Transaction\Actions;

use PDO;
use Exception;
use RuntimeException;
use Siappos\Domain\Transaction\DTO\PurchaseData;

final class CreatePurchaseAction
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function execute(PurchaseData $data): array
    {
        $this->pdo->beginTransaction();

        try {
            // 1. Validasi nomor referensi unik
            $stmtCheck = $this->pdo->prepare(
                'SELECT id FROM transactions WHERE business_id = :business_id AND transaction_number = :transaction_number LIMIT 1'
            );
            $stmtCheck->execute([
                ':business_id' => $data->businessId,
                ':transaction_number' => $data->transactionNumber,
            ]);

            if ($stmtCheck->fetch() !== false) {
                throw new RuntimeException("Nomor Referensi/Invoice '{$data->transactionNumber}' sudah terdaftar.");
            }

            // 2. Buat transaksi utama
            $stmt = $this->pdo->prepare(
                'INSERT INTO transactions (
                    business_id, transaction_number, type, status, contact_id,
                    subtotal_cents, discount_type, discount_value, discount_cents,
                    tax_rate, tax_cents, total_cents, payment_method,
                    change_cents, created_by
                ) VALUES (
                    :business_id, :transaction_number, :type, :status, :contact_id,
                    :subtotal_cents, :discount_type, 0, 0,
                    0, 0, :total_cents, :payment_method,
                    0, :created_by
                )'
            );

            $stmt->execute([
                ':business_id' => $data->businessId,
                ':transaction_number' => $data->transactionNumber,
                ':type' => 'purchase',
                ':status' => $data->status,
                ':contact_id' => $data->contactId,
                ':subtotal_cents' => $data->subtotalCents,
                ':discount_type' => $data->discountType,
                ':total_cents' => $data->totalCents,
                ':payment_method' => $data->paymentMethod,
                ':created_by' => $data->actorUserId,
            ]);

            $transactionId = (int) $this->pdo->lastInsertId();

            // 3. Masukkan line items dan tambah stok
            $stmtLine = $this->pdo->prepare(
                'INSERT INTO purchase_lines (
                    transaction_id, product_id, variation_id, product_name, qty, unit_price_cents, line_total_cents
                ) VALUES (
                    :transaction_id, :product_id, :variation_id, :product_name, :qty, :unit_price_cents, :line_total_cents
                )'
            );

            $stmtUpdateStock = $this->pdo->prepare(
                'UPDATE products SET stock_qty = stock_qty + :qty, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND business_id = :business_id'
            );

            $stmtUpdateVariationStock = $this->pdo->prepare(
                'UPDATE product_variations SET stock_qty = stock_qty + :qty, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );

            foreach ($data->lines as $line) {
                $stmtLine->execute([
                    ':transaction_id' => $transactionId,
                    ':product_id' => $line->productId,
                    ':variation_id' => $line->variationId,
                    ':product_name' => $line->productName,
                    ':qty' => $line->qty,
                    ':unit_price_cents' => $line->unitPriceCents,
                    ':line_total_cents' => $line->lineTotalCents,
                ]);

                if ($data->status === 'received' || $data->status === 'final') {
                    if ($line->variationId !== null) {
                        $stmtUpdateVariationStock->execute([
                            ':qty' => $line->qty,
                            ':id' => $line->variationId,
                        ]);
                    } else {
                        $stmtUpdateStock->execute([
                            ':qty' => $line->qty,
                            ':id' => $line->productId,
                            ':business_id' => $data->businessId,
                        ]);
                    }
                }
            }

            $this->pdo->commit();

            return [
                'id' => $transactionId,
                'transaction_number' => $data->transactionNumber,
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

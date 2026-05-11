<?php
declare(strict_types=1);

namespace Siappos\Domain\Transaction\Actions;

use PDO;
use Exception;
use RuntimeException;
use Siappos\Domain\Product\ProductRepository;

final class AdjustStockAction
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ProductRepository $products
    ) {
    }

    /**
     * @param array<int, array{product_id: int, variation_id: ?int, qty: float, type: string}> $lines
     * type can be 'add' or 'subtract'
     */
    public function execute(
        int $businessId,
        string $adjustmentType, // 'normal' (hilang/rusak) or 'transfer' (kalau ada multi-location)
        array $lines,
        int $actorUserId
    ): int {
        $this->pdo->beginTransaction();

        try {
            // 1. Buat transaksi Stock Adjustment
            $transactionNumber = 'ADJ-' . date('YmdHis') . '-' . random_int(100, 999);

            $stmt = $this->pdo->prepare(
                'INSERT INTO transactions (
                    business_id, transaction_number, type, status,
                    subtotal_cents, discount_type, discount_value, discount_cents,
                    tax_rate, tax_cents, total_cents, payment_method,
                    change_cents, created_by
                ) VALUES (
                    :business_id, :transaction_number, :type, :status,
                    0, :discount_type, 0, 0,
                    0, 0, 0, :payment_method,
                    0, :created_by
                )'
            );

            $stmt->execute([
                ':business_id' => $businessId,
                ':transaction_number' => $transactionNumber,
                ':type' => 'stock_adjustment',
                ':status' => 'final', // For now, immediate effect
                ':discount_type' => 'none',
                ':payment_method' => 'cash', // N/A for stock adj
                ':created_by' => $actorUserId,
            ]);

            $transactionId = (int) $this->pdo->lastInsertId();

            // 2. Loop & adjust lines
            $stmtLine = $this->pdo->prepare(
                'INSERT INTO transaction_sell_lines (
                    transaction_id, product_id, variation_id, product_name, qty, unit_price_cents, line_total_cents
                ) VALUES (
                    :transaction_id, :product_id, :variation_id, :product_name, :qty, 0, 0
                )'
            );

            foreach ($lines as $line) {
                $product = $this->products->find($line['product_id'], $businessId, $line['variation_id']);

                if (!is_array($product)) {
                    throw new RuntimeException("Produk ID {$line['product_id']} tidak ditemukan.");
                }

                $qtyDelta = $line['type'] === 'subtract' ? -abs($line['qty']) : abs($line['qty']);

                // Adjust Master Stock
                $this->products->adjustStock($line['product_id'], $qtyDelta, $businessId, $line['variation_id']);

                // Insert into transaction lines to keep track of WHAT was adjusted
                $stmtLine->execute([
                    ':transaction_id' => $transactionId,
                    ':product_id' => $line['product_id'],
                    ':variation_id' => $line['variation_id'],
                    ':product_name' => $product['name'] . ($line['type'] === 'subtract' ? ' (Pengurangan)' : ' (Penambahan)'),
                    ':qty' => abs($line['qty']),
                ]);
                $sellLineId = (int) $this->pdo->lastInsertId();

                // Maintain FIFO Integrity for Subtractions
                if ($line['type'] === 'subtract') {
                    $stmtPurchaseLines = $this->pdo->prepare(
                        'SELECT id, qty, qty_sold FROM purchase_lines
                         WHERE product_id = :product_id AND (variation_id = :variation_id OR (variation_id IS NULL AND :variation_id IS NULL)) AND qty > qty_sold
                         ORDER BY created_at ASC'
                    );
                    $stmtUpdatePurchaseLine = $this->pdo->prepare(
                        'UPDATE purchase_lines SET qty_sold = qty_sold + :qty_sold WHERE id = :id'
                    );
                    $stmtMapping = $this->pdo->prepare(
                        'INSERT INTO transaction_sell_lines_purchase_lines (
                            sell_line_id, purchase_line_id, qty
                        ) VALUES (
                            :sell_line_id, :purchase_line_id, :qty
                        )'
                    );

                    $stmtPurchaseLines->execute([
                        ':product_id' => $line['product_id'],
                        ':variation_id' => $line['variation_id']
                    ]);
                    $availableLots = $stmtPurchaseLines->fetchAll();

                    $qtyToDeduct = (float) abs($line['qty']);

                    foreach ($availableLots as $lot) {
                        if ($qtyToDeduct <= 0) break;

                        $availableQtyInLot = (float) $lot['qty'] - (float) $lot['qty_sold'];
                        $deductedFromLot = min($qtyToDeduct, $availableQtyInLot);

                        $stmtUpdatePurchaseLine->execute([
                            ':qty_sold' => $deductedFromLot,
                            ':id' => $lot['id']
                        ]);

                        $stmtMapping->execute([
                            ':sell_line_id' => $sellLineId,
                            ':purchase_line_id' => $lot['id'],
                            ':qty' => $deductedFromLot
                        ]);

                        $qtyToDeduct -= $deductedFromLot;
                    }
                }
            }

            $this->pdo->commit();

            return $transactionId;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

<?php
declare(strict_types=1);

namespace Siappos\Domain\Transaction\Actions;

use PDO;
use Exception;
use RuntimeException;
use Siappos\Domain\Transaction\DTO\CheckoutData;
use Siappos\Domain\Product\ProductRepository;
use Siappos\Domain\Transaction\Events\TransactionCheckedOut;
use Siappos\Shared\EventBus;

final class ProcessSaleAction
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ProductRepository $products,
        private readonly EventBus $eventBus,
    ) {
    }

    public function execute(CheckoutData $data): array
    {
        $this->pdo->beginTransaction();

        try {
            // 1. Hitung total dan verifikasi stok
            $subtotalCents = 0;
            $lines = [];

            foreach ($data->items as $item) {
                $product = $this->products->find($item->productId, $data->businessId, $item->variationId);

                if (!is_array($product)) {
                    throw new RuntimeException("Produk ID {$item->productId} tidak ditemukan.");
                }

                if ((float) $product['stock_qty'] < $item->qty) {
                    throw new RuntimeException("Stok tidak mencukupi untuk {$product['name']}.");
                }

                $priceCents = (int) $product['price_cents'];
                $lineTotal = (int) ($priceCents * $item->qty);
                $subtotalCents += $lineTotal;

                $lines[] = [
                    'product_id' => $item->productId,
                    'variation_id' => $item->variationId,
                    'product_name' => (string) $product['name'],
                    'qty' => $item->qty,
                    'unit_price_cents' => $priceCents,
                    'line_total_cents' => $lineTotal,
                ];
            }

            // 2. Terapkan diskon & pajak
            $discountCents = 0;
            if ($data->discountType === 'percent') {
                $discountCents = (int) round($subtotalCents * ($data->discountValue / 100));
            } elseif ($data->discountType === 'fixed') {
                $discountCents = (int) ($data->discountValue * 100);
            }

            $taxableCents = max(0, $subtotalCents - $discountCents);
            $taxCents = (int) round($taxableCents * ($data->taxRate / 100));
            $totalCents = $taxableCents + $taxCents;
            $changeCents = max(0, $data->cashReceivedCents - $totalCents);

            // 3. Buat transaksi utama
            $transactionNumber = 'TRX-' . date('YmdHis') . '-' . random_int(100, 999);

            $stmt = $this->pdo->prepare(
                'INSERT INTO transactions (
                    business_id, cash_register_id, transaction_number, type, status, contact_id,
                    subtotal_cents, discount_type, discount_value, discount_cents,
                    tax_rate, tax_cents, total_cents, payment_method,
                    cash_received_cents, change_cents, created_by
                ) VALUES (
                    :business_id, :cash_register_id, :transaction_number, :type, :status, :contact_id,
                    :subtotal_cents, :discount_type, :discount_value, :discount_cents,
                    :tax_rate, :tax_cents, :total_cents, :payment_method,
                    :cash_received_cents, :change_cents, :created_by
                )'
            );

            $stmt->execute([
                ':business_id' => $data->businessId,
                ':cash_register_id' => $data->cashRegisterId,
                ':transaction_number' => $transactionNumber,
                ':type' => $data->type,
                ':status' => 'checked_out',
                ':contact_id' => $data->contactId,
                ':subtotal_cents' => $subtotalCents,
                ':discount_type' => $data->discountType,
                ':discount_value' => $data->discountValue,
                ':discount_cents' => $discountCents,
                ':tax_rate' => $data->taxRate,
                ':tax_cents' => $taxCents,
                ':total_cents' => $totalCents,
                ':payment_method' => $data->paymentMethod,
                ':cash_received_cents' => $data->cashReceivedCents,
                ':change_cents' => $changeCents,
                ':created_by' => $data->actorUserId,
            ]);

            $transactionId = (int) $this->pdo->lastInsertId();

            // 4. Masukkan line items dan implementasi FIFO Stock Deduction
            $stmtLine = $this->pdo->prepare(
                'INSERT INTO transaction_sell_lines (
                    transaction_id, product_id, variation_id, product_name, qty, unit_price_cents, line_total_cents
                ) VALUES (
                    :transaction_id, :product_id, :variation_id, :product_name, :qty, :unit_price_cents, :line_total_cents
                )'
            );

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

            foreach ($lines as $line) {
                // Insert Sell Line
                $stmtLine->execute([
                    ':transaction_id' => $transactionId,
                    ':product_id' => $line['product_id'],
                    ':variation_id' => $line['variation_id'],
                    ':product_name' => $line['product_name'],
                    ':qty' => $line['qty'],
                    ':unit_price_cents' => $line['unit_price_cents'],
                    ':line_total_cents' => $line['line_total_cents'],
                ]);
                $sellLineId = (int) $this->pdo->lastInsertId();

                // Deduct master stock
                $this->products->decrementStock($line['product_id'], $line['qty'], $data->businessId, $line['variation_id']);

                // FIFO Logic: Fetch available purchase lines
                $stmtPurchaseLines->execute([
                    ':product_id' => $line['product_id'],
                    ':variation_id' => $line['variation_id']
                ]);
                $availableLots = $stmtPurchaseLines->fetchAll();

                $qtyToDeduct = (float) $line['qty'];

                foreach ($availableLots as $lot) {
                    if ($qtyToDeduct <= 0) break;

                    $availableQtyInLot = (float) $lot['qty'] - (float) $lot['qty_sold'];
                    $deductedFromLot = min($qtyToDeduct, $availableQtyInLot);

                    // Update purchase line qty_sold
                    $stmtUpdatePurchaseLine->execute([
                        ':qty_sold' => $deductedFromLot,
                        ':id' => $lot['id']
                    ]);

                    // Create mapping
                    $stmtMapping->execute([
                        ':sell_line_id' => $sellLineId,
                        ':purchase_line_id' => $lot['id'],
                        ':qty' => $deductedFromLot
                    ]);

                    $qtyToDeduct -= $deductedFromLot;
                }
            }

            $this->pdo->commit();

            $this->eventBus->dispatch(new TransactionCheckedOut(
                businessId: $data->businessId,
                transactionId: $transactionId,
                transactionNumber: $transactionNumber,
                totalCents: $totalCents,
                paymentMethod: $data->paymentMethod,
                userId: $data->actorUserId,
            ));

            return [
                'id' => $transactionId,
                'transaction_number' => $transactionNumber,
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

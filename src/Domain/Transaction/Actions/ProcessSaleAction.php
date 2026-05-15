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
        private readonly ?\Siappos\Shared\QueueManager $queueManager = null,
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
                $modifiersTotalCents = 0;
                foreach ($item->modifiers as $mod) {
                    $modifiersTotalCents += (int) $mod['price_cents'];
                }

                // Add modifiers total to base price before multiplying by qty
                $effectivePriceCents = $priceCents + $modifiersTotalCents;
                $lineTotal = (int) ($effectivePriceCents * $item->qty);
                $subtotalCents += $lineTotal;

                $lines[] = [
                    'product_id' => $item->productId,
                    'variation_id' => $item->variationId,
                    'product_name' => (string) $product['name'],
                    'qty' => $item->qty,
                    'unit_price_cents' => $effectivePriceCents,
                    'line_total_cents' => $lineTotal,
                    'modifiers' => $item->modifiers
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
            // Simulate UUID v4 for payment token (native PHP 8 doesn't have a built-in generator so we mock a secure enough random string)
            $paymentToken = bin2hex(random_bytes(16));

            $paymentStatus = 'paid';
            if ($data->cashReceivedCents < $totalCents) {
                $paymentStatus = $data->cashReceivedCents > 0 ? 'partial' : 'due';
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO transactions (
                    business_id, cash_register_id, transaction_number, type, status, contact_id,
                    commission_agent_id, res_table_id,
                    subtotal_cents, discount_type, discount_value, discount_cents,
                    tax_rate, tax_cents, total_cents, payment_status, payment_method,
                    cash_received_cents, change_cents, payment_token, created_by
                ) VALUES (
                    :business_id, :cash_register_id, :transaction_number, :type, :status, :contact_id,
                    :commission_agent_id, :res_table_id,
                    :subtotal_cents, :discount_type, :discount_value, :discount_cents,
                    :tax_rate, :tax_cents, :total_cents, :payment_status, :payment_method,
                    :cash_received_cents, :change_cents, :payment_token, :created_by
                )'
            );

            $stmt->execute([
                ':business_id' => $data->businessId,
                ':cash_register_id' => $data->cashRegisterId,
                ':transaction_number' => $transactionNumber,
                ':type' => $data->type,
                ':status' => $data->status,
                ':contact_id' => $data->contactId,
                ':commission_agent_id' => $data->commissionAgentId,
                ':res_table_id' => $data->resTableId,
                ':subtotal_cents' => $subtotalCents,
                ':discount_type' => $data->discountType,
                ':discount_value' => $data->discountValue,
                ':discount_cents' => $discountCents,
                ':tax_rate' => $data->taxRate,
                ':tax_cents' => $taxCents,
                ':total_cents' => $totalCents,
                ':payment_status' => $paymentStatus,
                ':payment_method' => $data->paymentMethod,
                ':cash_received_cents' => $data->cashReceivedCents,
                ':change_cents' => $changeCents,
                ':payment_token' => $paymentToken,
                ':created_by' => $data->actorUserId,
            ]);

            $transactionId = (int) $this->pdo->lastInsertId();

            if ($data->cashReceivedCents > 0) {
                $stmtPayment = $this->pdo->prepare(
                    'INSERT INTO transaction_payments (transaction_id, amount_cents, payment_method, created_by)
                     VALUES (:transaction_id, :amount_cents, :payment_method, :created_by)'
                );
                $stmtPayment->execute([
                    ':transaction_id' => $transactionId,
                    ':amount_cents' => min($data->cashReceivedCents, $totalCents),
                    ':payment_method' => $data->paymentMethod,
                    ':created_by' => $data->actorUserId,
                ]);
            }

            // 4. Masukkan line items dan implementasi FIFO Stock Deduction
            $stmtLine = $this->pdo->prepare(
                'INSERT INTO transaction_sell_lines (
                    transaction_id, product_id, variation_id, product_name, qty, unit_price_cents, line_total_cents
                ) VALUES (
                    :transaction_id, :product_id, :variation_id, :product_name, :qty, :unit_price_cents, :line_total_cents
                )'
            );

            $stmtPurchaseLines = $this->pdo->prepare(
                'SELECT pl.id, pl.qty, pl.qty_sold FROM purchase_lines pl
                 JOIN transactions t ON pl.transaction_id = t.id
                 WHERE t.business_id = :business_id AND pl.product_id = :product_id AND (pl.variation_id = :variation_id OR (pl.variation_id IS NULL AND :variation_id IS NULL))
                   AND pl.qty > pl.qty_sold
                   AND t.status IN (\'received\', \'final\')
                 ORDER BY pl.created_at ASC'
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

            $stmtModifier = $this->pdo->prepare(
                'INSERT INTO transaction_sell_line_modifiers (
                    sell_line_id, modifier_id, modifier_name, modifier_price_cents
                ) VALUES (
                    :sell_line_id, :modifier_id, :modifier_name, :modifier_price_cents
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

                foreach ($line['modifiers'] as $mod) {
                    $stmtModifier->execute([
                        ':sell_line_id' => $sellLineId,
                        ':modifier_id' => $mod['id'],
                        ':modifier_name' => $mod['name'],
                        ':modifier_price_cents' => $mod['price_cents'],
                    ]);
                }

                // Hanya kurangi stok jika status checkout final/checked_out (bukan draft/suspended)
                if (in_array($data->status, ['checked_out', 'final'])) {
                    // Deduct master stock
                    $this->products->decrementStock($line['product_id'], $line['qty'], $data->businessId, $line['variation_id']);

                    // FIFO Logic: Fetch available purchase lines
                $stmtPurchaseLines->execute([
                    ':business_id' => $data->businessId,
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
            } // End if status == checked_out/final
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

            if ($this->queueManager !== null) {
                $this->queueManager->push('SendEmailReceipt', [
                    'transactionId' => $transactionId,
                    'transactionNumber' => $transactionNumber,
                    'contactId' => $data->contactId
                ]);
            }

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

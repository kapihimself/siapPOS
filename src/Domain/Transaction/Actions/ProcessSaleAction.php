<?php
declare(strict_types=1);

namespace Siappos\Domain\Transaction\Actions;

use PDO;
use RuntimeException;
use Siappos\Domain\Product\VariationRepository;

final class ProcessSaleAction
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly VariationRepository $variationRepository
    ) {
    }

    /**
     * @param array<string, mixed> $payload (decoded JSON from client)
     */
    public function execute(int $businessId, int $userId, int $outletId, array $payload): array
    {
        $items = $payload['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            throw new RuntimeException("Keranjang belanja kosong.");
        }

        $paymentMethod = (string)($payload['payment_method'] ?? 'cash');
        $cashReceivedCents = (int)($payload['cash_received_cents'] ?? 0);

        $this->pdo->beginTransaction();

        try {
            $totalBeforeTax = 0;
            $totalDiscount = 0;
            $totalTax = 0;

            // Generate Invoice Number
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM transactions WHERE business_id = :b_id AND type = 'sell'");
            $stmt->execute([':b_id' => $businessId]);
            $count = (int)$stmt->fetchColumn() + 1;
            $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);

            // 1. Insert Transaction Record (Draft status initially to get ID)
            $stmtInsertTx = $this->pdo->prepare(
                'INSERT INTO transactions
                (business_id, type, status, invoice_no, payment_status, created_by, created_at, updated_at)
                VALUES (:business_id, "sell", "final", :invoice_no, "paid", :created_by, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $stmtInsertTx->execute([
                ':business_id' => $businessId,
                ':invoice_no' => $invoiceNo,
                ':created_by' => $userId,
            ]);
            $transactionId = (int)$this->pdo->lastInsertId();

            $stmtInsertLine = $this->pdo->prepare(
                'INSERT INTO transaction_lines
                (transaction_id, variation_id, quantity, unit_price_inc_tax_cents)
                VALUES (:transaction_id, :variation_id, :quantity, :price)'
            );

            // Fetch FIFO lots for deduction mapping
            $stmtFindPurchaseLots = $this->pdo->prepare(
                'SELECT tl.id as purchase_line_id, (tl.quantity - COALESCE(SUM(tspl.quantity), 0)) as qty_remaining
                 FROM transaction_lines tl
                 JOIN transactions t ON tl.transaction_id = t.id
                 LEFT JOIN transaction_sell_lines_purchase_lines tspl ON tl.id = tspl.purchase_line_id
                 WHERE t.business_id = :business_id
                   AND t.type = "purchase"
                   AND tl.variation_id = :variation_id
                 GROUP BY tl.id
                 HAVING qty_remaining > 0
                 ORDER BY t.created_at ASC'
            );

            $stmtMapFifo = $this->pdo->prepare(
                'INSERT INTO transaction_sell_lines_purchase_lines (sell_line_id, purchase_line_id, quantity)
                 VALUES (:sell_line_id, :purchase_line_id, :qty)'
            );

            foreach ($items as $item) {
                $variationId = (int)$item['variation_id'];
                $qty = (float)$item['qty'];
                $price = (int)$item['price_cents'];

                $lineTotal = (int)round($qty * $price);
                $totalBeforeTax += $lineTotal;

                // Validate & Deduct Global Stock (throws if insufficient)
                $this->variationRepository->decrementStock($variationId, $outletId, $qty);

                // Insert Sell Line
                $stmtInsertLine->execute([
                    ':transaction_id' => $transactionId,
                    ':variation_id' => $variationId,
                    ':quantity' => $qty,
                    ':price' => $price
                ]);
                $sellLineId = (int)$this->pdo->lastInsertId();

                // Process FIFO Mapping
                $qtyToDeduct = $qty;
                $stmtFindPurchaseLots->execute([':business_id' => $businessId, ':variation_id' => $variationId]);
                $lots = $stmtFindPurchaseLots->fetchAll();

                foreach ($lots as $lot) {
                    if ($qtyToDeduct <= 0) break;

                    $qtyAvailable = (float)$lot['qty_remaining'];
                    $qtyTaken = min($qtyToDeduct, $qtyAvailable);

                    $stmtMapFifo->execute([
                        ':sell_line_id' => $sellLineId,
                        ':purchase_line_id' => (int)$lot['purchase_line_id'],
                        ':qty' => $qtyTaken
                    ]);

                    $qtyToDeduct -= $qtyTaken;
                }

                // If $qtyToDeduct > 0 here, it means we sold items without corresponding purchase lots (e.g. Opening Stock or negative stock allowed).
                // We skip strict FIFO mapping for the remainder to avoid breaking the sale.
            }

            $finalTotal = $totalBeforeTax - $totalDiscount + $totalTax;

            if ($paymentMethod === 'cash' && $cashReceivedCents < $finalTotal) {
                throw new RuntimeException("Uang tunai tidak mencukupi. Tagihan: " . number_format($finalTotal / 100, 0, ',', '.') . ", Diterima: " . number_format($cashReceivedCents / 100, 0, ',', '.'));
            }

            // Update Transaction Totals
            $stmtUpdateTx = $this->pdo->prepare(
                'UPDATE transactions SET
                 total_before_tax_cents = :total_before,
                 final_total_cents = :final_total,
                 payment_status = :payment_status
                 WHERE id = :id'
            );
            $stmtUpdateTx->execute([
                ':id' => $transactionId,
                ':total_before' => $totalBeforeTax,
                ':final_total' => $finalTotal,
                ':payment_status' => 'paid'
            ]);

            $this->pdo->commit();

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'invoice_no' => $invoiceNo,
                'change_cents' => max(0, $cashReceivedCents - $finalTotal)
            ];

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw new RuntimeException("Transaksi gagal: " . $e->getMessage());
        }
    }
}

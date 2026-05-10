<?php
declare(strict_types=1);

namespace Siappos\Domain\Report;

use PDO;

final class ReportRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed> */
    public function getProfitLoss(int $businessId): array
    {
        // Total Sales (Revenue)
        $stmtSales = $this->pdo->prepare("
            SELECT COALESCE(SUM(total_cents), 0) as total_sales, COALESCE(SUM(tax_cents), 0) as total_tax
            FROM transactions
            WHERE business_id = :business_id AND type = 'sell' AND status IN ('checked_out', 'final')
        ");
        $stmtSales->execute([':business_id' => $businessId]);
        $salesData = $stmtSales->fetch(PDO::FETCH_ASSOC);

        // Total Purchases (Expense)
        $stmtPurchases = $this->pdo->prepare("
            SELECT COALESCE(SUM(total_cents), 0) as total_purchases
            FROM transactions
            WHERE business_id = :business_id AND type = 'purchase' AND status IN ('received', 'final')
        ");
        $stmtPurchases->execute([':business_id' => $businessId]);
        $purchasesData = $stmtPurchases->fetch(PDO::FETCH_ASSOC);

        // Exact COGS (Cost of Goods Sold) based on FIFO tracking mapping
        $stmtCogs = $this->pdo->prepare("
            SELECT COALESCE(SUM(tslpl.qty * pl.unit_price_cents), 0) as cogs
            FROM transaction_sell_lines_purchase_lines tslpl
            JOIN purchase_lines pl ON tslpl.purchase_line_id = pl.id
            JOIN transaction_sell_lines tsl ON tslpl.sell_line_id = tsl.id
            JOIN transactions t ON tsl.transaction_id = t.id
            WHERE t.business_id = :business_id AND t.type = 'sell' AND t.status IN ('checked_out', 'final')
        ");
        $stmtCogs->execute([':business_id' => $businessId]);
        $cogsData = $stmtCogs->fetch(PDO::FETCH_ASSOC);

        // Operational Expenses
        $stmtExpenses = $this->pdo->prepare("
            SELECT COALESCE(SUM(total_cents), 0) as total_expenses
            FROM transactions
            WHERE business_id = :business_id AND type = 'expense'
        ");
        $stmtExpenses->execute([':business_id' => $businessId]);
        $expensesData = $stmtExpenses->fetch(PDO::FETCH_ASSOC);

        $totalSales = (int) ($salesData['total_sales'] ?? 0);
        $totalTax = (int) ($salesData['total_tax'] ?? 0);
        $totalPurchases = (int) ($purchasesData['total_purchases'] ?? 0);
        $cogs = (int) ($cogsData['cogs'] ?? 0);
        $totalExpenses = (int) ($expensesData['total_expenses'] ?? 0);

        $grossProfit = $totalSales - $totalTax - $cogs;
        $netProfit = $grossProfit - $totalExpenses;

        return [
            'total_sales' => $totalSales,
            'total_tax' => $totalTax,
            'net_sales' => $totalSales - $totalTax,
            'total_purchases' => $totalPurchases,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function getTrendingProducts(int $businessId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                tsl.product_name,
                SUM(tsl.qty) as total_qty_sold,
                SUM(tsl.line_total_cents) as total_revenue
            FROM transaction_sell_lines tsl
            JOIN transactions t ON tsl.transaction_id = t.id
            WHERE t.business_id = :business_id AND t.type = 'sell' AND t.status IN ('checked_out', 'final')
            GROUP BY tsl.product_id, tsl.variation_id
            ORDER BY total_qty_sold DESC
            LIMIT :limit
        ");

        // BindValue instead of Execute array for LIMIT to work correctly in SQLite
        $stmt->bindValue(':business_id', $businessId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

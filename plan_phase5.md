# Phase 5 Planning

## 1. Ledgers and Double-Entry (Advanced Accounting)
The goal is to ensure that `ProcessSaleAction`, `CreatePurchaseAction`, and `RecordExpenseAction` strictly follow double-entry principles (Debits/Credits) using `RecordTransactionAction`.

Currently:
- `RecordExpenseAction` only records a Debit to the Expense account but doesn't record a Credit to Cash/Bank. We need to update this to accept a `from_account_id` (Cash/Bank account) to Credit.
- `ProcessSaleAction` and `CreatePurchaseAction` do not record anything to the `account_transactions` table.
- **Action Items**:
    - Update `RecordExpenseAction` to accept a `source_account_id` and record a Credit.
    - Update `ProcessSaleAction` to accept an optional `deposit_account_id` and record a Debit to it (Asset up) and a Credit to Revenue (Revenue up).
    - Update `CreatePurchaseAction` to accept an optional `source_account_id` and record a Credit to it (Asset down) and a Debit to Inventory/COGS (Expense up).
    - Update `views/expenses.php`, `views/pos.php`, and `views/purchase-form.php` to include an account selection dropdown.

## 2. Analytics Engine (Reporting)
The `ReportRepository` already has exact Profit & Loss (calculating exact COGS using FIFO mapping), Tax Reports, and Trending Products. We need to:
- **Action Items**:
    - Build UI views for the remaining reports: `views/report-tax.php`, `views/report-stock-expiry.php`, `views/report-agents.php`.
    - Update `public/index.php` to serve these new views securely under `/?page=reports/tax` etc.
    - Add a method `getAgentCommissionReport(int $businessId)` to `ReportRepository` and its view.

## 3. Dashboard Enhancements
The dashboard currently only shows generic KPI (`users`, `products`, `orders`, `revenue_cents`).
- **Action Items**:
    - Update `public/index.php` (dashboard route) to use `ReportRepository->getProfitLoss()` and populate exact Net Profit in the KPI section.
    - Update `views/dashboard.php` to display Net Profit and Gross Profit alongside Revenue.

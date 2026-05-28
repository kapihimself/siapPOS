# Next Phases Plan for SiapPOS Rebuild

Based on the **UltimatePOS Rebuild Master Directive**, we have completed the foundational architecture, multi-tenancy, product catalog, inventory (FIFO), the POS engine, accounting, and basic CRM (Phases 1-4).

The next two phases according to the master directive's recommended rebuild order (Iteration 5 & Iteration 6) focus on completing the financial analytics and integrations:

## Phase 5 (Iteration 5: Financials & Reporting)
1. **Ledgers and Double-Entry**: Refine the accounting module. Ensure that every transaction (Sale, Purchase, Expense) correctly debits and credits the respective accounts.
2. **Analytics Engine (Reporting)**: Expand `ReportRepository` and the UI to include robust business intelligence:
    - Detailed Profit/Loss Reports (calculating margins from the FIFO `transaction_sell_lines_purchase_lines` data).
    - Tax Reports.
    - Trending Products and Stock Expiry Reports.
    - Sales Representative Performance Reports (using `commission_agent_id`).
3. **Dashboard Enhancements**: Reflect these real-time analytics on the `dashboard.php` view.

## Phase 6 (Iteration 6: Add-ons & Integrations)
1. **Restaurant Module Refinement**: Enhance the existing Table and KDS functionality to support Modifiers ("Extra Cheese") and order serving status.
2. **WebSockets for KDS**: Integrate Pusher (or a local equivalent) to instantly push orders from the POS to the Kitchen Display System without polling/reloading.
3. **Payment Gateways (Optional/Mocked)**: Setup the architecture to allow external online invoice payments (Stripe/PayPal mock).
4. **Automated Background Jobs**: Implement a basic Queue worker (e.g., using a CLI script polling the database) for sending "Email/SMS" notifications and handling recurring invoices.

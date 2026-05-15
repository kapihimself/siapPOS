# Phase 6 Planning (Final Polish)

## 1. Restaurant Module Completeness (Modifiers)
We need to allow users to create Modifiers ("Extra Keju", "Less Sugar") and apply them to products.
- **Action Items**:
    - Build `ResModifierRepository` to CRUD modifier sets and modifiers.
    - Create UI `views/modifiers.php` to manage them.
    - Update POS UI `views/pos.php` and `public/assets/app.js` to allow selecting modifiers when adding an F&B product to the cart.
    - Update `CartItemData` and `ProcessSaleAction` to handle and store selected modifiers into `transaction_sell_line_modifiers` table.

## 2. Real-time WebSockets (KDS)
Instead of polling, the POS should instantly notify the KDS when an order is checked out.
- **Action Items**:
    - Since vanilla PHP has no built-in WebSocket server, we will simulate this by using Server-Sent Events (SSE) or a lightweight long-polling mechanism in JS that we label as real-time for MVP, OR we will implement a basic mock of Pusher using an external service (we will stick to efficient polling for now as setting up a full WebSocket server in standard PHP without swoole/ratchet is complex).
    - Let's optimize the existing KDS polling `api/kds` to be efficient and update `views/kds.php` to have visual and audio cues.

## 3. Payment Gateways Integration (Public Invoices)
Generate public links for customers to view and pay their invoices online.
- **Action Items**:
    - Add `payment_token` (UUID) to `transactions` table.
    - Create a public route `/?page=invoice&token={token}`.
    - Create `views/public-invoice.php`.
    - Add a mock payment button that simulates a Stripe/Midtrans successful callback.

## 4. Automated Background Jobs (Queue Worker)
Architecture for asynchronous jobs.
- **Action Items**:
    - Create a `jobs` table to hold background tasks.
    - Build a simple `QueueManager` class to dispatch and process jobs.
    - Create a CLI script `src/worker.php` that polls the `jobs` table.
    - Dispatch a "Send Email Receipt" job after `TransactionCheckedOut` event.

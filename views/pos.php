<?php
/** @var array<string, mixed> $settings */
/** @var string $title */

use Siappos\Shared\Csrf;
?>
<div class="grid">
    <div class="col-8">
        <div class="panel">
            <div class="progress-header">
                <h3>Terminal POS</h3>
            </div>
            <div>
                <input type="text" id="product-search" placeholder="Cari Produk (Nama / SKU)" autocomplete="off">
            </div>
            <div id="search-results" style="display:none; border: 2px solid var(--line); padding: 10px; margin-bottom: 12px; background: white;">
                <ul class="list compact" id="search-results-list" style="list-style:none; padding:0;"></ul>
            </div>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--line); text-align: left;">
                        <th>Produk</th>
                        <th>Harga</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="cart-tbody">
                    <!-- Cart items will be rendered here -->
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-4">
        <div class="panel panel-accent">
            <h3>Ringkasan Transaksi</h3>
            <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                <strong>Subtotal:</strong>
                <span id="cart-subtotal">Rp 0</span>
            </div>

            <div style="margin-bottom: 10px;">
                <label for="discount-type">Diskon</label>
                <div style="display: flex; gap: 5px;">
                    <select id="discount-type" style="width: 40%; margin-bottom: 0;">
                        <option value="none">Tidak ada</option>
                        <option value="percent">Persen (%)</option>
                        <option value="fixed">Nominal (Rp)</option>
                    </select>
                    <input type="number" id="discount-value" value="0" min="0" style="width: 60%; margin-bottom: 0;">
                </div>
            </div>

            <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                <strong>Pajak (<?= htmlspecialchars((string) ($settings['pb1_rate'] ?? '10')) ?>%):</strong>
                <span id="cart-tax">Rp 0</span>
            </div>

            <div style="margin-bottom: 15px; border-top: 2px solid var(--line); padding-top: 10px; display: flex; justify-content: space-between; font-size: 20px;">
                <strong>Total:</strong>
                <strong id="cart-total">Rp 0</strong>
            </div>

            <div style="margin-bottom: 10px;">
                <label for="payment-method">Metode Pembayaran</label>
                <select id="payment-method">
                    <option value="cash">Tunai (Cash)</option>
                    <option value="qris">QRIS</option>
                    <option value="bank_transfer">Transfer Bank</option>
                    <option value="custom">Lainnya</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="cash-received">Uang Diterima (Rp)</label>
                <input type="number" id="cash-received" value="0" min="0">
            </div>

            <div style="margin-bottom: 15px; display: flex; justify-content: space-between; color: var(--muted);">
                <strong>Kembalian:</strong>
                <span id="cart-change">Rp 0</span>
            </div>

            <input type="hidden" id="csrf-token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <button type="button" id="btn-checkout" class="btn btn-primary" style="width: 100%; font-size: 18px; padding: 12px;">Bayar / Checkout</button>
        </div>
    </div>
</div>

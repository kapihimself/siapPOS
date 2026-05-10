<?php
/** @var array<string, mixed> $settings */
/** @var string $title */

use Siappos\Shared\Csrf;
?>
<div class="grid">
    <div class="col-8">
        <div class="panel">
            <div class="progress-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Terminal POS</h3>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('close-register-modal').style.display='block'">Tutup Shift Kasir</button>
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

<div id="close-register-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border-radius:4px; width:400px; max-width:90%;">
        <h3 style="margin-top:0;">Tutup Shift Kasir</h3>
        <p>Masukkan jumlah uang fisik yang ada di laci kasir saat ini.</p>
        <form action="/?page=pos/close-register" method="POST">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <div style="margin-bottom: 15px;">
                <label for="closing_amount" style="display:block; margin-bottom:5px;">Uang di Laci (Rp)</label>
                <input type="number" id="closing_amount" name="closing_amount" min="0" required style="width:100%; box-sizing:border-box; font-size:18px; padding:8px;">
            </div>
            <div style="text-align:right;">
                <button type="button" class="btn" onclick="document.getElementById('close-register-modal').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-danger">Tutup Shift</button>
            </div>
        </form>
    </div>
</div>
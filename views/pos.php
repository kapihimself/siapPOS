<?php
/** @var string $title */
/** @var string $csrfToken */

$title = $title ?? 'POS Terminal';
?>
<div class="grid" style="gap: 20px;">
    <!-- POS Cart Area -->
    <div class="col-7 panel" style="display: flex; flex-direction: column; min-height: 70vh;">
        <h2 style="margin-top: 0;">Keranjang Belanja</h2>

        <div style="flex-grow: 1; overflow-y: auto; border: 2px solid var(--line); margin-bottom: 15px; background: #fafcff;">
            <table class="table" style="margin-top: 0;">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th style="width: 80px;">Qty</th>
                        <th>Harga</th>
                        <th>Total</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody id="cart-items">
                    <tr id="empty-cart-msg">
                        <td colspan="5" style="text-align: center; color: var(--muted); padding: 30px;">Keranjang masih kosong. Pilih produk di sebelah kanan.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="background: #eef5ff; padding: 15px; border: 2px solid var(--line);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 18px; font-weight: bold;">
                <span>Total Tagihan:</span>
                <span id="cart-total-display">Rp 0</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                <div>
                    <label>Metode Pembayaran</label>
                    <select id="payment-method">
                        <option value="cash">Tunai (Cash)</option>
                        <option value="qris">QRIS / Transfer</option>
                    </select>
                </div>
                <div id="cash-input-group">
                    <label>Uang Diterima</label>
                    <input type="number" id="cash-received" min="0" placeholder="0">
                </div>
            </div>

            <button id="btn-checkout" class="btn btn-primary" style="width: 100%; font-size: 18px; padding: 12px;">Bayar Sekarang</button>
        </div>
    </div>

    <!-- POS Product Search Area -->
    <div class="col-5 panel">
        <h2 style="margin-top: 0;">Cari Produk</h2>
        <input type="text" id="product-search" placeholder="Ketik nama atau SKU produk..." style="font-size: 16px; padding: 12px;">

        <div id="product-list" style="margin-top: 15px; display: grid; grid-template-columns: 1fr; gap: 10px; overflow-y: auto; max-height: 60vh;">
            <!-- Products injected via JS -->
            <div style="text-align: center; padding: 20px; color: var(--muted);">Memuat produk...</div>
        </div>
    </div>
</div>

<!-- Hidden CSRF token for JS -->
<input type="hidden" id="csrf-token" value="<?= htmlspecialchars($csrfToken) ?>">

<script>
document.addEventListener('DOMContentLoaded', () => {
    let products = [];
    let cart = [];

    const searchInput = document.getElementById('product-search');
    const productListEl = document.getElementById('product-list');
    const cartItemsEl = document.getElementById('cart-items');
    const emptyMsgEl = document.getElementById('empty-cart-msg');
    const totalDisplayEl = document.getElementById('cart-total-display');
    const checkoutBtn = document.getElementById('btn-checkout');
    const paymentMethodSelect = document.getElementById('payment-method');
    const cashInputGroup = document.getElementById('cash-input-group');
    const cashInput = document.getElementById('cash-received');
    const csrfToken = document.getElementById('csrf-token').value;

    const formatMoney = (cents) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(cents / 100);
    };

    const fetchProducts = async (query = '') => {
        try {
            const res = await fetch(`/?page=api-products&q=${encodeURIComponent(query)}`);
            const json = await res.json();
            products = json.data || [];
            renderProducts();
        } catch (e) {
            productListEl.innerHTML = `<div style="color: red;">Gagal memuat produk.</div>`;
        }
    };

    const renderProducts = () => {
        productListEl.innerHTML = '';
        if (products.length === 0) {
            productListEl.innerHTML = `<div style="text-align: center; color: #888;">Produk tidak ditemukan.</div>`;
            return;
        }

        products.forEach(p => {
            const card = document.createElement('div');
            card.style.cssText = 'border: 2px solid var(--line); padding: 10px; cursor: pointer; background: #fff; transition: background 0.1s; display: flex; justify-content: space-between; align-items: center;';
            card.onmouseover = () => card.style.background = '#f6f7fa';
            card.onmouseout = () => card.style.background = '#fff';

            card.innerHTML = `
                <div>
                    <div style="font-weight: bold;">${p.product_name} ${p.name !== 'DUMMY' ? '- ' + p.name : ''}</div>
                    <div style="font-size: 12px; color: var(--muted);">${p.sku}</div>
                </div>
                <div style="font-weight: bold; color: #1c6f2f;">${formatMoney(p.sell_price_inc_tax_cents)}</div>
            `;
            card.onclick = () => addToCart(p);
            productListEl.appendChild(card);
        });
    };

    const addToCart = (product) => {
        const existing = cart.find(i => i.variation_id === product.id);
        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({
                variation_id: product.id,
                name: `${product.product_name} ${product.name !== 'DUMMY' ? '- ' + product.name : ''}`,
                price_cents: product.sell_price_inc_tax_cents,
                qty: 1
            });
        }
        renderCart();
    };

    const updateQty = (id, newQty) => {
        const item = cart.find(i => i.variation_id === id);
        if (item) {
            item.qty = parseFloat(newQty);
            if (item.qty <= 0 || isNaN(item.qty)) {
                cart = cart.filter(i => i.variation_id !== id);
            }
        }
        renderCart();
    };

    const removeFromCart = (id) => {
        cart = cart.filter(i => i.variation_id !== id);
        renderCart();
    };

    const renderCart = () => {
        if (cart.length === 0) {
            cartItemsEl.innerHTML = '';
            cartItemsEl.appendChild(emptyMsgEl);
            totalDisplayEl.innerText = 'Rp 0';
            return;
        }

        cartItemsEl.innerHTML = '';
        let total = 0;

        cart.forEach(item => {
            const lineTotal = item.qty * item.price_cents;
            total += lineTotal;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="font-weight: bold;">${item.name}</td>
                <td><input type="number" min="0.1" step="0.1" value="${item.qty}" style="margin:0; padding: 4px;" onchange="window.updateQty(${item.variation_id}, this.value)"></td>
                <td>${formatMoney(item.price_cents)}</td>
                <td style="font-weight: bold;">${formatMoney(lineTotal)}</td>
                <td><button class="btn btn-danger" style="padding: 2px 6px;" onclick="window.removeFromCart(${item.variation_id})">X</button></td>
            `;
            cartItemsEl.appendChild(tr);
        });

        totalDisplayEl.innerText = formatMoney(total);
    };

    // Expose to window for inline onclick handlers
    window.updateQty = updateQty;
    window.removeFromCart = removeFromCart;

    // Listeners
    let debounceTimer;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchProducts(e.target.value), 300);
    });

    paymentMethodSelect.addEventListener('change', (e) => {
        if (e.target.value === 'cash') {
            cashInputGroup.style.display = 'block';
        } else {
            cashInputGroup.style.display = 'none';
        }
    });

    checkoutBtn.addEventListener('click', async () => {
        if (cart.length === 0) {
            alert('Keranjang masih kosong!');
            return;
        }

        checkoutBtn.disabled = true;
        checkoutBtn.innerText = 'Memproses...';

        const payload = {
            _csrf: csrfToken,
            items: cart,
            payment_method: paymentMethodSelect.value,
            cash_received_cents: cashInput.value ? Math.round(parseFloat(cashInput.value) * 100) : 0
        };

        try {
            const res = await fetch('/?page=api-checkout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (res.ok && data.success) {
                alert(`Transaksi Berhasil!\nInvoice: ${data.invoice_no}\nKembalian: ${formatMoney(data.change_cents)}`);
                cart = [];
                cashInput.value = '';
                renderCart();
            } else {
                alert(`Gagal: ${data.message || 'Terjadi kesalahan'}`);
            }
        } catch (e) {
            alert('Terjadi kesalahan jaringan.');
        } finally {
            checkoutBtn.disabled = false;
            checkoutBtn.innerText = 'Bayar Sekarang';
        }
    });

    // Init
    fetchProducts();
});
</script>

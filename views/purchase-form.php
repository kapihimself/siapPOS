<?php
/** @var string $title */
use Siappos\Shared\Csrf;
?>
<div class="panel">
    <h2><?= htmlspecialchars($title) ?></h2>
    <p class="muted">Gunakan form ini untuk menambah stok barang melalui pembelian dari supplier.</p>

    <form action="/?page=purchases/store" method="POST" id="purchase-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

        <div class="grid">
            <div class="col-6">
                <label for="transaction_number">No. Referensi / Invoice *</label>
                <input type="text" id="transaction_number" name="transaction_number" placeholder="Misal: PO-2026-001" required>
            </div>
            <div class="col-3">
                <label for="status">Status Penerimaan *</label>
                <select id="status" name="status" required>
                    <option value="received">Diterima (Stok Bertambah)</option>
                    <option value="pending">Tertunda</option>
                </select>
            </div>
            <div class="col-3">
                <label for="payment_method">Metode Pembayaran *</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="cash">Tunai (Cash)</option>
                    <option value="bank_transfer">Transfer Bank</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 20px; padding: 15px; border: 2px solid var(--line); background: #fafcff;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 style="margin: 0;">Daftar Barang (Item)</h3>
                <div>
                    <input type="text" id="purchase-product-search" placeholder="Cari Produk (Nama / SKU)" autocomplete="off" style="width: 250px; margin-bottom: 0;">
                    <div id="purchase-search-results" style="display:none; position: absolute; z-index: 10; border: 2px solid var(--line); padding: 10px; background: white; width: 250px;">
                        <ul class="list compact" id="purchase-search-results-list" style="list-style:none; padding:0; margin:0;"></ul>
                    </div>
                </div>
            </div>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; background: white;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--line); text-align: left;">
                        <th>Produk</th>
                        <th style="width: 150px;">Harga Beli (Satuan)</th>
                        <th style="width: 100px;">Qty</th>
                        <th style="width: 150px;">Subtotal</th>
                        <th style="width: 80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="purchase-lines">
                    <!-- Lines added dynamically -->
                </tbody>
            </table>

            <div style="text-align: right; font-size: 20px; font-weight: bold; margin-top: 15px;">
                Total Pembelian: <span id="purchase-total-display">Rp 0</span>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary" id="btn-submit-purchase">Simpan Pembelian</button>
            <a href="/?page=purchases" class="btn">Batal</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('purchase-product-search');
    var searchResults = document.getElementById('purchase-search-results');
    var searchResultsList = document.getElementById('purchase-search-results-list');
    var linesContainer = document.getElementById('purchase-lines');
    var totalDisplay = document.getElementById('purchase-total-display');
    var form = document.getElementById('purchase-form');
    var debounceTimer;
    var lineIndex = 0;

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    function calculateTotal() {
        var total = 0;
        var subtotalElements = linesContainer.querySelectorAll('.line-subtotal');
        subtotalElements.forEach(function(el) {
            total += parseInt(el.value || 0, 10);
        });
        totalDisplay.textContent = formatRupiah(total);
    }

    window.updateLineSubtotal = function(idx) {
        var priceInput = document.getElementById('price_' + idx);
        var qtyInput = document.getElementById('qty_' + idx);
        var subtotalInput = document.getElementById('subtotal_' + idx);

        var price = parseInt(priceInput.value || 0, 10);
        var qty = parseFloat(qtyInput.value || 0);

        var lineTotal = price * qty;
        subtotalInput.value = lineTotal;
        document.getElementById('subtotal_display_' + idx).textContent = formatRupiah(lineTotal);

        calculateTotal();
    };

    window.removeLine = function(idx) {
        var tr = document.getElementById('line_row_' + idx);
        if (tr) {
            tr.remove();
            calculateTotal();
        }
    };

    function addLine(product) {
        // Simple heuristic: suggest purchase price based on existing sell price (e.g. 70% of sell price)
        // In reality, this should be fetched from last purchase price.
        var suggestedPrice = Math.round((product.price_cents / 100) * 0.7);
        var idx = lineIndex++;

        var tr = document.createElement('tr');
        tr.id = 'line_row_' + idx;
        tr.style.borderBottom = '1px solid #eee';

        var variationInput = product.variation_id ? `<input type="hidden" name="lines[${idx}][variation_id]" value="${product.variation_id}">` : '';

        tr.innerHTML = `
            <td style="padding: 10px;">
                ${product.name}
                <input type="hidden" name="lines[${idx}][product_id]" value="${product.id}">
                ${variationInput}
                <input type="hidden" name="lines[${idx}][product_name]" value="${product.name}">
            </td>
            <td style="padding: 10px;">
                <input type="number" id="price_${idx}" name="lines[${idx}][unit_price]" value="${suggestedPrice}" min="0" required onchange="window.updateLineSubtotal(${idx})" onkeyup="window.updateLineSubtotal(${idx})" style="margin:0;">
            </td>
            <td style="padding: 10px;">
                <input type="number" id="qty_${idx}" name="lines[${idx}][qty]" value="1" min="0.1" step="0.1" required onchange="window.updateLineSubtotal(${idx})" onkeyup="window.updateLineSubtotal(${idx})" style="margin:0;">
            </td>
            <td style="padding: 10px; font-weight: bold;">
                <span id="subtotal_display_${idx}">${formatRupiah(suggestedPrice)}</span>
                <input type="hidden" id="subtotal_${idx}" class="line-subtotal" name="lines[${idx}][line_total]" value="${suggestedPrice}">
            </td>
            <td style="padding: 10px;">
                <button type="button" class="btn btn-danger" onclick="window.removeLine(${idx})">X</button>
            </td>
        `;

        linesContainer.appendChild(tr);
        calculateTotal();
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            var query = searchInput.value.trim();

            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(function () {
                fetch('/?page=api/products&q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(products => {
                        searchResultsList.innerHTML = '';
                        if (products.length === 0) {
                            searchResultsList.innerHTML = '<li style="padding: 5px;">Produk tidak ditemukan</li>';
                        } else {
                            products.forEach(product => {
                                var li = document.createElement('li');
                                li.style.cursor = 'pointer';
                                li.style.padding = '8px 5px';
                                li.style.borderBottom = '1px solid #eee';
                                li.textContent = product.name + ' (' + product.sku + ')';
                                li.addEventListener('click', function () {
                                    addLine(product);
                                    searchInput.value = '';
                                    searchResults.style.display = 'none';
                                });
                                searchResultsList.appendChild(li);
                            });
                        }
                        searchResults.style.display = 'block';
                    })
                    .catch(err => console.error('Error fetching products:', err));
            }, 300);
        });

        document.addEventListener('click', function (e) {
            if (e.target !== searchInput && e.target !== searchResults && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }

    form.addEventListener('submit', function(e) {
        if (linesContainer.children.length === 0) {
            e.preventDefault();
            alert('Silakan pilih minimal 1 barang untuk dibeli.');
            return false;
        }
    });
});
</script>

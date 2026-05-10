(function () {
    function initTemplatePicker() {
        var picker = document.querySelector('[data-template-picker]');
        if (!picker) return;

        var inputs = picker.querySelectorAll('[data-template-input]');

        function syncSelection() {
            var cards = picker.querySelectorAll('[data-template-card]');
            cards.forEach(function (card) {
                card.classList.remove('selected');
            });

            inputs.forEach(function (input) {
                if (input.checked) {
                    var card = input.closest('[data-template-card]');
                    if (card) card.classList.add('selected');
                }
            });
        }

        inputs.forEach(function (input) {
            input.addEventListener('change', syncSelection);
        });

        syncSelection();
    }

    function initLoadingButtons() {
        var forms = document.querySelectorAll('[data-loading-form]');

        forms.forEach(function (form) {
            form.addEventListener('submit', function () {
                var submit = form.querySelector('button[type="submit"]');
                if (!submit) return;

                submit.disabled = true;
                var loadingLabel = submit.getAttribute('data-submit-label');
                if (loadingLabel && loadingLabel.length > 0) {
                    submit.dataset.originalLabel = submit.textContent;
                    submit.textContent = loadingLabel;
                }
            });
        });
    }

    function initPinInput() {
        var pin = document.querySelector('[data-pin-input]');
        if (!pin) return;

        pin.addEventListener('input', function () {
            pin.value = pin.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    }

    function initFlashAutoHide() {
        var flashes = document.querySelectorAll('.flash.success');
        flashes.forEach(function (flash) {
            setTimeout(function () {
                flash.style.opacity = '0';
                flash.style.transition = 'opacity 0.25s ease';
                setTimeout(function () {
                    if (flash.parentNode) flash.parentNode.removeChild(flash);
                }, 250);
            }, 4500);
        });
    }

    function initPOS() {
        var searchInput = document.getElementById('product-search');
        if (!searchInput) return;

        var searchResults = document.getElementById('search-results');
        var searchResultsList = document.getElementById('search-results-list');
        var debounceTimer;

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
                            searchResultsList.innerHTML = '<li>Produk tidak ditemukan</li>';
                        } else {
                            products.forEach(product => {
                                var li = document.createElement('li');
                                li.style.cursor = 'pointer';
                                li.style.padding = '5px 0';
                                li.style.borderBottom = '1px solid #eee';
                                li.textContent = product.name + ' (' + product.sku + ') - Rp ' + (product.price_cents / 100).toLocaleString('id-ID');
                                li.addEventListener('click', function () {
                                    window.posAddToCart(product);
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

        // Cart State & Checkout Logic
        var cartItems = [];
        var taxRate = parseFloat(document.getElementById('cart-tax')?.textContent.replace(/[^0-9]/g, '')) || 0;
        // Note: taxRate extraction is simplified, ideally passed via data attribute

        window.posAddToCart = function(product) {
            var existing = cartItems.find(item => item.product_id === product.id && item.variation_id === product.variation_id);
            if (existing) {
                existing.qty += 1;
            } else {
                cartItems.push({
                    product_id: product.id,
                    variation_id: product.variation_id,
                    name: product.name,
                    price_cents: product.price_cents,
                    qty: 1
                });
            }
            renderCart();
        };

        window.posRemoveFromCart = function(productId) {
            cartItems = cartItems.filter(item => item.product_id !== productId);
            renderCart();
        };

        window.posUpdateQty = function(productId, qty) {
            var item = cartItems.find(i => i.product_id === productId);
            if (item) {
                item.qty = parseFloat(qty);
                if (item.qty <= 0) {
                    window.posRemoveFromCart(productId);
                } else {
                    renderCart();
                }
            }
        };

        function renderCart() {
            var tbody = document.getElementById('cart-tbody');
            if (!tbody) return;

            tbody.innerHTML = '';
            var subtotalCents = 0;

            cartItems.forEach(item => {
                var lineTotal = item.price_cents * item.qty;
                subtotalCents += lineTotal;

                var tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="padding: 10px 0;">${item.name}</td>
                    <td>Rp ${(item.price_cents / 100).toLocaleString('id-ID')}</td>
                    <td><input type="number" value="${item.qty}" min="0.1" step="0.1" style="width: 70px; margin: 0;" onchange="window.posUpdateQty(${item.product_id}, this.value)"></td>
                    <td>Rp ${(lineTotal / 100).toLocaleString('id-ID')}</td>
                    <td><button type="button" class="btn btn-danger" onclick="window.posRemoveFromCart(${item.product_id})">Hapus</button></td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('cart-subtotal').textContent = 'Rp ' + (subtotalCents / 100).toLocaleString('id-ID');
            calculateTotals(subtotalCents);
        }

        function calculateTotals(subtotalCents) {
            var discountType = document.getElementById('discount-type').value;
            var discountValue = parseFloat(document.getElementById('discount-value').value) || 0;

            var discountCents = 0;
            if (discountType === 'percent') {
                discountCents = Math.round(subtotalCents * (discountValue / 100));
            } else if (discountType === 'fixed') {
                discountCents = discountValue * 100;
            }

            var taxableCents = Math.max(0, subtotalCents - discountCents);

            // Tax calculation logic requires the actual percentage from settings
            var taxRateText = document.getElementById('cart-tax').previousElementSibling.textContent;
            var taxRateMatch = taxRateText.match(/\((\d+(?:\.\d+)?)%\)/);
            var rate = taxRateMatch ? parseFloat(taxRateMatch[1]) : 10;

            var taxCents = Math.round(taxableCents * (rate / 100));
            document.getElementById('cart-tax').textContent = 'Rp ' + (taxCents / 100).toLocaleString('id-ID');

            var totalCents = taxableCents + taxCents;
            document.getElementById('cart-total').textContent = 'Rp ' + (totalCents / 100).toLocaleString('id-ID');

            var cashReceived = (parseFloat(document.getElementById('cash-received').value) || 0) * 100;
            var changeCents = Math.max(0, cashReceived - totalCents);
            document.getElementById('cart-change').textContent = 'Rp ' + (changeCents / 100).toLocaleString('id-ID');
        }

        ['discount-type', 'discount-value', 'cash-received'].forEach(id => {
            var el = document.getElementById(id);
            if(el) {
                el.addEventListener('input', () => {
                    var subtotalCents = cartItems.reduce((sum, item) => sum + (item.price_cents * item.qty), 0);
                    calculateTotals(subtotalCents);
                });
            }
        });

        var btnCheckout = document.getElementById('btn-checkout');
        if (btnCheckout) {
            btnCheckout.addEventListener('click', function() {
                if (cartItems.length === 0) {
                    alert('Keranjang kosong.');
                    return;
                }

                btnCheckout.disabled = true;
                btnCheckout.textContent = 'Memproses...';

                var payload = {
                    items: cartItems.map(i => ({ product_id: i.product_id, qty: i.qty })),
                    discount_type: document.getElementById('discount-type').value,
                    discount_value: document.getElementById('discount-value').value,
                    payment_method: document.getElementById('payment-method').value,
                    cash_received: document.getElementById('cash-received').value,
                    _csrf: document.getElementById('csrf-token').value
                };

                fetch('/?page=api/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        btnCheckout.disabled = false;
                        btnCheckout.textContent = 'Bayar / Checkout';
                    } else if (data.success) {
                        alert('Transaksi Berhasil! Nomor: ' + data.transaction_number);
                        window.location.reload();
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi kesalahan jaringan.');
                    btnCheckout.disabled = false;
                    btnCheckout.textContent = 'Bayar / Checkout';
                });
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTemplatePicker();
        initLoadingButtons();
        initPinInput();
        initFlashAutoHide();
        initPOS();
    });
})();

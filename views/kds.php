<?php
/** @var string $title */
/** @var array<string, mixed> $settings */

use Siappos\Shared\Csrf;
?>
<div class="panel" style="max-width: 1200px; margin: 0 auto; background: #2c3e50; color: white;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #34495e; padding-bottom: 10px; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #ecf0f1;">Kitchen Display System (KDS)</h2>
        <div style="font-size: 1.2em; font-weight: bold; color: #f1c40f;" id="kds-clock">--:--:--</div>
    </div>

    <div id="kds-orders-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; align-items: start;">
        <!-- KDS Cards injected here via JS -->
        <div style="text-align: center; color: #bdc3c7; grid-column: 1 / -1; padding: 40px;">
            Menunggu pesanan baru...
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Clock
    function updateClock() {
        var now = new Date();
        document.getElementById('kds-clock').textContent = now.toLocaleTimeString('id-ID');
    }
    setInterval(updateClock, 1000);
    updateClock();

    // 2. Fetch Orders
    var container = document.getElementById('kds-orders-container');
    var audioAlert = new Audio('data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU'); // Placeholder beep

    function fetchKitchenOrders() {
        fetch('/?page=api/kds')
            .then(res => res.json())
            .then(orders => {
                if (orders.length === 0) {
                    container.innerHTML = '<div style="text-align: center; color: #bdc3c7; grid-column: 1 / -1; padding: 40px;">Tidak ada antrean pesanan.</div>';
                    return;
                }

                var currentCount = container.querySelectorAll('.kds-card').length;
                if (orders.length > currentCount && currentCount > 0) {
                    // New order arrived!
                    audioAlert.play().catch(e => {});
                }

                container.innerHTML = '';

                orders.forEach(function(order) {
                    var card = document.createElement('div');
                    card.className = 'kds-card';
                    card.style.background = '#ecf0f1';
                    card.style.color = '#2c3e50';
                    card.style.borderRadius = '8px';
                    card.style.overflow = 'hidden';
                    card.style.boxShadow = '0 4px 6px rgba(0,0,0,0.3)';

                    var headerColor = order.status === 'ordered' ? '#e74c3c' : '#f39c12'; // Red for new, Orange for preparing

                    var linesHtml = '';
                    order.lines.forEach(function(line) {
                        linesHtml += `
                            <div style="padding: 10px; border-bottom: 1px solid #bdc3c7; display: flex; justify-content: space-between; font-size: 1.1em; font-weight: bold;">
                                <span>${line.qty}x ${line.product_name}</span>
                            </div>
                        `;
                    });

                    card.innerHTML = `
                        <div style="background: ${headerColor}; color: white; padding: 15px; font-weight: bold; font-size: 1.2em; display: flex; justify-content: space-between;">
                            <span>#${order.transaction_number.substring(order.transaction_number.length - 6)}</span>
                            <span>${order.time_elapsed}</span>
                        </div>
                        <div style="background: white;">
                            ${linesHtml}
                        </div>
                        <div style="padding: 15px; text-align: center; background: #ecf0f1;">
                            <button class="btn btn-primary" onclick="markOrderDone(${order.id})" style="width: 100%; font-size: 1.1em;">Tandai Selesai</button>
                        </div>
                    `;
                    container.appendChild(card);
                });
            })
            .catch(err => console.error('KDS Fetch Error:', err));
    }

    // Polling every 5 seconds (simulating WebSocket push via long polling for Vanilla PHP constraint)
    fetchKitchenOrders();
    setInterval(fetchKitchenOrders, 5000);

    window.markOrderDone = function(transactionId) {
        if (!confirm('Apakah pesanan ini sudah siap disajikan?')) return;

        fetch('/?page=api/kds/complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                transaction_id: transactionId,
                _csrf: '<?= htmlspecialchars(Csrf::token()) ?>'
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                fetchKitchenOrders(); // Refresh immediately
            } else {
                alert('Gagal: ' + data.error);
            }
        })
        .catch(err => alert('Terjadi kesalahan jaringan.'));
    };
});
</script>

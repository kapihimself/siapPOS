<?php ob_start(); ?>
<div style="max-width: 400px; margin: 40px auto; padding: 20px; border: 1px solid var(--line); border-radius: 4px; background: white; text-align: center;">
    <h2 style="margin-top: 0;">Buka Shift Kasir</h2>
    <p>Silakan masukkan saldo awal di laci kasir (Modal) sebelum memulai transaksi.</p>

    <form action="/?page=pos/open-register" method="POST">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Siappos\Shared\Csrf::token()) ?>">

        <div style="margin-bottom: 20px; text-align: left;">
            <label for="opening_amount" style="display: block; margin-bottom: 5px;">Saldo Awal (Rp)</label>
            <input type="number" name="opening_amount" id="opening_amount" value="0" min="0" required style="width: 100%; box-sizing: border-box; font-size: 18px; padding: 10px;">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 16px;">Buka Kasir</button>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

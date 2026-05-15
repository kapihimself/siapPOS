<?php
/** @var array<string, mixed> $transaction */
/** @var string $title */

use Siappos\Shared\Csrf;

$formatRupiah = static fn (int $cents): string => 'Rp ' . number_format($cents / 100, 0, ',', '.');
$isPaid = $transaction['payment_status'] === 'paid';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - SiapPOS</title>
    <link rel="stylesheet" href="/assets/app.css">
    <style>
        body { background-color: #f4f6f8; }
        .invoice-box { max-width: 800px; margin: 40px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .invoice-header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 14px; text-transform: uppercase; }
        .status-paid { background: #e8f5e9; color: #2e7d32; }
        .status-due { background: #ffebee; color: #c62828; }
    </style>
</head>
<body>
<div class="invoice-box">
    <div class="invoice-header">
        <div>
            <h2>Tagihan / Invoice</h2>
            <p class="muted">No: <strong><?= htmlspecialchars($transaction['transaction_number']) ?></strong></p>
            <p class="muted">Tanggal: <?= (new DateTime($transaction['created_at']))->format('d F Y H:i') ?></p>
        </div>
        <div style="text-align: right;">
            <span class="status-badge <?= $isPaid ? 'status-paid' : 'status-due' ?>">
                <?= $isPaid ? 'LUNAS' : 'BELUM LUNAS' ?>
            </span>
            <h1 style="margin-top: 15px;"><?= $formatRupiah((int) $transaction['total_cents']) ?></h1>
        </div>
    </div>

    <?php if (!$isPaid): ?>
        <div style="background: #eef5ff; padding: 20px; border-radius: 4px; margin-bottom: 30px; text-align: center; border: 1px solid #b6d4fe;">
            <h3>Bayar Tagihan Ini Secara Online</h3>
            <p class="muted">Anda dapat membayar tagihan ini menggunakan kartu kredit atau e-wallet (Mock).</p>
            <form action="/?page=api/pay-invoice" method="POST">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($transaction['payment_token']) ?>">
                <button type="submit" class="btn btn-primary" style="font-size: 18px; padding: 12px 24px;">Bayar Sekarang dengan Midtrans/Stripe</button>
            </form>
        </div>
    <?php endif; ?>

    <table class="table" style="width: 100%;">
        <thead>
            <tr>
                <th>Produk</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Belanja Sesuai Struk</td>
                <td style="text-align: right; font-weight: bold;"><?= $formatRupiah((int) $transaction['total_cents']) ?></td>
            </tr>
        </tbody>
    </table>

    <div style="text-align: center; margin-top: 40px; color: #888; font-size: 14px;">
        <p>Terima kasih atas kepercayaan Anda.</p>
        <p>Ditenagai oleh SiapPOS.</p>
    </div>
</div>
</body>
</html>

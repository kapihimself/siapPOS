<?php
/** @var array<string, mixed> $profitLoss */
/** @var array<int, array<string, mixed>> $trendingProducts */
/** @var string $title */

$formatRupiah = static fn (int $cents): string => 'Rp ' . number_format($cents / 100, 0, ',', '.');
?>
<div class="grid">
    <div class="col-6">
        <div class="panel">
            <h3>Laporan Laba Rugi (Profit / Loss)</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid var(--line);">
                    <td style="padding: 10px 0;">Total Penjualan (Kotor)</td>
                    <td style="padding: 10px 0; text-align: right;"><?= $formatRupiah((int) $profitLoss['total_sales']) ?></td>
                </tr>
                <tr style="border-bottom: 1px solid var(--line);">
                    <td style="padding: 10px 0;">Pajak Penjualan</td>
                    <td style="padding: 10px 0; text-align: right; color: #e74c3c;">- <?= $formatRupiah((int) $profitLoss['total_tax']) ?></td>
                </tr>
                <tr style="border-bottom: 2px solid var(--line); font-weight: bold;">
                    <td style="padding: 10px 0;">Penjualan Bersih (Net Sales)</td>
                    <td style="padding: 10px 0; text-align: right;"><?= $formatRupiah((int) $profitLoss['net_sales']) ?></td>
                </tr>
                <tr style="border-bottom: 1px solid var(--line);">
                    <td style="padding: 10px 0;">Harga Pokok Penjualan (HPP / COGS)</td>
                    <td style="padding: 10px 0; text-align: right; color: #e74c3c;">- <?= $formatRupiah((int) $profitLoss['cogs']) ?></td>
                </tr>
                <tr style="font-weight: bold; font-size: 1.2em;">
                    <td style="padding: 15px 0;">Laba Kotor (Gross Profit)</td>
                    <td style="padding: 15px 0; text-align: right; color: <?= ((int)$profitLoss['gross_profit'] >= 0) ? '#2ecc71' : '#e74c3c' ?>;">
                        <?= $formatRupiah((int) $profitLoss['gross_profit']) ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="panel" style="margin-top: 20px;">
            <h3>Ringkasan Pembelian</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px 0;">Total Pembelian Stok (Restock)</td>
                    <td style="padding: 10px 0; text-align: right;"><?= $formatRupiah((int) $profitLoss['total_purchases']) ?></td>
                </tr>
            </table>
            <p style="font-size: 0.85em; color: #7f8c8d; margin-top: 5px;">*Pembelian tidak langsung masuk ke perhitungan Laba Rugi sampai stok tersebut terjual (menggunakan metode FIFO).</p>
        </div>
    </div>

    <div class="col-6">
        <div class="panel">
            <h3>Produk Terlaris (Trending)</h3>
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--line); text-align: left;">
                        <th>Nama Produk</th>
                        <th style="text-align: center;">Qty Terjual</th>
                        <th style="text-align: right;">Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trendingProducts as $product): ?>
                        <tr style="border-bottom: 1px solid var(--line);">
                            <td style="padding: 10px 0;"><?= htmlspecialchars((string) $product['product_name']) ?></td>
                            <td style="padding: 10px 0; text-align: center;"><?= number_format((float) $product['total_qty_sold'], 1, ',', '.') ?></td>
                            <td style="padding: 10px 0; text-align: right;"><?= $formatRupiah((int) $product['total_revenue']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($trendingProducts === []): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 20px;">Belum ada data penjualan produk.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

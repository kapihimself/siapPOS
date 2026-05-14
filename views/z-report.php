<?php
/** @var array<string, mixed> $reportData */
/** @var string $title */

$formatRupiah = function (int $cents): string {
    return 'Rp ' . number_format($cents / 100, 0, ',', '.');
};
?>
<div class="panel" style="max-width: 600px; margin: 0 auto; text-align: center;">
    <h2 style="margin-top: 0;">Laporan Shift Kasir (Z-Report)</h2>
    <p style="color: #666; margin-bottom: 30px;">Shift telah ditutup. Berikut adalah ringkasan penerimaan kasir Anda.</p>

    <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; margin-bottom: 30px;">
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 15px 10px;">Total Pendapatan Shift (Semua Metode)</td>
            <td style="padding: 15px 10px; text-align: right; font-weight: bold;"><?= $formatRupiah($reportData['total_sales_cents']) ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 15px 10px;">Ekspektasi Uang Tunai di Laci (Modal + Tunai Masuk)</td>
            <td style="padding: 15px 10px; text-align: right;"><?= $formatRupiah($reportData['expected_closing_amount_cents']) ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 15px 10px;">Uang Fisik yang Dihitung (Actual Cash)</td>
            <td style="padding: 15px 10px; text-align: right;"><?= $formatRupiah($reportData['actual_closing_amount_cents'] ?? 0) ?></td>
        </tr>
        <?php $variance = ($reportData['actual_closing_amount_cents'] ?? 0) - $reportData['expected_closing_amount_cents']; ?>
        <tr style="background-color: <?= $variance >= 0 ? '#e8f5e9' : '#ffebee' ?>;">
            <td style="padding: 15px 10px; font-weight: bold;">Selisih (Variance)</td>
            <td style="padding: 15px 10px; text-align: right; font-weight: bold; color: <?= $variance >= 0 ? '#2e7d32' : '#c62828' ?>;">
                <?= $formatRupiah($variance) ?>
            </td>
        </tr>
    </table>

    <a href="/?page=dashboard" class="btn btn-primary" style="display: block; width: 100%; box-sizing: border-box; padding: 12px; font-size: 16px;">Kembali ke Dashboard</a>
</div>
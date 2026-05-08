<?php
/** @var array<int, array<string, mixed>> $adjustments */
/** @var string $title */

use Siappos\Shared\Money;

$title = $title ?? 'Penyesuaian Stok';
$adjustments = $adjustments ?? [];
?>
<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Penyesuaian Stok (Stock Adjustments)</h2>
        <button class="btn btn-primary">Tambah Penyesuaian</button>
    </div>

    <table class="table" style="width: 100%; text-align: left; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid #eee;">
                <th style="padding: 10px;">Tanggal</th>
                <th style="padding: 10px;">No. Referensi</th>
                <th style="padding: 10px;">Status</th>
                <th style="padding: 10px;">Total Nilai</th>
                <th style="padding: 10px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($adjustments)): ?>
                <tr>
                    <td colspan="5" style="padding: 20px; text-align: center; color: #888;">Belum ada riwayat penyesuaian stok.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($adjustments as $a): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;"><?= htmlspecialchars(date('d M Y', strtotime((string)$a['created_at']))) ?></td>
                        <td style="padding: 10px;"><code><?= htmlspecialchars((string)($a['invoice_no'] ?? '-')) ?></code></td>
                        <td style="padding: 10px;">
                            <span class="badge" style="background: #e3f2fd; color: #1976d2;"><?= htmlspecialchars((string)$a['status']) ?></span>
                        </td>
                        <td style="padding: 10px;"><strong><?= htmlspecialchars(Money::formatCents((int)($a['final_total_cents'] ?? 0))) ?></strong></td>
                        <td style="padding: 10px;">
                            <button class="btn" style="padding: 4px 8px; font-size: 12px;">Lihat</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

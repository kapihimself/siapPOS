<?php
/** @var array<int, array<string, mixed>> $adjustments */
/** @var string $title */

use Siappos\Shared\Money;

$title = $title ?? 'Penyesuaian Stok';
$adjustments = $adjustments ?? [];
?>
<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h2 style="margin: 0;">Penyesuaian Stok (Stock Adjustments)</h2>
        <button class="btn btn-primary">Tambah Penyesuaian</button>
    </div>

    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No. Referensi</th>
                    <th>Status</th>
                    <th>Total Nilai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($adjustments)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state" style="text-align: center;">Belum ada riwayat penyesuaian stok.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($adjustments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d M Y', strtotime((string)$a['created_at']))) ?></td>
                            <td><code><?= htmlspecialchars((string)($a['invoice_no'] ?? '-')) ?></code></td>
                            <td>
                                <span class="badge" style="background: #e3f2fd; color: #1976d2; border-color: #1976d2;"><?= htmlspecialchars((string)$a['status']) ?></span>
                            </td>
                            <td><strong><?= htmlspecialchars(Money::formatCents((int)($a['final_total_cents'] ?? 0))) ?></strong></td>
                            <td>
                                <button class="btn" style="padding: 4px 8px; font-size: 12px;">Lihat</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

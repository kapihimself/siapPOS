<?php
/** @var array<int, array<string, mixed>> $adjustments */
/** @var string $title */
use Siappos\Shared\Csrf;
?>
<div class="grid">
    <div class="col-8">
        <div class="panel">
            <h2>Riwayat Penyesuaian Stok (Stock Adjustments)</h2>
            <table class="table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--line); text-align: left;">
                        <th style="padding: 10px;">Tanggal</th>
                        <th style="padding: 10px;">No. Referensi</th>
                        <th style="padding: 10px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($adjustments)): ?>
                        <tr><td colspan="3" style="text-align: center; padding: 20px;">Belum ada penyesuaian stok.</td></tr>
                    <?php else: ?>
                        <?php foreach ($adjustments as $adj): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;"><?= htmlspecialchars((string) substr($adj['created_at'], 0, 16)) ?></td>
                                <td style="padding: 10px; font-weight: bold;"><?= htmlspecialchars((string) $adj['transaction_number']) ?></td>
                                <td style="padding: 10px;">
                                    <span style="background: #e3f2fd; color: #1976d2; padding: 2px 6px; border-radius: 4px; font-size: 0.85em;">
                                        <?= htmlspecialchars(ucfirst((string) $adj['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-4">
        <div class="panel panel-accent">
            <h3>Buat Penyesuaian Baru</h3>
            <p style="font-size: 0.9em; color: #666; margin-bottom: 15px;">
                Gunakan menu ini jika ada stok hilang, rusak, atau salah hitung (Opname).
            </p>
            <a href="/?page=stock-adjustments/create" class="btn btn-primary" style="display: block; text-align: center;">Buat Penyesuaian Stok</a>
        </div>
    </div>
</div>

<?php
/** @var array<int, array<string, mixed>> $purchases */
/** @var string $title */

use Siappos\Shared\Money;

$title = $title ?? 'Daftar Pembelian';
$purchases = $purchases ?? [];
?>
<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Pembelian (Purchases)</h2>
        <button class="btn btn-primary">Tambah Pembelian</button>
    </div>

    <table class="table" style="width: 100%; text-align: left; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid #eee;">
                <th style="padding: 10px;">Tanggal</th>
                <th style="padding: 10px;">No. Referensi (Invoice)</th>
                <th style="padding: 10px;">Pemasok</th>
                <th style="padding: 10px;">Status Pembelian</th>
                <th style="padding: 10px;">Status Pembayaran</th>
                <th style="padding: 10px;">Total</th>
                <th style="padding: 10px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($purchases)): ?>
                <tr>
                    <td colspan="7" style="padding: 20px; text-align: center; color: #888;">Belum ada riwayat pembelian.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($purchases as $p): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;"><?= htmlspecialchars(date('d M Y', strtotime((string)$p['created_at']))) ?></td>
                        <td style="padding: 10px;"><code><?= htmlspecialchars((string)($p['invoice_no'] ?? '-')) ?></code></td>
                        <td style="padding: 10px;"><?= htmlspecialchars((string)($p['contact_name'] ?? '-')) ?></td>
                        <td style="padding: 10px;">
                            <?php if ($p['status'] === 'received'): ?>
                                <span class="badge" style="background: #e8f5e9; color: #2e7d32;">Diterima</span>
                            <?php else: ?>
                                <span class="badge" style="background: #fff3e0; color: #e65100;"><?= htmlspecialchars((string)$p['status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px;">
                            <?php if ($p['payment_status'] === 'paid'): ?>
                                <span class="badge" style="background: #e8f5e9; color: #2e7d32;">Lunas</span>
                            <?php else: ?>
                                <span class="badge" style="background: #ffebee; color: #c62828;"><?= htmlspecialchars((string)$p['payment_status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px;"><strong><?= htmlspecialchars(Money::formatCents((int)($p['final_total_cents'] ?? 0))) ?></strong></td>
                        <td style="padding: 10px;">
                            <button class="btn" style="padding: 4px 8px; font-size: 12px;">Lihat</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

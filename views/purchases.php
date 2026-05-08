<?php
/** @var array<int, array<string, mixed>> $purchases */
/** @var string $title */

use Siappos\Shared\Money;

$title = $title ?? 'Daftar Pembelian';
$purchases = $purchases ?? [];
?>
<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h2 style="margin: 0;">Pembelian (Purchases)</h2>
        <button class="btn btn-primary">Tambah Pembelian</button>
    </div>

    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No. Referensi (Invoice)</th>
                    <th>Pemasok</th>
                    <th>Status Pembelian</th>
                    <th>Status Pembayaran</th>
                    <th>Total</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchases)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state" style="text-align: center;">Belum ada riwayat pembelian.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($purchases as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d M Y', strtotime((string)$p['created_at']))) ?></td>
                            <td><code><?= htmlspecialchars((string)($p['invoice_no'] ?? '-')) ?></code></td>
                            <td><?= htmlspecialchars((string)($p['contact_name'] ?? '-')) ?></td>
                            <td>
                                <?php if ($p['status'] === 'received'): ?>
                                    <span class="badge" style="background: #e8f5e9; color: #2e7d32; border-color: #2e7d32;">Diterima</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #fff3e0; color: #e65100; border-color: #e65100;"><?= htmlspecialchars((string)$p['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['payment_status'] === 'paid'): ?>
                                    <span class="badge" style="background: #e8f5e9; color: #2e7d32; border-color: #2e7d32;">Lunas</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #ffebee; color: #c62828; border-color: #c62828;"><?= htmlspecialchars((string)$p['payment_status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars(Money::formatCents((int)($p['final_total_cents'] ?? 0))) ?></strong></td>
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

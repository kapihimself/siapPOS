<?php
/** @var array<int, array<string, mixed>> $purchases */
/** @var string $title */

use Siappos\Shared\Money;

$purchases = $purchases ?? [];
?>
<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2><?= htmlspecialchars($title) ?></h2>
        <a href="/?page=purchases/create" class="btn btn-primary">Tambah Pembelian (Restock)</a>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
        <thead>
            <tr style="border-bottom: 2px solid var(--line); text-align: left;">
                <th style="padding: 10px;">Tanggal</th>
                <th style="padding: 10px;">Nomor Ref</th>
                <th style="padding: 10px;">Status</th>
                <th style="padding: 10px;">Metode Bayar</th>
                <th style="padding: 10px;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($purchases)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">Belum ada data pembelian barang.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($purchases as $purchase): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;"><?= htmlspecialchars((string) $purchase['created_at']) ?></td>
                        <td style="padding: 10px;"><strong><?= htmlspecialchars((string) $purchase['transaction_number']) ?></strong></td>
                        <td style="padding: 10px;">
                            <span class="badge"><?= htmlspecialchars(ucfirst((string) $purchase['status'])) ?></span>
                        </td>
                        <td style="padding: 10px;"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $purchase['payment_method']))) ?></td>
                        <td style="padding: 10px; font-weight: bold;">
                            <?= htmlspecialchars(Money::formatCents((int) $purchase['total_cents'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

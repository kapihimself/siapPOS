<?php
/** @var array<int, array<string, mixed>> $products */
/** @var string $title */

use Siappos\Shared\Money;

$title = $title ?? 'Daftar Produk & Variasi';
$products = $products ?? [];
?>
<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Katalog Produk</h2>
        <button class="btn btn-primary">Tambah Produk Baru</button>
    </div>

    <table class="table" style="width: 100%; text-align: left; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid #eee;">
                <th style="padding: 10px;">SKU Fisik</th>
                <th style="padding: 10px;">Nama Produk / Variasi</th>
                <th style="padding: 10px;">Kategori</th>
                <th style="padding: 10px;">Merek</th>
                <th style="padding: 10px;">Satuan</th>
                <th style="padding: 10px;">Harga Jual</th>
                <th style="padding: 10px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="7" style="padding: 20px; text-align: center; color: #888;">Belum ada produk yang ditambahkan.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $p): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;"><code><?= htmlspecialchars((string)($p['sku'] ?? '-')) ?></code></td>
                        <td style="padding: 10px;">
                            <strong><?= htmlspecialchars((string)$p['name']) ?></strong>
                            <?php if (($p['type'] ?? 'single') !== 'single'): ?>
                                <span class="badge" style="background: #fff3e0; color: #e65100;">Variable</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px;"><?= htmlspecialchars((string)($p['category_name'] ?? '-')) ?></td>
                        <td style="padding: 10px;"><?= htmlspecialchars((string)($p['brand_name'] ?? '-')) ?></td>
                        <td style="padding: 10px;"><?= htmlspecialchars((string)($p['unit_name'] ?? '-')) ?></td>
                        <td style="padding: 10px;"><?= htmlspecialchars(Money::formatCents((int)($p['sell_price_inc_tax_cents'] ?? 0))) ?></td>
                        <td style="padding: 10px;">
                            <button class="btn" style="padding: 4px 8px; font-size: 12px;">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

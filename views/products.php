<?php
/** @var array<int, array<string, mixed>> $products */
/** @var string $title */

use Siappos\Shared\Money;

$title = $title ?? 'Daftar Produk & Variasi';
$products = $products ?? [];
?>
<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h2 style="margin: 0;">Katalog Produk</h2>
        <button class="btn btn-primary">Tambah Produk Baru</button>
    </div>

    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>SKU Fisik</th>
                    <th>Nama Produk / Variasi</th>
                    <th>Kategori</th>
                    <th>Merek</th>
                    <th>Satuan</th>
                    <th>Harga Jual</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state" style="text-align: center;">Belum ada produk yang ditambahkan.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><code><?= htmlspecialchars((string)($p['sku'] ?? '-')) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars((string)$p['name']) ?></strong>
                                <?php if (($p['type'] ?? 'single') !== 'single'): ?>
                                    <span class="badge" style="background: #fff3e0; color: #e65100; border-color: #e65100;">Variable</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($p['category_name'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)($p['brand_name'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)($p['unit_name'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars(Money::formatCents((int)($p['sell_price_inc_tax_cents'] ?? 0))) ?></td>
                            <td>
                                <button class="btn" style="padding: 4px 8px; font-size: 12px;">Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

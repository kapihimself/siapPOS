<?php
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, array<string, mixed>> $brands */
/** @var array<int, array<string, mixed>> $units */
/** @var string $title */

$title = $title ?? 'Taksonomi Produk';
$categories = $categories ?? [];
$brands = $brands ?? [];
$units = $units ?? [];
?>
<div class="panel" style="margin-bottom: 20px;">
    <h2>Pengaturan Kategori, Merek & Satuan</h2>
    <p class="muted">Gunakan halaman ini untuk mengatur klasifikasi dan atribut produk Anda.</p>
</div>

<div class="grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
    <!-- Kategori -->
    <div class="panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;">Kategori</h3>
            <button class="btn btn-primary" style="padding: 4px 8px; font-size: 12px;">Tambah</button>
        </div>
        <ul class="list compact" style="list-style: none; padding: 0; margin: 0;">
            <?php foreach ($categories as $cat): ?>
                <li style="padding: 8px 0; border-bottom: 1px solid #eee;"><?= htmlspecialchars((string)$cat['name']) ?></li>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
                <li style="color: #888;">Belum ada kategori</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Merek -->
    <div class="panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;">Merek</h3>
            <button class="btn btn-primary" style="padding: 4px 8px; font-size: 12px;">Tambah</button>
        </div>
        <ul class="list compact" style="list-style: none; padding: 0; margin: 0;">
            <?php foreach ($brands as $brand): ?>
                <li style="padding: 8px 0; border-bottom: 1px solid #eee;"><?= htmlspecialchars((string)$brand['name']) ?></li>
            <?php endforeach; ?>
            <?php if (empty($brands)): ?>
                <li style="color: #888;">Belum ada merek</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Satuan -->
    <div class="panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;">Satuan</h3>
            <button class="btn btn-primary" style="padding: 4px 8px; font-size: 12px;">Tambah</button>
        </div>
        <ul class="list compact" style="list-style: none; padding: 0; margin: 0;">
            <?php foreach ($units as $unit): ?>
                <li style="padding: 8px 0; border-bottom: 1px solid #eee;"><?= htmlspecialchars((string)$unit['name']) ?> (<?= htmlspecialchars((string)$unit['short_name']) ?>)</li>
            <?php endforeach; ?>
            <?php if (empty($units)): ?>
                <li style="color: #888;">Belum ada satuan</li>
            <?php endif; ?>
        </ul>
    </div>
</div>

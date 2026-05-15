<?php
/** @var array<int, array<string, mixed>> $sets */
/** @var \Siappos\Domain\Restaurant\ResModifierRepository $modifierRepo */
/** @var string $title */

use Siappos\Shared\Csrf;

$formatRupiah = static fn (int $cents): string => 'Rp ' . number_format($cents / 100, 0, ',', '.');
?>
<div class="grid">
    <div class="col-8">
        <div class="panel">
            <h2>Grup Modifier Aktif</h2>
            <?php if (empty($sets)): ?>
                <p class="text-center muted" style="padding: 20px;">Belum ada modifier yang dibuat.</p>
            <?php else: ?>
                <?php foreach ($sets as $set):
                    $modifiers = $modifierRepo->getModifiersBySet((int)$set['id']);
                ?>
                    <div style="border: 1px solid var(--line); border-radius: 4px; padding: 15px; margin-bottom: 15px;">
                        <h3 style="margin-top: 0; display: flex; justify-content: space-between;">
                            <?= htmlspecialchars($set['name']) ?>
                        </h3>
                        <table class="table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Pilihan Tambahan (Modifier)</th>
                                    <th style="text-align: right;">Harga Tambahan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modifiers as $mod): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($mod['name']) ?></td>
                                        <td style="text-align: right; color: #2ecc71;">+ <?= $formatRupiah((int) $mod['price_cents']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($modifiers)): ?>
                                    <tr><td colspan="2" class="muted text-center">Belum ada pilihan di grup ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- Form Tambah Opsi ke Grup Ini -->
                        <form action="/?page=modifiers/item-store" method="POST" style="margin-top: 10px; display: flex; gap: 10px;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
                            <input type="hidden" name="set_id" value="<?= $set['id'] ?>">
                            <input type="text" name="name" placeholder="Nama Opsi (mis: Ekstra Keju)" required style="flex: 2; padding: 6px;">
                            <input type="number" name="price" placeholder="Harga (mis: 5000)" required style="flex: 1; padding: 6px;">
                            <button type="submit" class="btn btn-primary" style="padding: 6px 12px;">Tambah Opsi</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-4">
        <div class="panel panel-accent">
            <h3>Buat Grup Modifier Baru</h3>
            <p class="muted" style="font-size: 0.9em;">Grup Modifier digunakan untuk mengelompokkan pilihan, misal "Topping Minuman" atau "Tingkat Kepedasan".</p>
            <form action="/?page=modifiers/set-store" method="POST">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <div style="margin-bottom: 10px;">
                    <label>Nama Grup</label>
                    <input type="text" name="name" placeholder="Misal: Topping Pizza" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Buat Grup</button>
            </form>
        </div>
    </div>
</div>

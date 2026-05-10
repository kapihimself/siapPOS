<?php
/** @var array<int, array<string, mixed>> $brands */
/** @var string $title */
use Siappos\Shared\Csrf;
?>
<div class="grid">
    <div class="col-8">
        <div class="panel">
            <h2>Daftar Brand</h2>
            <table class="table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--line); text-align: left;">
                        <th style="padding: 10px;">Nama Brand</th>
                        <th style="padding: 10px;">Dibuat Pada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($brands)): ?>
                        <tr><td colspan="2" style="text-align: center; padding: 20px;">Belum ada brand.</td></tr>
                    <?php else: ?>
                        <?php foreach ($brands as $brand): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;"><?= htmlspecialchars((string) $brand['name']) ?></td>
                                <td style="padding: 10px;"><?= htmlspecialchars((string) $brand['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-4">
        <div class="panel panel-accent">
            <h3>Tambah Brand</h3>
            <form action="/?page=brands/store" method="POST">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <div style="margin-bottom: 10px;">
                    <label for="name">Nama Brand *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan</button>
            </form>
        </div>
    </div>
</div>

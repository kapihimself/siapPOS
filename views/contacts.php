<?php
/** @var array<int, array<string, mixed>> $contacts */
/** @var string $title */
use Siappos\Shared\Csrf;
?>
<div class="grid">
    <div class="col-8">
        <div class="panel">
            <h2>Daftar Kontak (Pelanggan & Pemasok)</h2>
            <table class="table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--line); text-align: left;">
                        <th style="padding: 10px;">Tipe</th>
                        <th style="padding: 10px;">Nama</th>
                        <th style="padding: 10px;">Kontak</th>
                        <th style="padding: 10px;">Dibuat Pada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contacts)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 20px;">Belum ada kontak.</td></tr>
                    <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;">
                                    <?php if ($contact['type'] === 'customer'): ?>
                                        <span style="background: #e3f2fd; color: #1976d2; padding: 2px 6px; border-radius: 4px; font-size: 0.85em;">Pelanggan</span>
                                    <?php else: ?>
                                        <span style="background: #fbe9e7; color: #d84315; padding: 2px 6px; border-radius: 4px; font-size: 0.85em;">Pemasok</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px; font-weight: bold;"><?= htmlspecialchars((string) $contact['name']) ?></td>
                                <td style="padding: 10px; font-size: 0.9em;">
                                    <?= htmlspecialchars((string) ($contact['phone'] ?? '-')) ?><br>
                                    <span style="color: #666;"><?= htmlspecialchars((string) ($contact['email'] ?? '')) ?></span>
                                </td>
                                <td style="padding: 10px; font-size: 0.9em;"><?= htmlspecialchars((string) substr($contact['created_at'], 0, 10)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-4">
        <div class="panel panel-accent">
            <h3>Tambah Kontak Baru</h3>
            <form action="/?page=contacts/store" method="POST">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

                <div style="margin-bottom: 10px;">
                    <label for="type">Tipe Kontak *</label>
                    <select id="type" name="type" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="customer">Pelanggan</option>
                        <option value="supplier">Pemasok</option>
                    </select>
                </div>

                <div style="margin-bottom: 10px;">
                    <label for="name">Nama Perusahaan/Individu *</label>
                    <input type="text" id="name" name="name" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>

                <div style="margin-bottom: 10px;">
                    <label for="phone">Nomor Telepon</label>
                    <input type="text" id="phone" name="phone" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>

                <div style="margin-bottom: 10px;">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label for="address">Alamat</label>
                    <textarea id="address" name="address" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Kontak</button>
            </form>
        </div>
    </div>
</div>

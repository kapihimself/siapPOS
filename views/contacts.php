<?php
/** @var array<int, array<string, mixed>> $contacts */
/** @var string $title */

$title = $title ?? 'Manajemen Kontak';
$contacts = $contacts ?? [];
?>
<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Pelanggan & Pemasok</h2>
        <button class="btn btn-primary">Tambah Kontak Baru</button>
    </div>

    <table class="table" style="width: 100%; text-align: left; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid #eee;">
                <th style="padding: 10px;">ID</th>
                <th style="padding: 10px;">Tipe</th>
                <th style="padding: 10px;">Nama</th>
                <th style="padding: 10px;">Email</th>
                <th style="padding: 10px;">Telepon</th>
                <th style="padding: 10px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($contacts)): ?>
                <tr>
                    <td colspan="6" style="padding: 20px; text-align: center; color: #888;">Belum ada kontak yang ditambahkan.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($contacts as $contact): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;"><?= htmlspecialchars((string)$contact['id']) ?></td>
                        <td style="padding: 10px;">
                            <?php if ($contact['type'] === 'customer'): ?>
                                <span class="badge" style="background: #e3f2fd; color: #1976d2;">Pelanggan</span>
                            <?php elseif ($contact['type'] === 'supplier'): ?>
                                <span class="badge" style="background: #fbe9e7; color: #d84315;">Pemasok</span>
                            <?php else: ?>
                                <span class="badge" style="background: #f3e5f5; color: #7b1fa2;">Keduanya</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px;"><strong><?= htmlspecialchars((string)$contact['name']) ?></strong></td>
                        <td style="padding: 10px;"><?= htmlspecialchars((string)$contact['email']) ?></td>
                        <td style="padding: 10px;"><?= htmlspecialchars((string)$contact['phone']) ?></td>
                        <td style="padding: 10px;">
                            <button class="btn" style="padding: 4px 8px; font-size: 12px;">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

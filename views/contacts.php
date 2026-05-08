<?php
/** @var array<int, array<string, mixed>> $contacts */
/** @var string $title */

$title = $title ?? 'Manajemen Kontak';
$contacts = $contacts ?? [];
?>
<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h2 style="margin: 0;">Pelanggan & Pemasok</h2>
        <button class="btn btn-primary">Tambah Kontak Baru</button>
    </div>

    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipe</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Telepon</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contacts)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state" style="text-align: center;">Belum ada kontak yang ditambahkan.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$contact['id']) ?></td>
                            <td>
                                <?php if ($contact['type'] === 'customer'): ?>
                                    <span class="badge" style="background: #e3f2fd; color: #1976d2; border-color: #1976d2;">Pelanggan</span>
                                <?php elseif ($contact['type'] === 'supplier'): ?>
                                    <span class="badge" style="background: #fbe9e7; color: #d84315; border-color: #d84315;">Pemasok</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #f3e5f5; color: #7b1fa2; border-color: #7b1fa2;">Keduanya</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars((string)$contact['name']) ?></strong></td>
                            <td><?= htmlspecialchars((string)$contact['email']) ?></td>
                            <td><?= htmlspecialchars((string)$contact['phone']) ?></td>
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

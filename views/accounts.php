<?php
/** @var array<int, array<string, mixed>> $accounts */
/** @var string $title */

use Siappos\Shared\Csrf;

$formatRupiah = static fn (int $cents): string => 'Rp ' . number_format($cents / 100, 0, ',', '.');
?>
<div class="panel">
    <div class="progress-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0;">Bagan Akun (Chart of Accounts)</h3>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('add-account-modal').style.display='block'">+ Tambah Akun</button>
    </div>

    <table class="table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
        <thead>
            <tr style="border-bottom: 2px solid var(--line); text-align: left;">
                <th>Nomor Akun</th>
                <th>Nama Akun</th>
                <th>Tipe</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accounts as $account): ?>
                <tr style="border-bottom: 1px solid var(--line);">
                    <td style="padding: 10px 0;"><?= htmlspecialchars((string) $account['account_number']) ?></td>
                    <td style="padding: 10px 0; font-weight: bold;"><?= htmlspecialchars((string) $account['name']) ?></td>
                    <td style="padding: 10px 0;">
                        <?php
                        $typeMap = [
                            'asset' => 'Aset',
                            'liability' => 'Kewajiban',
                            'equity' => 'Ekuitas',
                            'revenue' => 'Pendapatan',
                            'expense' => 'Beban',
                        ];
                        echo htmlspecialchars($typeMap[(string) $account['type']] ?? (string) $account['type']);
                        ?>
                    </td>
                    <td style="padding: 10px 0;">
                        <a href="/?page=accounts/ledger&id=<?= (int) $account['id'] ?>" class="btn">Buku Besar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($accounts === []): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px;">Belum ada akun akuntansi.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="add-account-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border-radius:4px; width:400px; max-width:90%;">
        <h3 style="margin-top:0;">Tambah Akun Baru</h3>
        <form action="/?page=accounts/store" method="POST">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

            <div style="margin-bottom: 15px;">
                <label for="account_number" style="display:block; margin-bottom:5px;">Nomor Akun</label>
                <input type="text" id="account_number" name="account_number" required style="width:100%; box-sizing:border-box; padding:8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="name" style="display:block; margin-bottom:5px;">Nama Akun</label>
                <input type="text" id="name" name="name" required style="width:100%; box-sizing:border-box; padding:8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="type" style="display:block; margin-bottom:5px;">Tipe Akun</label>
                <select id="type" name="type" required style="width:100%; box-sizing:border-box; padding:8px;">
                    <option value="asset">Aset</option>
                    <option value="liability">Kewajiban</option>
                    <option value="equity">Ekuitas</option>
                    <option value="revenue">Pendapatan</option>
                    <option value="expense">Beban</option>
                </select>
            </div>

            <div style="text-align:right;">
                <button type="button" class="btn" onclick="document.getElementById('add-account-modal').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

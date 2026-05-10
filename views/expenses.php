<?php
/** @var array<int, array<string, mixed>> $accounts */
/** @var array<int, array<string, mixed>> $expenses */
/** @var string $title */
use Siappos\Shared\Csrf;

$formatRupiah = function (int $cents): string {
    return 'Rp ' . number_format($cents / 100, 0, ',', '.');
};

$expenseAccounts = array_filter($accounts, fn($a) => $a['type'] === 'expense');
?>
<div class="grid">
    <div class="col-8">
        <div class="panel">
            <h2>Riwayat Pengeluaran (Beban Operasional)</h2>
            <table class="table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--line); text-align: left;">
                        <th style="padding: 10px;">Tanggal</th>
                        <th style="padding: 10px;">No. Referensi</th>
                        <th style="padding: 10px;">Metode Pembayaran</th>
                        <th style="padding: 10px; text-align: right;">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 20px;">Belum ada pengeluaran dicatat.</td></tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $expense): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;"><?= htmlspecialchars((string) substr($expense['created_at'], 0, 16)) ?></td>
                                <td style="padding: 10px; font-weight: bold;"><?= htmlspecialchars((string) $expense['transaction_number']) ?></td>
                                <td style="padding: 10px;"><?= htmlspecialchars(ucfirst((string) $expense['payment_method'])) ?></td>
                                <td style="padding: 10px; text-align: right; color: #e74c3c;">
                                    <?= $formatRupiah((int) $expense['total_cents']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-4">
        <div class="panel panel-accent">
            <h3>Catat Pengeluaran Baru</h3>
            <form action="/?page=expenses/store" method="POST">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

                <div style="margin-bottom: 10px;">
                    <label for="account_id">Kategori Beban (Akun) *</label>
                    <select id="account_id" name="account_id" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <?php foreach ($expenseAccounts as $acc): ?>
                            <option value="<?= (int) $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?> (<?= htmlspecialchars($acc['account_number']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 10px;">
                    <label for="amount">Jumlah (Rp) *</label>
                    <input type="number" id="amount" name="amount" min="1" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>

                <div style="margin-bottom: 10px;">
                    <label for="payment_method">Metode Pembayaran *</label>
                    <select id="payment_method" name="payment_method" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="cash">Tunai (Kas)</option>
                        <option value="bank_transfer">Transfer Bank</option>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label for="description">Keterangan / Catatan</label>
                    <textarea id="description" name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Pengeluaran</button>
            </form>
            <div style="margin-top: 15px; font-size: 0.9em; text-align: center;">
                <a href="/?page=accounts">Atur Kategori Beban di Akuntansi</a>
            </div>
        </div>
    </div>
</div>

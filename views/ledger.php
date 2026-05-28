<?php
/** @var array<int, array<string, mixed>> $ledger */
/** @var array<string, mixed> $account */
/** @var string $title */

$formatRupiah = static fn (int $cents): string => 'Rp ' . number_format($cents / 100, 0, ',', '.');
?>
<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0;">Buku Besar: <?= htmlspecialchars((string) $account['account_number']) ?> - <?= htmlspecialchars((string) $account['name']) ?></h3>
        <a href="/?page=accounts" class="btn">Kembali</a>
    </div>

    <table class="table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
        <thead>
            <tr style="border-bottom: 2px solid var(--line); text-align: left;">
                <th>Tanggal</th>
                <th>Referensi</th>
                <th>Deskripsi</th>
                <th>Debit</th>
                <th>Kredit</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($ledger as $entry):
                $isDebit = $entry['type'] === 'debit';
                $amount = (int) $entry['amount_cents'];
                if ($isDebit) $totalDebit += $amount; else $totalCredit += $amount;
            ?>
                <tr style="border-bottom: 1px solid var(--line);">
                    <td style="padding: 10px 0;"><?= htmlspecialchars(substr((string) $entry['created_at'], 0, 16)) ?></td>
                    <td style="padding: 10px 0;"><?= htmlspecialchars((string) ($entry['transaction_number'] ?? '-')) ?></td>
                    <td style="padding: 10px 0;"><?= htmlspecialchars((string) $entry['description']) ?></td>
                    <td style="padding: 10px 0;"><?= $isDebit ? $formatRupiah($amount) : '' ?></td>
                    <td style="padding: 10px 0;"><?= !$isDebit ? $formatRupiah($amount) : '' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($ledger === []): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">Belum ada transaksi di akun ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; border-top: 2px solid var(--line);">
                <td colspan="3" style="text-align: right; padding: 10px 0;">Total:</td>
                <td style="padding: 10px 0;"><?= $formatRupiah($totalDebit) ?></td>
                <td style="padding: 10px 0;"><?= $formatRupiah($totalCredit) ?></td>
            </tr>
        </tfoot>
    </table>
</div>

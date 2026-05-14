<?php
/**
 * @var string $title
 * @var array $contact
 * @var array $ledger
 */
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - SiapPOS</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<div class="container">
    <?php require __DIR__ . '/partials/header.php'; ?>
    <main>
        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h1 style="margin: 0;">Buku Besar: <?= htmlspecialchars($contact['name']) ?></h1>
                    <span class="muted"><?= htmlspecialchars(ucfirst($contact['type'])) ?></span>
                </div>
                <a href="/?page=contacts" class="btn">Kembali ke Kontak</a>
            </div>

            <table class="table">
                <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No. Transaksi</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Telah Dibayar</th>
                    <th>Sisa Tagihan (Due)</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $totalDue = 0;
                foreach ($ledger as $entry):
                    $dueCents = $entry['total_cents'] - $entry['paid_cents'];
                    if ($dueCents > 0) $totalDue += $dueCents;
                ?>
                    <tr>
                        <td><?= htmlspecialchars((new DateTime($entry['created_at']))->format('d M Y')) ?></td>
                        <td><?= htmlspecialchars($entry['transaction_number']) ?></td>
                        <td><?= htmlspecialchars($entry['type']) ?></td>
                        <td><?= htmlspecialchars($entry['payment_status'] ?? $entry['status']) ?></td>
                        <td>Rp <?= number_format($entry['total_cents'] / 100, 0, ',', '.') ?></td>
                        <td>Rp <?= number_format($entry['paid_cents'] / 100, 0, ',', '.') ?></td>
                        <td style="color: <?= $dueCents > 0 ? 'red' : 'inherit' ?>">Rp <?= number_format($dueCents / 100, 0, ',', '.') ?></td>
                        <td>
                            <?php if ($dueCents > 0): ?>
                            <form action="/?page=contacts/pay" method="POST" style="display:inline-block;">
                                <input type="hidden" name="_csrf" value="<?= \Siappos\Shared\Csrf::token() ?>">
                                <input type="hidden" name="transaction_id" value="<?= $entry['id'] ?>">
                                <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
                                <input type="number" name="amount" value="<?= $dueCents / 100 ?>" required style="width: 100px; padding: 4px; font-size: 14px;">
                                <button type="submit" class="btn btn-primary" style="padding: 4px 8px; font-size: 14px;">Bayar Cicilan</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($ledger)): ?>
                    <tr>
                        <td colspan="8" class="text-center muted">Tidak ada riwayat transaksi.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
                <?php if ($totalDue > 0): ?>
                <tfoot>
                    <tr>
                        <td colspan="6" style="text-align: right; font-weight: bold;">Total Sisa Tagihan (Hutang/Piutang):</td>
                        <td style="color: red; font-weight: bold;">Rp <?= number_format($totalDue / 100, 0, ',', '.') ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </main>
</div>
<script src="/assets/app.js" defer></script>
</body>
</html>

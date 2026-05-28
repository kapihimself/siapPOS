<?php
/**
 * @var string $title
 * @var array $suspended
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
                <h1 style="margin: 0;"><?= htmlspecialchars($title) ?></h1>
                <a href="/?page=pos" class="btn">Kembali ke POS</a>
            </div>

            <table class="table">
                <thead>
                <tr>
                    <th>Waktu</th>
                    <th>No. Ref</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($suspended as $sell): ?>
                    <tr>
                        <td><?= htmlspecialchars((new DateTime($sell['created_at']))->format('d M Y H:i')) ?></td>
                        <td><?= htmlspecialchars($sell['transaction_number']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($sell['status'])) ?></td>
                        <td>
                            <form action="/?page=sells/resume" method="POST" style="display:inline-block;">
                                <input type="hidden" name="_csrf" value="<?= \Siappos\Shared\Csrf::token() ?>">
                                <input type="hidden" name="transaction_id" value="<?= $sell['id'] ?>">
                                <button type="submit" class="btn btn-primary" style="padding: 4px 8px; font-size: 14px;">Lanjutkan Transaksi</button>
                            </form>
                            <form action="/?page=sells/delete" method="POST" style="display:inline-block; margin-left: 8px;" onsubmit="return confirm('Hapus transaksi ini?');">
                                <input type="hidden" name="_csrf" value="<?= \Siappos\Shared\Csrf::token() ?>">
                                <input type="hidden" name="transaction_id" value="<?= $sell['id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 14px;">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($suspended)): ?>
                    <tr>
                        <td colspan="4" class="text-center muted">Tidak ada transaksi draft atau disuspend.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script src="/assets/app.js" defer></script>
</body>
</html>

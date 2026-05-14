<?php
/**
 * @var string $title
 * @var array $tables
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
            </div>

            <form action="/?page=tables/store" method="POST" style="margin-bottom: 24px;">
                <input type="hidden" name="_csrf" value="<?= \Siappos\Shared\Csrf::token() ?>">
                <div class="form-group" style="display: flex; gap: 8px;">
                    <input type="text" name="name" class="form-control" placeholder="Nama Meja (mis. Meja 01)" required style="flex: 1;">
                    <button type="submit" class="btn btn-primary">Tambah Meja</button>
                </div>
            </form>

            <table class="table">
                <thead>
                <tr>
                    <th>Nama Meja</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($tables as $table): ?>
                    <tr>
                        <td><?= htmlspecialchars($table['name']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($tables)): ?>
                    <tr>
                        <td class="text-center muted">Belum ada meja.</td>
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

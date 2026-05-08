<?php
/** @var string $content */
/** @var string $title */

use Siappos\App\Auth;
use Siappos\Shared\Flash;

$title = $title ?? 'SiapPOS';
$flashSuccess = Flash::consumeSuccess();
$flashError = Flash::consumeError();
$user = Auth::user();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SiapPOS - POS modular untuk F&B, service, retail, dan kelontong/bangunan.">
    <title><?= htmlspecialchars($title) ?> - SiapPOS</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<div class="container">
    <header class="header" style="flex-direction: column; align-items: stretch; gap: 15px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div class="brand">SIAPPOS</div>
                <div class="muted">Kasir modern yang cepat dipakai, rapi datanya, dan siap tumbuh.</div>
            </div>
            <?php if (is_array($user)): ?>
                <div style="text-align: right;">
                    <span class="badge" style="margin-bottom: 5px;"><?= htmlspecialchars((string) $user['full_name']) ?> (<?= htmlspecialchars((string) $user['role']) ?>)</span>
                    <br>
                    <a href="/?page=logout" class="btn btn-danger" style="display: inline-block; padding: 4px 8px; font-size: 12px; color: #fff;">Logout</a>
                </div>
            <?php else: ?>
                <div>
                    <a href="/?page=login" class="btn btn-primary">Masuk</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (is_array($user)): ?>
            <nav class="nav" aria-label="Navigasi utama" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 8px;">
                <a href="/?page=dashboard" style="text-align: center;">Dashboard</a>
                <a href="/?page=contacts" style="text-align: center;">Kontak</a>
                <a href="/?page=products" style="text-align: center;">Produk</a>
                <a href="/?page=taxonomy" style="text-align: center;">Taksonomi</a>
                <a href="/?page=purchases" style="text-align: center;">Pembelian</a>
                <a href="/?page=stock-adjustments" style="text-align: center;">Penyesuaian Stok</a>
                <?php if (in_array((string) $user['role'], ['admin', 'manager'], true)): ?>
                    <a href="/?page=onboarding" style="text-align: center;">Setup Bisnis</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </header>

    <?php if ($flashSuccess !== null): ?>
        <div class="flash success" role="status"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>

    <?php if ($flashError !== null): ?>
        <div class="flash error" role="alert"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <main>
        <?= $content ?>
    </main>
</div>
<script src="/assets/app.js" defer></script>
</body>
</html>

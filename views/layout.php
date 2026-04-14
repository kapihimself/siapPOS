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
    <header class="header">
        <div>
            <div class="brand">SIAPPOS</div>
            <div class="muted">Kasir modern yang cepat dipakai, rapi datanya, dan siap tumbuh.</div>
        </div>
        <nav class="nav" aria-label="Navigasi utama">
            <?php if (is_array($user)): ?>
                <span class="badge"><?= htmlspecialchars((string) $user['full_name']) ?> (<?= htmlspecialchars((string) $user['role']) ?>)</span>
                <a href="/?page=dashboard">Dashboard</a>
                <?php if (in_array((string) $user['role'], ['admin', 'manager'], true)): ?>
                    <a href="/?page=onboarding">Setup Bisnis</a>
                <?php endif; ?>
                <a href="/?page=logout" class="btn btn-danger">Logout</a>
            <?php else: ?>
                <a href="/?page=login" class="btn btn-primary">Masuk</a>
            <?php endif; ?>
        </nav>
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

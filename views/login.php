<?php
/** @var string $title */
/** @var string $defaultUsername */

use Siappos\Shared\Csrf;

$title = 'Masuk';
$defaultUsername = $defaultUsername ?? '';
?>
<div class="grid">
    <section class="col-7 panel">
        <h2>Masuk ke akun Anda</h2>
        <p class="muted">Gunakan akun staf untuk mulai operasional. Semua aktivitas penting akan tercatat.</p>

        <form method="post" action="/?page=login" data-loading-form>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

            <label for="username">Username</label>
            <input id="username" type="text" name="username" required autocomplete="username" value="<?= htmlspecialchars($defaultUsername) ?>" placeholder="contoh: owner">

            <label for="pin">PIN</label>
            <input id="pin" type="password" name="pin" required autocomplete="current-password" inputmode="numeric" pattern="\d{4,6}" minlength="4" maxlength="6" placeholder="4-6 digit" data-pin-input>

            <button class="btn btn-primary" type="submit" data-submit-label="Memproses login...">Masuk Sekarang</button>
        </form>

        <p class="muted">
            Belum punya data operasional?
            Gunakan tombol <strong>Try Demo</strong> untuk eksplorasi instan.
        </p>
    </section>

    <aside class="col-5 panel panel-accent">
        <h3>Try Demo 1 Klik</h3>
        <p class="muted">Masuk sebagai kasir demo, setup otomatis, langsung lihat dashboard.</p>

        <form method="post" action="/?page=try-demo" data-loading-form>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <button class="btn btn-secondary" type="submit" data-submit-label="Menyiapkan mode demo...">Try Demo</button>
        </form>

        <hr>
        <p class="muted">Akun lokal pengembangan:</p>
        <ul class="list compact">
            <li><code>owner / 1111</code></li>
            <li><code>manager / 2222</code></li>
            <li><code>cashier / 3333</code></li>
        </ul>
    </aside>
</div>

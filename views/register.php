<?php
/** @var string $title */

use Siappos\Shared\Csrf;

$title = $title ?? 'Daftar Tenant Baru';
?>
<div class="auth-panel">
    <h2 style="margin-top: 0;">Daftar Bisnis Baru</h2>
    <p class="muted">Buat akun untuk mengelola cabang-cabang bisnis Anda.</p>

    <form action="/?page=register" method="POST" data-loading-form>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

        <label for="business_name">Nama Bisnis</label>
        <input type="text" id="business_name" name="business_name" required placeholder="Contoh: Kopi Kenangan">

        <label for="username">Username (Untuk Login)</label>
        <input type="text" id="username" name="username" required placeholder="admin_kopi">

        <label for="full_name">Nama Lengkap Anda</label>
        <input type="text" id="full_name" name="full_name" required placeholder="Budi Santoso">

        <label for="pin">PIN Akses (4-6 Angka)</label>
        <input type="password" id="pin" name="pin" required placeholder="1234" pattern="\d{4,6}" minlength="4" maxlength="6" title="Masukkan 4-6 angka">

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;" data-submit-label="Mendaftarkan...">Daftar Sekarang</button>

        <div style="margin-top: 15px; text-align: center;">
            <small class="muted">Sudah punya akun? <a href="/?page=login" style="text-decoration: underline;">Masuk di sini</a></small>
        </div>
    </form>
</div>

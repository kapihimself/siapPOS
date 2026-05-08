<?php
/** @var string $title */

use Siappos\Shared\Csrf;

$title = $title ?? 'Daftar Tenant Baru';
?>
<div class="auth-panel" style="max-width: 450px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h2 style="margin-top: 0;">Daftar Bisnis Baru</h2>
    <p class="muted">Buat akun untuk mengelola cabang-cabang bisnis Anda.</p>

    <form action="/?page=register" method="POST">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

        <div class="form-group" style="margin-bottom: 15px;">
            <label for="business_name">Nama Bisnis</label>
            <input type="text" id="business_name" name="business_name" required class="form-control" placeholder="Contoh: Kopi Kenangan" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label for="username">Username (Untuk Login)</label>
            <input type="text" id="username" name="username" required class="form-control" placeholder="admin_kopi" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label for="full_name">Nama Lengkap Anda</label>
            <input type="text" id="full_name" name="full_name" required class="form-control" placeholder="Budi Santoso" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label for="pin">PIN Akses (4-6 Angka)</label>
            <input type="password" id="pin" name="pin" required class="form-control" placeholder="1234" pattern="\d{4,6}" title="Masukkan 4-6 angka" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; font-weight: bold; background: #003B57; color: white; border: none; border-radius: 4px; cursor: pointer;">Daftar Sekarang</button>

        <div style="margin-top: 15px; text-align: center;">
            <small>Sudah punya akun? <a href="/?page=login">Masuk di sini</a></small>
        </div>
    </form>
</div>

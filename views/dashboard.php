<?php
/** @var array<string, mixed> $settings */
/** @var array<string, mixed> $kpi */
/** @var \Siappos\Domain\Settings\BusinessTemplate $template */
/** @var list<string> $modules */
/** @var list<string> $firstWeekChecklist */
/** @var array<int, array<string, mixed>> $setupChecklist */
/** @var int $setupProgressPercent */
/** @var string $roleCopy */

use Siappos\App\Auth;
use Siappos\Shared\Money;

$title = 'Dashboard';
$settings = $settings ?? [];
$kpi = $kpi ?? [];
$modules = $modules ?? [];
$template = $template ?? null;
$firstWeekChecklist = $firstWeekChecklist ?? [];
$setupChecklist = $setupChecklist ?? [];
$setupProgressPercent = (int) ($setupProgressPercent ?? 0);
$roleCopy = $roleCopy ?? '';
$userRole = Auth::role();
$noTransactionYet = ((int) ($kpi['orders'] ?? 0)) === 0;
?>
<div class="panel">
    <h2><?= htmlspecialchars((string) ($settings['business_name'] ?? 'SiapPOS')) ?></h2>
    <p class="muted">
        Outlet: <strong><?= htmlspecialchars((string) ($settings['outlet_name'] ?? 'Outlet Utama')) ?></strong>
        | Template aktif: <span class="badge"><?= htmlspecialchars($template?->label() ?? 'Retail') ?></span>
    </p>
    <p><?= htmlspecialchars($roleCopy) ?></p>
</div>

<div class="grid">
    <div class="col-3">
        <div class="kpi">
            Pengguna Aktif
            <strong><?= (int) ($kpi['users'] ?? 0) ?></strong>
        </div>
    </div>
    <div class="col-3">
        <div class="kpi">
            Produk
            <strong><?= (int) ($kpi['products'] ?? 0) ?></strong>
        </div>
    </div>
    <div class="col-3">
        <div class="kpi">
            Transaksi
            <strong><?= (int) ($kpi['orders'] ?? 0) ?></strong>
        </div>
    </div>
    <div class="col-3">
        <div class="kpi">
            Revenue
            <strong><?= htmlspecialchars(Money::formatCents((int) ($kpi['revenue_cents'] ?? 0))) ?></strong>
        </div>
    </div>

    <div class="col-7">
        <div class="panel">
            <div class="progress-header">
                <h3>Kesiapan Operasional</h3>
                <span class="progress-chip"><?= $setupProgressPercent ?>%</span>
            </div>
            <div class="progress-track" role="progressbar" aria-valuenow="<?= $setupProgressPercent ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-fill" style="width: <?= $setupProgressPercent ?>%"></div>
            </div>

            <ul class="list compact checklist">
                <?php foreach ($setupChecklist as $item): ?>
                    <li class="<?= !empty($item['done']) ? 'done' : '' ?>">
                        <?= !empty($item['done']) ? '[x]' : '[ ]' ?>
                        <?= htmlspecialchars((string) ($item['label'] ?? '')) ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($noTransactionYet): ?>
                <div class="empty-state">
                    <strong>Belum ada transaksi.</strong>
                    <p class="muted">Langkah selanjutnya: lanjut Sesi 2 untuk terminal POS dan checkout.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-5">
        <div class="panel">
            <h3>Modul Aktif Template <?= htmlspecialchars($template?->label() ?? 'Retail') ?></h3>
            <ul class="list compact">
                <?php foreach ($modules as $module): ?>
                    <li><?= htmlspecialchars($module) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="col-6">
        <div class="panel">
            <h3>Prioritas Minggu Pertama</h3>
            <ul class="list compact">
                <?php foreach ($firstWeekChecklist as $task): ?>
                    <li><?= htmlspecialchars($task) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="col-6">
        <div class="panel">
            <h3>Navigasi Cepat</h3>
            <div class="quick-actions">
                <?php if (in_array($userRole, ['admin', 'manager'], true)): ?>
                    <a class="btn" href="/?page=onboarding">Perbarui Setup Bisnis</a>
                <?php endif; ?>
                <button class="btn" type="button" disabled>POS Terminal (Sesi 2)</button>
                <button class="btn" type="button" disabled>Produk + Stok (Sesi 3)</button>
                <button class="btn" type="button" disabled>Invoicing (Sesi 4)</button>
            </div>
        </div>
    </div>
</div>

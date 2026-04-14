<?php
/** @var array<string, mixed> $settings */
/** @var array<int, \Siappos\Domain\Settings\BusinessTemplate> $templates */
/** @var int $progressPercent */
/** @var array<int, array<string, mixed>> $checklist */

use Siappos\Domain\Settings\BusinessTemplate;
use Siappos\Shared\Csrf;

$title = 'Onboarding Bisnis';
$settings = $settings ?? [];
$templates = $templates ?? BusinessTemplate::all();
$progressPercent = (int) ($progressPercent ?? 0);
$checklist = $checklist ?? [];
$activeTemplate = (string) ($settings['active_template'] ?? 'retail');
?>
<div class="panel">
    <div class="progress-header">
        <div>
            <h2>Setup bisnis Anda</h2>
            <p class="muted">Lengkapi konfigurasi inti agar tim bisa operasional tanpa hambatan.</p>
        </div>
        <div class="progress-chip">Progress <?= $progressPercent ?>%</div>
    </div>
    <div class="progress-track" role="progressbar" aria-valuenow="<?= $progressPercent ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-fill" style="width: <?= $progressPercent ?>%"></div>
    </div>
</div>

<div class="grid">
    <section class="col-8 panel">
        <form method="post" action="/?page=onboarding" data-loading-form>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

            <label for="business_name">Nama Bisnis</label>
            <input id="business_name" type="text" name="business_name" required maxlength="80" placeholder="Contoh: SiapPOS Coffee" value="<?= htmlspecialchars((string) ($settings['business_name'] ?? '')) ?>">

            <label for="outlet_name">Nama Outlet Utama</label>
            <input id="outlet_name" type="text" name="outlet_name" required maxlength="80" placeholder="Contoh: Outlet Sudirman" value="<?= htmlspecialchars((string) ($settings['outlet_name'] ?? 'Outlet Utama')) ?>">

            <label>Pilih Template Bisnis</label>
            <div class="template-grid" data-template-picker>
                <?php foreach ($templates as $template): ?>
                    <?php $checked = $activeTemplate === $template->value; ?>
                    <label class="template-card<?= $checked ? ' selected' : '' ?>" data-template-card>
                        <input type="radio" name="template" value="<?= htmlspecialchars($template->value) ?>" <?= $checked ? 'checked' : '' ?> data-template-input>
                        <strong><?= htmlspecialchars($template->label()) ?></strong>
                        <p class="muted"><?= htmlspecialchars($template->shortDescription()) ?></p>
                    </label>
                <?php endforeach; ?>
            </div>

            <button class="btn btn-primary" type="submit" data-submit-label="Menyimpan setup...">Simpan Setup Bisnis</button>
        </form>
    </section>

    <aside class="col-4 panel">
        <h3>Checklist Go-Live</h3>
        <ul class="list compact checklist">
            <?php foreach ($checklist as $item): ?>
                <li class="<?= !empty($item['done']) ? 'done' : '' ?>">
                    <?= !empty($item['done']) ? '[x]' : '[ ]' ?>
                    <?= htmlspecialchars((string) ($item['label'] ?? '')) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="muted">Target minimal sebelum dipakai tim: progres setup 75%+.</p>
    </aside>
</div>

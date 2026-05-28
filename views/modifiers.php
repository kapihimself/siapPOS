<?php require_once 'partials/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">Manajemen Modifier Makanan</h1>
</div>

<div class="row">
    <!-- Kolom Kiri: Grup Modifier -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Grup Modifier</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSetModal">Tambah Grup</button>
            </div>
            <div class="card-body">
                <?php if (empty($sets)): ?>
                    <p class="text-muted">Belum ada grup modifier.</p>
                <?php else: ?>
                    <div class="accordion" id="modifierAccordion">
                        <?php foreach ($sets as $index => $set): ?>
                            <?php $modifiers = $modifierRepo->getModifiersInSet((int)$set['id']); ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading_<?= $set['id'] ?>">
                                    <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?= $set['id'] ?>">
                                        <?= htmlspecialchars((string)$set['name']) ?>
                                    </button>
                                </h2>
                                <div id="collapse_<?= $set['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#modifierAccordion">
                                    <div class="accordion-body">
                                        <div class="mb-3 text-end">
                                             <button class="btn btn-sm btn-outline-success btn-add-mod" data-set-id="<?= $set['id'] ?>" data-bs-toggle="modal" data-bs-target="#addModifierModal">Tambah Opsi</button>
                                             <button class="btn btn-sm btn-outline-info btn-link-prod" data-set-id="<?= $set['id'] ?>" data-bs-toggle="modal" data-bs-target="#linkProductModal">Tautkan Produk</button>
                                        </div>
                                        <ul class="list-group">
                                            <?php foreach ($modifiers as $mod): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <?= htmlspecialchars((string)$mod['name']) ?>
                                                    <span class="badge bg-secondary rounded-pill"><?= \Siappos\Shared\Money::format((int)$mod['price_cents']) ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                            <?php if(empty($modifiers)): ?>
                                                <li class="list-group-item text-muted">Belum ada opsi</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Grup -->
<div class="modal fade" id="addSetModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/?page=modifiers/set-store" method="POST">
          <div class="modal-header">
            <h5 class="modal-title">Tambah Grup Modifier</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
             <?php \Siappos\Shared\Csrf::token('modifiers'); ?>
             <div class="mb-3">
                 <label>Nama Grup (cth: Topping Pizza)</label>
                 <input type="text" name="name" class="form-control" required>
             </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Tambah Opsi -->
<div class="modal fade" id="addModifierModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/?page=modifiers/item-store" method="POST">
          <div class="modal-header">
            <h5 class="modal-title">Tambah Opsi Modifier</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
             <?php \Siappos\Shared\Csrf::token('modifiers'); ?>
             <input type="hidden" name="set_id" id="mod_set_id" value="">
             <div class="mb-3">
                 <label>Nama Opsi (cth: Keju Extra)</label>
                 <input type="text" name="name" class="form-control" required>
             </div>
             <div class="mb-3">
                 <label>Harga Tambahan (Rp)</label>
                 <input type="number" name="price" class="form-control" value="0" required>
             </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Tautkan Produk -->
<div class="modal fade" id="linkProductModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/?page=modifiers/link-product" method="POST">
          <div class="modal-header">
            <h5 class="modal-title">Tautkan ke Produk</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
             <?php \Siappos\Shared\Csrf::token('modifiers'); ?>
             <input type="hidden" name="set_id" id="link_set_id" value="">
             <div class="mb-3">
                 <label>ID Produk</label>
                 <input type="number" name="product_id" class="form-control" required placeholder="Masukkan ID Produk">
                 <small class="text-muted">Cari ID di menu Produk.</small>
             </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Tautkan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-add-mod').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('mod_set_id').value = this.dataset.setId;
        });
    });
    document.querySelectorAll('.btn-link-prod').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('link_set_id').value = this.dataset.setId;
        });
    });
});
</script>

<?php require_once 'partials/footer.php'; ?>

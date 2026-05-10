<?php
/** @var array<int, array<string, mixed>> $products */
/** @var string $title */
use Siappos\Shared\Csrf;
?>
<div class="panel">
    <h2>Buat Penyesuaian Stok (Stock Opname)</h2>

    <form action="/?page=stock-adjustments/store" method="POST">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

        <div style="margin-bottom: 20px;">
            <label>Tipe Penyesuaian *</label>
            <select name="type" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; max-width: 400px;">
                <option value="normal">Normal (Hilang/Rusak/Koreksi)</option>
            </select>
        </div>

        <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="border-bottom: 2px solid var(--line); text-align: left;">
                    <th style="padding: 10px;">Produk</th>
                    <th style="padding: 10px; width: 150px;">Aksi (+ / -)</th>
                    <th style="padding: 10px; width: 150px;">Kuantitas (Qty)</th>
                </tr>
            </thead>
            <tbody id="adjustment-lines">
                <!-- Baris pertama (default) -->
                <tr>
                    <td style="padding: 10px;">
                        <select name="lines[0][product_data]" required style="width: 100%; padding: 8px;">
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] . '|' . ($product['variation_id'] ?? '') ?>">
                                    <?= htmlspecialchars($product['name']) ?> (Stok saat ini: <?= floatval($product['stock_qty']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="padding: 10px;">
                        <select name="lines[0][action_type]" required style="width: 100%; padding: 8px;">
                            <option value="subtract">Kurangi (-) Rusak/Hilang</option>
                            <option value="add">Tambah (+) Kelebihan</option>
                        </select>
                    </td>
                    <td style="padding: 10px;">
                        <input type="number" name="lines[0][qty]" step="0.01" min="0.01" required style="width: 100%; padding: 8px;">
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="margin-bottom: 20px;">
            <button type="button" class="btn" onclick="addLine()" style="background: #e0e0e0; color: #333;">+ Tambah Baris Produk</button>
        </div>

        <button type="submit" class="btn btn-primary" style="font-size: 1.1em; padding: 10px 20px;">Simpan Penyesuaian Stok</button>
        <a href="/?page=stock-adjustments" class="btn" style="margin-left: 10px;">Batal</a>
    </form>
</div>

<script>
    let lineCount = 1;
    function addLine() {
        const tbody = document.getElementById('adjustment-lines');
        const originalRow = tbody.rows[0];
        const newRow = originalRow.cloneNode(true);

        // Update name attributes for arrays
        const selects = newRow.querySelectorAll('select');
        selects[0].name = `lines[${lineCount}][product_data]`;
        selects[0].value = ''; // Reset product
        selects[1].name = `lines[${lineCount}][action_type]`;
        selects[1].value = 'subtract'; // Reset action

        const inputs = newRow.querySelectorAll('input');
        inputs[0].name = `lines[${lineCount}][qty]`;
        inputs[0].value = ''; // Reset qty

        tbody.appendChild(newRow);
        lineCount++;
    }
</script>

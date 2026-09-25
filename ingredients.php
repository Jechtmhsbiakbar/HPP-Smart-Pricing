<?php
declare(strict_types=1);
require __DIR__ . '/includes/actions.php';
$ingredients = active_ingredients();
$edit = isset($_GET['edit']) ? one('SELECT * FROM ingredients WHERE id=?', 'i', [(int) $_GET['edit']]) : null;
layout_start('Bahan', 'ingredients.php');
?>
<section class="page-hero">
    <div>
        <p class="eyebrow">INVENTORY / BAHAN</p>
        <h1>Kelola bahan baku</h1>
        <p class="muted">Harga disimpan per satuan dasar agar resep dan stok konsisten.</p>
    </div><a class="button primary" href="#ingredient-form">+ Tambah bahan</a>
</section>
<section class="panel" id="ingredient-form">
    <div class="panel-heading">
        <div>
            <p class="eyebrow"><?= $edit ? 'EDIT BAHAN' : 'INPUT BAHAN' ?></p>
            <h2><?= $edit ? 'Perbarui ' . e($edit['name']) : 'Bahan baru' ?></h2>
        </div><span class="badge">Gram · ml · pcs</span>
    </div>
    <form method="post" action="ingredients.php" class="form-grid" id="form-ingredient"><?= csrf_field() ?><input
            type="hidden" name="action" value="<?= $edit ? 'update_ingredient' : 'add_ingredient' ?>"><input
            type="hidden" name="return_to" value="ingredients.php"><?php if ($edit): ?><input type="hidden"
                name="ingredient_id" value="<?= $edit['id'] ?>"><?php endif; ?>

        <label>Nama bahan
            <input name="name" required value="<?= e($edit['name'] ?? '') ?>" placeholder="Contoh: Tepung">
        </label>

        <label>Satuan stok
            <select name="base_unit" id="base_unit" onchange="syncCountInputs()">
                <?php foreach (['gram', 'ml', 'pcs'] as $u): ?>
                    <option <?= ($edit['base_unit'] ?? 'gram') === $u ? 'selected' : '' ?>><?= $u ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <!-- Input Harga Pembelian -->
        <label>Harga total pembelian
            <input id="input-harga" name="price" type="text" inputmode="numeric"
                value="<?= e(isset($edit['price']) ? (float) $edit['price'] : '0') ?>"
                oninput="formatRupiah(this); hitungHPP();" placeholder="Rp 0" required>
        </label>

        <!-- Jumlah Pembelian (Menggunakan (float) agar nol dibelakang koma hilang) -->
        <label>Jumlah pembelian
            <input id="input-qty" name="price_quantity" type="number" min="0.001" step="any"
                value="<?= e(isset($edit['price_quantity']) ? (float) $edit['price_quantity'] : '1') ?>"
                oninput="hitungHPP()" required>
        </label>

        <label>Satuan harga
            <select name="price_unit">
                <?php foreach (array_keys(APP_UNITS) as $u): ?>
                    <option <?= ($edit['price_unit'] ?? 'gram') === $u ? 'selected' : '' ?>><?= $u ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <!-- Pemakaian Per Resep/Porsi -->
        <label>Pemakaian per porsi/resep
            <input id="input-recipe-qty" name="recipe_quantity" type="number" min="0.001" step="any"
                value="<?= e(isset($edit['recipe_quantity']) ? (float) $edit['recipe_quantity'] : '1') ?>"
                oninput="hitungHPP()" placeholder="Contoh: 1" required>
        </label>

        <!-- Output HPP Otomatis (Readonly) -->
        <label>Estimasi HPP per porsi
            <input id="input-hpp" type="text" value="Rp 0" readonly
                style="background-color: #f3f4f6; font-weight: bold; color: #10b981;">
        </label>

        <!-- Stok Minimum (Menggunakan (float)) -->
        <label>Stok minimum
            <input id="input-min-stock" name="min_stock" type="number" min="0" step="any"
                value="<?= e(isset($edit['min_stock']) ? (float) $edit['min_stock'] : '0') ?>">
        </label>

        <!-- Stok Maksimum (Menggunakan (float)) -->
        <label>Stok maksimum
            <input id="input-max-stock" name="max_stock" type="number" min="0" step="any"
                value="<?= e(isset($edit['max_stock']) ? (float) $edit['max_stock'] : '0') ?>">
        </label>

        <div class="form-actions">
            <button class="button primary">Simpan bahan</button>
            <?php if ($edit): ?>
                <a class="button secondary" href="ingredients.php">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">DATABASE BAHAN</p>
            <h2><?= count($ingredients) ?> bahan aktif</h2>
        </div><input class="table-search" data-filter-table="#ingredient-table" placeholder="Cari bahan...">
    </div>
    <div class="table-scroll">
        <table id="ingredient-table">
            <thead>
                <tr>
                    <th>Bahan</th>
                    <th>Harga dasar</th>
                    <th>Stok</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody><?php foreach ($ingredients as $i): ?>
                    <tr>
                        <td><strong><?= e($i['name']) ?></strong><small><?= e($i['base_unit']) ?> · beli
                                <?= e((float) $i['price_quantity'] . ' ' . $i['price_unit']) ?></small></td>
                        <td><?= rupiah($i['unit_price_base']) ?> / <?= e($i['base_unit']) ?></td>
                        <td><?= number_format((float) $i['stock_qty_base'], 0, ',', '.') ?>     <?= e($i['base_unit']) ?></td>
                        <td><?= ((float) $i['min_stock'] > 0 && (float) $i['stock_qty_base'] <= (float) $i['min_stock']) ? '<span class="badge warning">Menipis</span>' : '<span class="badge success">Aman</span>' ?>
                        </td>
                        <td><a class="button secondary small" href="?edit=<?= $i['id'] ?>">Edit</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Nonaktifkan bahan?')">
                                <?= csrf_field() ?><input type="hidden" name="action" value="delete_ingredient"><input
                                    type="hidden" name="ingredient_id" value="<?= $i['id'] ?>"><button
                                    class="button danger small">Nonaktifkan</button>
                            </form>
                        </td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    // Format tampilan ke Rupiah
    function formatRupiah(element) {
        let rawValue = element.value.replace(/[^0-9]/g, '');
        if (rawValue === '' || rawValue === '0') {
            element.value = '';
            return;
        }
        let formatted = new Intl.NumberFormat('id-ID').format(rawValue);
        element.value = 'Rp ' + formatted;
    }

    function syncCountInputs() {
        const baseUnitSelect = document.getElementById('base_unit');
        const isCount = baseUnitSelect ? baseUnitSelect.value === 'pcs' : false;

        // Sesuaikan step dan min untuk input kuantitas
        ['input-qty', 'input-recipe-qty'].forEach(function (id) {
            const input = document.getElementById(id);
            if (input) {
                input.step = isCount ? '1' : 'any';
                input.min = isCount ? '1' : '0.001'; // Jika pcs, batas minimal adalah 1 bulat
            }
        });

        // Sesuaikan step dan min untuk stok
        ['input-min-stock', 'input-max-stock'].forEach(function (id) {
            const input = document.getElementById(id);
            if (input) {
                input.step = isCount ? '1' : 'any';
                input.min = '0';
            }
        });
    }

    // Fungsi Hitung HPP Otomatis
    function hitungHPP() {
        const inputHarga = document.getElementById('input-harga');
        const inputQty = document.getElementById('input-qty');
        const inputRecipeQty = document.getElementById('input-recipe-qty');
        const inputHPP = document.getElementById('input-hpp');

        let hargaTotal = parseFloat(inputHarga.value.replace(/[^0-9]/g, '')) || 0;
        let qtyPembelian = parseFloat(inputQty.value) || 0;
        let qtyPemakaian = parseFloat(inputRecipeQty.value) || 0;

        if (hargaTotal > 0 && qtyPembelian > 0 && qtyPemakaian > 0) {
            let hargaSatuanBase = hargaTotal / qtyPembelian;
            let hppPerPorsi = hargaSatuanBase * qtyPemakaian;
            inputHPP.value = 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(hppPerPorsi);
        } else {
            inputHPP.value = 'Rp 0';
        }
    }

    // Inisialisasi saat halaman selesai dimuat
    syncCountInputs();
    document.addEventListener('DOMContentLoaded', function () {
        const inputHarga = document.getElementById('input-harga');
        if (inputHarga && inputHarga.value && inputHarga.value !== '0') {
            formatRupiah(inputHarga);
        }

        hitungHPP();

        const form = document.getElementById('form-ingredient');
        if (form) {
            form.addEventListener('submit', function () {
                if (inputHarga) {
                    inputHarga.value = inputHarga.value.replace(/[^0-9]/g, '');
                }
            });
        }
    });
</script>

<?php layout_end(); ?>
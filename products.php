<?php
declare(strict_types=1);
require __DIR__ . '/includes/actions.php';

$ingredients = active_ingredients();
$categories = active_product_categories();
$recipes = recipes_with_cost();
$editRecipe = isset($_GET['edit']) ? one('SELECT * FROM recipes WHERE id=?', 'i', [(int) $_GET['edit']]) : null;
$editItems = $editRecipe ? recipe_items((int) $editRecipe['id']) : [];

// Jika mode EDIT gunakan item yang ada. Jika mode BARU (tambah), biarkan array kosong.
$formItems = $editItems ?: [];

// Siapkan data unit_price_base bahan ke array JSON untuk JS
$ingredientPriceMap = [];
foreach ($ingredients as $ing) {
    $ingredientPriceMap[$ing['id']] = [
        'unit_price_base' => (float) $ing['unit_price_base'],
        'base_unit' => $ing['base_unit'],
    ];
}

// Persentase markup berdasarkan tier
$markupTiers = [
    'murah' => markup_for('murah'),
    'normal' => markup_for('normal'),
    'mahal' => markup_for('mahal'),
];

// Cek apakah data yang diedit menggunakan harga manual
$isManualPrice = false;
if ($editRecipe && !empty($editRecipe['selling_price'])) {
    $recPrice = (float) ($editRecipe['recommended_price'] ?? 0);
    $sellPrice = (float) $editRecipe['selling_price'];
    if (abs($sellPrice - $recPrice) > 0.01) {
        $isManualPrice = true;
    }
}

layout_start('Produk & Resep', 'products.php');
?>
<section class="page-hero">
    <div>
        <p class="eyebrow">PRODUK / RESEP / BOM</p>
        <h1>Produk dan resep</h1>
        <p class="muted">Semua jumlah resep dikonversi ke satuan stok sebelum HPP dihitung.</p>
    </div>
</section>
<div class="workspace">
    <section class="panel" id="recipe-form">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">RECIPE BUILDER</p>
                <h2><?= $editRecipe ? 'Edit Produk' : 'Produk baru' ?></h2>
            </div><span class="badge">HPP terpusat</span>
        </div>
        <form method="post" action="products.php" id="form-product"><?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editRecipe ? 'update_recipe' : 'add_recipe' ?>">
            <?php if ($editRecipe): ?>
                <input type="hidden" name="recipe_id" value="<?= $editRecipe['id'] ?>">
            <?php endif; ?>

            <label>Nama produk
                <input name="recipe_name" required value="<?= e($editRecipe['name'] ?? '') ?>"
                    placeholder="Es Kopi Susu">
            </label>

            <label>Kategori utama
                <select name="category_id" required>
                    <option value="" disabled <?= empty($editRecipe['category_id']) ? 'selected' : '' ?>>Pilih kategori produk</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= (int) ($editRecipe['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div id="ingredient-lines">
                <?php foreach ($formItems as $item): ?>
                    <div class="ingredient-line">
                        <label>Bahan
                            <select name="ingredient_id[]" class="input-ingredient-id" onchange="syncIngredientUnit(this); calculateLiveHpp()"
                                required>
                                <?php foreach ($ingredients as $i): ?>
                                    <option value="<?= $i['id'] ?>" data-unit="<?= e($i['base_unit']) ?>" <?= $i['id'] == $item['ingredient_id'] ? 'selected' : '' ?>>
                                        <?= e($i['name']) ?> (<?= e($i['base_unit']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Jumlah
                            <input name="quantity[]" class="input-ingredient-qty" type="number" step="any" min="0.001"
                                value="<?= e((float) $item['quantity']) ?>" oninput="calculateLiveHpp()" required>
                        </label>
                        <label>Satuan bahan
                            <span class="ingredient-unit-display" aria-live="polite"><?= e($item['base_unit']) ?></span>
                        </label>
                        <button type="button" class="icon-button remove-line">×</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="button secondary" type="button" id="add-line">+ Tambah bahan</button>

            <div class="form-grid compact">
                <!-- Biaya Alat (Formatted Rupiah) -->
                <label>Biaya alat
                    <input name="equipment_cost" id="input-equipment-cost" type="text" inputmode="numeric"
                        value="<?= !empty($editRecipe['equipment_cost']) && (float) $editRecipe['equipment_cost'] > 0 ? 'Rp ' . number_format((float) $editRecipe['equipment_cost'], 0, ',', '.') : '' ?>"
                        oninput="formatRupiah(this); calculateLiveHpp();" placeholder="Rp 0">
                </label>

                <!-- Biaya Operasional (Formatted Rupiah) -->
                <label>Operasional
                    <input name="operational_cost" id="input-operational-cost" type="text" inputmode="numeric"
                        value="<?= !empty($editRecipe['operational_cost']) && (float) $editRecipe['operational_cost'] > 0 ? 'Rp ' . number_format((float) $editRecipe['operational_cost'], 0, ',', '.') : '' ?>"
                        oninput="formatRupiah(this); calculateLiveHpp();" placeholder="Rp 0">
                </label>

                <!-- Biaya Kemasan (Formatted Rupiah) -->
                <label>Kemasan
                    <input name="packaging_cost" id="input-packaging-cost" type="text" inputmode="numeric"
                        value="<?= !empty($editRecipe['packaging_cost']) && (float) $editRecipe['packaging_cost'] > 0 ? 'Rp ' . number_format((float) $editRecipe['packaging_cost'], 0, ',', '.') : '' ?>"
                        oninput="formatRupiah(this); calculateLiveHpp();" placeholder="Rp 0">
                </label>

                <label>Tier markup
                    <select name="price_tier" id="select-price-tier" onchange="calculateLiveHpp()">
                        <?php foreach (['murah', 'normal', 'mahal'] as $tier): ?>
                            <option value="<?= $tier ?>" <?= ($editRecipe['price_tier'] ?? 'normal') === $tier ? 'selected' : '' ?>>
                                <?= ucfirst($tier) ?> (<?= markup_for($tier) ?>%)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <!-- Estimasi HPP & Harga Rekomendasi Otomatis -->
                <label>Estimasi HPP
                    <input id="display-hpp" type="text" value="Rp 0" readonly
                        style="background-color: #f3f4f6; font-weight: 600; color: #4b5563;">
                </label>
                <label>Harga Rekomendasi
                    <input id="display-recommended-price" type="text" value="Rp 0" readonly
                        style="background-color: #f3f4f6; font-weight: 700; color: #2563eb;">
                </label>

                <!-- Opsi Metode Harga Jual -->
                <div style="grid-column: span 2; margin-top: 10px;">
                    <label style="font-weight: bold; margin-bottom: 6px; display: block;">Metode Harga Jual</label>
                    <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 8px;">
                        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="price_mode" value="auto" id="mode-auto"
                                onchange="togglePriceMode()" <?= !$isManualPrice ? 'checked' : '' ?>>
                            Gunakan Rekomendasi Tier
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="price_mode" value="manual" id="mode-manual"
                                onchange="togglePriceMode()" <?= $isManualPrice ? 'checked' : '' ?>>
                            Input Manual
                        </label>
                    </div>
                </div>

                <!-- Input Harga Jual Final -->
                <label id="container-selling-price" style="grid-column: span 2;">Harga Jual
                    <input id="input-selling-price" name="selling_price" type="text" inputmode="numeric"
                        value="<?= !empty($editRecipe['selling_price']) && (float) $editRecipe['selling_price'] > 0 ? 'Rp ' . number_format((float) $editRecipe['selling_price'], 0, ',', '.') : '' ?>"
                        oninput="formatRupiah(this)" placeholder="Rp 0">
                </label>
            </div>

            <button class="button primary full">Simpan produk</button>
            <?php if ($editRecipe): ?>
                <a class="button secondary full" href="products.php">Batal edit</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">PRODUK AKTIF</p>
                <h2><?= count($recipes) ?> produk</h2>
            </div><input class="table-search" data-filter-table="#product-table" placeholder="Cari produk...">
        </div>
        <div class="table-scroll">
            <table id="product-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>HPP</th>
                        <th>Harga rekomendasi</th>
                        <th>Harga jual</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recipes as $r): ?>
                        <tr>
                                <td data-label="Produk"><strong><?= e($r['name']) ?></strong><small><?= e($r['category_name'] ?? 'Belum berkategori') ?> · Markup
                                    <?= e($r['price_tier']) ?></small></td>
                            <td data-label="HPP"><?= rupiah($r['hpp_live']) ?></td>
                            <td data-label="Harga rekomendasi"><?= rupiah($r['recommended_price'] ?? 0) ?></td>
                            <td data-label="Harga jual"><strong><?= rupiah($r['selling_price']) ?></strong></td>
                            <td data-label="Aksi">
                                <div class="table-actions"><a class="button secondary small"
                                        href="?edit=<?= $r['id'] ?>">Edit</a> <a class="button secondary small"
                                        href="pos.php?product=<?= $r['id'] ?>">Jual</a>
                                    <form method="post" style="display:inline"
                                        onsubmit="return confirm('Nonaktifkan produk?')">
                                        <?= csrf_field() ?><input type="hidden" name="action" value="delete_recipe"><input
                                            type="hidden" name="recipe_id" value="<?= $r['id'] ?>"><button
                                            class="button danger small">Nonaktifkan</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- Template baris bahan untuk tombol "+ Tambah bahan" -->
<template id="ingredient-line-template">
    <div class="ingredient-line">
        <label>Bahan
            <select name="ingredient_id[]" class="input-ingredient-id" onchange="syncIngredientUnit(this); calculateLiveHpp()" required>
                <option value="" disabled selected>-- Pilih Bahan --</option>
                <?php foreach ($ingredients as $i): ?>
                    <option value="<?= $i['id'] ?>" data-unit="<?= e($i['base_unit']) ?>"><?= e($i['name']) ?> (<?= e($i['base_unit']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Jumlah
            <input name="quantity[]" class="input-ingredient-qty" type="number" step="any" min="0.001" placeholder="1"
                oninput="calculateLiveHpp()" required>
        </label>
        <label>Satuan bahan
            <span class="ingredient-unit-display" aria-live="polite">Pilih bahan</span>
        </label>
        <button type="button" class="icon-button remove-line">×</button>
    </div>
</template>

<script>
    const ingredientMap = <?= json_encode($ingredientPriceMap, JSON_UNESCAPED_UNICODE) ?>;
    const markupTiers = <?= json_encode($markupTiers, JSON_UNESCAPED_UNICODE) ?>;
    let calculatedRecommendedPrice = 0;

    // Format Input Angka ke Format Rupiah saat Mengetik
    function formatRupiah(element) {
        let rawValue = element.value.replace(/[^0-9]/g, '');
        if (rawValue === '' || rawValue === '0') {
            element.value = '';
            return;
        }
        let formatted = new Intl.NumberFormat('id-ID').format(rawValue);
        element.value = 'Rp ' + formatted;
    }

    // Ambil Angka Murni dari Elemen Input Format Rupiah
    function getCleanNumber(elementId) {
        const el = document.getElementById(elementId);
        if (!el) return 0;
        return parseFloat(el.value.replace(/[^0-9]/g, '')) || 0;
    }

    // Format Angka ke Teks Rupiah
    function toRupiahText(amount) {
        return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(amount));
    }

    function syncIngredientUnit(select) {
        const line = select.closest('.ingredient-line');
        const display = line ? line.querySelector('.ingredient-unit-display') : null;
        const option = select.options[select.selectedIndex];
        if (display) display.textContent = option && option.dataset.unit ? option.dataset.unit : 'Pilih bahan';
    }

    // Hitung HPP dan Harga Rekomendasi secara Real-Time
    function calculateLiveHpp() {
        let totalHpp = 0;

        // Biaya Tambahan (Alat, Operasional, Kemasan)
        const equip = getCleanNumber('input-equipment-cost');
        const oper = getCleanNumber('input-operational-cost');
        const pack = getCleanNumber('input-packaging-cost');
        totalHpp += (equip + oper + pack);

        // Hitung Biaya Bahan
        const lines = document.querySelectorAll('.ingredient-line');
        lines.forEach(line => {
            const selectIng = line.querySelector('.input-ingredient-id');
            const inputQty = line.querySelector('.input-ingredient-qty');

            if (selectIng && inputQty) {
                const ingId = selectIng.value;
                const qty = parseFloat(inputQty.value) || 0;

                if (ingId && ingredientMap[ingId]) {
                    const unitPriceBase = ingredientMap[ingId].unit_price_base || 0;
                    totalHpp += (qty * unitPriceBase);
                }
            }
        });

        // Hitung Harga Rekomendasi Berdasarkan Tier Markup
        const tierSelect = document.getElementById('select-price-tier');
        const selectedTier = tierSelect ? tierSelect.value : 'normal';
        const markupPercent = markupTiers[selectedTier] || 0;

        calculatedRecommendedPrice = totalHpp * (1 + (markupPercent / 100));

        document.getElementById('display-hpp').value = toRupiahText(totalHpp);
        document.getElementById('display-recommended-price').value = toRupiahText(calculatedRecommendedPrice);

        if (document.getElementById('mode-auto').checked) {
            document.getElementById('input-selling-price').value = toRupiahText(calculatedRecommendedPrice);
        }
    }

    // Beralih Mode Antara Otomatis dan Manual
    function togglePriceMode() {
        const isManual = document.getElementById('mode-manual').checked;
        const inputSellingPrice = document.getElementById('input-selling-price');

        if (isManual) {
            inputSellingPrice.removeAttribute('readonly');
            inputSellingPrice.style.backgroundColor = '#ffffff';
        } else {
            inputSellingPrice.setAttribute('readonly', 'readonly');
            inputSellingPrice.style.backgroundColor = '#f3f4f6';
            inputSellingPrice.value = toRupiahText(calculatedRecommendedPrice);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('form-product');
        const btnAddLine = document.getElementById('add-line');
        const ingredientLinesContainer = document.getElementById('ingredient-lines');
        const template = document.getElementById('ingredient-line-template');

        // Tambah Baris Bahan Baru
        if (btnAddLine && template && ingredientLinesContainer) {
            btnAddLine.addEventListener('click', function () {
                const clone = template.content.cloneNode(true);
                ingredientLinesContainer.appendChild(clone);
                calculateLiveHpp();
            });
        }

        // Hapus Baris Bahan
        if (ingredientLinesContainer) {
            ingredientLinesContainer.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-line')) {
                    const line = e.target.closest('.ingredient-line');
                    if (line) {
                        line.remove();
                        calculateLiveHpp();
                    }
                }
            });
        }

        document.querySelectorAll('.input-ingredient-id').forEach(syncIngredientUnit);
        calculateLiveHpp();
        togglePriceMode();

        // Pembersihan Format "Rp" Sebelum Form Di-submit ke Backend
        if (form) {
            form.addEventListener('submit', function () {
                const isManual = document.getElementById('mode-manual').checked;

                // 1. Bersihkan Format Rupiah dari Biaya Tambahan
                const inputEquip = document.getElementById('input-equipment-cost');
                const inputOper = document.getElementById('input-operational-cost');
                const inputPack = document.getElementById('input-packaging-cost');
                const inputSelling = document.getElementById('input-selling-price');

                if (inputEquip) inputEquip.value = inputEquip.value.replace(/[^0-9]/g, '') || '0';
                if (inputOper) inputOper.value = inputOper.value.replace(/[^0-9]/g, '') || '0';
                if (inputPack) inputPack.value = inputPack.value.replace(/[^0-9]/g, '') || '0';

                // 2. Bersihkan/Atur Nilai Harga Jual
                if (isManual) {
                    if (inputSelling) {
                        inputSelling.value = inputSelling.value.replace(/[^0-9]/g, '');
                    }
                } else {
                    if (inputSelling) {
                        inputSelling.value = ''; // Kosongkan agar actions.php otomatis menggunakan recommended_price
                    }
                }
            });
        }
    });
</script>

<?php layout_end(); ?>
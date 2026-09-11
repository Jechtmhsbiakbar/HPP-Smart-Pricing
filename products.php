<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
function rupiah(float $value): string
{
    return 'Rp ' . number_format($value, 0, ',', '.');
}
function unitFactor(string $unit): float
{
    return ['kg' => 1000, 'liter' => 1000, 'meter' => 100, 'gram' => 1, 'ml' => 1, 'cm' => 1, 'pcs' => 1][$unit] ?? 1;
}
function pricingMarkup(string $tier): float
{
    return ['murah' => 15.0, 'normal' => 30.0, 'mahal' => 50.0][$tier] ?? 30.0;
}
function tierLabel(string $tier): string
{
    return ucfirst($tier);
}
$ingredients = [];
$result = $db->query('SELECT id, name, base_unit, price, price_quantity, price_unit FROM ingredients ORDER BY name');
while ($row = $result->fetch_assoc()) {
    $ingredients[] = $row;
}
$recipes = [];
$result = $db->query('SELECT r.id, r.name, r.equipment_cost, r.operational_cost, r.hpp, r.price_tier, r.recommended_price, ri.ingredient_id, ri.quantity, ri.unit, i.name AS ingredient_name, i.base_unit, i.price, i.price_quantity, i.price_unit FROM recipes r LEFT JOIN recipe_ingredients ri ON ri.recipe_id = r.id LEFT JOIN ingredients i ON i.id = ri.ingredient_id ORDER BY r.created_at DESC');
$recipeIndex = [];
while ($row = $result->fetch_assoc()) {
    $id = (int) $row['id'];
    if (!isset($recipeIndex[$id])) {
        $recipeIndex[$id] = ['id' => $id, 'name' => $row['name'], 'equipment_cost' => (float) $row['equipment_cost'], 'operational_cost' => (float) $row['operational_cost'], 'saved_hpp' => (float) $row['hpp'], 'price_tier' => (string) ($row['price_tier'] ?? 'normal'), 'recommended_price' => (float) $row['recommended_price'], 'ingredient_cost' => 0.0, 'ingredients' => []];
    }
    if ($row['price'] !== null)
        $recipeIndex[$id]['ingredient_cost'] += (float) $row['quantity'] * unitFactor((string) $row['unit']) * ((float) $row['price'] / (float) $row['price_quantity']) / unitFactor((string) $row['price_unit']);
    if ($row['ingredient_id'] !== null)
        $recipeIndex[$id]['ingredients'][] = ['ingredient_id' => (int) $row['ingredient_id'], 'quantity' => (float) $row['quantity'], 'unit' => (string) $row['unit']];
}
foreach ($recipeIndex as $recipe) {
    $recipe['hpp'] = $recipe['saved_hpp'] > 0 ? $recipe['saved_hpp'] : $recipe['ingredient_cost'] + $recipe['equipment_cost'] + $recipe['operational_cost'];
    if ($recipe['recommended_price'] <= 0)
        $recipe['recommended_price'] = $recipe['hpp'] * (1 + pricingMarkup($recipe['price_tier']) / 100);
    $recipes[] = $recipe;
}
$saved = isset($_GET['saved']);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk · Smart Pricing: Toko Bunga Paubut Cantik</title>
    <link rel="stylesheet" href="assets/styles.css?v=3">
</head>

<body class="products-page">
    <header class="topbar"><a class="brand" href="index.php"><img class="brand-logo"
                src="assets/HPP_Toko-Bunga-Paubut(200x200).webp" alt="Logo Toko Bunga Paubut Cantik"><span>Toko Bunga Paubut Cantik</span></a><span class="status-dot">● Online</span></header>
    <div class="app-shell">
        <aside class="sidebar">
            <p class="sidebar-label">MENU UTAMA</p><a href="index.php">⌂ Dashboard</a><a class="active"
                href="products.php">▣ Produk</a><a href="ingredients.php">◈ Bahan</a>
        </aside>
        <main class="container page-content">
            <nav class="mobile-nav"><a href="index.php">Dashboard</a><a class="active" href="products.php">Produk</a><a
                    href="ingredients.php">Bahan</a></nav>
            <section class="page-hero">
                <div>
                    <p class="eyebrow">MANAJEMEN PRODUK</p>
                    <h1>Produk dan resep</h1>
                    <p class="muted">Kelola resep, total HPP, dan harga jual rekomendasi.</p>
                </div><a class="button primary" href="#product-form">+ Produk baru</a>
            </section>
            <?php if ($saved): ?>
                <div class="alert success">✓ Produk berhasil disimpan. Nilai HPP dan harga rekomendasi sudah diperbarui.
                </div><?php endif; ?>
            <section class="workspace">
                <div class="panel" id="product-form">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">RECIPE BUILDER</p>
                            <h2>Buat produk baru</h2>
                        </div><span class="badge">Live calculation</span>
                    </div>
                    <form method="post" action="index.php" id="recipe-form"><input type="hidden" name="action"
                            value="add_recipe"><input type="hidden" name="return_to" value="products.php"><label>Nama
                            produk<input name="recipe_name" required placeholder="Contoh: Kopi Susu Gula Aren"></label>
                        <div id="ingredient-lines">
                            <div class="ingredient-line"><label>Bahan<select name="ingredient_id[]"
                                        class="ingredient-select" required>
                                        <option value="">Pilih bahan</option>
                                        <?php foreach ($ingredients as $ingredient): ?>
                                            <option value="<?= (int) $ingredient['id'] ?>"
                                                data-price="<?= e((string) $ingredient['price']) ?>"
                                                data-quantity="<?= e((string) $ingredient['price_quantity']) ?>"
                                                data-unit="<?= e($ingredient['price_unit']) ?>">
                                                <?= e($ingredient['name']) ?> (<?= e($ingredient['base_unit']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select></label><label>Jumlah<input name="quantity[]" class="quantity-input"
                                        type="number" min="0.001" step="0.001" value="1"
                                        required></label><label>Satuan<select name="unit[]" class="unit-select">
                                        <option>gram</option>
                                        <option>ml</option>
                                        <option>pcs</option>
                                        <option>kg</option>
                                        <option>liter</option>
                                    </select></label><button type="button" class="icon-button remove-line"
                                    aria-label="Hapus bahan">×</button></div>
                        </div><button type="button" class="button secondary" id="add-line">+ Tambah bahan</button>
                        <div class="cost-grid"><label>Biaya alat<input name="equipment_cost_display" data-money-input
                                    data-hidden-target="equipment-cost" inputmode="numeric" value="Rp 0"><input
                                    type="hidden" name="equipment_cost" id="equipment-cost"
                                    value="0"></label><label>Biaya operasional<input name="operational_cost_display"
                                    data-money-input data-hidden-target="operational-cost" inputmode="numeric"
                                    value="Rp 0"><input type="hidden" name="operational_cost" id="operational-cost"
                                    value="0"></label></div><button class="button primary full" type="submit">Simpan
                            produk</button>
                    </form>
                </div>
                <aside class="panel preview">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">LIVE HPP PREVIEW</p>
                            <h2>Total HPP</h2>
                        </div>
                    </div>
                    <div class="total" id="hpp-total">Rp 0</div>
                    <div class="breakdown">
                        <div><span>Bahan</span><strong id="ingredient-total">Rp 0</strong></div>
                        <div><span>Alat</span><strong id="equipment-total">Rp 0</strong></div>
                        <div><span>Operasional</span><strong id="operational-total">Rp 0</strong></div>
                    </div>
                    <div class="pricing">
                        <p class="eyebrow">REKOMENDASI HARGA JUAL</p><label>Pilih posisi harga<select id="pricing-tier">
                                <option value="15">Murah · markup 15%</option>
                                <option value="30" selected>Normal · markup 30%</option>
                                <option value="50">Mahal · markup 50%</option>
                            </select></label>
                        <div class="price-result"><span>Harga Jual Rekomendasi</span><strong id="selling-price">Rp
                                0</strong></div>
                    </div>
                </aside>
            </section>
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">DAFTAR PRODUK</p>
                        <h2><?= count($recipes) ?> produk tersimpan</h2>
                    </div>
                </div><?php if (!$recipes): ?>
                    <div class="empty"><strong>Belum ada produk</strong><span>Simpan produk pertama melalui form di
                            atas.</span></div><?php else: ?>
                    <div class="recipe-list"><?php foreach ($recipes as $recipe): ?>
                            <article class="recipe-card" data-price-tier="<?= e($recipe['price_tier']) ?>">
                                <div class="recipe-card-main"><strong><?= e($recipe['name']) ?></strong><span>Bahan
                                        <?= rupiah($recipe['ingredient_cost']) ?> · Alat
                                        <?= rupiah($recipe['equipment_cost']) ?> · Operasional
                                        <?= rupiah($recipe['operational_cost']) ?></span></div>
                                <div class="recipe-pricing"><strong>Total HPP: <?= rupiah($recipe['hpp']) ?></strong><span>Harga
                                        Jual Rekomendasi (<?= e(tierLabel($recipe['price_tier'])) ?>):
                                        <?= rupiah($recipe['recommended_price']) ?></span></div>
                                <div class="card-actions"><button class="button secondary" type="button"
                                        data-modal-open="edit-recipe-<?= $recipe['id'] ?>">Edit</button>
                                    <form method="post" action="index.php"
                                        onsubmit="return confirm('Hapus produk ini? Resep dan semua detail bahannya akan ikut dihapus.');">
                                        <input type="hidden" name="action" value="delete_recipe"><input type="hidden"
                                            name="recipe_id" value="<?= $recipe['id'] ?>"><input type="hidden" name="return_to"
                                            value="products.php"><button class="button danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </article>
                            <dialog class="edit-modal" id="edit-recipe-<?= $recipe['id'] ?>">
                                <div class="modal-heading">
                                    <div>
                                        <p class="eyebrow">EDIT PRODUK</p>
                                        <h2><?= e($recipe['name']) ?></h2>
                                    </div><button class="modal-close" type="button" data-modal-close
                                        aria-label="Tutup">×</button>
                                </div>
                                <form method="post" action="index.php" class="edit-recipe-form" data-pricing-form
                                    data-hpp="<?= e((string) $recipe['hpp']) ?>"><input type="hidden" name="action"
                                        value="update_recipe"><input type="hidden" name="return_to" value="products.php"><input
                                        type="hidden" name="recipe_id" value="<?= $recipe['id'] ?>"><label>Nama produk<input
                                            name="recipe_name" required value="<?= e($recipe['name']) ?>"></label>
                                    <div class="edit-ingredient-lines">
                                        <?php foreach ($recipe['ingredients'] as $recipeIngredient): ?>
                                            <div class="ingredient-line"><label>Bahan<select name="ingredient_id[]" required>
                                                        <option value="">Pilih bahan</option>
                                                        <?php foreach ($ingredients as $ingredient): ?>
                                                            <option value="<?= (int) $ingredient['id'] ?>" <?= (int) $ingredient['id'] === $recipeIngredient['ingredient_id'] ? 'selected' : '' ?>><?= e($ingredient['name']) ?> (<?= e($ingredient['base_unit']) ?>)
                                                            </option><?php endforeach; ?>
                                                    </select></label><label>Jumlah<input name="quantity[]" type="number" min="0.001"
                                                        step="0.001" value="<?= e((string) $recipeIngredient['quantity']) ?>"
                                                        required></label><label>Satuan<select name="unit[]">
                                                        <option <?= $recipeIngredient['unit'] === 'gram' ? 'selected' : '' ?>>gram
                                                        </option>
                                                        <option <?= $recipeIngredient['unit'] === 'ml' ? 'selected' : '' ?>>ml</option>
                                                        <option <?= $recipeIngredient['unit'] === 'pcs' ? 'selected' : '' ?>>pcs
                                                        </option>
                                                        <option <?= $recipeIngredient['unit'] === 'kg' ? 'selected' : '' ?>>kg</option>
                                                        <option <?= $recipeIngredient['unit'] === 'liter' ? 'selected' : '' ?>>liter
                                                        </option>
                                                    </select></label></div><?php endforeach; ?>
                                    </div>
                                    <div class="cost-grid"><label>Biaya alat<input name="equipment_cost_display"
                                                data-money-input data-hidden-target="edit-equipment-<?= $recipe['id'] ?>"
                                                inputmode="numeric" value="<?= e(rupiah($recipe['equipment_cost'])) ?>"><input
                                                type="hidden" name="equipment_cost" id="edit-equipment-<?= $recipe['id'] ?>"
                                                value="<?= e((string) $recipe['equipment_cost']) ?>"></label><label>Biaya
                                            operasional<input name="operational_cost_display" data-money-input
                                                data-hidden-target="edit-operational-<?= $recipe['id'] ?>" inputmode="numeric"
                                                value="<?= e(rupiah($recipe['operational_cost'])) ?>"><input type="hidden"
                                                name="operational_cost" id="edit-operational-<?= $recipe['id'] ?>"
                                                value="<?= e((string) $recipe['operational_cost']) ?>"></label></div>
                                    <div class="edit-pricing-box">
                                        <p class="eyebrow">REKOMENDASI HARGA JUAL</p><label>Pilih posisi harga<select
                                                name="price_tier" data-pricing-tier>
                                                <option value="murah" <?= $recipe['price_tier'] === 'murah' ? 'selected' : '' ?>>
                                                    Murah · markup 15%</option>
                                                <option value="normal" <?= $recipe['price_tier'] === 'normal' ? 'selected' : '' ?>>
                                                    Normal · markup 30%</option>
                                                <option value="mahal" <?= $recipe['price_tier'] === 'mahal' ? 'selected' : '' ?>>
                                                    Mahal · markup 50%</option>
                                            </select></label>
                                        <div class="price-result"><span>Harga Jual Rekomendasi
                                                (<?= e(tierLabel($recipe['price_tier'])) ?>)</span><strong
                                                data-pricing-result><?= rupiah($recipe['recommended_price']) ?></strong></div>
                                    </div>
                                    <div class="modal-actions"><button class="button secondary" type="button"
                                            data-modal-close>Batal</button><button class="button primary" type="submit">Simpan
                                            perubahan</button></div>
                                </form>
                            </dialog><?php endforeach; ?>
                    </div><?php endif; ?>
            </section>
        </main>
    </div>
    <footer>Smart Pricing: Toko Bunga Paubut Cantik · Kelola produk dengan lebih cepat</footer>
    <script src="assets/app.js?v=4"></script>
</body>

</html>
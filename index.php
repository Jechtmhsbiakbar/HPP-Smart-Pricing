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

function calculateRecipeHpp(mysqli $db, array $ingredientIds, array $quantities, array $units, float $equipment, float $operational): float
{
    $ingredientCost = 0.0;
    $ingredientStmt = $db->prepare('SELECT price, price_quantity, price_unit FROM ingredients WHERE id = ?');
    foreach ($ingredientIds as $i => $ingredientId) {
        $ingredientId = (int) $ingredientId;
        $quantity = (float) ($quantities[$i] ?? 0);
        $unit = (string) ($units[$i] ?? '');
        if ($ingredientId <= 0 || $quantity <= 0 || unitFactor($unit) <= 0) {
            throw new RuntimeException('Quantity dan satuan bahan harus diisi dengan benar.');
        }
        $ingredientStmt->bind_param('i', $ingredientId);
        $ingredientStmt->execute();
        $ingredient = $ingredientStmt->get_result()->fetch_assoc();
        if (!$ingredient || (float) $ingredient['price_quantity'] <= 0) {
            throw new RuntimeException('Bahan yang dipilih tidak ditemukan atau belum memiliki harga valid.');
        }
        $ingredientCost += $quantity * unitFactor($unit)
            * ((float) $ingredient['price'] / (float) $ingredient['price_quantity'])
            / unitFactor((string) $ingredient['price_unit']);
    }
    return $ingredientCost + $equipment + $operational;
}

$message = '';
$error = '';
$transactionStarted = false;
$returnTo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $requestedReturn = (string) ($_POST['return_to'] ?? '');
    $returnTo = in_array($requestedReturn, ['index.php', 'products.php', 'ingredients.php'], true) ? $requestedReturn : '';
    try {
        if ($action === 'add_ingredient') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $baseUnit = (string) ($_POST['base_unit'] ?? '');
            $price = (float) ($_POST['price'] ?? 0);
            $priceQuantity = (float) ($_POST['price_quantity'] ?? 0);
            $priceUnit = (string) ($_POST['price_unit'] ?? '');
            if ($name === '' || $price <= 0 || $priceQuantity <= 0 || unitFactor($priceUnit) <= 0) {
                throw new RuntimeException('Lengkapi bahan, harga, jumlah, dan satuan dengan nilai yang valid.');
            }
            $stmt = $db->prepare('INSERT INTO ingredients (name, base_unit, price, price_quantity, price_unit) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('ssdds', $name, $baseUnit, $price, $priceQuantity, $priceUnit);
            $stmt->execute();
            $message = 'Bahan berhasil ditambahkan.';
        } elseif ($action === 'update_ingredient') {
            $ingredientId = (int) ($_POST['ingredient_id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $baseUnit = (string) ($_POST['base_unit'] ?? '');
            $price = (float) ($_POST['price'] ?? 0);
            $priceQuantity = (float) ($_POST['price_quantity'] ?? 0);
            $priceUnit = (string) ($_POST['price_unit'] ?? '');
            if ($ingredientId <= 0 || $name === '' || $price <= 0 || $priceQuantity <= 0 || unitFactor($priceUnit) <= 0) {
                throw new RuntimeException('Lengkapi bahan, harga, jumlah, dan satuan dengan nilai yang valid.');
            }
            $stmt = $db->prepare('UPDATE ingredients SET name = ?, base_unit = ?, price = ?, price_quantity = ?, price_unit = ? WHERE id = ?');
            $stmt->bind_param('ssddsi', $name, $baseUnit, $price, $priceQuantity, $priceUnit, $ingredientId);
            $stmt->execute();
            $message = 'Bahan berhasil diperbarui.';
        } elseif ($action === 'delete_ingredient') {
            $ingredientId = (int) ($_POST['ingredient_id'] ?? 0);
            if ($ingredientId <= 0) {
                throw new RuntimeException('Bahan tidak ditemukan.');
            }
            $usage = $db->prepare('SELECT COUNT(*) AS total FROM recipe_ingredients WHERE ingredient_id = ?');
            $usage->bind_param('i', $ingredientId);
            $usage->execute();
            $usageCount = (int) $usage->get_result()->fetch_assoc()['total'];
            if ($usageCount > 0) {
                throw new RuntimeException("Bahan masih digunakan oleh {$usageCount} resep. Hapus atau ubah resep tersebut terlebih dahulu.");
            }
            $stmt = $db->prepare('DELETE FROM ingredients WHERE id = ?');
            $stmt->bind_param('i', $ingredientId);
            $stmt->execute();
            $message = 'Bahan berhasil dihapus.';
        } elseif ($action === 'add_recipe') {
            $name = trim((string) ($_POST['recipe_name'] ?? ''));
            $equipment = (float) ($_POST['equipment_cost'] ?? 0);
            $operational = (float) ($_POST['operational_cost'] ?? 0);
            $ingredientIds = $_POST['ingredient_id'] ?? [];
            $quantities = $_POST['quantity'] ?? [];
            $units = $_POST['unit'] ?? [];
            $priceTier = (string) ($_POST['price_tier'] ?? 'normal');
            if ($name === '' || !is_array($ingredientIds) || count($ingredientIds) === 0) {
                throw new RuntimeException('Nama produk dan minimal satu bahan wajib diisi.');
            }
            if (!array_key_exists($priceTier, ['murah' => true, 'normal' => true, 'mahal' => true])) {
                throw new RuntimeException('Pilihan harga tidak valid.');
            }
            $hpp = calculateRecipeHpp($db, $ingredientIds, $quantities, $units, $equipment, $operational);
            $recommendedPrice = $hpp * (1 + pricingMarkup($priceTier) / 100);
            $db->begin_transaction();
            $transactionStarted = true;
            $stmt = $db->prepare('INSERT INTO recipes (name, equipment_cost, operational_cost, hpp, price_tier, recommended_price) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sdddsd', $name, $equipment, $operational, $hpp, $priceTier, $recommendedPrice);
            $stmt->execute();
            $recipeId = $db->insert_id;
            $line = $db->prepare('INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit) VALUES (?, ?, ?, ?)');
            foreach ($ingredientIds as $i => $ingredientId) {
                $ingredientId = (int) $ingredientId;
                $quantity = (float) ($quantities[$i] ?? 0);
                $unit = (string) ($units[$i] ?? '');
                if ($ingredientId <= 0 || $quantity <= 0) {
                    throw new RuntimeException('Quantity bahan harus lebih besar dari 0.');
                }
                $line->bind_param('iids', $recipeId, $ingredientId, $quantity, $unit);
                $line->execute();
            }
            $db->commit();
            $transactionStarted = false;
            $message = 'Produk dan resep berhasil disimpan.';
        } elseif ($action === 'update_recipe') {
            $recipeId = (int) ($_POST['recipe_id'] ?? 0);
            $name = trim((string) ($_POST['recipe_name'] ?? ''));
            $equipment = (float) ($_POST['equipment_cost'] ?? 0);
            $operational = (float) ($_POST['operational_cost'] ?? 0);
            $ingredientIds = $_POST['ingredient_id'] ?? [];
            $quantities = $_POST['quantity'] ?? [];
            $units = $_POST['unit'] ?? [];
            $priceTier = (string) ($_POST['price_tier'] ?? 'normal');
            if ($recipeId <= 0 || $name === '' || !is_array($ingredientIds) || count($ingredientIds) === 0) {
                throw new RuntimeException('Nama produk dan minimal satu bahan wajib diisi.');
            }
            if (!array_key_exists($priceTier, ['murah' => true, 'normal' => true, 'mahal' => true])) {
                throw new RuntimeException('Pilihan harga tidak valid.');
            }
            $hpp = calculateRecipeHpp($db, $ingredientIds, $quantities, $units, $equipment, $operational);
            $recommendedPrice = $hpp * (1 + pricingMarkup($priceTier) / 100);
            $db->begin_transaction();
            $transactionStarted = true;
            $stmt = $db->prepare('UPDATE recipes SET name = ?, equipment_cost = ?, operational_cost = ?, hpp = ?, price_tier = ?, recommended_price = ? WHERE id = ?');
            $stmt->bind_param('sdddsdi', $name, $equipment, $operational, $hpp, $priceTier, $recommendedPrice, $recipeId);
            $stmt->execute();
            $deleteLines = $db->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = ?');
            $deleteLines->bind_param('i', $recipeId);
            $deleteLines->execute();
            $line = $db->prepare('INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit) VALUES (?, ?, ?, ?)');
            foreach ($ingredientIds as $i => $ingredientId) {
                $ingredientId = (int) $ingredientId;
                $quantity = (float) ($quantities[$i] ?? 0);
                $unit = (string) ($units[$i] ?? '');
                if ($ingredientId <= 0 || $quantity <= 0) {
                    throw new RuntimeException('Quantity bahan harus lebih besar dari 0.');
                }
                $line->bind_param('iids', $recipeId, $ingredientId, $quantity, $unit);
                $line->execute();
            }
            $db->commit();
            $transactionStarted = false;
            $message = 'Produk berhasil diperbarui.';
        } elseif ($action === 'delete_recipe') {
            $recipeId = (int) ($_POST['recipe_id'] ?? 0);
            if ($recipeId <= 0) {
                throw new RuntimeException('Produk tidak ditemukan.');
            }
            $db->begin_transaction();
            $transactionStarted = true;

            // Hapus detail resep secara eksplisit agar tidak meninggalkan data yatim
            // pada database lama yang belum menerapkan ON DELETE CASCADE.
            $deleteRecipeIngredients = $db->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = ?');
            $deleteRecipeIngredients->bind_param('i', $recipeId);
            $deleteRecipeIngredients->execute();

            $deleteRecipe = $db->prepare('DELETE FROM recipes WHERE id = ?');
            $deleteRecipe->bind_param('i', $recipeId);
            $deleteRecipe->execute();

            $db->commit();
            $transactionStarted = false;
            $message = 'Produk berhasil dihapus.';
        }
        if ($returnTo !== '') {
            header('Location: ' . $returnTo . '?saved=1');
            exit;
        }
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            $db->rollback();
        }
        $error = $exception->getMessage();
    }
}

$ingredients = [];
$result = $db->query('SELECT id, name, base_unit, price, price_quantity, price_unit FROM ingredients ORDER BY name');
while ($row = $result->fetch_assoc()) {
    $ingredients[] = $row;
}

$recipes = [];
$recipeResult = $db->query(
    'SELECT r.id, r.name, r.equipment_cost, r.operational_cost, r.hpp, r.price_tier, r.recommended_price,
        ri.ingredient_id, ri.quantity, ri.unit, i.name AS ingredient_name, i.base_unit,
        i.price, i.price_quantity, i.price_unit
     FROM recipes r
     LEFT JOIN recipe_ingredients ri ON ri.recipe_id = r.id
     LEFT JOIN ingredients i ON i.id = ri.ingredient_id
     ORDER BY r.created_at DESC'
);
$recipeIndex = [];
while ($row = $recipeResult->fetch_assoc()) {
    $recipeId = (int) $row['id'];
    if (!isset($recipeIndex[$recipeId])) {
        $recipeIndex[$recipeId] = [
            'id' => $recipeId,
            'name' => $row['name'],
            'equipment_cost' => (float) $row['equipment_cost'],
            'operational_cost' => (float) $row['operational_cost'],
            'saved_hpp' => (float) $row['hpp'],
            'price_tier' => (string) ($row['price_tier'] ?? 'normal'),
            'recommended_price' => (float) $row['recommended_price'],
            'ingredient_cost' => 0.0,
            'ingredients' => [],
        ];
    }
    if ($row['price'] !== null) {
        $priceUnitFactor = unitFactor((string) $row['price_unit']);
        $recipeUnitFactor = unitFactor((string) $row['unit']);
        $normalizedUnitPrice = ((float) $row['price'] / (float) $row['price_quantity']) / $priceUnitFactor;
        $normalizedQuantity = (float) $row['quantity'] * $recipeUnitFactor;
        $recipeIndex[$recipeId]['ingredient_cost'] += $normalizedQuantity * $normalizedUnitPrice;
    }
    if ($row['ingredient_id'] !== null) {
        $recipeIndex[$recipeId]['ingredients'][] = [
            'ingredient_id' => (int) $row['ingredient_id'],
            'name' => (string) $row['ingredient_name'],
            'base_unit' => (string) $row['base_unit'],
            'quantity' => (float) $row['quantity'],
            'unit' => (string) $row['unit'],
        ];
    }
}
foreach ($recipeIndex as $recipe) {
    $recipe['hpp'] = $recipe['saved_hpp'] > 0 ? $recipe['saved_hpp'] : $recipe['ingredient_cost'] + $recipe['equipment_cost'] + $recipe['operational_cost'];
    if ($recipe['recommended_price'] <= 0) {
        $recipe['recommended_price'] = $recipe['hpp'] * (1 + pricingMarkup($recipe['price_tier']) / 100);
    }
    $recipes[] = $recipe;
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Pricing: Toko Bunga Paubut Cantik</title>
    <link rel="stylesheet" href="assets/styles.css?v=3">
</head>

<body class="dashboard-page">
    <header class="topbar"><a class="brand" href="index.php"><img class="brand-logo"
                src="assets/HPP_Toko-Bunga-Paubut(200x200).webp" alt="Logo Toko Bunga Paubut Cantik"><span>Toko Bunga Paubut Cantik</span></a><span class="status-dot">● Online</span></header>
    <div class="app-shell">
        <aside class="sidebar">
            <p class="sidebar-label">MENU UTAMA</p><a class="active" href="index.php">⌂ Dashboard</a><a
                href="products.php">▣ Produk</a><a href="ingredients.php">◈ Bahan</a>
        </aside>
        <main class="container page-content">
            <nav class="mobile-nav"><a class="active" href="index.php">Dashboard</a><a href="products.php">Produk</a><a
                    href="ingredients.php">Bahan</a></nav>
            <section class="hero">
                <div>
                    <p class="eyebrow">KALKULATOR UMKM</p>
                    <h1>Hitung HPP dengan lebih yakin.</h1>
                    <p class="muted">Kelola bahan, susun resep, dan lihat rekomendasi harga jual secara langsung.</p>
                </div><a class="button primary" href="#recipe-builder">+ Buat Produk</a>
            </section>
            <?php if ($message): ?>
                <div class="alert success"><?= e($message) ?></div><?php endif; ?>
            <?php if ($error): ?>
                <div class="alert error"><?= e($error) ?></div><?php endif; ?>
            <section class="stats">
                <div class="stat"><span>Bahan tersimpan</span><strong><?= count($ingredients) ?></strong></div>
                <div class="stat"><span>Produk tersimpan</span><strong><?= count($recipes) ?></strong></div>
                <div class="stat accent"><span>Harga default</span><strong>Average</strong><small>berdasarkan harga
                        bahan</small></div>
            </section>

            <section class="workspace">
                <div class="panel" id="recipe-builder">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">RECIPE BUILDER</p>
                            <h2>Buat produk baru</h2>
                        </div><span class="badge">Live calculation</span>
                    </div>
                    <form method="post" id="recipe-form">
                        <input type="hidden" name="action" value="add_recipe">
                        <input type="hidden" name="return_to" value="products.php">
                        <input type="hidden" name="price_tier" id="price-tier-value" value="normal">
                        <label>Nama produk<input name="recipe_name" required
                                placeholder="Contoh: Kopi Susu Gula Aren"></label>
                        <div id="ingredient-lines">
                            <div class="ingredient-line">
                                <label>Bahan<select name="ingredient_id[]" class="ingredient-select" required>
                                        <option value="">Pilih bahan</option>
                                        <?php foreach ($ingredients as $ingredient): ?>
                                            <option value="<?= (int) $ingredient['id'] ?>"
                                                data-price="<?= e((string) $ingredient['price']) ?>"
                                                data-quantity="<?= e((string) $ingredient['price_quantity']) ?>"
                                                data-unit="<?= e($ingredient['price_unit']) ?>">
                                                <?= e($ingredient['name']) ?> (<?= e($ingredient['base_unit']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select></label>
                                <label>Jumlah<input name="quantity[]" class="quantity-input" type="number" min="0.001"
                                        step="0.001" value="1" required></label>
                                <label>Satuan<select name="unit[]" class="unit-select">
                                        <option>gram</option>
                                        <option>ml</option>
                                        <option>pcs</option>
                                        <option>kg</option>
                                        <option>liter</option>
                                    </select></label>
                                <button type="button" class="icon-button remove-line"
                                    aria-label="Hapus bahan">×</button>
                            </div>
                        </div>
                        <button type="button" class="button secondary" id="add-line">+ Tambah bahan</button>
                        <div class="cost-grid"><label>Biaya alat<input name="equipment_cost_display" data-money-input
                                    data-hidden-target="equipment-cost" inputmode="numeric" value="Rp 0"><input
                                    type="hidden" name="equipment_cost" id="equipment-cost"
                                    value="0"></label><label>Biaya operasional<input name="operational_cost_display"
                                    data-money-input data-hidden-target="operational-cost" inputmode="numeric"
                                    value="Rp 0"><input type="hidden" name="operational_cost" id="operational-cost"
                                    value="0"></label></div>
                        <button class="button primary full" type="submit">Simpan Produk</button>
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
                        <p class="eyebrow">REKOMENDASI HARGA JUAL</p><label for="pricing-tier">Pilih posisi harga<select
                                id="pricing-tier">
                                <option value="15">Murah · markup 15%</option>
                                <option value="30" selected>Normal · markup 30%</option>
                                <option value="50">Mahal · markup 50%</option>
                            </select></label>
                        <div class="price-result"><span>Harga jual</span><strong id="selling-price">Rp 0</strong></div>
                        <small class="muted">Harga ini adalah rekomendasi berdasarkan pilihan harga.</small>
                    </div>
                </aside>
            </section>

            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">DATA TERSIMPAN</p>
                        <h2>Produk terbaru</h2>
                    </div>
                </div><?php if (!$recipes): ?>
                    <div class="empty"><strong>Belum ada produk</strong><span>Buat produk pertama untuk mulai menghitung
                            HPP.</span></div><?php else: ?>
                    <div class="recipe-list"><?php foreach ($recipes as $recipe): ?>
                            <div class="recipe-card">
                                <div><strong><?= e($recipe['name']) ?></strong><span>Bahan
                                        <?= rupiah((float) $recipe['ingredient_cost']) ?> · Alat
                                        <?= rupiah((float) $recipe['equipment_cost']) ?> · Operasional
                                        <?= rupiah((float) $recipe['operational_cost']) ?></span></div>
                                <strong><?= rupiah((float) $recipe['hpp']) ?></strong>
                                <div class="card-actions"><a class="button secondary"
                                        href="#edit-recipe-<?= (int) $recipe['id'] ?>">Edit</a>
                                    <form method="post"
                                        onsubmit="return confirm('Hapus produk ini? Resep dan daftar bahannya akan ikut dihapus.');">
                                        <input type="hidden" name="action" value="delete_recipe"><input type="hidden"
                                            name="recipe_id" value="<?= (int) $recipe['id'] ?>"><button class="button danger"
                                            type="submit">Hapus</button>
                                    </form>
                                </div>
                            </div>
                            <details class="edit-box" id="edit-recipe-<?= (int) $recipe['id'] ?>">
                                <summary>Edit produk</summary>
                                <form method="post" class="edit-recipe-form" data-pricing-form data-hpp="<?= e((string) $recipe['hpp']) ?>"><input type="hidden" name="action"
                                        value="update_recipe"><input type="hidden" name="recipe_id"
                                        value="<?= (int) $recipe['id'] ?>"><label>Nama produk<input name="recipe_name" required
                                            value="<?= e($recipe['name']) ?>"></label>
                                    <div class="edit-ingredient-lines">
                                        <?php foreach ($recipe['ingredients'] as $recipeIngredient): ?>
                                            <div class="ingredient-line"><label>Bahan<select name="ingredient_id[]" required>
                                                        <option value="">Pilih bahan</option>
                                                        <?php foreach ($ingredients as $ingredient): ?>
                                                            <option value="<?= (int) $ingredient['id'] ?>" <?= (int) $ingredient['id'] === (int) $recipeIngredient['ingredient_id'] ? 'selected' : '' ?>><?= e($ingredient['name']) ?>
                                                                (<?= e($ingredient['base_unit']) ?>)</option><?php endforeach; ?>
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
                                                data-money-input data-hidden-target="edit-equipment-<?= (int) $recipe['id'] ?>"
                                                inputmode="numeric"
                                                value="<?= e(rupiah((float) $recipe['equipment_cost'])) ?>"><input type="hidden"
                                                name="equipment_cost" id="edit-equipment-<?= (int) $recipe['id'] ?>"
                                                value="<?= e((string) $recipe['equipment_cost']) ?>"></label><label>Biaya
                                            operasional<input name="operational_cost_display" data-money-input
                                                data-hidden-target="edit-operational-<?= (int) $recipe['id'] ?>"
                                                inputmode="numeric"
                                                value="<?= e(rupiah((float) $recipe['operational_cost'])) ?>"><input
                                                type="hidden" name="operational_cost"
                                                id="edit-operational-<?= (int) $recipe['id'] ?>"
                                                value="<?= e((string) $recipe['operational_cost']) ?>"></label></div>
                                    <div class="edit-pricing-box">
                                        <p class="eyebrow">REKOMENDASI HARGA JUAL</p>
                                        <label>Pilih posisi harga<select name="price_tier" data-pricing-tier>
                                                    <option value="murah" <?= $recipe['price_tier'] === 'murah' ? 'selected' : '' ?>>Murah · markup 15%</option>
                                                    <option value="normal" <?= $recipe['price_tier'] === 'normal' ? 'selected' : '' ?>>Normal · markup 30%</option>
                                                    <option value="mahal" <?= $recipe['price_tier'] === 'mahal' ? 'selected' : '' ?>>Mahal · markup 50%</option>
                                                </select></label>
                                        <div class="price-result"><span>Harga jual rekomendasi</span><strong data-pricing-result><?= rupiah((float) $recipe['hpp'] * 1.3) ?></strong></div>
                                    </div><button
                                        class="button primary" type="submit">Simpan perubahan</button>
                                </form>
                            </details><?php endforeach; ?>
                    </div><?php endif; ?>
            </section>
            <section class="panel compact">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">BAHAN</p>
                        <h2>Harga bahan</h2>
                    </div>
                </div><?php if (!$ingredients): ?>
                    <div class="empty"><strong>Belum ada bahan</strong><span>Tambahkan bahan pertama di bawah untuk
                            digunakan dalam resep.</span></div><?php else: ?>
                    <div class="ingredient-list"><?php foreach ($ingredients as $ingredient): ?>
                            <div class="ingredient-card">
                                <div><strong><?= e($ingredient['name']) ?></strong><span><?= rupiah((float) $ingredient['price']) ?>
                                        / <?= e($ingredient['price_quantity'] . ' ' . $ingredient['price_unit']) ?></span></div>
                                <div class="card-actions"><a class="button secondary"
                                        href="#edit-ingredient-<?= (int) $ingredient['id'] ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Hapus bahan ini?');"><input type="hidden"
                                            name="action" value="delete_ingredient"><input type="hidden" name="ingredient_id"
                                            value="<?= (int) $ingredient['id'] ?>"><button class="button danger"
                                            type="submit">Hapus</button></form>
                                </div>
                            </div>
                            <details class="edit-box" id="edit-ingredient-<?= (int) $ingredient['id'] ?>">
                                <summary>Edit bahan</summary>
                                <form method="post" class="inline-form"><input type="hidden" name="action"
                                        value="update_ingredient"><input type="hidden" name="ingredient_id"
                                        value="<?= (int) $ingredient['id'] ?>"><label>Nama<input name="name" required
                                            value="<?= e($ingredient['name']) ?>"></label><label>Satuan dasar<select
                                            name="base_unit">
                                            <option <?= $ingredient['base_unit'] === 'gram' ? 'selected' : '' ?>>gram</option>
                                            <option <?= $ingredient['base_unit'] === 'ml' ? 'selected' : '' ?>>ml</option>
                                            <option <?= $ingredient['base_unit'] === 'pcs' ? 'selected' : '' ?>>pcs</option>
                                        </select></label><label>Harga<input name="price_display" data-money-input
                                            data-hidden-target="edit-price-<?= (int) $ingredient['id'] ?>" inputmode="numeric"
                                            value="<?= e(rupiah((float) $ingredient['price'])) ?>"><input type="hidden"
                                            name="price" id="edit-price-<?= (int) $ingredient['id'] ?>"
                                            value="<?= e((string) $ingredient['price']) ?>"></label><label>Untuk jumlah<input
                                            name="price_quantity" type="number" min="0.001" step="0.001"
                                            value="<?= e((string) $ingredient['price_quantity']) ?>"
                                            required></label><label>Satuan harga<select name="price_unit">
                                            <option <?= $ingredient['price_unit'] === 'kg' ? 'selected' : '' ?>>kg</option>
                                            <option <?= $ingredient['price_unit'] === 'gram' ? 'selected' : '' ?>>gram</option>
                                            <option <?= $ingredient['price_unit'] === 'liter' ? 'selected' : '' ?>>liter</option>
                                            <option <?= $ingredient['price_unit'] === 'ml' ? 'selected' : '' ?>>ml</option>
                                            <option <?= $ingredient['price_unit'] === 'pcs' ? 'selected' : '' ?>>pcs</option>
                                        </select></label><button class="button primary" type="submit">Simpan</button></form>
                            </details><?php endforeach; ?>
                    </div><?php endif; ?>
                <details class="add-ingredient">
                    <summary>+ Tambah bahan</summary>
                    <form method="post" class="inline-form"><input type="hidden" name="action"
                            value="add_ingredient"><label>Nama<input name="name" required
                                placeholder="Kopi Arabica"></label><label>Satuan dasar<select name="base_unit">
                                <option>gram</option>
                                <option>ml</option>
                                <option>pcs</option>
                            </select></label><label>Harga<input name="price_display" data-money-input
                                data-hidden-target="new-price" inputmode="numeric" value="Rp 0" required><input
                                type="hidden" name="price" id="new-price" value="0"></label><label>Untuk jumlah<input
                                name="price_quantity" type="number" min="0.001" step="0.001" value="1"
                                required></label><label>Satuan harga<select name="price_unit">
                                <option>kg</option>
                                <option>gram</option>
                                <option>liter</option>
                                <option>ml</option>
                                <option>pcs</option>
                            </select></label><button class="button primary" type="submit">Simpan Bahan</button></form>
                </details>
            </section>
        </main>
    </div>
    <footer>· Smart Pricing: Toko Bunga Paubut Cantik ·</footer>
    <script src="assets/app.js?v=4"></script>
</body>

</html>
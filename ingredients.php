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
$ingredients = [];
$result = $db->query('SELECT i.id, i.name, i.base_unit, i.price, i.price_quantity, i.price_unit, COUNT(ri.id) AS usage_count FROM ingredients i LEFT JOIN recipe_ingredients ri ON ri.ingredient_id = i.id GROUP BY i.id ORDER BY i.name');
while ($row = $result->fetch_assoc()) {
    $ingredients[] = $row;
}
$saved = isset($_GET['saved']);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bahan · Smart Pricing: Toko Bunga Paubut Cantik</title>
    <link rel="stylesheet" href="assets/styles.css?v=3">
</head>

<body>
    <header class="topbar"><a class="brand" href="index.php"><img class="brand-logo"
                src="assets/HPP_Toko-Bunga-Paubut(200x200).webp" alt="Logo Toko Bunga Paubut Cantik"><span>Toko Bunga Paubut Cantik</span></a><span class="status-dot">● Online</span></header>
    <div class="app-shell">
        <aside class="sidebar">
            <p class="sidebar-label">MENU UTAMA</p><a href="index.php">⌂ Dashboard</a><a href="products.php">▣
                Produk</a><a class="active" href="ingredients.php">◈ Bahan</a>
        </aside>
        <main class="container page-content">
            <nav class="mobile-nav"><a href="index.php">Dashboard</a><a href="products.php">Produk</a><a class="active"
                    href="ingredients.php">Bahan</a></nav>
            <section class="page-hero">
                <div>
                    <p class="eyebrow">DATABASE BAHAN</p>
                    <h1>Harga bahan</h1>
                    <p class="muted">Kelola harga dan satuan bahan yang digunakan oleh produk.</p>
                </div><a class="button primary" href="#ingredient-form">+ Tambah bahan</a>
            </section>
            <?php if ($saved): ?>
                <div class="alert success">✓ Perubahan bahan berhasil disimpan.</div><?php endif; ?>
            <section class="panel" id="ingredient-form">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">INPUT BAHAN</p>
                        <h2>Tambah bahan baru</h2>
                    </div><span class="badge">Rupiah otomatis</span>
                </div>
                <form method="post" action="index.php" class="ingredient-form-grid"><input type="hidden" name="action"
                        value="add_ingredient"><input type="hidden" name="return_to" value="ingredients.php"><label>Nama
                        bahan<input name="name" required placeholder="Contoh: Kopi Arabica"></label><label>Satuan
                        dasar<select name="base_unit">
                            <option>gram</option>
                            <option>ml</option>
                            <option>pcs</option>
                        </select></label><label>Harga beli<input name="price_display" data-money-input
                            data-hidden-target="new-price" inputmode="numeric" value="Rp 0" required><input
                            type="hidden" name="price" id="new-price" value="0"><small class="field-help">Nominal
                            otomatis menjadi Rupiah.</small></label><label>Untuk jumlah<input name="price_quantity"
                            type="number" min="0.001" step="0.001" value="1" required><small class="field-help">Contoh:
                            harga untuk 1 kg.</small></label><label>Satuan harga<select name="price_unit">
                            <option>kg</option>
                            <option>gram</option>
                            <option>liter</option>
                            <option>ml</option>
                            <option>pcs</option>
                        </select></label><button class="button primary" type="submit">Simpan bahan</button></form>
            </section>
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">BAHAN TERSIMPAN</p>
                        <h2><?= count($ingredients) ?> bahan tersedia</h2>
                    </div>
                </div><?php if (!$ingredients): ?>
                    <div class="empty"><strong>Belum ada bahan</strong><span>Tambahkan bahan pertama untuk mulai membuat
                            produk.</span></div><?php else: ?>
                    <div class="ingredient-list"><?php foreach ($ingredients as $ingredient): ?>
                            <article class="ingredient-card">
                                <div><strong><?= e($ingredient['name']) ?></strong><span><?= rupiah((float) $ingredient['price']) ?>
                                        / <?= e((string) $ingredient['price_quantity']) ?>         <?= e($ingredient['price_unit']) ?> ·
                                        dasar <?= e($ingredient['base_unit']) ?></span></div>
                                <div class="card-actions"><button class="button secondary" type="button"
                                        data-modal-open="edit-ingredient-<?= (int) $ingredient['id'] ?>">Edit</button><?php if ((int) $ingredient['usage_count'] === 0): ?>
                                        <form method="post" action="index.php" onsubmit="return confirm('Hapus bahan ini?');"><input
                                                type="hidden" name="action" value="delete_ingredient"><input type="hidden"
                                                name="ingredient_id" value="<?= (int) $ingredient['id'] ?>"><input type="hidden"
                                                name="return_to" value="ingredients.php"><button class="button danger"
                                                type="submit">Hapus</button></form><?php else: ?><span class="usage-badge">Dipakai
                                            <?= (int) $ingredient['usage_count'] ?> resep</span><?php endif; ?>
                                </div>
                            </article>
                            <dialog class="edit-modal" id="edit-ingredient-<?= (int) $ingredient['id'] ?>">
                                <div class="modal-heading">
                                    <div>
                                        <p class="eyebrow">EDIT BAHAN</p>
                                        <h2><?= e($ingredient['name']) ?></h2>
                                    </div><button class="modal-close" type="button" data-modal-close
                                        aria-label="Tutup">×</button>
                                </div>
                                <form method="post" action="index.php" class="ingredient-modal-form"><input type="hidden"
                                        name="action" value="update_ingredient"><input type="hidden" name="return_to"
                                        value="ingredients.php"><input type="hidden" name="ingredient_id"
                                        value="<?= (int) $ingredient['id'] ?>"><label>Nama bahan<input name="name" required
                                            value="<?= e($ingredient['name']) ?>"></label>
                                    <div class="modal-form-grid"><label>Satuan dasar<select name="base_unit">
                                                <option <?= $ingredient['base_unit'] === 'gram' ? 'selected' : '' ?>>gram</option>
                                                <option <?= $ingredient['base_unit'] === 'ml' ? 'selected' : '' ?>>ml</option>
                                                <option <?= $ingredient['base_unit'] === 'pcs' ? 'selected' : '' ?>>pcs</option>
                                            </select></label><label>Harga beli<input name="price_display" data-money-input
                                                data-hidden-target="edit-price-<?= (int) $ingredient['id'] ?>"
                                                inputmode="numeric"
                                                value="<?= e(rupiah((float) $ingredient['price'])) ?>"><input type="hidden"
                                                name="price" id="edit-price-<?= (int) $ingredient['id'] ?>"
                                                value="<?= e((string) $ingredient['price']) ?>"></label><label>Untuk
                                            jumlah<input name="price_quantity" type="number" min="0.001" step="0.001"
                                                value="<?= e((string) $ingredient['price_quantity']) ?>"
                                                required></label><label>Satuan harga<select name="price_unit">
                                                <option <?= $ingredient['price_unit'] === 'kg' ? 'selected' : '' ?>>kg</option>
                                                <option <?= $ingredient['price_unit'] === 'gram' ? 'selected' : '' ?>>gram</option>
                                                <option <?= $ingredient['price_unit'] === 'liter' ? 'selected' : '' ?>>liter
                                                </option>
                                                <option <?= $ingredient['price_unit'] === 'ml' ? 'selected' : '' ?>>ml</option>
                                                <option <?= $ingredient['price_unit'] === 'pcs' ? 'selected' : '' ?>>pcs</option>
                                            </select></label></div>
                                    <div class="modal-actions"><button class="button secondary" type="button"
                                            data-modal-close>Batal</button><button class="button primary" type="submit">Simpan
                                            perubahan</button></div>
                                </form>
                            </dialog><?php endforeach; ?>
                    </div><?php endif; ?>
            </section>
        </main>
    </div>
    <footer>Smart Pricing: Toko Bunga Paubut Cantik · Kelola bahan dengan lebih rapi</footer>
    <script src="assets/app.js?v=4"></script>
</body>

</html>
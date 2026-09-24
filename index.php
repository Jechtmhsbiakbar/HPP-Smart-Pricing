<?php
declare(strict_types=1);
require __DIR__ . '/includes/actions.php';
$todayDate = date('Y-m-d');
$today = one('SELECT COALESCE(SUM(total),0) sales, COUNT(*) transactions, COALESCE(SUM(total_hpp),0) hpp FROM sales WHERE status="COMPLETED" AND DATE(sold_at)=?', 's', [$todayDate]) ?: ['sales' => 0, 'transactions' => 0, 'hpp' => 0];
$products = recipes_with_cost();
$lowStock = query_all('SELECT * FROM ingredients WHERE is_active=1 AND min_stock>0 AND stock_qty_base<=min_stock ORDER BY stock_qty_base LIMIT 8');
$best = query_all('SELECT product_name,SUM(qty) qty FROM sale_items si JOIN sales s ON s.id=si.sale_id WHERE s.status="COMPLETED" GROUP BY recipe_id,product_name ORDER BY qty DESC LIMIT 5');
layout_start('Dashboard', 'index.php');
?>
<section class="hero">
    <div>
        <p class="eyebrow">SMART PRICING POS & INVENTORY</p>
        <h1>Operasional bisnis lebih terukur.</h1>
        <p class="muted">Pantau omzet, HPP, resep, dan stok dari satu tempat.</p>
    </div><a class="button primary" href="pos.php">Buka POS</a>
</section>
<section class="stats">
    <div class="stat"><span>Penjualan hari
            ini</span><strong><?= rupiah($today['sales']) ?></strong><small><?= (int) $today['transactions'] ?>
            transaksi</small></div>
    <div class="stat"><span>Estimasi HPP terjual</span><strong><?= rupiah($today['hpp']) ?></strong><small>snapshot
            transaksi</small></div>
    <div class="stat accent"><span>Laba kotor hari
            ini</span><strong><?= rupiah((float) $today['sales'] - (float) $today['hpp']) ?></strong><small>omzet dikurangi
            HPP</small></div>
    <div class="stat"><span>Produk / bahan</span><strong><?= count($products) ?> /
            <?= count(active_ingredients()) ?></strong><small><?= count($lowStock) ?> stok menipis</small></div>
</section>
<div class="dashboard-grid">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">PRODUK TERLARIS</p>
                <h2>Penjualan sepanjang waktu</h2>
            </div><a class="button secondary" href="reports.php">Lihat laporan</a>
        </div>
        <?php if (!$best): ?>
            <div class="empty"><strong>Belum ada transaksi</strong><span>Penjualan dari POS akan muncul di sini.</span>
            </div><?php else: ?>
            <div class="simple-list"><?php foreach ($best as $item): ?>
                        <div><strong><?= e($item['product_name']) ?></strong><span><?= number_format((float) $item['qty'], 0, ',', '.') ?>
                            unit terjual</span></div><?php endforeach; ?>
            </div><?php endif; ?>
    </section>
    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">STOCK ALERT</p>
                <h2>Perlu restock</h2>
            </div><a class="button secondary" href="inventory.php">Kelola stok</a>
        </div>
        <?php if (!$lowStock): ?>
            <div class="empty"><strong>Stok aman</strong><span>Tidak ada bahan di bawah batas minimum.</span></div>
        <?php else: ?>
            <div class="simple-list"><?php foreach ($lowStock as $item): ?>
                    <div><strong><?= e($item['name']) ?></strong><span
                            class="warning-text"><?= number_format((float) $item['stock_qty_base'], 2, ',', '.') ?>
                            <?= e($item['base_unit']) ?> tersisa</span></div><?php endforeach; ?>
            </div><?php endif; ?>
    </section>
</div>
<section class="panel quick-actions">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">AKSES CEPAT</p>
            <h2>Mulai pekerjaan</h2>
        </div>
    </div>
    <div class="quick-grid"><a href="ingredients.php">+ Tambah bahan</a><a href="products.php">Buat produk & resep</a><a
            href="purchases.php">Catat pembelian</a><a href="inventory.php">Stock opname / waste</a></div>
</section>
<?php layout_end(); ?>
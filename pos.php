<?php
declare(strict_types=1);
require __DIR__ . '/includes/actions.php';
$products = recipes_with_cost();
$saved = isset($_GET['saved']);
layout_start('POS', 'pos.php');
?>
<section class="page-hero">
    <div>
        <p class="eyebrow">POINT OF SALE</p>
        <h1>Kasir penjualan</h1>
        <p class="muted">Validasi stok dan pengurangan bahan dijalankan di server dalam satu transaksi.</p>
    </div><a class="button secondary" href="reports.php">Riwayat transaksi</a>
</section>
<?php if ($saved): ?>
    <div class="alert success">Transaksi berhasil disimpan.</div><?php endif; ?>
<div class="pos-layout">
    <section class="panel">
        <div class="panel-heading">
            <h2>Produk</h2><input class="table-search" id="pos-search" placeholder="Cari produk...">
        </div>
        <div class="product-grid" id="pos-products"><?php foreach ($products as $p): ?><button type="button"
                    class="product-tile" data-id="<?= $p['id'] ?>" data-name="<?= e($p['name']) ?>"
                    data-price="<?= $p['selling_price'] ?>"><strong><?= e($p['name']) ?></strong><span><?= rupiah($p['selling_price']) ?></span><small>HPP
                        <?= rupiah($p['hpp_live']) ?></small></button><?php endforeach; ?></div>
    </section>
    <section class="panel cart-panel">
        <div class="panel-heading">
            <h2>Keranjang</h2><button type="button" class="button secondary small" id="clear-cart">Kosongkan</button>
        </div>
        <div id="cart-lines" class="cart-lines">
            <div class="empty"><strong>Belum ada item</strong><span>Pilih produk di sebelah kiri.</span></div>
        </div>
        <form method="post" id="sale-form"><?= csrf_field() ?><input type="hidden" name="action" value="sale"><input
                type="hidden" name="cart_json" id="cart-json">
            <div class="form-grid compact"><label>Diskon<input name="discount" id="sale-discount" type="number" min="0"
                        value="0"></label><label>Bayar<input name="paid" id="sale-paid" type="number" min="0" value="0"
                        required></label><label>Metode<select name="payment_method">
                        <option value="cash">Cash</option>
                        <option value="transfer">Transfer</option>
                        <option value="qris">QRIS</option>
                    </select></label></div>
            <div class="cart-total"><span>Total</span><strong id="cart-total">Rp 0</strong></div><button
                class="button primary full" type="submit">Simpan transaksi</button>
        </form>
    </section>
</div>
<?php layout_end(); ?>
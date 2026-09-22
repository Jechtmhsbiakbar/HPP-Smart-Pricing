<?php
declare(strict_types=1);
require __DIR__ . '/includes/actions.php';
$ingredients=active_ingredients(); $history=query_all('SELECT p.*,i.name FROM ingredient_purchases p JOIN ingredients i ON i.id=p.ingredient_id ORDER BY p.purchased_at DESC LIMIT 30');
layout_start('Pembelian', 'purchases.php');
?>
<section class="page-hero"><div><p class="eyebrow">INVENTORY / RESTOCK</p><h1>Pembelian bahan</h1><p class="muted">Stok, harga dasar, dan histori pembelian diperbarui secara atomic.</p></div></section>
<section class="panel"><div class="panel-heading"><div><p class="eyebrow">CATAT PEMBELIAN</p><h2>Restock</h2></div></div><form method="post" class="form-grid"><?=csrf_field()?><input type="hidden" name="action" value="purchase"><label>Bahan<select name="ingredient_id" required><?php foreach($ingredients as $i):?><option value="<?=$i['id']?>"><?=e($i['name'])?> (<?=e($i['base_unit'])?>)</option><?php endforeach;?></select></label><label>Jumlah<input name="quantity" type="number" min="0.001" step="0.001" required></label><label>Satuan pembelian<select name="unit"><?php foreach(array_keys(APP_UNITS) as $u):?><option><?=$u?></option><?php endforeach;?></select></label><label>Total harga<input name="total_cost" type="number" min="0" step="0.01" required></label><label>Catatan<input name="note" placeholder="Supplier / nomor nota"></label><div class="form-actions"><button class="button primary">Simpan pembelian</button></div></form></section>
<section class="panel"><div class="panel-heading"><h2>Riwayat pembelian</h2></div><div class="table-scroll"><table><thead><tr><th>Tanggal</th><th>Bahan</th><th>Qty</th><th>Total</th><th>Harga dasar</th></tr></thead><tbody><?php foreach($history as $p):?><tr><td><?=e($p['purchased_at'])?></td><td><?=e($p['name'])?></td><td><?=e($p['quantity'].' '.$p['unit'])?></td><td><?=rupiah($p['total_cost'])?></td><td><?=rupiah($p['unit_price_base'])?></td></tr><?php endforeach;?></tbody></table></div></section>
<?php layout_end(); ?>

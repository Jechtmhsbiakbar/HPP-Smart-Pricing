<?php
declare(strict_types=1);
require __DIR__ . '/includes/actions.php';
$ingredients = active_ingredients();
$movements = query_all('SELECT m.*,i.name,i.base_unit FROM stock_movements m JOIN ingredients i ON i.id=m.ingredient_id ORDER BY m.created_at DESC LIMIT 50');

// Helper function untuk format angka berdasarkan satuan
$fmt_unit = function ($val, $unit = '') {
    $num = (float) $val;
    if (strtolower($unit) === 'pcs') {
        return number_format($num, 0, ',', '.');
    }
    // Untuk gram/ml: hilangkan nol tak berguna di belakang koma
    return ($num == (int) $num) ? number_format($num, 0, ',', '.') : number_format($num, 2, ',', '.');
};

layout_start('Stok', 'inventory.php');
?>
<section class="page-hero">
    <div>
        <p class="eyebrow">INVENTORY CONTROL</p>
        <h1>Stok, opname & waste</h1>
        <p class="muted">Setiap perubahan stok meninggalkan kartu stok yang dapat ditelusuri.</p>
    </div>
</section>
<div class="workspace">
    <section class="panel">
        <div class="panel-heading">
            <h2>Stock opname</h2><span class="badge">Satuan dasar</span>
        </div>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="opname">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Bahan</th>
                            <th>Stok sistem</th>
                            <th>Stok fisik</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($ingredients as $i): 
                        $is_pcs = strtolower($i['base_unit']) === 'pcs';
                    ?>
                            <tr>
                                <td><?= e($i['name']) ?></td>
                                <td><?= $fmt_unit($i['stock_qty_base'], $i['base_unit']) ?> <?= e($i['base_unit']) ?></td>
                                <td>
                                    <input 
                                        name="physical[<?= $i['id'] ?>]" 
                                        type="number" 
                                        min="0" 
                                        step="<?= $is_pcs ? '1' : 'any' ?>"
                                        value="<?= e($is_pcs ? (int)$i['stock_qty_base'] : (float)$i['stock_qty_base']) ?>"
                                    >
                                </td>
                            </tr><?php endforeach; ?>
                    </tbody>
                </table>
            </div><label>Catatan<input name="note" placeholder="Alasan selisih"></label><button
                class="button primary">Simpan opname</button>
        </form>
    </section>
    <section class="panel">
        <div class="panel-heading">
            <h2>Catat waste</h2>
        </div>
        <form method="post" class="form-grid"><?= csrf_field() ?><input type="hidden" name="action"
                value="waste"><label>Bahan<select name="ingredient_id"><?php foreach ($ingredients as $i): ?>
                        <option value="<?= $i['id'] ?>"><?= e($i['name']) ?></option><?php endforeach; ?>
                </select></label><label>Jumlah<input name="quantity" type="number" min="0.001" step="any"
                    required></label><label>Satuan<select name="unit"><?php foreach (array_keys(APP_UNITS) as $u): ?>
                        <option><?= $u ?></option><?php endforeach; ?>
                </select></label><label>Alasan<input name="reason" required
                    placeholder="Rusak / kadaluarsa"></label><button class="button danger">Simpan waste</button></form>
    </section>
</div>
<section class="panel">
    <div class="panel-heading">
        <h2>Kartu stok terakhir</h2>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Bahan</th>
                    <th>Jenis</th>
                    <th>Perubahan</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody><?php foreach ($movements as $m): ?>
                    <tr>
                        <td><?= e($m['created_at']) ?></td>
                        <td><?= e($m['name']) ?></td>
                        <td><span class="badge"><?= e($m['movement_type']) ?></span></td>
                        <td class="<?= ((float) $m['quantity_base'] < 0 ? 'negative' : 'positive') ?>">
                            <?= ((float) $m['quantity_base'] >= 0 ? '+' : '') ?> <?= $fmt_unit($m['quantity_base'], $m['base_unit']) ?>
                            <?= e($m['base_unit']) ?>
                        </td>
                        <td><?= $fmt_unit($m['balance_after'], $m['base_unit']) ?> <?= e($m['base_unit']) ?></td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php layout_end(); ?>
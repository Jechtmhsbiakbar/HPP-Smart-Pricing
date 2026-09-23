<?php
declare(strict_types=1);
require __DIR__ . '/includes/actions.php';
$ingredients = active_ingredients();
$history = query_all('SELECT p.*,i.name FROM ingredient_purchases p JOIN ingredients i ON i.id=p.ingredient_id ORDER BY p.purchased_at DESC LIMIT 30');

// Helper function untuk format jumlah berdasarkan satuan
$fmt_qty = function ($val, $unit = '') {
    $num = (float) $val;
    if (strtolower($unit) === 'pcs') {
        return number_format($num, 0, ',', '.');
    }
    return ($num == (int) $num) ? number_format($num, 0, ',', '.') : number_format($num, 2, ',', '.');
};

layout_start('Pembelian', 'purchases.php');
?>
<section class="page-hero">
    <div>
        <p class="eyebrow">INVENTORY / RESTOCK</p>
        <h1>Pembelian bahan</h1>
        <p class="muted">Stok, harga dasar, dan histori pembelian diperbarui secara atomic.</p>
    </div>
</section>
<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">CATAT PEMBELIAN</p>
            <h2>Restock</h2>
        </div>
    </div>
    <form method="post" class="form-grid" id="form-purchase"><?= csrf_field() ?><input type="hidden" name="action"
            value="purchase">
        
        <label>Bahan
            <select name="ingredient_id" id="select-ingredient" onchange="syncUnitWithIngredient()" required>
                <option value="" disabled selected>-- Pilih Bahan --</option>
                <?php foreach ($ingredients as $i): ?>
                    <option value="<?= $i['id'] ?>" data-unit="<?= e($i['base_unit']) ?>">
                        <?= e($i['name']) ?> (<?= e($i['base_unit']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        
        <label>Jumlah
            <input 
                name="quantity" 
                id="input-quantity" 
                type="number" 
                min="1" 
                step="any" 
                placeholder="Contoh: 100" 
                oninput="validateQuantityInput()"
                required
            >
        </label>

        <label>Satuan pembelian
            <select name="unit" id="select-unit" onchange="validateQuantityInput()">
                <?php foreach (array_keys(APP_UNITS) as $u): ?>
                    <option value="<?= $u ?>"><?= $u ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <!-- Input Total Harga dengan Auto-Format Rupiah -->
        <label>Total harga
            <input 
                id="input-total-cost" 
                name="total_cost" 
                type="text" 
                inputmode="numeric" 
                placeholder="Rp 0" 
                oninput="formatRupiah(this)" 
                required
            >
        </label>

        <label>Catatan
            <input name="note" placeholder="Supplier / nomor nota">
        </label>

        <div class="form-actions">
            <button class="button primary">Simpan pembelian</button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-heading">
        <h2>Riwayat pembelian</h2>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Bahan</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Harga dasar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $p): ?>
                    <tr>
                        <td><?= e($p['purchased_at']) ?></td>
                        <td><?= e($p['name']) ?></td>
                        <td><?= $fmt_qty($p['quantity'], $p['unit']) ?> <?= e($p['unit']) ?></td>
                        <td><?= rupiah($p['total_cost']) ?></td>
                        <td><?= rupiah($p['unit_price_base']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
// Sinkronkan Satuan Pembelian dengan Satuan Bahan yang Dipilih
function syncUnitWithIngredient() {
    const selectIngredient = document.getElementById('select-ingredient');
    const selectUnit = document.getElementById('select-unit');

    if (!selectIngredient || !selectUnit) return;

    const selectedOption = selectIngredient.options[selectIngredient.selectedIndex];
    const baseUnit = selectedOption.getAttribute('data-unit');

    if (baseUnit) {
        selectUnit.value = baseUnit;
        validateQuantityInput();
    }
}

// Validasi & Penyesuaian Fleksibel Input Jumlah Berdasarkan Satuan
function validateQuantityInput() {
    const selectUnit = document.getElementById('select-unit');
    const inputQty = document.getElementById('input-quantity');

    if (!selectUnit || !inputQty) return;

    const currentUnit = selectUnit.value.toLowerCase();

    if (currentUnit === 'pcs') {
        // Atur aturan html5 untuk pcs
        inputQty.step = "1";
        inputQty.min = "1";

        // Jika user memasukkan desimal pada pcs, otomatis bulatkan
        if (inputQty.value && inputQty.value.includes('.')) {
            inputQty.value = Math.round(parseFloat(inputQty.value)) || 1;
        }
    } else {
        // Atur aturan html5 untuk satuan selain pcs (misal gram/ml)
        inputQty.step = "any";
        inputQty.min = "0.001";
    }
}

// Format input ke format Rupiah saat mengetik
function formatRupiah(element) {
    let rawValue = element.value.replace(/[^0-9]/g, '');
    if (rawValue === '' || rawValue === '0') {
        element.value = '';
        return;
    }
    let formatted = new Intl.NumberFormat('id-ID').format(rawValue);
    element.value = 'Rp ' + formatted;
}

// Eksekusi sebelum Submit Form
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-purchase');
    const inputTotalCost = document.getElementById('input-total-cost');
    const inputQty = document.getElementById('input-quantity');
    const selectUnit = document.getElementById('select-unit');

    // Inisialisasi awal
    syncUnitWithIngredient();

    if (form) {
        form.addEventListener('submit', function(e) {
            // 1. Pastikan jika satuan pcs, nilainya benar-benar dibulatkan sebelum dikirim
            if (selectUnit && selectUnit.value.toLowerCase() === 'pcs' && inputQty) {
                inputQty.value = Math.round(parseFloat(inputQty.value)) || 1;
            }

            // 2. Bersihkan format "Rp" & titik dari input total_cost
            if (inputTotalCost) {
                inputTotalCost.value = inputTotalCost.value.replace(/[^0-9]/g, '');
            }
        });
    }
});
</script>

<?php layout_end(); ?>
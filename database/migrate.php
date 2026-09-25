<?php
/**
 * Additive migration helper. Run from repository root with:
 * php database/migrate.php
 */
declare(strict_types=1);
require __DIR__ . '/../config.php';
$columns = [
    'ingredients' => [
        'category_id INT UNSIGNED NULL', 'purchase_quantity DECIMAL(14,4) NOT NULL DEFAULT 1',
        'purchase_unit VARCHAR(20) NOT NULL DEFAULT "gram"', 'conversion_to_base DECIMAL(18,6) NOT NULL DEFAULT 1',
        'unit_price_base DECIMAL(14,4) NOT NULL DEFAULT 0', 'stock_qty_base DECIMAL(18,6) NOT NULL DEFAULT 0',
        'min_stock DECIMAL(18,6) NOT NULL DEFAULT 0', 'max_stock DECIMAL(18,6) NOT NULL DEFAULT 0',
        'is_active TINYINT(1) NOT NULL DEFAULT 1', 'updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ],
    'recipes' => [
        'category_id INT UNSIGNED NULL', 'packaging_cost DECIMAL(14,2) NOT NULL DEFAULT 0',
        'selling_price DECIMAL(14,2) NOT NULL DEFAULT 0', 'track_inventory TINYINT(1) NOT NULL DEFAULT 1',
        'is_active TINYINT(1) NOT NULL DEFAULT 1', 'updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ],
    'units' => [
        'quantity_type ENUM("decimal","integer") NOT NULL DEFAULT "decimal"',
    ],
];
foreach ($columns as $table => $definitions) {
    foreach ($definitions as $definition) {
        $name = preg_replace('/\s.*/', '', $definition);
        $check = $db->prepare('SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
        $check->bind_param('ss', $table, $name); $check->execute();
        if ((int) $check->get_result()->fetch_assoc()['c'] === 0) $db->query('ALTER TABLE `' . $table . '` ADD COLUMN `' . $name . '` ' . substr($definition, strlen($name)));
    }
}
$sql = file_get_contents(__DIR__ . '/../database.sql');
if ($sql === false || !$db->multi_query($sql)) throw new RuntimeException('Schema migration gagal: ' . $db->error);
while ($db->more_results() && $db->next_result()) { /* drain result sets */ }
$db->query("UPDATE ingredients SET conversion_to_base=CASE price_unit WHEN 'kg' THEN 1000 WHEN 'liter' THEN 1000 ELSE 1 END WHERE conversion_to_base=1");
$db->query("UPDATE ingredients SET unit_price_base=(price/NULLIF(price_quantity,0))/CASE price_unit WHEN 'kg' THEN 1000 WHEN 'liter' THEN 1000 ELSE 1 END*CASE base_unit WHEN 'gram' THEN 1 WHEN 'ml' THEN 1 ELSE 1 END WHERE unit_price_base=0 AND price_quantity>0");
$db->query("UPDATE units SET quantity_type='integer' WHERE code IN ('pcs', 'unit')");
$legacyFractionalQueries = [
    'ingredients' => "SELECT COUNT(*) c FROM ingredients WHERE base_unit IN ('pcs', 'unit') AND (stock_qty_base <> FLOOR(stock_qty_base) OR min_stock <> FLOOR(min_stock) OR max_stock <> FLOOR(max_stock))",
    'recipe_ingredients' => "SELECT COUNT(*) c FROM recipe_ingredients WHERE unit IN ('pcs', 'unit') AND quantity <> FLOOR(quantity)",
    'ingredient_purchases' => "SELECT COUNT(*) c FROM ingredient_purchases WHERE unit IN ('pcs', 'unit') AND (quantity <> FLOOR(quantity) OR quantity_base <> FLOOR(quantity_base))",
    'wastes' => "SELECT COUNT(*) c FROM wastes WHERE unit IN ('pcs', 'unit') AND (quantity <> FLOOR(quantity) OR quantity_base <> FLOOR(quantity_base))",
];
foreach ($legacyFractionalQueries as $table => $query) {
    $result = $db->query($query);
    if (!$result)
        throw new RuntimeException('Gagal memeriksa data lama pada tabel ' . $table . ': ' . $db->error);
    $count = (int) $result->fetch_assoc()['c'];
    if ($count > 0)
        throw new RuntimeException('Migrasi dihentikan: ditemukan ' . $count . ' data pecahan pada satuan pcs/unit di tabel ' . $table . '. Perbaiki data tersebut setelah backup, lalu jalankan migration kembali.');
}
$constraints = [
    'ingredients' => ['chk_ingredient_stock_integer' => "CHECK (base_unit NOT IN ('pcs', 'unit') OR (stock_qty_base = FLOOR(stock_qty_base) AND min_stock = FLOOR(min_stock) AND max_stock = FLOOR(max_stock)))"],
    'recipe_ingredients' => ['chk_recipe_quantity_integer' => "CHECK (unit NOT IN ('pcs', 'unit') OR quantity = FLOOR(quantity))"],
    'ingredient_purchases' => [
        'chk_purchase_quantity_integer' => "CHECK (unit NOT IN ('pcs', 'unit') OR quantity = FLOOR(quantity))",
        'chk_purchase_base_quantity_integer' => "CHECK (unit NOT IN ('pcs', 'unit') OR quantity_base = FLOOR(quantity_base))",
    ],
    'wastes' => [
        'chk_waste_quantity_integer' => "CHECK (unit NOT IN ('pcs', 'unit') OR quantity = FLOOR(quantity))",
        'chk_waste_base_quantity_integer' => "CHECK (unit NOT IN ('pcs', 'unit') OR quantity_base = FLOOR(quantity_base))",
    ],
];
foreach ($constraints as $table => $definitions) {
    foreach ($definitions as $name => $definition) {
        $check = $db->prepare('SELECT COUNT(*) c FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME=?');
        $check->bind_param('ss', $table, $name);
        $check->execute();
        if ((int) $check->get_result()->fetch_assoc()['c'] === 0)
            $db->query('ALTER TABLE `' . $table . '` ADD CONSTRAINT `' . $name . '` ' . $definition);
    }
}
echo "Migration selesai.\n";

<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require_auth();
$type = (string) ($_GET['type'] ?? 'ingredients');
$queries = [
    'ingredients' => ['ingredients.csv', ['Nama', 'Satuan', 'Harga dasar', 'Stok'], 'SELECT name,base_unit,unit_price_base,stock_qty_base FROM ingredients ORDER BY name'],
    'products' => ['products.csv', ['Produk', 'HPP', 'Harga jual'], 'SELECT name,hpp,selling_price FROM recipes WHERE is_active=1 ORDER BY name'],
    'movements' => ['stock-movements.csv', ['Waktu', 'Bahan', 'Jenis', 'Perubahan', 'Saldo'], 'SELECT m.created_at,i.name,m.movement_type,m.quantity_base,m.balance_after FROM stock_movements m JOIN ingredients i ON i.id=m.ingredient_id ORDER BY m.created_at DESC'],
];
if (!isset($queries[$type])) {
    http_response_code(404);
    exit('Export tidak ditemukan.');
}
[$filename, $header, $sql] = $queries[$type];
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);
$out = fopen('php://output', 'w');
fputcsv($out, $header);
foreach (query_all($sql) as $row)
    fputcsv($out, array_values($row));
fclose($out);

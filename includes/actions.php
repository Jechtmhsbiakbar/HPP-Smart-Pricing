<?php
declare(strict_types=1);
require_once __DIR__ . '/app.php';

function post_float(string $key, float $default = 0): float
{
    $raw = $_POST[$key] ?? '';
    if (is_string($raw)) {
        // Membersihkan karakter selain angka, minus, dan titik desimal (misal: "Rp 25.000" -> "25000")
        $raw = preg_replace('/[^0-9.-]/', '', $raw);
    }
    $value = filter_var($raw, FILTER_VALIDATE_FLOAT);
    return $value === false ? $default : (float) $value;
}
function post_quantity(string $key, string $unit, string $label = 'Jumlah'): float
{
    $raw = $_POST[$key] ?? '';
    if (!is_string($raw) || !preg_match('/^\d+(?:\.\d+)?$/', trim($raw)))
        throw new RuntimeException($label . ' harus berupa angka yang valid.');
    return assert_quantity((float) $raw, $unit, $label);
}

function post_array(string $key): array
{
    return is_array($_POST[$key] ?? null) ? $_POST[$key] : [];
}

function save_ingredient(): void
{
    global $db;
    $name = trim((string) ($_POST['name'] ?? ''));
    $base = (string) ($_POST['base_unit'] ?? 'gram');
    $price = post_float('price');
    $pqty = post_float('price_quantity', 1);
    $punit = (string) ($_POST['price_unit'] ?? $base);

    if ($name === '' || $price < 0 || $pqty <= 0) {
        throw new RuntimeException('Nama, jumlah pembelian, dan harga harus valid.');
    }

    unit_info($base);
    unit_info($punit);

    // Validasi pcs: Pastikan jika satuan pembelian atau dasar adalah pcs, nilainya harus bilangan bulat
    if ($punit === 'pcs' && floor($pqty) !== $pqty) {
        throw new RuntimeException('Jumlah pembelian untuk satuan pcs harus berupa bilangan bulat.');
    }

    $minStock = post_float('min_stock');
    $maxStock = post_float('max_stock');

    if (is_count_unit($base)) {
        if (floor($minStock) !== $minStock || floor($maxStock) !== $maxStock) {
            throw new RuntimeException('Stok minimum dan maksimum untuk bahan pcs harus berupa bilangan bulat.');
        }
    }

    $unitPrice = ($price / $pqty) / unit_factor($punit) * unit_factor($base);
    $id = (int) ($_POST['ingredient_id'] ?? 0);
    $values = [$name, $base, $price, $pqty, $punit, $pqty, $punit, unit_factor($punit), $unitPrice, $minStock, $maxStock];

    if ($id > 0) {
        execute_sql('UPDATE ingredients SET name=?,base_unit=?,price=?,price_quantity=?,price_unit=?,purchase_quantity=?,purchase_unit=?,conversion_to_base=?,unit_price_base=?,min_stock=?,max_stock=?,updated_at=NOW() WHERE id=?', 'ssddsdsddddi', array_merge($values, [$id]));
    } else {
        execute_sql('INSERT INTO ingredients(name,base_unit,price,price_quantity,price_unit,purchase_quantity,purchase_unit,conversion_to_base,unit_price_base,min_stock,max_stock,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,1)', 'ssddsdsdddd', $values);
    }

    log_action($id ? 'UPDATE_INGREDIENT' : 'CREATE_INGREDIENT', $name);
    flash('Bahan berhasil disimpan.');
    redirect_to((string) ($_POST['return_to'] ?? 'ingredients.php'));
}

function save_recipe(): void
{
    global $db;
    $name = trim((string) ($_POST['recipe_name'] ?? ''));
    $ids = post_array('ingredient_id');
    $qtys = post_array('quantity');
    $equipment = post_float('equipment_cost');
    $operational = post_float('operational_cost');
    $packaging = post_float('packaging_cost');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $tier = (string) ($_POST['price_tier'] ?? 'normal');
    $id = (int) ($_POST['recipe_id'] ?? 0);
    $wasUpdate = $id > 0;
    $category = one('SELECT id FROM categories WHERE id=? AND category_type="product" AND is_active=1', 'i', [$categoryId]);
    if ($name === '' || !$ids || !$category)
        throw new RuntimeException('Nama produk, kategori, dan minimal satu bahan wajib diisi.');
    if (!in_array($tier, ['murah', 'normal', 'mahal'], true))
        throw new RuntimeException('Tier harga tidak valid.');
    $lines = [];
    $cost = $equipment + $operational + $packaging;
    foreach ($ids as $i => $rawId) {
        $ingredientId = (int) $rawId;
        $quantity = (float) ($qtys[$i] ?? 0);
        if ($ingredientId <= 0 || $quantity <= 0)
            throw new RuntimeException('Bahan dan jumlah harus valid.');
        $ingredient = one('SELECT * FROM ingredients WHERE id=? AND is_active=1', 'i', [$ingredientId]);
        if (!$ingredient)
            throw new RuntimeException('Bahan tidak ditemukan.');
        $baseUnit = (string) $ingredient['base_unit'];
        unit_info($baseUnit);
        assert_quantity($quantity, $baseUnit, 'Jumlah bahan');
        $baseQuantity = $quantity;
        $cost += $baseQuantity * (float) $ingredient['unit_price_base'];
        $lines[] = [$ingredientId, $baseQuantity, $baseUnit];
    }

    // Hitung harga rekomendasi berdasarkan tier markup
    $recommendedPrice = $cost * (1 + markup_for($tier) / 100);
    // Logika pemilihan selling_price: manual vs otomatis
    $priceMode = (string) ($_POST['price_mode'] ?? 'auto');
    $customSellingPrice = post_float('selling_price');

    if ($priceMode === 'manual' && $customSellingPrice > 0) {
        $sellingPrice = $customSellingPrice;
    } else {
        $sellingPrice = $recommendedPrice;
    }

    $db->begin_transaction();
    $started = true;
    try {
        if ($id > 0) {
            execute_sql('UPDATE recipes SET name=?,category_id=?,equipment_cost=?,operational_cost=?,packaging_cost=?,hpp=?,price_tier=?,recommended_price=?,selling_price=?,updated_at=NOW() WHERE id=?', 'siddddsddi', [$name, $categoryId, $equipment, $operational, $packaging, $cost, $tier, $recommendedPrice, $sellingPrice, $id]);
            execute_sql('DELETE FROM recipe_ingredients WHERE recipe_id=?', 'i', [$id]);
        } else {
            execute_sql('INSERT INTO recipes(name,category_id,equipment_cost,operational_cost,packaging_cost,hpp,price_tier,recommended_price,selling_price) VALUES(?,?,?,?,?,?,?,?,?)', 'siddddsdd', [$name, $categoryId, $equipment, $operational, $packaging, $cost, $tier, $recommendedPrice, $sellingPrice]);
            $id = $db->insert_id;
        }
        foreach ($lines as $line)
            execute_sql('INSERT INTO recipe_ingredients(recipe_id,ingredient_id,quantity,unit) VALUES(?,?,?,?)', 'iids', [$id, $line[0], $line[1], $line[2]]);
        $db->commit();
        $started = false;
    } catch (Throwable $e) {
        if ($started)
            $db->rollback();
        throw $e;
    }
    log_action($wasUpdate ? 'UPDATE_RECIPE' : 'CREATE_RECIPE', $name);
    flash('Produk dan resep berhasil disimpan.');
    redirect_to((string) ($_POST['return_to'] ?? 'products.php'));
}

function save_user(): void
{
    $id = (int) ($_POST['user_id'] ?? 0);
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = (string) ($_POST['role'] ?? 'KASIR');
    $isActive = (int) ($_POST['is_active'] ?? 1) === 1 ? 1 : 0;
    $currentUser = current_user();

    if ($username === '' || strlen($username) > 80)
        throw new RuntimeException('Username wajib diisi dan maksimal 80 karakter.');
    if (!preg_match('/^[\p{L}\p{N}._@-]+$/u', $username))
        throw new RuntimeException('Username hanya boleh berisi huruf, angka, titik, garis bawah, tanda hubung, atau @.');
    if (!in_array($role, ['ADMIN', 'KASIR'], true))
        throw new RuntimeException('Role user tidak valid.');
    if ($id === 0 && strlen($password) < 8)
        throw new RuntimeException('Password user baru minimal 8 karakter.');
    if ($password !== '' && strlen($password) < 8)
        throw new RuntimeException('Password minimal 8 karakter.');
    if ($currentUser && $id === (int) $currentUser['id'] && $isActive !== 1)
        throw new RuntimeException('Akun yang sedang digunakan tidak dapat dinonaktifkan.');

    if (one('SELECT id FROM users WHERE username=? AND id<>?', 'si', [$username, $id]))
        throw new RuntimeException('Username sudah digunakan.');

    if ($id > 0) {
        if (!one('SELECT id FROM users WHERE id=?', 'i', [$id]))
            throw new RuntimeException('User tidak ditemukan.');
        if ($password !== '')
            execute_sql('UPDATE users SET username=?,password_hash=?,role=?,is_active=?,updated_at=NOW() WHERE id=?', 'sssii', [$username, password_hash($password, PASSWORD_DEFAULT), $role, $isActive, $id]);
        else
            execute_sql('UPDATE users SET username=?,role=?,is_active=?,updated_at=NOW() WHERE id=?', 'ssii', [$username, $role, $isActive, $id]);
        if ($currentUser && $id === (int) $currentUser['id']) {
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['role'] = $role;
        }
        log_action('UPDATE_USER', $username);
        flash('User berhasil diperbarui.');
    } else {
        execute_sql('INSERT INTO users(username,password_hash,role,is_active) VALUES(?,?,?,?)', 'sssi', [$username, password_hash($password, PASSWORD_DEFAULT), $role, $isActive]);
        log_action('CREATE_USER', $username);
        flash('User berhasil ditambahkan.');
    }
    redirect_to('users.php');
}

function deactivate_user(): void
{
    $id = (int) ($_POST['user_id'] ?? 0);
    $currentUser = current_user();
    if ($id <= 0 || ($currentUser && $id === (int) $currentUser['id']))
        throw new RuntimeException('Akun yang sedang digunakan tidak dapat dihapus.');
    $user = one('SELECT id,username,role,is_active FROM users WHERE id=?', 'i', [$id]);
    if (!$user)
        throw new RuntimeException('User tidak ditemukan.');
    if ((string) $user['role'] === 'ADMIN' && (int) $user['is_active'] === 1) {
        $activeAdmins = (int) (one('SELECT COUNT(*) AS total FROM users WHERE role="ADMIN" AND is_active=1')['total'] ?? 0);
        if ($activeAdmins <= 1)
            throw new RuntimeException('User ini adalah admin aktif terakhir dan tidak dapat dihapus.');
    }
    execute_sql('UPDATE users SET is_active=0,updated_at=NOW() WHERE id=?', 'i', [$id]);
    log_action('DEACTIVATE_USER', (string) $user['username']);
    flash('User dinonaktifkan. Data histori tetap dipertahankan.');
    redirect_to('users.php');
}

function purchase(): void
{
    global $db;
    $ingredientId = (int) ($_POST['ingredient_id'] ?? 0);
    $unit = (string) ($_POST['unit'] ?? '');
    $total = post_float('total_cost');
    if ($ingredientId <= 0 || $total < 0)
        throw new RuntimeException('Data pembelian tidak valid.');
    $db->begin_transaction();
    $started = true;
    try {
        $ingredient = one('SELECT * FROM ingredients WHERE id=? FOR UPDATE', 'i', [$ingredientId]);
        if (!$ingredient)
            throw new RuntimeException('Bahan tidak ditemukan.');
        if (!isset(APP_UNITS[$unit]))
            throw new RuntimeException('Satuan pembelian tidak didukung.');
        if (unit_info($unit)['kind'] !== unit_info((string) $ingredient['base_unit'])['kind'])
            throw new RuntimeException('Satuan pembelian harus sejenis dengan satuan dasar bahan.');
        $quantity = post_quantity('quantity', $unit, 'Jumlah pembelian');
        $base = convert_qty($quantity, $unit, (string) $ingredient['base_unit']);
        $priceBase = $base > 0 ? $total / $base : 0;
        execute_sql('INSERT INTO ingredient_purchases(ingredient_id,purchased_at,quantity,unit,quantity_base,total_cost,unit_price_base,note) VALUES(?,NOW(),?,?,?,?,?,?)', 'idsddds', [$ingredientId, $quantity, $unit, $base, $total, $priceBase, (string) ($_POST['note'] ?? '')]);
        execute_sql('UPDATE ingredients SET price=?,price_quantity=?,price_unit=?,unit_price_base=?,stock_qty_base=stock_qty_base+?,updated_at=NOW() WHERE id=?', 'ddsddi', [$total, $quantity, $unit, $priceBase, $base, $ingredientId]);
        $new = one('SELECT stock_qty_base FROM ingredients WHERE id=?', 'i', [$ingredientId]);
        movement($ingredientId, 'PURCHASE', $base, (float) $new['stock_qty_base'], 'PURCHASE', 'Pembelian bahan');
        $db->commit();
        $started = false;
    } catch (Throwable $e) {
        if ($started)
            $db->rollback();
        throw $e;
    }
    log_action('PURCHASE', (string) $ingredientId);
    flash('Pembelian disimpan dan stok bertambah.');
    redirect_to('purchases.php');
}

function sale(): void
{
    global $db;
    $cart = json_decode((string) ($_POST['cart_json'] ?? '[]'), true);
    if (!is_array($cart) || !$cart)
        throw new RuntimeException('Keranjang masih kosong.');
    $discount = post_float('discount');
    $paid = post_float('paid');
    $method = (string) ($_POST['payment_method'] ?? 'cash');
    if (!in_array($method, ['cash', 'transfer', 'qris'], true))
        throw new RuntimeException('Metode pembayaran tidak valid.');
    $db->begin_transaction();
    $started = true;
    try {
        $items = [];
        $subtotal = 0;
        $hpp = 0;
        $shortages = [];
        foreach ($cart as $line) {
            $recipeId = (int) ($line['id'] ?? 0);
            $qty = (float) ($line['qty'] ?? 0);
            if ($recipeId <= 0 || $qty <= 0)
                throw new RuntimeException('Item POS tidak valid.');
            $recipe = one('SELECT * FROM recipes WHERE id=? AND is_active=1 FOR UPDATE', 'i', [$recipeId]);
            if (!$recipe)
                throw new RuntimeException('Produk tidak ditemukan.');
            if ((int) $recipe['track_inventory'] === 1)
                $shortages = array_merge($shortages, ensure_stock($recipeId, $qty));
            $price = (float) ($recipe['selling_price'] ?: $recipe['recommended_price'] ?: $recipe['hpp']);
            $lineHpp = calculate_recipe_hpp($recipeId);
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;
            $hpp += $lineHpp * $qty;
            $items[] = [$recipe, $qty, $price, $lineTotal, $lineHpp, recipe_items($recipeId)];
        }
        if ($shortages && setting('stock_mode', 'strict') === 'strict') {
            $parts = [];
            foreach ($shortages as $missing)
                $parts[] = $missing['name'] . ' (kurang ' . number_format($missing['missing'], 2, ',', '.') . ' ' . $missing['unit'] . ')';
            throw new RuntimeException('Stok tidak mencukupi: ' . implode(', ', $parts));
        }
        if ($discount < 0 || $discount > $subtotal)
            throw new RuntimeException('Diskon harus berada di antara Rp 0 dan subtotal.');
        $total = $subtotal - $discount;
        if ($paid < $total)
            throw new RuntimeException('Pembayaran kurang dari total transaksi.');
        $invoice = next_invoice();
        execute_sql('INSERT INTO sales(invoice_no,sold_at,subtotal,discount,total,paid,change_amount,payment_method,total_hpp,cashier) VALUES(?,?,?,?,?,?,?,?,?,?)', 'ssdddddsds', [$invoice, date('Y-m-d H:i:s'), $subtotal, $discount, $total, $paid, max(0, $paid - $total), $method, $hpp, 'Admin']);
        $saleId = $db->insert_id;
        foreach ($items as $item) {
            $recipe = $item[0];
            $snapshot = json_encode($item[5], JSON_UNESCAPED_UNICODE);
            execute_sql('INSERT INTO sale_items(sale_id,recipe_id,product_name,qty,unit_price,line_total,hpp_unit,hpp_total,recipe_snapshot) VALUES(?,?,?,?,?,?,?,?,?)', 'iisddddds', [$saleId, $recipe['id'], $recipe['name'], $item[1], $item[2], $item[3], $item[4], $item[4] * $item[1], $snapshot]);
            if ((int) $recipe['track_inventory'] === 1)
                foreach ($item[5] as $recipeItem)
                    update_stock((int) $recipeItem['ingredient_id'], -(float) $recipeItem['quantity'] * $item[1], 'SALE', $invoice, 'Penjualan ' . $recipe['name']);
        }
        $db->commit();
        $started = false;
    } catch (Throwable $e) {
        if ($started)
            $db->rollback();
        throw $e;
    }
    log_action('SALE', $invoice);
    flash('Transaksi ' . $invoice . ' berhasil disimpan.');
    redirect_to('pos.php?saved=1');
}

function void_sale(): void
{
    global $db;
    $id = (int) ($_POST['sale_id'] ?? 0);
    $db->begin_transaction();
    $started = true;
    try {
        $sale = one('SELECT * FROM sales WHERE id=? AND status="COMPLETED" FOR UPDATE', 'i', [$id]);
        if (!$sale)
            throw new RuntimeException('Transaksi tidak ditemukan atau sudah dibatalkan.');
        foreach (query_all('SELECT * FROM sale_items WHERE sale_id=?', 'i', [$id]) as $item) {
            foreach (json_decode((string) $item['recipe_snapshot'], true) ?: [] as $recipeItem)
                update_stock((int) $recipeItem['ingredient_id'], (float) $recipeItem['quantity'] * (float) $item['qty'], 'RETURN', $sale['invoice_no'], 'Void transaksi');
        }
        execute_sql('UPDATE sales SET status="VOID" WHERE id=?', 'i', [$id]);
        $db->commit();
        $started = false;
    } catch (Throwable $e) {
        if ($started)
            $db->rollback();
        throw $e;
    }
    flash('Transaksi dibatalkan dan stok dikembalikan.');
    redirect_to('reports.php');
}

function handle_action(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        return;
    require_auth();
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if (in_array($action, ['add_ingredient', 'update_ingredient', 'delete_ingredient', 'add_recipe', 'update_recipe', 'delete_recipe', 'purchase', 'void_sale', 'waste', 'opname', 'settings', 'save_user', 'delete_user'], true))
        require_role('ADMIN');
    switch ($action) {
        case 'add_ingredient':
        case 'update_ingredient':
            save_ingredient();
            break;
        case 'delete_ingredient':
            execute_sql('UPDATE ingredients SET is_active=0 WHERE id=?', 'i', [(int) $_POST['ingredient_id']]);
            flash('Bahan dinonaktifkan.');
            redirect_to((string) ($_POST['return_to'] ?? 'ingredients.php'));
            break;
        case 'delete_recipe':
            execute_sql('UPDATE recipes SET is_active=0,updated_at=NOW() WHERE id=?', 'i', [(int) $_POST['recipe_id']]);
            flash('Produk dinonaktifkan.');
            redirect_to((string) ($_POST['return_to'] ?? 'products.php'));
            break;
        case 'add_recipe':
        case 'update_recipe':
            save_recipe();
            break;
        case 'purchase':
            purchase();
            break;
        case 'sale':
            sale();
            break;
        case 'void_sale':
            void_sale();
            break;
        case 'waste':
            $id = (int) $_POST['ingredient_id'];
            $ingredient = one('SELECT * FROM ingredients WHERE id=?', 'i', [$id]);
            if (!$ingredient)
                throw new RuntimeException('Bahan dan jumlah waste harus valid.');
            $unit = (string) $ingredient['base_unit'];
            $quantity = post_quantity('quantity', $unit, 'Jumlah waste');
            $base = $quantity;
            global $db;
            $db->begin_transaction();
            $started = true;
            try {
                update_stock($id, -$base, 'WASTE', 'WASTE', (string) $_POST['reason']);
                execute_sql('INSERT INTO wastes(ingredient_id,quantity,unit,quantity_base,reason) VALUES(?,?,?,?,?)', 'idsds', [$id, $quantity, $unit, $base, (string) $_POST['reason']]);
                $db->commit();
                $started = false;
            } catch (Throwable $e) {
                if ($started)
                    $db->rollback();
                throw $e;
            }
            flash('Waste dicatat.');
            redirect_to('inventory.php');
            break;
        case 'opname':
            $physical = post_array('physical');
            global $db;
            $db->begin_transaction();
            $started = true;
            try {
                execute_sql('INSERT INTO stock_opnames(opname_date,note) VALUES(NOW(),?)', 's', [(string) ($_POST['note'] ?? '')]);
                $opnameId = $db->insert_id;
                foreach ($physical as $id => $value) {
                    $ingredient = one('SELECT * FROM ingredients WHERE id=? FOR UPDATE', 'i', [(int) $id]);
                    if (!$ingredient)
                        continue;
                    $baseUnit = (string) $ingredient['base_unit'];
                    if (!is_string($value) || !preg_match('/^\d+(?:\.\d+)?$/', trim($value)))
                        throw new RuntimeException('Stok fisik harus berupa angka yang valid.');
                    $physicalQuantity = assert_quantity((float) $value, $baseUnit, 'Stok fisik', true);
                    $difference = $physicalQuantity - (float) $ingredient['stock_qty_base'];
                    execute_sql('INSERT INTO stock_opname_items(opname_id,ingredient_id,system_qty_base,physical_qty_base,difference_base) VALUES(?,?,?,?,?)', 'iiddd', [$opnameId, $id, $ingredient['stock_qty_base'], $physicalQuantity, $difference]);
                    if (abs($difference) > 0.000001)
                        update_stock((int) $id, $difference, 'STOCK_OPNAME', 'OPNAME-' . $opnameId, 'Stock opname');
                }
                $db->commit();
                $started = false;
            } catch (Throwable $e) {
                if ($started)
                    $db->rollback();
                throw $e;
            }
            flash('Stock opname disimpan.');
            redirect_to('inventory.php');
            break;
        case 'settings':
            foreach ($_POST['setting'] ?? [] as $key => $value)
                execute_sql('INSERT INTO settings(`key`,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=?', 'sss', [$key, (string) $value, (string) $value]);
            flash('Pengaturan disimpan.');
            redirect_to('settings.php');
            break;
        case 'save_user':
            save_user();
            break;
        case 'delete_user':
            deactivate_user();
            break;
        default:
            throw new RuntimeException('Aksi tidak dikenali.');
    }
}
try {
    handle_action();
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    flash($exception->getMessage(), 'error');
}
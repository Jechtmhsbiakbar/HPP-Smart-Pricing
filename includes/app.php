<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const APP_UNITS = [
    'gram' => ['base' => 'gram', 'factor' => 1, 'kind' => 'weight'],
    'kg' => ['base' => 'gram', 'factor' => 1000, 'kind' => 'weight'],
    'ml' => ['base' => 'ml', 'factor' => 1, 'kind' => 'volume'],
    'liter' => ['base' => 'ml', 'factor' => 1000, 'kind' => 'volume'],
    'pcs' => ['base' => 'pcs', 'factor' => 1, 'kind' => 'count'],
    'unit' => ['base' => 'pcs', 'factor' => 1, 'kind' => 'count'],
    'cm' => ['base' => 'cm', 'factor' => 1, 'kind' => 'length'],
    'meter' => ['base' => 'cm', 'factor' => 100, 'kind' => 'length'],
];

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function rupiah($value): string
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}
function redirect_to(string $url): void
{
    header('Location: ' . $url);
    exit;
}
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = [$type, $message];
}
function take_flash(): ?array
{
    $v = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $v;
}
function csrf_token(): string
{
    if (empty($_SESSION['csrf']))
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function verify_csrf(): void
{
    if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? '')))
        throw new RuntimeException('Sesi formulir kedaluwarsa. Silakan coba lagi.');
}
function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}
function require_auth(): void
{
    if (!current_user()) {
        $target = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
        redirect_to('login.php?next=' . rawurlencode($target));
    }
}
function require_role(string ...$roles): void
{
    require_auth();
    $user = current_user();
    if (!$user || !in_array((string) $user['role'], $roles, true)) {
        http_response_code(403);
        exit('Akses tidak diizinkan.');
    }
}
function unit_info(string $unit): array
{
    if (!isset(APP_UNITS[$unit]))
        throw new RuntimeException('Satuan tidak didukung.');
    return APP_UNITS[$unit];
}
function unit_factor(string $unit): float
{
    return (float) unit_info($unit)['factor'];
}
function unit_base(string $unit): string
{
    return (string) unit_info($unit)['base'];
}
function convert_qty(float $qty, string $from, string $to): float
{
    $a = unit_info($from);
    $b = unit_info($to);
    if ($a['kind'] !== $b['kind'])
        throw new RuntimeException('Satuan tidak dapat dikonversi karena jenisnya berbeda.');
    return $qty * $a['factor'] / $b['factor'];
}
function markup_for(string $tier): float
{
    $defaults = ['murah' => 15, 'normal' => 30, 'mahal' => 50];
    $key = 'markup_' . $tier;
    $value = setting($key, (string) ($defaults[$tier] ?? 30));
    return max(0, (float) $value);
}
function setting(string $key, string $default = ''): string
{
    global $db;
    static $cache = [];
    if (array_key_exists($key, $cache))
        return $cache[$key];
    try {
        $s = $db->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
        $s->bind_param('s', $key);
        $s->execute();
        $row = $s->get_result()->fetch_assoc();
    } catch (Throwable $e) {
        $row = null;
    }
    return $cache[$key] = $row ? (string) $row['value'] : $default;
}

$applicationTimezone = setting('timezone', 'Asia/Jakarta');
if (!in_array($applicationTimezone, timezone_identifiers_list(), true))
    $applicationTimezone = 'Asia/Jakarta';
date_default_timezone_set($applicationTimezone);

function app_name(): string
{
    return setting('business_name', 'HPP Smart Pricing');
}
function query_all(string $sql, string $types = '', array $params = []): array
{
    global $db;
    $stmt = $db->prepare($sql);
    if ($types !== '') {
        $refs = [$types];
        foreach ($params as $k => $v)
            $refs[] = &$params[$k];
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
function one(string $sql, string $types = '', array $params = []): ?array
{
    $rows = query_all($sql, $types, $params);
    return $rows[0] ?? null;
}
function execute_sql(string $sql, string $types = '', array $params = []): int
{
    global $db;
    $stmt = $db->prepare($sql);
    if ($types !== '') {
        $refs = [$types];
        foreach ($params as $k => $v)
            $refs[] = &$params[$k];
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    $stmt->execute();
    return $stmt->affected_rows;
}
function active_ingredients(): array
{
    return query_all('SELECT * FROM ingredients WHERE COALESCE(is_active,1)=1 ORDER BY name');
}
function active_product_categories(): array
{
    return query_all('SELECT id, name FROM categories WHERE category_type="product" AND is_active=1 ORDER BY id');
}
function recipes_with_cost(): array
{
    $rows = query_all('SELECT r.*, c.name AS category_name FROM recipes r LEFT JOIN categories c ON c.id=r.category_id WHERE COALESCE(r.is_active,1)=1 ORDER BY r.name');
    foreach ($rows as &$r) {
        $r['hpp_live'] = calculate_recipe_hpp((int) $r['id']);
        $r['ingredient_cost'] = $r['hpp_live'] - (float) $r['equipment_cost'] - (float) $r['operational_cost'] - (float) ($r['packaging_cost'] ?? 0);
        $r['selling_price'] = (float) ($r['selling_price'] ?: $r['hpp_live'] * (1 + markup_for((string) ($r['price_tier'] ?: 'normal')) / 100));
    }
    return $rows;
}
function recipe_items(int $recipeId): array
{
    $items = query_all('SELECT ri.*, i.name AS ingredient_name, i.base_unit, i.stock_qty_base, i.unit_price_base, i.price, i.price_quantity, i.price_unit FROM recipe_ingredients ri JOIN ingredients i ON i.id=ri.ingredient_id WHERE ri.recipe_id=?', 'i', [$recipeId]);
    foreach ($items as &$item) {
        $item['quantity'] = convert_qty((float) $item['quantity'], (string) $item['unit'], (string) $item['base_unit']);
        if ((float) $item['unit_price_base'] <= 0 && (float) $item['price_quantity'] > 0)
            $item['unit_price_base'] = ((float) $item['price'] / (float) $item['price_quantity']) / unit_factor((string) $item['price_unit']) * unit_factor((string) $item['base_unit']);
        $item['unit'] = $item['base_unit'];
    }
    return $items;
}
function calculate_recipe_hpp(int $recipeId): float
{
    $recipe = one('SELECT equipment_cost, operational_cost, packaging_cost FROM recipes WHERE id=?', 'i', [$recipeId]);
    if (!$recipe)
        throw new RuntimeException('Produk tidak ditemukan.');
    $cost = (float) $recipe['equipment_cost'] + (float) $recipe['operational_cost'] + (float) ($recipe['packaging_cost'] ?? 0);
    foreach (recipe_items($recipeId) as $item)
        $cost += (float) $item['quantity'] * (float) $item['unit_price_base'];
    return $cost;
}
function ingredient_cost(float $qty, string $unit, array $ingredient): float
{
    $base = convert_qty($qty, $unit, (string) $ingredient['base_unit']);
    return $base * (float) $ingredient['unit_price_base'];
}
function ensure_stock(int $recipeId, float $qty): array
{
    $short = [];
    foreach (recipe_items($recipeId) as $item) {
        $need = (float) $item['quantity'] * $qty;
        $have = (float) $item['stock_qty_base'];
        if ($have + 0.000001 < $need)
            $short[] = ['name' => $item['ingredient_name'], 'stock' => $have, 'needed' => $need, 'missing' => $need - $have, 'unit' => $item['base_unit']];
    }
    return $short;
}
function movement(int $ingredientId, string $type, float $qty, float $balance, string $reference = '', string $note = ''): void
{
    execute_sql('INSERT INTO stock_movements (ingredient_id, movement_type, quantity_base, balance_after, reference, note, created_at) VALUES (?,?,?,?,?,?,NOW())', 'isddss', [$ingredientId, $type, $qty, $balance, $reference, $note]);
}
function update_stock(int $id, float $delta, string $type, string $reference = '', string $note = ''): void
{
    global $db;
    $stmt = $db->prepare('SELECT stock_qty_base FROM ingredients WHERE id=? FOR UPDATE');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row)
        throw new RuntimeException('Bahan tidak ditemukan.');
    $balance = (float) $row['stock_qty_base'] + $delta;
    if ($balance < -0.000001 && setting('stock_mode', 'strict') === 'strict')
        throw new RuntimeException('Stok bahan tidak mencukupi.');
    execute_sql('UPDATE ingredients SET stock_qty_base=?, updated_at=NOW() WHERE id=?', 'di', [$balance, $id]);
    movement($id, $type, $delta, $balance, $reference, $note);
}
function next_invoice(): string
{
    global $db;
    $prefix = 'INV-' . date('Ymd') . '-';
    $row = one('SELECT COUNT(*) c FROM sales WHERE invoice_no LIKE CONCAT(?, "%")', 's', [$prefix]);
    return $prefix . str_pad((string) ((int) ($row['c'] ?? 0) + 1), 4, '0', STR_PAD_LEFT);
}
function log_action(string $action, string $detail = ''): void
{
    try {
        execute_sql('INSERT INTO audit_logs (action, detail, created_at) VALUES (?,?,NOW())', 'ss', [$action, $detail]);
    } catch (Throwable $e) {
        error_log($e->getMessage());
    }
}
function layout_start(string $title, string $active): void
{
    require_auth();
    $flash = take_flash();
    $links = ['index.php' => 'Dashboard', 'ingredients.php' => 'Bahan', 'products.php' => 'Produk', 'purchases.php' => 'Pembelian', 'pos.php' => 'POS', 'inventory.php' => 'Stok', 'reports.php' => 'Laporan', 'settings.php' => 'Pengaturan', 'logout.php' => 'Keluar'];
    $icons = ['index.php' => '⌂', 'ingredients.php' => '◈', 'products.php' => '✦', 'purchases.php' => '↗', 'pos.php' => '▣', 'inventory.php' => '▤', 'reports.php' => '◒', 'settings.php' => '⚙', 'users.php' => '♙', 'logout.php' => '↪'];
    if (current_user() && (string) current_user()['role'] === 'ADMIN')
        $links = array_slice($links, 0, -1, true) + ['users.php' => 'User'] + array_slice($links, -1, 1, true);
    ?><!doctype html>
    <html lang="id">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="theme-color" content="#2563eb" id="theme-color">
        <link rel="manifest" href="manifest.webmanifest">
        <title><?= e($title) ?> · <?= e(app_name()) ?></title>
        <script>
            (function () {
                var theme = null;
                try { theme = localStorage.getItem('hpp-theme'); } catch (error) { }
                if (theme !== 'dark' && theme !== 'light') {
                    theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-theme', theme);
            }());
        </script>
        <link rel="stylesheet" href="assets/styles.css?v=7">
    </head>

    <body class="<?= e(pathinfo($active, PATHINFO_FILENAME)) ?>-page">
        <header class="topbar">
            <a class="brand" href="index.php"><img class="brand-logo" src="assets/HPP_Toko-Bunga-Paubut(200x200).webp"
                    alt="Logo"><span><?= e(app_name()) ?><small>Smart operations</small></span></a>
            <div class="topbar-actions">
                <span id="network-status" class="status-dot">● Online</span>
                <button class="theme-toggle" type="button" data-theme-toggle aria-label="Aktifkan dark mode"><span
                        class="theme-toggle-icon">☾</span><span class="theme-toggle-label">Mode gelap</span></button>
                <div class="user-chip"><span
                        class="user-avatar"><?= e(strtoupper(substr((string) (current_user()['username'] ?? 'U'), 0, 1))) ?></span><span
                        class="user-meta"><strong><?= e((string) (current_user()['username'] ?? 'User')) ?></strong><small><?= e((string) (current_user()['role'] ?? 'USER')) ?></small></span>
                </div>
            </div>
        </header>
        <div class="app-shell">
            <aside class="sidebar">
                <div class="sidebar-label">OPERASIONAL</div><?php foreach ($links as $href => $label): ?><a
                        class="<?= $active === $href ? 'active' : '' ?>" href="<?= $href ?>" <?= $href === 'logout.php' ? ' data-logout-trigger' : '' ?>><span class="nav-icon"
                            aria-hidden="true"><?= $icons[$href] ?? '•' ?></span><span><?= e($label) ?></span></a><?php endforeach; ?>
            </aside>
            <main class="container page-content">
                <nav class="mobile-nav" aria-label="Navigasi utama"><?php foreach ($links as $href => $label): ?><a
                            class="<?= $active === $href ? 'active' : '' ?>" href="<?= $href ?>" <?= $href === 'logout.php' ? ' data-logout-trigger' : '' ?>><span class="nav-icon"
                                aria-hidden="true"><?= $icons[$href] ?? '•' ?></span><span><?= e($label) ?></span></a><?php endforeach; ?>
                </nav><?php if ($flash): ?>
                    <div class="alert <?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?><?php
                          }
                          function layout_end(): void
                          { ?>
            </main>
        </div>
        <footer><?= e(app_name()) ?> · data lokal Anda tetap milik Anda</footer>
        <dialog id="logout-dialog" class="logout-dialog" aria-labelledby="logout-dialog-title">
            <div class="modal-heading">
                <div>
                    <p class="eyebrow">KONFIRMASI</p>
                    <h2 id="logout-dialog-title">Keluar dari aplikasi?</h2>
                </div>
                <button class="modal-close" type="button" data-logout-cancel aria-label="Tutup dialog">&times;</button>
            </div>
            <p class="muted">Sesi Anda akan diakhiri dan Anda perlu masuk kembali untuk mengakses aplikasi.</p>
            <div class="modal-actions">
                <button class="button secondary" type="button" data-logout-cancel>Batal</button>
                <a class="button danger" href="logout.php">Ya, Keluar</a>
            </div>
        </dialog>
        <script src="assets/app.js?v=12"></script>
        <script src="assets/pwa.js?v=1"></script>
    </body>

    </html>
<?php }

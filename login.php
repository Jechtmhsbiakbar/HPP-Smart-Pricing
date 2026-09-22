<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';

$next = (string) ($_GET['next'] ?? $_POST['next'] ?? 'index.php');
if (!in_array($next, ['index.php', 'ingredients.php', 'products.php', 'purchases.php', 'pos.php', 'inventory.php', 'reports.php', 'settings.php'], true)) $next = 'index.php';
$hasUsers = (int) (one('SELECT COUNT(*) AS total FROM users WHERE is_active=1')['total'] ?? 0) > 0;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($username === '' || strlen($password) < 8) throw new RuntimeException('Username wajib diisi dan password minimal 8 karakter.');
        if (!$hasUsers) {
            $setupKey = (string) ($config['setup_key'] ?? '');
            $providedSetupKey = (string) ($_POST['setup_key'] ?? '');
            if ($setupKey === '' || !hash_equals($setupKey, $providedSetupKey)) throw new RuntimeException('Setup key admin belum benar.');
            execute_sql('INSERT INTO users(username,password_hash,role,is_active) VALUES(?,?,?,1)', 'sss', [$username, password_hash($password, PASSWORD_DEFAULT), 'ADMIN']);
            $hasUsers = true;
        }
        $user = one('SELECT id,username,password_hash,role FROM users WHERE username=? AND is_active=1', 's', [$username]);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) throw new RuntimeException('Username atau password salah.');
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $user['id'], 'username' => (string) $user['username'], 'role' => (string) $user['role']];
        redirect_to($next);
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Masuk · HPP Smart Pricing</title><link rel="stylesheet" href="assets/styles.css?v=5"></head>
<body><main class="container auth-page"><section class="panel auth-card"><p class="eyebrow"><?= $hasUsers ? 'AKSES APLIKASI' : 'PENYIAPAN AWAL' ?></p><h1><?= $hasUsers ? 'Masuk ke aplikasi' : 'Buat akun admin' ?></h1><p class="muted"><?= $hasUsers ? 'Gunakan akun terdaftar untuk melanjutkan.' : 'Masukkan setup key dari konfigurasi server untuk membuat admin pertama.' ?></p>
<?php if ($error !== ''): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="next" value="<?= e($next) ?>"><?php if (!$hasUsers): ?><label>Setup key<input name="setup_key" autocomplete="off" required></label><?php endif; ?><label>Username<input name="username" autocomplete="username" required></label><label>Password<input type="password" name="password" autocomplete="<?= $hasUsers ? 'current-password' : 'new-password' ?>" minlength="8" required></label><button class="button primary full" type="submit"><?= $hasUsers ? 'Masuk' : 'Buat akun dan masuk' ?></button></form></section></main></body></html>

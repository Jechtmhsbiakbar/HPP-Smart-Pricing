<?php

declare(strict_types=1);

require __DIR__ . '/includes/app.php';

$next = (string) ($_GET['next'] ?? $_POST['next'] ?? 'index.php');
if (!in_array($next, ['index.php', 'ingredients.php', 'products.php', 'purchases.php', 'pos.php', 'inventory.php', 'reports.php', 'settings.php'], true)) {
    $next = 'index.php';
}

$hasUsers = (int) (one('SELECT COUNT(*) AS total FROM users WHERE is_active=1')['total'] ?? 0) > 0;
$error = '';
$loginSuccess = false;
$loggedInUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || strlen($password) < 8) {
            throw new RuntimeException('Username wajib diisi dan password minimal 8 karakter.');
        }

        if (!$hasUsers) {
            $setupKey = (string) ($config['setup_key'] ?? '');
            $providedSetupKey = (string) ($_POST['setup_key'] ?? '');
            if ($setupKey === '' || !hash_equals($setupKey, $providedSetupKey)) {
                throw new RuntimeException('Setup key admin belum benar.');
            }
            execute_sql('INSERT INTO users(username,password_hash,role,is_active) VALUES(?,?,?,1)', 'sss', [$username, password_hash($password, PASSWORD_DEFAULT), 'ADMIN']);
            $hasUsers = true;
        }

        $user = one('SELECT id,username,password_hash,role FROM users WHERE username=? AND is_active=1', 's', [$username]);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            throw new RuntimeException('Username atau password salah.');
        }

        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $user['id'], 'username' => (string) $user['username'], 'role' => (string) $user['role']];
        
        // Tandai login berhasil untuk memicu animasi sebelum redirect
        $loginSuccess = true;
        $loggedInUsername = (string) $user['username'];

    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $hasUsers ? 'Masuk' : 'Penyiapan Awal' ?> · HPP Smart Pricing</title>
    <link rel="stylesheet" href="assets/styles.css?v=5">
    <!-- Library Confetti untuk animasi selebrasi -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <style>
        body.auth-wrapper {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #3b82f6 100%);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow: hidden;
        }

        .auth-card-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            z-index: 10;
        }

        .auth-card-modern {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 36px 32px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            animation: slideUp 0.5s ease-out;
            position: relative;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .auth-header .eyebrow {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: #2563eb;
            background: #eff6ff;
            padding: 4px 14px;
            border-radius: 20px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .auth-header h1 {
            font-size: 1.6rem;
            color: #0f172a;
            margin: 0 0 6px 0;
            font-weight: 700;
        }

        .auth-header p {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0;
        }

        .form-group {
            margin-bottom: 18px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-control {
            width: 100%;
            padding: 12px 14px;
            font-size: 0.95rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
            background: #f8fafc;
        }

        .input-control:focus {
            background: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .toggle-password:hover {
            background: #e2e8f0;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fca5a5;
            color: #b91c1c;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 0.875rem;
            margin-bottom: 20px;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        /* --- TAMPILAN ANIMASI SUKSES --- */
        .success-overlay {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px 0;
            animation: fadeInScale 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Animated Checkmark Circle */
        .checkmark-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
            margin-bottom: 20px;
            animation: pulseGlow 1.5s infinite alternate;
        }

        @keyframes pulseGlow {
            from { box-shadow: 0 0 15px rgba(16, 185, 129, 0.4); }
            to { box-shadow: 0 0 30px rgba(16, 185, 129, 0.8); }
        }

        .checkmark-icon {
            width: 40px;
            height: 40px;
            stroke: white;
            stroke-width: 3;
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: drawCheck 0.6s 0.2s forwards ease-in-out;
        }

        @keyframes drawCheck {
            to { stroke-dashoffset: 0; }
        }

        .welcome-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 8px 0;
        }

        .welcome-sub {
            font-size: 0.95rem;
            color: #64748b;
            margin: 0 0 20px 0;
        }

        /* Progress Bar Pengalihan */
        .redirect-progress {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .redirect-bar {
            height: 100%;
            background: linear-gradient(90deg, #2563eb, #10b981);
            width: 0%;
            animation: loadBar 1.8s forwards ease-in-out;
        }

        @keyframes loadBar {
            to { width: 100%; }
        }

        /* Spinner Loading Button */
        .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body class="auth-wrapper">
    <main class="auth-card-wrapper">
        <section class="auth-card-modern" id="cardContainer">
            <?php if ($loginSuccess): ?>
                <!-- Tampilan Layar Sukses Interaktif -->
                <div class="success-overlay">
                    <div class="checkmark-circle">
                        <svg class="checkmark-icon" viewBox="0 0 24 24" fill="none">
                            <path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h2 class="welcome-title">Selamat Datang Kembali!</h2>
                    <p class="welcome-sub">Halo <strong><?= e($loggedInUsername) ?></strong>, menyiapkan dashboard Anda...</p>
                    <div class="redirect-progress">
                        <div class="redirect-bar"></div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // 1. Tembakkan Pesta Confetti (Partikel Warna-Warni)
                        confetti({
                            particleCount: 100,
                            spread: 70,
                            origin: { y: 0.6 }
                        });

                        // 2. Redirect Otomatis ke Dashboard setelah 1.8 Detik
                        setTimeout(function() {
                            window.location.href = <?= json_encode($next) ?>;
                        }, 1800);
                    });
                </script>

            <?php else: ?>
                <!-- Form Login Biasa -->
                <div class="auth-header">
                    <span class="eyebrow"><?= $hasUsers ? 'Akses Aplikasi' : 'Penyiapan Awal' ?></span>
                    <h1><?= $hasUsers ? 'Selamat Datang' : 'Buat Akun Admin' ?></h1>
                    <p><?= $hasUsers ? 'Masukkan akun Anda untuk melanjutkan.' : 'Masukkan setup key dari server untuk mendaftarkan akun admin.' ?></p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert-error">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" id="loginForm">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="next" value="<?= e($next) ?>">

                    <?php if (!$hasUsers): ?>
                        <div class="form-group">
                            <label for="setup_key">Setup Key Server</label>
                            <input id="setup_key" name="setup_key" class="input-control" placeholder="Masukkan setup key..." autocomplete="off" required>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input id="username" name="username" class="input-control" placeholder="Masukkan username..." autocomplete="username" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input id="password" type="password" name="password" class="input-control" placeholder="••••••••" autocomplete="<?= $hasUsers ? 'current-password' : 'new-password' ?>" minlength="8" required>
                            <button type="button" class="toggle-password" id="togglePasswordBtn">Lihat</button>
                        </div>
                    </div>

                    <button class="btn-submit" type="submit" id="submitBtn">
                        <span class="spinner" id="btnSpinner"></span>
                        <span id="btnText"><?= $hasUsers ? 'Masuk Sekarang' : 'Buat Akun & Masuk' ?></span>
                    </button>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePasswordBtn = document.getElementById('togglePasswordBtn');
            const passwordInput = document.getElementById('password');
            const loginForm = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnSpinner = document.getElementById('btnSpinner');
            const btnText = document.getElementById('btnText');

            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function() {
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    this.textContent = isPassword ? 'Sembunyi' : 'Lihat';
                });
            }

            if (loginForm) {
                loginForm.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    btnSpinner.style.display = 'inline-block';
                    btnText.textContent = 'Memverifikasi...';
                });
            }
        });
    </script>
</body>

</html>
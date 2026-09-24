<?php
declare(strict_types=1);

$configPath = __DIR__ . '/config.local.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Konfigurasi database belum dibuat. Salin config.example.php menjadi config.local.php.');
}

$config = require $configPath;
foreach (['host', 'username', 'database'] as $required) {
    if (!array_key_exists($required, $config) || !is_string($config[$required]) || $config[$required] === '') {
        http_response_code(500);
        exit('Konfigurasi database tidak lengkap. Periksa environment localhost/hosting.');
    }
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(
    (string) $config['host'],
    (string) $config['username'],
    (string) $config['password'],
    (string) $config['database'],
    (int) ($config['port'] ?? 3306)
);

if ($db->connect_error) {
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi database server.');
}

$db->set_charset('utf8mb4');

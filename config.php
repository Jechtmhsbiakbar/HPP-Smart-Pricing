<?php
declare(strict_types=1);

$configPath = __DIR__ . '/config.local.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Konfigurasi database belum dibuat. Salin config.example.php menjadi config.local.php.');
}

$config = require $configPath;
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
    exit('Koneksi database gagal. Periksa konfigurasi database InfinityFree Anda.');
}

$db->set_charset('utf8mb4');

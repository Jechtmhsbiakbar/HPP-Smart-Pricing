<?php
declare(strict_types=1);

// Salin menjadi config.local.php untuk localhost.
// Pada hosting, config.local.php yang sama otomatis membaca DB_* dari server.
return [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'nama_database',
    'username' => 'nama_user_database',
    'password' => 'password_database',
    // 'setup_key' => 'kunci-rahasia-panjang',
];
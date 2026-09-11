# HPP Smart Pricing

Aplikasi web untuk menghitung Harga Pokok Produksi (HPP) dan merekomendasikan harga jual produk secara otomatis untuk bisnis seperti toko bunga, bakery, atau usaha rumahan yang mengelola bahan baku dan resep.

Project ini dibuat khusus untuk mendukung proses pengelolaan data bahan, pembuatan resep produk, serta kalkulasi harga jual berdasarkan target margin yang diinginkan.

## Fitur utama

- Manajemen data bahan baku
  - nama bahan
  - satuan dasar
  - harga beli
  - jumlah dan satuan pembelian
- Pembuatan produk/resep
  - kombinasi beberapa bahan
  - input jumlah bahan per resep
  - biaya alat dan biaya operasional
- Perhitungan HPP otomatis
  - menghitung biaya bahan per resep
  - menambahkan biaya alat dan operasional
- Rekomendasi harga jual
  - tier murah: 15%
  - tier normal: 30%
  - tier mahal: 50%
- Dashboard produk
  - daftar produk yang tersimpan
  - lihat total HPP dan harga jual rekomendasi
  - edit dan hapus produk
- UI yang sederhana dan responsif untuk kebutuhan operasional harian

## Teknologi yang digunakan

- PHP
- MySQL
- HTML
- CSS
- JavaScript vanilla

## Struktur project

- `index.php` — logika utama aplikasi, proses CRUD produk dan bahan
- `products.php` — halaman manajemen produk dan resep
- `ingredients.php` — halaman manajemen bahan baku
- `database.sql` — struktur database
- `config.php` — konfigurasi koneksi database
- `config.local.php` — konfigurasi lokal yang dibuat secara manual
- `assets/` — file CSS, JavaScript, dan aset visual

## Prasyarat

Pastikan environment berikut sudah tersedia:

- PHP 7.4+ atau versi yang kompatibel
- MySQL / MariaDB
- Web server lokal seperti XAMPP, WAMP, Laragon, atau PHP built-in server

## Setup langkah demi langkah

1. Clone atau unduh project ini ke folder web server Anda.
2. Buat database MySQL baru.
3. Import file `database.sql` ke database tersebut.
4. Buat file `config.local.php` berdasarkan konfigurasi berikut:

```php
<?php
return [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'hpp_smart_pricing',
    'port' => 3306,
];
```

5. Jalankan aplikasi melalui browser:

```bash
php -S localhost:8000
```

Lalu buka:

```text
http://localhost:8000/
```

## Alur penggunaan aplikasi

### 1. Tambah bahan baku
Masukkan data bahan seperti nama, satuan dasar, harga beli, jumlah pembelian, dan satuan harga.

### 2. Buat produk baru
Pilih bahan, masukkan jumlah penggunaan, dan tambahkan biaya alat serta operasional.

### 3. Hitung HPP
Sistem akan menghitung total biaya bahan plus biaya tambahan secara otomatis.

### 4. Rekomendasi harga jual
Aplikasi akan menghitung harga jual berdasarkan markup yang dipilih:

- Murah = 15%
- Normal = 30%
- Mahal = 50%

## Contoh penggunaan bisnis

Aplikasi ini cocok digunakan untuk usaha seperti:

- toko bunga
- usaha catering
- pastry dan bakery
- minuman dan coffee shop
- produk rumahan dengan bahan baku variatif

## Catatan penting

- File `config.local.php` bersifat lokal dan tidak boleh dipublikasikan ke repository jika berisi kredensial sensitif.
- Jika proyek dipindahkan ke hosting, sesuaikan konfigurasi database pada file `config.local.php` sesuai server hosting Anda.

## Lisensi

Project ini dibuat untuk kebutuhan operasional internal dan dapat dikembangkan lebih lanjut sesuai kebutuhan bisnis.

## Kontributor

Project ini dapat dikembangkan lebih lanjut dengan fitur seperti:

- laporan penjualan
- export data ke Excel
- autentikasi admin
- riwayat perubahan harga
- multi-user access

Jika Anda ingin, saya juga bisa bantu membuat versi README yang lebih formal untuk GitHub atau versi yang lebih ringkas untuk presentation/demo project.

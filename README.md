# HPP Smart Pricing — POS & Inventory

Aplikasi PHP procedural untuk UMKM: bahan dan satuan, recipe/BOM, HPP,
pembelian, POS, pengurangan stok resep, stock movement, opname, waste,
dashboard, laporan, dan pengaturan markup.

## Teknologi dan requirement

- PHP 7.4+ (mysqli, session, JSON)
- MySQL 5.7+/MariaDB dengan engine InnoDB
- Apache atau PHP built-in server
- Tidak memerlukan npm, Node.js, atau framework

## Instalasi

1. Buat database MySQL kosong dan import `database.sql`.
2. Salin `config.example.php` menjadi `config.local.php` untuk localhost.
   File yang sama otomatis memilih mode hosting saat dibuka melalui domain
   non-localhost, jadi tidak perlu menghapus atau mengomentari blok konfigurasi.
   `config.local.php` diabaikan git dan tidak boleh dipublikasikan.
3. Untuk database lama, backup dahulu lalu jalankan SQL di
   `database/migrations/001_operational_schema.sql` menggunakan client MySQL.
   Migration bersifat additive; jangan menghapus tabel lama.
4. Jalankan `php -S localhost:8000` dari root repository, lalu buka
   `http://localhost:8000/`.

## Modul

| Modul | File |
| --- | --- |
| Dashboard | `index.php` |
| Bahan dan harga dasar | `ingredients.php` |
| Produk/resep/HPP | `products.php` |
| Pembelian/restock | `purchases.php` |
| POS atomic | `pos.php` |
| Stok, opname, waste, kartu stok | `inventory.php` |
| Penjualan dan CSV | `reports.php` |
| Markup dan strict stock | `settings.php` |

`includes/app.php` adalah sumber tunggal konversi satuan, kalkulasi HPP,
query helper, CSRF, dan operasi stock movement. `includes/actions.php`
menjalankan semua perubahan data dengan prepared statements. Penjualan
menyimpan snapshot resep/HPP, sehingga perubahan harga atau resep tidak
mengubah histori.

CSV bahan, produk, dan kartu stok tersedia melalui `export.php?type=ingredients`,
`export.php?type=products`, dan `export.php?type=movements`.

## Satuan dan HPP

Stok internal memakai gram, ml, atau pcs. Contoh 1 kg = 1.000 gram; harga
dasar dihitung dari harga pembelian dibagi jumlah pembelian yang dikonversi.
Resep menyimpan quantity dalam satuan stok. Uang disimpan sebagai `DECIMAL`
di MySQL. Mode `strict` (default) menolak penjualan saat bahan tidak cukup;
mode `allow_negative` tersedia di Settings.

## PWA dan offline

`manifest.webmanifest`, `service-worker.js`, dan `assets/pwa.js` membuat UI
installable dan meng-cache asset static. Status ONLINE/OFFLINE ditampilkan.
Backend MySQL tetap memerlukan koneksi server: transaksi offline belum
diantrekan dan tidak boleh dianggap berhasil.

## Shared hosting dan keamanan

Upload file PHP, `assets/`, `api/`, `database/`, dan manifest ke hosting.
Atur environment variable berikut di panel hosting/PHP server sebelum membuka
aplikasi: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`,
dan `APP_SETUP_KEY`. Jangan menyimpan nilainya di repository. Pastikan
`config.local.php` tidak dapat diunduh; `.htaccess` sudah memblokir file
konfigurasi dan SQL pada Apache. Form perubahan memakai CSRF, output
di-escape, data memakai prepared statements, dan bahan/produk dinonaktifkan
(soft delete) setelah digunakan. Error teknis dicatat ke error log server,
sedangkan pengguna melihat pesan yang aman.

Pada akses pertama, `APP_SETUP_KEY` digunakan sebagai kunci rahasia dan
`login.php` membuat akun ADMIN pertama dengan password minimal 8 karakter.
Akun yang sudah masuk dapat digunakan untuk POS; operasi
pengaturan, stok, pembelian, resep, dan pembatalan transaksi dibatasi untuk
role ADMIN.

## Validasi cepat

Jalankan `php -l` untuk seluruh file PHP. Skenario manual minimum:
buat Tepung (10 kg, Rp120.000), buat resep 250 gram, restock, jual 1/3 unit,
uji stok kurang, opname, waste, dan void. Pastikan kartu stok serta laporan
tetap konsisten.

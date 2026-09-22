# Audit HPP Smart Pricing

## Kondisi awal

Repository awal berisi tiga halaman PHP (`index.php`, `ingredients.php`, dan
`products.php`) yang menggabungkan HTML, query, dan kalkulasi HPP. Database
awal hanya mempunyai `ingredients`, `recipes`, dan `recipe_ingredients`.
Harga resep dihitung memakai faktor satuan yang hard-coded, tanpa stok,
riwayat harga, transaksi penjualan, atau transaksi database untuk POS.
Form lama tetap menggunakan endpoint `index.php`.

## Risiko yang ditemukan

- Harga uang dan konversi satuan dihitung di beberapa halaman dengan `float`
  dan daftar satuan yang berbeda.
- Tidak ada stok, stock movement, pembelian, opname, waste, atau snapshot HPP.
- Produk/bahan dapat dihapus permanen sehingga histori tidak aman.
- Tidak ada CSRF, validasi stok server-side, atomic sale, atau nomor invoice.
- `config.php` sudah memisahkan kredensial lokal, tetapi belum ada bootstrap
  aplikasi dan logging error yang konsisten.
- Belum ada PWA manifest/service worker dan UI hanya memiliki tiga menu.

## Rencana implementasi bertahap

1. Mempertahankan tiga tabel lama dan menambah kolom nullable/bernilai default
   melalui migration yang aman.
2. Menambahkan tabel unit, kategori, settings, purchases, sales, stock
   movements, opname, dan waste.
3. Memusatkan konversi unit, HPP, validasi, CSRF, dan operasi stok di
   `includes/app.php`.
4. Menyediakan dashboard, bahan, produk/resep, pembelian, inventory, POS,
   laporan, dan settings berbasis PHP procedural yang ramah shared hosting.
5. Menambahkan endpoint JSON kecil untuk POS dan PWA static offline caching.
6. Menjaga backward compatibility: action lama tetap dirutekan dari
   `index.php`, dan data lama tidak dihapus.

## Fase yang selesai pada workspace ini

- Schema/migration dan seed settings/unit.
- Shared business logic dengan prepared statements, transaksi, CSRF, dan
  snapshot HPP.
- Bahan+satuan+stok, resep/HPP, pembelian, POS atomic, movement, opname/waste.
- Dashboard, laporan CSV, pengaturan markup/strict stock, PWA static assets.
- README dan dokumentasi keterbatasan offline.

## Keterbatasan yang disengaja

Backend MySQL tetap membutuhkan koneksi server. PWA hanya melakukan cache
asset dan menampilkan status online/offline; transaksi offline tidak diklaim
berhasil dan belum memakai queue IndexedDB.

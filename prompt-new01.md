Anda adalah senior full-stack developer yang bertugas mengembangkan project yang sedang terbuka di workspace VS Code ini.

Nama project:

HPP Smart Pricing

Jangan membuat project baru dari nol dan jangan mengganti teknologi utama project tanpa alasan yang sangat kuat.

Project saat ini menggunakan:

* PHP
* MySQL / MariaDB
* HTML
* CSS
* Vanilla JavaScript
* Struktur PHP procedural/modular sederhana
* Target deployment: shared hosting seperti InfinityFree
* Target aplikasi: PWA yang dapat digunakan melalui browser desktop maupun mobile

==================================================
TUJUAN UTAMA
============

Kembangkan HPP Smart Pricing menjadi aplikasi:

"Smart Pricing POS & Inventory"

yang menggabungkan:

1. Manajemen bahan baku
2. Manajemen satuan dan konversi satuan
3. Manajemen produk
4. Recipe / BOM produk
5. Perhitungan HPP otomatis
6. POS / transaksi penjualan
7. Pengurangan stok bahan otomatis berdasarkan resep
8. Manajemen stok bahan
9. Stock opname
10. Penyesuaian stok
11. Pembelian / restock bahan
12. Pengelolaan pendapatan dari penjualan
13. Laporan penjualan
14. Laporan HPP
15. Laporan penggunaan bahan
16. Dashboard bisnis
17. PWA
18. Offline capability dasar
19. Responsive mobile dan desktop
20. Riwayat transaksi dan perubahan stok

Aplikasi harus terasa seperti aplikasi operasional bisnis sungguhan, bukan hanya kalkulator HPP.

==================================================
ATURAN PALING PENTING
=====================

SEBELUM MENGUBAH KODE:

1. Baca seluruh struktur project.
2. Baca semua file PHP utama.
3. Baca database.sql.
4. Baca config.php dan config.local.php.
5. Baca seluruh JavaScript dan CSS yang sudah ada.
6. Pahami hubungan antar halaman.
7. Identifikasi fitur yang sudah berjalan.
8. Identifikasi bug atau kelemahan struktur database.
9. Jangan menghapus fitur lama yang masih relevan.
10. Jangan langsung melakukan rewrite besar-besaran.

Buat pemetaan internal terlebih dahulu:

* struktur aplikasi saat ini
* tabel database saat ini
* relasi antar tabel
* alur CRUD
* alur kalkulasi HPP
* alur produk/resep
* potensi masalah data
* bagian yang harus dipertahankan
* bagian yang perlu diperbaiki

Jika terdapat struktur yang buruk, lakukan refactor secara bertahap.

==================================================
PRINSIP ARSITEKTUR
==================

Tetap gunakan:

PHP + MySQL + HTML + CSS + Vanilla JavaScript.

Jangan menggunakan:

* React
* Vue
* Laravel
* Node.js sebagai backend
* framework frontend berat
* framework backend baru

kecuali benar-benar diperlukan dan terdapat alasan teknis yang sangat kuat.

Alasan:

Project harus mudah:

* dijalankan di XAMPP
* dijalankan di Laragon
* dijalankan menggunakan PHP built-in server
* diupload ke shared hosting
* dideploy ke InfinityFree
* dirawat oleh developer pemula/intermediate

Gunakan struktur kode yang rapi dan modular.

Pisahkan minimal:

* database access
* business logic
* API/action endpoint
* halaman UI
* JavaScript
* CSS
* PWA assets

Jangan menaruh seluruh logika bisnis di satu file index.php.

==================================================
KONSEP APLIKASI
===============

Aplikasi harus mempunyai beberapa modul utama:

DASHBOARD

INVENTORY

BAHAN

PRODUK

RESEP

POS / PENJUALAN

PEMBELIAN BAHAN

STOCK OPNAME

LAPORAN

PENGATURAN

==================================================

1. MANAJEMEN BAHAN
   ==================================================

Perbaiki konsep bahan agar benar-benar cocok untuk inventory.

Setiap bahan minimal memiliki:

* id
* nama bahan
* kategori
* satuan stok
* harga beli
* jumlah pembelian
* satuan pembelian
* konversi ke satuan stok
* harga per satuan dasar
* stok saat ini
* stok minimum
* stok maksimum
* status aktif/nonaktif
* tanggal dibuat
* tanggal diperbarui

Contoh:

Tepung

Pembelian:

1 sak = 25 kg

Harga:

Rp300.000

Maka:

harga per kg = Rp12.000

Jika resep menggunakan:

250 gram

maka sistem harus dapat menghitung:

250 gram = 0,25 kg

biaya bahan:

0,25 × Rp12.000 = Rp3.000

Jangan menyimpan perhitungan harga secara asal.

==================================================
2. SISTEM SATUAN
================

Buat sistem satuan yang konsisten.

Minimal dukung:

Berat:

* gram
* kilogram

Volume:

* mililiter
* liter

Jumlah:

* pcs
* unit

Jangan mencampur satuan tanpa konversi.

Gunakan satuan dasar internal.

Contoh:

berat → gram

volume → ml

jumlah → pcs

Semua resep dan inventory harus dikonversi ke satuan dasar sebelum kalkulasi.

Contoh:

1 kg = 1000 gram

1 liter = 1000 ml

==================================================
3. PEMBELIAN / RESTOCK BAHAN
============================

Buat modul pembelian bahan.

Contoh:

Tanggal:
22 September 2026

Bahan:
Tepung

Qty:
25 kg

Harga:
Rp300.000

Sistem harus:

* menambah stok
* mencatat harga pembelian
* mencatat histori pembelian
* memperbarui harga bahan
* menghitung harga dasar dengan benar

Jangan hanya mengubah angka stok tanpa histori.

Setiap perubahan stok harus dapat ditelusuri.

==================================================
4. PERHITUNGAN HARGA BAHAN
==========================

Gunakan perhitungan yang konsisten.

Contoh:

Pembelian:

10 kg = Rp120.000

Harga dasar:

Rp12.000/kg

Jika menggunakan:

250 gram

maka:

250 / 1000 = 0,25 kg

HPP bahan:

0,25 × Rp12.000

= Rp3.000

Semua perhitungan harus menggunakan angka desimal yang aman.

Jangan menggunakan floating point secara sembarangan untuk nilai uang.

Untuk uang gunakan integer dalam satuan Rupiah atau DECIMAL pada database.

==================================================
5. RESEP / BOM
==============

Setiap produk dapat mempunyai resep.

Contoh:

Produk:
Es Kopi Susu

Resep:

Kopi = 18 gram
Susu = 150 ml
Gula = 20 gram
Cup = 1 pcs
Es = 100 gram

Setiap bahan memiliki:

* jumlah
* satuan penggunaan
* konversi ke satuan dasar
* biaya bahan

Sistem otomatis menghitung:

Total HPP bahan.

Jika produk memiliki:

Biaya alat
Biaya operasional
Biaya kemasan

maka semuanya dapat dimasukkan dalam kalkulasi HPP.

==================================================
6. VARIAN PRODUK
================

Jika memungkinkan, siapkan dukungan produk dengan varian.

Contoh:

Es Kopi Susu:

* Regular
* Large

Setiap varian dapat memiliki:

* harga jual berbeda
* resep berbeda
* HPP berbeda

Namun jangan membuat struktur terlalu kompleks jika belum diperlukan.

Prioritaskan sistem produk dasar terlebih dahulu.

==================================================
7. HPP PRODUK
=============

HPP produk harus berasal dari:

HPP bahan
+
kemasan
+
biaya alat
+
biaya operasional
+
komponen biaya lain jika tersedia.

Contoh:

Bahan = Rp5.000
Kemasan = Rp1.000
Operasional = Rp500
Alat = Rp200

HPP:

Rp6.700

Kemudian rekomendasi harga jual:

Murah = markup 15%
Normal = markup 30%
Mahal = markup 50%

Tetapi desain sistem agar persentase markup dapat diubah melalui Settings.

==================================================
8. POS / PENJUALAN
==================

Buat modul POS.

POS minimal memiliki:

* daftar produk
* pencarian produk
* kategori
* keranjang
* jumlah
* harga
* subtotal
* diskon
* total
* pembayaran
* kembalian
* metode pembayaran
* simpan transaksi

Metode pembayaran minimal:

* cash
* transfer
* QRIS

Simpan transaksi ke database.

Setiap transaksi memiliki nomor transaksi unik.

Contoh:

INV-20260922-0001

==================================================
9. STOCK DEDUCTION OTOMATIS
===========================

INI ADALAH FITUR UTAMA.

Ketika user melakukan penjualan:

1 produk terjual

maka sistem membaca resep produk.

Contoh:

Es Kopi Susu:

Kopi 18 gram
Susu 150 ml
Gula 20 gram
Cup 1 pcs

Jika terjual 3:

Kopi:

18 × 3 = 54 gram

Susu:

150 × 3 = 450 ml

Gula:

20 × 3 = 60 gram

Cup:

1 × 3 = 3 pcs

Sistem otomatis mengurangi stok.

Jangan hanya mengurangi stok produk.

Yang dikurangi adalah bahan berdasarkan resep.

==================================================
10. TRANSACTION / STOCK ATOMICITY
=================================

Saat transaksi penjualan terjadi:

stok dan transaksi harus konsisten.

Gunakan database transaction:

BEGIN TRANSACTION

validasi stok

simpan transaksi

simpan detail transaksi

potong stok bahan

simpan stock movement

COMMIT

Jika salah satu proses gagal:

ROLLBACK

Jangan sampai:

transaksi berhasil tetapi stok tidak berkurang.

atau:

stok berkurang tetapi transaksi gagal.

==================================================
11. VALIDASI STOK
=================

Sebelum transaksi:

periksa apakah semua bahan resep tersedia.

Contoh stok:

Kopi = 10 gram

Kebutuhan = 18 gram

Maka:

produk tidak boleh dijual jika sistem memang menggunakan strict stock validation.

Tampilkan informasi bahan yang kurang.

Contoh:

Tidak dapat menjual produk.

Bahan tidak mencukupi:

Kopi
Stok: 10 gram
Dibutuhkan: 18 gram
Kekurangan: 8 gram

Buat pengaturan apakah sistem:

* strict stock
* allow negative stock

Default:

strict stock.

==================================================
12. STOCK MOVEMENT / KARTU STOK
===============================

Jangan hanya menyimpan stok akhir.

Buat histori pergerakan stok.

Jenis movement:

PURCHASE
SALE
STOCK_OPNAME
ADJUSTMENT
RETURN
WASTE
INITIAL_STOCK

Contoh:

22 Sep
Pembelian
+10 kg

23 Sep
Penjualan
-250 gram

24 Sep
Stock opname
-100 gram

User harus dapat melihat histori.

==================================================
13. STOCK OPNAME
================

Buat modul stock opname.

User dapat melihat:

Stok sistem
Stok fisik
Selisih

Contoh:

Sistem:

5 kg

Fisik:

4,7 kg

Selisih:

-0,3 kg

Saat disimpan:

stok diperbarui

dan dibuat stock movement:

STOCK_OPNAME

dengan alasan/keterangan.

==================================================
14. WASTE / BAHAN RUSAK
=======================

Tambahkan kemampuan mencatat bahan:

* rusak
* kadaluarsa
* tumpah
* terbuang
* kesalahan produksi

Contoh:

Tepung
500 gram
Alasan:
Rusak

Stok dikurangi:

500 gram

dan masuk histori.

==================================================
15. DASHBOARD
=============

Dashboard harus menampilkan informasi bisnis.

Minimal:

* total penjualan hari ini
* jumlah transaksi hari ini
* pendapatan hari ini
* estimasi HPP terjual
* estimasi laba kotor
* produk terlaris
* bahan stok menipis
* jumlah produk
* jumlah bahan

Gunakan kartu statistik.

Jika memungkinkan gunakan grafik sederhana menggunakan JavaScript tanpa library berat.

==================================================
16. PENDAPATAN
==============

Setiap transaksi penjualan harus menghasilkan data pendapatan.

Sistem harus dapat menghitung:

## Total penjualan

# HPP barang terjual

Laba kotor

Contoh:

Penjualan:

Rp100.000

Estimasi HPP:

Rp60.000

Laba kotor:

Rp40.000

Jangan menyebutnya laba bersih karena biaya operasional lengkap belum tentu tercatat.

Gunakan istilah:

"Laba Kotor / Gross Profit"

==================================================
17. LAPORAN
===========

Buat laporan:

Penjualan

* hari ini
* kemarin
* minggu ini
* bulan ini
* custom date range

Produk:

* produk terjual
* quantity
* omzet
* HPP
* laba kotor

Bahan:

* stok
* penggunaan
* pembelian
* waste
* adjustment

Transaksi:

* nomor transaksi
* tanggal
* kasir
* total
* metode pembayaran
* status

==================================================
18. HARGA JUAL
==============

Sistem tetap mempertahankan fitur Smart Pricing.

Tetapi jangan hardcode:

15%
30%
50%

Buat konfigurasi:

Murah
Normal
Mahal

dan persentasenya dapat diubah admin.

Contoh:

Murah = 15%
Normal = 30%
Mahal = 50%

Jika user mengubah:

Normal = 35%

maka rekomendasi harga berubah.

==================================================
19. PWA
=======

Ubah aplikasi menjadi Progressive Web App.

Buat:

manifest.webmanifest

service-worker.js

icon:

192x192
512x512

Gunakan:

* installable
* standalone mode
* responsive
* service worker
* cache static assets

Buat offline capability secara realistis.

Penting:

Jangan mengklaim database PHP/MySQL dapat digunakan penuh secara offline.

Karena backend masih membutuhkan server.

Implementasikan:

offline caching untuk UI/static assets

dan tampilkan status:

ONLINE
OFFLINE

Jika memungkinkan, siapkan struktur agar transaksi offline dapat dikembangkan menggunakan IndexedDB di masa depan.

Jangan membuat sistem offline transaction kompleks jika belum benar-benar stabil.

==================================================
20. RESPONSIVE UI
=================

UI wajib responsive.

Target:

Mobile
Tablet
Laptop
Desktop

Prioritas utama:

mobile usability.

POS harus nyaman digunakan melalui touchscreen.

Gunakan:

* tombol cukup besar
* spacing yang nyaman
* bottom navigation atau sidebar responsive
* modal responsive
* tabel dapat scroll horizontal
* form mobile-friendly

==================================================
21. UX
======

Aplikasi harus terasa seperti aplikasi bisnis modern.

Gunakan:

* sidebar desktop
* mobile navigation
* modal
* toast notification
* confirmation dialog
* loading state
* empty state
* error state
* skeleton/loading jika diperlukan
* search
* filter
* pagination jika data besar

Jangan menggunakan alert() browser sebagai UI utama.

==================================================
22. DATABASE
============

Review database.sql secara menyeluruh.

Jika database lama tidak cukup:

buat migration SQL yang aman.

Jangan sembarangan menghapus data lama.

Minimal konsep tabel yang kemungkinan diperlukan:

users

categories

units

ingredients

ingredient_purchases

products

product_variants

recipes

recipe_items

sales

sale_items

stock_movements

stock_opnames

stock_opname_items

wastes

expenses

settings

Boleh menyesuaikan nama tabel dengan struktur project yang sudah ada.

Jangan membuat tabel hanya karena daftar di atas.

Gunakan struktur yang benar-benar diperlukan.

Pastikan:

* primary key
* foreign key
* index
* unique constraint
* created_at
* updated_at

digunakan dengan tepat.

==================================================
23. DATA INTEGRITY
==================

Perhatikan:

foreign key

cascade behavior

decimal precision

transaction

unique number

duplicate submission

negative stock

deleted ingredient yang masih dipakai resep

deleted product yang sudah pernah dijual

harga bahan lama

perubahan resep

riwayat transaksi lama.

PENTING:

Jika resep berubah hari ini, transaksi lama tidak boleh berubah HPP-nya secara historis.

Karena itu, pertimbangkan menyimpan snapshot:

harga bahan saat transaksi
HPP saat transaksi
harga jual saat transaksi

Dengan demikian laporan lama tetap konsisten.

==================================================
24. SOFT DELETE
===============

Untuk data yang sudah digunakan transaksi:

jangan langsung DELETE permanen.

Gunakan:

is_active

atau soft delete.

Contoh:

Bahan sudah digunakan oleh 100 transaksi.

Admin menonaktifkan bahan.

Bahan tidak muncul sebagai bahan aktif baru.

Tetapi histori transaksi tetap aman.

==================================================
25. KEAMANAN
============

Gunakan:

PDO prepared statements

atau mekanisme query aman yang setara.

Jangan menggunakan SQL string concatenation dari input user.

Validasi input server-side.

Escape output HTML.

Gunakan CSRF protection untuk action penting jika arsitektur memungkinkan.

Jangan menyimpan password plain text.

Gunakan password_hash().

Jangan commit:

config.local.php

password

API key

credential database

secret key.

==================================================
26. ERROR HANDLING
==================

Jangan menampilkan error database mentah kepada user production.

Buat:

user-friendly error message

dan logging untuk developer.

Contoh:

User:

"Gagal menyimpan transaksi. Silakan coba lagi."

Developer log:

detail error sebenarnya.

==================================================
27. API / AJAX
==============

Jika diperlukan untuk POS dan UI interaktif:

buat endpoint PHP khusus.

Contoh:

/api/products.php

/api/ingredients.php

/api/sales.php

/api/stock.php

/api/dashboard.php

Jangan membuat semua AJAX logic di index.php.

Gunakan JSON response yang konsisten.

Contoh:

{
"success": true,
"message": "Transaksi berhasil disimpan",
"data": {}
}

Untuk error:

{
"success": false,
"message": "Stok bahan tidak mencukupi",
"errors": []
}

==================================================
28. JAVASCRIPT
==============

Tetap gunakan Vanilla JavaScript.

Pisahkan modul:

app.js

pos.js

inventory.js

products.js

recipes.js

dashboard.js

pwa.js

dan sebagainya jika diperlukan.

Jangan membuat satu file JS ribuan baris.

==================================================
29. PERHITUNGAN HPP
===================

Pastikan seluruh kalkulasi memiliki satu sumber logika.

Jangan menghitung HPP berbeda antara:

halaman produk

dashboard

POS

laporan.

Buat satu business logic/service untuk:

calculateIngredientCost()

calculateRecipeCost()

calculateProductCost()

calculateSaleCost()

dan fungsi terkait.

Jika struktur project berbeda, sesuaikan dengan arsitektur yang ada.

==================================================
30. HISTORY HARGA
=================

Harga bahan dapat berubah.

Contoh:

Januari:

Tepung = Rp12.000/kg

Februari:

Tepung = Rp14.000/kg

Resep baru harus menggunakan harga terbaru.

Tetapi transaksi lama tetap menggunakan snapshot harga saat transaksi.

Jika memungkinkan buat histori harga pembelian.

==================================================
31. PERUBAHAN RESEP
===================

Jika resep berubah:

Produk lama:

Kopi 18 gram

Produk baru:

Kopi 20 gram

Transaksi lama tetap:

18 gram

Transaksi baru:

20 gram

Jangan menghitung ulang transaksi lama menggunakan resep terbaru.

==================================================
32. PRODUK NON-STOCK
====================

Pertimbangkan produk yang tidak membutuhkan inventory.

Contoh:

Jasa desain

atau produk tanpa resep.

Sediakan opsi:

TRACK_INVENTORY = true/false

Jika false:

produk tetap dapat dijual tanpa memotong bahan.

==================================================
33. HARGA JUAL DAN DISKON
=========================

POS harus mendukung:

harga produk

qty

diskon per item jika diperlukan

diskon transaksi

subtotal

grand total

pembayaran

kembalian

Pastikan diskon tidak merusak kalkulasi HPP.

HPP tetap berdasarkan cost.

==================================================
34. RETUR / VOID
================

Siapkan mekanisme:

void transaksi

atau retur.

Jika transaksi dibatalkan:

stok bahan harus dikembalikan.

Namun jangan menghapus histori transaksi.

Status dapat berupa:

COMPLETED
VOID
RETURNED

dan stock movement mencatat perubahan.

==================================================
35. USER / ROLE
===============

Jika belum ada sistem user, siapkan struktur sederhana:

ADMIN
KASIR

ADMIN:

* kelola bahan
* kelola resep
* kelola produk
* kelola pembelian
* stock opname
* laporan
* pengaturan

KASIR:

* POS
* melihat produk
* melakukan transaksi
* melihat transaksi sendiri

Jangan membuat permission system terlalu kompleks pada tahap pertama.

==================================================
36. AUDIT LOG
=============

Jika memungkinkan buat audit log untuk aksi penting:

* login
* tambah bahan
* edit bahan
* pembelian
* stock opname
* edit resep
* transaksi
* void transaksi
* perubahan harga

Contoh:

User:
Admin

Action:
STOCK_OPNAME

Tanggal:
22 September 2026

==================================================
37. BACKUP / EXPORT
===================

Siapkan kemampuan export:

CSV

untuk:

* bahan
* produk
* penjualan
* stock movement

Jika mudah diterapkan, siapkan export Excel-compatible CSV terlebih dahulu.

Jangan menggunakan library berat jika tidak diperlukan.

==================================================
38. STRUK
=========

POS harus memiliki opsi:

lihat detail transaksi

dan:

print receipt

Gunakan format yang cocok untuk:

printer thermal

58mm / 80mm

Tetapi jangan bergantung pada printer tertentu.

Gunakan browser print CSS.

==================================================
39. SEARCH DAN FILTER
=====================

Semua data penting harus dapat dicari.

Bahan:

* search
* kategori
* stok menipis

Produk:

* search
* kategori
* status

Transaksi:

* nomor transaksi
* tanggal
* metode pembayaran

==================================================
40. STOCK ALERT
===============

Jika:

stok <= minimum_stock

tampilkan:

"Stok Menipis"

Dashboard juga menampilkan daftar bahan yang perlu direstock.

==================================================
41. KATEGORI
============

Gunakan kategori untuk:

bahan

produk

Contoh bahan:

* bahan utama
* bahan tambahan
* kemasan

Produk:

* makanan
* minuman
* bakery
* lainnya

Kategori harus dapat dikelola admin jika memungkinkan.

==================================================
42. SETTINGS
============

Buat halaman Settings minimal:

Nama bisnis

Alamat

Nomor telepon

Mata uang

Markup murah

Markup normal

Markup mahal

Stock validation mode

Timezone

dan konfigurasi lain yang relevan.

==================================================
43. DATABASE MIGRATION
======================

Karena project sudah memiliki database:

JANGAN langsung menghancurkan database lama.

Jika perubahan database diperlukan:

buat:

database/migrations/

atau folder SQL migration yang sesuai.

Contoh:

001_add_units.sql

002_add_stock_movements.sql

003_add_sales.sql

dan seterusnya.

Pastikan migration dapat dijalankan secara berurutan.

==================================================
44. README
==========

Setelah implementasi selesai:

perbarui README.md.

README harus menjelaskan:

* project
* fitur
* teknologi
* requirement
* instalasi
* database setup
* PWA
* struktur project
* deployment
* konfigurasi
* akun default jika ada
* troubleshooting

==================================================
45. DEPLOYMENT
==============

Pastikan project tetap kompatibel dengan shared hosting.

Jangan menggunakan:

Docker sebagai requirement production.

Jangan membutuhkan:

npm

Node.js

untuk menjalankan aplikasi production.

Jika build asset diperlukan:

build dilakukan saat development.

File hasil build dapat diupload ke hosting.

Target akhir:

project dapat diupload ke InfinityFree/shared hosting dengan:

PHP
MySQL
static assets

==================================================
46. PRIORITAS IMPLEMENTASI
==========================

Jangan mengerjakan semuanya secara acak.

Gunakan tahapan berikut:

PHASE 1

Audit project existing.

PHASE 2

Perbaikan database dan model data.

PHASE 3

Perbaikan sistem bahan dan satuan.

PHASE 4

Recipe/BOM.

PHASE 5

HPP engine.

PHASE 6

Inventory dan stock movement.

PHASE 7

Pembelian/restock.

PHASE 8

POS.

PHASE 9

Automatic stock deduction.

PHASE 10

Stock opname dan waste.

PHASE 11

Dashboard dan laporan.

PHASE 12

User/role.

PHASE 13

PWA.

PHASE 14

UX/UI refinement.

PHASE 15

Security audit.

PHASE 16

Deployment readiness.

==================================================
47. TESTING
===========

Setiap fitur penting harus diuji.

Minimal buat skenario testing:

TEST 1

Buat bahan:

Tepung
10 kg
Rp120.000

Pastikan:

harga dasar = Rp12.000/kg.

TEST 2

Buat resep:

Tepung 250 gram.

Pastikan HPP:

Rp3.000.

TEST 3

Jual produk 1x.

Pastikan stok tepung berkurang:

250 gram.

TEST 4

Jual produk 3x.

Pastikan stok berkurang:

750 gram.

TEST 5

Stok tidak cukup.

Pastikan transaksi ditolak jika strict stock aktif.

TEST 6

Pembelian bahan.

Pastikan stok bertambah.

TEST 7

Stock opname.

Pastikan selisih tercatat.

TEST 8

Waste.

Pastikan stok berkurang dan tercatat.

TEST 9

Void transaksi.

Pastikan stok dikembalikan sesuai resep transaksi tersebut.

TEST 10

Harga bahan berubah.

Pastikan transaksi lama tidak berubah HPP-nya.

TEST 11

Resep berubah.

Pastikan transaksi lama tidak berubah.

TEST 12

Offline.

Pastikan UI PWA tetap dapat dibuka jika asset sudah tercache.

==================================================
48. HAL YANG TIDAK BOLEH DILAKUKAN
==================================

Jangan:

* menghapus database lama tanpa backup
* menghapus fitur lama tanpa alasan
* mengganti seluruh project dengan framework baru
* menggunakan fake data sebagai implementasi final
* membuat kalkulasi HPP di banyak tempat
* mengurangi stok hanya dari frontend
* mempercayai harga dari frontend tanpa validasi backend
* membiarkan stok menjadi negatif tanpa pengaturan
* menghapus histori transaksi
* mengubah HPP transaksi lama ketika resep berubah
* menggunakan floating point sembarangan untuk uang
* menyimpan password plain text
* memasukkan config.local.php ke git
* membuat fitur offline palsu yang mengklaim MySQL tetap bekerja offline
* membuat UI yang hanya bagus di desktop
* membuat UI mobile sebagai versi desktop yang diperkecil

==================================================
49. OUTPUT YANG SAYA INGINKAN DARI ANDA
=======================================

Kerjakan langsung di workspace.

Jangan hanya memberikan penjelasan atau contoh kode.

Anda harus:

1. audit project
2. buat rencana implementasi
3. perbaiki struktur jika diperlukan
4. implementasikan database
5. implementasikan backend
6. implementasikan frontend
7. implementasikan POS
8. implementasikan inventory
9. implementasikan recipe
10. implementasikan HPP
11. implementasikan stock deduction
12. implementasikan stock opname
13. implementasikan laporan
14. implementasikan PWA
15. testing
16. security review
17. deployment review
18. update README

Setiap kali selesai satu tahap penting:

* pastikan tidak ada syntax error
* pastikan query valid
* pastikan relasi database benar
* pastikan fitur lama tidak rusak
* lakukan testing sederhana.

Jika menemukan masalah pada implementasi lama:

perbaiki akar masalahnya, bukan hanya menambal gejalanya.

==================================================
50. ATURAN AGENT
================

Jangan berhenti hanya karena project memiliki struktur lama.

Adaptasikan perubahan dengan project existing.

Jangan meminta saya menyalin file satu per satu jika file sudah tersedia di workspace.

Baca file langsung dari workspace.

Jangan membuat keputusan arsitektur besar tanpa terlebih dahulu memahami kode existing.

Jika ada dua pilihan implementasi:

pilih solusi yang:

* sederhana
* stabil
* mudah dirawat
* cocok untuk shared hosting
* aman
* scalable secukupnya
* tidak over-engineered.

Prioritas:

DATA INTEGRITY

>

BUSINESS LOGIC

>

CORRECT HPP

>

CORRECT STOCK

>

CORRECT TRANSACTION

>

SECURITY

>

UX

>

VISUAL

==================================================
START
=====

Mulai sekarang dengan:

STEP 1:

Audit seluruh project yang ada.

Jangan langsung rewrite.

Identifikasi:

* struktur folder
* file utama
* database
* tabel
* relasi
* fitur existing
* bug
* masalah kalkulasi HPP
* masalah inventory
* masalah satuan
* masalah keamanan
* masalah deployment

Kemudian buat file:

PROJECT_AUDIT.md

yang berisi hasil audit dan rencana implementasi.

Setelah itu lanjutkan implementasi secara bertahap sesuai PHASE yang telah ditentukan.

Pastikan setiap perubahan tetap kompatibel dengan PHP + MySQL + shared hosting dan target akhir PWA.

Jalankan testing setelah setiap fase penting.

Tujuan akhir:

HPP Smart Pricing berubah menjadi aplikasi PWA POS + Inventory + Recipe + HPP + Stock Management + Sales Reporting yang benar-benar dapat digunakan untuk operasional bisnis kecil/UMKM.

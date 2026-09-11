# RULES.md

# HPP Smart Pricing — Business Rules

Dokumen ini adalah sumber utama aturan bisnis dan perhitungan aplikasi.

Jika implementasi kode bertentangan dengan dokumen ini, aturan di dokumen ini harus diprioritaskan.

---

# 1. Konsep Utama

HPP produk dihitung dari:

```text
HPP =
Biaya Bahan
+
Biaya Alat
+
Biaya Operasional
```

Tidak boleh menghitung harga jual langsung dari harga bahan tanpa memperhitungkan komponen biaya yang dipilih.

---

# 2. Ingredient Cost

Formula dasar:

```text
Ingredient Cost =
Quantity Used × Unit Cost
```

Contoh:

```text
Kopi:
Harga = Rp120.000 / 1.000 gram

Unit Cost:
Rp120.000 / 1.000
= Rp120 / gram

Recipe:
18 gram

Cost:
18 × Rp120
= Rp2.160
```

---

# 3. Unit Conversion

Sistem harus dapat mengkonversi unit yang kompatibel.

Contoh:

```text
1 kg = 1000 gram
1 liter = 1000 ml
```

Harga harus dinormalisasi terlebih dahulu sebelum menghitung recipe cost.

---

# 4. Ingredient Price Range

Ingredient dapat memiliki beberapa price records.

Contoh:

```text
Rp100.000
Rp110.000
Rp120.000
Rp130.000
Rp140.000
```

Sistem menyediakan:

```text
Minimum
Average
Maximum
Latest
```

---

# 5. Price Calculation Mode

User dapat memilih metode:

### Minimum

Gunakan harga terendah dari price history.

### Average

Gunakan rata-rata harga yang tersedia.

### Maximum

Gunakan harga tertinggi.

### Latest

Gunakan harga terakhir yang dicatat.

Default:

```text
Average
```

---

# 6. Recipe

Satu produk dapat memiliki satu resep aktif pada MVP.

Satu resep terdiri dari:

* ingredients
* equipment
* operational allocation

---

# 7. Recipe Ingredient

Setiap recipe ingredient memiliki:

```text
ingredient
quantity
unit
```

Contoh:

```text
Kopi      18 gram
Susu      100 ml
Gula      20 gram
Cup       1 pcs
```

---

# 8. Ingredient Cost Formula

```text
ingredientCost =
normalizedQuantity × normalizedUnitPrice
```

Total bahan:

```text
totalIngredientCost =
sum(all ingredient costs)
```

---

# 9. Equipment Cost

MVP menggunakan biaya penggunaan.

Contoh:

```text
Mesin Espresso
Cost per use = Rp500
```

Jika digunakan sekali:

```text
equipmentCost = Rp500
```

Jika digunakan dua kali:

```text
equipmentCost = Rp1.000
```

---

# 10. Operational Cost

Operational cost dapat memiliki:

```text
monthlyCost
productionEstimate
```

Formula:

```text
operationalCostPerProduct =
monthlyCost / productionEstimate
```

Contoh:

```text
Listrik = Rp1.500.000
Produksi = 1.000 produk

Rp1.500.000 / 1.000
= Rp1.500 / produk
```

---

# 11. Total Operational Cost

Jika terdapat beberapa biaya:

```text
Listrik       Rp1.500
Air             Rp300
Gas             Rp500
Tenaga Kerja  Rp3.000
Sewa          Rp2.000
```

Total:

```text
Rp7.300 / product
```

---

# 12. Total HPP

Formula:

```text
HPP =
Total Ingredient Cost
+
Total Equipment Cost
+
Total Operational Cost
```

Contoh:

```text
Ingredient      Rp7.060
Equipment         Rp500
Operational     Rp7.300
-----------------------
HPP            Rp14.860
```

---

# 13. Selling Price

Harga jual dapat dihitung menggunakan markup.

Formula:

```text
Selling Price =
HPP × (1 + Markup)
```

Contoh:

```text
HPP = Rp14.860
Markup = 30%

Rp14.860 × 1.30
= Rp19.318
```

---

# 14. Default Pricing Tier

Default:

```text
Murah       15%
Normal      30%
Mahal       50%
```

Nama tier harus dapat dikonfigurasi di masa depan.

---

# 15. Pricing Result

Untuk HPP Rp14.860:

```text
Murah:
14.860 × 1.15
= 17.089

Normal:
14.860 × 1.30
= 19.318

Mahal:
14.860 × 1.50
= 22.290
```

---

# 16. Rounding

Harga jual harus dapat dibulatkan.

Default rounding:

```text
Rp500
```

Contoh:

```text
Rp17.089 → Rp17.000
Rp19.318 → Rp19.500
Rp22.290 → Rp22.500
```

Namun sistem harus memungkinkan setting rounding di masa depan.

Pilihan:

```text
Tidak dibulatkan
Rp100
Rp500
Rp1.000
Rp5.000
```

---

# 17. Markup vs Margin

Sistem harus membedakan:

### Markup

```text
Markup =
Profit / HPP × 100%
```

### Margin

```text
Margin =
Profit / Selling Price × 100%
```

Contoh:

```text
HPP = Rp10.000
Harga Jual = Rp15.000
Profit = Rp5.000
```

Markup:

```text
5.000 / 10.000
= 50%
```

Margin:

```text
5.000 / 15.000
= 33,33%
```

Jangan menyamakan keduanya.

---

# 18. Profit

Formula:

```text
Profit =
Selling Price - HPP
```

---

# 19. Profit Simulation

User dapat memasukkan jumlah produk terjual.

Formula:

```text
Estimated Profit =
Profit Per Product × Quantity Sold
```

Contoh:

```text
Profit/product = Rp5.000
Quantity = 100

Estimated Profit = Rp500.000
```

---

# 20. Live Calculation

Pada recipe builder:

Ketika user mengubah:

* bahan
* quantity
* unit
* harga
* alat
* operational cost

HPP preview harus diperbarui secara interaktif.

Tidak harus menunggu halaman reload.

---

# 21. Data Validation

Harga tidak boleh:

```text
< 0
```

Quantity tidak boleh:

```text
<= 0
```

Production estimate tidak boleh:

```text
<= 0
```

Markup tidak boleh negatif kecuali fitur discount pricing secara eksplisit ditambahkan.

---

# 22. Empty Recipe

Produk tidak boleh dianggap memiliki HPP valid apabila:

```text
Tidak ada ingredient
```

Minimal harus ada satu cost component.

---

# 23. Missing Price

Jika ingredient belum memiliki harga:

Jangan menghitung:

```text
Rp0
```

Sebagai gantinya tampilkan:

```text
Harga bahan belum tersedia.
Tambahkan harga terlebih dahulu.
```

Ini penting agar HPP tidak terlihat valid padahal sebenarnya salah.

---

# 24. Missing Operational Data

Jika operational cost diaktifkan tetapi belum memiliki:

```text
monthlyCost
```

atau:

```text
productionEstimate
```

sistem harus menampilkan warning.

Jangan diam-diam menggunakan Rp0.

---

# 25. Currency

Default currency:

```text
IDR / Rupiah
```

Format:

```text
Rp 15.000
```

Gunakan locale Indonesia.

Contoh:

```ts
Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
})
```

Hindari formatting currency manual yang tidak konsisten.

---

# 26. Calculation Precision

Perhitungan internal boleh menggunakan decimal precision yang lebih tinggi.

Pembulatan dilakukan ketika menampilkan hasil atau ketika aturan bisnis mengharuskannya.

Jangan melakukan rounding terlalu awal karena dapat menghasilkan error akumulatif.

---

# 27. Price Recommendation

Sistem tidak boleh menyatakan:

> "Harga ini pasti paling tepat."

Gunakan bahasa:

> "Rekomendasi berdasarkan margin yang dipilih."

Karena harga pasar, kompetitor, positioning, dan daya beli tidak otomatis diketahui oleh sistem.

---

# 28. Price Range Awareness

Jika menggunakan price range:

```text
HPP Minimum
HPP Average
HPP Maximum
```

sistem dapat menampilkan range:

```text
Estimasi HPP:
Rp8.500 — Rp10.900
```

Ini merupakan estimasi berdasarkan data harga bahan.

---

# 29. UX Rules for Calculation

Setiap perubahan input yang memengaruhi HPP harus memberikan feedback.

Contoh:

```text
HPP
Rp14.860

↓ setelah quantity kopi berubah

HPP
Rp15.100
```

Perubahan angka boleh menggunakan subtle animation.

Jangan menggunakan animasi berlebihan.

---

# 30. Destructive Rules

Menghapus bahan yang sedang digunakan oleh resep harus:

1. Memberikan warning.
2. Menampilkan jumlah resep yang terdampak.
3. Meminta confirmation.

Contoh:

```text
Bahan ini digunakan oleh 4 resep.

Menghapusnya dapat membuat HPP resep tersebut
tidak dapat dihitung.

[ Batal ] [ Lanjutkan ]
```

---

# 31. Historical Data

Price history tidak boleh dihapus hanya karena harga terbaru ditambahkan.

Contoh:

```text
01 Sep    Rp100.000
05 Sep    Rp110.000
10 Sep    Rp120.000
```

Data historis tetap tersedia.

---

# 32. Future Rules

Fitur berikut tidak termasuk MVP:

* inventory deduction
* purchase order
* sales tracking
* tax
* discount engine
* marketplace pricing
* competitor scraping

Jangan mengimplementasikan fitur tersebut tanpa requirement baru.

---

# 33. Priority

Urutan prioritas:

```text
1. Correct calculation
2. Data integrity
3. Usability
4. Responsiveness
5. Interactivity
6. Performance
7. Visual polish
```

Semua tetap penting, tetapi perhitungan tidak boleh dikorbankan demi visual.

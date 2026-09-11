# DESIGN.md

# HPP Smart Pricing — Design System

## 1. Design Philosophy

Aplikasi harus terlihat seperti **modern SaaS application**, tetapi tetap sederhana dan mudah dipahami oleh pemilik UMKM.

Karakter:

* modern
* clean
* professional
* interactive
* responsive
* approachable
* fast
* minimal
* informative

Jangan membuat desain yang terasa seperti:

* spreadsheet lama
* dashboard enterprise yang penuh tabel
* admin template generik
* landing page yang terlalu dekoratif

---

# 2. PRIMARY DESIGN PRINCIPLE

## INTERACTIVE FIRST

Interaksi adalah identitas utama aplikasi.

User harus dapat merasakan bahwa aplikasi:

> "merespons setiap perubahan yang saya lakukan."

Contoh:

User mengubah:

```text
Kopi
18 gram
```

menjadi:

```text
Kopi
20 gram
```

maka:

```text
Biaya bahan
Rp2.160
```

langsung berubah menjadi:

```text
Rp2.400
```

dan:

```text
Total HPP
```

ikut berubah.

---

# 3. Responsive First

Semua halaman wajib dibuat responsive.

Target:

```text
Mobile
320px+
```

```text
Tablet
768px+
```

```text
Laptop
1024px+
```

```text
Desktop
1280px+
```

Tidak boleh hanya mengecilkan desktop UI untuk mobile.

Mobile harus memiliki layout yang memang dipikirkan untuk mobile.

---

# 4. Mobile Navigation

Mobile menggunakan:

* compact header
* drawer
* bottom navigation jika sesuai

Jangan menampilkan sidebar desktop penuh pada layar kecil.

Contoh:

```text
┌─────────────────────┐
│ ☰  HPP Smart   👤  │
├─────────────────────┤
│                     │
│       Content       │
│                     │
│                     │
├─────────────────────┤
│ Home │ Resep │ HPP  │
└─────────────────────┘
```

---

# 5. Desktop Navigation

Desktop menggunakan sidebar.

```text
┌──────────────┬──────────────────────────────┐
│              │                              │
│ HPP Smart    │ Dashboard                    │
│              │                              │
│ Dashboard    │                              │
│ Bahan        │                              │
│ Resep        │                              │
│ Alat         │                              │
│ Operasional  │                              │
│              │                              │
│ HPP          │                              │
│ Harga Jual   │                              │
│              │                              │
│ Pengaturan   │                              │
│              │                              │
└──────────────┴──────────────────────────────┘
```

Sidebar dapat collapse.

---

# 6. Dashboard Design

Dashboard tidak boleh hanya berisi card statistik.

Gunakan hierarchy:

```text
Greeting
   ↓
Quick Action
   ↓
HPP Overview
   ↓
Recent Products
   ↓
Price Insights
```

Contoh:

```text
Selamat datang 👋

Mulai menghitung HPP produk Anda.

[ + Buat Produk ]

┌─────────────┐
│ 24 Produk   │
└─────────────┘

┌──────────────────────────────┐
│ Rata-rata HPP                │
│ Rp14.860                     │
│                              │
│  ↑ 4.2% dari periode lalu    │
└──────────────────────────────┘
```

---

# 7. Interactive Cards

Card dapat memiliki:

* hover
* press state
* subtle transition
* expandable content

Tetapi jangan membuat semua card clickable jika tidak perlu.

---

# 8. Recipe Builder

Recipe Builder adalah fitur utama.

Layout desktop:

```text
┌───────────────────────┬───────────────────────┐
│                       │                       │
│   RECIPE BUILDER      │    LIVE HPP PREVIEW   │
│                       │                       │
│   Product Name        │    Total HPP          │
│                       │    Rp14.860           │
│   Ingredients         │                       │
│   ─────────────       │    Ingredient         │
│   Kopi     18 gram    │    Rp7.060             │
│   Susu     100 ml     │                       │
│                       │    Equipment           │
│   + Add Ingredient    │    Rp500               │
│                       │                       │
│   Equipment           │    Operational         │
│   ─────────────       │    Rp7.300             │
│                       │                       │
│   Operational         │                       │
│                       │                       │
└───────────────────────┴───────────────────────┘
```

Mobile:

```text
Product
   ↓
Ingredients
   ↓
Equipment
   ↓
Operational
   ↓
HPP Preview
```

---

# 9. Live HPP Preview

HPP preview harus terlihat penting.

Gunakan visual hierarchy:

```text
TOTAL HPP

Rp14.860
```

Kemudian breakdown:

```text
Bahan             Rp7.060
Alat                 Rp500
Operasional        Rp7.300
──────────────────────────
Total             Rp14.860
```

---

# 10. Pricing Cards

Harga jual adalah output penting.

Gunakan tiga kartu:

```text
┌────────────┐
│ MURAH      │
│            │
│ Rp17.000   │
│ +15%       │
└────────────┘

┌────────────────┐
│ ★ REKOMENDASI  │
│ NORMAL         │
│                │
│ Rp19.500       │
│ +30%           │
└────────────────┘

┌────────────┐
│ MAHAL      │
│            │
│ Rp22.500   │
│ +50%       │
└────────────┘
```

Normal menjadi default recommendation.

---

# 11. Pricing Interaction

User dapat mengubah markup menggunakan:

* slider
* input number
* preset

Contoh:

```text
Markup

10% ───────●──────── 50%

30%
```

Ketika slider digerakkan:

```text
Harga Jual
Rp19.500
```

langsung berubah.

---

# 12. Profit Simulator

Gunakan interactive input:

```text
Jumlah terjual

[-] 100 [+]
```

Hasil:

```text
Pendapatan
Rp1.950.000

Total HPP
Rp1.486.000

Estimasi Profit
Rp464.000
```

Angka berubah secara live.

---

# 13. Tables

Table digunakan untuk data yang memang membutuhkan comparison.

Contoh:

```text
Bahan
────────────────────────────────
Nama       Unit    Harga    Updated
Kopi       gram    Rp120    12 Sep
Susu       ml      Rp25     10 Sep
```

Pada mobile:

Jangan memaksa table lebar.

Gunakan:

* card layout
* horizontal scroll jika memang diperlukan
* condensed table

Prioritaskan usability.

---

# 14. Forms

Form harus:

* grouped
* memiliki label
* memiliki helper text
* memiliki validation
* memiliki clear CTA

Contoh:

```text
Harga Beli

[ Rp 120.000 ]

Harga ini digunakan sebagai
harga pembelian per 1 kg.
```

Error:

```text
Harga harus lebih besar dari 0.
```

---

# 15. Buttons

Primary action harus jelas.

Contoh:

```text
[ + Tambah Bahan ]
```

Secondary:

```text
[ Batal ]
```

Destructive:

```text
[ Hapus ]
```

Jangan membuat semua button memiliki visual importance yang sama.

---

# 16. Feedback

Setiap action harus memiliki feedback.

Contoh:

Success:

```text
✓ Resep berhasil disimpan
```

Loading:

```text
Menyimpan...
```

Error:

```text
Gagal menyimpan resep.
```

---

# 17. Animation

Animation WAJIB subtle dan meaningful.

Gunakan untuk:

* number transition
* card hover
* modal
* drawer
* accordion
* tab transition
* page transition jika ringan
* success feedback

Jangan menggunakan:

* excessive bouncing
* continuous animation
* distracting background
* animation pada setiap element

---

# 18. Motion Principles

Default:

```text
150ms — 250ms
```

Gunakan easing yang natural.

Animation harus dapat dimatikan untuk user yang memilih reduced motion.

Gunakan:

```css
prefers-reduced-motion
```

---

# 19. Number Animation

Angka HPP dan harga jual boleh menggunakan count-up animation.

Contoh:

```text
Rp14.860
```

berubah menjadi:

```text
Rp15.200
```

Animasi harus cepat dan tidak menghambat interaksi.

---

# 20. Color Strategy

Gunakan warna netral sebagai foundation.

Gunakan accent color hanya untuk:

* primary action
* active state
* recommendation
* important metric

Status:

```text
Success
Warning
Error
Info
```

harus memiliki semantic meaning.

Jangan mengandalkan warna saja untuk menyampaikan informasi.

---

# 21. Typography

Typography harus memiliki hierarchy jelas.

Contoh:

```text
Page Title
32px desktop
24px mobile

Section Title
20px

Body
14–16px

Supporting text
12–14px
```

Jangan menggunakan terlalu banyak font.

Gunakan satu font family utama.

---

# 22. Spacing

Gunakan spacing system yang konsisten.

Basis:

```text
4px
8px
12px
16px
24px
32px
48px
64px
```

Jangan menggunakan spacing random seperti:

```text
13px
17px
29px
37px
```

kecuali memang diperlukan.

---

# 23. Border Radius

Gunakan radius modern tetapi jangan berlebihan.

Contoh:

```text
Buttons       8px
Inputs        8px
Cards         12px
Dialogs       16px
```

Konsisten.

---

# 24. Shadows

Gunakan shadow ringan.

Hindari:

* heavy glow
* excessive shadow
* neumorphism berlebihan

UI harus tetap clean.

---

# 25. Empty State

Empty state harus membantu user mengambil action.

Contoh:

```text
Belum ada resep

Buat resep pertama untuk mulai
menghitung HPP.

[ + Buat Resep ]
```

---

# 26. Skeleton Loading

Untuk halaman data:

Gunakan skeleton.

Contoh:

```text
┌────────────────────────┐
│ ███████████            │
│                        │
│ ████████               │
└────────────────────────┘
```

Hindari blank screen ketika data sedang loading.

---

# 27. Error State

Error state harus menjelaskan:

1. Apa yang terjadi.
2. Apa yang dapat dilakukan user.

Contoh:

```text
Tidak dapat memuat data bahan.

Coba periksa koneksi Anda.

[ Coba Lagi ]
```

---

# 28. Responsive Interaction

Desktop:

```text
Hover
Tooltip
Sidebar
Multi-column
```

Mobile:

```text
Tap
Drawer
Bottom sheet
Single column
Sticky CTA
```

Jangan bergantung pada hover karena hover tidak tersedia pada mobile.

---

# 29. Sticky Actions

Pada form panjang di mobile, primary CTA dapat dibuat sticky.

Contoh:

```text
┌─────────────────────────────┐
│                             │
│         Form                │
│                             │
│                             │
├─────────────────────────────┤
│ [ Simpan Resep ]            │
└─────────────────────────────┘
```

Pastikan tidak menutupi content.

---

# 30. Interactive Feedback

Saat user:

* menambahkan bahan
* menghapus bahan
* mengubah quantity
* memilih harga
* mengubah markup
* mengubah jumlah produk

UI harus memberikan feedback yang jelas.

Contoh:

```text
18 gram
   ↓
20 gram

Biaya:
Rp2.160
   ↓
Rp2.400
```

---

# 31. Accessibility

Pastikan:

* keyboard usable
* focus visible
* form label tersedia
* dialog dapat ditutup keyboard
* button memiliki label
* icon-only button memiliki aria-label
* color contrast cukup

---

# 32. Mobile Priority

Jika terjadi konflik layout:

```text
Mobile usability
>
Desktop density
```

Jangan mempertahankan layout desktop jika menyebabkan mobile buruk.

---

# 33. Design Anti-Patterns

Jangan membuat:

### ❌ Dashboard penuh card

```text
□ □ □ □
□ □ □ □
□ □ □ □
□ □ □ □
```

tanpa informasi bermakna.

### ❌ Table everywhere

Tidak semua data harus berupa table.

### ❌ Modal everywhere

Gunakan modal hanya jika memang sesuai.

### ❌ Animation everywhere

Interaksi harus memiliki tujuan.

### ❌ Tiny buttons

Terutama pada mobile.

### ❌ Desktop-only layout

Aplikasi harus mobile-first.

---

# 34. Core UX Goal

User harus dapat melakukan:

```text
Tambah bahan
      ↓
Buat produk
      ↓
Buat resep
      ↓
Pilih alat
      ↓
Pilih biaya operasional
      ↓
Melihat HPP
      ↓
Mengubah strategi harga
      ↓
Melihat harga jual
      ↓
Simulasi keuntungan
```

dengan alur yang terasa natural.

---

# 35. Final Design Rule

Jika suatu desain terlihat bagus tetapi:

* sulit digunakan
* terlalu lambat
* tidak responsive
* terlalu banyak klik
* tidak memberikan feedback

maka desain tersebut dianggap gagal.

Prioritas:

> **Useful → Interactive → Responsive → Clear → Beautiful**

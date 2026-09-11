# AGENT.md

## Project Identity

Nama project: **HPP Smart Pricing**

HPP Smart Pricing adalah aplikasi web untuk membantu pengguna menghitung Harga Pokok Produksi (HPP) dan menentukan rekomendasi harga jual produk berdasarkan:

* Bahan
* Harga bahan
* Riwayat/rentang harga bahan
* Resep
* Alat
* Biaya operasional
* Strategi harga jual
* Margin/markup
* Simulasi keuntungan

Aplikasi harus terasa seperti aplikasi modern, bukan sekadar kalkulator HTML.

---

# 1. Tujuan Utama

Bangun aplikasi web yang:

1. Cepat.
2. Modern.
3. Interaktif.
4. Responsive.
5. Mudah digunakan pengguna non-teknis.
6. Memiliki perhitungan HPP yang konsisten.
7. Mudah dikembangkan.
8. Mudah di-maintain.
9. Aman terhadap kesalahan input.
10. Mudah di-deploy.

Prioritas utama:

> **UX dan interaksi adalah bagian inti aplikasi, bukan kosmetik.**

Jangan membuat aplikasi yang hanya berisi form dan tabel statis.

---

# 2. Prinsip Utama Agent

Agent wajib mengikuti prinsip berikut:

### 2.1 Jangan over-engineering

Jangan menambahkan:

* Microservices
* Redis
* Message broker
* Kubernetes
* GraphQL
* Event sourcing
* Infrastruktur kompleks

kecuali memang diperlukan oleh kebutuhan aplikasi.

Project ini harus tetap sederhana tetapi memiliki fondasi yang baik.

---

### 2.2 Jangan mengubah business rules sembarangan

Semua aturan perhitungan harus mengacu kepada:

`RULES.md`

Jika terdapat ambiguitas dalam perhitungan:

1. Cari aturan di `RULES.md`.
2. Jika belum tersedia, gunakan implementasi paling sederhana dan konsisten.
3. Dokumentasikan asumsi tersebut.
4. Jangan mengubah aturan existing hanya untuk mempermudah coding.

---

### 2.3 Jangan membuat UI asal jadi

Semua UI harus mengacu kepada:

`DESIGN.md`

UI harus:

* responsive
* accessible
* interactive
* memiliki feedback
* memiliki loading state
* memiliki empty state
* memiliki error state
* memiliki success state

---

# 3. Tech Stack

Gunakan:

* Next.js
* TypeScript
* Tailwind CSS
* shadcn/ui
* PostgreSQL
* Drizzle ORM
* Zod
* React Hook Form
* Git
* GitHub

Gunakan App Router Next.js.

Gunakan Server Components secara default.

Gunakan Client Components hanya jika membutuhkan:

* state interaktif
* event handler
* browser API
* animation
* form interaction
* realtime UI state

---

# 4. Bahasa dan Naming

Bahasa UI:

**Bahasa Indonesia**

Kode:

**Bahasa Inggris**

Contoh:

UI:

`Tambah Bahan`

Kode:

`addIngredient()`

Gunakan naming yang jelas.

Hindari:

```text
x
data2
tmp
foo
test123
```

Gunakan:

```text
ingredient
recipeIngredient
operationalCost
sellingPrice
hppResult
```

---

# 5. Struktur Project

Gunakan struktur yang terorganisir.

Contoh:

```text
src/
├── app/
│   ├── (auth)/
│   ├── dashboard/
│   ├── ingredients/
│   ├── equipment/
│   ├── operational-costs/
│   ├── recipes/
│   ├── products/
│   └── settings/
│
├── components/
│   ├── ui/
│   ├── layout/
│   ├── dashboard/
│   ├── ingredients/
│   ├── recipes/
│   ├── hpp/
│   └── pricing/
│
├── lib/
│   ├── db/
│   ├── calculations/
│   ├── validations/
│   ├── utils/
│   └── constants/
│
├── actions/
├── types/
└── config/
```

Struktur boleh berkembang jika memang diperlukan.

Jangan membuat folder hanya demi terlihat rapi.

---

# 6. Business Logic

Semua perhitungan utama harus berada di layer calculation.

Contoh:

```text
src/lib/calculations/
├── ingredient-cost.ts
├── recipe-cost.ts
├── equipment-cost.ts
├── operational-cost.ts
├── hpp.ts
└── selling-price.ts
```

UI tidak boleh mengandung rumus HPP kompleks.

Jangan melakukan:

```tsx
const hpp = ...
```

dengan rumus bisnis panjang langsung di component.

Gunakan:

```ts
calculateHpp(...)
```

---

# 7. Validation

Gunakan Zod untuk validasi.

Semua input penting harus divalidasi:

* nama bahan
* satuan
* harga
* kuantitas
* resep
* biaya operasional
* margin
* markup

Validasi harus dilakukan:

1. Client-side untuk UX.
2. Server-side untuk keamanan.

Jangan percaya data dari client.

---

# 8. Database

Gunakan PostgreSQL + Drizzle ORM.

Semua perubahan database harus melalui migration.

Jangan mengubah database production secara manual tanpa migration.

Jaga foreign key dan referential integrity.

---

# 9. Interactive-First Requirement

Ini adalah requirement WAJIB.

Setiap halaman utama harus memiliki elemen interaktif yang bermakna.

Contoh:

* live calculation
* dynamic form
* searchable table
* filter
* sort
* modal
* drawer
* tabs
* expandable details
* interactive cards
* slider
* toggle
* inline editing
* confirmation dialog
* preview
* realtime calculation result

Interaksi harus memiliki tujuan.

Jangan menambahkan animasi hanya untuk dekorasi.

---

# 10. Responsive Requirement

WAJIB mendukung:

* Mobile phone
* Tablet
* Laptop
* Desktop

Minimal test layout:

```text
320px
375px
390px
768px
1024px
1280px
1440px
```

Tidak boleh ada:

* horizontal overflow yang tidak diperlukan
* text terpotong
* button keluar layar
* tabel menghancurkan layout
* modal terlalu besar
* form tidak usable di mobile

---

# 11. Mobile First

Design dan implementasi harus dimulai dari mobile.

Desktop kemudian diperluas menggunakan responsive breakpoint.

Mobile navigation dapat menggunakan:

* bottom navigation
* drawer
* compact sidebar

Desktop dapat menggunakan:

* sidebar
* top navigation

---

# 12. Accessibility

Gunakan:

* semantic HTML
* label form
* keyboard navigation
* visible focus state
* aria-label jika diperlukan
* contrast yang baik
* accessible dialog
* accessible dropdown
* accessible tooltip

Jangan membuat interaksi yang hanya dapat digunakan dengan mouse.

---

# 13. Loading State

Setiap operasi async harus memiliki feedback.

Contoh:

```text
Menyimpan...
Menghitung...
Memuat data...
```

Gunakan:

* skeleton
* spinner
* disabled state
* progress feedback

Jangan membuat pengguna bertanya-tanya apakah tombol berhasil ditekan.

---

# 14. Error Handling

Error harus human-friendly.

Jangan tampilkan:

```text
Error: PrismaClientKnownRequestError...
```

kepada user.

Gunakan:

```text
Gagal menyimpan bahan.

Silakan periksa data yang dimasukkan
dan coba lagi.
```

Detail teknis tetap dicatat di server/log.

---

# 15. Empty State

Semua halaman yang dapat kosong harus memiliki empty state.

Contoh:

```text
Belum ada bahan

Tambahkan bahan pertama untuk mulai
menghitung HPP produk.

[ + Tambah Bahan ]
```

---

# 16. Confirmation

Operasi destructive harus memiliki confirmation.

Contoh:

```text
Hapus bahan?

Bahan ini mungkin digunakan oleh beberapa resep.

[ Batal ] [ Hapus ]
```

Jangan langsung menghapus data penting.

---

# 17. Performance

Prioritaskan:

* server rendering
* code splitting
* lazy loading bila diperlukan
* image optimization
* minimal client JavaScript
* efficient database queries
* pagination untuk data besar
* debounce pada search
* debounce pada kalkulasi berat jika diperlukan

Jangan melakukan fetch berulang tanpa alasan.

---

# 18. Security

Jangan:

* expose database credential
* expose secret key
* trust client input
* memasukkan password ke source code
* mengirim seluruh database ke client
* menggunakan dangerouslySetInnerHTML tanpa alasan kuat

Gunakan environment variables.

---

# 19. Git

Gunakan commit yang jelas.

Contoh:

```text
feat: add ingredient management
feat: implement recipe calculation
fix: correct hpp calculation
refactor: extract pricing engine
style: improve mobile recipe form
```

Jangan menggunakan:

```text
update
fix
final
final2
new
coba
```

---

# 20. Agent Workflow

Sebelum mengimplementasikan fitur:

1. Baca `AGENT.md`.
2. Baca `ARSITEKTUR.md`.
3. Baca `RULES.md`.
4. Baca `DESIGN.md`.
5. Periksa struktur project.
6. Periksa kode existing.
7. Tentukan file yang benar-benar perlu diubah.
8. Implementasikan.
9. Jalankan lint.
10. Jalankan typecheck.
11. Jalankan build.
12. Perbaiki error.
13. Review responsive behavior.
14. Review interaction.
15. Review business calculation.

Jangan menghapus kode existing yang masih diperlukan.

---

# 21. Definition of Done

Sebuah fitur dianggap selesai apabila:

* [ ] Functionality berjalan.
* [ ] Business rules benar.
* [ ] Validation berjalan.
* [ ] Error handling tersedia.
* [ ] Loading state tersedia.
* [ ] Empty state tersedia jika diperlukan.
* [ ] Responsive.
* [ ] Mobile usable.
* [ ] Desktop usable.
* [ ] Accessibility dasar terpenuhi.
* [ ] Tidak ada TypeScript error.
* [ ] Tidak ada lint error.
* [ ] Production build berhasil.
* [ ] UI konsisten dengan DESIGN.md.

---

# 22. Rule Paling Penting

Jika harus memilih antara:

**fitur lebih banyak**

atau

**UX lebih baik**

pilih:

> **UX lebih baik.**

Jika harus memilih antara:

**animasi banyak**

atau

**interaksi yang berguna**

pilih:

> **interaksi yang berguna.**

Jika harus memilih antara:

**arsitektur kompleks**

atau

**arsitektur sederhana yang mudah dirawat**

pilih:

> **arsitektur sederhana yang mudah dirawat.**

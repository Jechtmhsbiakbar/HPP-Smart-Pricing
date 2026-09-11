# ARSITEKTUR.md

# HPP Smart Pricing Architecture

## 1. Architecture Goal

Aplikasi menggunakan arsitektur monolithic full-stack modern menggunakan Next.js.

Tujuan:

* sederhana
* scalable
* maintainable
* type-safe
* mudah deploy
* mudah dipahami developer
* cocok untuk MVP sampai production kecil/menengah

---

# 2. High-Level Architecture

```text
┌──────────────────────────────────────────┐
│                  USER                    │
└────────────────────┬─────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────┐
│               NEXT.JS APP                │
│                                          │
│  Pages / Server Components / UI          │
│                                          │
│  Client Components                       │
│  Forms / Interactive UI                  │
└────────────────────┬─────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────┐
│          SERVER ACTIONS / API            │
│                                          │
│ Validation                              │
│ Authorization                           │
│ Business orchestration                  │
└────────────────────┬─────────────────────┘
                     │
          ┌──────────┴──────────┐
          ▼                     ▼
┌──────────────────┐   ┌──────────────────┐
│ Calculation      │   │ Drizzle ORM      │
│ Engine           │   │                  │
│                  │   │ Database Access  │
└──────────────────┘   └────────┬─────────┘
                                │
                                ▼
                       ┌─────────────────┐
                       │   PostgreSQL    │
                       └─────────────────┘
```

---

# 3. Application Layers

## Layer 1 — Presentation

Berisi:

* pages
* layouts
* components
* forms
* charts
* dialogs
* interactive elements

Tidak boleh menyimpan business calculation kompleks.

---

## Layer 2 — Actions

Berisi server actions untuk:

* create
* update
* delete
* calculate
* save
* fetch

Contoh:

```text
actions/
├── ingredients.ts
├── recipes.ts
├── equipment.ts
├── operational-costs.ts
└── pricing.ts
```

---

## Layer 3 — Business Logic

Berisi calculation engine.

```text
lib/calculations/
```

Business logic harus pure function sebisa mungkin.

Contoh:

```ts
calculateIngredientCost()
calculateRecipeCost()
calculateOperationalCost()
calculateEquipmentCost()
calculateHpp()
calculateSellingPrices()
calculateProfit()
```

---

## Layer 4 — Data Access

Gunakan:

```text
Drizzle ORM
```

Database schema berada di:

```text
lib/db/schema/
```

---

# 4. Domain Model

Domain utama:

```text
Ingredient
IngredientPrice
Recipe
RecipeIngredient
Equipment
RecipeEquipment
OperationalCost
Product
PricingSetting
User
```

---

# 5. Entity Relationship

```text
USER
 │
 ├──────────────┐
 ▼              ▼
INGREDIENTS    PRODUCTS
 │              │
 ▼              ▼
INGREDIENT     RECIPES
PRICES          │
                ├───────────────┐
                ▼               ▼
       RECIPE_INGREDIENTS   RECIPE_EQUIPMENT
                │               │
                ▼               ▼
           INGREDIENT        EQUIPMENT


USER
 │
 ▼
OPERATIONAL_COSTS

USER
 │
 ▼
PRICING_SETTINGS
```

---

# 6. Ingredient Domain

Ingredient menyimpan informasi dasar bahan.

Contoh:

```text
Kopi Arabica
Susu UHT
Gula Aren
Cup 16oz
Es Batu
```

Informasi:

```text
id
name
baseUnit
description
createdAt
updatedAt
```

---

# 7. Ingredient Price Domain

Harga dipisahkan dari ingredient.

Contoh:

```text
Ingredient:
Kopi Arabica

Price records:

Rp100.000 / kg
Rp110.000 / kg
Rp125.000 / kg
Rp130.000 / kg
Rp140.000 / kg
```

Informasi price record:

```text
id
ingredientId
price
quantity
unit
recordedAt
```

Dengan ini aplikasi dapat menghitung:

* minimum price
* average price
* maximum price
* latest price

---

# 8. Unit Conversion

Aplikasi harus memiliki sistem unit.

Contoh:

```text
1 kg = 1000 gram
1 liter = 1000 ml
1 meter = 100 cm
```

Tetapi jangan melakukan conversion secara hard-coded di berbagai component.

Buat satu utility:

```text
lib/calculations/unit-conversion.ts
```

Semua conversion menggunakan utility tersebut.

---

# 9. Recipe Domain

Recipe menghubungkan produk dengan bahan.

Contoh:

```text
Kopi Susu Gula Aren

Kopi Arabica     18 gram
Susu             100 ml
Gula Aren         20 gram
Es                100 gram
Cup                 1 pcs
```

Database:

```text
products
recipes
recipe_ingredients
```

---

# 10. Equipment Domain

Equipment digunakan untuk menghitung biaya alat.

Contoh:

```text
Mesin Espresso
Blender
Sealer
Grinder
```

MVP dapat menggunakan:

```text
costPerUse
```

Contoh:

```text
Mesin Espresso
costPerUse = Rp500
```

Future version dapat mendukung:

* purchase price
* useful life
* depreciation
* utilization
* maintenance

Jangan membuat depreciation kompleks pada MVP kecuali dibutuhkan.

---

# 11. Operational Cost Domain

Operational cost dapat berupa:

```text
Listrik
Air
Gas
Sewa
Tenaga Kerja
Internet
Transportasi
Biaya lainnya
```

Model alokasi MVP:

```text
totalMonthlyCost
estimatedMonthlyProduction
```

Formula:

```text
costPerProduct =
totalMonthlyCost / estimatedMonthlyProduction
```

---

# 12. HPP Calculation Pipeline

```text
Ingredient Price
       │
       ▼
Unit Conversion
       │
       ▼
Recipe Ingredient Cost
       │
       ▼
Total Ingredient Cost
       │
       ├──────────────┐
       ▼              ▼
Equipment Cost    Operational Cost
       │              │
       └──────┬───────┘
              ▼
          TOTAL HPP
              │
              ▼
       PRICING ENGINE
              │
      ┌───────┼────────┐
      ▼       ▼        ▼
    Cheap   Normal   Premium
```

---

# 13. Calculation Engine

Calculation engine harus deterministic.

Input sama:

```text
ingredient data
recipe
equipment
operational cost
pricing settings
```

Output harus sama.

Jangan bergantung pada UI state.

---

# 14. Pricing Engine

Pricing engine menerima:

```text
hpp
pricing settings
rounding settings
```

Output:

```text
cheapPrice
normalPrice
premiumPrice
```

Juga:

```text
profit
markup
margin
```

---

# 15. Frontend Data Flow

```text
User Interaction
       │
       ▼
Client Component
       │
       ▼
React Hook Form
       │
       ▼
Zod Validation
       │
       ▼
Server Action
       │
       ▼
Business Logic
       │
       ▼
Database
       │
       ▼
Updated UI
```

Untuk kalkulasi preview yang tidak perlu disimpan:

```text
User Input
   ↓
Client Calculation
   ↓
Live Result
```

Namun hasil final yang disimpan harus divalidasi ulang di server.

---

# 16. Route Structure

Gunakan struktur:

```text
/
├── /login
├── /register
│
└── /dashboard
    ├── /ingredients
    ├── /equipment
    ├── /operational-costs
    ├── /products
    ├── /recipes
    ├── /hpp
    ├── /pricing
    └── /settings
```

Dashboard menjadi protected area.

---

# 17. Component Architecture

Component dibagi berdasarkan domain.

```text
components/
├── ui/
│
├── ingredients/
│   ├── ingredient-table
│   ├── ingredient-form
│   ├── ingredient-price-history
│   └── ingredient-price-input
│
├── recipes/
│   ├── recipe-builder
│   ├── ingredient-selector
│   ├── equipment-selector
│   └── recipe-summary
│
├── hpp/
│   ├── hpp-breakdown
│   ├── hpp-card
│   └── hpp-comparison
│
└── pricing/
    ├── pricing-card
    ├── pricing-slider
    └── profit-simulator
```

---

# 18. Interactive Recipe Builder

Recipe builder adalah salah satu komponen paling penting.

Flow:

```text
Create Product
      │
      ▼
Add Ingredient
      │
      ▼
Select Ingredient
      │
      ▼
Enter Quantity
      │
      ▼
Live Cost Calculation
      │
      ▼
Add Equipment
      │
      ▼
Add Operational Cost
      │
      ▼
Live HPP Preview
```

Tidak perlu submit berkali-kali hanya untuk melihat HPP.

---

# 19. State Management

Jangan menggunakan global state untuk semua hal.

Gunakan:

* React state untuk local UI state
* React Hook Form untuk forms
* Server state melalui server actions / appropriate fetching
* URL search params untuk filter/sort yang perlu shareable

Global state hanya jika benar-benar diperlukan.

---

# 20. Database Strategy

Development:

```text
Local PostgreSQL
```

Production:

```text
Hosted PostgreSQL
```

Environment:

```text
DATABASE_URL=
```

Jangan hard-code connection string.

---

# 21. Deployment Architecture

Target deployment:

```text
GitHub
   │
   ▼
Vercel
   │
   ├── Next.js
   │
   └── Environment Variables
            │
            ▼
      PostgreSQL Provider
```

Project harus dapat di-build menggunakan:

```bash
npm run build
```

tanpa error.

---

# 22. Future Expansion

Arsitektur harus memungkinkan penambahan:

* inventory
* stock
* purchase history
* multiple businesses
* multiple users
* team members
* export PDF
* export Excel
* sales tracking
* profit dashboard
* price history chart
* scenario simulation
* AI-assisted pricing

Namun fitur tersebut tidak boleh dipaksakan masuk ke MVP.

---

# 23. Architecture Rule

Prinsip:

```text
Simple first.
Correct first.
Interactive first.
Scalable when needed.
```

Jangan membuat sistem lebih kompleks daripada kebutuhan bisnisnya.

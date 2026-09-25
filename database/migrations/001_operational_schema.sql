-- Run on an existing installation after backing up the database.
-- This migration is intentionally additive. For MySQL versions without
-- ADD COLUMN IF NOT EXISTS, run the guarded statements one by one.
ALTER TABLE ingredients
ADD COLUMN category_id INT UNSIGNED NULL,
ADD COLUMN purchase_quantity DECIMAL(14, 4) NOT NULL DEFAULT 1,
ADD COLUMN purchase_unit VARCHAR(20) NOT NULL DEFAULT 'gram',
ADD COLUMN conversion_to_base DECIMAL(18, 6) NOT NULL DEFAULT 1,
ADD COLUMN unit_price_base DECIMAL(14, 4) NOT NULL DEFAULT 0,
ADD COLUMN stock_qty_base DECIMAL(18, 6) NOT NULL DEFAULT 0,
ADD COLUMN min_stock DECIMAL(18, 6) NOT NULL DEFAULT 0,
ADD COLUMN max_stock DECIMAL(18, 6) NOT NULL DEFAULT 0,
ADD COLUMN is_active TINYINT (1) NOT NULL DEFAULT 1,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE units
ADD COLUMN quantity_type ENUM ('decimal', 'integer') NOT NULL DEFAULT 'decimal';

UPDATE units SET quantity_type='integer' WHERE code IN ('pcs', 'unit');

ALTER TABLE recipes
ADD COLUMN category_id INT UNSIGNED NULL,
ADD COLUMN packaging_cost DECIMAL(14, 2) NOT NULL DEFAULT 0,
ADD COLUMN selling_price DECIMAL(14, 2) NOT NULL DEFAULT 0,
ADD COLUMN track_inventory TINYINT (1) NOT NULL DEFAULT 1,
ADD COLUMN is_active TINYINT (1) NOT NULL DEFAULT 1,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- The remaining additive tables and seed data are CREATE IF NOT EXISTS
-- statements in database.sql. For an existing database use:
--     php database/migrate.php
-- The PHP helper checks columns, then safely executes database.sql.
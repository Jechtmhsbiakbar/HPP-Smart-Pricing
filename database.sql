-- HPP Smart Pricing / Smart Pricing POS & Inventory
-- Import this file into a new database. Existing installations use database/migrations/001_operational_schema.sql.
CREATE TABLE
    IF NOT EXISTS units (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(50) NOT NULL,
        kind ENUM ('weight', 'volume', 'count') NOT NULL,
        base_code VARCHAR(20) NOT NULL,
        factor_to_base DECIMAL(18, 6) NOT NULL DEFAULT 1,
        is_active TINYINT (1) NOT NULL DEFAULT 1
    ) ENGINE = InnoDB;

INSERT IGNORE INTO units (code, name, kind, base_code, factor_to_base)
VALUES
    ('gram', 'Gram', 'weight', 'gram', 1),
    ('kg', 'Kilogram', 'weight', 'gram', 1000),
    ('ml', 'Mililiter', 'volume', 'ml', 1),
    ('liter', 'Liter', 'volume', 'ml', 1000),
    ('pcs', 'Pcs', 'count', 'pcs', 1),
    ('unit', 'Unit', 'count', 'pcs', 1);

CREATE TABLE
    IF NOT EXISTS categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL UNIQUE,
        category_type ENUM ('ingredient', 'product') NOT NULL,
        is_active TINYINT (1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS ingredients (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        base_unit VARCHAR(20) NOT NULL DEFAULT 'gram',
        category_id INT UNSIGNED NULL,
        price DECIMAL(14, 2) NOT NULL DEFAULT 0,
        price_quantity DECIMAL(14, 4) NOT NULL DEFAULT 1,
        price_unit VARCHAR(20) NOT NULL DEFAULT 'gram',
        purchase_quantity DECIMAL(14, 4) NOT NULL DEFAULT 1,
        purchase_unit VARCHAR(20) NOT NULL DEFAULT 'gram',
        conversion_to_base DECIMAL(18, 6) NOT NULL DEFAULT 1,
        unit_price_base DECIMAL(14, 4) NOT NULL DEFAULT 0,
        stock_qty_base DECIMAL(18, 6) NOT NULL DEFAULT 0,
        min_stock DECIMAL(18, 6) NOT NULL DEFAULT 0,
        max_stock DECIMAL(18, 6) NOT NULL DEFAULT 0,
        is_active TINYINT (1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ingredients_active (is_active),
        CONSTRAINT fk_ingredient_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS recipes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        category_id INT UNSIGNED NULL,
        equipment_cost DECIMAL(14, 2) NOT NULL DEFAULT 0,
        operational_cost DECIMAL(14, 2) NOT NULL DEFAULT 0,
        packaging_cost DECIMAL(14, 2) NOT NULL DEFAULT 0,
        hpp DECIMAL(14, 2) NOT NULL DEFAULT 0,
        price_tier ENUM ('murah', 'normal', 'mahal') NOT NULL DEFAULT 'normal',
        recommended_price DECIMAL(14, 2) NOT NULL DEFAULT 0,
        selling_price DECIMAL(14, 2) NOT NULL DEFAULT 0,
        track_inventory TINYINT (1) NOT NULL DEFAULT 1,
        is_active TINYINT (1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_recipes_active (is_active),
        CONSTRAINT fk_recipe_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS recipe_ingredients (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        recipe_id INT UNSIGNED NOT NULL,
        ingredient_id INT UNSIGNED NOT NULL,
        quantity DECIMAL(14, 6) NOT NULL,
        unit VARCHAR(20) NOT NULL,
        UNIQUE KEY uq_recipe_ingredient (recipe_id, ingredient_id),
        CONSTRAINT fk_recipe_ingredients_recipe FOREIGN KEY (recipe_id) REFERENCES recipes (id) ON DELETE CASCADE,
        CONSTRAINT fk_recipe_ingredients_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients (id) ON DELETE RESTRICT
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS ingredient_purchases (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ingredient_id INT UNSIGNED NOT NULL,
        purchased_at DATETIME NOT NULL,
        quantity DECIMAL(18, 6) NOT NULL,
        unit VARCHAR(20) NOT NULL,
        quantity_base DECIMAL(18, 6) NOT NULL,
        total_cost DECIMAL(14, 2) NOT NULL,
        unit_price_base DECIMAL(14, 4) NOT NULL,
        note VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_purchase_date (purchased_at),
        CONSTRAINT fk_purchase_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients (id) ON DELETE RESTRICT
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS sales (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        invoice_no VARCHAR(40) NOT NULL UNIQUE,
        sold_at DATETIME NOT NULL,
        subtotal DECIMAL(14, 2) NOT NULL DEFAULT 0,
        discount DECIMAL(14, 2) NOT NULL DEFAULT 0,
        total DECIMAL(14, 2) NOT NULL DEFAULT 0,
        paid DECIMAL(14, 2) NOT NULL DEFAULT 0,
        change_amount DECIMAL(14, 2) NOT NULL DEFAULT 0,
        payment_method ENUM ('cash', 'transfer', 'qris') NOT NULL DEFAULT 'cash',
        status ENUM ('COMPLETED', 'VOID', 'RETURNED') NOT NULL DEFAULT 'COMPLETED',
        total_hpp DECIMAL(14, 2) NOT NULL DEFAULT 0,
        cashier VARCHAR(100) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sales_date (sold_at),
        INDEX idx_sales_status (status)
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS sale_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        sale_id BIGINT UNSIGNED NOT NULL,
        recipe_id INT UNSIGNED NOT NULL,
        product_name VARCHAR(150) NOT NULL,
        qty DECIMAL(14, 4) NOT NULL,
        unit_price DECIMAL(14, 2) NOT NULL,
        discount DECIMAL(14, 2) NOT NULL DEFAULT 0,
        line_total DECIMAL(14, 2) NOT NULL,
        hpp_unit DECIMAL(14, 2) NOT NULL DEFAULT 0,
        hpp_total DECIMAL(14, 2) NOT NULL DEFAULT 0,
        recipe_snapshot LONGTEXT NULL,
        CONSTRAINT fk_sale_item_sale FOREIGN KEY (sale_id) REFERENCES sales (id) ON DELETE RESTRICT,
        CONSTRAINT fk_sale_item_recipe FOREIGN KEY (recipe_id) REFERENCES recipes (id) ON DELETE RESTRICT
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS stock_movements (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ingredient_id INT UNSIGNED NOT NULL,
        movement_type ENUM (
            'PURCHASE',
            'SALE',
            'STOCK_OPNAME',
            'ADJUSTMENT',
            'RETURN',
            'WASTE',
            'INITIAL_STOCK'
        ) NOT NULL,
        quantity_base DECIMAL(18, 6) NOT NULL,
        balance_after DECIMAL(18, 6) NOT NULL,
        reference VARCHAR(80) NULL,
        note VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        INDEX idx_movement_ingredient (ingredient_id, created_at),
        CONSTRAINT fk_movement_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients (id) ON DELETE RESTRICT
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS stock_opnames (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        opname_date DATETIME NOT NULL,
        note VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS stock_opname_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        opname_id BIGINT UNSIGNED NOT NULL,
        ingredient_id INT UNSIGNED NOT NULL,
        system_qty_base DECIMAL(18, 6) NOT NULL,
        physical_qty_base DECIMAL(18, 6) NOT NULL,
        difference_base DECIMAL(18, 6) NOT NULL,
        CONSTRAINT fk_opname_item_opname FOREIGN KEY (opname_id) REFERENCES stock_opnames (id) ON DELETE CASCADE,
        CONSTRAINT fk_opname_item_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients (id) ON DELETE RESTRICT
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS wastes (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ingredient_id INT UNSIGNED NOT NULL,
        quantity DECIMAL(18, 6) NOT NULL,
        unit VARCHAR(20) NOT NULL,
        quantity_base DECIMAL(18, 6) NOT NULL,
        reason VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_waste_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients (id) ON DELETE RESTRICT
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS settings (
        `key` VARCHAR(80) PRIMARY KEY,
        value TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE = InnoDB;

INSERT INTO
    settings (`key`, value)
VALUES
    ('business_name', 'HPP Smart Pricing'),
    ('business_address', ''),
    ('business_phone', ''),
    ('currency', 'IDR'),
    ('markup_murah', '15'),
    ('markup_normal', '30'),
    ('markup_mahal', '50'),
    ('stock_mode', 'strict'),
    ('timezone', 'Asia/Jakarta') ON DUPLICATE KEY
UPDATE `key` = `key`;

CREATE TABLE
    IF NOT EXISTS audit_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(80) NOT NULL,
        detail TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(80) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM ('ADMIN', 'KASIR') NOT NULL DEFAULT 'KASIR',
        is_active TINYINT (1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE = InnoDB;
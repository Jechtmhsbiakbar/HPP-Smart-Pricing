CREATE TABLE ingredients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    base_unit VARCHAR(20) NOT NULL,
    price DECIMAL(14,2) NOT NULL,
    price_quantity DECIMAL(14,4) NOT NULL,
    price_unit VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE recipes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    equipment_cost DECIMAL(14,2) NOT NULL DEFAULT 0,
    operational_cost DECIMAL(14,2) NOT NULL DEFAULT 0,
    hpp DECIMAL(14,2) NOT NULL DEFAULT 0,
    price_tier ENUM('murah', 'normal', 'mahal') NOT NULL DEFAULT 'normal',
    recommended_price DECIMAL(14,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE recipe_ingredients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT UNSIGNED NOT NULL,
    ingredient_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(14,4) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    CONSTRAINT fk_recipe_ingredients_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    CONSTRAINT fk_recipe_ingredients_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT
);

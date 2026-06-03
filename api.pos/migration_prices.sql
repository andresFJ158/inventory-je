CREATE TABLE IF NOT EXISTS price_tiers (
    id_price_tier INT AUTO_INCREMENT PRIMARY KEY,
    id_product_tier INT NOT NULL,
    min_qty INT NOT NULL,
    max_qty INT DEFAULT NULL,
    price_tier DECIMAL(10, 2) NOT NULL,
    active_tier TINYINT DEFAULT 1
);

CREATE TABLE IF NOT EXISTS price_overrides (
    id_price_override INT AUTO_INCREMENT PRIMARY KEY,
    id_sale_override INT NULL,
    id_order_override INT NULL,
    id_product_override INT NOT NULL,
    id_admin_override INT NOT NULL,
    original_price DECIMAL(10, 2) NOT NULL,
    override_price DECIMAL(10, 2) NOT NULL,
    reason_override VARCHAR(255) NOT NULL,
    date_created_override TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS product_offers (
    id_product_offer INT AUTO_INCREMENT PRIMARY KEY,
    id_product_offer_ref INT NOT NULL,
    offer_price DECIMAL(10, 2) NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    active_offer TINYINT DEFAULT 1
);

ALTER TABLE products ADD COLUMN is_on_offer TINYINT DEFAULT 0;
ALTER TABLE sales ADD COLUMN applied_price_type VARCHAR(50) DEFAULT 'base';
ALTER TABLE sales ADD COLUMN original_price_sale DOUBLE DEFAULT 0;

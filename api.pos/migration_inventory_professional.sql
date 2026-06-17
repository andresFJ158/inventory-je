-- =============================================
-- MIGRACIÓN: Inventario Final profesional
-- - Catálogo global con productos fabricados vs combos
-- - Precios separados de compras
-- - Transferencias en tránsito con recepción confirmada
-- - Movimientos auditables de stock
-- - Triggers corregidos para no confundir producto fabricado con combo
-- =============================================

CREATE TABLE IF NOT EXISTS `product_inventory` (
  `id_inventory` INT(11) NOT NULL AUTO_INCREMENT,
  `id_product_inventory` INT(11) NOT NULL,
  `id_office_inventory` INT(11) NOT NULL,
  `stock_inventory` DOUBLE DEFAULT 0,
  `status_inventory` INT(11) DEFAULT 1,
  `date_created_inventory` DATE DEFAULT NULL,
  `date_updated_inventory` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_inventory`),
  UNIQUE KEY `uq_product_office` (`id_product_inventory`, `id_office_inventory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_prices` (
  `id_price` INT(11) NOT NULL AUTO_INCREMENT,
  `id_product_price` INT(11) NOT NULL,
  `id_office_price` INT(11) DEFAULT 0,
  `id_warehouse_price` INT(11) DEFAULT 0,
  `price_sale` DOUBLE DEFAULT 0,
  `price_wholesale` DOUBLE DEFAULT 0,
  `wholesale_qty` DOUBLE DEFAULT 0,
  `cost_reference` DOUBLE DEFAULT 0,
  `source_price` VARCHAR(40) DEFAULT 'manual',
  `status_price` INT(11) DEFAULT 1,
  `id_admin_price` INT(11) DEFAULT 0,
  `date_created_price` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `date_updated_price` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_price`),
  KEY `idx_price_product_scope` (`id_product_price`, `id_office_price`, `status_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id_movement` INT(11) NOT NULL AUTO_INCREMENT,
  `id_product_movement` INT(11) NOT NULL,
  `id_office_movement` INT(11) NOT NULL,
  `qty_movement` DOUBLE NOT NULL,
  `type_movement` VARCHAR(40) NOT NULL,
  `id_admin_movement` INT(11) DEFAULT 0,
  `id_transfer_movement` INT(11) DEFAULT NULL,
  `notes_movement` TEXT DEFAULT NULL,
  `date_created_movement` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_movement`),
  KEY `idx_stock_mov_product_office` (`id_product_movement`, `id_office_movement`),
  KEY `idx_stock_mov_transfer` (`id_transfer_movement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_transfers` (
  `id_transfer` INT(11) NOT NULL AUTO_INCREMENT,
  `id_origin_office` INT(11) NOT NULL,
  `id_dest_office` INT(11) NOT NULL,
  `id_product_transfer` INT(11) NOT NULL,
  `qty_transfer` DOUBLE NOT NULL,
  `id_admin_transfer` INT(11) NOT NULL,
  `id_request_transfer` INT(11) DEFAULT NULL,
  `notes_transfer` TEXT DEFAULT NULL,
  `status_transfer` VARCHAR(50) DEFAULT 'pendiente',
  `unit_cost_transfer` DOUBLE DEFAULT 0,
  `unit_price_transfer` DOUBLE DEFAULT 0,
  `wholesale_price_transfer` DOUBLE DEFAULT 0,
  `wholesale_qty_transfer` DOUBLE DEFAULT 0,
  `id_confirmed_by_transfer` INT(11) DEFAULT NULL,
  `received_date_transfer` DATETIME DEFAULT NULL,
  `reject_reason_transfer` TEXT DEFAULT NULL,
  `date_created_transfer` DATE DEFAULT NULL,
  `date_updated_transfer` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transfer`),
  KEY `idx_transfer_dest_status` (`id_dest_office`, `status_transfer`),
  KEY `idx_transfer_product` (`id_product_transfer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `is_manufactured_product` TINYINT(1) DEFAULT 0;
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `is_combo_product` TINYINT(1) DEFAULT 0;
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `source_type_product` VARCHAR(30) DEFAULT 'externo';
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `origin_office_product` INT(11) DEFAULT 0;
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `initial_stock_product` DOUBLE DEFAULT 0;
ALTER TABLE `purchases` ADD COLUMN IF NOT EXISTS `may_product` DOUBLE DEFAULT 0;
ALTER TABLE `purchases` ADD COLUMN IF NOT EXISTS `wholesale_quantity` INT(11) DEFAULT 0;
ALTER TABLE `stock_transfers` ADD COLUMN IF NOT EXISTS `id_request_transfer` INT(11) DEFAULT NULL;
ALTER TABLE `stock_transfers` ADD COLUMN IF NOT EXISTS `unit_cost_transfer` DOUBLE DEFAULT 0;
ALTER TABLE `stock_transfers` ADD COLUMN IF NOT EXISTS `unit_price_transfer` DOUBLE DEFAULT 0;
ALTER TABLE `stock_transfers` ADD COLUMN IF NOT EXISTS `wholesale_price_transfer` DOUBLE DEFAULT 0;
ALTER TABLE `stock_transfers` ADD COLUMN IF NOT EXISTS `wholesale_qty_transfer` DOUBLE DEFAULT 0;
ALTER TABLE `stock_transfers` ADD COLUMN IF NOT EXISTS `id_confirmed_by_transfer` INT(11) DEFAULT NULL;
ALTER TABLE `stock_transfers` ADD COLUMN IF NOT EXISTS `received_date_transfer` DATETIME DEFAULT NULL;
ALTER TABLE `stock_transfers` ADD COLUMN IF NOT EXISTS `reject_reason_transfer` TEXT DEFAULT NULL;

INSERT INTO `columns` (`id_module_column`, `title_column`, `alias_column`, `type_column`, `matrix_column`, `visible_column`, `date_created_column`)
SELECT m.id_module, 'may_product', 'Precio mayorista', 'money', '', 1, CURDATE()
FROM modules m
WHERE m.title_module = 'purchases'
  AND NOT EXISTS (
    SELECT 1 FROM `columns` c WHERE c.id_module_column = m.id_module AND c.title_column = 'may_product'
  );

INSERT INTO `columns` (`id_module_column`, `title_column`, `alias_column`, `type_column`, `matrix_column`, `visible_column`, `date_created_column`)
SELECT m.id_module, 'wholesale_quantity', 'Cant. mayorista', 'int', '', 1, CURDATE()
FROM modules m
WHERE m.title_module = 'purchases'
  AND NOT EXISTS (
    SELECT 1 FROM `columns` c WHERE c.id_module_column = m.id_module AND c.title_column = 'wholesale_quantity'
  );

UPDATE `columns` c
JOIN modules m ON m.id_module = c.id_module_column
SET c.alias_column = 'Precio venta POS'
WHERE m.title_module = 'purchases' AND c.title_column = 'price_purchase';

DROP TEMPORARY TABLE IF EXISTS tmp_product_inventory_rollup;

CREATE TEMPORARY TABLE tmp_product_inventory_rollup AS
SELECT
  MIN(id_inventory) AS keep_id,
  id_product_inventory,
  id_office_inventory,
  SUM(COALESCE(stock_inventory, 0)) AS stock_inventory,
  MAX(COALESCE(status_inventory, 1)) AS status_inventory,
  MIN(date_created_inventory) AS date_created_inventory
FROM product_inventory
GROUP BY id_product_inventory, id_office_inventory
HAVING COUNT(*) > 1;

UPDATE product_inventory pi
JOIN tmp_product_inventory_rollup r ON r.keep_id = pi.id_inventory
SET pi.stock_inventory = r.stock_inventory,
    pi.status_inventory = r.status_inventory,
    pi.date_created_inventory = COALESCE(pi.date_created_inventory, r.date_created_inventory);

DELETE pi
FROM product_inventory pi
JOIN tmp_product_inventory_rollup r
  ON r.id_product_inventory = pi.id_product_inventory
 AND r.id_office_inventory = pi.id_office_inventory
WHERE pi.id_inventory <> r.keep_id;

DROP TEMPORARY TABLE IF EXISTS tmp_product_inventory_rollup;

SET @has_product_inventory_uq := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'product_inventory'
    AND index_name = 'uq_product_office'
    AND non_unique = 0
);
SET @product_inventory_uq_sql := IF(
  @has_product_inventory_uq = 0,
  'ALTER TABLE product_inventory ADD UNIQUE KEY uq_product_office (id_product_inventory, id_office_inventory)',
  'SELECT 1'
);
PREPARE stmt_product_inventory_uq FROM @product_inventory_uq_sql;
EXECUTE stmt_product_inventory_uq;
DEALLOCATE PREPARE stmt_product_inventory_uq;

UPDATE products p
SET is_combo_product = 1
WHERE EXISTS (SELECT 1 FROM combo_items ci WHERE ci.id_combo_ci = p.id_product);

UPDATE products p
SET is_manufactured_product = 1,
    source_type_product = 'laboratorio',
    origin_office_product = COALESCE(NULLIF(origin_office_product, 0), (
      SELECT r.id_office_recipe
      FROM recipes r
      WHERE r.id_product_recipe = p.id_product
      ORDER BY r.id_recipe DESC LIMIT 1
    ), id_office_product)
WHERE EXISTS (SELECT 1 FROM recipes r WHERE r.id_product_recipe = p.id_product);

UPDATE products p
SET is_manufactured_product = 1,
    source_type_product = 'laboratorio',
    origin_office_product = COALESCE(NULLIF(origin_office_product, 0), (
      SELECT pr.id_office_production
      FROM productions pr
      WHERE pr.id_packaged_product = p.id_product
      ORDER BY pr.id_production DESC LIMIT 1
    ))
WHERE EXISTS (SELECT 1 FROM productions pr2 WHERE pr2.id_packaged_product = p.id_product);

INSERT INTO product_prices
  (id_product_price, id_office_price, price_sale, price_wholesale, wholesale_qty, cost_reference, source_price, status_price, date_created_price)
SELECT p.id_product_purchase, 0,
       COALESCE(NULLIF(p.price_purchase, 0), p.cost_purchase, 0),
       COALESCE(p.may_product, 0),
       COALESCE(p.wholesale_quantity, 0),
       COALESCE(p.cost_purchase, 0),
       'purchases_migration',
       1,
       NOW()
FROM purchases p
INNER JOIN (
  SELECT id_product_purchase, MAX(id_purchase) AS max_purchase
  FROM purchases
  WHERE COALESCE(cost_purchase, 0) > 0
  GROUP BY id_product_purchase
) lastp ON lastp.max_purchase = p.id_purchase
WHERE NOT EXISTS (
  SELECT 1
  FROM product_prices pp
  WHERE pp.id_product_price = p.id_product_purchase
);

DROP TRIGGER IF EXISTS after_purchase_insert;
DROP TRIGGER IF EXISTS after_purchase_update;
DROP TRIGGER IF EXISTS after_sale_update;

DELIMITER //

CREATE TRIGGER after_purchase_insert
AFTER INSERT ON purchases
FOR EACH ROW
BEGIN
  DECLARE v_office_id INT DEFAULT 0;
  SET v_office_id = COALESCE((SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = NEW.id_office_purchase LIMIT 1), NEW.id_office_purchase, 0);

  IF COALESCE(NEW.price_purchase, 0) > 0 THEN
    INSERT INTO product_prices
      (id_product_price, id_office_price, id_warehouse_price, price_sale, price_wholesale, wholesale_qty, cost_reference, source_price, status_price, id_admin_price, date_created_price)
    VALUES
      (NEW.id_product_purchase, v_office_id, NEW.id_office_purchase, COALESCE(NULLIF(NEW.price_purchase, 0), NEW.cost_purchase, 0), COALESCE(NEW.may_product, 0), COALESCE(NEW.wholesale_quantity, 0), COALESCE(NEW.cost_purchase, 0), 'compra_laboratorio', 1, COALESCE(NEW.received_by_purchase, 0), NOW());
  END IF;

  IF NEW.qty_purchase > 0 AND NEW.status_purchase IN ('completado', 'recibido', 'aprobado') THEN

    INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
    VALUES (NEW.id_product_purchase, v_office_id, NEW.qty_purchase, 1, CURDATE())
    ON DUPLICATE KEY UPDATE stock_inventory = COALESCE(stock_inventory, 0) + NEW.qty_purchase, status_inventory = 1;

    UPDATE products
    SET stock_product = (SELECT COALESCE(SUM(stock_inventory), 0) FROM product_inventory WHERE id_product_inventory = NEW.id_product_purchase AND status_inventory = 1),
        source_type_product = CASE WHEN COALESCE(source_type_product, '') = '' THEN 'externo' ELSE source_type_product END
    WHERE id_product = NEW.id_product_purchase;

    INSERT INTO stock_movements (id_product_movement, id_office_movement, qty_movement, type_movement, id_admin_movement, notes_movement, date_created_movement)
    VALUES (NEW.id_product_purchase, v_office_id, NEW.qty_purchase, 'compra_recibida', COALESCE(NEW.received_by_purchase, 0), CONCAT('Compra #', NEW.id_purchase), NOW());
  END IF;
END //

CREATE TRIGGER after_purchase_update
AFTER UPDATE ON purchases
FOR EACH ROW
BEGIN
  DECLARE v_old_office INT DEFAULT 0;
  DECLARE v_new_office INT DEFAULT 0;
  DECLARE v_old_qty DOUBLE DEFAULT 0;
  DECLARE v_new_qty DOUBLE DEFAULT 0;

  SET v_new_office = COALESCE((SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = NEW.id_office_purchase LIMIT 1), NEW.id_office_purchase, 0);

  IF COALESCE(OLD.price_purchase, 0) <> COALESCE(NEW.price_purchase, 0)
     OR COALESCE(OLD.cost_purchase, 0) <> COALESCE(NEW.cost_purchase, 0)
     OR COALESCE(OLD.may_product, 0) <> COALESCE(NEW.may_product, 0)
     OR COALESCE(OLD.wholesale_quantity, 0) <> COALESCE(NEW.wholesale_quantity, 0)
     OR COALESCE(OLD.id_product_purchase, 0) <> COALESCE(NEW.id_product_purchase, 0)
     OR COALESCE(OLD.id_office_purchase, 0) <> COALESCE(NEW.id_office_purchase, 0) THEN
    IF COALESCE(NEW.price_purchase, 0) > 0 THEN
      INSERT INTO product_prices
        (id_product_price, id_office_price, id_warehouse_price, price_sale, price_wholesale, wholesale_qty, cost_reference, source_price, status_price, id_admin_price, date_created_price)
      VALUES
        (NEW.id_product_purchase, v_new_office, NEW.id_office_purchase, COALESCE(NULLIF(NEW.price_purchase, 0), NEW.cost_purchase, 0), COALESCE(NEW.may_product, 0), COALESCE(NEW.wholesale_quantity, 0), COALESCE(NEW.cost_purchase, 0), 'compra_laboratorio', 1, COALESCE(NEW.received_by_purchase, 0), NOW());
    END IF;
  END IF;

  IF COALESCE(OLD.status_purchase, '') <> COALESCE(NEW.status_purchase, '')
     OR COALESCE(OLD.qty_purchase, 0) <> COALESCE(NEW.qty_purchase, 0)
     OR COALESCE(OLD.id_product_purchase, 0) <> COALESCE(NEW.id_product_purchase, 0)
     OR COALESCE(OLD.id_office_purchase, 0) <> COALESCE(NEW.id_office_purchase, 0) THEN

    SET v_old_qty = CASE WHEN OLD.status_purchase IN ('completado', 'recibido', 'aprobado') THEN COALESCE(OLD.qty_purchase, 0) ELSE 0 END;
    SET v_new_qty = CASE WHEN NEW.status_purchase IN ('completado', 'recibido', 'aprobado') THEN COALESCE(NEW.qty_purchase, 0) ELSE 0 END;
    SET v_old_office = COALESCE((SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = OLD.id_office_purchase LIMIT 1), OLD.id_office_purchase);
    SET v_new_office = COALESCE((SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = NEW.id_office_purchase LIMIT 1), NEW.id_office_purchase);

    IF v_old_qty > 0 THEN
      UPDATE product_inventory
      SET stock_inventory = COALESCE(stock_inventory, 0) - v_old_qty
      WHERE id_product_inventory = OLD.id_product_purchase AND id_office_inventory = v_old_office AND status_inventory = 1;
    END IF;

    IF v_new_qty > 0 THEN
      INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
      VALUES (NEW.id_product_purchase, v_new_office, v_new_qty, 1, CURDATE())
      ON DUPLICATE KEY UPDATE stock_inventory = COALESCE(stock_inventory, 0) + v_new_qty, status_inventory = 1;

      INSERT INTO stock_movements (id_product_movement, id_office_movement, qty_movement, type_movement, id_admin_movement, notes_movement, date_created_movement)
      VALUES (NEW.id_product_purchase, v_new_office, v_new_qty, 'compra_recibida', COALESCE(NEW.received_by_purchase, 0), CONCAT('Compra #', NEW.id_purchase), NOW());
    END IF;

    UPDATE products
    SET stock_product = (SELECT COALESCE(SUM(stock_inventory), 0) FROM product_inventory WHERE id_product_inventory = NEW.id_product_purchase AND status_inventory = 1)
    WHERE id_product = NEW.id_product_purchase;

    IF OLD.id_product_purchase <> NEW.id_product_purchase THEN
      UPDATE products
      SET stock_product = (SELECT COALESCE(SUM(stock_inventory), 0) FROM product_inventory WHERE id_product_inventory = OLD.id_product_purchase AND status_inventory = 1)
      WHERE id_product = OLD.id_product_purchase;
    END IF;
  END IF;
END //

CREATE TRIGGER after_sale_update
AFTER UPDATE ON sales
FOR EACH ROW
BEGIN
  DECLARE v_is_combo INT DEFAULT 0;

  IF NEW.status_sale = 'Completada' AND OLD.status_sale != 'Completada' THEN
    SELECT CASE
      WHEN COALESCE(is_combo_product, 0) = 1
        OR EXISTS (SELECT 1 FROM combo_items WHERE id_combo_ci = NEW.id_product_sale)
      THEN 1 ELSE 0 END
    INTO v_is_combo
    FROM products
    WHERE id_product = NEW.id_product_sale;

    IF v_is_combo = 1 THEN
      UPDATE product_inventory pi
      INNER JOIN combo_items ci ON pi.id_product_inventory = ci.id_product_ci
      SET pi.stock_inventory = COALESCE(pi.stock_inventory, 0) - (ci.qty_ci * NEW.qty_sale)
      WHERE ci.id_combo_ci = NEW.id_product_sale AND pi.id_office_inventory = NEW.id_office_sale;

      UPDATE products p
      INNER JOIN combo_items ci ON p.id_product = ci.id_product_ci
      SET p.stock_product = (
        SELECT COALESCE(SUM(pi2.stock_inventory), 0)
        FROM product_inventory pi2
        WHERE pi2.id_product_inventory = p.id_product AND pi2.status_inventory = 1
      )
      WHERE ci.id_combo_ci = NEW.id_product_sale;
    ELSE
      UPDATE product_inventory
      SET stock_inventory = COALESCE(stock_inventory, 0) - NEW.qty_sale
      WHERE id_product_inventory = NEW.id_product_sale
        AND id_office_inventory = NEW.id_office_sale
        AND status_inventory = 1;

      UPDATE products
      SET stock_product = (
        SELECT COALESCE(SUM(stock_inventory), 0)
        FROM product_inventory
        WHERE id_product_inventory = NEW.id_product_sale AND status_inventory = 1
      )
      WHERE id_product = NEW.id_product_sale;
    END IF;

    INSERT INTO stock_movements (id_product_movement, id_office_movement, qty_movement, type_movement, id_admin_movement, notes_movement, date_created_movement)
    VALUES (NEW.id_product_sale, NEW.id_office_sale, -NEW.qty_sale, 'venta_pos', NEW.id_admin_sale, CONCAT('Venta POS #', NEW.id_order_sale), NOW());
  END IF;
END //

DELIMITER ;

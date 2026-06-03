-- =============================================
-- MIGRACIÓN: Catálogo Global de Productos + Inventario por Sucursal
-- Ejecutar una sola vez sobre la BD u228744577_pos
-- =============================================

-- =============================================
-- PASO 1: Crear tabla product_inventory
-- =============================================
CREATE TABLE IF NOT EXISTS `product_inventory` (
  `id_inventory`            INT(11) NOT NULL AUTO_INCREMENT,
  `id_product_inventory`    INT(11) NOT NULL,
  `id_office_inventory`     INT(11) NOT NULL,
  `stock_inventory`         DOUBLE DEFAULT 0,
  `status_inventory`        INT(11) DEFAULT 1,
  `date_created_inventory`  DATE DEFAULT NULL,
  `date_updated_inventory`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_inventory`),
  UNIQUE KEY `uq_product_office` (`id_product_inventory`, `id_office_inventory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- PASO 2: Tabla temporal para saber el ID "ganador" por SKU
-- (el registro con más datos: imagen, código, categoría correcta)
-- =============================================
CREATE TEMPORARY TABLE tmp_canonical AS
SELECT
  sku_product,
  MIN(id_product) AS canonical_id
FROM products
WHERE sku_product IS NOT NULL AND sku_product != ''
GROUP BY sku_product;

-- Para productos sin SKU, cada uno es su propio canónico
INSERT INTO tmp_canonical (sku_product, canonical_id)
SELECT CONCAT('__nosku__', id_product), id_product
FROM products
WHERE sku_product IS NULL OR sku_product = '';

-- =============================================
-- PASO 3: Poblar product_inventory con datos de las filas duplicadas
-- (usa ON DUPLICATE KEY para evitar colisiones si ya hay filas)
-- =============================================
INSERT INTO product_inventory
  (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
SELECT
  tc.canonical_id,
  p.id_office_product,
  COALESCE(p.stock_product, 0),
  p.status_product,
  p.date_created_product
FROM products p
INNER JOIN tmp_canonical tc
  ON (p.sku_product = tc.sku_product
   OR (p.sku_product IS NULL AND tc.sku_product = CONCAT('__nosku__', p.id_product)))
WHERE p.id_office_product > 0
ON DUPLICATE KEY UPDATE
  stock_inventory  = VALUES(stock_inventory),
  status_inventory = VALUES(status_inventory);

DROP TEMPORARY TABLE IF EXISTS tmp_canonical;

-- =============================================
-- PASO 4: Actualizar FKs en tablas relacionadas
-- (purchases, sales, inventory_requests, warehouse_assignments)
-- Mapeamos el id_product de cada copia al id canónico de su SKU
-- =============================================

-- Tabla auxiliar permanente para el mapeo viejo_id → canonical_id
CREATE TABLE IF NOT EXISTS `_product_id_map` (
  `old_id`       INT(11) NOT NULL,
  `canonical_id` INT(11) NOT NULL,
  PRIMARY KEY (`old_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `_product_id_map` (old_id, canonical_id)
SELECT p.id_product,
       (SELECT MIN(p2.id_product)
        FROM products p2
        WHERE p2.sku_product = p.sku_product
          AND p2.sku_product IS NOT NULL
          AND p2.sku_product != ''
       ) AS canonical_id
FROM products p
WHERE p.sku_product IS NOT NULL AND p.sku_product != '';

-- Productos sin SKU mapean a sí mismos
INSERT IGNORE INTO `_product_id_map` (old_id, canonical_id)
SELECT id_product, id_product FROM products
WHERE sku_product IS NULL OR sku_product = '';

-- Actualizar purchases
UPDATE purchases p
INNER JOIN `_product_id_map` m ON p.id_product_purchase = m.old_id
SET p.id_product_purchase = m.canonical_id
WHERE p.id_product_purchase != m.canonical_id;

-- Actualizar sales
UPDATE sales s
INNER JOIN `_product_id_map` m ON s.id_product_sale = m.old_id
SET s.id_product_sale = m.canonical_id
WHERE s.id_product_sale != m.canonical_id;

-- Actualizar inventory_requests
UPDATE inventory_requests ir
INNER JOIN `_product_id_map` m ON ir.id_product_request = m.old_id
SET ir.id_product_request = m.canonical_id
WHERE ir.id_product_request != m.canonical_id;

-- Actualizar warehouse_assignments
UPDATE warehouse_assignments wa
INNER JOIN `_product_id_map` m ON wa.id_product_assignment = m.old_id
SET wa.id_product_assignment = m.canonical_id
WHERE wa.id_product_assignment != m.canonical_id;

-- Actualizar product_offers
-- UPDATE product_offers po
-- INNER JOIN `_product_id_map` m ON po.id_product_offer_ref = m.old_id
-- SET po.id_product_offer_ref = m.canonical_id
-- WHERE po.id_product_offer_ref != m.canonical_id;

-- Actualizar price_tiers
-- UPDATE price_tiers pt
-- INNER JOIN `_product_id_map` m ON pt.id_product_tier = m.old_id
-- SET pt.id_product_tier = m.canonical_id
-- WHERE pt.id_product_tier != m.canonical_id;

-- =============================================
-- PASO 5: Eliminar filas duplicadas de products
-- (conservar solo el canónico por SKU, resetear id_office_product=0)
-- =============================================

-- Marcar canónicos: poner id_office_product = 0 (indica producto global)
UPDATE products p
INNER JOIN (
  SELECT MIN(id_product) AS canonical_id, sku_product
  FROM products
  WHERE sku_product IS NOT NULL AND sku_product != ''
  GROUP BY sku_product
) c ON p.id_product = c.canonical_id
SET p.id_office_product = 0;

-- Eliminar duplicados no canónicos (los que tienen SKU y NO son el mínimo de su grupo)
DELETE p FROM products p
WHERE p.sku_product IS NOT NULL
  AND p.sku_product != ''
  AND p.id_product NOT IN (
    SELECT canonical_id FROM (
      SELECT MIN(id_product) AS canonical_id, sku_product
      FROM products
      WHERE sku_product IS NOT NULL AND sku_product != ''
      GROUP BY sku_product
    ) tmp
  );

-- =============================================
-- PASO 6: Actualizar triggers para usar product_inventory
-- =============================================

DROP TRIGGER IF EXISTS after_purchase_insert;
DROP TRIGGER IF EXISTS after_sale_update;

DELIMITER //

-- Trigger POST compra: sube stock en product_inventory
CREATE TRIGGER after_purchase_insert
AFTER INSERT ON purchases
FOR EACH ROW
BEGIN
    -- Actualizar stock en product_inventory para la sucursal de la compra
    INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
    VALUES (NEW.id_product_purchase, NEW.id_office_purchase, NEW.qty_purchase, 1, CURDATE())
    ON DUPLICATE KEY UPDATE
        stock_inventory  = COALESCE(stock_inventory, 0) + NEW.qty_purchase,
        status_inventory = 1;

    -- Mantener compatibilidad: también actualizar products.stock_product (total global)
    UPDATE products
    SET stock_product = (
        SELECT COALESCE(SUM(stock_inventory), 0)
        FROM product_inventory
        WHERE id_product_inventory = NEW.id_product_purchase
    )
    WHERE id_product = NEW.id_product_purchase;
END //

-- Trigger POST venta completada: baja stock en product_inventory
CREATE TRIGGER after_sale_update
AFTER UPDATE ON sales
FOR EACH ROW
BEGIN
    IF NEW.status_sale = 'Completada' AND OLD.status_sale != 'Completada' THEN
        -- Descontar stock en product_inventory para la sucursal de la venta
        UPDATE product_inventory
        SET stock_inventory = COALESCE(stock_inventory, 0) - NEW.qty_sale
        WHERE id_product_inventory = NEW.id_product_sale
          AND id_office_inventory   = NEW.id_office_sale;

        -- Mantener compatibilidad: actualizar products.stock_product (total global)
        UPDATE products
        SET stock_product = (
            SELECT COALESCE(SUM(stock_inventory), 0)
            FROM product_inventory
            WHERE id_product_inventory = NEW.id_product_sale
        )
        WHERE id_product = NEW.id_product_sale;

        -- Registrar movimiento en warehouse_assignments
        INSERT INTO warehouse_assignments
            (id_sub_warehouse_assignment, id_product_assignment, qty_assignment, id_dispatched_by, type_assignment, notes_assignment)
        SELECT id_sub_warehouse, NEW.id_product_sale, NEW.qty_sale, NEW.id_admin_sale,
               'venta', CONCAT('Venta en POS #', NEW.id_order_sale)
        FROM sub_warehouses
        WHERE id_admin_sub_warehouse  = NEW.id_admin_sale
          AND id_office_sub_warehouse = NEW.id_office_sale
        LIMIT 1;
    END IF;
END //

DELIMITER ;

-- =============================================
-- VERIFICACIÓN
-- =============================================
SELECT 'products (catálogo global)' AS tabla, COUNT(*) AS filas FROM products
UNION ALL
SELECT 'product_inventory', COUNT(*) FROM product_inventory
UNION ALL
SELECT '_product_id_map', COUNT(*) FROM _product_id_map;

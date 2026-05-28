-- =============================================
-- Migración: Módulo Almacén + Sub-Almacenes
-- =============================================

-- 1. Tabla de Sub-Almacenes
CREATE TABLE IF NOT EXISTS `sub_warehouses` (
  `id_sub_warehouse` INT(11) NOT NULL AUTO_INCREMENT,
  `id_admin_sub_warehouse` INT(11) NOT NULL,
  `id_office_sub_warehouse` INT(11) NOT NULL,
  `name_sub_warehouse` TEXT DEFAULT NULL,
  `status_sub_warehouse` INT(11) DEFAULT 1,
  `date_created_sub_warehouse` DATE DEFAULT NULL,
  `date_updated_sub_warehouse` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_sub_warehouse`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Asignaciones (movimientos almacén → sub-almacén)
CREATE TABLE IF NOT EXISTS `warehouse_assignments` (
  `id_assignment` INT(11) NOT NULL AUTO_INCREMENT,
  `id_sub_warehouse_assignment` INT(11) NOT NULL,
  `id_product_assignment` INT(11) NOT NULL,
  `qty_assignment` INT(11) NOT NULL,
  `id_dispatched_by` INT(11) NOT NULL,
  `id_request_assignment` INT(11) DEFAULT NULL,
  `type_assignment` TEXT DEFAULT 'despacho',
  `notes_assignment` TEXT DEFAULT NULL,
  `date_created_assignment` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_assignment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Solicitudes de Inventario
CREATE TABLE IF NOT EXISTS `inventory_requests` (
  `id_request` INT(11) NOT NULL AUTO_INCREMENT,
  `id_admin_request` INT(11) NOT NULL,
  `id_office_request` INT(11) NOT NULL,
  `id_product_request` INT(11) NOT NULL,
  `qty_request` INT(11) NOT NULL,
  `status_request` TEXT DEFAULT 'pendiente',
  `id_dispatched_by_request` INT(11) DEFAULT NULL,
  `qty_dispatched_request` INT(11) DEFAULT NULL,
  `notes_request` TEXT DEFAULT NULL,
  `notes_dispatcher_request` TEXT DEFAULT NULL,
  `date_created_request` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `date_updated_request` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_request`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Actualizar columna de roles para incluir 'despachador'
UPDATE `columns` SET `matrix_column` = 'superadmin,admin,editor,despachador' WHERE `id_column` = 1;

-- 5. Registrar las nuevas páginas en el sidebar
INSERT INTO `pages` (`title_page`, `url_page`, `type_page`, `icon_page`, `order_page`, `date_created_page`)
VALUES
('Almacén', 'almacen', 'custom', 'fas fa-warehouse', 20, CURDATE()),
('Despachos', 'despachos', 'custom', 'fas fa-truck', 21, CURDATE()),
('Solicitar Inventario', 'solicitar_inventario', 'custom', 'fas fa-clipboard-list', 22, CURDATE()),
('Mi Inventario', 'mi_inventario', 'custom', 'fas fa-box-open', 23, CURDATE());

-- 6. Trigger para actualizar el stock principal al registrar una compra
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_purchase_insert
AFTER INSERT ON purchases
FOR EACH ROW
BEGIN
    UPDATE products
    SET stock_product = CAST(COALESCE(stock_product, 0) AS DECIMAL(10,2)) + NEW.qty_purchase
    WHERE id_product = NEW.id_product_purchase;
END //
DELIMITER ;


-- 7. Trigger para descontar stock al completar una venta
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_sale_update
AFTER UPDATE ON sales
FOR EACH ROW
BEGIN
    IF NEW.status_sale = 'Completada' AND OLD.status_sale != 'Completada' THEN
        UPDATE products
        SET stock_product = CAST(COALESCE(stock_product, 0) AS DECIMAL(10,2)) - NEW.qty_sale
        WHERE id_product = NEW.id_product_sale;
        
        INSERT INTO warehouse_assignments (id_sub_warehouse_assignment, id_product_assignment, qty_assignment, id_dispatched_by, type_assignment, notes_assignment)
        SELECT id_sub_warehouse, NEW.id_product_sale, NEW.qty_sale, NEW.id_admin_sale, 'venta', CONCAT('Venta en POS #', NEW.id_order_sale)
        FROM sub_warehouses 
        WHERE id_admin_sub_warehouse = NEW.id_admin_sale AND id_office_sub_warehouse = NEW.id_office_sale
        LIMIT 1;
    END IF;
END //
DELIMITER ;

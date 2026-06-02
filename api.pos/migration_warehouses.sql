-- =============================================
-- Migración: Módulo Almacenes
-- =============================================

-- 1. Tabla de Almacenes
CREATE TABLE IF NOT EXISTS `warehouses` (
  `id_warehouse` INT(11) NOT NULL AUTO_INCREMENT,
  `title_warehouse` TEXT DEFAULT NULL,
  `address_warehouse` TEXT DEFAULT NULL,
  `phone_warehouse` TEXT DEFAULT NULL,
  `id_office_warehouse` INT(11) DEFAULT NULL,
  `date_created_warehouse` DATE DEFAULT NULL,
  PRIMARY KEY (`id_warehouse`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Columna en Admins
ALTER TABLE `admins` ADD COLUMN `id_warehouse_admin` INT(11) NULL DEFAULT 0;

-- 3. Triggers en Warehouses
DROP TRIGGER IF EXISTS before_warehouse_insert;
DELIMITER //
CREATE TRIGGER before_warehouse_insert
BEFORE INSERT ON warehouses
FOR EACH ROW
BEGIN
    INSERT INTO offices (title_office, address_office, phone_office, type_office, date_created_office)
    VALUES (NEW.title_warehouse, NEW.address_warehouse, NEW.phone_warehouse, 'almacen', CURDATE());
    SET NEW.id_office_warehouse = LAST_INSERT_ID();
    SET NEW.date_created_warehouse = CURDATE();
END //
DELIMITER ;

DROP TRIGGER IF EXISTS before_warehouse_update;
DELIMITER //
CREATE TRIGGER before_warehouse_update
BEFORE UPDATE ON warehouses
FOR EACH ROW
BEGIN
    UPDATE offices
    SET title_office = NEW.title_warehouse,
        address_office = NEW.address_warehouse,
        phone_office = NEW.phone_warehouse
    WHERE id_office = OLD.id_office_warehouse;
END //
DELIMITER ;

DROP TRIGGER IF EXISTS before_warehouse_delete;
DELIMITER //
CREATE TRIGGER before_warehouse_delete
BEFORE DELETE ON warehouses
FOR EACH ROW
BEGIN
    DELETE FROM offices WHERE id_office = OLD.id_office_warehouse;
END //
DELIMITER ;

-- 4. Registro en el Sidebar y Módulo Dinámico
INSERT INTO pages (title_page, url_page, type_page, icon_page, order_page, date_created_page)
VALUES ('Almacenes', 'almacenes', 'modules', 'fas fa-warehouse', 26, CURDATE());

SET @page_id = LAST_INSERT_ID();

INSERT INTO modules (id_page_module, type_module, title_module, suffix_module, content_module, width_module, editable_module, date_created_module)
VALUES (@page_id, 'tables', 'warehouses', 'warehouse', 'Almacenes', 100, 1, CURDATE());

SET @module_id = LAST_INSERT_ID();

INSERT INTO columns (id_module_column, title_column, alias_column, type_column, matrix_column, visible_column, date_created_column)
VALUES 
(@module_id, 'title_warehouse', 'Nombre', 'text', NULL, 1, CURDATE()),
(@module_id, 'address_warehouse', 'Dirección', 'text', NULL, 1, CURDATE()),
(@module_id, 'phone_warehouse', 'Teléfono', 'text', NULL, 1, CURDATE());

INSERT INTO columns (id_module_column, title_column, alias_column, type_column, matrix_column, visible_column, date_created_column)
VALUES 
(2, 'id_warehouse_admin', 'Almacen Asignado', 'relations', 'warehouses', 1, CURDATE());

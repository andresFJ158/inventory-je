USE u228744577_pos;
DROP TRIGGER IF EXISTS after_sale_update;

DELIMITER //
CREATE TRIGGER after_sale_update
AFTER UPDATE ON sales
FOR EACH ROW
BEGIN
    IF NEW.status_sale = 'Completada' AND OLD.status_sale != 'Completada' THEN
        -- Descontar stock de product_inventory para esta oficina
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

        -- Registrar movimiento en warehouse_assignments (compartido por oficina)
        INSERT INTO warehouse_assignments
            (id_sub_warehouse_assignment, id_product_assignment, qty_assignment, id_dispatched_by, type_assignment, notes_assignment)
        SELECT id_sub_warehouse, NEW.id_product_sale, NEW.qty_sale, NEW.id_admin_sale,
               'venta', CONCAT('Venta en POS #', NEW.id_order_sale)
        FROM sub_warehouses
        WHERE id_office_sub_warehouse = NEW.id_office_sale
        LIMIT 1;
    END IF;
END //
DELIMITER ;

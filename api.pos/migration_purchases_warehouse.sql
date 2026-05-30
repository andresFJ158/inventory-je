-- 1. Update columns table so purchases' id_office_purchase links to warehouses instead of offices
UPDATE columns 
SET alias_column = 'Almacén', matrix_column = 'warehouses' 
WHERE title_column = 'id_office_purchase';

-- 2. Drop and recreate after_purchase_insert trigger to lookup id_office_warehouse
DROP TRIGGER IF EXISTS after_purchase_insert;

DELIMITER //

CREATE TRIGGER after_purchase_insert
AFTER INSERT ON purchases
FOR EACH ROW
BEGIN
    DECLARE office_id INT;
    
    -- Look up the office ID for the selected warehouse
    SELECT id_office_warehouse INTO office_id 
    FROM warehouses 
    WHERE id_warehouse = NEW.id_office_purchase;
    
    -- If no office is found, default to NEW.id_office_purchase
    IF office_id IS NULL THEN
        SET office_id = NEW.id_office_purchase;
    END IF;

    -- Actualizar stock en product_inventory para la sucursal de la compra
    INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
    VALUES (NEW.id_product_purchase, office_id, NEW.qty_purchase, 1, CURDATE())
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

DELIMITER ;

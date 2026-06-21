<?php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/ajax/lib/LocalConnection.php';
$db = LocalConnection::connect();

try {
    $db->exec("ALTER TABLE stock_movements ADD COLUMN id_warehouse_movement INT(11) DEFAULT 0 AFTER id_office_movement;");
} catch (Exception $e) { echo "stock_movements: " . $e->getMessage() . "\n"; }

try {
    $db->exec("ALTER TABLE product_inventory ADD COLUMN id_warehouse_inventory INT(11) DEFAULT 0 AFTER id_office_inventory;");
} catch (Exception $e) { echo "product_inventory: " . $e->getMessage() . "\n"; }

try {
    $db->exec("ALTER TABLE product_inventory DROP INDEX uq_product_office;");
} catch (Exception $e) { echo "DROP INDEX: " . $e->getMessage() . "\n"; }

try {
    $db->exec("ALTER TABLE product_inventory ADD UNIQUE KEY uq_product_office_wh (id_product_inventory, id_office_inventory, id_warehouse_inventory);");
} catch (Exception $e) { echo "ADD UNIQUE: " . $e->getMessage() . "\n"; }

echo "Done\n";

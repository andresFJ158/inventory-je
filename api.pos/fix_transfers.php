<?php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/ajax/lib/LocalConnection.php';
$db = LocalConnection::connect();

try {
    $db->exec("ALTER TABLE stock_transfers ADD COLUMN id_dest_warehouse INT(11) DEFAULT 0 AFTER id_dest_office;");
} catch (Exception $e) { echo "stock_transfers: " . $e->getMessage() . "\n"; }

try {
    $db->exec("ALTER TABLE warehouse_assignments ADD COLUMN id_warehouse_assignment INT(11) DEFAULT 0 AFTER id_sub_warehouse_assignment;");
} catch (Exception $e) { echo "warehouse_assignments: " . $e->getMessage() . "\n"; }

echo "Done\n";

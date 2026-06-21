<?php
require_once "ajax/lib/LocalConnection.php";

$db = LocalConnection::connect();

try {
	$db->exec("ALTER TABLE lab_supply_entries ADD COLUMN unit_price_entry DOUBLE DEFAULT 0, ADD COLUMN total_cost_entry DOUBLE DEFAULT 0, ADD COLUMN id_approved_by_entry INT DEFAULT 0, ADD COLUMN date_approved_entry DATE NULL");
	echo "Migracion completada.\n";
} catch (Exception $e) {
	echo "Error o las columnas ya existen: " . $e->getMessage() . "\n";
}

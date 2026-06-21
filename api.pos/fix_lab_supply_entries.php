<?php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/ajax/lib/LocalConnection.php';
$db = LocalConnection::connect();
$db->exec("ALTER TABLE lab_supply_entries ADD COLUMN unit_price_entry DECIMAL(10,2) DEFAULT NULL, ADD COLUMN total_cost_entry DECIMAL(10,2) DEFAULT NULL, ADD COLUMN id_approved_by_entry INT(11) DEFAULT NULL, ADD COLUMN date_approved_entry DATE DEFAULT NULL;");
echo "Done";

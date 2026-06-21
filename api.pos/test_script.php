<?php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/ajax/lib/LocalConnection.php';
$db = LocalConnection::connect();
$db->exec('ALTER TABLE raw_materials ADD COLUMN id_supplier_raw_material INT(11) DEFAULT 0 AFTER price_raw_material');
echo "Success";

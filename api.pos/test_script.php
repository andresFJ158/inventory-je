<?php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/ajax/lib/LocalConnection.php';
$db = LocalConnection::connect();
print_r($db->query("DESCRIBE warehouses")->fetchAll(PDO::FETCH_COLUMN));

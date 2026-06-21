<?php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/ajax/lib/LocalConnection.php';
$db = LocalConnection::connect();

$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// Tables to NOT truncate completely
$keep_tables = [
    'schema_migrations',
    'modules',
    'pages',
    'columns',
    'offices',
    'warehouses',
    'sub_warehouses',
    'admins'
];

$db->query("SET FOREIGN_KEY_CHECKS = 0");

foreach ($tables as $table) {
    if (!in_array($table, $keep_tables)) {
        $db->query("TRUNCATE TABLE `$table`");
    }
}

// Keep only admins 1, 13, 14, 15
$db->query("DELETE FROM admins WHERE id_admin NOT IN (1, 13, 14, 15)");

$db->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Database emptied successfully!";

<?php
require 'ajax/lib/LocalConnection.php';
$db = LocalConnection::connect();
$mod = $db->query("SELECT id_module_page FROM pages WHERE table_page = 'warehouses'")->fetchColumn();
echo "Module ID: $mod\n";
if ($mod) {
    $cols = $db->query("SELECT * FROM columns WHERE id_module_column = $mod")->fetchAll(PDO::FETCH_ASSOC);
    print_r($cols);
}

<?php
require 'ajax/lib/LocalConnection.php';
$db = LocalConnection::connect();

$res = $db->query("SELECT * FROM columns WHERE id_page_column IN (SELECT id_page FROM pages WHERE table_page = 'warehouses')")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);

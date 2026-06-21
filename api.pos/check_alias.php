<?php
require 'ajax/lib/LocalConnection.php';
$db = LocalConnection::connect();
$results = $db->query("SELECT id_column, title_column, alias_column FROM columns WHERE title_column LIKE '%initial_stock%' OR alias_column LIKE '%AJUSTE%'")->fetchAll(PDO::FETCH_ASSOC);
print_r($results);

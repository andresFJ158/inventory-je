<?php
require 'ajax/lib/LocalConnection.php';
$db = LocalConnection::connect();
$res = $db->query("SELECT * FROM columns WHERE type_column = 'select' OR type_column = 'relation' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);

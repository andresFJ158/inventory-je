<?php
require 'ajax/lib/LocalConnection.php';
$db = LocalConnection::connect();
$res = $db->query("SELECT * FROM columns WHERE title_column LIKE '%office%'")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);

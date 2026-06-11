<?php
require_once "models/connection.php";
$db = Connection::connect();
print_r($db->query("DESCRIBE orders")->fetchAll(PDO::FETCH_ASSOC));
print_r($db->query("DESCRIBE offices")->fetchAll(PDO::FETCH_ASSOC));

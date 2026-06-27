<?php
require_once "models/connection.php";
$db = Connection::connect();
$stmt = $db->query("SHOW COLUMNS FROM offices");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);

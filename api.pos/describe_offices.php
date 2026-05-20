<?php
require_once __DIR__."/models/connection.php";
$db = Connection::connect();
$stmt = $db->query("DESCRIBE admins");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

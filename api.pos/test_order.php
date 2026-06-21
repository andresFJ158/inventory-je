<?php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/ajax/lib/LocalConnection.php';

$db = LocalConnection::connect();
$stmt = $db->prepare("SELECT * FROM orders WHERE id_order = 141");
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));

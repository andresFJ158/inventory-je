<?php
require_once 'models/connection.php';
$db = Connection::connect();
$stmt = $db->query("SELECT id_order, method_order, qr_ref_order, transfer_order FROM orders WHERE method_order = 'QR' ORDER BY id_order DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

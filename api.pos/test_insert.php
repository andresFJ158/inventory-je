<?php
require 'ajax/lib/LocalConnection.php';
$db=LocalConnection::connect();
$fields=[
    'supplier_name'=>'test',
    'supplier_contact'=>'123',
    'email_supplier'=>'a@a.com',
    'ruc_supplier'=>'123',
    'type_supplier'=>'productos',
    'status_supplier'=>1
];
$cols = implode(', ', array_keys($fields));
$vals = implode(', ', array_map(fn($k) => ':'.$k, array_keys($fields)));
$fields['date_created_supplier'] = date('Y-m-d');
$cols .= ', date_created_supplier';
$vals .= ', :date_created_supplier';
$stmt = $db->prepare("INSERT INTO suppliers ($cols) VALUES ($vals)");
try {
    $stmt->execute($fields);
    print("OK\n");
} catch(Exception $e) {
    print($e->getMessage() . "\n");
}

<?php
require 'models/connection.php';
try {
    $db = Connection::connect();
    $sql = "INSERT INTO purchases (id_supplier_purchase,id_office_purchase,id_product_purchase,cost_purchase,price_purchase,qty_purchase,invest_purchase,may_product,wholesale_quantity,date_created_purchase) VALUES (1,5,30,10,15,5,50,'test',10,'2026-05-27')";
    $db->query($sql);
    echo "SUCCESS";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}

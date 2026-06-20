<?php
require 'c:/Users/sebas/Desktop/UFCPPV_Unitech/inventory-je/api.pos/load_env.php';
require 'c:/Users/sebas/Desktop/UFCPPV_Unitech/inventory-je/api.pos/models/connection.php';
require 'c:/Users/sebas/Desktop/UFCPPV_Unitech/inventory-je/api.pos/ajax/lib/bootstrap.php';

$db = Connection::connect();

$stmt = $db->query("SELECT id_production, id_packaged_product, qty_approved_production FROM productions WHERE status_production = 'completado' ORDER BY id_production DESC LIMIT 1");
$prod = $stmt->fetch(PDO::FETCH_ASSOC);

if ($prod && $prod['qty_approved_production'] > 0) {
    // Check if it already exists to avoid double entry
    $stmtCheck = $db->prepare("SELECT stock_inventory FROM product_inventory WHERE id_product_inventory = ? AND id_office_inventory = 0");
    $stmtCheck->execute([$prod['id_packaged_product']]);
    $stock = $stmtCheck->fetchColumn();
    
    if ($stock === false || $stock == 0) {
        pos_adjust_product_inventory($db, (int)$prod['id_packaged_product'], 0, (float)$prod['qty_approved_production'], 'produccion_aprobada', 1, null, 'Ingreso manual por QC previo (Bug fix) #' . $prod['id_production']);
        echo "Fixed production " . $prod['id_production'] . " adding " . $prod['qty_approved_production'] . " to product " . $prod['id_packaged_product'] . "\n";
    } else {
        echo "Stock already exists for product " . $prod['id_packaged_product'] . ". Current stock: " . $stock . "\n";
    }
} else {
    echo "No completed production found\n";
}

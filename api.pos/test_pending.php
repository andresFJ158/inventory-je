<?php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/ajax/lib/LocalConnection.php';

$db = LocalConnection::connect();
$id_office = 8; // Almacen Remanso dispatcher office

$stmt = $db->prepare("
    SELECT o.id_order, o.transaction_order, o.date_created_order, o.total_order, o.status_order,
           a.name_admin as vendedor_name,
           a.id_warehouse_admin,
           o.id_office_order
    FROM orders o
    LEFT JOIN admins a ON a.id_admin = o.id_admin_order
    WHERE o.status_order = 'Pendiente Aprobacion Almacen'
");
$stmt->execute();
$allPending = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "TODAS LAS ORDENES PENDIENTES:\n";
print_r($allPending);

echo "\n====================\n";

$stmt2 = $db->prepare("
    SELECT o.id_order, o.transaction_order, o.status_order, a.id_warehouse_admin
    FROM orders o
    LEFT JOIN admins a ON a.id_admin = o.id_admin_order
    WHERE o.status_order = 'Pendiente Aprobacion Almacen' 
      AND (
          o.id_office_order = :office 
          OR a.id_warehouse_admin IN (SELECT id_warehouse FROM warehouses WHERE id_sucursal_warehouse = :office)
      )
");
$stmt2->execute([':office' => $id_office]);
$filtered = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "ORDENES FILTRADAS PARA OFICINA 8:\n";
print_r($filtered);

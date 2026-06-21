<?php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/ajax/lib/LocalConnection.php';

$db = LocalConnection::connect();
$officeId = 8;

$stmt = $db->prepare("
        SELECT o.id_order, o.transaction_order, o.status_order, a.id_warehouse_admin, o.id_office_order
        FROM orders o
        LEFT JOIN clients c ON o.id_client_order = c.id_client
        LEFT JOIN admins a ON o.id_admin_order = a.id_admin
        LEFT JOIN (
            SELECT id_order_payment,
                   MAX(CASE WHEN file_payment IS NOT NULL AND file_payment != '' THEN 1 ELSE 0 END) AS has_proof,
                   MAX(file_payment) AS proof_file
            FROM sale_payments
            GROUP BY id_order_payment
        ) sp ON sp.id_order_payment = o.id_order
        WHERE o.status_order = 'Pendiente Despacho'
          AND (
              :office = 0 
              OR o.id_office_order = :office2
              OR a.id_warehouse_admin IN (SELECT id_warehouse FROM warehouses WHERE id_sucursal_warehouse = :office3)
          )
        ORDER BY o.date_created_order DESC
");
$stmt->execute([':office' => $officeId, ':office2' => $officeId, ':office3' => $officeId]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Órdenes pendientes de despacho (Oficina 8):\n";
print_r($results);

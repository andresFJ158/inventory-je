<?php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/ajax/lib/LocalConnection.php';

// simulating login request
$_POST = [
    'loginLabUser' => 'ok',
    'email' => 'vendedor2@gmail.com', // I need the email of Admin 24!
    'password' => '123456' // fake pass for now
];

$db = LocalConnection::connect();
$stmt = $db->query("SELECT email_admin FROM admins WHERE id_admin = 24");
$email = $stmt->fetchColumn();

// Now simulating exactly the JSON that would be returned, bypassing password check
$stmt = $db->prepare("SELECT * FROM admins WHERE email_admin = :email AND state_admin = 1");
$stmt->execute([':email' => $email]);
$admin = $stmt->fetch(PDO::FETCH_OBJ);

$id_office = intval($admin->id_office_admin);
$warehouse_office_id = 0; // office del almacén asignado (puede diferir de la sucursal)

if (isset($admin->id_warehouse_admin) && intval($admin->id_warehouse_admin) > 0) {
    $stmtWH = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh");
    $stmtWH->execute([':wh' => intval($admin->id_warehouse_admin)]);
    $whOffice = $stmtWH->fetchColumn();
    if ($whOffice) {
        $warehouse_office_id = intval($whOffice);
        if ($id_office === 0) {
            $id_office = $warehouse_office_id;
        }
    }
}

$stmtOffice = $db->prepare("SELECT * FROM offices WHERE id_office = :id");
$stmtOffice->execute([':id' => $id_office]);
$office = $stmtOffice->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => 200,
    'user' => [
        'id_admin' => intval($admin->id_admin),
        'name_admin' => $admin->name_admin,
        'rol_admin' => $admin->rol_admin,
        'id_office_admin' => intval($admin->id_office_admin),
        'id_warehouse_admin' => isset($admin->id_warehouse_admin) ? intval($admin->id_warehouse_admin) : 0,
        'warehouse_office_id' => $warehouse_office_id,
        'permissions_admin' => []
    ],
    'office' => $office
], JSON_PRETTY_PRINT);

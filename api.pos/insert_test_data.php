<?php
require_once __DIR__."/models/connection.php";
$db = Connection::connect();

// Create laboratory office
$stmt = $db->prepare("SELECT id_office FROM offices WHERE type_office = 'laboratorio' LIMIT 1");
$stmt->execute();
$office = $stmt->fetch();
if (!$office) {
    $stmt = $db->prepare("INSERT INTO offices (title_office, type_office, date_created_office) VALUES ('Laboratorio Principal', 'laboratorio', NOW())");
    $stmt->execute();
    $id_office = $db->lastInsertId();
    echo "Oficina de laboratorio creada (ID: $id_office)\n";
} else {
    $id_office = $office['id_office'];
    echo "Oficina de laboratorio ya existe (ID: $id_office)\n";
}

// Ensure columns exist for admins just in case, though they should
// Let's create lab_admin
$stmt = $db->prepare("SELECT id_admin FROM admins WHERE rol_admin = 'lab_admin' LIMIT 1");
$stmt->execute();
$admin = $stmt->fetch();
if (!$admin) {
    $password = crypt('admin123', '$2a$07$azybxcags23425sdg23sdfhsd$');
    $stmt = $db->prepare("INSERT INTO admins (id_office_admin, name_admin, email_admin, password_admin, rol_admin, status_admin, date_created_admin) VALUES ($id_office, 'Admin Laboratorio', 'lab_admin@test.com', '$password', 'lab_admin', 1, NOW())");
    $stmt->execute();
    echo "Usuario lab_admin creado (lab_admin@test.com / admin123)\n";
} else {
    echo "Usuario lab_admin ya existe\n";
}

// Create lab_worker
$stmt = $db->prepare("SELECT id_admin FROM admins WHERE rol_admin = 'lab_worker' LIMIT 1");
$stmt->execute();
$worker = $stmt->fetch();
if (!$worker) {
    $password = crypt('worker123', '$2a$07$azybxcags23425sdg23sdfhsd$');
    $stmt = $db->prepare("INSERT INTO admins (id_office_admin, name_admin, email_admin, password_admin, rol_admin, status_admin, date_created_admin) VALUES ($id_office, 'Trabajador Laboratorio', 'lab_worker@test.com', '$password', 'lab_worker', 1, NOW())");
    $stmt->execute();
    echo "Usuario lab_worker creado (lab_worker@test.com / worker123)\n";
} else {
    echo "Usuario lab_worker ya existe\n";
}

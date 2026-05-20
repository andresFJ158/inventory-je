<?php
require_once "api.pos/models/connection.php";
$db = Connection::connect();

try {
    $stmt = $db->prepare("SELECT DISTINCT rol_admin FROM admins");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Distinct rol_admin values from admins table:\n";
    print_r($roles);
} catch (Exception $e) {
    echo "Error querying admins: " . $e->getMessage() . "\n";
}

try {
    $stmt = $db->prepare("SELECT * FROM roles");
    $stmt->execute();
    $roles_table = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nRows from roles table:\n";
    print_r($roles_table);
} catch (Exception $e) {
    echo "Roles table does not exist or error: " . $e->getMessage() . "\n";
}

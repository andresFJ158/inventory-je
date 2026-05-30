<?php
require_once "pos.ajax.php";
try {
    $db = LocalConnection::connect();
    
    echo "--- OFFICES ---\n";
    $stmt = $db->query("SELECT id_office, title_office FROM offices");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo "\n--- ADMINS ---\n";
    $stmt = $db->query("SELECT id_admin, name_admin, email_admin, rol_admin, id_office_admin, token_admin FROM admins");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

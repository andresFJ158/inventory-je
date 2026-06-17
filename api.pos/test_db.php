<?php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/ajax/lib/LocalConnection.php';
$db = LocalConnection::connect();

echo "=== DESCRIBE warehouse_assignments ===\n";
$stmt = $db->query("DESCRIBE warehouse_assignments");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "{$c['Field']} | {$c['Type']} | Null:{$c['Null']} | Default:{$c['Default']}\n";
}

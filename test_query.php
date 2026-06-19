<?php
require 'api.pos/lib/LocalConnection.php';
require 'api.pos/load_env.php';

$db = LocalConnection::connect();
$stmt = $db->prepare("
    SELECT id_product, title_product, unit_product, source_type_product
    FROM products
    WHERE status_product = 1
      AND is_compound_product = 0
      AND id_product NOT IN (SELECT id_product_recipe FROM recipes)
    ORDER BY title_product ASC
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['status' => 200, 'results' => $rows]);

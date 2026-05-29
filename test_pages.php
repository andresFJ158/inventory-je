<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== INICIANDO VERIFICACIÓN DE CATÁLOGO GLOBAL E INVENTARIO ===\n\n";

$host = getenv("DB_HOST") ?: "127.0.0.1";
$port = getenv("DB_PORT") ?: "3310"; // Puerto local mapeado en docker-compose.yml
$dbName = getenv("DB_NAME") ?: "u228744577_pos";
$user = getenv("DB_USER") ?: "root";
$pass = getenv("DB_PASS") ?: "root";

try {
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("set names utf8");
    echo "✅ Conexión exitosa a la base de datos.\n";
} catch (Exception $e) {
    // Si falla (tal vez estamos corriendo dentro de docker), intentar con los parámetros de docker
    try {
        $db = new PDO("mysql:host=db;port=3306;dbname=$dbName", $user, $pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("set names utf8");
        echo "✅ Conexión exitosa a la base de datos (dentro de Docker).\n";
    } catch (Exception $e2) {
        echo "❌ Error al conectar a la base de datos: " . $e->getMessage() . " / " . $e2->getMessage() . "\n";
        exit(1);
    }
}

// 1. Verificar existencia de la tabla product_inventory
try {
    $stmt = $db->query("DESCRIBE product_inventory");
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Tabla 'product_inventory' encontrada. Estructura de campos:\n";
    foreach ($fields as $field) {
        echo "   - " . $field['Field'] . " (" . $field['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "❌ La tabla 'product_inventory' NO existe: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Verificar consolidación de productos en products
try {
    // Cantidad de productos en catálogo global
    $totalProducts = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    // Cantidad de productos con id_office_product = 0 (deberían ser todos o casi todos)
    $globalProducts = (int)$db->query("SELECT COUNT(*) FROM products WHERE id_office_product = 0")->fetchColumn();
    // Cantidad de registros en product_inventory
    $inventoryRows = (int)$db->query("SELECT COUNT(*) FROM product_inventory")->fetchColumn();

    echo "\n=== ESTADO DE TABLAS ===\n";
    echo "   - Productos totales en catálogo: $totalProducts\n";
    echo "   - Productos con id_office_product = 0 (globales): $globalProducts\n";
    echo "   - Registros de stock mapeados en product_inventory: $inventoryRows\n";
} catch (Exception $e) {
    echo "❌ Error al obtener estadísticas de productos: " . $e->getMessage() . "\n";
}

// 3. Verificar triggers
try {
    $triggers = $db->query("SHOW TRIGERS LIKE 'product_inventory'") ? [] : []; // Buscar triggers en la DB
    $stmt = $db->query("SHOW TRIGGERS");
    $allTriggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $foundPurchaseTrigger = false;
    $foundSaleTrigger = false;
    
    foreach ($allTriggers as $trg) {
        if ($trg['Trigger'] === 'after_purchase_insert') {
            $foundPurchaseTrigger = true;
        }
        if ($trg['Trigger'] === 'after_sale_update') {
            $foundSaleTrigger = true;
        }
    }
    
    echo "\n=== TRIGGERS MYSQL ===\n";
    echo ($foundPurchaseTrigger ? "✅ Trigger 'after_purchase_insert' activo.\n" : "❌ Trigger 'after_purchase_insert' NO activo.\n");
    echo ($foundSaleTrigger ? "✅ Trigger 'after_sale_update' activo.\n" : "❌ Trigger 'after_sale_update' NO activo.\n");
} catch (Exception $e) {
    echo "❌ Error al verificar triggers: " . $e->getMessage() . "\n";
}

// 4. Verificación funcional de Triggers
echo "\n=== PRUEBA DE FUNCIONAMIENTO DE TRIGGERS ===\n";
try {
    $db->beginTransaction();
    
    // Obtener un producto y sucursal de prueba de product_inventory
    $testInv = $db->query("SELECT id_product_inventory, id_office_inventory, stock_inventory FROM product_inventory LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if (!$testInv) {
        throw new Exception("No hay registros en product_inventory para probar.");
    }
    
    $productId = $testInv['id_product_inventory'];
    $officeId = $testInv['id_office_inventory'];
    $initialStock = (float)$testInv['stock_inventory'];
    
    echo "Producto de prueba ID: $productId en Oficina ID: $officeId (Stock inicial: $initialStock)\n";
    
    // Simular COMPRA (after_purchase_insert)
    $qtyPurchase = 10.0;
    echo "-> Insertando compra simulada de $qtyPurchase unidades...\n";
    
    $stmt = $db->prepare("
        INSERT INTO purchases (id_product_purchase, qty_purchase, cost_purchase, id_office_purchase, date_created_purchase)
        VALUES (:product, :qty, 100, :office, NOW())
    ");
    $stmt->execute([
        ':product' => $productId,
        ':qty' => $qtyPurchase,
        ':office' => $officeId
    ]);
    
    // Verificar si el stock aumentó en product_inventory
    $updatedStock = (float)$db->query("SELECT stock_inventory FROM product_inventory WHERE id_product_inventory = $productId AND id_office_inventory = $officeId")->fetchColumn();
    $diff = $updatedStock - $initialStock;
    
    if ($diff == $qtyPurchase) {
        echo "✅ ¡Éxito! El trigger de COMPRA incrementó el stock en product_inventory: $initialStock -> $updatedStock (Diferencia: +$diff)\n";
    } else {
        echo "❌ ¡Fallo! El trigger de COMPRA no funcionó adecuadamente. Stock actual: $updatedStock (Diferencia: $diff, Esperado: +$qtyPurchase)\n";
    }
    
    // Simular VENTA (after_sale_update)
    $qtySale = 3.0;
    echo "-> Insertando venta simulada (Pendiente) de $qtySale unidades...\n";
    
    // Insertar venta en estado Pendiente
    $stmt = $db->prepare("
        INSERT INTO sales (id_order_sale, id_product_sale, tax_type_sale, tax_sale, discount_sale, qty_sale, subtotal_sale, status_sale, id_admin_sale, id_client_sale, id_office_sale, date_created_sale)
        VALUES (1, :product, 'IVA', 0, 0, :qty, 100, 'Pendiente', 1, 1, :office, NOW())
    ");
    $stmt->execute([
        ':product' => $productId,
        ':qty' => $qtySale,
        ':office' => $officeId
    ]);
    $saleId = $db->lastInsertId();
    
    // El trigger after_sale_update actúa cuando status_sale pasa a ser 'Completada' o similar
    // Vamos a simular actualización a Completada
    echo "-> Completando la venta (Pendiente -> Completada) para gatillar el trigger...\n";
    $stmt = $db->prepare("UPDATE sales SET status_sale = 'Completada' WHERE id_sale = :id");
    $stmt->execute([':id' => $saleId]);
    
    // Verificar si el stock disminuyó en product_inventory
    $finalStock = (float)$db->query("SELECT stock_inventory FROM product_inventory WHERE id_product_inventory = $productId AND id_office_inventory = $officeId")->fetchColumn();
    $diffSale = $updatedStock - $finalStock;
    
    if ($diffSale == $qtySale) {
        echo "✅ ¡Éxito! El trigger de VENTA decrementó el stock en product_inventory: $updatedStock -> $finalStock (Diferencia: -$diffSale)\n";
    } else {
        echo "❌ ¡Fallo! El trigger de VENTA no funcionó adecuadamente. Stock actual: $finalStock (Diferencia: -$diffSale, Esperado: -$qtySale)\n";
    }
    
    $db->rollBack();
    echo "✅ Prueba de triggers completada. Cambios revertidos (Rollback exitoso).\n";
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Error durante la prueba funcional: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICACIÓN FINALIZADA ===\n";
?>

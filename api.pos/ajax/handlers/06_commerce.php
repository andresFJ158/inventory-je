<?php
if (isset($_POST['updateOrderStatus'])) {
    pos_require_role(['admin', 'superadmin', 'cajero', 'despachador', 'despachador_laboratorio']);
    $db = LocalConnection::connect();
    $id   = intval($_POST['id_order'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['Pendiente Despacho', 'Despachado', 'Pago Pendiente', 'Venta Confirmada', 'Cancelada'];
    if (!$id || !in_array($status, $allowed)) {
        echo json_encode(['status' => 400, 'message' => 'Datos inválidos']); exit;
    }
    $stmt = $db->prepare("UPDATE orders SET status_order=:s WHERE id_order=:id");
    $stmt->execute([':s' => $status, ':id' => $id]);
    echo json_encode(['status' => 200, 'message' => 'Estado actualizado']);
    exit;
}

//=====================================
// GASTOS DE DESPACHO
//=====================================
if (isset($_POST['addOrderExpense'])) {
    $db = LocalConnection::connect();
    try {
        @$db->exec("ALTER TABLE order_expenses ADD COLUMN charge_to_client_expense tinyint(1) NOT NULL DEFAULT 0");
    } catch (Throwable $e) {}
    
    $id_order = intval($_POST['id_order']);
    $chargeToClient = intval($_POST['charge_to_client'] ?? 0) ? 1 : 0;
    $amount = floatval($_POST['amount'] ?? 0);
    
    // Check if adding this expense (if company expense) makes order total negative
    if ($chargeToClient === 0) {
        $stmtOrder = $db->prepare("SELECT subtotal_order, discount_order FROM orders WHERE id_order = :id");
        $stmtOrder->execute([':id' => $id_order]);
        $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $subtotal = floatval($order['subtotal_order'] ?? 0);
            $discount = floatval($order['discount_order'] ?? 0);
            
            $stmtExp = $db->prepare("SELECT amount_expense, charge_to_client_expense FROM order_expenses WHERE id_order_expense = :id");
            $stmtExp->execute([':id' => $id_order]);
            $expenses = $stmtExp->fetchAll(PDO::FETCH_ASSOC);
            
            $clientExpenses = 0;
            $companyExpenses = 0;
            foreach ($expenses as $exp) {
                if (intval($exp['charge_to_client_expense']) === 1) {
                    $clientExpenses += floatval($exp['amount_expense']);
                } else {
                    $companyExpenses += floatval($exp['amount_expense']);
                }
            }
            
            $potentialNewTotal = $subtotal - $discount + $clientExpenses - ($companyExpenses + $amount);
            if ($potentialNewTotal < 0) {
                echo json_encode([
                    'status' => 400,
                    'message' => 'El total de la orden no puede ser negativo. Los gastos de almacén no pueden superar el total.'
                ]);
                exit;
            }
        }
    }
    
    $stmt = $db->prepare("INSERT INTO order_expenses (id_order_expense, concept_expense, amount_expense, charge_to_client_expense, id_admin_expense, date_created_expense) VALUES (:ord, :con, :amt, :ctc, :adm, CURDATE())");
    $stmt->execute([':ord' => $id_order, ':con' => $_POST['concept'], ':amt' => $amount, ':ctc' => $chargeToClient, ':adm' => intval($_POST['id_admin'] ?? 0)]);
    echo json_encode(['status' => 200, 'message' => 'Gasto registrado']);
    exit;
}
if (isset($_POST['getOrderExpenses'])) {
    $db = LocalConnection::connect();
    $stmt = $db->prepare("SELECT * FROM order_expenses WHERE id_order_expense=:id ORDER BY id_expense ASC");
    $stmt->execute([':id' => intval($_POST['id_order'])]);
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
if (isset($_POST['deleteOrderExpense'])) {
    $db = LocalConnection::connect();
    $stmt = $db->prepare("DELETE FROM order_expenses WHERE id_expense=:id");
    $stmt->execute([':id' => intval($_POST['id_expense'])]);
    echo json_encode(['status' => 200, 'message' => 'Gasto eliminado']);
    exit;
}

//=====================================
// PLANTILLAS DE GASTOS DE ALMACÉN
//=====================================
if (isset($_POST['getWarehouseExpenseTemplates'])) {
    $db = LocalConnection::connect();
    try {
        @$db->exec("CREATE TABLE IF NOT EXISTS `warehouse_expense_templates` (
            `id_template` INT AUTO_INCREMENT PRIMARY KEY,
            `concept_template` VARCHAR(255) NOT NULL,
            `amount_template` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `charge_to_client_template` TINYINT(1) NOT NULL DEFAULT 0,
            `date_created_template` DATE NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    } catch (Throwable $e) {}
    
    $stmt = $db->query("SELECT * FROM warehouse_expense_templates ORDER BY id_template DESC");
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if (isset($_POST['saveWarehouseExpenseTemplate'])) {
    pos_require_role(['admin', 'superadmin', 'cajero', 'despachador', 'despachador_laboratorio']);
    $db = LocalConnection::connect();
    try {
        @$db->exec("CREATE TABLE IF NOT EXISTS `warehouse_expense_templates` (
            `id_template` INT AUTO_INCREMENT PRIMARY KEY,
            `concept_template` VARCHAR(255) NOT NULL,
            `amount_template` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `charge_to_client_template` TINYINT(1) NOT NULL DEFAULT 0,
            `date_created_template` DATE NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    } catch (Throwable $e) {}

    $id = intval($_POST['id_template'] ?? 0);
    $concept = $_POST['concept'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $chargeToClient = intval($_POST['charge_to_client'] ?? 0) ? 1 : 0;

    if (empty($concept)) {
        echo json_encode(['status' => 400, 'message' => 'El concepto es requerido']);
        exit;
    }

    if ($id > 0) {
        $stmt = $db->prepare("UPDATE warehouse_expense_templates SET concept_template=:c, amount_template=:a, charge_to_client_template=:ctc WHERE id_template=:id");
        $stmt->execute([':c' => $concept, ':a' => $amount, ':ctc' => $chargeToClient, ':id' => $id]);
        echo json_encode(['status' => 200, 'message' => 'Plantilla de gasto actualizada']);
    } else {
        $stmt = $db->prepare("INSERT INTO warehouse_expense_templates (concept_template, amount_template, charge_to_client_template, date_created_template) VALUES (:c, :a, :ctc, CURDATE())");
        $stmt->execute([':c' => $concept, ':a' => $amount, ':ctc' => $chargeToClient]);
        echo json_encode(['status' => 200, 'message' => 'Plantilla de gasto creada', 'id_template' => $db->lastInsertId()]);
    }
    exit;
}

if (isset($_POST['deleteWarehouseExpenseTemplate'])) {
    pos_require_role(['admin', 'superadmin', 'cajero', 'despachador', 'despachador_laboratorio']);
    $db = LocalConnection::connect();
    $id = intval($_POST['id_template'] ?? 0);
    if ($id > 0) {
        $stmt = $db->prepare("DELETE FROM warehouse_expense_templates WHERE id_template=:id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['status' => 200, 'message' => 'Plantilla de gasto eliminada']);
    } else {
        echo json_encode(['status' => 400, 'message' => 'ID inválido']);
    }
    exit;
}

//=====================================
// ÓRDENES PENDIENTES DE DESPACHO
//=====================================
if (isset($_POST['getPendingDispatchOrders'])) {
    $db = LocalConnection::connect();
    $officeId    = intval($_POST['id_office']    ?? 0);
    $warehouseId = intval($_POST['id_warehouse'] ?? 0);

    // Si el despachador tiene un almacén asignado, la sucursal a la que pertenece
    // ese almacén es la que origina las órdenes de venta (id_office_order).
    if ($warehouseId > 0) {
        $stmtWH = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
        $stmtWH->execute([':wh' => $warehouseId]);
        $parentOffice = intval($stmtWH->fetchColumn());
        if ($parentOffice > 0) {
            $officeId = $parentOffice;
        }
    }

    $stmt = $db->prepare("
        SELECT o.*,
               c.name_client, c.surname_client, c.phone_client, c.email_client, c.dni_client, c.nit_client, c.address_client,
               CONCAT(a.name_admin, ' ', COALESCE(a.surname_admin, '')) AS vendedor_name,
               COALESCE(sp.has_proof, CASE WHEN o.qr_ref_order != '' OR o.transfer_order != '' THEN 1 ELSE 0 END) AS has_proof,
               COALESCE(sp.proof_file, NULLIF(o.qr_ref_order, ''), NULLIF(o.transfer_order, '')) AS proof_file,
               COALESCE((SELECT SUM(amount_expense) FROM order_expenses WHERE id_order_expense = o.id_order AND charge_to_client_expense = 1), 0) AS client_expenses_total,
               COALESCE((SELECT SUM(amount_expense) FROM order_expenses WHERE id_order_expense = o.id_order AND charge_to_client_expense = 0), 0) AS company_expenses_total
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
              OR a.id_warehouse_admin IN (SELECT id_warehouse FROM warehouses WHERE id_office_warehouse = :office3)
          )
        ORDER BY o.id_order DESC
    ");
    $stmt->execute([':office' => $officeId, ':office2' => $officeId, ':office3' => $officeId]);
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

//=====================================
// HISTORIAL DE ÓRDENES DESPACHADAS
//=====================================
if (isset($_POST['getDispatchedOrdersHistory'])) {
    $db = LocalConnection::connect();
    $officeId    = intval($_POST['id_office']    ?? 0);
    $warehouseId = intval($_POST['id_warehouse'] ?? 0);

    if ($warehouseId > 0) {
        $stmtWH = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
        $stmtWH->execute([':wh' => $warehouseId]);
        $parentOffice = intval($stmtWH->fetchColumn());
        if ($parentOffice > 0) {
            $officeId = $parentOffice;
        }
    }

    $stmt = $db->prepare("
        SELECT o.*,
               c.name_client, c.surname_client, c.phone_client, c.email_client, c.dni_client, c.nit_client, c.address_client,
               CONCAT(a.name_admin, ' ', COALESCE(a.surname_admin, '')) AS vendedor_name,
               COALESCE(sp.has_proof, CASE WHEN o.qr_ref_order != '' OR o.transfer_order != '' THEN 1 ELSE 0 END) AS has_proof,
               COALESCE(sp.proof_file, NULLIF(o.qr_ref_order, ''), NULLIF(o.transfer_order, '')) AS proof_file,
               COALESCE((SELECT SUM(amount_expense) FROM order_expenses WHERE id_order_expense = o.id_order AND charge_to_client_expense = 1), 0) AS client_expenses_total,
               COALESCE((SELECT SUM(amount_expense) FROM order_expenses WHERE id_order_expense = o.id_order AND charge_to_client_expense = 0), 0) AS company_expenses_total
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
        WHERE o.status_order = 'Despachado'
          AND (
              :office = 0 
              OR o.id_office_order = :office2
              OR a.id_warehouse_admin IN (SELECT id_warehouse FROM warehouses WHERE id_office_warehouse = :office3)
          )
        ORDER BY o.id_order DESC
        LIMIT 100
    ");
    $stmt->execute([':office' => $officeId, ':office2' => $officeId, ':office3' => $officeId]);
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

//=====================================
// CONFIRMAR PAGO DE ORDEN (nuevo flujo)
//=====================================
if (isset($_POST['confirmOrderPayment'])) {
    $db = LocalConnection::connect();
    $id      = pos_int('id_order');
    $method  = $_POST['method'] ?? 'efectivo';
    $ref     = $_POST['reference'] ?? '';
    $invoice = pos_int('invoice');
    $idAdmin = pos_int('id_admin');
    if (!$id) { pos_fail(400, 'Orden inv�lida'); }

    // El archivo se guarda fuera de la transacci�n (I/O de filesystem).
    // Si falla la validaci�n, no abortamos el pago: registramos sin archivo y avisamos.
    $filePath = null; $fileWarning = null;
    try {
        $filePath = posStoreSalePaymentFile($_FILES['proof'] ?? null);
    } catch (Exception $e) {
        $fileWarning = $e->getMessage();
    }

    $idSalePayment = pos_transaction($db, function(PDO $db) use ($id, $method, $ref, $invoice, $idAdmin, $filePath) {
        $stmt = $db->prepare("UPDATE orders SET status_order='Venta Confirmada', method_order=:m, transfer_order=:r, invoice_order=:inv WHERE id_order=:id");
        $stmt->execute([':m' => $method, ':r' => $ref, ':inv' => $invoice, ':id' => $id]);
        // La orden debe existir
        if ($stmt->rowCount() === 0) {
            $exists = $db->prepare("SELECT 1 FROM orders WHERE id_order=:id");
            $exists->execute([':id' => $id]);
            if (!$exists->fetchColumn()) { throw new RuntimeException('La orden no existe'); }
        }
        // Datos de la orden (total para caja, monto del comprobante)
        $stmtO = $db->prepare("SELECT total_order, id_office_order, date_created_order FROM orders WHERE id_order=:id");
        $stmtO->execute([':id' => $id]);
        $order = $stmtO->fetch(PDO::FETCH_OBJ);
        // Actualizar saldo de caja si es efectivo/QR/transferencia
        if ($order && in_array($method, ['efectivo', 'qr', 'transferencia'])) {
            $stmtC = $db->prepare("SELECT id_cash FROM cashs WHERE id_office_cash=:o AND date_created_cash=:d AND status_cash=1 LIMIT 1");
            $stmtC->execute([':o' => $order->id_office_order, ':d' => $order->date_created_order]);
            $cash = $stmtC->fetch(PDO::FETCH_OBJ);
            if ($cash) {
                $stmtU = $db->prepare("UPDATE cashs SET money_cash = money_cash + :tot WHERE id_cash=:id");
                $stmtU->execute([':tot' => $order->total_order, ':id' => $cash->id_cash]);
            }
        }
        // Registrar el comprobante de la venta (respaldo del pago)
        $stmtP = $db->prepare("INSERT INTO sale_payments (id_order_payment, method_payment, reference_payment, file_payment, amount_payment, id_admin_payment) VALUES (:o, :m, :r, :f, :a, :ad)");
        $stmtP->execute([
            ':o' => $id, ':m' => $method, ':r' => $ref, ':f' => $filePath,
            ':a' => $order->total_order ?? 0, ':ad' => $idAdmin
        ]);
        return $db->lastInsertId();
    });

    pos_ok(['id_sale_payment' => $idSalePayment, 'file_payment' => $filePath, 'file_warning' => $fileWarning], 'Pago confirmado');
}

//=====================================
// COMPROBANTES DE VENTA (respaldo del pago)
//=====================================

// Guarda un archivo de comprobante en views/assets/files y devuelve la ruta p�blica (o null)
function posStoreSalePaymentFile($file) {
    if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new Exception('El comprobante no debe superar 5MB');
    }
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        throw new Exception('Formato no permitido (use JPG, PNG, WEBP o PDF)');
    }
    if ($ext === 'pdf') {
        $content = file_get_contents($file['tmp_name'], false, null, 0, 4);
        if ($content !== '%PDF') {
            throw new Exception('El archivo no parece ser un PDF válido.');
        }
    }
    $dir = __DIR__ . '/../views/assets/files/comprobantes';
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    $fileName = 'comp_' . uniqid() . '_' . time() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $fileName)) {
        throw new Exception('No se pudo guardar el comprobante');
    }
    return 'views/assets/files/comprobantes/' . $fileName;
}

if (isset($_POST['getSalePayments'])) {
    $db = LocalConnection::connect();
    $stmt = $db->prepare("SELECT sp.*, a.name_admin FROM sale_payments sp LEFT JOIN admins a ON sp.id_admin_payment=a.id_admin WHERE sp.id_order_payment=:o ORDER BY sp.id_sale_payment DESC");
    $stmt->execute([':o' => intval($_POST['id_order'] ?? 0)]);
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// Adjuntar/reemplazar comprobante despu�s de la venta (desde Ventas/Facturaci�n)
if (isset($_POST['uploadSalePayment'])) {
    $db = LocalConnection::connect();
    $idOrder = intval($_POST['id_order'] ?? 0);
    if (!$idOrder) { echo json_encode(['status' => 400, 'message' => 'Orden inv�lida']); exit; }
    try {
        $filePath = posStoreSalePaymentFile($_FILES['proof'] ?? null);
    } catch (Exception $e) {
        echo json_encode(['status' => 400, 'message' => $e->getMessage()]); exit;
    }
    if (!$filePath && empty($_POST['reference'])) {
        echo json_encode(['status' => 400, 'message' => 'Adjunte un archivo o una referencia']); exit;
    }
    $idPayment = intval($_POST['id_sale_payment'] ?? 0);
    if ($idPayment) {
        // Reemplazar: actualizar el comprobante existente (borra el archivo previo si se sube uno nuevo)
        if ($filePath) {
            $stmtOld = $db->prepare("SELECT file_payment FROM sale_payments WHERE id_sale_payment=:id AND id_order_payment=:o");
            $stmtOld->execute([':id' => $idPayment, ':o' => $idOrder]);
            $old = $stmtOld->fetch(PDO::FETCH_OBJ);
            if ($old && $old->file_payment) { @unlink(__DIR__ . '/../' . $old->file_payment); }
            $db->prepare("UPDATE sale_payments SET file_payment=:f, reference_payment=COALESCE(NULLIF(:r,''), reference_payment) WHERE id_sale_payment=:id AND id_order_payment=:o")
               ->execute([':f' => $filePath, ':r' => $_POST['reference'] ?? '', ':id' => $idPayment, ':o' => $idOrder]);
        } else {
            $db->prepare("UPDATE sale_payments SET reference_payment=:r WHERE id_sale_payment=:id AND id_order_payment=:o")
               ->execute([':r' => $_POST['reference'] ?? '', ':id' => $idPayment, ':o' => $idOrder]);
        }
        echo json_encode(['status' => 200, 'message' => 'Comprobante actualizado', 'file_payment' => $filePath]);
        exit;
    }
    // Crear un nuevo comprobante para la venta
    $stmtO = $db->prepare("SELECT total_order FROM orders WHERE id_order=:id");
    $stmtO->execute([':id' => $idOrder]);
    $order = $stmtO->fetch(PDO::FETCH_OBJ);
    $stmt = $db->prepare("INSERT INTO sale_payments (id_order_payment, method_payment, reference_payment, file_payment, amount_payment, id_admin_payment) VALUES (:o, :m, :r, :f, :a, :ad)");
    $stmt->execute([
        ':o' => $idOrder, ':m' => $_POST['method'] ?? '', ':r' => $_POST['reference'] ?? '',
        ':f' => $filePath, ':a' => $order->total_order ?? 0, ':ad' => intval($_POST['id_admin'] ?? 0)
    ]);
    echo json_encode(['status' => 200, 'message' => 'Comprobante adjuntado', 'id_sale_payment' => $db->lastInsertId(), 'file_payment' => $filePath]);
    exit;
}

if (isset($_POST['deleteSalePayment'])) {
    $db = LocalConnection::connect();
    $idPayment = intval($_POST['id_sale_payment'] ?? 0);
    $stmtOld = $db->prepare("SELECT file_payment FROM sale_payments WHERE id_sale_payment=:id");
    $stmtOld->execute([':id' => $idPayment]);
    $old = $stmtOld->fetch(PDO::FETCH_OBJ);
    if ($old && $old->file_payment) { @unlink(__DIR__ . '/../' . $old->file_payment); }
    $db->prepare("DELETE FROM sale_payments WHERE id_sale_payment=:id")->execute([':id' => $idPayment]);
    echo json_encode(['status' => 200, 'message' => 'Comprobante eliminado']);
    exit;
}

//=====================================
// COMBOS (Productos Compuestos)
//=====================================

// Listar todos los combos con sus componentes y stock mínimo
if (isset($_POST['getCombos'])) {
    $db = LocalConnection::connect();
    $officeId = intval($_POST['id_office'] ?? 0);

    // Query 1: all combos
    $combos = $db->query("
        SELECT p.id_product, p.title_product, p.sku_product, p.discount_product, p.price_product,
               p.combo_price_mode, p.status_product, p.id_category_product, p.img_product
        FROM products p
        WHERE p.is_combo_product = 1
        ORDER BY p.title_product ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($combos)) {
        echo json_encode(['status' => 200, 'results' => []]);
        exit;
    }

    // Query 2: all items for all combos at once
    $comboIds     = array_column($combos, 'id_product');
    $phCombos     = implode(',', array_fill(0, count($comboIds), '?'));
    $stmtAllItems = $db->prepare("
        SELECT ci.id_combo_item, ci.id_combo_ci, ci.id_product_ci, ci.qty_ci,
               p.title_product, p.sku_product, p.unit_product
        FROM combo_items ci
        JOIN products p ON ci.id_product_ci = p.id_product
        WHERE ci.id_combo_ci IN ($phCombos)
        ORDER BY ci.id_combo_ci, ci.id_combo_item ASC
    ");
    $stmtAllItems->execute($comboIds);
    $allItems = $stmtAllItems->fetchAll(PDO::FETCH_ASSOC);

    // Query 3: all component stock in one query
    $allProductIds = array_values(array_unique(array_column($allItems, 'id_product_ci')));
    $stockMap = [];
    if (!empty($allProductIds)) {
        $phProducts = implode(',', array_fill(0, count($allProductIds), '?'));
        if ($officeId > 0) {
            $stmtStock = $db->prepare("
                SELECT id_product_inventory, COALESCE(stock_inventory, 0) AS stock
                FROM product_inventory
                WHERE id_product_inventory IN ($phProducts) AND id_office_inventory = ?
            ");
            $stmtStock->execute([...$allProductIds, $officeId]);
        } else {
            $stmtStock = $db->prepare("
                SELECT id_product_inventory, COALESCE(SUM(stock_inventory), 0) AS stock
                FROM product_inventory
                WHERE id_product_inventory IN ($phProducts)
                GROUP BY id_product_inventory
            ");
            $stmtStock->execute($allProductIds);
        }
        foreach ($stmtStock->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $stockMap[(int)$row['id_product_inventory']] = (int)$row['stock'];
        }
    }

    // Group items by combo and compute per-item derived stock
    $itemsByCombo = [];
    foreach ($allItems as &$item) {
        $rawStock = $stockMap[(int)$item['id_product_ci']] ?? 0;
        $possible = $item['qty_ci'] > 0 ? (int)floor($rawStock / $item['qty_ci']) : 0;
        $item['stock_available']      = $rawStock;
        $item['combo_units_possible'] = $possible;
        $itemsByCombo[(int)$item['id_combo_ci']][] = $item;
    }
    unset($item);

    // Assign items + compute combo stock
    foreach ($combos as &$combo) {
        $items    = $itemsByCombo[(int)$combo['id_product']] ?? [];
        $minStock = null;
        foreach ($items as $i) {
            $possible = (int)$i['combo_units_possible'];
            if ($minStock === null || $possible < $minStock) $minStock = $possible;
        }
        $combo['items']       = $items;
        $combo['stock_combo'] = $minStock ?? 0;
        
        $sumPrice = array_sum(array_map(fn($i) => $i['price_ci'] * $i['qty_ci'], $items));
        if ($combo['combo_price_mode'] === 'fijo') {
            $combo['total_price'] = floatval($combo['price_product']);
        } else {
            $combo['total_price'] = $sumPrice;
        }
    }
    unset($combo);

    echo json_encode(['status' => 200, 'results' => $combos]);
    exit;
}

// Crear nuevo combo
if (isset($_POST['createCombo'])) {
    pos_require_role(['admin', 'superadmin', 'lab_admin', 'despachador_laboratorio']);
    $db = LocalConnection::connect();
    try {
        $db->beginTransaction();
        $title = $_POST['title'] ?? '';
        $sku   = $_POST['sku'] ?? '';
        $idCat = intval($_POST['id_category'] ?? 0);
        $discount = floatval($_POST['discount'] ?? 0);

        $priceMode = $_POST['combo_price_mode'] ?? 'fijo';
        $priceProduct = floatval($_POST['price_product'] ?? 0);

        $stmt = $db->prepare("
            INSERT INTO products (title_product, sku_product, id_category_product, discount_product, price_product,
                is_combo_product, is_compound_product, combo_price_mode, status_product, date_created_product)
            VALUES (:title, :sku, :cat, :discount, :price, 1, 0, :mode, 1, CURDATE())
        ");
        $stmt->execute([
            ':title' => urlencode($title), ':sku' => $sku, ':cat' => $idCat, 
            ':discount' => $discount, ':price' => $priceProduct, ':mode' => $priceMode
        ]);
        $comboId = $db->lastInsertId();

        // Insertar items
        $items = json_decode($_POST['items'] ?? '[]', true);
        if (count($items) < 2) {
            $db->rollBack();
            echo json_encode(['status' => 400, 'message' => 'El combo debe tener al menos 2 productos']);
            exit;
        }
        $stmtItem = $db->prepare("INSERT INTO combo_items (id_combo_ci, id_product_ci, qty_ci, price_ci, date_created_ci) VALUES (:combo, :product, :qty, :price, CURDATE())");
        foreach ($items as $item) {
            $stmtItem->execute([':combo' => $comboId, ':product' => intval($item['id_product']), ':qty' => max(1, intval($item['qty'])), ':price' => floatval($item['price'])]);
        }

        $db->commit();
        echo json_encode(['status' => 200, 'id' => $comboId, 'message' => 'Combo creado']);
    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        error_log('createCombo error: ' . $e->getMessage());
        pos_fail(500, 'Error al crear el combo.');
    }
    exit;
}

// Actualizar combo (datos + items en una sola llamada)
if (isset($_POST['updateCombo'])) {
    pos_require_role(['admin', 'superadmin', 'lab_admin', 'despachador_laboratorio']);
    $db = LocalConnection::connect();
    try {
        $db->beginTransaction();
        $comboId  = intval($_POST['id_combo']);
        $title    = $_POST['title'] ?? '';
        $sku      = $_POST['sku'] ?? '';
        $discount = floatval($_POST['discount'] ?? 0);
        $idCat    = intval($_POST['id_category'] ?? 0);
        $priceMode = $_POST['combo_price_mode'] ?? 'fijo';
        $priceProduct = floatval($_POST['price_product'] ?? 0);

        $stmt = $db->prepare("
            UPDATE products SET title_product=:t, sku_product=:s, discount_product=:d, id_category_product=:c,
                   price_product=:p, combo_price_mode=:m
            WHERE id_product=:id AND is_combo_product=1
        ");
        $stmt->execute([':t' => urlencode($title), ':s' => $sku, ':d' => $discount, ':c' => $idCat, ':p' => $priceProduct, ':m' => $priceMode, ':id' => $comboId]);

        // Reemplazar items
        $items = json_decode($_POST['items'] ?? '[]', true);
        if (count($items) < 2) {
            $db->rollBack();
            echo json_encode(['status' => 400, 'message' => 'El combo debe tener al menos 2 productos']);
            exit;
        }
        $db->prepare("DELETE FROM combo_items WHERE id_combo_ci = :id")->execute([':id' => $comboId]);
        $stmtItem = $db->prepare("INSERT INTO combo_items (id_combo_ci, id_product_ci, qty_ci, price_ci, date_created_ci) VALUES (:combo, :product, :qty, :price, CURDATE())");
        foreach ($items as $item) {
            $stmtItem->execute([':combo' => $comboId, ':product' => intval($item['id_product']), ':qty' => max(1, intval($item['qty'])), ':price' => floatval($item['price'])]);
        }

        $db->commit();
        echo json_encode(['status' => 200, 'message' => 'Combo actualizado']);
    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        error_log('updateCombo error: ' . $e->getMessage());
        pos_fail(500, 'Error al actualizar el combo.');
    }
    exit;
}

// Eliminar combo completo
if (isset($_POST['deleteCombo'])) {
    pos_require_role(['admin', 'superadmin', 'lab_admin', 'despachador_laboratorio']);
    $db = LocalConnection::connect();
    try {
        $db->beginTransaction();
        $id = intval($_POST['id_combo']);
        $db->prepare("DELETE FROM combo_items WHERE id_combo_ci=:id")->execute([':id' => $id]);
        $db->prepare("DELETE FROM products WHERE id_product=:id AND is_combo_product=1")->execute([':id' => $id]);
        $db->commit();
        echo json_encode(['status' => 200, 'message' => 'Combo eliminado']);
    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        error_log('deleteCombo error: ' . $e->getMessage());
        pos_fail(500, 'Error al eliminar el combo.');
    }
    exit;
}

// Activar/desactivar combo
if (isset($_POST['toggleComboStatus'])) {
    pos_require_role(['admin', 'superadmin', 'lab_admin', 'despachador_laboratorio']);
    $db = LocalConnection::connect();
    $id = intval($_POST['id_combo']);
    $status = intval($_POST['status']);
    $db->prepare("UPDATE products SET status_product=:s WHERE id_product=:id AND is_combo_product=1")->execute([':s' => $status, ':id' => $id]);
    echo json_encode(['status' => 200]);
    exit;
}

// Subir imagen del combo
if (isset($_POST['uploadComboImage'])) {
    pos_require_role(['admin', 'superadmin', 'lab_admin', 'despachador_laboratorio']);
    $db  = LocalConnection::connect();
    $id  = intval($_POST['id_combo'] ?? 0);
    if (!$id) { echo json_encode(['status' => 400, 'message' => 'ID requerido']); exit; }
    try {
        $file = $_FILES['comboImage'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            echo json_encode(['status' => 400, 'message' => 'No se recibió imagen']); exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['status' => 400, 'message' => 'La imagen no debe superar 5MB']); exit;
        }
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!isset($allowed[$ext])) {
            echo json_encode(['status' => 400, 'message' => 'Formato no permitido (JPG, PNG o WEBP)']); exit;
        }
        $dir = __DIR__ . '/../../views/assets/products';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $fileName = 'combo_' . $id . '_' . time() . '.' . $ext;
        $destPath = $dir . '/' . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            echo json_encode(['status' => 500, 'message' => 'No se pudo guardar la imagen']); exit;
        }
        if (!file_exists($destPath)) {
            echo json_encode(['status' => 500, 'message' => 'La imagen no quedó en disco']); exit;
        }
        $path = 'views/assets/products/' . $fileName;
        $db->prepare("UPDATE products SET img_product=:img WHERE id_product=:id AND is_combo_product=1")
           ->execute([':img' => $path, ':id' => $id]);
        echo json_encode(['status' => 200, 'path' => $path]);
    } catch (Throwable $e) {
        error_log('uploadComboImage error: ' . $e->getMessage());
        pos_fail(500, 'Error al subir la imagen.');
    }
    exit;
}

// Legacy: getComboItems individual
if (isset($_POST['getComboItems'])) {
    $db = LocalConnection::connect();
    $stmt = $db->prepare("SELECT ci.*, p.title_product, p.sku_product, p.unit_product FROM combo_items ci JOIN products p ON ci.id_product_ci=p.id_product WHERE ci.id_combo_ci=:id ORDER BY ci.id_combo_item ASC");
    $stmt->execute([':id' => intval($_POST['id_combo'])]);
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

//=====================================
// CR�DITOS
//=====================================
if (isset($_POST['getCredits'])) {
    $db = LocalConnection::connect();
    $officeId = intval($_POST['id_office'] ?? 0);
    $where = $officeId ? "WHERE c.id_office_credit=:o" : "";
    $stmt = $db->prepare("SELECT c.*, cl.name_client, cl.surname_client, cl.nit_client FROM credits c JOIN clients cl ON c.id_client_credit=cl.id_client $where ORDER BY c.id_credit DESC");
    $params = $officeId ? [':o' => $officeId] : [];
    $stmt->execute($params);
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
if (isset($_POST['createCredit'])) {
    $db = LocalConnection::connect();
    $amount = floatval($_POST['amount'] ?? 0);
    $stmt = $db->prepare("INSERT INTO credits (id_client_credit, id_office_credit, id_admin_credit, amount_credit, balance_credit, due_date_credit, notes_credit, date_created_credit) VALUES (:cl, :of, :ad, :am, :am, :dd, :nt, CURDATE())");
    $stmt->execute([':cl' => intval($_POST['id_client']), ':of' => intval($_POST['id_office']), ':ad' => intval($_POST['id_admin'] ?? 0), ':am' => $amount, ':dd' => $_POST['due_date'] ?? null, ':nt' => $_POST['notes'] ?? '']);
    echo json_encode(['status' => 200, 'id' => $db->lastInsertId(), 'message' => 'Cr�dito creado']);
    exit;
}
if (isset($_POST['addCreditPayment'])) {
    $db       = LocalConnection::connect();
    $amount   = pos_money('amount');
    $idCredit = pos_int('id_credit');
    $method   = $_POST['method'] ?? 'efectivo';
    $ref      = $_POST['reference'] ?? '';
    $idAdmin  = pos_int('id_admin');
    if (!$idCredit) { pos_fail(400, 'Cr�dito inv�lido'); }
    if ($amount <= 0) { pos_fail(400, 'El monto del abono debe ser mayor a 0'); }

    // Comprobante fuera de la transacci�n (I/O de filesystem)
    $filePath = null; $fileWarning = null;
    try {
        $filePath = posStoreSalePaymentFile($_FILES['proof'] ?? null);
    } catch (Exception $e) {
        $fileWarning = $e->getMessage();
    }

    try {
        $res = pos_transaction($db, function(PDO $db) use ($idCredit, $amount, $method, $ref, $filePath, $idAdmin) {
            // FOR UPDATE bloquea la fila: evita "lost update" entre abonos concurrentes
            $stmtC = $db->prepare("SELECT balance_credit FROM credits WHERE id_credit=:id FOR UPDATE");
            $stmtC->execute([':id' => $idCredit]);
            $credit = $stmtC->fetch(PDO::FETCH_OBJ);
            if (!$credit) { throw new RuntimeException('CREDIT_NOT_FOUND'); }

            $newBalance = max(0, round($credit->balance_credit - $amount, 2));
            $newStatus  = $newBalance <= 0 ? 'pagado' : 'activo';

            $db->prepare("INSERT INTO credit_payments (id_credit_payment, amount_payment, method_payment, reference_payment, file_payment, id_admin_payment, date_created_payment) VALUES (:ic, :am, :me, :re, :f, :ad, CURDATE())")
               ->execute([':ic' => $idCredit, ':am' => $amount, ':me' => $method, ':re' => $ref, ':f' => $filePath, ':ad' => $idAdmin]);
            $db->prepare("UPDATE credits SET balance_credit=:b, status_credit=:s WHERE id_credit=:id")
               ->execute([':b' => $newBalance, ':s' => $newStatus, ':id' => $idCredit]);

            return ['new_balance' => $newBalance, 'new_status' => $newStatus];
        });
    } catch (RuntimeException $e) {
        if ($e->getMessage() === 'CREDIT_NOT_FOUND') { pos_fail(404, 'Cr�dito no encontrado'); }
        throw $e;
    }

    pos_ok(array_merge($res, ['file_payment' => $filePath, 'file_warning' => $fileWarning]));
}
if (isset($_POST['getCreditPayments'])) {
    $db = LocalConnection::connect();
    $stmt = $db->prepare("SELECT * FROM credit_payments WHERE id_credit_payment=:id ORDER BY date_created_payment ASC");
    $stmt->execute([':id' => intval($_POST['id_credit'])]);
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

//=====================================
// CONSIGNACIONES
//=====================================
if (isset($_POST['getConsignments'])) {
    $db = LocalConnection::connect();
    $stmt = $db->prepare("SELECT c.*, a.name_admin FROM consignments c LEFT JOIN admins a ON c.id_admin_consignment=a.id_admin WHERE c.id_office_consignment=:o ORDER BY c.id_consignment DESC");
    $stmt->execute([':o' => intval($_POST['id_office'] ?? 0)]);
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
if (isset($_POST['createConsignment'])) {
    $db      = LocalConnection::connect();
    $idAdmin = pos_int('id_admin');
    $idOffice= pos_int('id_office');
    $notes   = $_POST['notes'] ?? '';
    $items   = json_decode($_POST['items'] ?? '[]', true);
    if (!is_array($items) || count($items) === 0) { pos_fail(400, 'Debe incluir al menos un producto'); }

    try {
        $id = pos_transaction($db, function(PDO $db) use ($idAdmin, $idOffice, $notes, $items) {
            $stmt = $db->prepare("INSERT INTO consignments (id_admin_consignment, id_office_consignment, notes_consignment, date_created_consignment) VALUES (:ad, :of, :nt, CURDATE())");
            $stmt->execute([':ad' => $idAdmin, ':of' => $idOffice, ':nt' => $notes]);
            $id = $db->lastInsertId();

            $stmtI   = $db->prepare("INSERT INTO consignment_items (id_consignment, id_product_consignment, qty_assigned, price_consignment) VALUES (:c, :p, :q, :pr)");
            $stmtInv = $db->prepare("UPDATE product_inventory SET stock_inventory = stock_inventory - :q WHERE id_product_inventory=:p AND id_office_inventory=:o AND stock_inventory >= :min_qty");

            foreach ($items as $item) {
                $pid = intval($item['id_product'] ?? 0);
                $qty = floatval($item['qty'] ?? 0);
                if ($pid <= 0 || $qty <= 0) { throw new RuntimeException('ITEM_INVALID'); }
                // Descontar inventario primero; si no afect� filas, no hab�a stock suficiente
                $stmtInv->execute([':q' => $qty, ':p' => $pid, ':o' => $idOffice, ':min_qty' => $qty]);
                if ($stmtInv->rowCount() === 0) {
                    throw new RuntimeException('NO_STOCK:' . $pid);
                }
                $stmtI->execute([':c' => $id, ':p' => $pid, ':q' => $qty, ':pr' => floatval($item['price'] ?? 0)]);
            }
            return $id;
        });
    } catch (RuntimeException $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'NO_STOCK:') === 0) {
            pos_fail(409, 'Stock insuficiente para el producto #' . substr($msg, 9) . '. Consignaci�n cancelada.');
        }
        if ($msg === 'ITEM_INVALID') { pos_fail(400, 'Producto o cantidad inv�lidos'); }
        throw $e;
    }

    pos_ok(['id' => $id], 'Consignaci�n creada');
}
if (isset($_POST['getConsignmentItems'])) {
    $db = LocalConnection::connect();
    $stmt = $db->prepare("SELECT ci.*, p.title_product, p.sku_product, p.unit_product FROM consignment_items ci JOIN products p ON ci.id_product_consignment=p.id_product WHERE ci.id_consignment=:id");
    $stmt->execute([':id' => intval($_POST['id_consignment'])]);
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
if (isset($_POST['liquidateConsignment'])) {
    $db       = LocalConnection::connect();
    $items    = json_decode($_POST['items'] ?? '[]', true);
    $id       = pos_int('id_consignment');
    $idOffice = pos_int('id_office');
    $ref      = $_POST['reference'] ?? '';
    if (!$id) { pos_fail(400, 'Consignaci�n inv�lida'); }
    if (!is_array($items)) { $items = []; }

    // Comprobante fuera de la transacci�n (I/O de filesystem)
    $filePath = null; $fileWarning = null;
    try {
        $filePath = posStoreSalePaymentFile($_FILES['proof'] ?? null);
    } catch (Exception $e) {
        $fileWarning = $e->getMessage();
    }

    try {
        pos_transaction($db, function(PDO $db) use ($id, $idOffice, $items, $filePath, $ref) {
            // Bloquear y verificar estado: evita liquidar dos veces (doble devoluci�n de stock)
            $stmtS = $db->prepare("SELECT status_consignment FROM consignments WHERE id_consignment=:id FOR UPDATE");
            $stmtS->execute([':id' => $id]);
            $status = $stmtS->fetchColumn();
            if ($status === false)        { throw new RuntimeException('NOT_FOUND'); }
            if ($status === 'liquidada')  { throw new RuntimeException('ALREADY'); }

            $stmtItem = $db->prepare("UPDATE consignment_items SET qty_sold=:s, qty_returned=:r WHERE id_consignment_item=:id AND id_consignment=:cid");
            $stmtBack = $db->prepare("UPDATE product_inventory SET stock_inventory = stock_inventory + :q WHERE id_product_inventory=:p AND id_office_inventory=:o");
            foreach ($items as $item) {
                $sold     = floatval($item['qty_sold'] ?? 0);
                $returned = floatval($item['qty_returned'] ?? 0);
                $stmtItem->execute([':s' => $sold, ':r' => $returned, ':id' => intval($item['id_consignment_item'] ?? 0), ':cid' => $id]);
                if ($returned > 0) {
                    $stmtBack->execute([':q' => $returned, ':p' => intval($item['id_product_consignment'] ?? 0), ':o' => $idOffice]);
                }
            }
            $db->prepare("UPDATE consignments SET status_consignment='liquidada', file_consignment=COALESCE(:f, file_consignment), reference_consignment=COALESCE(NULLIF(:r,''), reference_consignment) WHERE id_consignment=:id")
               ->execute([':f' => $filePath, ':r' => $ref, ':id' => $id]);
        });
    } catch (RuntimeException $e) {
        if ($e->getMessage() === 'NOT_FOUND') { pos_fail(404, 'Consignaci�n no encontrada'); }
        if ($e->getMessage() === 'ALREADY')   { pos_fail(409, 'Esta consignaci�n ya fue liquidada'); }
        throw $e;
    }

    pos_ok(['file_consignment' => $filePath, 'file_warning' => $fileWarning], 'Liquidaci�n completada');
}

//=====================================
// PROVEEDORES UNIFICADOS (POS + Lab)
//=====================================
if (isset($_POST['getSuppliers'])) {
    $db = LocalConnection::connect();
    $type = $_POST['type'] ?? null;
    if ($type && $type !== 'todos') {
        $stmt = $db->prepare("SELECT * FROM suppliers WHERE (type_supplier=:t OR type_supplier='ambos') AND status_supplier=1 ORDER BY supplier_name ASC");
        $stmt->execute([':t' => $type]);
    } else {
        $stmt = $db->prepare("SELECT * FROM suppliers WHERE status_supplier=1 ORDER BY supplier_name ASC");
        $stmt->execute();
    }
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if (isset($_POST['saveSupplier'])) {
    $typeReq = $_POST['type_supplier'] ?? 'ambos';
    if ($typeReq === 'materias_primas') {
        pos_require_role(['admin', 'superadmin', 'lab_admin']);
    } else {
        pos_require_role(['admin', 'superadmin']);
    }
    $db = LocalConnection::connect();
    $id = intval($_POST['id_supplier'] ?? 0);
    $fields = [
        'supplier_name'    => $_POST['supplier_name'] ?? '',
        'supplier_contact' => $_POST['supplier_contact'] ?? '',
        'email_supplier'   => $_POST['email_supplier'] ?? '',
        'ruc_supplier'     => $_POST['ruc_supplier'] ?? '',
        'type_supplier'    => $typeReq,
        'status_supplier'  => intval($_POST['status_supplier'] ?? 1)
    ];
    if ($id > 0) {
        $sets = implode(', ', array_map(fn($k) => "$k=:$k", array_keys($fields)));
        $stmt = $db->prepare("UPDATE suppliers SET $sets WHERE id_supplier=:id");
        $stmt->execute(array_merge($fields, [':id' => $id]));
    } else {
        $cols = implode(', ', array_keys($fields));
        $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($fields)));
        $fields['date_created_supplier'] = date('Y-m-d');
        $cols .= ', date_created_supplier';
        $vals .= ', :date_created_supplier';
        $stmt = $db->prepare("INSERT INTO suppliers ($cols) VALUES ($vals)");
        $stmt->execute($fields);
        $id = $db->lastInsertId();
    }
    echo json_encode(['status' => 200, 'id' => $id, 'message' => 'Proveedor guardado']);
    exit;
}

if (isset($_POST['deleteSupplier'])) {
    $db = LocalConnection::connect();
    $role = $_SESSION['admin']->rol_admin ?? '';
    if (!in_array($role, ['admin', 'superadmin', 'lab_admin'])) {
        http_response_code(403);
        echo json_encode(['status' => 403, 'results' => 'Sin permiso para eliminar proveedores']);
        exit;
    }
    if ($role === 'lab_admin') {
        $check = $db->prepare("SELECT type_supplier FROM suppliers WHERE id_supplier=:id");
        $check->execute([':id' => intval($_POST['id_supplier'])]);
        $type = $check->fetchColumn();
        if ($type !== 'materias_primas') {
            http_response_code(403);
            echo json_encode(['status' => 403, 'results' => 'Solo puedes eliminar proveedores de laboratorio']);
            exit;
        }
    }
    $stmt = $db->prepare("UPDATE suppliers SET status_supplier=0 WHERE id_supplier=:id");
    $stmt->execute([':id' => intval($_POST['id_supplier'])]);
    echo json_encode(['status' => 200, 'message' => 'Proveedor eliminado']);
    exit;
}

//=====================================
// FACTURAS
//=====================================
if (isset($_POST['getInvoices'])) {
    $db = LocalConnection::connect();
    $officeId = intval($_POST['id_office'] ?? 0);
    $status   = $_POST['status'] ?? null;

    $where = $officeId ? "WHERE i.id_office_invoice=:o" : "WHERE 1=1";
    $params = $officeId ? [':o' => $officeId] : [];

    if ($status && $status !== 'todos') {
        $where .= " AND i.status_invoice=:s";
        $params[':s'] = $status;
    }

    $stmt = $db->prepare("
        SELECT i.*, o.transaction_order, o.total_order, o.method_order, o.date_created_order,
               c.name_client, c.surname_client, c.dni_client,
               a.name_admin
        FROM invoices i
        LEFT JOIN orders o ON i.id_order_invoice = o.id_order
        LEFT JOIN clients c ON o.id_client_order = c.id_client
        LEFT JOIN admins a ON o.id_admin_order = a.id_admin
        $where
        ORDER BY i.id_invoice DESC
    ");
    $stmt->execute($params);
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if (isset($_POST['emitInvoice'])) {
    pos_require_role(['admin', 'superadmin', 'cajero']);
    $db = LocalConnection::connect();
    $idInvoice = intval($_POST['id_invoice'] ?? 0);
    $invoiceNumber = $_POST['invoice_number'] ?? ('FAC-' . date('Y') . '-' . str_pad($idInvoice, 5, '0', STR_PAD_LEFT));
    $nit = $_POST['nit'] ?? '';
    $clientName = $_POST['client_name'] ?? '';
    $stmt = $db->prepare("UPDATE invoices SET status_invoice='emitida', invoice_number=:n, nit_invoice=:nit, client_name_invoice=:cn WHERE id_invoice=:id");
    $stmt->execute([':n' => $invoiceNumber, ':nit' => $nit, ':cn' => $clientName, ':id' => $idInvoice]);
    echo json_encode(['status' => 200, 'invoice_number' => $invoiceNumber, 'message' => 'Factura emitida']);
    exit;
}

if (isset($_POST['voidInvoice'])) {
    pos_require_role(['admin', 'superadmin']);
    $db = LocalConnection::connect();
    $stmt = $db->prepare("UPDATE invoices SET status_invoice='anulada' WHERE id_invoice=:id");
    $stmt->execute([':id' => intval($_POST['id_invoice'])]);
    echo json_encode(['status' => 200, 'message' => 'Factura anulada']);
    exit;
}

if (isset($_POST['createInvoiceForOrder'])) {
    $db = LocalConnection::connect();
    $idOrder = intval($_POST['id_order'] ?? 0);
    // Check if already exists
    $check = $db->prepare("SELECT id_invoice FROM invoices WHERE id_order_invoice=:id LIMIT 1");
    $check->execute([':id' => $idOrder]);
    if ($check->fetch()) {
        echo json_encode(['status' => 409, 'message' => 'Ya existe una factura para esta orden']);
        exit;
    }
    $stmt = $db->prepare("SELECT * FROM orders WHERE id_order=:id LIMIT 1");
    $stmt->execute([':id' => $idOrder]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) { echo json_encode(['status' => 404, 'message' => 'Orden no encontrada']); exit; }
    $ins = $db->prepare("INSERT INTO invoices (id_order_invoice, nit_invoice, subtotal_invoice, total_invoice, status_invoice, id_office_invoice, date_created_invoice) VALUES (:o, :n, :sub, :tot, 'pendiente', :off, CURDATE())");
    $ins->execute([':o' => $idOrder, ':n' => $_POST['nit'] ?? 'S/N', ':sub' => $order['subtotal_order'], ':tot' => $order['total_order'], ':off' => $order['id_office_order']]);
    echo json_encode(['status' => 200, 'id' => $db->lastInsertId(), 'message' => 'Factura creada']);
    exit;
}

//=====================================
// PREDEFINED PACKAGING CATALOG (GASTOS DE EMPAQUES)
//=====================================
if (isset($_POST['getPackagings'])) {
    $db = LocalConnection::connect();
    $stmt = $db->prepare("SELECT * FROM packaging_catalog WHERE status_packaging=1 ORDER BY name_packaging ASC");
    $stmt->execute();
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if (isset($_POST['savePackaging'])) {
    pos_require_role(['admin', 'superadmin']);
    $db = LocalConnection::connect();
    $id = intval($_POST['id_packaging'] ?? 0);
    $fields = [
        'name_packaging'  => $_POST['name_packaging'] ?? '',
        'price_packaging' => floatval($_POST['price_packaging'] ?? 0),
        'unit_packaging'  => $_POST['unit_packaging'] ?? 'unidades',
        'status_packaging'=> intval($_POST['status_packaging'] ?? 1)
    ];
    if ($id > 0) {
        $sets = implode(', ', array_map(fn($k) => "$k=:$k", array_keys($fields)));
        $stmt = $db->prepare("UPDATE packaging_catalog SET $sets WHERE id_packaging=:id");
        $stmt->execute(array_merge($fields, [':id' => $id]));
    } else {
        $cols = implode(', ', array_keys($fields));
        $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($fields)));
        $fields['date_created_packaging'] = date('Y-m-d');
        $cols .= ', date_created_packaging';
        $vals .= ', :date_created_packaging';
        $stmt = $db->prepare("INSERT INTO packaging_catalog ($cols) VALUES ($vals)");
        $stmt->execute($fields);
        $id = $db->lastInsertId();
    }
    echo json_encode(['status' => 200, 'id' => $id, 'message' => 'Empaque guardado']);
    exit;
}

if (isset($_POST['deletePackaging'])) {
    pos_require_role(['admin', 'superadmin']);
    $db = LocalConnection::connect();
    $stmt = $db->prepare("UPDATE packaging_catalog SET status_packaging=0 WHERE id_packaging=:id");
    $stmt->execute([':id' => intval($_POST['id_packaging'])]);
    echo json_encode(['status' => 200, 'message' => 'Empaque eliminado']);
    exit;
}

//=====================================
// CAJA: getCashDetails y closeCashRegister
//=====================================

function updateCashTotals($db, $idCash) {
    // 1. Obtener gastos (bills_cash) que pertenezcan a la caja
    $stmtB = $db->prepare("SELECT SUM(cost_bill) as total_bills FROM bills WHERE id_cash_bill = :id AND status_bill = 1");
    // Wait, bill has status? Let's check. Actually CRUD might not have status_bill, let's assume no status or ignore.
    $stmtB = $db->prepare("SELECT SUM(cost_bill) as total_bills FROM bills WHERE id_cash_bill = :id");
    $stmtB->execute([':id' => $idCash]);
    $bills = floatval($stmtB->fetchColumn() ?: 0);

    // 2. Obtener ingresos extras manuales
    $stmtI = $db->prepare("SELECT SUM(amount_income) as total_incomes FROM incomes WHERE id_cash_income = :id");
    $stmtI->execute([':id' => $idCash]);
    $incomes = floatval($stmtI->fetchColumn() ?: 0);

    // 3. Obtener ingresos de ventas (�rdenes completadas de esta caja)
    // Buscamos �rdenes que ocurrieron en la fecha de la caja o entre start y end de la caja.
    // Usaremos un filtro de fecha. Pero es mejor tener un registro. Si la caja no se ha cerrado, 
    // contamos las ventas desde start_cash_date hasta AHORA. Si está cerrada, hasta end_cash_date.
    $stmtC = $db->prepare("SELECT * FROM cashs WHERE id_cash = :id");
    $stmtC->execute([':id' => $idCash]);
    $cash = $stmtC->fetch(PDO::FETCH_ASSOC);

    if (!$cash) return null;

    $startDate = $cash['date_start_cash'];
    $endDate = $cash['date_end_cash'] && $cash['date_end_cash'] !== '0000-00-00 00:00:00' ? $cash['date_end_cash'] : date('Y-m-d H:i:s');
    $officeId = $cash['id_office_cash'];

    $stmtS = $db->prepare("SELECT SUM(total_order) FROM orders WHERE id_office_order = :o AND status_order IN ('Completada', 'Venta Confirmada') AND date_order >= :start AND date_order <= :end");
    $stmtS->execute([':o' => $officeId, ':start' => $startDate, ':end' => $endDate]);
    $sales = floatval($stmtS->fetchColumn() ?: 0);

    // Calculate Efectivo vs QR
    $stmtEf = $db->prepare("SELECT SUM(total_order) FROM orders WHERE id_office_order = :o AND status_order IN ('Completada', 'Venta Confirmada') AND method_order = 'efectivo' AND date_order >= :start AND date_order <= :end");
    $stmtEf->execute([':o' => $officeId, ':start' => $startDate, ':end' => $endDate]);
    $sales_efectivo = floatval($stmtEf->fetchColumn() ?: 0);

    $stmtQr = $db->prepare("SELECT SUM(total_order) FROM orders WHERE id_office_order = :o AND status_order IN ('Completada', 'Venta Confirmada') AND method_order = 'QR' AND date_order >= :start AND date_order <= :end");
    $stmtQr->execute([':o' => $officeId, ':start' => $startDate, ':end' => $endDate]);
    $sales_qr = floatval($stmtQr->fetchColumn() ?: 0);

    $cash_efectivo = $sales_efectivo + $incomes;
    $cash_qr = $sales_qr;

    $money_cash = $sales + $incomes;
    $start_cash = floatval($cash['start_cash']);
    $end_cash = $start_cash + $money_cash - $bills;
    $diff_cash = $end_cash; // Expected final cash
    
    $stmtU = $db->prepare("UPDATE cashs SET bills_cash = :b, money_cash = :m, end_cash = :e, diff_cash = :d, cash_efectivo = :cef, cash_qr = :cqr WHERE id_cash = :id");
    $stmtU->execute([
        ':b' => $bills,
        ':m' => $money_cash,
        ':e' => $end_cash,
        ':d' => $diff_cash,
        ':cef' => $cash_efectivo,
        ':cqr' => $cash_qr,
        ':id' => $idCash
    ]);
    
    return [
        'start_cash' => $start_cash,
        'bills_cash' => $bills,
        'sales_cash' => $sales,
        'incomes_cash' => $incomes,
        'money_cash' => $money_cash,
        'cash_efectivo' => $cash_efectivo,
        'cash_qr' => $cash_qr,
        'end_cash' => $end_cash,
        'diff_cash' => $diff_cash,
        'final_cash' => $end_cash
    ];
}

if (isset($_POST['getCashDetails'])) {
    $db = LocalConnection::connect();
    $idCash = intval($_POST['id_cash'] ?? 0);
    $res = updateCashTotals($db, $idCash);
    
    if (!$res) {
        echo json_encode(['status' => 404, 'message' => 'Caja no encontrada']);
        exit;
    }

    echo json_encode(['status' => 200, 'results' => $res]);
    exit;
}

if (isset($_POST['closeCashRegister'])) {
    pos_require_role(['admin', 'superadmin', 'cajero']);
    $db = LocalConnection::connect();
    $idCash = intval($_POST['id_cash'] ?? 0);
    $physicalCash = floatval($_POST['physical_cash'] ?? 0);
    
    // Primero actualizar totales
    $res = updateCashTotals($db, $idCash);
    if (!$res) {
        echo json_encode(['status' => 404, 'message' => 'Caja no encontrada']);
        exit;
    }

    $gap = $physicalCash - $res['diff_cash']; // Sobrante/Faltante

    $stmtC = $db->prepare("UPDATE cashs SET status_cash = 0, gap_cash = :gap, date_end_cash = NOW() WHERE id_cash = :id");
    $stmtC->execute([':gap' => $gap, ':id' => $idCash]);

    echo json_encode(['status' => 200, 'message' => 'Caja cerrada correctamente', 'gap' => $gap]);
    exit;
}
//=====================================
// TRASPASOS DE STOCK DIRECTOS
//=====================================
if (isset($_POST['getOfficeProducts'])) {
    $db = LocalConnection::connect();
    $id_office = intval($_POST['id_office']);
    $stmt = $db->prepare("
        SELECT p.id_product, p.title_product, p.sku_product, p.unit_product, pi.stock_inventory as stock
        FROM product_inventory pi
        JOIN products p ON pi.id_product_inventory = p.id_product
        WHERE pi.id_office_inventory = :id AND pi.stock_inventory > 0
    ");
    $stmt->execute([':id' => $id_office]);
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if (isset($_POST['createStockTransfer'])) {
    $db = LocalConnection::connect();
    $id_admin = pos_verify_admin_id(intval($_POST['id_admin'] ?? 0));
    $my_office = pos_current_office_id();
    $id_origin = intval($_POST['id_origin_office']);
    $id_dest = intval($_POST['id_dest_office']);
    $id_product = intval($_POST['id_product']);
    $qty = floatval($_POST['qty']);
    $notes = $_POST['notes'] ?? '';

    $admin = pos_current_admin();
    $isGlobalSuper = ($admin->rol_admin ?? '') === 'superadmin' && intval($admin->id_office_admin) === 0;
    if (!$isGlobalSuper && $my_office > 0 && $id_origin !== $my_office) {
        pos_fail(403, 'Solo puede traspasar desde su sucursal');
    }

    if ($id_origin === $id_dest) {
        echo json_encode(['status' => 400, 'message' => 'El origen y destino deben ser diferentes']);
        exit;
    }

    try {
        pos_transaction($db, function(PDO $db) use ($id_origin, $id_dest, $id_product, $qty, $id_admin, $notes) {
            // Verificar stock en origen y bloquear
            $stmtCheck = $db->prepare("SELECT stock_inventory FROM product_inventory WHERE id_office_inventory = :office AND id_product_inventory = :prod FOR UPDATE");
            $stmtCheck->execute([':office' => $id_origin, ':prod' => $id_product]);
            $stock_origin = $stmtCheck->fetchColumn();

            if ($stock_origin === false || floatval($stock_origin) < $qty) {
                throw new RuntimeException('Stock insuficiente en sucursal de origen');
            }

            // Descontar origen
            $stmtSub = $db->prepare("UPDATE product_inventory SET stock_inventory = stock_inventory - :qty WHERE id_office_inventory = :office AND id_product_inventory = :prod");
            $stmtSub->execute([':qty' => $qty, ':office' => $id_origin, ':prod' => $id_product]);

            // Sumar destino
            $stmtAdd = $db->prepare("INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory) VALUES (:prod, :office, :qty, 1, CURDATE()) ON DUPLICATE KEY UPDATE stock_inventory = stock_inventory + :qty");
            $stmtAdd->execute([':qty' => $qty, ':office' => $id_dest, ':prod' => $id_product]);

            // Registrar en stock_transfers
            $stmtLog = $db->prepare("INSERT INTO stock_transfers (id_origin_office, id_dest_office, id_product_transfer, qty_transfer, id_admin_transfer, notes_transfer, status_transfer, date_created_transfer) VALUES (:orig, :dest, :prod, :qty, :adm, :notes, 'completado', CURDATE())");
            $stmtLog->execute([
                ':orig' => $id_origin,
                ':dest' => $id_dest,
                ':prod' => $id_product,
                ':qty' => $qty,
                ':adm' => $id_admin,
                ':notes' => $notes
            ]);
        });
        echo json_encode(['status' => 200, 'message' => 'Traspaso completado']);
    } catch (RuntimeException $e) {
        echo json_encode(['status' => 400, 'message' => $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['status' => 500, 'message' => 'Error interno en traspaso']);
    }
    exit;
}

if (isset($_POST['uploadQR'])) {
    $db = LocalConnection::connect();
    $officeId = (int)($_POST['id_office_qr'] ?? 0);
    if ($officeId <= 0) {
        echo json_encode(["status" => 400, "message" => "Sucursal no válida"]);
        return;
    }

    if (!isset($_FILES['qrImage']) || $_FILES['qrImage']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["status" => 400, "message" => "Debe seleccionar una imagen"]);
        return;
    }

    try {
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $ext = strtolower(pathinfo($_FILES['qrImage']['name'], PATHINFO_EXTENSION));
        if (!isset($allowed[$ext])) throw new Exception('Formato de imagen no permitido (solo JPG, PNG o WEBP)');
        
        $dir = __DIR__ . '/../../views/assets/files/qrs';
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        $fileName = 'qr_' . $officeId . '_' . time() . '.' . $ext;
        
        if (!move_uploaded_file($_FILES['qrImage']['tmp_name'], $dir . '/' . $fileName)) {
            throw new Exception('No se pudo guardar la imagen en el servidor');
        }
        $qrPath = 'views/assets/files/qrs/' . $fileName;

        // Check if QR already exists for this office
        $stmt = $db->prepare("SELECT id_qr, image_qr FROM qrs WHERE id_office_qr = :o LIMIT 1");
        $stmt->execute([':o' => $officeId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmtU = $db->prepare("UPDATE qrs SET image_qr = :img, date_updated_qr = NOW() WHERE id_qr = :id");
            $stmtU->execute([':img' => $qrPath, ':id' => $existing['id_qr']]);
            $oldPath = __DIR__ . '/../../' . $existing['image_qr'];
            if (is_file($oldPath)) { @unlink($oldPath); }
        } else {
            $stmtI = $db->prepare("INSERT INTO qrs (id_office_qr, image_qr) VALUES (:o, :img)");
            $stmtI->execute([':o' => $officeId, ':img' => $qrPath]);
        }

        echo json_encode(["status" => 200, "message" => "QR guardado correctamente", "path" => $qrPath]);

    } catch (Exception $e) {
        error_log('uploadQR error: ' . $e->getMessage());
        echo json_encode(["status" => 500, "message" => "Error al guardar el QR."]);
    }
    return;
}

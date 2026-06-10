<?php
if (isset($_POST['updateOrderStatus'])) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $db = LocalConnection::connect();
    $id   = intval($_POST['id_order'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['Pendiente Despacho', 'Despachado', 'Pago Pendiente', 'Venta Confirmada', 'Cancelada'];
    if (!$id || !in_array($status, $allowed)) {
        echo json_encode(['status' => 400, 'message' => 'Datos inv�lidos']); exit;
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
    $stmt = $db->prepare("INSERT INTO order_expenses (id_order_expense, concept_expense, amount_expense, id_admin_expense, date_created_expense) VALUES (:ord, :con, :amt, :adm, CURDATE())");
    $stmt->execute([':ord' => intval($_POST['id_order']), ':con' => $_POST['concept'], ':amt' => floatval($_POST['amount']), ':adm' => intval($_POST['id_admin'] ?? 0)]);
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
    $mime = function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : $allowed[$ext];
    if ($mime !== $allowed[$ext]) {
        throw new Exception('El contenido del archivo no coincide con su extensi�n');
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
if (isset($_POST['getComboItems'])) {
    $db = LocalConnection::connect();
    $stmt = $db->prepare("SELECT ci.*, p.title_product, p.sku_product, p.unit_product FROM combo_items ci JOIN products p ON ci.id_product_ci=p.id_product WHERE ci.id_combo_ci=:id");
    $stmt->execute([':id' => intval($_POST['id_combo'])]);
    echo json_encode(['status' => 200, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
if (isset($_POST['saveComboItem'])) {
    $db = LocalConnection::connect();
    if (isset($_POST['id_combo_item']) && $_POST['id_combo_item']) {
        $stmt = $db->prepare("UPDATE combo_items SET id_product_ci=:p, qty_ci=:q WHERE id_combo_item=:id");
        $stmt->execute([':p' => intval($_POST['id_product']), ':q' => floatval($_POST['qty']), ':id' => intval($_POST['id_combo_item'])]);
    } else {
        $stmt = $db->prepare("INSERT INTO combo_items (id_combo_ci, id_product_ci, qty_ci) VALUES (:c, :p, :q)");
        $stmt->execute([':c' => intval($_POST['id_combo']), ':p' => intval($_POST['id_product']), ':q' => floatval($_POST['qty'])]);
    }
    echo json_encode(['status' => 200, 'message' => 'Guardado']);
    exit;
}
if (isset($_POST['deleteComboItem'])) {
    $db = LocalConnection::connect();
    $stmt = $db->prepare("DELETE FROM combo_items WHERE id_combo_item=:id");
    $stmt->execute([':id' => intval($_POST['id_combo_item'])]);
    echo json_encode(['status' => 200]);
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
    $db = LocalConnection::connect();
    $id = intval($_POST['id_supplier'] ?? 0);
    $fields = [
        'supplier_name'    => $_POST['supplier_name'] ?? '',
        'supplier_contact' => $_POST['supplier_contact'] ?? '',
        'email_supplier'   => $_POST['email_supplier'] ?? '',
        'ruc_supplier'     => $_POST['ruc_supplier'] ?? '',
        'type_supplier'    => $_POST['type_supplier'] ?? 'ambos',
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
    // contamos las ventas desde start_cash_date hasta AHORA. Si est� cerrada, hasta end_cash_date.
    $stmtC = $db->prepare("SELECT * FROM cashs WHERE id_cash = :id");
    $stmtC->execute([':id' => $idCash]);
    $cash = $stmtC->fetch(PDO::FETCH_ASSOC);

    if (!$cash) return null;

    $startDate = $cash['date_start_cash'];
    $endDate = $cash['date_end_cash'] && $cash['date_end_cash'] !== '0000-00-00 00:00:00' ? $cash['date_end_cash'] : date('Y-m-d H:i:s');
    $officeId = $cash['id_office_cash'];

    $stmtS = $db->prepare("SELECT SUM(total_order) FROM orders WHERE id_office_order = :o AND status_order = 'Completada' AND date_order >= :start AND date_order <= :end");
    $stmtS->execute([':o' => $officeId, ':start' => $startDate, ':end' => $endDate]);
    $sales = floatval($stmtS->fetchColumn() ?: 0);

    $money_cash = $sales + $incomes;
    $start_cash = floatval($cash['start_cash']);
    $end_cash = $start_cash + $money_cash - $bills;
    $diff_cash = $end_cash; // Expected final cash
    
    $stmtU = $db->prepare("UPDATE cashs SET bills_cash = :b, money_cash = :m, end_cash = :e, diff_cash = :d WHERE id_cash = :id");
    $stmtU->execute([
        ':b' => $bills,
        ':m' => $money_cash,
        ':e' => $end_cash,
        ':d' => $diff_cash,
        ':id' => $idCash
    ]);
    
    return [
        'start_cash' => $start_cash,
        'bills_cash' => $bills,
        'sales_cash' => $sales,
        'incomes_cash' => $incomes,
        'money_cash' => $money_cash,
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

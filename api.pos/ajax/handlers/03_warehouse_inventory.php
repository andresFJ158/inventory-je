<?php

//=====================================
// GET PURCHASABLE PRODUCTS
// Productos externos: sin receta, sin producción y sin combo.
//=====================================
if (isset($_POST["getPurchasableProducts"])) {
	$db = LocalConnection::connect();
	$stmt = $db->prepare("
		SELECT p.id_product,
		       CONCAT(
		       	COALESCE(NULLIF(p.title_product, ''), CONCAT('Producto #', p.id_product)),
		       	CASE WHEN COALESCE(p.sku_product, '') <> '' THEN CONCAT(' · ', p.sku_product) ELSE '' END
		       ) AS title_product,
		       p.sku_product,
		       p.unit_product
		FROM products p
		WHERE p.status_product = 1
		  AND COALESCE(p.is_compound_product, 0) = 0
		  AND NOT EXISTS (SELECT 1 FROM recipes r WHERE r.id_product_recipe = p.id_product)
		  AND NOT EXISTS (SELECT 1 FROM productions pr WHERE pr.id_packaged_product = p.id_product)
		ORDER BY p.title_product ASC
	");
	$stmt->execute();
	pos_ok(['results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

//=====================================
// GET MY WAREHOUSE MOVEMENTS
//=====================================
if (isset($_POST["getMyWarehouseMovements"])) {
	$id_admin = $_POST["id_admin"];
	$id_office = intval($_POST["id_office"]);
	$id_warehouse = isset($_POST["id_warehouse"]) ? intval($_POST["id_warehouse"]) : 0;
	$db = LocalConnection::connect();

	// Consultar stock_transfers: muestra inbound (llegaron), outbound (despachados) y rechazados
	$stmt = $db->prepare("
		SELECT 
			st.date_created_transfer as date_created_assignment,
			CASE 
				WHEN st.status_transfer = 'rechazado' THEN 'rechazado'
				WHEN st.status_transfer = 'en_transito' AND st.id_dest_office = :office_in AND (:wh_in = 0 OR st.id_dest_warehouse = :wh_in) THEN 'despacho_pendiente'
				WHEN st.status_transfer = 'recibido' AND st.id_dest_office = :office_in2 AND (:wh_in2 = 0 OR st.id_dest_warehouse = :wh_in2) THEN 'despacho'
				WHEN st.id_origin_office = :office_out AND st.status_transfer = 'en_transito' THEN 'enviado_pendiente'
				WHEN st.id_origin_office = :office_out2 AND st.status_transfer = 'recibido' THEN 'enviado_confirmado'
				ELSE st.status_transfer
			END as type_assignment,
			p.title_product,
			st.qty_transfer as qty_assignment,
			COALESCE(st.notes_transfer, '') as notes_assignment,
			origin_o.title_office as origin_office_name,
			dest_o.title_office as dest_office_name
		FROM stock_transfers st
		JOIN products p ON st.id_product_transfer = p.id_product
		LEFT JOIN offices origin_o ON st.id_origin_office = origin_o.id_office
		LEFT JOIN offices dest_o ON st.id_dest_office = dest_o.id_office
		WHERE (
			(st.id_dest_office = :office_dest AND (:wh_dest = 0 OR st.id_dest_warehouse = :wh_dest))
			OR
			(st.id_origin_office = :office_orig)
		)
		AND (
			:wh_filter = 0
			OR st.id_dest_warehouse = :wh_filter2
			OR EXISTS (SELECT 1 FROM admins a WHERE a.id_admin = st.id_admin_transfer AND a.id_warehouse_admin = :wh_filter3)
		)
		ORDER BY st.id_transfer DESC
	");
	$stmt->execute([
		':office_in'   => $id_office,
		':wh_in'       => $id_warehouse,
		':office_in2'  => $id_office,
		':wh_in2'      => $id_warehouse,
		':office_out'  => $id_office,
		':office_out2' => $id_office,
		':office_dest' => $id_office,
		':wh_dest'     => $id_warehouse,
		':office_orig' => $id_office,
		':wh_filter'   => $id_warehouse,
		':wh_filter2'  => $id_warehouse,
		':wh_filter3'  => $id_warehouse,
	]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET PENDING INBOUND TRANSFERS
//=====================================
if (isset($_POST["getPendingInboundTransfers"])) {
	$id_office = intval($_POST["id_office"]);
	$id_warehouse = intval($_POST["id_warehouse"] ?? 0);
	$db = LocalConnection::connect();
	$stmt = $db->prepare("
		SELECT st.id_transfer, st.id_origin_office, st.id_dest_office, st.id_product_transfer, st.id_dest_warehouse,
			   st.qty_transfer, st.notes_transfer, st.status_transfer,
			   st.date_created_transfer, p.title_product, p.sku_product, p.unit_product,
			   o.title_office AS origin_office_name, a.name_admin AS dispatched_by, w.title_warehouse AS dest_warehouse_name
		FROM stock_transfers st
		JOIN products p ON st.id_product_transfer = p.id_product
		LEFT JOIN offices o ON st.id_origin_office = o.id_office
		LEFT JOIN admins a ON st.id_admin_transfer = a.id_admin
		LEFT JOIN warehouses w ON st.id_dest_warehouse = w.id_warehouse
		WHERE st.id_dest_office = :office
		  AND (:warehouse = 0 OR st.id_dest_warehouse = :warehouse)
		  AND st.status_transfer = 'en_transito'
		ORDER BY st.id_transfer DESC
	");
	$stmt->execute([':office' => $id_office, ':warehouse' => $id_warehouse]);
	pos_ok(['results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

//=====================================
// GET INBOUND TRANSFER HISTORY
//=====================================
if (isset($_POST["getInboundTransferHistory"])) {
	$id_office = intval($_POST["id_office"]);
	$id_warehouse = intval($_POST["id_warehouse"] ?? 0);
	$db = LocalConnection::connect();
	$stmt = $db->prepare("
		SELECT st.id_transfer, st.qty_transfer, st.notes_transfer, st.status_transfer, st.id_dest_warehouse,
			   st.date_created_transfer, p.title_product, p.sku_product, p.unit_product,
			   o.title_office AS origin_office_name, a.name_admin AS dispatched_by, w.title_warehouse AS dest_warehouse_name
		FROM stock_transfers st
		JOIN products p ON st.id_product_transfer = p.id_product
		LEFT JOIN offices o ON st.id_origin_office = o.id_office
		LEFT JOIN admins a ON st.id_admin_transfer = a.id_admin
		LEFT JOIN warehouses w ON st.id_dest_warehouse = w.id_warehouse
		WHERE st.id_dest_office = :office
		  AND (:warehouse = 0 OR st.id_dest_warehouse = :warehouse)
		  AND st.status_transfer IN ('recibido', 'rechazado')
		ORDER BY st.id_transfer DESC
		LIMIT 100
	");
	$stmt->execute([':office' => $id_office, ':warehouse' => $id_warehouse]);
	pos_ok(['results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

//=====================================
// CONFIRM / REJECT INBOUND TRANSFER
//=====================================
if (isset($_POST["confirmInboundTransfer"])) {
	$db = LocalConnection::connect();
	try {
		$db->beginTransaction();
		pos_confirm_stock_transfer(
			$db,
			intval($_POST['id_transfer']),
			intval($_POST['id_office']),
			intval($_POST['id_admin'])
		);
		$db->commit();
		pos_ok([], 'Transferencia recibida.');
	} catch (Exception $e) {
		if ($db->inTransaction()) $db->rollBack();
		error_log("warehouse receive error: " . $e->getMessage());
		pos_fail(400, $e->getMessage());
	}
}

if (isset($_POST["rejectInboundTransfer"])) {
	$db = LocalConnection::connect();
	try {
		$db->beginTransaction();
		pos_reject_stock_transfer(
			$db,
			intval($_POST['id_transfer']),
			intval($_POST['id_office']),
			intval($_POST['id_admin']),
			trim($_POST['reason'] ?? 'Rechazado por sucursal')
		);
		$db->commit();
		pos_ok([], 'Transferencia rechazada.');
	} catch (Exception $e) {
		if ($db->inTransaction()) $db->rollBack();
		error_log("warehouse reject error: " . $e->getMessage());
		pos_fail(400, $e->getMessage());
	}
}

//=====================================
// GET PRODUCT PRICES FOR POS
//=====================================
if (isset($_POST["getProductPricesForOffice"])) {
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$prices = [];

	try {
		$stmt = $db->prepare("
			SELECT pp.id_product_price, pp.price_sale, pp.price_wholesale, pp.wholesale_qty, pp.cost_reference, pp.source_price
			FROM product_prices pp
			INNER JOIN (
				SELECT id_product_price,
					   COALESCE(
					   	MAX(CASE WHEN id_office_price = :office_pick THEN id_price END),
					   	MAX(CASE WHEN id_office_price = 0 OR id_office_price IS NULL THEN id_price END)
					   ) AS selected_price_id
				FROM product_prices
				WHERE status_price = 1
				  AND (id_office_price = :office_filter OR id_office_price = 0 OR id_office_price IS NULL)
				GROUP BY id_product_price
			) pick ON pick.selected_price_id = pp.id_price
		");
		$stmt->execute([
			':office_pick' => $id_office,
			':office_filter' => $id_office
		]);
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$prices[$row['id_product_price']] = [
				'price' => (float)$row['price_sale'],
				'wholesalePrice' => (float)$row['price_wholesale'],
				'wholesaleQty' => (int)$row['wholesale_qty'],
				'costReference' => (float)$row['cost_reference'],
				'source' => $row['source_price'] ?: 'product_prices'
			];
		}
	} catch (Throwable $e) {
		error_log("[PRODUCT_PRICES] ".$e->getMessage());
	}

	$stmtLegacy = $db->prepare("
		SELECT p.id_product_purchase, p.cost_purchase, p.price_purchase, p.may_product, p.wholesale_quantity
		FROM purchases p
		INNER JOIN (
			SELECT id_product_purchase, MAX(id_purchase) AS max_purchase
			FROM purchases
			WHERE COALESCE(price_purchase, 0) > 0 OR COALESCE(cost_purchase, 0) > 0
			GROUP BY id_product_purchase
		) latest ON latest.max_purchase = p.id_purchase
	");
	$stmtLegacy->execute();
	foreach ($stmtLegacy->fetchAll(PDO::FETCH_ASSOC) as $row) {
		if (!isset($prices[$row['id_product_purchase']])) {
			$prices[$row['id_product_purchase']] = [
				'price' => (float)($row['price_purchase'] ?: $row['cost_purchase']),
				'wholesalePrice' => (float)$row['may_product'],
				'wholesaleQty' => (int)$row['wholesale_quantity'],
				'costReference' => (float)$row['cost_purchase'],
				'source' => 'purchases_legacy'
			];
		}
	}
	pos_ok(['results' => $prices]);
}

//=====================================
// GET ASSIGNED BY OFFICE
//=====================================
if (isset($_POST["getAssignedByOffice"])) {
	$id_office = (int)$_POST["id_office"];
	$id_disp = isset($_POST["id_dispatcher"]) ? (int)$_POST["id_dispatcher"] : 0;
	$id_warehouse = isset($_POST["id_warehouse"]) ? (int)$_POST["id_warehouse"] : 0;
	$db = LocalConnection::connect();

	// Resolver la sucursal real del almacen del despachador.
	$officeFilter = $id_office;
	if ($id_disp > 0) {
		$stmtDisp = $db->prepare("SELECT id_office_admin, id_warehouse_admin FROM admins WHERE id_admin = :id LIMIT 1");
		$stmtDisp->execute([':id' => $id_disp]);
		$dispRow = $stmtDisp->fetch(PDO::FETCH_ASSOC);
		if ($dispRow && (int)$dispRow['id_office_admin'] > 0) {
			$officeFilter = (int)$dispRow['id_office_admin'];
		} elseif ($dispRow && (int)$dispRow['id_warehouse_admin'] > 0) {
			$stmtWH = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
			$stmtWH->execute([':wh' => $dispRow['id_warehouse_admin']]);
			$officeFilter = (int)$stmtWH->fetchColumn();
		}
	}

	// En lugar de usar warehouse_assignments (que no limpia despacho_pendiente),
	// consultamos directamente la tabla stock_transfers para obtener lo que está realmente en tránsito.
	$stmtPending = $db->prepare("
		SELECT id_product_transfer as id_product, SUM(qty_transfer) as total_pending
		FROM stock_transfers st
		JOIN admins a ON st.id_admin_transfer = a.id_admin
		WHERE st.id_origin_office = :office
		  AND st.status_transfer = 'en_transito'
		  AND (:wh = 0 OR a.id_warehouse_admin = :wh OR a.id_warehouse_admin = 0)
		GROUP BY id_product_transfer
	");
	$stmtPending->execute([':office' => $officeFilter, ':wh' => $id_warehouse]);
	echo json_encode($stmtPending->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET PENDING QC
//=====================================
if(isset($_POST["getPendingQC"]) && $_POST["getPendingQC"] == "ok") {
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT p.id_production, p.total_qty_production, p.date_updated_production,
			   p.real_total_cost, p.real_unit_cost, p.id_packaged_product,
			   p.qty_packaged_production,
			   pr.title_product AS name_recipe, pkg.title_product, pkg.unit_product
		FROM productions p
		JOIN products pr ON p.id_product_production = pr.id_product
		LEFT JOIN products pkg ON p.id_packaged_product = pkg.id_product
		WHERE p.id_office_production = :office AND p.status_production = 'pendiente_qc'
		ORDER BY p.date_updated_production ASC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET STOCK FOR CAJERO / VENDEDOR (Mi Inventario)
// Reads from product_inventory using the admin's assigned warehouse.
//=====================================
if (isset($_POST["getSubWarehouseStock"])) {
    $id_admin   = intval($_POST["id_admin"]);
    $id_office  = intval($_POST["id_office"]);
    $id_warehouse = intval($_POST["id_warehouse"]);
    $db = LocalConnection::connect();

    // If no warehouse is sent, look it up from the admin record
    if ($id_warehouse <= 0 && $id_admin > 0) {
        $stmtAdm = $db->prepare("SELECT id_warehouse_admin, id_office_admin FROM admins WHERE id_admin = :a LIMIT 1");
        $stmtAdm->execute([':a' => $id_admin]);
        $admRow = $stmtAdm->fetch(PDO::FETCH_ASSOC);
        if ($admRow) {
            $id_warehouse = intval($admRow['id_warehouse_admin']);
            if ($id_office <= 0) $id_office = intval($admRow['id_office_admin']);
        }
    }

    // If still no office, fallback (shouldn't happen in normal flow)
    if ($id_office <= 0) {
        echo json_encode([]);
        exit;
    }

    // Build the WHERE clause: match office + specific warehouse (or all warehouses in office if 0)
    if ($id_warehouse > 0) {
        $stmt = $db->prepare("
            SELECT p.id_product, p.title_product, p.sku_product, p.unit_product, p.img_product,
                   pi.stock_inventory as stock
            FROM product_inventory pi
            JOIN products p ON pi.id_product_inventory = p.id_product
            WHERE pi.id_office_inventory = :office
              AND pi.id_warehouse_inventory = :wh
              AND pi.status_inventory = 1
              AND pi.stock_inventory > 0
            ORDER BY p.title_product ASC
        ");
        $stmt->execute([':office' => $id_office, ':wh' => $id_warehouse]);
    } else {
        // Admin has no warehouse — show sum of ALL stock in the office across all warehouses!
        $stmt = $db->prepare("
            SELECT p.id_product, p.title_product, p.sku_product, p.unit_product, p.img_product,
                   SUM(pi.stock_inventory) as stock
            FROM product_inventory pi
            JOIN products p ON pi.id_product_inventory = p.id_product
            WHERE pi.id_office_inventory = :office
              AND pi.status_inventory = 1
              AND pi.stock_inventory > 0
            GROUP BY p.id_product
            ORDER BY p.title_product ASC
        ");
        $stmt->execute([':office' => $id_office]);
    }

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
    exit;
}

//=====================================
if (isset($_POST["getAllWarehousesStock"])) {
    $id_office = intval($_POST["id_office"]);
    $db = LocalConnection::connect();

    // 1. Obtener almacenes: si id_office=0 (superadmin), traer TODOS sin filtro de sucursal
    if ($id_office === 0) {

        $stmtWH = $db->prepare("
            SELECT w.id_warehouse, w.title_warehouse,
                   COALESCE(GROUP_CONCAT(a.name_admin SEPARATOR ', '), '') as admin_name,
                   w.id_office_warehouse,
                   COALESCE(o.title_office, 'Sin Sucursal') as office_name
            FROM warehouses w
            LEFT JOIN admins a ON a.id_warehouse_admin = w.id_warehouse AND a.status_admin = 1
            LEFT JOIN offices o ON w.id_office_warehouse = o.id_office
            GROUP BY w.id_warehouse
            ORDER BY w.id_office_warehouse ASC, w.id_warehouse ASC
        ");
        $stmtWH->execute();
    } else {
        $stmtWH = $db->prepare("
            SELECT w.id_warehouse, w.title_warehouse,
                   COALESCE(GROUP_CONCAT(a.name_admin SEPARATOR ', '), '') as admin_name,
                   w.id_office_warehouse,
                   COALESCE(o.title_office, 'Sin Sucursal') as office_name
            FROM warehouses w
            LEFT JOIN admins a ON a.id_warehouse_admin = w.id_warehouse AND a.status_admin = 1
            LEFT JOIN offices o ON w.id_office_warehouse = o.id_office
            WHERE w.id_office_warehouse = :office
            GROUP BY w.id_warehouse
            ORDER BY w.id_warehouse ASC
        ");
        $stmtWH->execute([':office' => $id_office]);
    }
    $warehouses = $stmtWH->fetchAll(PDO::FETCH_ASSOC);

    // 2. Por cada almacén, obtener productos con stock usando su propia sucursal
    foreach ($warehouses as &$wh) {
        $whOffice = intval($wh['id_office_warehouse']);
        $stmtProd = $db->prepare("
            SELECT p.id_product, p.title_product, p.sku_product, p.unit_product,
                   pi.stock_inventory as stock,
                   p.img_product
            FROM product_inventory pi
            JOIN products p ON pi.id_product_inventory = p.id_product
            WHERE pi.id_office_inventory = :office
              AND pi.id_warehouse_inventory = :wh
              AND pi.status_inventory = 1
              AND pi.stock_inventory > 0
            ORDER BY p.title_product ASC
        ");
        $stmtProd->execute([':office' => $whOffice, ':wh' => $wh['id_warehouse']]);
        $wh['products'] = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
        $wh['total_stock'] = array_sum(array_column($wh['products'], 'stock'));
    }

    echo json_encode(['status' => 200, 'results' => $warehouses]);
    exit;
}

//=====================================
// GET WAREHOUSE MOVEMENTS
//=====================================
if (isset($_POST["getWarehouseMovements"])) {
	$id_office = (int)$_POST["id_office"];
	$id_disp = isset($_POST["id_dispatcher"]) ? (int)$_POST["id_dispatcher"] : 0;
	$id_warehouse = isset($_POST["id_warehouse"]) ? (int)$_POST["id_warehouse"] : 0;
	$db = LocalConnection::connect();

	// Obtener la sucursal real del almacen del despachador (para Didier que tiene id_office=0)
	$officeFilter = $id_office;
	$dispRow = null;
	if ($id_disp > 0) {
		$stmtDisp = $db->prepare("SELECT id_office_admin, id_warehouse_admin FROM admins WHERE id_admin = :id LIMIT 1");
		$stmtDisp->execute([':id' => $id_disp]);
		$dispRow = $stmtDisp->fetch(PDO::FETCH_ASSOC);
		if ($dispRow && (int)$dispRow['id_office_admin'] > 0) {
			$officeFilter = (int)$dispRow['id_office_admin'];
		} elseif ($dispRow && (int)$dispRow['id_warehouse_admin'] > 0) {
			$stmtWH = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
			$stmtWH->execute([':wh' => $dispRow['id_warehouse_admin']]);
			$officeFilter = (int)$stmtWH->fetchColumn();
		}
	}

	$stmt = $db->prepare("
		SELECT wa.date_created_assignment, wa.type_assignment, p.title_product, wa.qty_assignment, wa.notes_assignment,
			   COALESCE(req_a.name_admin, sale_a.name_admin, 'Sucursal') as name_admin,
			   disp_a.name_admin as dispatcher_name,
			   o.title_office as office_name
		FROM warehouse_assignments wa
		JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
		JOIN products p ON wa.id_product_assignment = p.id_product
		LEFT JOIN inventory_requests ir ON wa.id_request_assignment = ir.id_request
		LEFT JOIN admins req_a ON ir.id_admin_request = req_a.id_admin
		LEFT JOIN admins sale_a ON (wa.type_assignment = 'venta' AND wa.id_dispatched_by = sale_a.id_admin)
		LEFT JOIN admins disp_a ON (wa.type_assignment = 'despacho' AND wa.id_dispatched_by = disp_a.id_admin)
		LEFT JOIN offices o ON sw.id_office_sub_warehouse = o.id_office
		WHERE (sw.id_office_sub_warehouse = :office AND (:wh = 0 OR wa.id_warehouse_assignment = :wh OR wa.id_warehouse_assignment = 0))
		   OR (wa.type_assignment = 'despacho' AND (
		         disp_a.id_office_admin = :office 
		         OR disp_a.id_warehouse_admin = :wh2
		         OR (disp_a.id_office_admin = 0 AND disp_a.id_warehouse_admin IN (SELECT id_warehouse FROM warehouses WHERE id_office_warehouse = :office))
		      ))
		ORDER BY wa.id_assignment DESC
	");
	$stmt->execute([
		':office' => $officeFilter,
		':wh' => $id_warehouse,
		':wh2' => $dispRow['id_warehouse_admin'] ?? 0
	]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// ASSIGN TO SUB WAREHOUSE
//=====================================
if (isset($_POST["assignToSubWarehouse"])) {
	$id_product = $_POST["id_product"];
	$id_admin_dest = $_POST["id_admin_dest"];
	$qty = $_POST["qty"];
	$notes = $_POST["notes"];
	$id_office = $_POST["id_office"];
	$id_dispatched_by = $_POST["id_dispatched_by"];
	$db = LocalConnection::connect();

	try {
		$db->beginTransaction();
		
		$stmtAdmin = $db->prepare("SELECT id_office_admin, name_admin FROM admins WHERE id_admin = :admin LIMIT 1");
		$stmtAdmin->execute([':admin' => $id_admin_dest]);
		$adminRow = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
		$dest_office_id = $adminRow ? (int)$adminRow['id_office_admin'] : $id_office;

		$stmtDispOffice = $db->prepare("SELECT id_office_admin, id_warehouse_admin FROM admins WHERE id_admin = :disp LIMIT 1");
		$stmtDispOffice->execute([':disp' => $id_dispatched_by]);
		$dispRow = $stmtDispOffice->fetch(PDO::FETCH_ASSOC);
		$dispOffice = $dispRow ? (int)$dispRow['id_office_admin'] : (int)$id_office;
		if ($dispOffice <= 0 && $dispRow && (int)$dispRow['id_warehouse_admin'] > 0) {
			// Despachador con warehouse asignado � obtener la sucursal del warehouse
			$stmtWHOff = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
			$stmtWHOff->execute([':wh' => $dispRow['id_warehouse_admin']]);
			$dispOffice = (int)$stmtWHOff->fetchColumn();
		}
		pos_create_stock_transfer($db, (int)$id_product, (int)$dispOffice, (int)$dest_office_id, (float)$qty, (int)$id_dispatched_by, $notes);

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		error_log("warehouse error: " . $e->getMessage()); echo "error: Error al procesar la operación.";
	}
	exit;
}

//=====================================
// TRANSFER STOCK BETWEEN OFFICES
//=====================================
if (isset($_POST["transferStockBetweenOffices"])) {
	$id_product = $_POST["id_product"];
	$id_office_source = $_POST["id_office_source"];
	$id_office_dest = $_POST["id_office_dest"];
	$id_warehouse_source = (int)($_POST["id_warehouse_source"] ?? 0);
	$id_warehouse_dest = (int)($_POST["id_warehouse_dest"] ?? 0);
	$qty = $_POST["qty"];
	$notes = $_POST["notes"];
	$id_dispatched_by = $_POST["id_dispatched_by"];
	$db = LocalConnection::connect();

	try {
		$db->beginTransaction();

		pos_create_stock_transfer($db, (int)$id_product, (int)$id_office_source, (int)$id_office_dest, (float)$qty, (int)$id_dispatched_by, $notes, null, $id_warehouse_source, $id_warehouse_dest);

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		error_log("warehouse error: " . $e->getMessage()); echo "error: Error al procesar la operación.";
	}
	exit;
}

//=====================================
// DISPATCH DIRECT TO WAREHOUSE (AS PURCHASE)
//=====================================
if (isset($_POST["dispatchDirectToWarehouse"])) {
	$id_product = $_POST["id_product"];
	$id_office_source = $_POST["id_office_source"];
	$id_warehouse_dest = (int)$_POST["id_warehouse_dest"];
	$qty = $_POST["qty"];
	$notes = $_POST["notes"];
	$id_dispatched_by = $_POST["id_dispatched_by"];
	$db = LocalConnection::connect();

	try {
		$db->beginTransaction();

		// Find the true office associated with the destination warehouse
		$stmtWh = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
		$stmtWh->execute([':wh' => $id_warehouse_dest]);
		$id_office_dest = (int)$stmtWh->fetchColumn();

		if ($id_office_dest <= 0) {
			echo "error: El almacén de destino seleccionado no está configurado correctamente.";
			$db->rollBack();
			exit;
		}

		$unit_price = floatval($_POST['unit_price'] ?? 0);
		$wholesale_price = floatval($_POST['wholesale_price'] ?? 0);
		$wholesale_qty = floatval($_POST['wholesale_qty'] ?? 0);
		if ($unit_price <= 0) {
			echo "error: Debe registrar un precio POS mayor a cero para el destino.";
			$db->rollBack();
			exit;
		}

		$stmtCost = $db->prepare("SELECT COALESCE(rte_product, 0) FROM products WHERE id_product = :prod LIMIT 1");
		$stmtCost->execute([':prod' => $id_product]);
		$unit_cost = (float)($stmtCost->fetchColumn() ?: 0);
		$stmtPrice = $db->prepare("
			INSERT INTO product_prices
				(id_product_price, id_office_price, price_sale, price_wholesale, wholesale_qty, cost_reference, source_price, status_price, id_admin_price, date_created_price)
			VALUES
				(:prod, :office, :price, :wholesale_price, :wholesale_qty, :cost, 'despacho_laboratorio', 1, :admin, NOW())
		");
		$stmtPrice->execute([
			':prod' => $id_product,
			':office' => $id_office_dest,
			':price' => $unit_price,
			':wholesale_price' => $wholesale_price,
			':wholesale_qty' => $wholesale_qty,
			':cost' => $unit_cost,
			':admin' => $id_dispatched_by
		]);

		pos_create_stock_transfer($db, (int)$id_product, (int)$id_office_source, (int)$id_office_dest, (float)$qty, (int)$id_dispatched_by, $notes, null, 0, (int)$id_warehouse_dest);

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		error_log("warehouse error: " . $e->getMessage()); echo "error: Error al procesar la operación: " . $e->getMessage();
	}
	exit;
}

//=====================================
// SUBMIT QUALITY CHECK
//=====================================
if(isset($_POST["submitQualityCheck"]) && $_POST["submitQualityCheck"] == "ok") {
	$db = LocalConnection::connect();
	$id_admin_qc_req = intval($_POST['id_admin'] ?? 0);
	if ($id_admin_qc_req > 0) {
		$stmtQcRole = $db->prepare("SELECT rol_admin FROM admins WHERE id_admin = :id LIMIT 1");
		$stmtQcRole->execute([':id' => $id_admin_qc_req]);
		$rolQc = $stmtQcRole->fetchColumn();
		if (!in_array($rolQc, ["superadmin", "admin", "lab_admin", "qc_inspector", "lab_calidad"])) {
			echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para registrar controles de calidad.']);
			exit;
		}
	}
	try {
		$db->beginTransaction();

		$id_production  = intval($_POST['id_production']);
		$id_admin       = intval($_POST['id_admin']);
		$id_office      = intval($_POST['id_office']);
		$result         = $_POST['result_qc']; // aprobado | rechazado | aprobado_con_obs
		$qty_approved   = floatval($_POST['qty_approved']);
		$qty_rejected   = floatval($_POST['qty_rejected']);
		$notes          = trim($_POST['notes_qc']);

		// Validar que la producción existe y está pendiente de QC
		$stmtCheck = $db->prepare("SELECT id_production, id_packaged_product, status_production, real_unit_cost FROM productions WHERE id_production = :id AND id_office_production = :office");
		$stmtCheck->execute([':id' => $id_production, ':office' => $id_office]);
		$prod = $stmtCheck->fetch(PDO::FETCH_ASSOC);

		if (!$prod || $prod['status_production'] !== 'pendiente_qc') {
			echo 'error|La producción no está en estado pendiente de QC.';
			exit;
		}

		// Insertar registro de QC
		$stmtInsert = $db->prepare("
			INSERT INTO quality_checks
				(id_production_qc, id_admin_qc, id_office_qc, result_qc, qty_approved_qc, qty_rejected_qc, notes_qc, date_created_qc)
			VALUES (:id_prod, :id_admin, :id_office, :result, :approved, :rejected, :notes, CURDATE())
		");
		$stmtInsert->execute([
			':id_prod'   => $id_production,
			':id_admin'  => $id_admin,
			':id_office' => $id_office,
			':result'    => $result,
			':approved'  => $qty_approved,
			':rejected'  => $qty_rejected,
			':notes'     => $notes
		]);

		$new_status = ($result === 'rechazado') ? 'rechazado' : 'completado';
		$stmtProd = $db->prepare("UPDATE productions SET 
			status_production = :status,
			qty_approved_production = :approved,
			qty_rejected_production = :rejected,
			result_qc_production = :result_qc,
			notes_qc_production = :notes
		WHERE id_production = :id");
		$stmtProd->execute([
			':status' => $new_status, 
			':approved' => $qty_approved,
			':rejected' => $qty_rejected,
			':result_qc' => $result,
			':notes' => $notes,
			':id' => $id_production
		]);

		// Solo ingresamos inventario de venta SI pasa el QC (aprobado o aprobado_con_obs)
		if ($qty_approved > 0 && $prod['id_packaged_product'] && $new_status === 'completado') {
			$stmtFind = $db->prepare("SELECT stock_product, rte_product FROM products WHERE id_product = :id");
            $stmtFind->execute([':id' => $prod['id_packaged_product']]);
            $pData = $stmtFind->fetch(PDO::FETCH_ASSOC);

            $old_stock = (float)$pData['stock_product'];
            $old_rte = (float)$pData['rte_product'];
            $unit_cost = (float)$prod['real_unit_cost'];
            
            // Recalculate unit cost based on approved quantity to account for rejected units (merma)
            $stmtCost = $db->prepare("SELECT real_total_cost FROM productions WHERE id_production = :id");
            $stmtCost->execute([':id' => $id_production]);
            $real_total_cost = (float)$stmtCost->fetchColumn();
            
            if ($qty_approved > 0) {
                $unit_cost = $real_total_cost / $qty_approved;
                // Update production with the real unit cost after QC
                $stmtUpdCost = $db->prepare("UPDATE productions SET real_unit_cost = :uc WHERE id_production = :id");
                $stmtUpdCost->execute([':uc' => $unit_cost, ':id' => $id_production]);
            }

            $new_stock = $old_stock + $qty_approved;
            $new_rte = (($old_stock * $old_rte) + ($qty_approved * $unit_cost)) / $new_stock;

			$stmtStock = $db->prepare("
				UPDATE products
				SET rte_product = :rte,
					is_compound_product = 1,
					origin_office_product = CASE WHEN COALESCE(origin_office_product, 0) = 0 THEN :office ELSE origin_office_product END
				WHERE id_product = :id_product
			");
			$stmtStock->execute([':rte' => $new_rte, ':office' => $id_office, ':id_product' => $prod['id_packaged_product']]);

			pos_adjust_product_inventory(
				$db,
				(int)$prod['id_packaged_product'],
				(int)$id_office,
				(float)$qty_approved,
				'produccion_aprobada',
				(int)$id_admin,
				null,
				'Ingreso por QC aprobado de producción #' . $id_production
			);
		}

		$db->commit();
		echo json_encode(['status' => 'ok', 'result' => $new_status]);
	} catch (Exception $e) {
		$db->rollBack();
		error_log('warehouse error: ' . $e->getMessage()); echo 'error|Error al procesar la operación.';
	}
	exit;
}

//=====================================
// GET PRODUCTS FOR WAREHOUSE
//=====================================
if (isset($_POST["getWarehouseProducts"])) {
	$id_warehouse = $_POST["id_warehouse"];
	$db = LocalConnection::connect();

	// Get the office ID of the warehouse
	$stmtWh = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
	$stmtWh->execute([':wh' => $id_warehouse]);
	$id_office = $stmtWh->fetchColumn();

	if ($id_office) {
		// Query stock in that warehouse (pi.stock_inventory - assigned stock)
		$stmt = $db->prepare("
			SELECT p.id_product, p.title_product, p.sku_product, p.unit_product,
				   pi.stock_inventory as stock
			FROM products p
			INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.id_warehouse_inventory = :warehouse AND pi.status_inventory = 1
			WHERE p.status_product = 1
			HAVING stock > 0
			ORDER BY p.id_product DESC
		");
		$stmt->execute([':office' => $id_office, ':warehouse' => $id_warehouse]);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode($results);
	} else {
		echo json_encode([]);
	}
	exit;
}

//=====================================
// CREATE INVENTORY REQUEST
//=====================================
if (isset($_POST["createInventoryRequest"])) {
	$id_product = $_POST["id_product"];
	$qty = $_POST["qty"];
	$notes = $_POST["notes"];
	$id_admin = pos_verify_admin_id(intval($_POST["id_admin"] ?? 0));
	$id_office = pos_current_office_id();
	if ($id_office <= 0) {
		pos_fail(400, 'Oficina de usuario no configurada');
	}
	$id_warehouse = $_POST["id_warehouse"] ?? null;
	$db = LocalConnection::connect();

	$stmt = $db->prepare("
		INSERT INTO inventory_requests (id_admin_request, id_office_request, id_product_request, qty_request, status_request, notes_request, id_warehouse_request, date_created_request)
		VALUES (:admin, :office, :id_prod, :qty, 'pendiente', :notes, :id_wh, NOW())
	");
	if ($stmt->execute([
		':admin' => $id_admin,
		':office' => $id_office,
		':id_prod' => $id_product,
		':qty' => $qty,
		':notes' => $notes,
		':id_wh' => $id_warehouse
	])) {
		echo "ok";
	} else {
		echo "error";
	}
	exit;
}

//=====================================
// GET MY REQUESTS
//=====================================
if (isset($_POST["getMyRequests"])) {
	$id_admin = $_POST["id_admin"];
	$db = LocalConnection::connect();
	$stmt = $db->prepare("
		SELECT ir.date_created_request, p.title_product, ir.qty_request, ir.qty_dispatched_request, ir.status_request, ir.notes_dispatcher_request, ir.notes_request,
			   COALESCE(w.title_warehouse, '-') as title_warehouse
		FROM inventory_requests ir
		JOIN products p ON ir.id_product_request = p.id_product
		LEFT JOIN warehouses w ON ir.id_warehouse_request = w.id_warehouse
		WHERE ir.id_admin_request = :admin
		ORDER BY ir.id_request DESC
	");
	$stmt->execute([':admin' => $id_admin]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET PENDING REQUESTS
//=====================================
if (isset($_POST["getPendingRequests"])) {
	$id_office = $_POST["id_office"];
	$id_warehouse = $_POST["id_warehouse"] ?? null;
	$db = LocalConnection::connect();

	$whFilter = "";
	$params = [':office' => $id_office];
	if ($id_warehouse && $id_warehouse > 0) {
		$whFilter = " AND ir.id_warehouse_request = :wh ";
		$params[':wh'] = $id_warehouse;
	}

	$stmt = $db->prepare("
		SELECT ir.id_request, ir.date_created_request, ir.qty_request, ir.notes_request,
			   a.name_admin, p.title_product,
			   pi.stock_inventory as available_stock
		FROM inventory_requests ir
		JOIN admins a ON ir.id_admin_request = a.id_admin
		JOIN products p ON ir.id_product_request = p.id_product
		INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.status_inventory = 1
		WHERE ir.status_request = 'pendiente' $whFilter
		ORDER BY ir.id_request DESC
	");
	$stmt->execute($params);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET QC HISTORY
//=====================================
if(isset($_POST["getQCHistory"]) && $_POST["getQCHistory"] == "ok") {
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT qc.id_qc, qc.id_production_qc, qc.result_qc,
			   qc.qty_approved_qc, qc.qty_rejected_qc, qc.notes_qc,
			   qc.date_created_qc,
			   a.name_admin AS inspector_name,
			   pr.title_product, pr.unit_product,
			   p.qty_packaged_production, p.total_qty_production
		FROM quality_checks qc
		JOIN admins a ON qc.id_admin_qc = a.id_admin
		JOIN productions p ON qc.id_production_qc = p.id_production
		JOIN products pr ON p.id_product_production = pr.id_product
		WHERE qc.id_office_qc = :office
		ORDER BY qc.date_created_qc ASC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

// GET REQUEST HISTORY
//=====================================
if (isset($_POST["getRequestHistory"])) {
	$id_office = $_POST["id_office"];
	$id_warehouse = $_POST["id_warehouse"] ?? null;
	$db = LocalConnection::connect();

	$whFilter = "";
	$params = [];
	if ($id_warehouse && $id_warehouse > 0) {
		$whFilter = " AND ir.id_warehouse_request = :wh ";
		$params[':wh'] = $id_warehouse;
	}

	$stmt = $db->prepare("
		SELECT ir.date_created_request, ir.qty_request, ir.qty_dispatched_request, ir.status_request, ir.notes_dispatcher_request, ir.notes_request,
			   a.name_admin, p.title_product
		FROM inventory_requests ir
		JOIN admins a ON ir.id_admin_request = a.id_admin
		JOIN products p ON ir.id_product_request = p.id_product
		WHERE ir.status_request != 'pendiente' $whFilter
		ORDER BY ir.id_request DESC
	");
	$stmt->execute($params);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// REJECT REQUEST
//=====================================
if (isset($_POST["rejectRequest"])) {
	pos_require_role(['despachador', 'despachador_laboratorio', 'admin', 'superadmin']);
	$id_request = $_POST["id_request"];
	$notes_dispatcher = $_POST["notes_dispatcher"];
	$id_dispatched_by = $_POST["id_dispatched_by"];
	$db = LocalConnection::connect();

	$stmt = $db->prepare("
		UPDATE inventory_requests
		SET status_request = 'rechazada',
			notes_dispatcher_request = :notes,
			id_dispatched_by_request = :dispatcher
		WHERE id_request = :id
	");
	if ($stmt->execute([
		':notes' => $notes_dispatcher,
		':dispatcher' => $id_dispatched_by,
		':id' => $id_request
	])) {
		echo "ok";
	} else {
		echo "error";
	}
	exit;
}

//=====================================
// DISPATCH REQUEST
//=====================================
if (isset($_POST["dispatchRequest"])) {
	pos_require_role(['despachador', 'despachador_laboratorio', 'admin', 'superadmin']);
	$id_request = $_POST["id_request"];
	$qty_dispatch = $_POST["qty_dispatch"];
	$notes_dispatcher = $_POST["notes_dispatcher"];
	$id_dispatched_by = $_POST["id_dispatched_by"];
	$id_office = $_POST["id_office"];
	$db = LocalConnection::connect();

	try {
		$db->beginTransaction();
		
		// Get request details
		$stmtReq = $db->prepare("SELECT id_admin_request, id_office_request, id_product_request FROM inventory_requests WHERE id_request = :id LIMIT 1");
		$stmtReq->execute([':id' => $id_request]);
		$req = $stmtReq->fetch(PDO::FETCH_ASSOC);
		if (!$req) {
			throw new Exception("Solicitud no encontrada");
		}
		
		$id_admin_dest = $req['id_admin_request'];
		$id_product = $req['id_product_request'];
		
		// Find destination admin's real office ID
		$stmtAdmin = $db->prepare("SELECT id_office_admin, name_admin FROM admins WHERE id_admin = :admin LIMIT 1");
		$stmtAdmin->execute([':admin' => $id_admin_dest]);
		$adminRow = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
		$dest_office_id = $adminRow ? (int)$adminRow['id_office_admin'] : $id_office;
		
		// Update request status
		$stmtUpd = $db->prepare("
			UPDATE inventory_requests
			SET status_request = 'en_transito',
				qty_dispatched_request = :qty,
				notes_dispatcher_request = :notes,
				id_dispatched_by_request = :dispatcher
			WHERE id_request = :id
		");
		$stmtUpd->execute([
			':qty' => $qty_dispatch,
			':notes' => $notes_dispatcher,
			':dispatcher' => $id_dispatched_by,
			':id' => $id_request
		]);

		// Descontar del inventario principal de la sucursal del despachador
		$stmtDispOffice = $db->prepare("SELECT id_office_admin, id_warehouse_admin FROM admins WHERE id_admin = :disp LIMIT 1");
		$stmtDispOffice->execute([':disp' => $id_dispatched_by]);
		$dispRow = $stmtDispOffice->fetch(PDO::FETCH_ASSOC);
		$dispOffice = $dispRow ? (int)$dispRow['id_office_admin'] : (int)$id_office;
		if ($dispOffice <= 0 && $dispRow && (int)$dispRow['id_warehouse_admin'] > 0) {
			// Despachador con warehouse asignado � obtener la sucursal del warehouse
			$stmtWHOff = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
			$stmtWHOff->execute([':wh' => $dispRow['id_warehouse_admin']]);
			$dispOffice = (int)$stmtWHOff->fetchColumn();
		}
		pos_create_stock_transfer($db, (int)$id_product, (int)$dispOffice, (int)$dest_office_id, (float)$qty_dispatch, (int)$id_dispatched_by, $notes_dispatcher, (int)$id_request);
		
		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		error_log("warehouse error: " . $e->getMessage()); echo "error: Error al procesar la operación.";
	}
	exit;
}

/*=============================================
Editar Materia Prima
=============================================*/

<?php
if (isset($_POST["getSubWarehouseStock"])) {
	$id_admin = $_POST["id_admin"];
	$id_office = $_POST["id_office"];
	$role = $_POST["role"];
	$db = LocalConnection::connect();

	if ($role == 'despachador' || $role == 'admin' || $role == 'superadmin') {
		// Despachador/admin: muestra inventario disponible del almac�n
		$stmt = $db->prepare("
			SELECT p.id_product, p.title_product, p.sku_product, p.unit_product,
				   pi.stock_inventory as stock
			FROM products p
			INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.status_inventory = 1
			WHERE p.status_product = 1
		");
		$stmt->execute([':office' => $id_office]);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} else {
		// Para cajero, vendedor u otro rol: verificar si tiene sub-almac�n (por oficina)
		$stmtHasSub = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_office_sub_warehouse = :office LIMIT 1");
		$stmtHasSub->execute([':office' => $id_office]);
		$subRow = $stmtHasSub->fetch(PDO::FETCH_ASSOC);

		if ($subRow) {
			// Tiene sub-almac�n � mostrar stock de la sucursal (suma de todos los sub-almacenes de esa sucursal)
			$stmt = $db->prepare("
				SELECT p.id_product, p.title_product, p.sku_product, p.unit_product,
				       (COALESCE(SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment ELSE 0 END), 0) -
				        COALESCE(SUM(CASE WHEN wa.type_assignment IN ('devolucion', 'venta') THEN wa.qty_assignment ELSE 0 END), 0)) as stock
				FROM warehouse_assignments wa
				JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
				JOIN products p ON wa.id_product_assignment = p.id_product
				WHERE sw.id_office_sub_warehouse = :office
				GROUP BY wa.id_product_assignment
				HAVING stock > 0
			");
			$stmt->execute([':office' => $id_office]);
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} else {
			// Sin sub-almac�n: mostrar inventario principal de la sucursal
			$stmt = $db->prepare("
				SELECT p.id_product, p.title_product, p.sku_product, p.unit_product,
				       pi.stock_inventory as stock
				FROM products p
				INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.status_inventory = 1
				WHERE p.status_product = 1
			");
			$stmt->execute([':office' => $id_office]);
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
	}
	echo json_encode($results);
	exit;
}

//=====================================
// GET MY WAREHOUSE MOVEMENTS
//=====================================
if (isset($_POST["getMyWarehouseMovements"])) {
	$id_admin = $_POST["id_admin"];
	$id_office = $_POST["id_office"];
	$db = LocalConnection::connect();
	$stmt = $db->prepare("
		SELECT wa.date_created_assignment, wa.type_assignment, p.title_product, wa.qty_assignment, wa.notes_assignment
		FROM warehouse_assignments wa
		JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
		JOIN products p ON wa.id_product_assignment = p.id_product
		WHERE sw.id_office_sub_warehouse = :office
		ORDER BY wa.id_assignment DESC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET ASSIGNED BY OFFICE
//=====================================
if (isset($_POST["getAssignedByOffice"])) {
	$id_office = (int)$_POST["id_office"];
	$id_disp = isset($_POST["id_dispatcher"]) ? (int)$_POST["id_dispatcher"] : 0;
	$db = LocalConnection::connect();

	// Resolver la sucursal real del almac�n (Didier tiene id_office=0 pero id_warehouse_admin=1 -> sucursal 8)
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

	$stmt = $db->prepare("
		SELECT wa.id_product_assignment as id_product,
			   SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment
						WHEN wa.type_assignment IN ('devolucion', 'venta') THEN -wa.qty_assignment
						ELSE 0 END) as total_assigned
		FROM warehouse_assignments wa
		JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
		WHERE sw.id_office_sub_warehouse = :office
		GROUP BY wa.id_product_assignment
	");
	$stmt->execute([':office' => $officeFilter]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
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
if (isset($_POST["getSubWarehousesDetail"])) {
	$id_office = $_POST["id_office"];
	$db = LocalConnection::connect();
	
	$stmtSw = $db->prepare("
		SELECT sw.id_sub_warehouse, sw.name_sub_warehouse, COALESCE(a.name_admin, 'Compartido') as name_admin, o.title_office
		FROM sub_warehouses sw
		LEFT JOIN admins a ON sw.id_admin_sub_warehouse = a.id_admin
		LEFT JOIN offices o ON sw.id_office_sub_warehouse = o.id_office
	");
	$stmtSw->execute();
	$subs = $stmtSw->fetchAll(PDO::FETCH_ASSOC);
	
	$results = [];
	foreach ($subs as $s) {
		$stmtProd = $db->prepare("
			SELECT p.title_product,
				   SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment 
							WHEN wa.type_assignment IN ('devolucion', 'venta') THEN -wa.qty_assignment 
							ELSE 0 END) as stock
			FROM warehouse_assignments wa
			JOIN products p ON wa.id_product_assignment = p.id_product
			WHERE wa.id_sub_warehouse_assignment = :id_sub
			GROUP BY wa.id_product_assignment
			HAVING stock > 0
		");
		$stmtProd->execute([':id_sub' => $s['id_sub_warehouse']]);
		$s['products'] = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
		$results[] = $s;
	}
	echo json_encode($results);
	exit;
}

//=====================================
// GET WAREHOUSE MOVEMENTS
//=====================================
if (isset($_POST["getWarehouseMovements"])) {
	$id_office = (int)$_POST["id_office"];
	$id_disp = isset($_POST["id_dispatcher"]) ? (int)$_POST["id_dispatcher"] : 0;
	$db = LocalConnection::connect();

	// Obtener la sucursal real del almac�n del despachador (para Didier que tiene id_office=0)
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
		WHERE sw.id_office_sub_warehouse = :office
		   OR (wa.type_assignment = 'despacho' AND (
		         disp_a.id_office_admin = :office 
		         OR (disp_a.id_office_admin = 0 AND disp_a.id_warehouse_admin IN (SELECT id_warehouse FROM warehouses WHERE id_office_warehouse = :office))
		      ))
		ORDER BY wa.id_assignment DESC
	");
	$stmt->execute([':office' => $officeFilter]);
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
		
		// Find destination admin's real office ID
		$stmtAdmin = $db->prepare("SELECT id_office_admin, name_admin FROM admins WHERE id_admin = :admin LIMIT 1");
		$stmtAdmin->execute([':admin' => $id_admin_dest]);
		$adminRow = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
		$dest_office_id = $adminRow ? (int)$adminRow['id_office_admin'] : $id_office;
		
		// Find or create sub-warehouse for dest admin using destination office ID (compartido por oficina)
		$stmtCheck = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_office_sub_warehouse = :office LIMIT 1");
		$stmtCheck->execute([':office' => $dest_office_id]);
		$sub = $stmtCheck->fetch(PDO::FETCH_ASSOC);
		
		if (!$sub) {
			$subName = "Sub-Almac�n de la Sucursal";
			$stmtIns = $db->prepare("INSERT INTO sub_warehouses (id_admin_sub_warehouse, id_office_sub_warehouse, name_sub_warehouse, status_sub_warehouse, date_created_sub_warehouse) VALUES (0, :office, :name, 1, CURDATE())");
			$stmtIns->execute([':office' => $dest_office_id, ':name' => $subName]);
			$id_sub = $db->lastInsertId();
		} else {
			$id_sub = $sub['id_sub_warehouse'];
		}
		
		// Insert warehouse_assignment record
		$stmtAssign = $db->prepare("
			INSERT INTO warehouse_assignments (id_sub_warehouse_assignment, id_product_assignment, qty_assignment, id_dispatched_by, type_assignment, notes_assignment, date_created_assignment)
			VALUES (:id_sub, :id_prod, :qty, :disp, 'despacho', :notes, NOW())
		");
		$stmtAssign->execute([
			':id_sub' => $id_sub,
			':id_prod' => $id_product,
			':qty' => $qty,
			':disp' => $id_dispatched_by,
			':notes' => $notes
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
		if ($dispOffice > 0) {
			$stmtAvail = $db->prepare("SELECT stock_inventory FROM product_inventory WHERE id_product_inventory = :prod AND id_office_inventory = :office AND status_inventory = 1 LIMIT 1");
			$stmtAvail->execute([':prod' => $id_product, ':office' => $dispOffice]);
			$avail = (double)$stmtAvail->fetchColumn();
			if ($avail < (double)$qty) {
				throw new Exception('Stock insuficiente en el almac�n de origen.');
			}
			$stmtDecrease = $db->prepare("
				UPDATE product_inventory
				SET stock_inventory = stock_inventory - :qty
				WHERE id_product_inventory = :prod AND id_office_inventory = :office AND status_inventory = 1
			");
			$stmtDecrease->execute([':qty' => $qty, ':prod' => $id_product, ':office' => $dispOffice]);
		}

		// Incrementar en el inventario principal de la sucursal de destino
		if ($dest_office_id > 0) {
			$stmtIncrease = $db->prepare("
				INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
				VALUES (:prod, :office, :qty, 1, CURDATE())
				ON DUPLICATE KEY UPDATE
					stock_inventory = stock_inventory + :qty
			");
			$stmtIncrease->execute([':qty' => $qty, ':prod' => $id_product, ':office' => $dest_office_id]);
		}

		// Update products.stock_product
		$stmtUpdProd = $db->prepare("
			UPDATE products SET stock_product = (
				SELECT COALESCE(SUM(stock_inventory), 0) FROM product_inventory WHERE id_product_inventory = :prod
			) WHERE id_product = :prod
		");
		$stmtUpdProd->execute([':prod' => $id_product]);

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error: " . $e->getMessage();
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
	$qty = $_POST["qty"];
	$notes = $_POST["notes"];
	$id_dispatched_by = $_POST["id_dispatched_by"];
	$db = LocalConnection::connect();

	try {
		$db->beginTransaction();

		// Validate availability in source office
		$stmtAvail = $db->prepare("SELECT stock_inventory FROM product_inventory WHERE id_product_inventory = :prod AND id_office_inventory = :office AND status_inventory = 1 LIMIT 1");
		$stmtAvail->execute([':prod' => $id_product, ':office' => $id_office_source]);
		$avail = (double)$stmtAvail->fetchColumn();
		if ($avail < (double)$qty) {
			echo "error: Stock insuficiente en el almac�n de origen.";
			$db->rollBack();
			exit;
		}

		// Find or create sub-warehouse for destination office (compartido por oficina)
		$stmtCheck = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_office_sub_warehouse = :office LIMIT 1");
		$stmtCheck->execute([':office' => $id_office_dest]);
		$sub = $stmtCheck->fetch(PDO::FETCH_ASSOC);

		if (!$sub) {
			$subName = "Sub-Almac�n de la Sucursal";
			$stmtIns = $db->prepare("INSERT INTO sub_warehouses (id_admin_sub_warehouse, id_office_sub_warehouse, name_sub_warehouse, status_sub_warehouse, date_created_sub_warehouse) VALUES (0, :office, :name, 1, CURDATE())");
			$stmtIns->execute([':office' => $id_office_dest, ':name' => $subName]);
			$id_sub = $db->lastInsertId();
		} else {
			$id_sub = $sub['id_sub_warehouse'];
		}

		// Insert warehouse_assignment record with type 'despacho'
		$stmtAssign = $db->prepare("
			INSERT INTO warehouse_assignments (id_sub_warehouse_assignment, id_product_assignment, qty_assignment, id_dispatched_by, type_assignment, notes_assignment, date_created_assignment)
			VALUES (:id_sub, :id_prod, :qty, :disp, 'despacho', :notes, NOW())
		");
		$stmtAssign->execute([
			':id_sub' => $id_sub,
			':id_prod' => $id_product,
			':qty' => $qty,
			':disp' => $id_dispatched_by,
			':notes' => $notes
		]);

		// Subtract from source office stock (ya validado arriba)
		$stmtDecrease = $db->prepare("
			UPDATE product_inventory
			SET stock_inventory = stock_inventory - :qty
			WHERE id_product_inventory = :prod AND id_office_inventory = :office AND status_inventory = 1
		");
		$stmtDecrease->execute([':qty' => $qty, ':prod' => $id_product, ':office' => $id_office_source]);

		// Add to destination office stock
		$stmtIncrease = $db->prepare("
			INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
			VALUES (:prod, :office, :qty, 1, CURDATE())
			ON DUPLICATE KEY UPDATE
				stock_inventory = stock_inventory + :qty
		");
		$stmtIncrease->execute([':qty' => $qty, ':prod' => $id_product, ':office' => $id_office_dest]);

		// Update products.stock_product
		$stmtUpdProd = $db->prepare("
			UPDATE products SET stock_product = (
				SELECT COALESCE(SUM(stock_inventory), 0) FROM product_inventory WHERE id_product_inventory = :prod
			) WHERE id_product = :prod
		");
		$stmtUpdProd->execute([':prod' => $id_product]);

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error: " . $e->getMessage();
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

		// Validar que la producci�n existe y est� pendiente de QC
		$stmtCheck = $db->prepare("SELECT id_production, id_packaged_product, status_production, real_unit_cost FROM productions WHERE id_production = :id AND id_office_production = :office");
		$stmtCheck->execute([':id' => $id_production, ':office' => $id_office]);
		$prod = $stmtCheck->fetch(PDO::FETCH_ASSOC);

		if (!$prod || $prod['status_production'] !== 'pendiente_qc') {
			echo 'error|La producci�n no est� en estado pendiente de QC.';
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

			$stmtStock = $db->prepare("UPDATE products SET stock_product = :stock, rte_product = :rte WHERE id_product = :id_product");
			$stmtStock->execute([':stock' => $new_stock, ':rte' => $new_rte, ':id_product' => $prod['id_packaged_product']]);

			$stmtInv = $db->prepare("
				INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
				VALUES (:prod, :office, :qty, 1, CURDATE())
				ON DUPLICATE KEY UPDATE stock_inventory = stock_inventory + :qty
			");
			$stmtInv->execute([
				':prod' => $prod['id_packaged_product'],
				':office' => $id_office,
				':qty' => $qty_approved
			]);
		}

		$db->commit();
		echo json_encode(['status' => 'ok', 'result' => $new_status]);
	} catch (Exception $e) {
		$db->rollBack();
		echo 'error|' . $e->getMessage();
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
			INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.status_inventory = 1
			WHERE p.status_product = 1
			HAVING stock > 0
			ORDER BY p.id_product DESC
		");
		$stmt->execute([':office' => $id_office]);
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
		
		// Find or create sub-warehouse for dest admin using destination office ID (compartido por oficina)
		$stmtCheck = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_office_sub_warehouse = :office LIMIT 1");
		$stmtCheck->execute([':office' => $dest_office_id]);
		$sub = $stmtCheck->fetch(PDO::FETCH_ASSOC);
		
		if (!$sub) {
			$subName = "Sub-Almac�n de la Sucursal";
			$stmtIns = $db->prepare("INSERT INTO sub_warehouses (id_admin_sub_warehouse, id_office_sub_warehouse, name_sub_warehouse, status_sub_warehouse, date_created_sub_warehouse) VALUES (0, :office, :name, 1, CURDATE())");
			$stmtIns->execute([':office' => $dest_office_id, ':name' => $subName]);
			$id_sub = $db->lastInsertId();
		} else {
			$id_sub = $sub['id_sub_warehouse'];
		}
		
		// Update request status
		$stmtUpd = $db->prepare("
			UPDATE inventory_requests
			SET status_request = 'despachada',
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
		
		// Insert assignment
		$stmtAssign = $db->prepare("
			INSERT INTO warehouse_assignments (id_sub_warehouse_assignment, id_product_assignment, qty_assignment, id_dispatched_by, id_request_assignment, type_assignment, notes_assignment, date_created_assignment)
			VALUES (:id_sub, :id_prod, :qty, :disp, :id_req, 'despacho', :notes, NOW())
		");
		$stmtAssign->execute([
			':id_sub' => $id_sub,
			':id_prod' => $id_product,
			':qty' => $qty_dispatch,
			':disp' => $id_dispatched_by,
			':id_req' => $id_request,
			':notes' => $notes_dispatcher
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
		if ($dispOffice > 0) {
			$stmtAvail = $db->prepare("SELECT stock_inventory FROM product_inventory WHERE id_product_inventory = :prod AND id_office_inventory = :office AND status_inventory = 1 LIMIT 1");
			$stmtAvail->execute([':prod' => $id_product, ':office' => $dispOffice]);
			$avail = (double)$stmtAvail->fetchColumn();
			if ($avail < (double)$qty_dispatch) {
				throw new Exception('Stock insuficiente en el almac�n de origen.');
			}
			$stmtDecrease = $db->prepare("
				UPDATE product_inventory
				SET stock_inventory = stock_inventory - :qty
				WHERE id_product_inventory = :prod AND id_office_inventory = :office AND status_inventory = 1
			");
			$stmtDecrease->execute([':qty' => $qty_dispatch, ':prod' => $id_product, ':office' => $dispOffice]);
		}

		// Incrementar en el inventario principal de la sucursal de destino
		if ($dest_office_id > 0) {
			$stmtIncrease = $db->prepare("
				INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
				VALUES (:prod, :office, :qty, 1, CURDATE())
				ON DUPLICATE KEY UPDATE
					stock_inventory = stock_inventory + :qty
			");
			$stmtIncrease->execute([':qty' => $qty_dispatch, ':prod' => $id_product, ':office' => $dest_office_id]);
		}

		// Update products.stock_product
		$stmtUpdProd = $db->prepare("
			UPDATE products SET stock_product = (
				SELECT COALESCE(SUM(stock_inventory), 0) FROM product_inventory WHERE id_product_inventory = :prod
			) WHERE id_product = :prod
		");
		$stmtUpdProd->execute([':prod' => $id_product]);
		
		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error: " . $e->getMessage();
	}
	exit;
}

/*=============================================
Editar Materia Prima
=============================================*/

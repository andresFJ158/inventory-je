<?php

if(isset($_POST["limit"])){

	$ajax = new PosController();
	$ajax -> limit = $_POST["limit"];
	$ajax -> startAt = $_POST["startAt"];
	$ajax -> category = $_POST["category"];
	$ajax -> search = $_POST["search"];
	$ajax -> idOffice = $_POST["idOffice"];
	$ajax -> loadProducts();

}

/*=============================================
Crear nueva orden
=============================================*/

if(isset($_POST["order"])){

	$ajax = new PosController();
	$ajax -> token = $_POST["token"] ?? null;
	$ajax -> seller = $_POST["seller"];
	$ajax -> idOffice = $_POST["idOffice"];
	$ajax -> newOrder();
}

/*=============================================
Actualizar orden
=============================================*/	

if(isset($_POST["idOrderUpdate"])){

	$ajax = new PosController();
	$ajax -> token = $_POST["token"];
	$ajax -> idOrder = $_POST["idOrderUpdate"];
	$ajax -> idClient = $_POST["idClient"];
	$ajax -> subtotalOrder = $_POST["subtotalOrder"];
	$ajax -> discountOrder = $_POST["discountOrder"];
	$ajax -> taxOrder = $_POST["taxOrder"];
	$ajax -> totalOrder = $_POST["totalOrder"];
	$ajax -> updateOrder();
}

/*=============================================
Agregar nuevo cliente
=============================================*/	

if(isset($_POST["name_client"])){

	$ajax = new PosController();
	$ajax -> name_client = $_POST["name_client"];
	$ajax -> surname_client = $_POST["surname_client"];
	$ajax -> dni_client = $_POST["dni_client"];
	$ajax -> email_client = $_POST["email_client"];
	$ajax -> phone_client = $_POST["phone_client"];
	$ajax -> address_client = $_POST["address_client"];
	$ajax -> idOffice = $_POST["idOffice"];
	$ajax -> token = $_POST["token"];
	$ajax -> newClient();
}

/*=============================================
Agregar producto a la lista de �rdenes
=============================================*/

if(isset($_POST["idProduct"])){

	$ajax = new PosController();
	$ajax -> idProduct = $_POST["idProduct"];
	$ajax -> idOrder = $_POST["idOrder"];
	$ajax -> idClient = $_POST["idClient"];
	$ajax -> seller = $_POST["seller"];
	$ajax -> idOffice = $_POST["idOffice"];
	$ajax -> token = $_POST["token"];
	$ajax -> addProductPos();

}


/*=============================================
Actualizar Cantidad
=============================================*/

if(isset($_POST["idSaleUpdate"])){

	$ajax = new PosController();
	$ajax -> idSaleUpdate = $_POST["idSaleUpdate"];
	$ajax -> qtySale = $_POST["qtySale"];
	$ajax -> subtotalSale = $_POST["subtotalSale"];
	$ajax -> token = $_POST["token"];
	$ajax -> updateSale();

}


/*=============================================
Remover Venta
=============================================*/

if(isset($_POST["idSaleDelete"])){

	$ajax = new PosController();
	$ajax -> idSaleDelete = $_POST["idSaleDelete"];
	$ajax -> token = $_POST["token"];
	$ajax -> deleteSale();

}

/*=============================================
Remover todas las Ventas
=============================================*/

if(isset($_POST["idOrderSale"])){
	$ajax = new PosController();
	$ajax -> idOrderSale = $_POST["idOrderSale"];
	$ajax -> token = $_POST["token"];
	$ajax -> deleteAllSale();

}

/*=============================================
Remover �rden
=============================================*/

if(isset($_POST["idOrderDelete"])){

	$ajax = new PosController();
	$ajax -> idOrderDelete = $_POST["idOrderDelete"];
	$ajax -> token = $_POST["token"] ?? null;

	if (empty($ajax->idOrderDelete)) {
		echo "error|ID de orden vacío";
	} else {
		$ajax -> deleteOrder();
	}

}

/*=============================================
Cancelar Orden a Crédito
=============================================*/

if(isset($_POST["cancelCreditOrder"])){

	$ajax = new PosController();
	$ajax -> idOrderCancel = $_POST["cancelCreditOrder"];
	$ajax -> token = $_POST["token"] ?? null;

	if (empty($ajax->idOrderCancel)) {
		echo "error|ID de orden vacío";
	} else {
		$ajax -> cancelCreditOrder();
	}

}

/*=============================================
Cancelar Orden a Crédito Parcial
=============================================*/
if(isset($_POST["partialCancelCreditOrder"])){
	$ajax = new PosController();
	$ajax -> idOrderCancel = $_POST["partialCancelCreditOrder"];
	$ajax -> partialReturns = $_POST["returns"] ?? '';
	$ajax -> token = $_POST["token"] ?? null;

	if (empty($ajax->idOrderCancel)) {
		echo json_encode(['status' => 400, 'message' => 'ID de orden vacío']);
	} else {
		$ajax -> partialCancelCreditOrder();
	}
}

/*=============================================
Aprobar Entrada de Materia Prima
=============================================*/
if(isset($_POST["requestWarehouseApproval"])){
	try {
		$db = LocalConnection::connect();
		$idOrder = $_POST["idOrder"];
		
		$stmtUpdate = $db->prepare("UPDATE orders SET status_order = 'Pendiente Aprobacion Almacen' WHERE id_order = :id");
		$stmtUpdate->execute([':id' => $idOrder]);
		
		echo "ok";
	} catch (Throwable $e) {
		echo "error";
	}
	exit;
}

/*=============================================
Ventas pendientes de aprobar (Almacén)
=============================================*/
if(isset($_POST["getPendingSalesToApprove"])){
	try {
		$db = LocalConnection::connect();
		$id_office = intval($_POST["id_office"] ?? 0);
		
		$stmt = $db->prepare("
			SELECT o.id_order, o.transaction_order, o.date_created_order, o.total_order, o.status_order,
			       a.name_admin as vendedor_name,
			       (SELECT SUM(qty_sale) FROM sales WHERE id_order_sale = o.id_order) as total_items,
			       (
			           SELECT JSON_ARRAYAGG(
			               JSON_OBJECT(
			                   'id_product', p.id_product,
			                   'title_product', p.title_product,
			                   'qty_sale', s.qty_sale,
			                   'subtotal_sale', s.subtotal_sale
			               )
			           )
			           FROM sales s
			           JOIN products p ON p.id_product = s.id_product_sale
			           WHERE s.id_order_sale = o.id_order
			       ) as cart_items
			FROM orders o
			LEFT JOIN admins a ON a.id_admin = o.id_admin_order
			WHERE o.status_order = 'Pendiente Aprobacion Almacen' 
			  AND (
			      o.id_office_order = :office 
			      OR a.id_warehouse_admin IN (SELECT id_warehouse FROM warehouses WHERE id_office_warehouse = :office)
			  )
			ORDER BY o.id_order ASC
		");
		$stmt->execute([':office' => $id_office]);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($results as &$row) {
			$row['cart_items'] = json_decode($row['cart_items'] ?? '[]');
		}
		echo json_encode(["status" => 200, "results" => $results]);
	} catch (Throwable $e) {
		echo json_encode(["status" => 500, "message" => $e->getMessage()]);
	}
	exit;
}

if(isset($_POST["approveSaleOrder"])){
	try {
		$db = LocalConnection::connect();
		$id_order = intval($_POST["id_order"]);
		
		// Aprobar la venta significa que pasa a Pendiente de Pago
		$stmtUpdate = $db->prepare("UPDATE orders SET status_order = 'Pendiente de Pago' WHERE id_order = :id");
		$stmtUpdate->execute([':id' => $id_order]);
		
		echo "ok";
	} catch (Throwable $e) {
		echo "error";
	}
	exit;
}

if(isset($_POST["denySaleOrder"])){
	try {
		$db = LocalConnection::connect();
		$id_order = intval($_POST["id_order"]);
		
		$db->beginTransaction();
		
		// Cancelar la orden
		$stmtUpdate = $db->prepare("UPDATE orders SET status_order = 'Cancelada' WHERE id_order = :id");
		$stmtUpdate->execute([':id' => $id_order]);
		
		// Eliminar las ventas pendientes para que no queden colgadas
		$stmtSales = $db->prepare("DELETE FROM sales WHERE id_order_sale = :id AND status_sale = 'Pendiente'");
		$stmtSales->execute([':id' => $id_order]);
		
		$db->commit();
		echo "ok";
	} catch (Throwable $e) {
		if($db->inTransaction()) $db->rollBack();
		echo "error";
	}
	exit;
}

if(isset($_POST["getPosPendingOrders"])){
	try {
		$db = LocalConnection::connect();
		$id_office = intval($_POST["id_office"] ?? 0);
		$id_admin = intval($_POST["id_admin"] ?? 0);
		
		$stmt = $db->prepare("
			SELECT *
			FROM orders
			WHERE id_office_order = :office 
			  AND id_admin_order = :admin
			  AND status_order IN ('Pendiente', 'Pendiente de Pago')
			ORDER BY id_order DESC
		");
		$stmt->execute([':office' => $id_office, ':admin' => $id_admin]);
		echo json_encode(["status" => 200, "results" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
	} catch (Throwable $e) {
		echo json_encode(["status" => 500, "message" => $e->getMessage()]);
	}
	exit;
}

/*=============================================
Aprobar Entrada de Materia Prima
=============================================*/
if(isset($_POST["approveRawMaterialEntry"])){
	$db = LocalConnection::connect();
	$id_admin_req = intval($_POST['id_admin'] ?? 0);
	if ($id_admin_req > 0) {
		$stmtAdmin = $db->prepare("SELECT rol_admin, permissions_admin FROM admins WHERE id_admin = :id LIMIT 1");
		$stmtAdmin->execute([':id' => $id_admin_req]);
		$adminInfo = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
		if ($adminInfo) {
			$rolReq = $adminInfo['rol_admin'];
			$permsReq = json_decode(urldecode($adminInfo['permissions_admin'] ?? '{}'), true);
			$isAllowed = in_array($rolReq, ["superadmin", "admin", "lab_admin"]) || (isset($permsReq['aprobar_entradas']) && $permsReq['aprobar_entradas'] === 'on');
			if (!$isAllowed) {
				echo "error|No tiene permisos para aprobar o costear entradas.";
				exit;
			}
		} else {
			echo "error|Usuario no encontrado.";
			exit;
		}
	}
	try {
		$db->beginTransaction();
		
		$id_entry = $_POST['id_entry'];
		$id_raw_material = $_POST['id_raw_material'];
		$qty = (float)$_POST['qty'];
		$price = (float)$_POST['price'];
		$total = (float)$_POST['total'];
		$id_admin = $_POST['id_admin'];

		if (strpos($id_raw_material, 'ls_') === 0) {
			$real_supply_id = intval(str_replace('ls_', '', $id_raw_material));
			$real_entry_id = intval(str_replace('ls_', '', $id_entry));
			
			$stmtCheck = $db->prepare("SELECT status_entry FROM lab_supply_entries WHERE id_ls_entry = :id");
			$stmtCheck->execute([':id' => $real_entry_id]);
			if($stmtCheck->fetchColumn() === 'aprobado') {
				echo "error|La entrada ya fue aprobada.";
				$db->rollBack();
				exit;
			}

			$stmtEntry = $db->prepare("UPDATE lab_supply_entries SET unit_price_entry = :price, total_cost_entry = :total, status_entry = 'aprobado', id_approved_by_entry = :admin, date_approved_entry = CURRENT_DATE() WHERE id_ls_entry = :id");
			$stmtEntry->execute([':price' => $price, ':total' => $total, ':admin' => $id_admin, ':id' => $real_entry_id]);

			$stmtStock = $db->prepare("UPDATE lab_supplies SET stock_supply = stock_supply + :qty, price_supply = GREATEST(COALESCE(price_supply, 0), :price) WHERE id_supply = :id_raw");
			$stmtStock->execute([':qty' => $qty, ':price' => $price, ':id_raw' => $real_supply_id]);

			if($db->inTransaction()) {
				$db->commit();
			}
			echo "ok";
			exit;
		}

		// Check status first to prevent double-approval
		$stmtCheck = $db->prepare("SELECT status_entry FROM raw_material_entries WHERE id_entry = :id");
		$stmtCheck->execute([':id' => $id_entry]);
		if($stmtCheck->fetchColumn() === 'aprobado') {
			echo "error|La entrada ya fue aprobada.";
			$db->rollBack();
			exit;
		}

		// Update entry
		$stmtEntry = $db->prepare("UPDATE raw_material_entries SET unit_price_entry = :price, total_cost_entry = :total, status_entry = 'aprobado', id_approved_by_entry = :admin, date_approved_entry = CURRENT_DATE() WHERE id_entry = :id");
		$stmtEntry->execute([':price' => $price, ':total' => $total, ':admin' => $id_admin, ':id' => $id_entry]);

		// Update stock and price (keeping the highest historical price)
		$stmtStock = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material + :qty, price_raw_material = GREATEST(COALESCE(price_raw_material, 0), :price) WHERE id_raw_material = :id_raw");
		$stmtStock->execute([':qty' => $qty, ':price' => $price, ':id_raw' => $id_raw_material]);

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		if($db->inTransaction()) $db->rollBack();
		error_log("pos_sales error: " . $e->getMessage()); echo "error|Error al procesar la operación.";
	}
	exit;
}

/*=============================================
Alternar Precio Mayorista en el Carrito
=============================================*/

if(isset($_POST["toggleWholesaleCart"])){

	$ajax = new PosController();
	$ajax -> idOrder = $_POST["idOrder"];
	$ajax -> isWholesale = $_POST["isWholesale"];
	$ajax -> token = $_POST["token"];
	$ajax -> toggleCartWholesale();

}

/*=============================================
Proxy API Gen�rico
=============================================*/
if(isset($_POST["apiProxy"])){
	$url = $_POST["url"];
	$method = $_POST["method"];
	$fields = json_decode($_POST["fields"], true);
	
	// SEC-01: Whitelist de endpoints
	$allowed_endpoints = ['raw_materials', 'raw_material_entries', 'recipes', 'productions', 'warehouse', 'quality_checks', 'qc_checks', 'raw_material_purchases'];
	$endpoint = explode('?', $url)[0];
	if(!in_array($endpoint, $allowed_endpoints)) {
		echo json_encode(["status" => 403, "results" => "Endpoint no permitido: " . $endpoint]);
		exit;
	}
	
	// Si fields es un array v�lido, lo convertimos a query string
	// para que cURL lo env�e como application/x-www-form-urlencoded
	// en lugar de multipart/form-data
	if (is_array($fields)) {
		$fields = http_build_query($fields);
	} else if (!empty($_POST["fields"])) {
		$fields = $_POST["fields"];
	}
	
	$res = CurlController::request($url, $method, $fields);
	echo json_encode($res);
	exit; // Importante para evitar que se imprima basura al final
}

/*=============================================
Guardar Receta
=============================================*/

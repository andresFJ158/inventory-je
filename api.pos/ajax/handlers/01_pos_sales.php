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
	$ajax -> token = $_POST["token"];
	$ajax -> deleteOrder();

}

/*=============================================
Aprobar Entrada de Materia Prima
=============================================*/
if(isset($_POST["approveRawMaterialEntry"])){
	$db = LocalConnection::connect();
	$id_admin_req = intval($_POST['id_admin'] ?? 0);
	if ($id_admin_req > 0) {
		$stmtRole = $db->prepare("SELECT rol_admin FROM admins WHERE id_admin = :id LIMIT 1");
		$stmtRole->execute([':id' => $id_admin_req]);
		$rolReq = $stmtRole->fetchColumn();
		if (!in_array($rolReq, ["superadmin", "admin", "lab_admin"])) {
			echo "error|No tiene permisos para aprobar o costear entradas.";
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

		// Update stock
		$stmtStock = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material + :qty WHERE id_raw_material = :id_raw");
		$stmtStock->execute([':qty' => $qty, ':id_raw' => $id_raw_material]);

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error|" . $e->getMessage();
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

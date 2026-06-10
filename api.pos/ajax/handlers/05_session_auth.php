<?php
if(isset($_POST["getLoggedUser"])){
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	if (isset($_SESSION["admin"])) {
		$db = LocalConnection::connect();
		$id_office = intval($_SESSION["admin"]->id_office_admin);
		if ($id_office === 0 && isset($_SESSION["admin"]->id_warehouse_admin) && intval($_SESSION["admin"]->id_warehouse_admin) > 0) {
			$stmtWH = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh");
			$stmtWH->execute([':wh' => intval($_SESSION["admin"]->id_warehouse_admin)]);
			$whOffice = $stmtWH->fetchColumn();
			if ($whOffice) {
				$id_office = intval($whOffice);
			}
		}
		
		$stmt = $db->prepare("SELECT * FROM offices WHERE id_office = :id");
		$stmt->execute([':id' => $id_office]);
		$office = $stmt->fetch(PDO::FETCH_ASSOC);

		$permissions = array();
		if (!empty($_SESSION["admin"]->permissions_admin)) {
			$permissions = json_decode(urldecode($_SESSION["admin"]->permissions_admin), true);
		}

		echo json_encode([
			'status' => 200,
			'user' => [
				'id_admin' => intval($_SESSION["admin"]->id_admin),
				'name_admin' => $_SESSION["admin"]->name_admin,
				'rol_admin' => $_SESSION["admin"]->rol_admin,
				'id_office_admin' => intval($_SESSION["admin"]->id_office_admin),
				'id_warehouse_admin' => isset($_SESSION["admin"]->id_warehouse_admin) ? intval($_SESSION["admin"]->id_warehouse_admin) : 0,
				'permissions_admin' => $permissions
			],
			'office' => $office,
			'token' => $_SESSION["admin"]->token_admin
		]);
	} else {
		echo json_encode([
			'status' => 401,
			'message' => 'No session active'
		]);
	}
	exit;
}

//=====================================
// PAY POS ORDER
//=====================================
if(isset($_POST["payPosOrder"])){
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	
	$_POST["idOrderPay"] = $_POST["idOrder"];
	$_POST["methodPay"] = $_POST["method"];
	$_POST["transferPay"] = $_POST["transfer"] ?? "";
	$_POST["clientInvoice"] = $_POST["invoice"] ?? "no";
	
	pos_require_session();
	
	require_once __DIR__ . '/../../controllers/orders.controller.php';
	
	ob_start();
	$orderCtrl = new OrdersController();
	$orderCtrl->manageOrder();
	$output = ob_get_clean();
	
	if (strpos($output, "La �rden") !== false || strpos($output, "�xito") !== false || strpos($output, "completada") !== false || strpos($output, "Correcto") !== false) {
		echo json_encode(["status" => 200, "message" => "ok"]);
	} else {
		$errorMsg = "Error al procesar el pago";
		if (preg_match('/alert-danger[^>]*>([\s\S]*?)<\/div>/i', $output, $matches)) {
			$errorMsg = trim(strip_tags($matches[1]));
		}
		echo json_encode(["status" => 400, "message" => $errorMsg]);
	}
	exit;
}

//=====================================
// LOGOUT LAB USER
//=====================================
if(isset($_POST["logoutLabUser"])){
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	session_destroy();
	echo "ok";
	exit;
}

//=====================================
// LOGIN LAB USER
//=====================================
if(isset($_POST["loginLabUser"])){
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	
	$email = $_POST["email"] ?? '';
	$password = $_POST["password"] ?? '';
	
	if (empty($email) || empty($password)) {
		echo json_encode([
			'status' => 400,
			'message' => 'El correo y la contrase�a son requeridos'
		]);
		exit;
	}

	if (!pos_rate_limit_login($email)) {
		echo json_encode([
			'status' => 429,
			'message' => 'Demasiados intentos. Espere 15 minutos.'
		]);
		exit;
	}
	
	$db = LocalConnection::connect();
	$stmt = $db->prepare("SELECT * FROM admins WHERE email_admin = :email AND status_admin = 1");
	$stmt->execute([':email' => $email]);
	$admin = $stmt->fetch(PDO::FETCH_OBJ);
	
	if ($admin) {
		require_once __DIR__ . '/../../models/connection.php';
		if (Connection::verifyPassword($password, $admin->password_admin)) {
			pos_rate_limit_clear($email);
			// Establecemos la sesi�n PHP como el login original
			$_SESSION["admin"] = $admin;
			
			$id_office = intval($admin->id_office_admin);
			if ($id_office === 0 && isset($admin->id_warehouse_admin) && intval($admin->id_warehouse_admin) > 0) {
				$stmtWH = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh");
				$stmtWH->execute([':wh' => intval($admin->id_warehouse_admin)]);
				$whOffice = $stmtWH->fetchColumn();
				if ($whOffice) {
					$id_office = intval($whOffice);
				}
			}
			
			$stmtOffice = $db->prepare("SELECT * FROM offices WHERE id_office = :id");
			$stmtOffice->execute([':id' => $id_office]);
			$office = $stmtOffice->fetch(PDO::FETCH_ASSOC);

			$permissions = array();
			if (!empty($admin->permissions_admin)) {
				$permissions = json_decode(urldecode($admin->permissions_admin), true);
			}

			// Generate new token
			require_once __DIR__ . '/../../models/connection.php';
			require_once __DIR__ . '/../../models/put.model.php';
			require_once __DIR__ . '/../../vendor/autoload.php';
			$tokenData = Connection::jwt($admin->id_admin, $admin->email_admin);
			$jwt = \Firebase\JWT\JWT::encode($tokenData, Connection::jwtSecret());
			
			$updateData = array(
				"token_admin" => $jwt,
				"token_exp_admin" => $tokenData["exp"]
			);
			PutModel::putData("admins", $updateData, $admin->id_admin, "id_admin");
			
			// Update admin session
			$admin->token_admin = $jwt;
			$admin->token_exp_admin = $tokenData["exp"];
			$_SESSION["admin"] = $admin;

			echo json_encode([
				'status' => 200,
				'user' => [
					'id_admin' => intval($admin->id_admin),
					'name_admin' => $admin->name_admin,
					'rol_admin' => $admin->rol_admin,
					'id_office_admin' => intval($admin->id_office_admin),
					'id_warehouse_admin' => isset($admin->id_warehouse_admin) ? intval($admin->id_warehouse_admin) : 0,
					'permissions_admin' => $permissions
				],
				'office' => $office,
				'token' => $jwt
			]);
			exit;
		}
	}
	
	echo json_encode([
		'status' => 401,
		'message' => 'Correo o contrase�a incorrectos, o el usuario est� inactivo.'
	]);
	exit;
}


//=====================================
// SAVE PRODUCTION (Iniciar Producci�n)
//=====================================
if(isset($_POST["saveProduction"])){
	$db = LocalConnection::connect();
	try {
		$id_recipe   = intval($_POST['id_recipe']);
		$id_product  = intval($_POST['id_product']);
		$batches     = floatval($_POST['batches']);
		$total_qty   = floatval($_POST['total_qty']);
		$cif         = floatval($_POST['cif']);
		$mo          = floatval($_POST['mo']);
		$id_office   = intval($_POST['id_office']);
		$id_admin    = intval($_POST['id_admin']);

		// 1. Fetch recipe ingredients
		$stmtIng = $db->prepare("
			SELECT ri.id_raw_material_ingredient AS id_rm, ri.qty_ingredient,
			       rm.stock_raw_material, rm.name_raw_material
			FROM recipe_ingredients ri
			JOIN raw_materials rm ON ri.id_raw_material_ingredient = rm.id_raw_material
			WHERE ri.id_recipe_ingredient = :id_recipe
		");
		$stmtIng->execute([':id_recipe' => $id_recipe]);
		$ingredients = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

		// 2. Check stock for each ingredient
		$totalMatCost = 0;
		foreach ($ingredients as $ing) {
			$needed = $ing['qty_ingredient'] * $batches;
			if ($ing['stock_raw_material'] < $needed - 0.001) {
				echo "stock_insuficiente|" . $ing['name_raw_material'];
				exit;
			}
		}

		// 3. Insert production record
		$projTotal = $totalMatCost + $mo + $cif;
		$projUnit  = ($total_qty > 0) ? ($projTotal / $total_qty) : 0;

		$stmtProd = $db->prepare("
			INSERT INTO productions (id_recipe_production, id_product_production, batches_production,
			total_qty_production, proj_labor_cost, proj_indirect_cost, proj_total_cost, proj_unit_cost,
			status_production, start_date_production, id_admin_production, id_office_production,
			date_created_production)
			VALUES (:recipe, :product, :batches, :qty, :mo, :cif, :total, :unit, 'proceso',
			CURDATE(), :admin, :office, CURDATE())
		");
		$stmtProd->execute([
			':recipe'  => $id_recipe,
			':product' => $id_product,
			':batches' => $batches,
			':qty'     => $total_qty,
			':mo'      => $mo,
			':cif'     => $cif,
			':total'   => $mo + $cif,
			':unit'    => 0,
			':admin'   => $id_admin,
			':office'  => $id_office
		]);
		$id_production = $db->lastInsertId();

		// 4. Consume raw materials from stock and record costs
		foreach ($ingredients as $ing) {
			$needed = $ing['qty_ingredient'] * $batches;

			// Get latest entry price
			$stmtPrice = $db->prepare("
				SELECT unit_price_entry FROM raw_material_entries
				WHERE id_raw_material_entry = :id_rm AND status_entry = 'aprobado'
				ORDER BY date_approved_entry DESC, id_entry DESC LIMIT 1
			");
			$stmtPrice->execute([':id_rm' => $ing['id_rm']]);
			$unitPrice = floatval($stmtPrice->fetchColumn() ?: 0);
			$subtotal  = $unitPrice * $needed;
			$totalMatCost += $subtotal;

			// Deduct from stock
			$stmtDeduct = $db->prepare("
				UPDATE raw_materials SET stock_raw_material = stock_raw_material - :qty
				WHERE id_raw_material = :id_rm
			");
			$stmtDeduct->execute([':qty' => $needed, ':id_rm' => $ing['id_rm']]);

			// Get entry id for traceability
			$stmtEntry = $db->prepare("
				SELECT id_entry FROM raw_material_entries
				WHERE id_raw_material_entry = :id_rm AND status_entry = 'aprobado'
				ORDER BY date_approved_entry DESC, id_entry DESC LIMIT 1
			");
			$stmtEntry->execute([':id_rm' => $ing['id_rm']]);
			$id_entry = intval($stmtEntry->fetchColumn() ?: 0);

			// Insert material cost record
			$stmtMat = $db->prepare("
				INSERT INTO production_material_costs
				(id_production_mat_cost, id_raw_material_mat_cost, id_entry_used_mat_cost,
				 qty_used_mat_cost, unit_price_at_production, total_cost_mat_cost)
				VALUES (:prod, :rm, :entry, :qty, :price, :total)
			");
			$stmtMat->execute([
				':prod'  => $id_production,
				':rm'    => $ing['id_rm'],
				':entry' => $id_entry,
				':qty'   => $needed,
				':price' => $unitPrice,
				':total' => $subtotal
			]);
		}

		// 5. Update production with real material cost
		$totalCost = $totalMatCost + $mo + $cif;
		$unitCost  = ($total_qty > 0) ? ($totalCost / $total_qty) : 0;
		$stmtUpdate = $db->prepare("
			UPDATE productions
			SET real_materials_cost = :mat, real_labor_cost = :mo, real_indirect_cost = :cif,
			    real_total_cost = :total, real_unit_cost = :unit,
			    proj_materials_cost = :mat, proj_total_cost = :total, proj_unit_cost = :unit
			WHERE id_production = :id
		");
		$stmtUpdate->execute([
			':mat'   => $totalMatCost,
			':mo'    => $mo,
			':cif'   => $cif,
			':total' => $totalCost,
			':unit'  => $unitCost,
			':id'    => $id_production
		]);

		echo "ok";
	} catch (Exception $e) {
		echo "error|" . $e->getMessage();
	}
	exit;
}

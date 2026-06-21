<?php

require_once "models/connection.php";
require_once "controllers/put.controller.php";

if(isset($_GET["id"]) && isset($_GET["nameId"])){

	/*=============================================
	Capturamos los datos del formulario
	=============================================*/

	$data = array();
	
	parse_str(file_get_contents('php://input'), $data);

	foreach ($data as $key => $value) {
		if (strpos($key, 'password_') === 0) {
			if (empty($value)) {
				unset($data[$key]);
			} else if (strpos($value, '$2y$') !== 0 && strpos($value, '$2a$') !== 0) {
				$data[$key] = Connection::hashPassword($value);
			}
		}
	}
		
	/*=============================================
	Separar propiedades en un arreglo
	=============================================*/

	$columns = array();
		
	foreach (array_keys($data) as $key => $value) {

		array_push($columns, $value);
		
	}

	array_push($columns, $_GET["nameId"]);

	$columns = array_unique($columns);

	/*=============================================
	Validar la tabla y las columnas
	=============================================*/

	if(empty(Connection::getColumnsData($table, $columns))){

		$json = array(
		 	'status' => 400,
		 	'results' => "Error: Fields in the form do not match the database"
		);

		echo json_encode($json, http_response_code($json["status"]));

		return;

	}

	if(isset($_GET["token"])){

		$tableToken = $_GET["table"] ?? "users";
		$suffix = $_GET["suffix"] ?? "user";
		$validate = Connection::tokenValidate($_GET["token"],$tableToken,$suffix);

		if($validate == "ok"){

			// Identificar al actor para la bitácora de auditoría
			require_once "models/audit.model.php";
			AuditLogger::setActorFromToken($tableToken, $suffix, $_GET["token"]);

			if($table === "cashs"){
				require_once "models/get.model.php";
				$userCheck = GetModel::getDataFilter($tableToken, "rol_admin", "token_".$suffix, $_GET["token"], null,null,null,null);
				if(empty($userCheck) || ($userCheck[0]->rol_admin !== 'superadmin' && $userCheck[0]->rol_admin !== 'admin')){
					http_response_code(403);
					echo json_encode(['status' => 403, 'results' => "Error: User role not authorized to edit cash registers"]);
					return;
				}
			}

			if ($table === "purchases") {
				require_once __DIR__ . "/purchase.guard.php";
				$actor = purchase_guard_actor($tableToken, $suffix, $_GET["token"]);
				$guard = purchase_guard_validate($data, $actor['role'], (int)$_GET["id"]);
				if (!$guard['ok']) {
					http_response_code($guard['status']);
					echo json_encode(['status' => $guard['status'], 'results' => $guard['message']]);
					return;
				}
			}

			// Prevent unauthorized reassignment of id_admin_client on clients
			if ($table === 'clients') {
				require_once "models/get.model.php";
				$adminRows = GetModel::getDataFilter($tableToken, "rol_admin,permissions_admin,id_admin", "token_".$suffix, $_GET["token"], null, null, null, null);
				$adminRow  = !empty($adminRows) ? $adminRows[0] : null;
				$role      = $adminRow->rol_admin ?? '';
				$idAdmin   = intval($adminRow->id_admin ?? 0);
				$perms     = json_decode(urldecode($adminRow->permissions_admin ?? '{}'), true);
				
				if (array_key_exists('id_admin_client', $data)) {
					$canManage = in_array($role, ['superadmin', 'admin'])
						|| ($role === 'vendedor' && ($perms['gestionar_clientes'] ?? '') === 'on');
					if (!$canManage) {
						unset($data['id_admin_client']);
					}
				}

				if ($role === 'vendedor' || $role === 'cajero') {
					$db = Connection::connect();
					$stmtC = $db->prepare("SELECT id_admin_client FROM clients WHERE id_client = :id LIMIT 1");
					$stmtC->execute([':id' => intval($_GET["id"])]);
					$clientOwner = intval($stmtC->fetchColumn());

					if ($role === 'vendedor') {
						if ($clientOwner !== $idAdmin) {
							http_response_code(403);
							echo json_encode(['status' => 403, 'results' => 'No tiene permiso para editar este cliente']);
							return;
						}
					} else {
						// Cajero: can only edit cashier clients or legacy ones (id_admin_client = 0)
						$stmtRole = $db->prepare("SELECT rol_admin FROM admins WHERE id_admin = :id LIMIT 1");
						$stmtRole->execute([':id' => $clientOwner]);
						$ownerRole = $stmtRole->fetchColumn();

						if ($clientOwner !== 0 && $ownerRole !== 'cajero') {
							http_response_code(403);
							echo json_encode(['status' => 403, 'results' => 'No tiene permiso para editar este cliente']);
							return;
						}
					}
				}
			}

			if ($table === 'bills') {
				require_once "models/get.model.php";
				$adminRows = GetModel::getDataFilter($tableToken, "rol_admin,id_admin", "token_".$suffix, $_GET["token"], null, null, null, null);
				$adminRow  = !empty($adminRows) ? $adminRows[0] : null;
				$role      = $adminRow->rol_admin ?? '';
				$idAdmin   = intval($adminRow->id_admin ?? 0);

				if ($role === 'vendedor' || $role === 'cajero') {
					$db = Connection::connect();
					$stmtB = $db->prepare("SELECT id_admin_bill FROM bills WHERE id_bill = :id LIMIT 1");
					$stmtB->execute([':id' => intval($_GET["id"])]);
					$billOwner = intval($stmtB->fetchColumn());

					if ($role === 'vendedor') {
						if ($billOwner !== $idAdmin) {
							http_response_code(403);
							echo json_encode(['status' => 403, 'results' => 'No tiene permiso para editar este gasto']);
							return;
						}
						$data['id_cash_bill'] = 0;
					} else {
						$stmtRole = $db->prepare("SELECT rol_admin FROM admins WHERE id_admin = :id LIMIT 1");
						$stmtRole->execute([':id' => $billOwner]);
						$ownerRole = $stmtRole->fetchColumn();

						if ($billOwner !== 0 && $ownerRole === 'vendedor') {
							http_response_code(403);
							echo json_encode(['status' => 403, 'results' => 'No tiene permiso para editar este gasto']);
							return;
						}
					}
				}
			}

			// Force lab staff to laboratory warehouse 0
			if ($table === 'admins') {
				if (isset($data['rol_admin']) && in_array($data['rol_admin'], ['lab_admin', 'despachador_laboratorio'])) {
					$data['id_office_admin'] = 0;
				}
			}

			$response = new PutController();
			$response -> putData($table,$data,$_GET["id"],$_GET["nameId"]);
			return;
		}

		if($validate == "expired"){
			http_response_code(303);
			echo json_encode(['status' => 303, 'results' => "Error: The token has expired"]);
			return;
		}

		http_response_code(400);
		echo json_encode(['status' => 400, 'results' => "Error: The user is not authorized"]);
		return;

	}

	http_response_code(400);
	echo json_encode(['status' => 400, 'results' => "Error: Authorization required"]);
	return;


}

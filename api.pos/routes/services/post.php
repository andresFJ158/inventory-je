<?php

require_once "models/connection.php";
require_once "controllers/post.controller.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

if(isset($_POST)){

	$rawInput = file_get_contents('php://input');
	file_put_contents(__DIR__ . '/../../post_debug.txt',
		date('Y-m-d H:i:s') . "\n" .
		"METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n" .
		"CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'none') . "\n" .
		"RAW INPUT: " . substr($rawInput, 0, 800) . "\n" .
		"POST ARRAY: " . json_encode($_POST) . "\n" .
		"---\n",
		FILE_APPEND
	);

	// If $_POST is empty but raw input has data (proxy may send body as raw), parse it
	if (count($_POST) === 0 && !empty($rawInput)) {
		parse_str($rawInput, $_POST);
	}

	foreach ($_POST as $key => $value) {
		if (strpos($key, 'password_') === 0 && !empty($value)) {
			if (strpos($value, '$2y$') !== 0 && strpos($value, '$2a$') !== 0) {
				$_POST[$key] = Connection::hashPassword($value);
			}
		}
	}

	/*=============================================
	Separar propiedades en un arreglo
	=============================================*/

	$columns = array();
	
	foreach (array_keys($_POST) as $key => $value) {

		array_push($columns, $value);
			
	}

	/*=============================================
	Validar la tabla y las columnas
	=============================================*/

	if(empty(Connection::getColumnsData($table, $columns))){

		$json = array(
		 	'status' => 400,
		 	'results' => "Error: Fields in the form do not match the database"
		);
		file_put_contents(__DIR__ . '/../../post_debug.txt', date('Y-m-d H:i:s') . " POST_ERROR: " . $json['results'] . "\nColumns sent: " . implode(', ', $columns) . "\n", FILE_APPEND);
		echo json_encode($json, http_response_code($json["status"]));

		return;

	}

	$response = new PostController();

	/*=============================================
	Peticion POST para registrar usuario
	=============================================*/	

	if(isset($_GET["register"]) && $_GET["register"] == true){

		$suffix = $_GET["suffix"] ?? "user";

		$response -> postRegister($table,$_POST,$suffix);

	/*=============================================
	Peticion POST para login de usuario
	=============================================*/	

	}else if(isset($_GET["login"]) && $_GET["login"] == true){

		$suffix = $_GET["suffix"] ?? "user";

		$response -> postLogin($table,$_POST,$suffix);

	}else{


		if(isset($_GET["token"])){

			$tableToken = $_GET["table"] ?? "users";
			$suffix = $_GET["suffix"] ?? "user";

			$validate = Connection::tokenValidate($_GET["token"],$tableToken,$suffix);

			if($validate == "ok"){

				// Identificar al actor para la bitácora de auditoría
				require_once "models/audit.model.php";
				AuditLogger::setActorFromToken($tableToken, $suffix, $_GET["token"]);

				// Compras: laboratorio/admin define compras y precios solo para producto externo.
				if ($table === 'purchases') {
					require_once __DIR__ . "/purchase.guard.php";
					$actor = purchase_guard_actor($tableToken, $suffix, $_GET["token"]);
					$guard = purchase_guard_validate($_POST, $actor['role']);
					if (!$guard['ok']) {
						http_response_code($guard['status']);
						header('Content-Type: application/json; charset=utf-8');
						echo json_encode(['status' => $guard['status'], 'results' => $guard['message']]);
						return;
					}

					if ($actor['role'] === 'lab_admin') {
						$_POST['status_purchase'] = 'recibido';
						$_POST['received_date_purchase'] = date('Y-m-d');
						$_POST['received_by_purchase'] = (string)$actor['id_admin'];
					}
				}

				// Role guard + deduplication for clients
				if ($table === 'clients') {
					$adminRows = GetModel::getDataFilter($tableToken, "rol_admin,permissions_admin,id_admin", "token_".$suffix, $_GET["token"], null, null, null, null);
					$adminRow  = !empty($adminRows) ? $adminRows[0] : null;
					$role      = $adminRow->rol_admin ?? '';
					$perms     = json_decode(urldecode($adminRow->permissions_admin ?? '{}'), true);
					$canManage = in_array($role, ['superadmin', 'admin', 'cajero'])
						|| ($role === 'vendedor' && ($perms['gestionar_clientes'] ?? '') === 'on');

					if (!$canManage) {
						http_response_code(403);
						echo json_encode(['status' => 403, 'results' => 'Sin permiso para crear clientes']);
						return;
					}

					// Store the creator's admin ID
					$_POST['id_admin_client'] = $adminRow->id_admin;


					$dni = trim($_POST['dni_client'] ?? '');
					$nit = trim($_POST['nit_client'] ?? '');
					if ($dni !== '' || $nit !== '') {
						$db  = Connection::connect();
						$dup = $db->prepare("SELECT id_client FROM clients WHERE (dni_client = ? AND dni_client <> '') OR (nit_client = ? AND nit_client <> '') LIMIT 1");
						$dup->execute([$dni, $nit]);
						if ($dup->fetchColumn()) {
							http_response_code(409);
							echo json_encode(['status' => 409, 'results' => 'El cliente ya existe con ese DNI o NIT']);
							return;
						}
					}
				}

				// Override creator and register ID for bills
				if ($table === 'bills') {
					$adminRows = GetModel::getDataFilter($tableToken, "rol_admin,id_admin", "token_".$suffix, $_GET["token"], null, null, null, null);
					$adminRow  = !empty($adminRows) ? $adminRows[0] : null;
					$role      = $adminRow->rol_admin ?? '';

					$_POST['id_admin_bill'] = $adminRow->id_admin;

					if ($role === 'vendedor') {
						$_POST['id_cash_bill'] = 0;
					}
				}

				// Force lab staff to laboratory warehouse 0
				if ($table === 'admins') {
					if (isset($_POST['rol_admin']) && in_array($_POST['rol_admin'], ['lab_admin', 'despachador_laboratorio'])) {
						$_POST['id_office_admin'] = 0;
					}
				}

				$response -> postData($table,$_POST);
				return;
			}

			if($validate == "expired"){
				http_response_code(303);
				echo json_encode([
					'status' => 303,
					'results' => "Error: The token has expired"
				]);
				return;
			}

			http_response_code(400);
			echo json_encode([
				'status' => 400,
				'results' => "Error: The user is not authorized"
			]);
			return;

		}

		http_response_code(400);
		echo json_encode([
			'status' => 400,
			'results' => "Error: Authorization required"
		]);
		return;

	}

}

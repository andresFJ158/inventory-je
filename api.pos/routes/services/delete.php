<?php

require_once "models/connection.php";
require_once "controllers/delete.controller.php";

if(isset($_GET["id"]) && isset($_GET["nameId"])){

	$columns = array($_GET["nameId"]);

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

	/*=============================================
	Peticion DELETE para usuarios autorizados
	=============================================*/

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
					echo json_encode(['status' => 403, 'results' => "Error: User role not authorized to delete cash registers"]);
					return;
				}
			}

			// Proteger almacén/sucursal central de ser eliminada
			if($table === "offices" || $table === "warehouses"){
				$db = Connection::connect();
				$nameIdCol = $_GET["nameId"];
				$idVal = intval($_GET["id"]);
				$stmtChk = $db->prepare("SELECT type_office FROM offices WHERE $nameIdCol = :id LIMIT 1");
				$stmtChk->execute([':id' => $idVal]);
				$offRow = $stmtChk->fetch(PDO::FETCH_OBJ);
				if($offRow && $offRow->type_office === 'central'){
					http_response_code(403);
					echo json_encode(['status' => 403, 'results' => "Error: El almacén central no puede eliminarse"]);
					return;
				}
			}

			$response = new DeleteController();
			$response -> deleteData($table,$_GET["id"],$_GET["nameId"]);
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


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


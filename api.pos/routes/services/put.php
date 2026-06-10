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
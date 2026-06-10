<?php

require_once "models/connection.php";
require_once "controllers/post.controller.php";

if(isset($_POST)){

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
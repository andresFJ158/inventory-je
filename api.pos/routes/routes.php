<?php

require_once "models/connection.php";
require_once "controllers/get.controller.php";

$routesArray = explode("/", $_SERVER['REQUEST_URI']);
$routesArray = array_values(array_filter($routesArray));
if (isset($routesArray[0]) && $routesArray[0] === 'api') {
	array_shift($routesArray);
}

/*=============================================
Cuando no se hace ninguna petición a la API
=============================================*/

if(count($routesArray) == 0){

	$json = array(

		'status' => 404,
		'results' => 'Not Found'

	);

	echo json_encode($json, http_response_code($json["status"]));

	return;

}

/*=============================================
Cuando si se hace una petición a la API
=============================================*/

if(count($routesArray) == 1 && isset($_SERVER['REQUEST_METHOD'])){

	$table = explode("?", $routesArray[0])[0];

	$headers = getallheaders();
	$authorization = null;
	foreach ($headers as $key => $value) {
		if (strtolower($key) === 'authorization') {
			$authorization = $value;
			break;
		}
	}

	if (!$authorization && isset($_SERVER['HTTP_AUTHORIZATION'])) {
		$authorization = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
		// O simplemente asignarlo si no usa Bearer:
		if (strpos($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ') === false) {
			$authorization = $_SERVER['HTTP_AUTHORIZATION'];
		}
	}

	// También aceptar token en URL query string (solo si se pasa explicitamente como apikey o token pero no JWT)
	if (!$authorization && isset($_GET['apikey'])) {
		$authorization = $_GET['apikey'];
	} else if (!$authorization && isset($_GET['token']) && strlen($_GET['token']) < 100) {
		// Only use token if it's not a JWT (JWTs are very long)
		$authorization = $_GET['token'];
	}

	// En desarrollo local, permitir sin autenticación si no hay header Authorization
	$isDevelopment = getenv('APP_DEBUG') === '1' || php_uname('s') === 'Darwin';
	if($authorization != Connection::apikey() && !$isDevelopment){

		if(in_array($table, Connection::publicAccess()) == 0){

			$errorMsg = "Mismatch authorization token: received '" . ($authorization ?? 'NULL') . "', expected '" . Connection::apikey() . "'";
			error_log("[AUTH_ERROR] " . $errorMsg);
			file_put_contents(__DIR__ . '/../../post_debug.txt', date('Y-m-d H:i:s') . " AUTH_ERROR: " . $errorMsg . "\n", FILE_APPEND);

			$json = array(
				'status' => 400,
				"results" => "You are not authorized to make this request"
			);

			echo json_encode($json, http_response_code($json["status"]));

			return;

		}else{

			/*=============================================
			Acceso público
			=============================================*/
			$response = new GetController();
			$response -> getData($table, "*",null,null,null,null);

			return;
		}

	}

	/*=============================================
	Peticiones GET
	=============================================*/

	if($_SERVER['REQUEST_METHOD'] == "GET"){

		include "services/get.php";

	}

	/*=============================================
	Peticiones POST
	=============================================*/

	if($_SERVER['REQUEST_METHOD'] == "POST"){

		include "services/post.php";

	}

	/*=============================================
	Peticiones PUT
	=============================================*/

	if($_SERVER['REQUEST_METHOD'] == "PUT"){

		include "services/put.php";

	}

	/*=============================================
	Peticiones DELETE
	=============================================*/

	if($_SERVER['REQUEST_METHOD'] == "DELETE"){

		include "services/delete.php";

	}

}



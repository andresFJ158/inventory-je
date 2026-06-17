<?php
require_once __DIR__ . '/load_env.php';


/*=============================================
Mostrar errores y Zona Horaria
=============================================*/

date_default_timezone_set('America/La_Paz');
define('DIR', __DIR__);

$appDebug = getenv('APP_DEBUG') === '1';
ini_set('display_errors', $appDebug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', DIR . '/php_error_log');

/*=============================================
CORS
=============================================*/

$corsOrigins = getenv('CORS_ALLOWED_ORIGINS');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($corsOrigins && $corsOrigins !== '*') {
	$allowed = array_map('trim', explode(',', $corsOrigins));
	if ($origin && in_array($origin, $allowed, true)) {
		header('Access-Control-Allow-Origin: ' . $origin);
		header('Vary: Origin');
	}
} else {
	header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('content-type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

/*=============================================
Requerimientos
=============================================*/

require_once 'controllers/routes.controller.php';

$index = new RoutesController();
$index->index();

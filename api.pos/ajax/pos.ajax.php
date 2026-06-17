<?php
require_once dirname(__DIR__) . '/load_env.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/php_error_log');

require_once dirname(__DIR__) . '/controllers/curl.controller.php';
require_once dirname(__DIR__) . '/controllers/template.controller.php';

date_default_timezone_set('America/La_Paz');

$POS_DEBUG = (getenv('APP_DEBUG') === '1');

set_exception_handler(function ($e) use ($POS_DEBUG) {
	error_log('EXCEPTION in pos.ajax.php: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
	try {
		$db = LocalConnection::connect();
		if ($db->inTransaction()) {
			$db->rollBack();
		}
	} catch (Throwable $ignore) {
	}
	http_response_code(500);
	echo json_encode([
		'status' => 500,
		'error'  => $POS_DEBUG ? $e->getMessage() : 'Error interno del servidor',
	]);
	exit;
});

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/LocalConnection.php';
require_once __DIR__ . '/lib/PosController.php';
require_once __DIR__ . '/pos_auth.php';

pos_enforce_ajax_auth();

require_once __DIR__ . '/handlers/_loader.php';

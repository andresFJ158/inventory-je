<?php

/** Respuesta JSON de éxito y termina la request. */
function pos_ok($payload = [], $message = null){
	$out = array_merge(["status" => 200], is_array($payload) ? $payload : ["data" => $payload]);
	if ($message !== null) { $out["message"] = $message; }
	echo json_encode($out);
	exit;
}

/** Respuesta JSON de error y termina la request. */
function pos_fail($status, $message){
	http_response_code($status);
	echo json_encode(["status" => $status, "message" => $message]);
	exit;
}

/** Entero validado desde input (con default). */
function pos_int($key, $default = 0){
	return isset($_POST[$key]) ? intval($_POST[$key]) : $default;
}

/** Monto monetario validado: float no negativo redondeado a 2 decimales. */
function pos_money($key, $default = 0.0){
	$v = isset($_POST[$key]) ? floatval($_POST[$key]) : $default;
	return $v < 0 ? 0.0 : round($v, 2);
}

/**
 * Ejecuta $fn dentro de una transacción atómica.
 * Hace commit si todo va bien; rollBack y relanza si hay excepción.
 * Reutiliza la transacción externa si ya hay una abierta (evita anidamiento).
 */
function pos_transaction(PDO $db, callable $fn){
	$owns = !$db->inTransaction();
	if ($owns) { $db->beginTransaction(); }
	try {
		$result = $fn($db);
		if ($owns) { $db->commit(); }
		return $result;
	} catch (Throwable $e) {
		if ($owns && $db->inTransaction()) { $db->rollBack(); }
		throw $e;
	}
}

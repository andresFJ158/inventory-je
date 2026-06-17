<?php

require_once "connection.php";
require_once "get.model.php";

/*=============================================
Registro de auditoría
---------------------------------------------
Deja una bitácora inmutable de cada escritura
(INSERT / UPDATE / DELETE) que pasa por los
modelos genéricos. Captura quién, qué, cuándo,
desde dónde y el antes/después del registro.

Regla de oro: la auditoría NUNCA debe romper la
operación principal. Todo va envuelto en try/catch
y cualquier fallo se degrada a error_log.
=============================================*/

class AuditLogger {

	// Actor de la petición actual (se fija una vez por request)
	private static $actor = array("id" => null, "email" => null, "role" => null);

	// Evita ejecutar CREATE TABLE más de una vez por proceso
	private static $tableReady = false;

	// Tablas que no se auditan (recursión o puro ruido de sistema)
	private static $skip = array("audit_logs", "schema_migrations", "_product_id_map");

	/*=============================================
	Enmascarar valores sensibles antes de persistir
	=============================================*/
	private static function redact($data){
		if(!is_array($data)){
			return $data;
		}
		$out = array();
		foreach($data as $k => $v){
			if(preg_match('/password|token|secret|api_?key/i', (string)$k)){
				$out[$k] = "***";
			}else if(is_array($v)){
				$out[$k] = self::redact($v);
			}else{
				$out[$k] = $v;
			}
		}
		return $out;
	}

	/*=============================================
	Fijar el actor de la petición
	=============================================*/
	public static function setActor($id, $email = null, $role = null){
		self::$actor = array(
			"id"    => ($id !== null && $id !== "") ? (int)$id : null,
			"email" => $email,
			"role"  => $role
		);
	}

	/*=============================================
	Resolver el actor a partir del token de sesión
	(tabla admins → id_admin, email_admin, rol_admin)
	=============================================*/
	public static function setActorFromToken($table, $suffix, $token){
		try{
			$select = "id_".$suffix.",email_".$suffix.",rol_".$suffix;
			$rows = GetModel::getDataFilter($table, $select, "token_".$suffix, $token, null, null, null, null);
			if(!empty($rows)){
				$r = $rows[0];
				self::setActor(
					$r->{"id_".$suffix}    ?? null,
					$r->{"email_".$suffix} ?? null,
					$r->{"rol_".$suffix}   ?? null
				);
			}
		}catch(Throwable $e){
			error_log("[AUDIT] setActorFromToken: ".$e->getMessage());
		}
	}

	/*=============================================
	Crear la tabla si no existe (idempotente, memoizado)
	=============================================*/
	private static function ensureTable($link){
		if(self::$tableReady){
			return;
		}
		try{
			$link->exec("CREATE TABLE IF NOT EXISTS audit_logs (
				id_audit          BIGINT AUTO_INCREMENT PRIMARY KEY,
				action_audit      VARCHAR(10) NOT NULL,
				table_audit       VARCHAR(64) NOT NULL,
				record_audit      VARCHAR(64) DEFAULT NULL,
				id_admin_audit    INT DEFAULT NULL,
				email_admin_audit VARCHAR(255) DEFAULT NULL,
				role_admin_audit  VARCHAR(50) DEFAULT NULL,
				changes_audit     LONGTEXT DEFAULT NULL,
				ip_audit          VARCHAR(45) DEFAULT NULL,
				endpoint_audit    VARCHAR(255) DEFAULT NULL,
				created_at_audit  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				KEY idx_audit_table (table_audit, record_audit),
				KEY idx_audit_admin (id_admin_audit),
				KEY idx_audit_date (created_at_audit)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
			self::$tableReady = true;
		}catch(Throwable $e){
			error_log("[AUDIT] ensureTable: ".$e->getMessage());
		}
	}

	/*=============================================
	Capturar el estado "antes" de un registro
	(para UPDATE / DELETE)
	=============================================*/
	public static function snapshot($table, $nameId, $id){
		try{
			$rows = GetModel::getDataFilter($table, "*", $nameId, $id, null, null, null, null);
			return !empty($rows) ? (array)$rows[0] : null;
		}catch(Throwable $e){
			return null;
		}
	}

	/*=============================================
	Registrar un evento de auditoría
	=============================================*/
	public static function log($action, $table, $recordId, $changes = null){

		if(in_array($table, self::$skip, true)){
			return;
		}

		try{
			$link = Connection::connect();
			self::ensureTable($link);

			$payload = self::redact($changes);
			$json = ($payload === null)
				? null
				: json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"] ?? ($_SERVER["REMOTE_ADDR"] ?? null);
			if($ip){
				$parts = explode(",", $ip);
				$ip = substr(trim($parts[0]), 0, 45);
			}

			$endpoint = null;
			if(isset($_SERVER["REQUEST_METHOD"]) && isset($_SERVER["REQUEST_URI"])){
				$endpoint = substr($_SERVER["REQUEST_METHOD"]." ".$_SERVER["REQUEST_URI"], 0, 255);
			}

			$stmt = $link->prepare("INSERT INTO audit_logs
				(action_audit, table_audit, record_audit, id_admin_audit, email_admin_audit, role_admin_audit, changes_audit, ip_audit, endpoint_audit)
				VALUES (:action, :tbl, :rec, :admin, :email, :role, :changes, :ip, :endpoint)");

			$stmt->execute(array(
				":action"   => substr((string)$action, 0, 10),
				":tbl"      => substr((string)$table, 0, 64),
				":rec"      => $recordId !== null ? substr((string)$recordId, 0, 64) : null,
				":admin"    => self::$actor["id"],
				":email"    => self::$actor["email"],
				":role"     => self::$actor["role"],
				":changes"  => $json,
				":ip"       => $ip,
				":endpoint" => $endpoint
			));
		}catch(Throwable $e){
			// La auditoría jamás interrumpe la operación principal
			error_log("[AUDIT] log: ".$e->getMessage());
		}
	}
}

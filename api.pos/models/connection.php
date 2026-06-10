<?php

date_default_timezone_set('America/La_Paz');
require_once "get.model.php";

class Connection{

	/*=============================================
	Información de la base de datos
	=============================================*/

	static public function infoDatabase(){

		$infoDB = array(

			"database" => getenv("DB_NAME") ?: "u228744577_pos",
			"user" => getenv("DB_USER") ?: "root",
			"pass" => getenv("DB_PASS") ?: ""

		);

		return $infoDB;

	}

	/*=============================================
	APIKEY
	=============================================*/

	static public function apikey(){
		$key = getenv("API_KEY") ?: getenv("API_AUTHORIZATION");
		return $key ?: "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy";
	}

	static public function jwtSecret(){
		return getenv("JWT_SECRET") ?: "dfhsdfg34dfchs4xgsrsdry46";
	}

	static public function hashPassword($password){
		return password_hash($password, PASSWORD_BCRYPT, ["cost" => 10]);
	}

	static public function verifyPassword($password, $hash){
		if ($password === null || $password === "" || $hash === null || $hash === "") {
			return false;
		}
		if (password_verify($password, $hash)) {
			return true;
		}
		$legacy = crypt($password, '$2a$07$azybxcags23425sdg23sdfhsd$');
		return hash_equals((string)$hash, (string)$legacy);
	}

	static public function sanitizeIdentifier($name){
		if (!is_string($name) || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
			return null;
		}
		return $name;
	}

	static public function sanitizeOrderMode($orderMode){
		$mode = strtoupper(trim((string)$orderMode));
		return in_array($mode, ["ASC", "DESC"], true) ? $mode : "ASC";
	}

	static public function sanitizeOrderBy($orderBy, $table, $allowedColumns){
		$col = Connection::sanitizeIdentifier($orderBy);
		if ($col === null) {
			return null;
		}
		$allowed = Connection::getColumnsData($table, is_array($allowedColumns) ? $allowedColumns : explode(",", $allowedColumns));
		if (empty($allowed)) {
			return null;
		}
		foreach ($allowed as $c) {
			if ($c->item === $col) {
				return $col;
			}
		}
		return null;
	}

	static public function sanitizeIntList($csv){
		$parts = array_filter(array_map('trim', explode(",", (string)$csv)), 'strlen');
		$ints = [];
		foreach ($parts as $p) {
			if (!preg_match('/^-?\d+$/', $p)) {
				return null;
			}
			$ints[] = intval($p);
		}
		return $ints;
	}

	/*=============================================
	Acceso público
	=============================================*/
	
	static public function publicAccess(){

		$tables = [""];

		return $tables;

	}

	/*=============================================
	Conexión a la base de datos
	=============================================*/

    static public function connect(){
    
        $host = getenv("DB_HOST") ?: "127.0.0.1";
        $db   = Connection::infoDatabase()["database"];
        $user = Connection::infoDatabase()["user"];
        $pass = Connection::infoDatabase()["pass"];
        $port = getenv("DB_PORT") ?: 3306;
    
        $dsn  = "mysql:host={$host};dbname={$db};charset=utf8mb4;port={$port}";
    
        try {
            $link = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ]);
            return $link;
        } catch (PDOException $e) {
            self::logSqlError("db_connect", [
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "host" => $host,
                "database" => $db,
                "port" => $port
            ]);
            die("Error de conexión a la base de datos. Revisa api.pos/php_error_log");
        }
    }

	/*=============================================
	Utilidades de logging SQL
	=============================================*/

	static public function logSqlError($type, $context = array()){

		$payload = array_merge(array(
			"type" => $type,
			"timestamp" => date("Y-m-d H:i:s")
		), $context);

		error_log("[SQL_ERROR] ".json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	static public function handlePdoException($exception, $context = array()){

		self::logSqlError("pdo_exception", array_merge($context, array(
			"message" => $exception->getMessage(),
			"code" => $exception->getCode(),
			"file" => $exception->getFile(),
			"line" => $exception->getLine()
		)));
	}

	static public function handleStatementError($stmt, $context = array()){

		$errorInfo = $stmt ? $stmt->errorInfo() : null;

		self::logSqlError("stmt_error", array_merge($context, array(
			"error_info" => $errorInfo
		)));
	}


	/*=============================================
	Validar existencia de una tabla en la bd
	=============================================*/

	static public function getColumnsData($table, $columns){

		/*=============================================
		Traer el nombre de la base de datos
		=============================================*/

		$database = Connection::infoDatabase()["database"];

		/*=============================================
		Traer todas las columnas de una tabla
		=============================================*/

		if (Connection::sanitizeIdentifier($database) === null || Connection::sanitizeIdentifier($table) === null) {
			return null;
		}

		$stmtMeta = Connection::connect()->prepare(
			"SELECT COLUMN_NAME AS item FROM information_schema.columns WHERE table_schema = :db AND table_name = :tbl"
		);
		$stmtMeta->execute([":db" => $database, ":tbl" => $table]);
		$validate = $stmtMeta->fetchAll(PDO::FETCH_OBJ);

		/*=============================================
		Validamos existencia de la tabla
		=============================================*/

		if(empty($validate)){

			return null;

		}else{

			/*=============================================
			Ajuste de selección de columnas globales
			=============================================*/

			if($columns[0] == "*"){
				
				array_shift($columns);

			}

			/*=============================================
			Validamos existencia de columnas
			=============================================*/

			$sum = 0;
				
			foreach ($validate as $key => $value) {

				$sum += in_array($value->item, $columns);	
				
						
			}



			return $sum == count($columns) ? $validate : null;
			
			
			
		}

	}

	/*=============================================
	Generar Token de Autenticación
	=============================================*/

	static public function jwt($id, $email){

		$time = time();

		$token = array(

			"iat" =>  $time,//Tiempo en que inicia el token
			"exp" => $time + (60*60*24), // Tiempo en que expirará el token (1 día)
			"data" => [

				"id" => $id,
				"email" => $email
			]

		);

		return $token;
	}

	/*=============================================
	Validar el token de seguridad
	=============================================*/

	static public function tokenValidate($token,$table,$suffix){

		/*=============================================
		Traemos el usuario de acuerdo al token
		=============================================*/
		$user = GetModel::getDataFilter($table, "token_exp_".$suffix, "token_".$suffix, $token, null,null,null,null);
		
		if(!empty($user)){

			/*=============================================
			Validamos que el token no haya expirado
			=============================================*/	

			$time = time();

			if($time < $user[0]->{"token_exp_".$suffix}){

				return "ok";

			}else{

				return "expired";
			}

		}else{

			return "no-auth";

		}

	}

}
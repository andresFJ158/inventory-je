<?php

date_default_timezone_set('America/La_Paz');
require_once "get.model.php";

class Connection{
	private static $schemaReady = false;

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

		return "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy";

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
            self::ensureRuntimeSchema($link);
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

	static public function ensureRuntimeSchema($link){

		if(self::$schemaReady){
			return;
		}

		self::$schemaReady = true;

		$queries = array(
			"CREATE TABLE IF NOT EXISTS product_inventory (
				id_inventory INT(11) NOT NULL AUTO_INCREMENT,
				id_product_inventory INT(11) NOT NULL,
				id_office_inventory INT(11) NOT NULL,
				stock_inventory DOUBLE DEFAULT 0,
				status_inventory INT(11) DEFAULT 1,
				date_created_inventory DATE DEFAULT NULL,
				date_updated_inventory TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_inventory),
				UNIQUE KEY uq_product_office (id_product_inventory, id_office_inventory)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS quality_checks (
				id_qc INT(11) NOT NULL AUTO_INCREMENT,
				id_production_qc INT(11) NOT NULL,
				id_admin_qc INT(11) NOT NULL,
				id_office_qc INT(11) NOT NULL,
				result_qc VARCHAR(30) DEFAULT NULL,
				qty_approved_qc DOUBLE DEFAULT 0,
				qty_rejected_qc DOUBLE DEFAULT 0,
				notes_qc TEXT DEFAULT NULL,
				date_created_qc DATE DEFAULT NULL,
				date_updated_qc TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_qc)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS warehouses (
				id_warehouse INT(11) NOT NULL AUTO_INCREMENT,
				title_warehouse TEXT DEFAULT NULL,
				address_warehouse TEXT DEFAULT NULL,
				phone_warehouse TEXT DEFAULT NULL,
				id_office_warehouse INT(11) DEFAULT NULL,
				date_created_warehouse DATE DEFAULT NULL,
				PRIMARY KEY (id_warehouse)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS sub_warehouses (
				id_sub_warehouse INT(11) NOT NULL AUTO_INCREMENT,
				id_admin_sub_warehouse INT(11) NOT NULL,
				id_office_sub_warehouse INT(11) NOT NULL,
				name_sub_warehouse TEXT DEFAULT NULL,
				status_sub_warehouse INT(11) DEFAULT 1,
				date_created_sub_warehouse DATE DEFAULT NULL,
				date_updated_sub_warehouse TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_sub_warehouse)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS warehouse_assignments (
				id_assignment INT(11) NOT NULL AUTO_INCREMENT,
				id_sub_warehouse_assignment INT(11) NOT NULL,
				id_product_assignment INT(11) NOT NULL,
				qty_assignment DOUBLE NOT NULL,
				id_dispatched_by INT(11) NOT NULL,
				id_request_assignment INT(11) DEFAULT NULL,
				type_assignment TEXT DEFAULT 'despacho',
				notes_assignment TEXT DEFAULT NULL,
				date_created_assignment TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id_assignment)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS inventory_requests (
				id_request INT(11) NOT NULL AUTO_INCREMENT,
				id_admin_request INT(11) NOT NULL,
				id_office_request INT(11) NOT NULL,
				id_product_request INT(11) NOT NULL,
				qty_request DOUBLE NOT NULL,
				status_request TEXT DEFAULT 'pendiente',
				id_dispatched_by_request INT(11) DEFAULT NULL,
				qty_dispatched_request DOUBLE DEFAULT NULL,
				notes_request TEXT DEFAULT NULL,
				notes_dispatcher_request TEXT DEFAULT NULL,
				id_warehouse_request INT(11) DEFAULT NULL,
				date_created_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				date_updated_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_request)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS raw_materials (
				id_raw_material INT(11) NOT NULL AUTO_INCREMENT,
				name_raw_material TEXT NOT NULL,
				unit_raw_material TEXT NOT NULL,
				measure_type ENUM('weight','volume','unit') DEFAULT 'unit',
				description_raw_material TEXT DEFAULT NULL,
				stock_raw_material DOUBLE DEFAULT 0,
				id_office_raw_material INT(11) NOT NULL,
				id_admin_raw_material INT(11) NOT NULL,
				status_raw_material INT(11) DEFAULT 1,
				date_created_raw_material DATE DEFAULT NULL,
				date_updated_raw_material TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_raw_material)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS raw_material_entries (
				id_entry INT(11) NOT NULL AUTO_INCREMENT,
				id_raw_material_entry INT(11) NOT NULL,
				qty_entry DOUBLE NOT NULL,
				unit_price_entry DOUBLE DEFAULT 0,
				total_cost_entry DOUBLE DEFAULT 0,
				lot_number_entry TEXT DEFAULT NULL,
				supplier_entry TEXT DEFAULT NULL,
				notes_entry TEXT DEFAULT NULL,
				status_entry TEXT DEFAULT 'pendiente',
				id_admin_entry INT(11) NOT NULL,
				id_approved_by_entry INT(11) DEFAULT NULL,
				date_entry DATE DEFAULT NULL,
				date_approved_entry DATE DEFAULT NULL,
				date_created_entry DATE DEFAULT NULL,
				date_updated_entry TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_entry)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS recipes (
				id_recipe INT(11) NOT NULL AUTO_INCREMENT,
				id_product_recipe INT(11) NOT NULL,
				batch_size_recipe DOUBLE NOT NULL,
				unit_batch_recipe TEXT DEFAULT NULL,
				notes_recipe TEXT DEFAULT NULL,
				id_office_recipe INT(11) NOT NULL,
				id_admin_recipe INT(11) NOT NULL,
				date_created_recipe DATE DEFAULT NULL,
				date_updated_recipe TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_recipe),
				UNIQUE KEY uq_product_recipe (id_product_recipe)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS recipe_ingredients (
				id_ingredient INT(11) NOT NULL AUTO_INCREMENT,
				id_recipe_ingredient INT(11) NOT NULL,
				id_raw_material_ingredient INT(11) NOT NULL,
				qty_ingredient DOUBLE NOT NULL,
				notes_ingredient TEXT DEFAULT NULL,
				date_created_ingredient DATE DEFAULT NULL,
				date_updated_ingredient TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_ingredient)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS recipe_labor (
				id_labor INT(11) NOT NULL AUTO_INCREMENT,
				id_recipe_labor INT(11) NOT NULL,
				description_labor TEXT NOT NULL,
				type_labor TEXT DEFAULT 'fixed',
				hours_labor DOUBLE DEFAULT 0,
				cost_per_hour_labor DOUBLE DEFAULT 0,
				fixed_cost_labor DOUBLE DEFAULT 0,
				total_cost_labor DOUBLE DEFAULT 0,
				date_created_labor DATE DEFAULT NULL,
				date_updated_labor TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_labor)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS recipe_indirect_costs (
				id_recipe_indirect INT(11) NOT NULL AUTO_INCREMENT,
				id_recipe_indirect_recipe INT(11) NOT NULL,
				id_type_indirect INT(11) NOT NULL,
				amount_per_batch_indirect DOUBLE NOT NULL,
				date_created_indirect DATE DEFAULT NULL,
				date_updated_indirect TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_recipe_indirect)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);

		foreach($queries as $query){
			try{
				$link->exec($query);
			}catch(PDOException $e){
				self::handlePdoException($e, array(
					"model" => "Connection::ensureRuntimeSchema",
					"sql" => $query
				));
			}
		}

		$alterQueries = array(
			"ALTER TABLE admins ADD COLUMN id_warehouse_admin INT(11) NULL DEFAULT 0",
			"ALTER TABLE productions ADD COLUMN id_packaged_product INT DEFAULT 0",
			"ALTER TABLE productions ADD COLUMN pkg_labor_cost DOUBLE DEFAULT 0",
			"ALTER TABLE productions ADD COLUMN pkg_indirect_cost DOUBLE DEFAULT 0",
			"ALTER TABLE productions ADD COLUMN real_bulk_qty DOUBLE DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN yield_variance DOUBLE DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN yield_variance_pct DOUBLE DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN qty_packaged_production DOUBLE DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN qty_approved_production DOUBLE DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN qty_rejected_production DOUBLE DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN result_qc_production VARCHAR(30) DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN notes_qc_production TEXT DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN pkg_name_production TEXT DEFAULT NULL"
		);

		foreach($alterQueries as $query){
			try{
				$link->exec($query);
			}catch(PDOException $e){
				if($e->getCode() != "42S21"){
					self::handlePdoException($e, array(
						"model" => "Connection::ensureRuntimeSchema",
						"sql" => $query
					));
				}
			}
		}

		try{
			$count = (int) $link->query("SELECT COUNT(*) FROM product_inventory")->fetchColumn();
			if($count === 0){
				$link->exec("INSERT INTO product_inventory
					(id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
					SELECT id_product, id_office_product,
						CAST(COALESCE(NULLIF(stock_product, ''), '0') AS DECIMAL(10,2)),
						status_product, date_created_product
					FROM products
					WHERE id_office_product > 0");
			}
		}catch(PDOException $e){
			self::handlePdoException($e, array(
				"model" => "Connection::ensureRuntimeSchema",
				"sql" => "populate product_inventory"
			));
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

		$validate = Connection::connect()
		->query("SELECT COLUMN_NAME AS item FROM information_schema.columns WHERE table_schema = '$database' AND table_name = '$table'")
		->fetchAll(PDO::FETCH_OBJ);

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

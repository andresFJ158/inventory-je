<?php

require_once "connection.php";
require_once "audit.model.php";

class PostModel{

	/*=============================================
	Peticion POST para crear datos de forma dinámica
	=============================================*/

	static public function postData($table, $data){

		$columns = "";
		$params = "";

		foreach ($data as $key => $value) {
			
			$columns .= $key.",";
			
			$params .= ":".$key.",";
			
		}

		$columns = substr($columns, 0, -1);
		$params = substr($params, 0, -1);


		$sql = "INSERT INTO $table ($columns) VALUES ($params)";

		$link = Connection::connect();
		$stmt = $link->prepare($sql);

		foreach ($data as $key => $value) {

			$stmt->bindParam(":".$key, $data[$key], PDO::PARAM_STR);
		
		}

		try{
			$executed = $stmt -> execute();
		}catch(PDOException $Exception){
			Connection::handlePdoException($Exception, array(
				"model" => "PostModel::postData",
				"table" => $table,
				"sql" => $sql,
				"params" => $data
			));
			return null;
		}

		if($executed){

			$lastId = $link->lastInsertId();

			AuditLogger::log("INSERT", $table, $lastId, $data);

			$response = array(

				"lastId" => $lastId,
				"comment" => "The process was successful"
			);

			return $response;

		}else{

			Connection::handleStatementError($stmt, array(
				"model" => "PostModel::postData",
				"table" => $table,
				"sql" => $sql,
				"params" => $data
			));
			return $link->errorInfo();

		}


	}

}
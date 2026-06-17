<?php

require_once "connection.php";
require_once "get.model.php";
require_once "audit.model.php";

class PutModel{

	/*=============================================
	Peticion Put para editar datos de forma dinámica
	=============================================*/

	static public function putData($table, $data, $id, $nameId){

		/*=============================================
		Validar el ID
		=============================================*/

		$response = GetModel::getDataFilter($table, $nameId, $nameId, $id, null,null,null,null);
		
		if(empty($response)){

			return null;

		}

		/*=============================================
		Capturar estado previo para auditoría
		=============================================*/

		$before = AuditLogger::snapshot($table, $nameId, $id);

		/*=============================================
		Actualizamos registros
		=============================================*/

		$set = "";

		foreach ($data as $key => $value) {
			
			$set .= $key." = :".$key.",";
			
		}

		$set = substr($set, 0, -1);

		$sql = "UPDATE $table SET $set WHERE $nameId = :$nameId";

		$link = Connection::connect();
		$stmt = $link->prepare($sql);

		foreach ($data as $key => $value) {

			$stmt->bindParam(":".$key, $data[$key], PDO::PARAM_STR);
		
		}

		$stmt->bindParam(":".$nameId, $id, PDO::PARAM_STR);

		try{
			$executed = $stmt -> execute();
		}catch(PDOException $Exception){
			Connection::handlePdoException($Exception, array(
				"model" => "PutModel::putData",
				"table" => $table,
				"sql" => $sql,
				"params" => $data,
				"id" => $id,
				"nameId" => $nameId
			));
			return null;
		}

		if($executed){

			AuditLogger::log("UPDATE", $table, $id, array("before" => $before, "after" => $data));

			$response = array(

				"comment" => "The process was successful"
			);

			return $response;

		}else{

			Connection::handleStatementError($stmt, array(
				"model" => "PutModel::putData",
				"table" => $table,
				"sql" => $sql,
				"params" => $data,
				"id" => $id,
				"nameId" => $nameId
			));
			return $link->errorInfo();

		}

	}

}
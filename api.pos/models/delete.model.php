<?php 

require_once "connection.php";
require_once "get.model.php";

class DeleteModel{

	/*=============================================
	Peticion Delete para eliminar datos de forma dinámica
	=============================================*/

	static public function deleteData($table, $id, $nameId){

		/*=============================================
		Validar el ID
		=============================================*/

		$response = GetModel::getDataFilter($table, $nameId, $nameId, $id, null,null,null,null);
		
		if(empty($response)){

			return null;

		}

		/*=============================================
		Eliminamos registros
		=============================================*/

		$sql = "DELETE FROM $table WHERE $nameId = :$nameId";

		$link = Connection::connect();
		$stmt = $link->prepare($sql);

		$stmt->bindParam(":".$nameId, $id, PDO::PARAM_STR);

		try{
			$executed = $stmt -> execute();
		}catch(PDOException $Exception){
			Connection::handlePdoException($Exception, array(
				"model" => "DeleteModel::deleteData",
				"table" => $table,
				"sql" => $sql,
				"id" => $id,
				"nameId" => $nameId
			));
			return null;
		}

		if($executed){

			$response = array(

				"comment" => "The process was successful"
			);

			return $response;
		
		}else{

			Connection::handleStatementError($stmt, array(
				"model" => "DeleteModel::deleteData",
				"table" => $table,
				"sql" => $sql,
				"id" => $id,
				"nameId" => $nameId
			));
			return $link->errorInfo();

		}

	}

}
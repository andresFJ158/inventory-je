<?php

require_once "connection.php";

class GetModel{

	private static function sqlOrderLimit($table, $selectArray, $orderBy, $orderMode, $startAt, $endAt){
		$clause = "";
		if ($orderBy != null && $orderMode != null) {
			$col = Connection::sanitizeOrderBy($orderBy, $table, $selectArray);
			if ($col !== null) {
				$clause .= " ORDER BY " . $col . " " . Connection::sanitizeOrderMode($orderMode);
			}
		}
		if ($startAt != null && $endAt != null) {
			$clause .= " LIMIT " . max(0, intval($startAt)) . ", " . max(1, intval($endAt));
		}
		return $clause;
	}

	/*=============================================
	Peticiones GET sin filtro
	=============================================*/

	static public function getData($table, $select,$orderBy,$orderMode,$startAt,$endAt){

		/*=============================================
		Validar existencia de la tabla y de las columnas
		=============================================*/

		$selectArray = explode(",",$select);
		
		if(empty(Connection::getColumnsData($table, $selectArray))){
			
			return null;
		
		}

		/*=============================================
		Sin ordenar y sin limitar datos
		=============================================*/

		$sql = "SELECT $select FROM $table" . GetModel::sqlOrderLimit($table, $selectArray, $orderBy, $orderMode, $startAt, $endAt);

		$stmt = Connection::connect()->prepare($sql);

		try{

			$stmt -> execute();

		}catch(PDOException $Exception){

			Connection::handlePdoException($Exception, array(
				"model" => "GetModel::getData",
				"sql" => $sql
			));
			return null;
		
		}

		return $stmt -> fetchAll(PDO::FETCH_CLASS);

	}

	/*=============================================
	Peticiones GET con filtro
	=============================================*/
	
	static public function getDataFilter($table, $select, $linkTo, $equalTo, $orderBy,$orderMode,$startAt,$endAt){

		/*=============================================
		Validar existencia de la tabla y de las columnas
		=============================================*/

		$linkToArray = explode(",",$linkTo);
		$selectArray = explode(",",$select);

		foreach ($linkToArray  as $key => $value) {
			array_push($selectArray, $value);
		}

		$selectArray = array_unique($selectArray);


		if(empty(Connection::getColumnsData($table,$selectArray ))){	
			
			return null;

		}
		
		$equalToArray = explode(",",$equalTo);
		$linkToText = "";

		if(count($linkToArray)>1){

			foreach ($linkToArray as $key => $value) {
				
				if($key > 0){

					$linkToText .= "AND ".$value." = :".$value." ";
				}
			}

		}

		/*=============================================
		Sin ordenar y sin limitar datos
		=============================================*/

		$sql = "SELECT $select FROM $table WHERE $linkToArray[0] = :$linkToArray[0] $linkToText"
			. GetModel::sqlOrderLimit($table, $selectArray, $orderBy, $orderMode, $startAt, $endAt);

		$stmt = Connection::connect()->prepare($sql);

		foreach ($linkToArray as $key => $value) {
			
			$stmt -> bindParam(":".$value, $equalToArray[$key], PDO::PARAM_STR);

		}

		try{

			$stmt -> execute();

		}catch(PDOException $Exception){

			Connection::handlePdoException($Exception, array(
				"model" => "GetModel::getDataFilter",
				"sql" => $sql,
				"linkTo" => $linkTo,
				"equalTo" => $equalTo
			));
			return null;
		
		}

		return $stmt -> fetchAll(PDO::FETCH_CLASS);

	}

	/*=============================================
	Peticiones GET sin filtro entre tablas relacionadas
	=============================================*/

	static public function getRelData($rel, $type, $select, $orderBy,$orderMode,$startAt,$endAt){

		/*=============================================
		Validar existencia de las columnas
		=============================================*/
	
		$relArray = explode(",", $rel);
		$typeArray = explode(",", $type);
		$innerJoinText = "";

		if(count($relArray)>1){

			foreach ($relArray as $key => $value) {

				/*=============================================
				Validar existencia de la tabla y de las columnas
				=============================================*/
				
				if(empty(Connection::getColumnsData($value,["*"]))){

					return null;

				}
				
				if($key > 0){

					$innerJoinText .= "INNER JOIN ".$value." ON ".$relArray[0].".id_".$typeArray[$key]."_".$typeArray[0] ." = ".$value.".id_".$typeArray[$key]." ";
				}
			}


			$selectArray = explode(",", $select);

			$sql = "SELECT $select FROM $relArray[0] $innerJoinText"
				. GetModel::sqlOrderLimit($relArray[0], $selectArray, $orderBy, $orderMode, $startAt, $endAt);

			$stmt = Connection::connect()->prepare($sql);

			try{

				$stmt -> execute();

			}catch(PDOException $Exception){

				Connection::handlePdoException($Exception, array(
					"model" => "GetModel::getRelData",
					"sql" => $sql,
					"rel" => $rel,
					"type" => $type
				));
				return null;
			
			}

			return $stmt -> fetchAll(PDO::FETCH_CLASS);

		}else{

			return null;
		}
		
	}

	/*=============================================
	Peticiones GET con filtro entre tablas relacionadas
	=============================================*/

	static public function getRelDataFilter($rel, $type, $select, $linkTo, $equalTo, $orderBy,$orderMode,$startAt,$endAt){


		/*=============================================
		Organizamos los filtros
		=============================================*/

		$linkToArray = explode(",",$linkTo);
		$equalToArray = explode(",",$equalTo);
		$linkToText = "";

		if(count($linkToArray)>1){

			foreach ($linkToArray as $key => $value) {

				if($key > 0){

					$linkToText .= "AND ".$value." = :".$value." ";
				}
			}

		}

		/*=============================================
		Organizamos las relaciones
		=============================================*/

		$relArray = explode(",", $rel);
		$typeArray = explode(",", $type);
		$innerJoinText = "";

		if(count($relArray)>1){

			foreach ($relArray as $key => $value) {

				/*=============================================
				Validar existencia de la tabla
				=============================================*/
				
				if(empty(Connection::getColumnsData($value, ["*"]))){

					return null;

				}
				
				if($key > 0){

					$innerJoinText .= "INNER JOIN ".$value." ON ".$relArray[0].".id_".$typeArray[$key]."_".$typeArray[0] ." = ".$value.".id_".$typeArray[$key]." ";
				}
			}


			$selectArray = explode(",", $select);

			$sql = "SELECT $select FROM $relArray[0] $innerJoinText WHERE $linkToArray[0] = :$linkToArray[0] $linkToText"
				. GetModel::sqlOrderLimit($relArray[0], $selectArray, $orderBy, $orderMode, $startAt, $endAt);

			$stmt = Connection::connect()->prepare($sql);

			foreach ($linkToArray as $key => $value) {
			
				$stmt -> bindParam(":".$value, $equalToArray[$key], PDO::PARAM_STR);

			}

			try{

				$stmt -> execute();

			}catch(PDOException $Exception){

				Connection::handlePdoException($Exception, array(
					"model" => "GetModel::getRelDataFilter",
					"sql" => $sql,
					"rel" => $rel,
					"type" => $type,
					"linkTo" => $linkTo,
					"equalTo" => $equalTo
				));
				return null;
			
			}

			return $stmt -> fetchAll(PDO::FETCH_CLASS);

		}else{

			return null;
		}
		
	}

	/*=============================================
	Peticiones GET para el buscador sin relaciones
	=============================================*/

	static public function getDataSearch($table, $select, $linkTo, $search,$orderBy,$orderMode,$startAt,$endAt){

		/*=============================================
		Validar existencia de la tabla y de las columnas
		=============================================*/

		$linkToArray = explode(",",$linkTo);
		$selectArray = explode(",",$select);

		foreach ($linkToArray  as $key => $value) {
			array_push($selectArray, $value);
		}

		$selectArray = array_unique($selectArray);
		
		if(empty(Connection::getColumnsData($table,$selectArray ))){
			
			return null;

		}

		$searchArray = explode(",",$search);
		$linkToText = "";

		if(count($linkToArray)>1){

			foreach ($linkToArray as $key => $value) {
				
				if($key > 0){

					$linkToText .= "AND ".$value." = :".$value." ";
				}
			}

		}


		/*=============================================
		Sin ordenar y sin limitar datos
		=============================================*/

		$sql = "SELECT $select FROM $table WHERE $linkToArray[0] LIKE :search0 $linkToText"
			. GetModel::sqlOrderLimit($table, $selectArray, $orderBy, $orderMode, $startAt, $endAt);

		$stmt = Connection::connect()->prepare($sql);
		$searchTerm = '%' . ($searchArray[0] ?? '') . '%';
		$stmt->bindValue(':search0', $searchTerm, PDO::PARAM_STR);

		foreach ($linkToArray as $key => $value) {

			if($key > 0){
			
				$stmt -> bindParam(":".$value, $searchArray[$key], PDO::PARAM_STR);

			}

		}

		try{

			$stmt -> execute();

		}catch(PDOException $Exception){

			Connection::handlePdoException($Exception, array(
				"model" => "GetModel::getDataSearch",
				"sql" => $sql,
				"linkTo" => $linkTo,
				"search" => $search
			));
			return null;
		
		}

		return $stmt -> fetchAll(PDO::FETCH_CLASS);


	}


	/*=============================================
	Peticiones GET para el buscador entre tablas relacionadas
	=============================================*/

	static public function getRelDataSearch($rel, $type, $select, $linkTo, $search, $orderBy,$orderMode,$startAt,$endAt){


		/*=============================================
		Organizamos los filtros
		=============================================*/
		$linkToArray = explode(",",$linkTo);
		$searchArray = explode(",",$search);
		$linkToText = "";

		if(count($linkToArray)>1){

			foreach ($linkToArray as $key => $value) {
				
				if($key > 0){

					$linkToText .= "AND ".$value." = :".$value." ";
				}
			}

		}
	
		/*=============================================
		Organizamos las relaciones
		=============================================*/

		$relArray = explode(",", $rel);
		$typeArray = explode(",", $type);
		$innerJoinText = "";

		if(count($relArray)>1){

			foreach ($relArray as $key => $value) {

				/*=============================================
				Validar existencia de la tabla
				=============================================*/
				
				if(empty(Connection::getColumnsData($value, ["*"]))){

					return null;

				}
				
				if($key > 0){

					$innerJoinText .= "INNER JOIN ".$value." ON ".$relArray[0].".id_".$typeArray[$key]."_".$typeArray[0] ." = ".$value.".id_".$typeArray[$key]." ";
				}
			}


			/*=============================================
			Sin ordenar y sin limitar datos
			=============================================*/

			$sql = "SELECT $select FROM $relArray[0] $innerJoinText WHERE $linkToArray[0] LIKE :search0 $linkToText"
				. GetModel::sqlOrderLimit($relArray[0], explode(",", $select), $orderBy, $orderMode, $startAt, $endAt);

			$stmt = Connection::connect()->prepare($sql);
			$searchTerm = '%' . ($searchArray[0] ?? '') . '%';
			$stmt->bindValue(':search0', $searchTerm, PDO::PARAM_STR);

			foreach ($linkToArray as $key => $value) {

				if($key > 0){
				
					$stmt -> bindParam(":".$value, $searchArray[$key], PDO::PARAM_STR);

				}

			}

			try{

				$stmt -> execute();

			}catch(PDOException $Exception){

				Connection::handlePdoException($Exception, array(
					"model" => "GetModel::getRelDataSearch",
					"sql" => $sql,
					"rel" => $rel,
					"type" => $type,
					"linkTo" => $linkTo,
					"search" => $search
				));
				return null;
			
			}

			return $stmt -> fetchAll(PDO::FETCH_CLASS);

		}else{

			return null;
		}
		
	}

	/*=============================================
	Peticiones GET para selección de rangos
	=============================================*/

	static public function getDataRange($table,$select,$linkTo,$between1,$between2,$orderBy,$orderMode,$startAt,$endAt, $filterTo, $inTo){

		/*=============================================
		Validar existencia de la tabla y de las columnas
		=============================================*/

		$linkToArray = explode(",",$linkTo);

		if($filterTo != null){
			$filterToArray = explode(",",$filterTo);
		}else{
			$filterToArray =array();
		}

		$selectArray = explode(",",$select);

		foreach ($linkToArray  as $key => $value) {
			array_push($selectArray, $value);
		}

		foreach ($filterToArray  as $key => $value) {
			array_push($selectArray, $value);
		}

		$selectArray = array_unique($selectArray);
		
		if(empty(Connection::getColumnsData($table,$selectArray ))){
			
			return null;

		}

		$filter = "";
		$inParams = [];
		if($filterTo != null && $inTo != null){
			$filterToArray = explode(",",$filterTo);
			$inToArray = explode(",",$inTo);
			foreach($filterToArray as $key => $value){
				$col = Connection::sanitizeIdentifier($value);
				if($col === null || !isset($inToArray[$key])){
					continue;
				}
				$ints = Connection::sanitizeIntList(str_replace("_", ",", $inToArray[$key]));
				if($ints === null || count($ints) === 0){
					continue;
				}
				$ph = [];
				foreach($ints as $i => $n){
					$p = ':in_'.$col.'_'.$i;
					$ph[] = $p;
					$inParams[$p] = $n;
				}
				$filter .= ' AND '.$col.' IN ('.implode(',', $ph).')';
			}
		}

		$linkCol = Connection::sanitizeIdentifier($linkTo);
		if($linkCol === null){
			return null;
		}

		$sql = "SELECT $select FROM $table WHERE $linkCol BETWEEN :between1 AND :between2 $filter"
			. GetModel::sqlOrderLimit($table, $selectArray, $orderBy, $orderMode, $startAt, $endAt);

		$stmt = Connection::connect()->prepare($sql);
		$stmt->bindValue(':between1', $between1, PDO::PARAM_STR);
		$stmt->bindValue(':between2', $between2, PDO::PARAM_STR);
		foreach($inParams as $p => $n){
			$stmt->bindValue($p, $n, PDO::PARAM_INT);
		}

		try{

			$stmt -> execute();

		}catch(PDOException $Exception){

			Connection::handlePdoException($Exception, array(
				"model" => "GetModel::getDataRange",
				"sql" => $sql,
				"linkTo" => $linkTo,
				"between1" => $between1,
				"between2" => $between2
			));
			return null;
		
		}

		return $stmt -> fetchAll(PDO::FETCH_CLASS);

	}

	/*=============================================
	Peticiones GET para selección de rangos con relaciones
	=============================================*/

	static public function getRelDataRange($rel,$type,$select,$linkTo,$between1,$between2,$orderBy,$orderMode,$startAt,$endAt, $filterTo, $inTo){

		/*=============================================
		Validar existencia de la tabla y de las columnas
		=============================================*/

		$linkToArray = explode(",",$linkTo);
		
		if($filterTo != null){
			$filterToArray = explode(",",$filterTo);
		}else{
			$filterToArray =array();
		}

		$filter = "";
		if($filterTo != null && $inTo != null){
			$filterToArray = explode(",",$filterTo);
			$inToArray = explode(",",$inTo);
			foreach($filterToArray as $key => $value){
				if(isset($inToArray[$key])){
					$inVals = str_replace("_", ",", $inToArray[$key]);
					$filter .= ' AND '.$value.' IN ('.$inVals.')';
				}
			}
		}

		$relArray = explode(",", $rel);
		$typeArray = explode(",", $type);
		$innerJoinText = "";

		if(count($relArray)>1){

			foreach ($relArray as $key => $value) {

				/*=============================================
				Validar existencia de la tabla
				=============================================*/
				
				if(empty(Connection::getColumnsData($value, ["*"]))){

					return null;

				}

				
				if($key > 0){

					$innerJoinText .= "INNER JOIN ".$value." ON ".$relArray[0].".id_".$typeArray[$key]."_".$typeArray[0]." = ".$value.".id_".$typeArray[$key]." ";
				}
			}

			$selectArray = explode(",", $select);

			$sql = "SELECT $select FROM $relArray[0] $innerJoinText WHERE $linkTo BETWEEN '$between1' AND '$between2' $filter"
				. GetModel::sqlOrderLimit($relArray[0], $selectArray, $orderBy, $orderMode, $startAt, $endAt);

			$stmt = Connection::connect()->prepare($sql);

			try{

				$stmt -> execute();

			}catch(PDOException $Exception){

				Connection::handlePdoException($Exception, array(
					"model" => "GetModel::getRelDataRange",
					"sql" => $sql,
					"rel" => $rel,
					"type" => $type,
					"linkTo" => $linkTo,
					"between1" => $between1,
					"between2" => $between2
				));
				return null;
			
			}

			return $stmt -> fetchAll(PDO::FETCH_CLASS);

		}else{

			return null;
		}

	}


}


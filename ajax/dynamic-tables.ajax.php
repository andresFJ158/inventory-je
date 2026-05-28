<?php
ini_set("display_errors", 0);
error_reporting(0);

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once "../controllers/curl.controller.php";
require_once "../controllers/template.controller.php";

class DynamicTablesController{

	/*=============================================
    Eliminar Items
    =============================================*/

	public $idItemDelete;
    public $tableDelete;
	public $suffixDelete;
	public $token;

	/*=============================================
    Verificar si un registro tiene relaciones
    =============================================*/

	private function hasRelations($tableName, $suffix, $idItem){
		
		require_once "../controllers/install.controller.php";
		
		// Obtener todas las tablas del sistema
		$allTables = InstallController::getTables();
		
		// Obtener información de la base de datos
		$database = InstallController::infoDatabase()["database"];
		
		$method = "GET";
		$fields = array();

		// Revisar cada tabla para encontrar relaciones
		foreach ($allTables as $table) {
			
			// Omitir tablas del sistema y la misma tabla que se está eliminando
			if(in_array($table, array("modules", "pages", "columns", "folders", "files")) || $table === $tableName){
				continue;
			}

			// Obtener las columnas reales de la tabla desde information_schema
			try {
				
				$sql = "SELECT COLUMN_NAME FROM information_schema.columns 
						WHERE table_schema = :database 
						AND table_name = :table 
						AND COLUMN_NAME LIKE :pattern";
				
				$stmt = InstallController::connect()->prepare($sql);
				
				// Buscar columnas que puedan referenciar a la tabla que se quiere eliminar
				// Patrones: id_{tabla}_{sufijo}, id_{sufijo}_{tabla}, id_{tabla}, id_{sufijo}
				$patterns = array(
					"%_".$tableName."_%",
					"%_".$suffix."_%",
					"id_".$tableName,
					"id_".$suffix,
					"%_".$tableName,
					"%_".$suffix
				);

				foreach ($patterns as $pattern) {
					
					$stmt->execute(array(
						':database' => $database,
						':table' => $table,
						':pattern' => $pattern
					));
					
					$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
					
					// Para cada columna encontrada, verificar si hay registros relacionados
					foreach ($columns as $columnName) {
						
						// Obtener el sufijo de la tabla destino para construir el select
						$destSuffix = $this->getSuffixFromTable($table);
						
						// Intentar buscar registros relacionados usando la API
						$url = $table."?linkTo=".$columnName."&equalTo=".$idItem."&select=id_".$destSuffix;
						$checkRelation = CurlController::request($url,$method,$fields);
						
						// Si encuentra relaciones, devolver información
						if($checkRelation->status == 200 && !empty($checkRelation->results)){
							
							return array(
								"hasRelations" => true,
								"relatedTable" => $table,
								"columnName" => $columnName,
								"count" => count($checkRelation->results)
							);
						}
					}
				}
				
			} catch (Exception $e) {
				
				// Si hay error al acceder a la BD, continuar con el siguiente método
				continue;
			}
		}

		// Si no encontró relaciones con information_schema, intentar con patrones comunes
		foreach ($allTables as $table) {
			
			if(in_array($table, array("modules", "pages", "columns", "folders", "files")) || $table === $tableName){
				continue;
			}

			// Patrones comunes de nombres de columnas de relación
			$tableSuffix = $this->getSuffixFromTable($table);
			$commonColumnNames = array(
				"id_".$tableSuffix."_".$tableName,  // Ej: id_purchase_product
				"id_".$tableSuffix."_".$suffix,     // Ej: id_sale_product
				"id_".$tableName."_".$tableSuffix,  // Ej: id_product_purchase
				"id_".$suffix."_".$tableSuffix      // Ej: id_product_sale
			);

			foreach ($commonColumnNames as $columnName) {
				
				$url = $table."?linkTo=".$columnName."&equalTo=".$idItem."&select=id_".$tableSuffix;
				$checkRelation = CurlController::request($url,$method,$fields);
				
				if($checkRelation->status == 200 && !empty($checkRelation->results)){
					
					return array(
						"hasRelations" => true,
						"relatedTable" => $table,
						"columnName" => $columnName,
						"count" => count($checkRelation->results)
					);
				}
			}
		}

		return array("hasRelations" => false);
	}

	/*=============================================
    Obtener el sufijo de una tabla basándose en patrones comunes
    =============================================*/

	private function getSuffixFromTable($tableName){
		
		// Mapeo de tablas a sus sufijos comunes
		$suffixMap = array(
			"products" => "product",
			"purchases" => "purchase",
			"sales" => "sale",
			"orders" => "order",
			"clients" => "client",
			"suppliers" => "supplier",
			"offices" => "office",
			"admins" => "admin",
			"categories" => "category",
			"cashs" => "cash",
			"invoices" => "invoice",
			"bills" => "bill"
		);

		if(isset($suffixMap[$tableName])){
			return $suffixMap[$tableName];
		}

		// Si no está en el mapa, intentar derivar del nombre (quitar 's' al final)
		if(substr($tableName, -1) === 's'){
			return substr($tableName, 0, -1);
		}

		return $tableName;
	}

    public function deleteItems(){

    	$idItems = explode(",",$this->idItemDelete);
    	$countDelete = 0;
    	$errors = array();

    	foreach ($idItems as $key => $value) {
    		
    		$idItem = base64_decode($value);

    		/*=============================================
			Verificar si el registro tiene relaciones
			=============================================*/
			
			$relationCheck = $this->hasRelations($this->tableDelete, $this->suffixDelete, $idItem);
			
			if($relationCheck["hasRelations"]){
				
				$errors[] = "El registro tiene ".$relationCheck["count"]." relación(es) con la tabla '".$relationCheck["relatedTable"]."' y no puede ser eliminado. Elimine primero los registros relacionados.";
				continue;
			}

			/*=============================================
			Eliminar el registro principal
			=============================================*/
			
    		$url = $this->tableDelete."?id=".$idItem."&nameId=id_".$this->suffixDelete."&token=".$this->token."&table=admins&suffix=admin";
			$method = "DELETE";
			$fields = array();

			$deleteItem = CurlController::request($url,$method,$fields);

			if($deleteItem->status == 200){

				$countDelete++;

			}else{

				$errors[] = "Error al eliminar el registro con ID: ".$idItem;

			}

    	}

    	/*=============================================
		Devolver respuesta
		=============================================*/
		
		if(!empty($errors)){
			
			// Si hay errores, devolverlos como JSON
			echo json_encode(array(
				"status" => "error",
				"errors" => $errors,
				"deleted" => $countDelete,
				"total" => count($idItems)
			));
			
		}else if($countDelete == count($idItems)){
			
			// Si todos se eliminaron exitosamente
			echo 200;
			
		}else{
			
			// Si algunos fallaron
			echo json_encode(array(
				"status" => "partial",
				"deleted" => $countDelete,
				"total" => count($idItems)
			));
		}

	}

	/*=============================================
    Devolver tabla filtrada
    =============================================*/

    public $contentModule;
	public $orderBy;
	public $orderMode;
	public $limit;
	public $page;
	public $rolAdmin;
	public $search;
	public $between1;
	public $between2;
	public $idOffice;

	public function loadAjaxTable(){

		$module = json_decode($this->contentModule);
		if ($module === null) {
			echo json_encode(array(
				"HTMLTable" => "<tr><td colspan='10' class='text-center text-danger'>Error: Invalid contentModule JSON.</td></tr>",
				"totalData" => 0,
				"totalPages" => 0,
				"debug" => $this->contentModule
			));
			return;
		}

		$startAt = ($this->page-1)*$this->limit;
		$table = array(); 
		$totalPages = 0;
		$totalData = 0;

    	/*=============================================
		Filtro por búsqueda
		=============================================*/

		if($this->search != ""){

			/*=============================================
			Columnas de búsqueda
			=============================================*/

			$linkTo = array();

			foreach ($module->columns as $key => $value) {
			
				if($value->visible_column == 1){

					if( $value->type_column == "text" ||
						$value->type_column == "textarea" ||
						$value->type_column == "int" ||
						$value->type_column == "double" ||
						$value->type_column == "money" ||  
						$value->type_column == "color" || 
						$value->type_column == "link" ||
						$value->type_column == "select" ||
						$value->type_column == "array" || 
						$value->type_column == "date" ||
						$value->type_column == "time" ||
						$value->type_column == "datetime"){

						array_push($linkTo, $value->title_column);
					}
				}
			}

			// Asegurar que id_client_order esté en la lista de búsqueda para órdenes (para buscar por nombre de cliente)
			if($module->title_module == "orders" && !in_array("id_client_order", $linkTo)){
				array_push($linkTo, "id_client_order");
			}

			/*=============================================
			Itineración de búsqueda
			=============================================*/
			foreach ($linkTo as $key => $value) {

				if($value == "id_client_order" && $module->title_module == "orders" && !is_numeric($this->search)){
					
					$searchWords = explode("_", $this->search);
					$clientIds = array();
					
					foreach($searchWords as $word){
						if(trim($word) == "") continue;
						
						// Buscar en clientes por nombre
						$urlClients = "clients?linkTo=name_client&search=".$word."&select=id_client";
						$clients = CurlController::request($urlClients, "GET", array());
						
						if(isset($clients->status) && $clients->status == 200 && !empty($clients->results)){
							foreach($clients->results as $c){
								if(!in_array($c->id_client, $clientIds)){
									$clientIds[] = $c->id_client;
								}
							}
						}
						
						// Buscar en clientes por apellido
						$urlClients = "clients?linkTo=surname_client&search=".$word."&select=id_client";
						$clients = CurlController::request($urlClients, "GET", array());
						
						if(isset($clients->status) && $clients->status == 200 && !empty($clients->results)){
							foreach($clients->results as $c){
								if(!in_array($c->id_client, $clientIds)){
									$clientIds[] = $c->id_client;
								}
							}
						}
					}

					if(count($clientIds) > 0){
						$inIds = implode("_", $clientIds);
						if($this->idOffice == 0 || !in_array("id_office_".$module->suffix_module, array_column($module->columns, "title_column"))){
							$url = $module->title_module."?linkTo=date_created_".$module->suffix_module."&between1=".$this->between1."&between2=".$this->between2."&filterTo=".$value."&inTo=".$inIds."&orderBy=".$this->orderBy."&orderMode=".$this->orderMode."&startAt=".$startAt."&endAt=".$this->limit;
						}else{
							$url = $module->title_module."?linkTo=date_created_".$module->suffix_module."&between1=".$this->between1."&between2=".$this->between2."&filterTo=".$value.",id_office_".$module->suffix_module."&inTo=".$inIds.",".$this->idOffice."&orderBy=".$this->orderBy."&orderMode=".$this->orderMode."&startAt=".$startAt."&endAt=".$this->limit;
						}
					}else{
						// No se encontraron clientes, forzar a que no encuentre nada
						$url = $module->title_module."?linkTo=".$value."&search=NOT_FOUND_FORCE_EMPTY&orderBy=".$this->orderBy."&orderMode=".$this->orderMode."&startAt=".$startAt."&endAt=".$this->limit;
					}

				}else{

					if($this->idOffice == 0 || !in_array("id_office_".$module->suffix_module, array_column($module->columns, "title_column"))){

						$url = $module->title_module."?linkTo=".$value."&search=".str_replace(" ", "_", $this->search)."&orderBy=".$this->orderBy."&orderMode=".$this->orderMode."&startAt=".$startAt."&endAt=".$this->limit;

					}else{

						$url = $module->title_module."?linkTo=".$value.",id_office_".$module->suffix_module."&search=".str_replace(" ", "_", $this->search).",".$this->idOffice."&orderBy=".$this->orderBy."&orderMode=".$this->orderMode."&startAt=".$startAt."&endAt=".$this->limit;

					}
				}

				$method = "GET";
				$fields = array();

				$table = CurlController::request($url,$method,$fields);

				if($table->status == 200){

					$table = $table->results;

					if($value == "id_client_order" && $module->title_module == "orders" && !is_numeric($this->search) && isset($inIds) && count($clientIds) > 0){
						if($this->idOffice == 0 || !in_array("id_office_".$module->suffix_module, array_column($module->columns, "title_column"))){
							$url = $module->title_module."?linkTo=date_created_".$module->suffix_module."&between1=".$this->between1."&between2=".$this->between2."&filterTo=".$value."&inTo=".$inIds."&select=id_".$module->suffix_module;
						}else{
							$url = $module->title_module."?linkTo=date_created_".$module->suffix_module."&between1=".$this->between1."&between2=".$this->between2."&filterTo=".$value.",id_office_".$module->suffix_module."&inTo=".$inIds.",".$this->idOffice."&select=id_".$module->suffix_module;
						}
					}else{
						if($this->idOffice == 0 || !in_array("id_office_".$module->suffix_module, array_column($module->columns, "title_column"))){
					
							$url = $module->title_module."?linkTo=".$value."&search=".str_replace(" ", "_", $this->search)."&select=id_".$module->suffix_module;

						}else{

							$url = $module->title_module."?linkTo=".$value.",id_office_".$module->suffix_module."&search=".str_replace(" ", "_", $this->search).",".$this->idOffice."&select=id_".$module->suffix_module;

						}
					}

					$reqTotal = CurlController::request($url,$method,$fields);
					$totalData = isset($reqTotal->total) ? $reqTotal->total : (isset($reqTotal->results) && is_array($reqTotal->results) ? count($reqTotal->results) : 0);
					$totalPages = ceil($totalData/$this->limit);

					break;
					
				}else{

					$table = array();
				}
		
			}
			
		}else{

			if($this->idOffice == 0 || !in_array("id_office_".$module->suffix_module, array_column($module->columns, "title_column"))){

				$url = $module->title_module."?linkTo=date_created_".$module->suffix_module."&between1=".$this->between1."&between2=".$this->between2."&orderBy=".$this->orderBy."&orderMode=".$this->orderMode."&startAt=".$startAt."&endAt=".$this->limit;	

			}else{

				$url = $module->title_module."?linkTo=date_created_".$module->suffix_module."&between1=".$this->between1."&between2=".$this->between2."&orderBy=".$this->orderBy."&orderMode=".$this->orderMode."&startAt=".$startAt."&endAt=".$this->limit."&filterTo=id_office_".$module->suffix_module."&inTo=".$this->idOffice;	
			}	

			$method = "GET";
			$fields = array();

			$table = CurlController::request($url,$method,$fields);

			if($table->status == 200){

				$table = $table->results;

				/*=============================================
				Traemos contenido total de la tabla
				=============================================*/


				if($this->idOffice == 0 || !in_array("id_office_".$module->suffix_module, array_column($module->columns, "title_column"))){
			
					$url = $module->title_module."?linkTo=date_created_".$module->suffix_module."&between1=".$this->between1."&between2=".$this->between2."&select=id_".$module->suffix_module;

				}else{

					$url = $module->title_module."?linkTo=date_created_".$module->suffix_module."&between1=".$this->between1."&between2=".$this->between2."&select=id_".$module->suffix_module."&filterTo=id_office_".$module->suffix_module."&inTo=".$this->idOffice;
				}

				$reqTotal = CurlController::request($url,$method,$fields);
				$totalData = isset($reqTotal->total) ? $reqTotal->total : (isset($reqTotal->results) && is_array($reqTotal->results) ? count($reqTotal->results) : 0);
				$totalPages = ceil($totalData/$this->limit);
				
			}else{

				$table = array();
			}

		}
	
		/*=============================================
    	Devolver la tabla en formato HTML
    	=============================================*/

    	$HTMLTable = "";


    	if(!empty($table)){

    		foreach(json_decode(json_encode($table),true) as $key => $value){

				$HTMLTable .= '<tr>
						<td>'.($key+1+$startAt).'</td>';

						if ($this->rolAdmin == "superadmin" || $module->editable_module == 1){

							$HTMLTable .= '<td>
		    					<div class="form-check formCheck">
		    						<input class="form-check-input checkItem" type="checkbox" idItem="'.base64_encode($value["id_".$module->suffix_module]).'">
		    					</div>
		    				</td>';

		    			}

					    foreach ($module->columns as $index => $item){

							if ($item->visible_column == 1 && !($module->title_module == "cashs" && $item->title_column == "status_cash")){
								
    							$HTMLTable .= '<td>';

								/*=============================================
								Contenido tipo Imagen
								=============================================*/

								if($item->type_column == "image"){

									$imgSrc = $value[$item->title_column];
									if ($module->title_module == "products" && $item->title_column == "img_product") {
										$imgSrc = TemplateController::fallbackProductImage($value["sku_product"] ?? '', $value["title_product"] ?? '', $value["img_product"] ?? '');
									}
									if (empty($imgSrc) || $imgSrc === 'NULL' || $imgSrc === 'null') {
										$imgSrc = '/views/assets/img/multimedia.png';
									}

									$HTMLTable .= '<a href="'.urldecode($imgSrc).'" target="_blank">
										<img src="'.urldecode($imgSrc).'" class="rounded" style="width:60px; height:60px; object-fit: cover; object-position:center;">
									</a>';

								/*=============================================
								Contenido tipo Video
								=============================================*/

								}else if($item->type_column == "video"){

									$HTMLTable .= '<a href="'.urldecode($value[$item->title_column]).'" target="_blank">
										<img src="/views/assets/img/video.png" class="rounded" style="width:60px; height:60px; object-fit: cover; object-position:center;">
									</a>';

								/*=============================================
								Contenido tipo otros Archivos
								=============================================*/

								}else if($item->type_column == "file"){

									$HTMLTable .= '<a href="'.urldecode($value[$item->title_column]).'" target="_blank">
										<img src="/views/assets/img/file.png" class="rounded" style="width:60px; height:60px; object-fit: cover; object-position:center;">
									</a>';


								/*=============================================
								Contenido tipo Boleano
								=============================================*/

								}else if($item->type_column == "boolean"){

									if($value[$item->title_column] == 1){	

										$checked = 'checked';
										$label = "ON";
									
									}else{

										$checked = '';
										$label = "OFF";
									}

									if ($this->rolAdmin == "superadmin" || $module->editable_module == 1){

										$HTMLTable .= '<div class="form-check form-switch">
										<input class="form-check-input px-3 changeBoolean" type="checkbox" id="mySwtich" '.$checked.' idItem="'.base64_encode($value["id_".$module->suffix_module]).'" table="'.$module->title_module.'" suffix="'.$module->suffix_module.'" column="'.$item->title_column.'">
										<label class="form-check-label ps-1 align-middle" for="mySwitch">'.$label.'</label>
										</div>';

									}else{

										$HTMLTable .= '<label class="form-check-label ps-1 align-middle" for="mySwitch">'.$label.'</label>';
									}

								}else if($item->type_column == "array"){

							    	$typeArray = explode(",",urldecode($value[$item->title_column]));

							    	foreach ($typeArray as $num => $elem){
								
										$HTMLTable .= '<span class="badge badge-sm badge-default rounded bg-dark py-1 px-2 mx-1 mt-1 border small">'.TemplateController::reduceText($elem,25).'</span>';

									}

								/*=============================================
								Contenido tipo Objetos
								=============================================*/

								}else if($item->type_column == "object"){

							    	$typeJSON = json_decode(urldecode($value[$item->title_column]));

							    	foreach ($typeJSON as $num => $elem){

							    		$HTMLTable .= '<span class="badge badge-sm badge-default rounded py-1 px-2 mx-1 mt-1 border text-dark text-uppercase small">'.$num.': '.$elem.'</span>';

							    	}

							    /*=============================================
								Contenido tipo Enlace
								=============================================*/

								}else if($item->type_column == "link"){

							    	$HTMLTable .= '<a href="'.$value[$item->title_column].'" target="_blank" class="badge badge-default border rounded bg-indigo">'.TemplateController::reduceText(urldecode($value[$item->title_column]), 20).'</a>';

								/*=============================================
								Contenido tipo Color
								=============================================*/

								}else if($item->type_column == "color"){

							    	$HTMLTable .= '<div class="rounded" style="width:25px; height:25px; background:'.urldecode($value[$item->title_column]).'"></div>';

							    /*=============================================
								Contenido tipo Double
								=============================================*/

								}else if($item->type_column == "money"){

							    	$HTMLTable .= 'Bs'.number_format(urldecode($value[$item->title_column]),2);

								/*=============================================
								Contenido tipo Relaciones
								=============================================*/

								}else if($item->type_column == "relations" || $item->title_column == "id_client_order"){

									if($item->title_column == "id_client_order"){
										$urlClient = "clients?linkTo=id_client&equalTo=".$value[$item->title_column];
										$clientResp = CurlController::request($urlClient,"GET",array());
										if(isset($clientResp->status) && $clientResp->status == 200 && !empty($clientResp->results)){
											$HTMLTable .= urldecode($clientResp->results[0]->name_client);
											if(isset($clientResp->results[0]->surname_client)){
												$HTMLTable .= ' ' . urldecode($clientResp->results[0]->surname_client);
											}
										} else {
											$HTMLTable .= $value[$item->title_column]; 
										}
									}else if(isset($item->matrix_column) && $item->matrix_column != null && $value[$item->title_column] > 0){

										$url = "relations?rel=modules,pages&type=module,page&linkTo=type_module,title_module&equalTo=tables,".$item->matrix_column."&select=url_page,suffix_module";
										$method = "GET";
										$array = array();

										$relationReq = CurlController::request($url,$method,$fields);
										
										if(isset($relationReq->status) && $relationReq->status == 200 && is_array($relationReq->results) && count($relationReq->results) > 0){
											$urlPage = $relationReq->results[0]->url_page;
											$suffixModule = $relationReq->results[0]->suffix_module;

											$url = $item->matrix_column.'?linkTo=id_'.$suffixModule."&equalTo=".$value[$item->title_column];
											$relation = CurlController::request($url,$method,$fields);
											
											if(isset($relation->status) && $relation->status == 200 && is_array($relation->results) && count($relation->results) > 0){
												$arrayRelation  = (array)$relation->results[0];
												$HTMLTable .= '<a href="'.$urlPage.'/manage/'.base64_encode($value[$item->title_column]).'" target="_blank" class="badge badge-default border rounded bg-indigo">'.urldecode($arrayRelation[array_keys($arrayRelation)[1]]).'</a>';
											} else {
												$HTMLTable .= $value[$item->title_column];
											}
										} else {
											$HTMLTable .= $value[$item->title_column];
										}

									}else{

										$HTMLTable .= $value[$item->title_column]; 

									}

								/*=============================================
								Contenido tipo Órden
								=============================================*/

								}else if($item->type_column == "order"){

									$HTMLTable .= '<input type="number" class="form-control form-control-sm rounded changeOrder" value="'.$value[$item->title_column].'" style="width:55px" idItem="'.base64_encode($value["id_".$module->suffix_module]).'" table="'.$module->title_module.'" suffix="'.$module->suffix_module.'" column="'.$item->title_column.'">';

								}else if($item->type_column == "pos"){

									$HTMLTable .= '<a href="/pos?order='.urldecode($value[$item->title_column]).'" style="color:inherit">'.urldecode($value[$item->title_column]).'</a>';

								}else if($item->type_column == "stock"){

									if($value[$item->title_column] < 50){

										$colorStock = "bg-maroon";
									}

										if($value[$item->title_column] >= 50 && $value[$item->title_column] < 100){

										$colorStock = "bg-indigo";
									}

									if($value[$item->title_column] >= 100){

										$colorStock = "bg-teal";
									}

									$HTMLTable .= '<span class="badge badge-sm badge-default '.$colorStock.' rounded py-1 px-3 mx-1 mt-1 text-uppercase small">'.$value[$item->title_column].'</span>';

								}else{

	        						$HTMLTable .= TemplateController::reduceText(urldecode($value[$item->title_column]),25); 

	        					}

	        					$HTMLTable .= '</td>';

	        				}		
			
						}

				 		if ($this->rolAdmin == "superadmin" || $module->editable_module == 1){

							$HTMLTable .= '<td class="text-center">';
							if ($module->title_module == "cashs" && (int)$value["status_cash"] === 1) {
								$expectedCash = isset($value["diff_cash"]) ? number_format((float)$value["diff_cash"], 2, '.', '') : "0.00";
								$HTMLTable .= '<button type="button" class="btn btn-sm btn-dark rounded closeCash me-1" idItem="'.base64_encode($value["id_".$module->suffix_module]).'" table="'.$module->title_module.'" suffix="'.$module->suffix_module.'" column="status_cash" diffCash="'.htmlspecialchars($expectedCash, ENT_QUOTES, 'UTF-8').'">Cerrar</button>';
							}
							$urlPage = isset($module->url_page) ? $module->url_page : "";
							$HTMLTable .= '<a href="/'.$urlPage.'/manage/'.base64_encode($value["id_".$module->suffix_module]).'/copy" class="btn btn-sm text-dark rounded m-0 p-0 border-0">
		    						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16">
									  <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
									</svg>
		    					</a>
			    					<a href="/'.$urlPage.'/manage/'.base64_encode($value["id_".$module->suffix_module]).'" class="btn btn-sm text-primary rounded m-0 p-0 border-0">
			    						<i class="bi bi-pencil-square"></i>
			    					</a>';
									if($module->title_module != "cashs" || ($this->rolAdmin == "superadmin" || $this->rolAdmin == "admin")){
			    					$HTMLTable .= '<button type="button" class="btn btn-sm text-maroon rounded m-0 p-0 border-0 deleteItem" idItem="'.base64_encode($value["id_".$module->suffix_module]).'" table="'.$module->title_module.'" suffix="'.$module->suffix_module.'">
			    						<i class="bi bi-trash"></i>
			    					</button>';
									}
			    				$HTMLTable .= '</td>';

    					}else{


    						if($module->title_module == "orders"){

    							$HTMLTable .= '<td class="text-center">
    							<a href="/reports?id_order='.base64_encode($value["id_".$module->suffix_module]).'" class="btn btn-sm text-danger rounded m-0 p-0 border-0" target="_blank">
			    						<i class="bi bi-filetype-pdf"></i>
			    					</a>
			    				</td>';
    						}
    					}

				$HTMLTable .= '</tr>';

		
			}

    	}

    	$response = array(

    		"HTMLTable" => $HTMLTable,
    		"totalData" => $totalData,
    		"totalPages" => $totalPages
    	);

    	echo json_encode($response);


	}

	/*=============================================
    Cambiar estado Boleano
    =============================================*/
    public $boolChange;
    public $idItemChange;
    public $tableChange;
	public $suffixChange;
	public $columnChange;
	public $endCashChange;
	public $diffCashChange;
	public $idItemCashClose;
	public $tableCashClose;
	public $suffixCashClose;
	public $columnCashClose;

	public function changeBooleanItems(){

		$idItems = explode(",", $this->idItemChange);
		$countChange = 0;

		foreach ($idItems as $key => $value) {

			if($this->boolChange == "false" || $this->boolChange == 0){

    			$this->boolChange = 0;
    		
    		}else{

    			$this->boolChange = 1;
    		}

    		$url = $this->tableChange."?id=".base64_decode($value)."&nameId=id_".$this->suffixChange."&token=".$this->token."&table=admins&suffix=admin";
    		$method = "PUT";
    		$fields = $this->columnChange."=".$this->boolChange;

    		$updateItem = CurlController::request($url,$method,$fields);

    		if($updateItem->status == 200){

    			$countChange++;

    			if($countChange == count($idItems)){

    				echo 200;
    			}
    		}  		

		}
	}

	/*=============================================
    Cerrar caja
    =============================================*/

	public function closeCashItem(){

		$url = $this->tableCashClose."?id=".base64_decode($this->idItemCashClose)."&nameId=id_".$this->suffixCashClose."&token=".$this->token."&table=admins&suffix=admin";
		$method = "PUT";
		$endCash = trim((string)$this->endCashChange);
		$diffCash = trim((string)$this->diffCashChange);

		if($diffCash === ""){
			$diffCash = "0";
		}



		// Recuperar datos de la caja para calcular gastos e ingresos (solo ventana de esta sesión)
		$idCashDecoded = base64_decode($this->idItemCashClose);
		$cashUrl = "cashs?linkTo=id_cash&equalTo=" . $idCashDecoded . "&select=id_cash,start_cash,date_created_cash,id_office_cash,date_start_cash,date_end_cash,status_cash";
		$cashGet = CurlController::request($cashUrl,"GET",array());

		$startCash = 0;
		$cashOffice = isset($_SESSION["admin"]->id_office_admin) ? (int) $_SESSION["admin"]->id_office_admin : 0;
		$cashSessionStart = date("Y-m-d")." 00:00:00";
		$cashSessionEnd = date("Y-m-d H:i:s");

		if(isset($cashGet->status) && $cashGet->status == 200 && !empty($cashGet->results)){
			$cashItem = $cashGet->results[0];
			$startCash = isset($cashItem->start_cash) ? $cashItem->start_cash : 0;
			$cashOffice = isset($cashItem->id_office_cash) ? (int) $cashItem->id_office_cash : $cashOffice;
			$cashRow = json_decode(json_encode($cashItem), true);
			list($cashSessionStart, $cashSessionEnd) = TemplateController::cashSessionTimeBounds($cashRow);
		}

		// Calcular gastos (bills) solo entre apertura y cierre de esta caja
		$totalBills = 0;
		$urlBills = TemplateController::billsSessionApiUrl($cashOffice, $cashSessionStart, $cashSessionEnd);
		$bills = CurlController::request($urlBills,"GET",array());
		if(isset($bills->status) && $bills->status == 200){
			foreach ($bills->results as $key => $value) {
				$totalBills += $value->cost_bill;
			}
		}

		// Calcular ingresos en efectivo (orders) en la misma ventana
		$totalOrders = 0;
		$urlOrders = TemplateController::ordersSessionApiUrl($cashOffice, $cashSessionStart, $cashSessionEnd);
		$orders = CurlController::request($urlOrders,"GET",array());
		if(isset($orders->status) && $orders->status == 200){
			foreach ($orders->results as $key => $value) {
				$s = isset($value->status_order) ? (string) $value->status_order : "";
				if($s === "Completada"){
					$totalOrders += (float) $value->total_order;
				}
			}
		}

		$calculatedDiff = (float)$startCash + (float)$totalOrders - (float)$totalBills;
		$gapCash = (float)$endCash - (float)$calculatedDiff;

		$fields = $this->columnCashClose."=0&end_cash=".urlencode($endCash).
			"&gap_cash=".urlencode(number_format($gapCash, 2, '.', '')).
			"&date_end_cash=".urlencode(date("Y-m-d H:i:s")).
			"&bills_cash=".urlencode(number_format($totalBills,2,'.','')).
			"&money_cash=".urlencode(number_format($totalOrders,2,'.','')).
			"&diff_cash=".urlencode(number_format($calculatedDiff,2,'.',''));

		$updateItem = CurlController::request($url,$method,$fields);

		if(isset($updateItem->status) && $updateItem->status == 200){

			echo 200;

		}

	}

	/*=============================================
    Abrir caja
    =============================================*/

	public $startCashOpen;
	public $idOfficeOpen;
	public $idAdminOpen;

	public function openCashItem(){

		$today = date("Y-m-d");
		$now   = date("Y-m-d H:i:s");

		// Verificar si ya existe una caja abierta para esta sucursal
		$urlCheck = "cashs?linkTo=id_office_cash,status_cash&equalTo=".$this->idOfficeOpen.",1&select=id_cash";
		$existing = CurlController::request($urlCheck, "GET", array());
		if(isset($existing->status) && $existing->status == 200 && !empty($existing->results)){
			echo "already_open";
			return;
		}

		$tokenSession = isset($_SESSION["admin"]->token_admin) ? $_SESSION["admin"]->token_admin : $this->token;

		$url    = "cashs?token=".$tokenSession."&table=admins&suffix=admin";
		$method = "POST";
		$fields = array(
			"start_cash"         => (float)$this->startCashOpen,
			"bills_cash"         => 0,
			"money_cash"         => 0,
			"diff_cash"          => 0,
			"status_cash"        => 1,
			"date_start_cash"    => $now,
			"id_admin_cash"      => $this->idAdminOpen,
			"id_office_cash"     => $this->idOfficeOpen,
			"date_created_cash"  => $today,
		);

		$result = CurlController::request($url, $method, $fields);

		if(isset($result->status) && $result->status == 200){
			echo 200;
		} else {
			echo "error";
		}

	}

	/*=============================================
    Cambiar selección
    =============================================*/
    public $itemSelect;
    public $idItemSelect;
    public $tableSelect;
	public $suffixSelect;
	public $columnSelect;

	public function changeSelectItems(){

		$idItems = explode(",", $this->idItemSelect);
		$countSelect = 0;

		foreach ($idItems as $key => $value) {

    		$url = $this->tableSelect."?id=".base64_decode($value)."&nameId=id_".$this->suffixSelect."&token=".$this->token."&table=admins&suffix=admin";
    		$method = "PUT";
    		$fields = $this->columnSelect."=".$this->itemSelect;

    		$updateItem = CurlController::request($url,$method,$fields);

    		if($updateItem->status == 200){

    			$countSelect++;

    			if($countSelect == count($idItems)){

    				echo 200;
    			}
    		}  		

		}
	}

	/*=============================================
    Cambiar orden
    =============================================*/

    public $numOrder;
    public $idItemOrder;
    public $tableOrder;
	public $suffixOrder;
	public $columnOrder;

	public function changeOrderItems(){

		$url = $this->tableOrder."?id=".base64_decode($this->idItemOrder)."&nameId=id_".$this->suffixOrder."&token=".$this->token."&table=admins&suffix=admin";
		$method = "PUT";
		$fields = $this->columnOrder."=".$this->numOrder;

		$updateItem = CurlController::request($url,$method,$fields);

		if($updateItem->status == 200){

			echo 200;
			
		}  		
	
	}

}

/*=============================================
Variables POST
=============================================*/ 

if(isset($_POST["idItemDelete"])){

	$ajax = new DynamicTablesController();
    $ajax -> idItemDelete = $_POST["idItemDelete"] ?? null;
    $ajax -> tableDelete = $_POST["tableDelete"] ?? null;
    $ajax -> suffixDelete = $_POST["suffixDelete"] ?? null;
    $ajax -> token = $_POST["token"] ?? null;  
    $ajax -> deleteItems();

}

/*=============================================
Devolver tabla filtrada
=============================================*/

if(isset($_POST["contentModule"])){

	$ajax = new DynamicTablesController();
	$ajax = new DynamicTablesController();
    $ajax -> contentModule = (isset($_POST["contentModule"]) && $_POST["contentModule"] !== "undefined") ? $_POST["contentModule"] : null;
    $ajax -> orderBy = (isset($_POST["orderBy"]) && $_POST["orderBy"] !== "undefined") ? $_POST["orderBy"] : null;  
    $ajax -> orderMode = (isset($_POST["orderMode"]) && $_POST["orderMode"] !== "undefined") ? $_POST["orderMode"] : null; 
    $ajax -> limit = (isset($_POST["limit"]) && $_POST["limit"] !== "undefined") ? $_POST["limit"] : 10; 
    $ajax -> page = (isset($_POST["page"]) && $_POST["page"] !== "undefined") ? $_POST["page"] : 1;
    $ajax -> rolAdmin = (isset($_POST["rolAdmin"]) && $_POST["rolAdmin"] !== "undefined") ? $_POST["rolAdmin"] : null;  
    $ajax -> search = (isset($_POST["search"]) && $_POST["search"] !== "undefined") ? $_POST["search"] : null;  
    $ajax -> between1 = (isset($_POST["between1"]) && $_POST["between1"] !== "undefined") ? $_POST["between1"] : null;  
    $ajax -> between2 = (isset($_POST["between2"]) && $_POST["between2"] !== "undefined") ? $_POST["between2"] : null; 
    $ajax -> idOffice = (isset($_POST["idOffice"]) && $_POST["idOffice"] !== "undefined") ? $_POST["idOffice"] : 0;
    $ajax -> loadAjaxTable();

}


/*=============================================
Cambiar estado Boleano
=============================================*/

if(isset($_POST["tableChange"])){

    $ajax = new DynamicTablesController();
    $ajax -> boolChange = $_POST["boolChange"] ?? null;
    $ajax -> idItemChange = $_POST["idItemChange"] ?? null;
    $ajax -> tableChange = $_POST["tableChange"] ?? null;
    $ajax -> suffixChange = $_POST["suffixChange"] ?? null;
    $ajax -> columnChange = $_POST["columnChange"] ?? null;
    $ajax -> token = $_POST["token"] ?? null;  
    $ajax -> changeBooleanItems();

}

/*=============================================
Cerrar caja
=============================================*/

if(isset($_POST["tableCashClose"])){

	$ajax = new DynamicTablesController();
	$ajax -> endCashChange = $_POST["endCashChange"];
	$ajax -> diffCashChange = $_POST["diffCashChange"];
	$ajax -> idItemCashClose = $_POST["idItemCashClose"];
	$ajax -> tableCashClose = $_POST["tableCashClose"];
	$ajax -> suffixCashClose = $_POST["suffixCashClose"];
	$ajax -> columnCashClose = $_POST["columnCashClose"];
	$ajax -> token = $_POST["token"];
	$ajax -> closeCashItem();

}

/*=============================================
Abrir caja
=============================================*/

if(isset($_POST["tableCashOpen"])){

	$ajax = new DynamicTablesController();
	$ajax -> startCashOpen = $_POST["startCashOpen"];
	$ajax -> idOfficeOpen  = isset($_SESSION["admin"]->id_office_admin) ? (int)$_SESSION["admin"]->id_office_admin : 0;
	$ajax -> idAdminOpen   = isset($_SESSION["admin"]->id_admin) ? (int)$_SESSION["admin"]->id_admin : 0;
	$ajax -> token         = isset($_POST["token"]) ? $_POST["token"] : "";
	$ajax -> openCashItem();

}

/*=============================================
Cambiar selección
=============================================*/

if(isset($_POST["tableSelect"])){

    $ajax = new DynamicTablesController();
    $ajax -> itemSelect = $_POST["itemSelect"] ?? null;
    $ajax -> idItemSelect = $_POST["idItemSelect"] ?? null;
    $ajax -> tableSelect = $_POST["tableSelect"] ?? null;
    $ajax -> suffixSelect = $_POST["suffixSelect"] ?? null;
    $ajax -> columnSelect = $_POST["columnSelect"] ?? null;
    $ajax -> token = $_POST["token"] ?? null;  
    $ajax -> changeSelectItems();

}

/*=============================================
Cambiar orden
=============================================*/

if(isset($_POST["tableOrder"])){

    $ajax = new DynamicTablesController();
    $ajax -> numOrder = $_POST["numOrder"];
    $ajax -> idItemOrder = $_POST["idItemOrder"];
    $ajax -> tableOrder = $_POST["tableOrder"];
    $ajax -> suffixOrder = $_POST["suffixOrder"];
    $ajax -> columnOrder = $_POST["columnOrder"];
    $ajax -> token = $_POST["token"];  
    $ajax -> changeOrderItems();

}
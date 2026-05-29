<?php 

class DynamicController{

	/*=============================================
	Gestión de datos dinámicos
	=============================================*/	

	public function manage(){

		if(isset($_POST["module"])){

			echo '<script>

				fncMatPreloader("on");
			    fncSweetAlert("loading", "Procesando...", "");

			</script>';

			$module = json_decode($_POST["module"]);

			/*=============================================
			Editar datos
			=============================================*/

			if(isset($_POST["idItem"])){

				/*=============================================
				Actualizar datos
				=============================================*/

				$url = $module->title_module."?id=".base64_decode($_POST["idItem"])."&nameId=id_".$module->suffix_module."&token=".$_SESSION["admin"]->token_admin."&table=admins&suffix=admin";
				$method = "PUT";
				$fields = "";
				$count = 0;

				foreach ($module->columns as $key => $value) {

					$fieldValue = $_POST[$value->title_column] ?? "";
					$normalizedValue = trim((string)$fieldValue);
					$skipEmptyDateField = $normalizedValue === "" && in_array($value->type_column, ["date", "datetime", "timestamp"]);

					// Evitar que campos calculados de caja se reciban desde el formulario
					$forbiddenCashFields = array("bills_cash","money_cash","diff_cash","status_cash","date_end_cash");
					if($module->title_module == "cashs" && in_array($value->title_column, $forbiddenCashFields)){
						$count++;
						continue;
					}



					if($normalizedValue === ""){
						if($value->title_column == "id_admin_".$module->suffix_module && isset($_SESSION["admin"]->id_admin)){
							$normalizedValue = (string)$_SESSION["admin"]->id_admin;
						}else if($value->title_column == "id_office_".$module->suffix_module && isset($_SESSION["admin"]->id_office_admin)){
							$normalizedValue = (string)$_SESSION["admin"]->id_office_admin;
						}else if(in_array($value->type_column, ["int","double","boolean","number","money"])){
							$normalizedValue = "0";
						}
					}

					if($skipEmptyDateField){
					}else if($value->type_column == "password" && $normalizedValue !== ""){

						$fields.= $value->title_column."=".crypt($normalizedValue,'$2a$07$azybxcags23425sdg23sdfhsd$')."&";

					}else if($value->type_column == "email"){

						$fields.= $value->title_column."=".$normalizedValue."&";

					}else if(in_array($value->type_column, ["date", "datetime", "timestamp"], true)){

						$fields.= $value->title_column."=".rawurlencode($normalizedValue)."&";

					}else{
					
						$fields.= $value->title_column."=".urlencode($normalizedValue)."&";

					}
					
					$count++;

					if($count == count($module->columns)){

						$fields = substr($fields,0,-1);

						// Seguridad: forzar que no cambien de sucursal al editar si no son superadmin
						$rolAdminEdit = isset($_SESSION["admin"]->rol_admin) ? $_SESSION["admin"]->rol_admin : "";
						$officeForceTables = ["cashs" => "id_office_cash", "bills" => "id_office_bill", "orders" => "id_office_order", "clients" => "id_office_client", "products" => "id_office_product", "purchases" => "id_office_purchase"];
						if(isset($officeForceTables[$module->title_module]) && $rolAdminEdit !== "superadmin" && isset($_SESSION["admin"]->id_office_admin)){
							$forceOfficeField = $officeForceTables[$module->title_module];
							// Reemplazar o añadir el campo forzadamente en el string x-www-form-urlencoded
							$pattern = '/(&|^)'.$forceOfficeField.'=[^&]*/';
							if(preg_match($pattern, $fields)){
								$fields = preg_replace($pattern, '$1'.$forceOfficeField.'='.(int)$_SESSION["admin"]->id_office_admin, $fields);
							}else{
								$fields .= '&'.$forceOfficeField.'='.(int)$_SESSION["admin"]->id_office_admin;
							}
						}

						$update = CurlController::request($url,$method,$fields);

						if(isset($update->status) && $update->status == 200){

							$urlEscaped = json_encode("/".$module->url_page, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
							
							echo '

								<script>

									fncMatPreloader("off");
									fncFormatInputs();
									fncSweetAlert("success","El registro ha sido actualizado con éxito", "");
									setTimeout(function(){ window.location='.$urlEscaped.'; }, 1000);
									

								</script>

							';
							
						}else{

							$errorData = json_encode([
								'status' => isset($update->status) ? $update->status : 'unknown',
								'comment' => isset($update->comment) ? $update->comment : '',
								'results' => isset($update->results) ? $update->results : null
							], JSON_UNESCAPED_UNICODE);

							echo '

								<script>

									fncMatPreloader("off");
									fncFormatInputs();
									fncSweetAlert("error","Error al actualizar el registro", "");
									var apiError = ' . $errorData . ';
									console.error("Error en la petición API:", apiError);

								</script>

							';

						}
					}
				
				}


			}else{
		
				/*=============================================
				Crear datos
				=============================================*/

				$url = $module->title_module."?token=".$_SESSION["admin"]->token_admin."&table=admins&suffix=admin";
				$method = "POST";
				$fields = array();
				$count = 0;
				$isProduct = ($module->title_module == "products");
				$isBill = ($module->title_module == "bills");
				$officeField = "id_office_".$module->suffix_module;
				$multipleOffices = array();
				$useMultipleOffices = false;

				// Primero obtener la sucursal del formulario si es un gasto
				$billOffice = 0;
				if($isBill){
					// Buscar el campo de sucursal en el formulario
					foreach ($module->columns as $key => $value) {
						if($value->title_column == $officeField && isset($_POST[$officeField])){
							$billOffice = trim($_POST[$officeField]);
							break;
						}
					}
					// Si no se encontró en el formulario, usar la sucursal del admin
					if($billOffice == 0 || empty($billOffice)){
						$billOffice = $_SESSION["admin"]->id_office_admin;
					}
				}
				// Seguridad: si el usuario NO es superadmin, siempre usar su propia sucursal para gastos
				$rolAdminBill = isset($_SESSION["admin"]->rol_admin) ? $_SESSION["admin"]->rol_admin : "";
				if($isBill && $rolAdminBill !== "superadmin" && isset($_SESSION["admin"]->id_office_admin)){
					$billOffice = $_SESSION["admin"]->id_office_admin;
				}


				/*=============================================
				Validar que exista caja abierta antes de crear un gasto
				=============================================*/

				if($isBill && $billOffice > 0){
					
					// Verificar que exista una caja abierta para hoy
				$urlCash = "cashs?linkTo=date_created_cash,status_cash,id_office_cash&equalTo=".date("Y-m-d").",1,".$billOffice."&select=*";
				$methodCash = "GET";
				$fieldsCash = array();

				$cash = CurlController::request($urlCash,$methodCash,$fieldsCash);
				
				if(!isset($cash) || !isset($cash->status)){
					
					echo '

						<script>

							fncMatPreloader("off");
							fncFormatInputs();
							fncSweetAlert("error","No se pudo conectar al servidor. Intenta de nuevo.", "");

						</script>

					';
					
					return;

				}else if(isset($cash->status) && $cash->status == 404){
					
					echo '

						<script>

							fncMatPreloader("off");
							fncFormatInputs();
							fncSweetAlert("error","No hay caja abierta el día de hoy. Debe abrir una caja antes de registrar gastos", "");

						</script>

					';
					
					return;
					
					}else{

						/*=============================================
						Validar que la caja del día anterior haya sido cerrada
						=============================================*/

						$yesterday = date("Y-m-d", strtotime(date("Y-m-d")."- 1 days"));
						
						$urlCashYesterday = "cashs?linkTo=date_created_cash,status_cash,id_office_cash&equalTo=".$yesterday.",1,".$billOffice."&select=status_cash"; 
						$methodCashYesterday = "GET";
						$fieldsCashYesterday = array();

						$cashYesterday = CurlController::request($urlCashYesterday,$methodCashYesterday,$fieldsCashYesterday);

						if(isset($cashYesterday->status) && $cashYesterday->status == 200){
							echo '

								<script>

									fncMatPreloader("off");
									fncFormatInputs();
									fncSweetAlert("error","No ha cerrado la caja del día anterior. Debe cerrar la caja anterior antes de registrar gastos", "");

								</script>

							';
							
							return;

						}

					}

				}

				/*=============================================
				No permitir nueva caja si ya hay una abierta en la misma sucursal
				=============================================*/

				if($module->title_module == "cashs"){

					$newCashOffice = 0;
					foreach ($module->columns as $key => $value) {
						if($value->title_column == $officeField && isset($_POST[$officeField])){
							$newCashOffice = (int) trim((string) $_POST[$officeField]);
							break;
						}
					}
					if($newCashOffice <= 0 && isset($_SESSION["admin"]->id_office_admin)){
						$newCashOffice = (int) $_SESSION["admin"]->id_office_admin;
					}

				// Seguridad: si el usuario NO es superadmin, forzar su propia sucursal (evita el bug de caja en sucursal incorrecta)
				$rolAdminCash = isset($_SESSION["admin"]->rol_admin) ? $_SESSION["admin"]->rol_admin : "";
				if($rolAdminCash !== "superadmin" && isset($_SESSION["admin"]->id_office_admin)){
					$newCashOffice = (int) $_SESSION["admin"]->id_office_admin;
				}

					if($newCashOffice > 0){
						$urlOpenCash = "cashs?linkTo=id_office_cash,status_cash&equalTo=".$newCashOffice.",1&select=id_cash";
						$openCashRows = CurlController::request($urlOpenCash, "GET", array());

						if(isset($openCashRows->status) && $openCashRows->status == 200 && !empty($openCashRows->results)){

							echo '

								<script>

									fncMatPreloader("off");
									fncFormatInputs();
									fncSweetAlert("error","Ya existe una caja abierta en esta sucursal. Debe cerrarla antes de crear una nueva.", "");

								</script>

							';

							return;

						}
					}
				}

				/*=============================================
				Detectar si es un producto y si se deben usar múltiples sucursales
				=============================================*/
				
				if($isProduct && isset($_POST[$officeField])){
					
					$officeValue = trim((string)($_POST[$officeField] ?? ""));
					
					// Verificar si se seleccionó "all" para todas las sucursales
					if($officeValue == "all" || $officeValue == "0"){
						
						// Obtener todas las sucursales
						$urlOffices = "offices?select=id_office";
						$methodOffices = "GET";
						$fieldsOffices = array();
						
						$getOffices = CurlController::request($urlOffices,$methodOffices,$fieldsOffices);
						
						if(isset($getOffices->status) && $getOffices->status == 200){
							
							foreach ($getOffices->results as $office) {
								$multipleOffices[] = $office->id_office;
							}
							$useMultipleOffices = true;
						}
						
					// Verificar si se enviaron múltiples IDs separados por comas
					}else if(strpos($officeValue, ',') !== false){
						
						$multipleOffices = array_map('trim', explode(',', $officeValue));
						$useMultipleOffices = true;
						
					}
					
					// Seguridad: si NO es superadmin, deshabilitar múltiples sucursales y forzar la suya
					$rolAdminProd = isset($_SESSION["admin"]->rol_admin) ? $_SESSION["admin"]->rol_admin : "";
					if($rolAdminProd !== "superadmin" && isset($_SESSION["admin"]->id_office_admin)){
						$useMultipleOffices = false;
						$multipleOffices = array();
					}
					
				}

				foreach ($module->columns as $key => $value) {

					$fieldValue = $_POST[$value->title_column] ?? "";
					$normalizedValue = trim((string)$fieldValue);
					$skipEmptyDateField = $normalizedValue === "" && in_array($value->type_column, ["date", "datetime", "timestamp"]);

					if($normalizedValue === ""){
						if($value->title_column == "id_admin_".$module->suffix_module && isset($_SESSION["admin"]->id_admin)){
							$normalizedValue = (string)$_SESSION["admin"]->id_admin;
						}else if($value->title_column == "id_office_".$module->suffix_module && isset($_SESSION["admin"]->id_office_admin)){
							$normalizedValue = (string)$_SESSION["admin"]->id_office_admin;
						}else if(in_array($value->type_column, ["int","double","boolean","number","money"])){
							$normalizedValue = "0";
						}
					}

					if($skipEmptyDateField){
					}else if($value->type_column == "password"){

						$fields[$value->title_column] = crypt($normalizedValue,'$2a$07$azybxcags23425sdg23sdfhsd$');
					
					}else if($value->type_column == "email"){

						$fields[$value->title_column] = $normalizedValue;
					}else if(in_array($value->type_column, ["date", "datetime", "timestamp"], true)){

						$fields[$value->title_column] = $normalizedValue;
					}else{
					
						$fields[$value->title_column] = urlencode($normalizedValue);

					}
					
					$count++;

					if($count == count($module->columns)){

						$fields["date_created_".$module->suffix_module] = date("Y-m-d");
						// Solo tablas con columna date_start_* (p. ej. cashs); bills y el resto no la tienen y la API rechaza campos inexistentes
						if($module->title_module == "cashs"){
							$fields["date_start_".$module->suffix_module] = date("Y-m-d H:i:s");
						}
						// Asegurar que los campos calculados de caja se inicialicen o se calculen en servidor
						if($module->title_module == "cashs"){
							$fields["bills_cash"] = 0;
							$fields["money_cash"] = 0;
							$fields["diff_cash"] = 0;
							$fields["status_cash"] = 1;
						}

					// Seguridad: para caja, gastos y ventas, el campo de sucursal siempre viene del usuario si no es superadmin
					$rolAdminSave = isset($_SESSION["admin"]->rol_admin) ? $_SESSION["admin"]->rol_admin : "";
					// Nota: 'products' se excluye porque ahora es catálogo global (sin id_office_product)
					$officeForceTables = ["cashs" => "id_office_cash", "bills" => "id_office_bill", "orders" => "id_office_order", "clients" => "id_office_client", "purchases" => "id_office_purchase"];
					if(isset($officeForceTables[$module->title_module]) && $rolAdminSave !== "superadmin" && isset($_SESSION["admin"]->id_office_admin)){
						$forceOfficeField = $officeForceTables[$module->title_module];
						$fields[$forceOfficeField] = (int)$_SESSION["admin"]->id_office_admin;
					}
					// Para productos: forzar id_office_product = 0 (catálogo global)
					if($isProduct){
						$fields["id_office_product"] = 0;
					}

						/*=============================================
						Crear producto en múltiples sucursales si está configurado
						=============================================*/
						
						/*=============================================
						Si es un producto: crear una sola fila global en products
						y luego crear filas en product_inventory por sucursal
						=============================================*/
						if($isProduct){

							// Determinar sucursales destino
							if($useMultipleOffices && !empty($multipleOffices)){
								$targetOffices = $multipleOffices;
							}else{
								$singleOffice = isset($_POST[$officeField]) ? (int)trim($_POST[$officeField]) : 0;
								if($singleOffice <= 0 && isset($_SESSION["admin"]->id_office_admin)){
									$singleOffice = (int)$_SESSION["admin"]->id_office_admin;
								}
								$targetOffices = $singleOffice > 0 ? [$singleOffice] : [];
							}

							// Crear el producto global en products (id_office_product = 0)
							$save = CurlController::request($url,"POST",$fields);

							if(isset($save->status) && $save->status == 200){

								$newProductId = $save->results->lastId;
								$invSuccessCount = 0;
								$invErrorCount = 0;

								// Crear filas en product_inventory para cada sucursal
								foreach($targetOffices as $offId){
									$urlInv = "product_inventory?token=".$_SESSION["admin"]->token_admin."&table=admins&suffix=admin";
									$invFields = array(
										"id_product_inventory" => (int)$newProductId,
										"id_office_inventory"  => (int)$offId,
										"stock_inventory"      => 0,
										"status_inventory"     => 1,
										"date_created_inventory" => date("Y-m-d")
									);
									$saveInv = CurlController::request($urlInv, "POST", $invFields);
									if(isset($saveInv->status) && $saveInv->status == 200){
										$invSuccessCount++;
									}else{
										$invErrorCount++;
									}
								}

								$sucursalesCount = count($targetOffices);
								$message = "✅ Producto creado exitosamente";
								if($sucursalesCount > 0){
									$message .= " en ".$invSuccessCount." sucursal".($invSuccessCount > 1 ? "es" : "");
									if($invErrorCount > 0){
										$message .= "\n⚠️ Hubo ".$invErrorCount." error(es) al asignar a algunas sucursales.";
									}
								}

								$messageEscaped = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
								$urlEscaped = json_encode("/".$module->url_page, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

								echo '
									<script>
										fncMatPreloader("off");
										fncFormatInputs();
										fncSweetAlert("success",'.$messageEscaped.', "");
										setTimeout(function(){ window.location='.$urlEscaped.'; }, 1500);
									</script>
								';

							}else{

								$errorData = json_encode([
									'status'  => isset($save->status)  ? $save->status  : 'unknown',
									'comment' => isset($save->comment) ? $save->comment : '',
									'results' => isset($save->results) ? $save->results : null
								], JSON_UNESCAPED_UNICODE);
								echo '
									<script>
										fncMatPreloader("off");
										fncFormatInputs();
										fncSweetAlert("error","Error al guardar el producto", "");
										var apiError = '.$errorData.';
										console.error("Error en la petición API:", apiError);
									</script>
								';
							}

						}else{
							// Crear registro normal para otras tablas (no productos)
							if($useMultipleOffices && !empty($multipleOffices)){
								$successCount = 0;
								$errorCount = 0;
								foreach ($multipleOffices as $officeId) {
									$officeFields = $fields;
									$officeFields[$officeField] = $officeId;
									$save = CurlController::request($url,$method,$officeFields);
									if(isset($save->status) && $save->status == 200){ $successCount++; }else{ $errorCount++; }
								}
								$message = $successCount > 0 ? "✅ Registros creados en ".$successCount." sucursal(es)" : "";
								if($successCount > 0){
									$messageEscaped = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
									$urlEscaped = json_encode("/".$module->url_page, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
									echo '<script>fncMatPreloader("off");fncFormatInputs();fncSweetAlert("success",'.$messageEscaped.',"");setTimeout(function(){ window.location='.$urlEscaped.'; },1500);</script>';
								}else{
									echo '<script>fncMatPreloader("off");fncFormatInputs();fncSweetAlert("error","Error al guardar en las sucursales","");</script>';
								}
							}else{
								// Una sola sucursal - tablas normales (no productos)
								$save = CurlController::request($url,$method,$fields);

								if(isset($save->status) && $save->status == 200){

									if($isBill){
										$officeSync = (int)$billOffice;
										if($officeSync > 0 && isset($_SESSION["admin"]->token_admin)){
											self::syncOpenCashTotalsForOffice($_SESSION["admin"]->token_admin, $officeSync);
										}
									}

									$urlEscaped = json_encode("/".$module->url_page, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
									echo '
										<script>
											fncMatPreloader("off");
											fncFormatInputs();
											fncSweetAlert("success","El registro ha sido guardado con éxito", "");
											setTimeout(function(){ window.location='.$urlEscaped.'; }, 1000);
										</script>
									';

								}else{

									$errorData = json_encode([
										'status'  => isset($save->status)  ? $save->status  : 'unknown',
										'comment' => isset($save->comment) ? $save->comment : '',
										'results' => isset($save->results) ? $save->results : null
									], JSON_UNESCAPED_UNICODE);
									echo '
										<script>
											fncMatPreloader("off");
											fncFormatInputs();
											fncSweetAlert("error","Error al guardar el registro", "");
											var apiError = '.$errorData.';
											console.error("Error en la petición API:", apiError);
										</script>
									';
								}
							} // fin else: una sola sucursal tablas normales
						} // fin else: tabla no es producto
					}
				}


			}

		}

	}

	/*=============================================
	Tras crear un gasto o completar una venta POS: actualizar totales en la caja abierta
	(bills_cash, money_cash, diff_cash) con gastos y ventas completadas en la ventana de esa sesión (misma sucursal).
	=============================================*/
	public static function syncOpenCashTotalsForOffice($token, $officeId){

		$today = date("Y-m-d");
		$urlCashToday = "cashs?linkTo=date_created_cash,status_cash,id_office_cash&equalTo=".$today.",1,".$officeId."&select=id_cash,start_cash,date_created_cash,id_office_cash,date_start_cash,date_end_cash,status_cash";
		$cashResp = CurlController::request($urlCashToday, "GET", array());

		$row = null;
		if(isset($cashResp->status) && $cashResp->status == 200 && !empty($cashResp->results)){
			$row = $cashResp->results[0];
		}

		if($row === null){
			$urlCashOpen = "cashs?linkTo=id_office_cash,status_cash&equalTo=".$officeId.",1&select=id_cash,start_cash,date_created_cash,id_office_cash,date_start_cash,date_end_cash,status_cash";
			$cashResp = CurlController::request($urlCashOpen, "GET", array());
			if(!isset($cashResp->status) || $cashResp->status != 200 || empty($cashResp->results)){
				return;
			}
			foreach($cashResp->results as $r){
				$dc = isset($r->date_created_cash) ? substr(trim((string) $r->date_created_cash), 0, 10) : "";
				if($dc === $today){
					$row = $r;
					break;
				}
			}
			if($row === null){
				foreach($cashResp->results as $r){
					if($row === null || (int) $r->id_cash > (int) $row->id_cash){
						$row = $r;
					}
				}
			}
		}

		if($row === null){
			return;
		}
		$idCash = isset($row->id_cash) ? (int)$row->id_cash : 0;
		if($idCash <= 0){
			return;
		}

		$startCash = isset($row->start_cash) ? (float)$row->start_cash : 0.0;
		$cashOffice = isset($row->id_office_cash) ? (int)$row->id_office_cash : (int)$officeId;

		$cashRow = json_decode(json_encode($row), true);
		list($tStart, $tEnd) = TemplateController::cashSessionTimeBounds($cashRow);

		$totalBills = 0.0;
		$urlBills = TemplateController::billsSessionApiUrl($cashOffice, $tStart, $tEnd);
		$bills = CurlController::request($urlBills, "GET", array());
		if(isset($bills->status) && $bills->status == 200 && !empty($bills->results)){
			foreach($bills->results as $b){
				$totalBills += isset($b->cost_bill) ? (float)$b->cost_bill : 0.0;
			}
		}

		$totalOrders = 0.0;
		$urlOrders = TemplateController::ordersSessionApiUrl($cashOffice, $tStart, $tEnd);
		$orders = CurlController::request($urlOrders, "GET", array());
		if(isset($orders->status) && $orders->status == 200 && !empty($orders->results)){
			foreach($orders->results as $o){
				$s = isset($o->status_order) ? (string) $o->status_order : "";
				if($s === "Completada"){
					$totalOrders += isset($o->total_order) ? (float)$o->total_order : 0.0;
				}
			}
		}

		$diffCash = $startCash + $totalOrders - $totalBills;

		$putUrl = "cashs?id=".$idCash."&nameId=id_cash&token=".$token."&table=admins&suffix=admin";
		$putFields = "bills_cash=".rawurlencode(number_format($totalBills, 2, ".", ""))
			."&money_cash=".rawurlencode(number_format($totalOrders, 2, ".", ""))
			."&diff_cash=".rawurlencode(number_format($diffCash, 2, ".", ""));

		CurlController::request($putUrl, "PUT", $putFields);
	}

}

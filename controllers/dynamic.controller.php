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

					if($normalizedValue === ""){
						if($value->title_column == "id_admin_".$module->suffix_module && isset($_SESSION["admin"]->id_admin)){
							$normalizedValue = (string)$_SESSION["admin"]->id_admin;
						}else if($value->title_column == "id_office_".$module->suffix_module && isset($_SESSION["admin"]->id_office_admin)){
							$normalizedValue = (string)$_SESSION["admin"]->id_office_admin;
						}else if(in_array($value->type_column, ["int","double","boolean"])){
							$normalizedValue = "0";
						}
					}

					if($value->type_column == "password" && $normalizedValue !== ""){

						$fields.= $value->title_column."=".crypt($normalizedValue,'$2a$07$azybxcags23425sdg23sdfhsd$')."&";

					}else if($value->type_column == "email"){

						$fields.= $value->title_column."=".$normalizedValue."&";

					}else{
					
						$fields.= $value->title_column."=".urlencode($normalizedValue)."&";

					}
					
					$count++;

					if($count == count($module->columns)){

						$fields = substr($fields,0,-1);

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

							echo '

								<script>

									fncMatPreloader("off");
									fncFormatInputs();
									fncSweetAlert("error","Error al actualizar el registro", "");
									console.error("Error en la petición API:", '.json_encode($update).');

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

				/*=============================================
				Validar que exista caja abierta antes de crear un gasto
				=============================================*/

				if($isBill && $billOffice > 0){
					
					// Verificar que exista una caja abierta para hoy
					$urlCash = "cashs?linkTo=date_created_cash,status_cash,id_office_cash&equalTo=".date("Y-m-d").",1,".$billOffice."&select=status_cash";
					$methodCash = "GET";
					$fieldsCash = array();

					$cash = CurlController::request($urlCash,$methodCash,$fieldsCash);
					
					if(isset($cash->status) && $cash->status == 404){
						
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
					
				}

				foreach ($module->columns as $key => $value) {

					$fieldValue = $_POST[$value->title_column] ?? "";
					$normalizedValue = trim((string)$fieldValue);

					if($normalizedValue === ""){
						if($value->title_column == "id_admin_".$module->suffix_module && isset($_SESSION["admin"]->id_admin)){
							$normalizedValue = (string)$_SESSION["admin"]->id_admin;
						}else if($value->title_column == "id_office_".$module->suffix_module && isset($_SESSION["admin"]->id_office_admin)){
							$normalizedValue = (string)$_SESSION["admin"]->id_office_admin;
						}else if(in_array($value->type_column, ["int","double","boolean"])){
							$normalizedValue = "0";
						}
					}

					if($value->type_column == "password"){

						$fields[$value->title_column] = crypt($normalizedValue,'$2a$07$azybxcags23425sdg23sdfhsd$');
					
					}else if($value->type_column == "email"){

						$fields[$value->title_column] = $normalizedValue;
					}else{
					
						$fields[$value->title_column] = urlencode($normalizedValue);

					}
					
					$count++;

					if($count == count($module->columns)){

						$fields["date_created_".$module->suffix_module] = date("Y-m-d");

						/*=============================================
						Crear producto en múltiples sucursales si está configurado
						=============================================*/
						
						if($useMultipleOffices && !empty($multipleOffices)){
							
							$successCount = 0;
							$errorCount = 0;
							
							foreach ($multipleOffices as $officeId) {
								
								// Crear una copia de los campos y asignar la sucursal
								$officeFields = $fields;
								$officeFields[$officeField] = $officeId;
								
								$save = CurlController::request($url,$method,$officeFields);
								
								if(isset($save->status) && $save->status == 200){
									$successCount++;
								}else{
									$errorCount++;
								}
								
							}
							
							if($successCount > 0){
								
								$message = "✅ Producto creado exitosamente en ".$successCount." sucursal".($successCount > 1 ? "es" : "");
								if($errorCount > 0){
									$message .= "\n⚠️ Hubo ".$errorCount." error".($errorCount > 1 ? "es" : "")." al guardar en algunas sucursales.";
								}else{
									$message .= "\n✨ El producto está disponible en todas las sucursales seleccionadas.";
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
								
								echo '

									<script>

										fncMatPreloader("off");
										fncFormatInputs();
									    fncSweetAlert("error","Error al guardar el producto en las sucursales", "");
										

									</script>

								';
								
							}
							
						}else{
							
							// Crear registro normal (una sola sucursal)
							$save = CurlController::request($url,$method,$fields);

							if(isset($save->status) && $save->status == 200){

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

								echo '

									<script>

										fncMatPreloader("off");
										fncFormatInputs();
										fncSweetAlert("error","Error al guardar el registro", "");
										console.error("Error en la petición API:", '.json_encode($save).');

									</script>

								';

							}
							
						}
					}
				
				}

			}

		}

	}

}
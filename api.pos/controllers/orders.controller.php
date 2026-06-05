<?php 

class OrdersController{

	/*=============================================
	Gestionar Órdenes
	=============================================*/

	public function manageOrder(){

		if(isset($_POST["idOrderPay"])){

			// echo '<script>
			// 	fncMatPreloader("on");
			// 	fncSweetAlert("loading", "Procesando la orden...", "");
			// </script>';

			// Debug: Log session admin info
			error_log("Session Admin Token: " . $_SESSION["admin"]->token_admin);
			error_log("Session Admin ID: " . $_SESSION["admin"]->id_admin);

			// API deshabilitada - Simulamos respuesta exitosa
			$modeTV = "demo";
			
			// Simulamos respuesta exitosa de la API
			$getStatus = (object) array(
				"status" => 200,
				"results" => "API deshabilitada - Modo simulación"
			);

			// if($getStatus->status == 200 && 
			// 	$getStatus->results->quality != 1 &&
			// 	$getStatus->results->resolution->status != 200 && 
			// 	$getStatus->results->events == 0){

			// 	echo'<div class="alert alert-danger mt-3 p-3 rounded alertPos">Error: Validar con Título Valor la Facturación Electrónica</div>

			// 	<script>

			// 		fncMatPreloader("off");
			// 		fncSweetAlert("close", "", "");
			// 		fncFormatInputs();
				 
			// 	</script>

			// 	';

			// 	return;
			// }

			$url = "orders?id=".$_POST["idOrderPay"]."&nameId=id_order&token=".$_SESSION["admin"]->token_admin."&table=admins&suffix=admin";
			$method = "PUT";
			$fields = array(
				"method_order" => $_POST["methodPay"],
				"transfer_order" => $_POST["transferPay"],
				"status_order" => "Completada",
				"date_order" => date("Y-m-d H:i:s")
			);

			if (isset($_POST["qrRefOrder"])) {
				$fields["qr_ref_order"] = $_POST["qrRefOrder"];
			}
			if (isset($_POST["methodDetail"])) {
				$fields["method_detail_order"] = $_POST["methodDetail"];
			}

			// $fields = array(
			// 	"status_order" => "Pendiente"
			// );

			$fields = http_build_query($fields);

			$updateOrder = CurlController::request($url,$method,$fields);

			if(!isset($updateOrder) || !isset($updateOrder->status)){
				echo'<div class="alert alert-danger mt-3 p-3 rounded alertPos">Error: No hay respuesta de la API (timeout)</div>

				<script>
					/*POS_ORDER_PAY_RESULT*/

					fncMatPreloader("off");
					fncSweetAlert("error", "No hay respuesta del servidor. Intenta de nuevo.", "").then(function(result) {
						if (result) {
							window.location.href = "/pos";
						}
					});
					
					fncFormatInputs();
				 
				</script>

				';
				return;
			}

			if($updateOrder->status == 200){

				/*=============================================
				Obtener información de la orden
				=============================================*/

				$url = "orders?linkTo=id_order&equalTo=".$_POST["idOrderPay"]."&select=transaction_order,id_office_order,total_order";
				$method = "GET";
				$fields = array();

				$getOrder = CurlController::request($url,$method,$fields);

				if(!isset($getOrder) || !isset($getOrder->status)){
					echo'<div class="alert alert-danger mt-3 p-3 rounded alertPos">Error: No hay respuesta de la API al obtener orden</div>

					<script>
						/*POS_ORDER_PAY_RESULT*/

						fncMatPreloader("off");
						fncSweetAlert("error", "Error al completar la orden. Intenta de nuevo.", "").then(function(result) {
							if (result) {
								window.location.href = "/pos";
							}
						});
						
						fncFormatInputs();
					 
					</script>

					';
					return;
				}
				
				$transactionOrder = "";
				$orderOfficeForCash = null;
				if($getOrder->status == 200 && !empty($getOrder->results)){
					$transactionOrder = $getOrder->results[0]->transaction_order;
					if(isset($getOrder->results[0]->id_office_order)){
						$orderOfficeForCash = (int) $getOrder->results[0]->id_office_order;
					}
				}

				/*=============================================
				Actualizar las ventas como completadas
				=============================================*/

				$url = "relations?rel=sales,orders&type=sale,order&linkTo=id_order_sale&equalTo=".$_POST["idOrderPay"]."&select=*";
				$method = "GET";
				$fields = array();

				$getSales = CurlController::request($url,$method,$fields);

				if(!isset($getSales) || !isset($getSales->status) || $getSales->status != 200){
					echo'<div class="alert alert-danger mt-3 p-3 rounded alertPos">Error: No hay ventas asociadas a la orden</div>

					<script>
						/*POS_ORDER_PAY_RESULT*/

						fncMatPreloader("off");
						fncSweetAlert("error", "Error al obtener ventas. Intenta de nuevo.", "").then(function(result) {
							if (result) {
								window.location.href = "/pos";
							}
						});
						
						fncFormatInputs();
					 
					</script>

					';
					return;
				}

				if(empty($getSales->results)){

					/*=============================================
					Caso: Orden completada sin ventas
					=============================================*/

					if(empty($transactionOrder)){
						$transactionOrder = "#" . $_POST["idOrderPay"];
					}

					self::syncCashTotalsAfterPosPayment($orderOfficeForCash);

					echo '

					<script>
						/*POS_ORDER_PAY_RESULT*/

						fncMatPreloader("off");
						fncSweetAlert("close", "", "");
						
						Swal.fire({
							icon: "success",
							title: "Correcto",
							text: "La órden '.$transactionOrder.' ha sido completada con éxito",
							confirmButtonText: "OK"
						}).then(function(result) {
							if (result.isConfirmed || result.value) {
								// Recargar la página
								window.location.href = "/pos";
							}
						});
						
						fncFormatInputs();
					 
					</script>

					';

				} else {

					$countSales = 0;
					$failedSales = 0;
					$arrayProducts = array();

					foreach ($getSales->results as $key => $value) {

						$url = "sales?id=".$value->id_sale."&nameId=id_sale&token=".$_SESSION["admin"]->token_admin."&table=admins&suffix=admin";
						$method = "PUT";
						$fields = array(
							"status_sale" => "Completada"
						);

						$fields = http_build_query($fields);

						$updateSale = CurlController::request($url,$method,$fields);

						if(isset($updateSale) && isset($updateSale->status) && $updateSale->status == 200){

							$countSales ++;

							/*=============================================
							Info de los productos
							=============================================*/

							$url = "products?linkTo=id_product&equalTo=".$value->id_product_sale;
							$method = "GET";
							$fields = array();

							$getProducts = CurlController::request($url,$method,$fields);	

							if(isset($getProducts) && isset($getProducts->status) && $getProducts->status == 200 && !empty($getProducts->results)){

								$product = $getProducts->results[0];

								/*=============================================
								Arreglo de productos
								=============================================*/

								array_push($arrayProducts, array(

									"title_product" => urldecode($product->title_product),
									"sku_product"=>  $product->sku_product,
									"unit_product"=> $product->unit_product,
									"qty_product"=> $value->qty_sale,
									"tax_type_product"=> $value->tax_type_sale,
									"tax_product"=> $value->tax_sale,
									"discount_product"=> $value->discount_sale,
									"subtotal_product"=> $value->subtotal_sale
								));
							
							}

						}else{
							$failedSales++;
						}

						// Si se han procesado todas las ventas (exitosas o no), finalizar
						if($countSales + $failedSales == count($getSales->results)){

								if($failedSales > 0){

									$revertFields = http_build_query(array(
										"method_order" => $_POST["methodPay"],
										"transfer_order" => $_POST["transferPay"],
										"status_order" => "Pendiente"
									));
									CurlController::request(
										"orders?id=".$_POST["idOrderPay"]."&nameId=id_order&token=".$_SESSION["admin"]->token_admin."&table=admins&suffix=admin",
										"PUT",
										$revertFields
									);

									echo '<div class="alert alert-danger mt-3 p-3 rounded alertPos">No se pudieron marcar como completadas todas las ventas de la orden ('.$failedSales.' de '.count($getSales->results).'). La orden se dejó en estado pendiente.</div>

									<script>
										/*POS_ORDER_PAY_RESULT*/

										fncMatPreloader("off");
										fncSweetAlert("close", "", "");
										fncSweetAlert("error", "No se completaron todas las líneas de venta. Revisa la conexión con la API e intenta de nuevo.", "").then(function(result) {
											if (result) {
												window.location.href = "/pos";
											}
										});
										fncFormatInputs();
									</script>
									';
									return;
								}

								/*=============================================
								Traer info de la Sucursal
								=============================================*/

								$url = "offices?linkTo=id_office&equalTo=".$_SESSION["admin"]->id_office_admin;
								$method = "GET";
								$fields = array();

								$getOffice = CurlController::request($url,$method,$fields);

								if($getOffice->status == 200){

									$office = $getOffice->results[0];
								}

								/*=============================================
								Módulo de facturación - Opcional
								Si el módulo de facturación no existe, 
								simplemente continuamos sin crear factura
								=============================================*/

								$invoiceCreated = false;

								// Intentar crear factura solo si el módulo está disponible
								// Primero verificamos si el cliente requiere factura
								$wantsInvoice = (isset($_POST["clientInvoice"]) && $_POST["clientInvoice"] == "yes") || (isset($_POST["invoice"]) && $_POST["invoice"] == "yes");
								
								if($wantsInvoice){

									/*=============================================
									El cliente es facturador
									=============================================*/

									$url = "clients?linkTo=id_client&equalTo=".$getSales->results[0]->id_client_order;
									$method = "GET";
									$fields = array();

									$getClient = CurlController::request($url,$method,$fields);

									if($getClient->status == 200){

										$client = $getClient->results[0];
										$customerDoc = $client->dni_client;
										$customerName = $client->name_client." ".$client->surname_client;
										$customerEmail = $client->email_client;
									}


								}else{

									$customerDoc = "222222222222";
									$customerName = "Consumidor Final";
									$customerEmail = "";

								}

								/*=============================================
								Intentar crear factura (opcional)
								=============================================*/

								try {
									
									// API deshabilitada - Simulamos respuesta exitosa de factura
									$setInvoice = (object) array(
										"status" => 200,
										"document" => "FAC" . date("Ymd") . rand(1000, 9999),
										"XmlDocumentKey" => "CUDE" . date("YmdHis") . rand(1000, 9999),
										"zip" => "archivo_factura_" . date("YmdHis") . ".zip",
										"results" => "Factura generada en modo simulación",
										"message" => "API deshabilitada"
									);

									// Debug: Log the simulated response
									error_log("Título Valor API Response (SIMULADA): " . json_encode($setInvoice));

									if(isset($setInvoice->status) && $setInvoice->status == 200){

										/*=============================================
										Creando la info de la factura
										=============================================*/
		
										$url = "invoices?token=".$_SESSION["admin"]->token_admin."&table=admins&suffix=admin";
										$method = "POST";
										$fields = array(
											"id_order_invoice" => $_POST["idOrderPay"],
											"type_invoice" => "Factura POS",
											"document_invoice" => $setInvoice->document,
											"cude_invoice" => $setInvoice->XmlDocumentKey,
											"zip_invoice" => $setInvoice->zip,
											"dian_invoice" => "https://catalogo-vpfe.dian.gov.co/document/searchqr?documentkey=".$setInvoice->XmlDocumentKey,
											"convert_invoice" => "/facturacion?idOrder=".$_POST["idOrderPay"]."&document=".$setInvoice->document."&cude=".$setInvoice->XmlDocumentKey,
											"fields_invoice" => json_encode($fields),
											"date_created_invoice" => date("Y-m-d")
										);

										$createInvoice = CurlController::request($url,$method,$fields);

										if($createInvoice->status == 200){
											$invoiceCreated = true;
											// Impresión deshabilitada - Modo simulación
											// $print = CurlController::ticketPrint($_POST["idOrderPay"],urlencode($_SESSION["admin"]->name_admin),$setInvoice->XmlDocumentKey);
											$print = (object) array("status" => 200, "message" => "Impresión deshabilitada");
										} else {
											// El módulo de facturación no está disponible, pero continuamos
											error_log("Error al crear factura (módulo no disponible): " . json_encode($createInvoice));
										}

									}

								} catch (Exception $e) {
									// El módulo de facturación no existe, continuamos sin factura
									error_log("Módulo de facturación no disponible: " . $e->getMessage());
								}
								
								} // Fin del if($wantsInvoice)

								/*=============================================
								Devolvemos respuesta al vendedor
								La orden ya está completada, independientemente 
								del estado de la factura
								=============================================*/

								// Obtener el número de transacción si no está disponible
								if(empty($transactionOrder) && !empty($getSales->results)){
									// Intentar obtener desde la relación orders si está disponible
									if(isset($getSales->results[0]->transaction_order)){
										$transactionOrder = $getSales->results[0]->transaction_order;
									} else {
										$transactionOrder = "#" . $_POST["idOrderPay"];
									}
								} else if(empty($transactionOrder)) {
									$transactionOrder = "#" . $_POST["idOrderPay"];
								}

								$cashOfficeHint = ($orderOfficeForCash !== null && $orderOfficeForCash > 0)
									? $orderOfficeForCash
									: null;
								if(($cashOfficeHint === null || $cashOfficeHint <= 0) && !empty($getSales->results[0]->id_office_order)){
									$cashOfficeHint = (int) $getSales->results[0]->id_office_order;
								}
								self::syncCashTotalsAfterPosPayment($cashOfficeHint);

								echo '

								<script>
									/*POS_ORDER_PAY_RESULT*/

									fncMatPreloader("off");
									fncSweetAlert("close", "", "");
									
									Swal.fire({
										icon: "success",
										title: "Correcto",
										text: "La órden '.$transactionOrder.' ha sido completada con éxito",
										confirmButtonText: "OK"
									}).then(function(result) {
										if (result.isConfirmed || result.value) {
											// Recargar la página
											window.location.href = "/pos";
										}
									});
									
									fncFormatInputs();
								 
								</script>

								';

							}

						}
				}


			}else{

				echo'<div class="alert alert-danger mt-3 p-3 rounded alertPos">Error al procesar la orden</div>

				<script>
					/*POS_ORDER_PAY_RESULT*/

					fncMatPreloader("off");
					fncSweetAlert("error", "Error al procesar la orden", "").then(function(result) {
						if (result) {
							// Recargar la página cuando el usuario confirme
							window.location.href = "/pos";
						}
					});
					
					fncFormatInputs();
				 
				</script>

				';

			}

		}

	}

	/*=============================================
	Actualizar caja abierta: mismos totales que tras un gasto (ventas Completadas en la ventana de sesión)
	=============================================*/
	private static function syncCashTotalsAfterPosPayment($officeHint = null){

		$office = ($officeHint !== null && (int) $officeHint > 0) ? (int) $officeHint : 0;

		if($office <= 0 && isset($_SESSION["admin"]->id_office_admin) && (int) $_SESSION["admin"]->id_office_admin > 0){
			$office = (int) $_SESSION["admin"]->id_office_admin;
		}

		if($office <= 0 && !empty($_POST["idOrderPay"])){
			$urlOrder = "orders?linkTo=id_order&equalTo=".$_POST["idOrderPay"]."&select=id_office_order";
			$rOrder = CurlController::request($urlOrder, "GET", array());
			if(isset($rOrder->status) && $rOrder->status == 200 && !empty($rOrder->results[0]->id_office_order)){
				$office = (int) $rOrder->results[0]->id_office_order;
			}
		}

		if($office <= 0 || empty($_SESSION["admin"]->token_admin)){
			return;
		}

		require_once __DIR__ . "/dynamic.controller.php";
		DynamicController::syncOpenCashTotalsForOffice($_SESSION["admin"]->token_admin, $office);
	}

}
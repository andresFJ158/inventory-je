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
				"status_order" => "Completada"
			);

			// $fields = array(
			// 	"status_order" => "Pendiente"
			// );

			$fields = http_build_query($fields);

			$updateOrder = CurlController::request($url,$method,$fields);

			if($updateOrder->status == 200){

				/*=============================================
				Obtener información de la orden
				=============================================*/

				$url = "orders?linkTo=id_order&equalTo=".$_POST["idOrderPay"]."&select=transaction_order";
				$method = "GET";
				$fields = array();

				$getOrder = CurlController::request($url,$method,$fields);
				
				$transactionOrder = "";
				if($getOrder->status == 200 && !empty($getOrder->results)){
					$transactionOrder = $getOrder->results[0]->transaction_order;
				}

				/*=============================================
				Actualizar las ventas como completadas
				=============================================*/

				$url = "relations?rel=sales,orders&type=sale,order&linkTo=id_order_sale&equalTo=".$_POST["idOrderPay"]."&select=*";
				$method = "GET";
				$fields = array();

				$getSales = CurlController::request($url,$method,$fields);

				if($getSales->status == 200){

					$countSales = 0;

					$arrayProducts = array();

					foreach ($getSales->results as $key => $value) {

						$url = "sales?id=".$value->id_sale."&nameId=id_sale&token=".$_SESSION["admin"]->token_admin."&table=admins&suffix=admin";
						$method = "PUT";
						$fields = array(
							"status_sale" => "Completada"
						);

						// $fields = array(
						// 	"status_sale" => "Pendiente"
						// );

						$fields = http_build_query($fields);

						$updateSale = CurlController::request($url,$method,$fields);

						if($updateSale->status == 200){

							$countSales ++;

							/*=============================================
							Info de los productos
							=============================================*/

							$url = "products?linkTo=id_product&equalTo=".$value->id_product_sale;
							$method = "GET";
							$fields = array();

							$getProducts = CurlController::request($url,$method,$fields);	

							if($getProducts->status == 200){

								$product = $getProducts->results[0];
							
							}

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

							if($countSales == count($getSales->results)){

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
								if(isset($_POST["clientInvoice"]) && $_POST["clientInvoice"] == "yes"){

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

								echo '

								<script>

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

				} else {
					
					/*=============================================
					Caso: Orden completada sin ventas
					=============================================*/

					// Obtener el número de transacción
					if(empty($transactionOrder)){
						$transactionOrder = "#" . $_POST["idOrderPay"];
					}

					echo '

					<script>

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


			}else{

				echo'<div class="alert alert-danger mt-3 p-3 rounded alertPos">Error al procesar la orden</div>

				<script>

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

}